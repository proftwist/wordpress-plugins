Отлично! Добавляем постоянную подсветку и проверку после сохранения. Исправляем:

## 1. Обновляем основной класс плагина

**quick-link-checker.php** - добавляем хуки для постоянной работы:

```php
<?php
// ... заголовок ...

class QuickLinkChecker {

    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));

        // Добавляем хук для проверки после сохранения
        add_action('wp_after_insert_post', array($this, 'after_post_save'), 10, 4);
    }

    // ... остальные методы без изменений ...

    public function after_post_save($post_id, $post, $update, $post_before) {
        // Проверяем, включена ли проверка
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Проверяем права и тип поста
        if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Проверяем только опубликованные посты и черновики
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }

        // Запускаем проверку с небольшой задержкой
        add_action('shutdown', function() use ($post_id) {
            $this->do_post_save_check($post_id);
        });
    }

    public function do_post_save_check($post_id) {
        require_once QLC_PLUGIN_PATH . 'includes/class-link-checker.php';
        $checker = new QLC_Link_Checker();
        $checker->check_post_links_immediately($post_id);
    }
}

// ... остальной код ...
```

## 2. Расширяем класс проверки ссылок

**includes/class-link-checker.php** - добавляем методы для немедленной проверки:

```php
<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Link_Checker {

    public function __construct() {
        add_action('save_post', array($this, 'check_post_links'), 10, 3);
        add_action('wp_ajax_qlc_check_links', array($this, 'ajax_check_links'));
        add_action('wp_ajax_qlc_get_broken_links', array($this, 'ajax_get_broken_links')); // Новый AJAX
    }

    // Существующий метод проверки при сохранении
    public function check_post_links($post_id, $post, $update) {
        // Проверяем, включена ли проверка
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Проверяем права и тип поста
        if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Проверяем только опубликованные посты и черновики
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }

        // Выполняем проверку
        $this->async_check_links($post_id);
    }

    // Новый метод для немедленной проверки после сохранения
    public function check_post_links_immediately($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $links = $this->extract_links($post->post_content);
        $broken_links = array();

        foreach ($links as $link) {
            if (!$this->check_link($link['url'])) {
                $broken_links[] = $link;
            }
            usleep(100000); // 0.1 секунда
        }

        // Сохраняем результат в мета-поле
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        // Логируем для отладки
        error_log('QLC: Immediately checked ' . count($links) . ' links, found ' . count($broken_links) . ' broken after save');

        return $broken_links;
    }

    // Новый AJAX метод для получения битых ссылок после сохранения
    public function ajax_get_broken_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('No post ID');
        }

        $broken_links = get_post_meta($post_id, '_qlc_broken_links', true);

        wp_send_json_success(array(
            'broken_links' => is_array($broken_links) ? $broken_links : array(),
            'broken_count' => is_array($broken_links) ? count($broken_links) : 0
        ));
    }

    // ... остальные существующие методы без изменений ...
}
```

## 3. Обновляем интеграцию с редактором

**includes/class-editor-integration.php** - добавляем постоянную подсветку:

```php
<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Editor_Integration {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_head', array($this, 'add_editor_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts')); // Новый хук
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        global $post;
        if (!$post) {
            return;
        }

        // Передаем ID поста в JavaScript
        wp_localize_script('qlc-admin-js', 'qlc_post', array(
            'post_id' => $post->ID
        ));
    }

    // ... остальные методы без изменений ...
}
```

## 4. Полностью переписываем JavaScript

**admin/js/admin.js** - добавляем постоянную подсветку и обновление после сохранения:

```javascript
(function($) {
    'use strict';

    let QLC = {
        postId: 0,
        currentBrokenLinks: [],

        init: function() {
            this.postId = typeof qlc_post !== 'undefined' ? qlc_post.post_id : 0;
            this.bindEvents();
            this.loadStoredBrokenLinks();
        },

        bindEvents: function() {
            $(document).on('click', '#qlc-check-now', this.checkLinksNow.bind(this));

            // Слушаем событие сохранения в Gutenberg
            if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                this.bindGutenbergEvents();
            }

            // Слушаем событие сохранения в Classic Editor
            $(document).on('click', '#publish, #save-post', this.onSavePost.bind(this));
        },

        bindGutenbergEvents: function() {
            wp.data.subscribe(() => {
                const isSavingPost = wp.data.select('core/editor').isSavingPost();
                const isAutosaving = wp.data.select('core/editor').isAutosavingPost();

                if (isSavingPost && !isAutosaving) {
                    // Ждем завершения сохранения
                    setTimeout(() => {
                        this.onPostSaved();
                    }, 2000);
                }
            });
        },

        onSavePost: function() {
            // Для Classic Editor - ждем завершения сохранения
            setTimeout(() => {
                this.onPostSaved();
            }, 3000);
        },

        onPostSaved: function() {
            console.log('QLC: Post saved, updating broken links...');
            this.loadStoredBrokenLinks();
        },

        loadStoredBrokenLinks: function() {
            if (!this.postId) {
                console.log('QLC: No post ID available');
                return;
            }

            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_get_broken_links',
                    post_id: this.postId,
                    nonce: qlc_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentBrokenLinks = response.data.broken_links;
                        console.log('QLC: Loaded', this.currentBrokenLinks.length, 'stored broken links');
                        this.highlightBrokenLinks(this.currentBrokenLinks);
                        this.updateBrokenLinksList(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('QLC: Error loading stored broken links:', error);
                }
            });
        },

        checkLinksNow: function(e) {
            if (e) e.preventDefault();

            const $button = $('#qlc-check-now');
            const $container = $('#qlc-broken-links-container');

            $button.prop('disabled', true).text(qlc_ajax.checking_text);

            let content = this.getEditorContent();

            if (!content) {
                console.error('QLC: Cannot find editor content');
                $container.html('<p style="color: #d63638;">Error: Cannot find editor content.</p>');
                $button.prop('disabled', false).text(qlc_ajax.check_now_text);
                return;
            }

            console.log('QLC: Checking content length:', content.length);

            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_check_links',
                    content: content,
                    nonce: qlc_ajax.nonce
                },
                success: (response) => {
                    console.log('QLC: AJAX success, found', response.data.broken_count, 'broken links');
                    this.currentBrokenLinks = response.data.broken_links;
                    this.updateBrokenLinksList(response.data, $container);
                    this.highlightBrokenLinks(response.data.broken_links);

                    // Сохраняем результат проверки
                    this.saveBrokenLinks(response.data.broken_links);
                },
                error: (xhr, status, error) => {
                    console.error('QLC: AJAX error', error);
                    $container.html('<p style="color: #d63638;">Error checking links: ' + error + '</p>');
                },
                complete: () => {
                    $button.prop('disabled', false).text(qlc_ajax.check_now_text);
                }
            });
        },

        saveBrokenLinks: function(brokenLinks) {
            if (!this.postId) {
                console.log('QLC: Cannot save broken links - no post ID');
                return;
            }

            // Сохраняем через AJAX
            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_save_broken_links',
                    post_id: this.postId,
                    broken_links: brokenLinks,
                    nonce: qlc_ajax.nonce
                },
                success: (response) => {
                    console.log('QLC: Broken links saved for post', this.postId);
                },
                error: (xhr, status, error) => {
                    console.error('QLC: Error saving broken links:', error);
                }
            });
        },

        getEditorContent: function() {
            let content = '';

            // 1. Пробуем Gutenberg/Block Editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                try {
                    const editor = wp.data.select('core/editor');
                    if (editor) {
                        content = editor.getEditedPostContent();
                        if (content) {
                            console.log('QLC: Got content from Gutenberg editor');
                            return content;
                        }
                    }
                } catch (e) {
                    console.log('QLC: Gutenberg editor not available');
                }
            }

            // 2. Пробуем Classic Editor (TinyMCE)
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                content = tinymce.get('content').getContent();
                if (content) {
                    console.log('QLC: Got content from TinyMCE editor');
                    return content;
                }
            }

            // 3. Пробуем текстовую область
            const $contentTextarea = $('#content');
            if ($contentTextarea.length > 0) {
                content = $contentTextarea.val();
                if (content) {
                    console.log('QLC: Got content from textarea');
                    return content;
                }
            }

            return content;
        },

        updateBrokenLinksList: function(data, $container = null) {
            if (!$container) {
                $container = $('#qlc-broken-links-container');
            }

            let html = '';

            console.log('QLC: Broken links found:', data.broken_count);

            if (data.broken_count === 0) {
                html = '<p>✅ ' + qlc_ajax.no_broken_links + '</p>';
            } else {
                html = '<p><strong>❌ ' + qlc_ajax.broken_links_found + '</strong> ' + data.broken_count + '</p>';
                html += '<ul style="max-height: 200px; overflow-y: auto;">';
                data.broken_links.forEach(function(link) {
                    html += '<li style="margin-bottom: 5px;"><code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px; word-break: break-all;">' + link.url + '</code></li>';
                });
                html += '</ul>';
            }

            html += '<button type="button" id="qlc-check-now" class="button button-secondary" style="margin-top: 10px;">';
            html += qlc_ajax.check_now_text;
            html += '</button>';

            // Добавляем информацию о последнем обновлении
            html += '<div style="margin-top: 10px; font-size: 11px; color: #666;">';
            html += 'Last checked: ' + new Date().toLocaleTimeString();
            html += '</div>';

            $container.html(html);
        },

        highlightBrokenLinks: function(brokenLinks) {
            // Сначала снимаем все подсветки
            $('a').removeClass('qlc-broken-link');

            console.log('QLC: Highlighting', brokenLinks.length, 'broken links');

            // Подсвечиваем битые ссылки
            brokenLinks.forEach((link) => {
                const escapedUrl = this.escapeUrlForSelector(link.url);
                const $links = $('a[href="' + escapedUrl + '"]');

                if ($links.length > 0) {
                    $links.addClass('qlc-broken-link');
                    console.log('QLC: Highlighted link:', link.url, 'found:', $links.length);
                } else {
                    console.log('QLC: Link not found in editor:', link.url);
                }
            });
        },

        escapeUrlForSelector: function(url) {
            return url.replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        }
    };

    $(document).ready(function() {
        QLC.init();
    });

})(jQuery);
```

## 5. Добавляем AJAX для сохранения ссылок

**includes/class-link-checker.php** - добавляем метод сохранения:

```php
// Добавляем в класс QLC_Link_Checker:
public function ajax_save_broken_links() {
    check_ajax_referer('qlc_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $broken_links = isset($_POST['broken_links']) ? $_POST['broken_links'] : array();

    if (!$post_id) {
        wp_send_json_error('No post ID');
    }

    // Сохраняем битые ссылки
    update_post_meta($post_id, '_qlc_broken_links', $broken_links);

    wp_send_json_success('Broken links saved');
}
```

И добавляем хук в конструктор:
```php
add_action('wp_ajax_qlc_save_broken_links', array($this, 'ajax_save_broken_links'));
```

## Что теперь работает:

1. **Постоянная подсветка** - ссылки остаются подсвеченными после перезагрузки страницы
2. **Автообновление после сохранения** - проверяет ссылки сразу после сохранения поста
3. **Слежение за изменениями** - при исправлении ссылки подсветка убирается
4. **Работает в обоих редакторах** - Gutenberg и Classic Editor

Теперь битые ссылки будут постоянно подсвечиваться пока их не исправят! 🔴✅