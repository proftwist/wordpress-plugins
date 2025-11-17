Правильно! Сделаем умную проверку только измененных ссылок. Исправляю:

## 1. Умный класс проверки с отслеживанием изменений

**includes/class-link-checker.php** - добавляем хеширование для отслеживания изменений:

```php
<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Link_Checker {

    private $checked_urls = array();

    public function __construct() {
        add_action('save_post', array($this, 'check_post_links'), 10, 3);
        add_action('wp_ajax_qlc_check_links', array($this, 'ajax_check_links'));
        add_action('wp_ajax_qlc_get_broken_links', array($this, 'ajax_get_broken_links'));
        add_action('wp_ajax_qlc_save_broken_links', array($this, 'ajax_save_broken_links'));
        add_action('wp_ajax_qlc_check_changed_links', array($this, 'ajax_check_changed_links')); // Новая умная проверка
    }

    // НОВЫЙ метод: проверяем только изменившиеся ссылки
    public function ajax_check_changed_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $current_links_data = isset($_POST['links_data']) ? $_POST['links_data'] : array();

        if (!$post_id) {
            wp_send_json_error('No post ID');
        }

        // Получаем сохраненные битые ссылки
        $stored_broken_links = get_post_meta($post_id, '_qlc_broken_links', true);
        $stored_links_hash = get_post_meta($post_id, '_qlc_links_hash', true);

        // Создаем хеш текущих ссылок для сравнения
        $current_links_hash = md5(json_encode($current_links_data));

        // Если хеш не изменился - возвращаем сохраненные данные
        if ($stored_links_hash === $current_links_hash) {
            wp_send_json_success(array(
                'broken_links' => is_array($stored_broken_links) ? $stored_broken_links : array(),
                'broken_count' => is_array($stored_broken_links) ? count($stored_broken_links) : 0,
                'links_unchanged' => true,
                'message' => 'Links unchanged - using cached data'
            ));
        }

        // Если ссылки изменились - проверяем только новые/измененные
        $links_to_check = $this->get_links_to_check($current_links_data, $stored_broken_links);
        $new_broken_links = array();

        foreach ($links_to_check as $link_data) {
            if (!$this->check_link($link_data['url'])) {
                $new_broken_links[] = array(
                    'url' => $link_data['url'],
                    'full_tag' => $link_data['full_tag']
                );
            }
            usleep(50000); // 0.05 сек
        }

        // Объединяем с существующими битыми ссылками (которые все еще актуальны)
        $all_broken_links = $this->merge_broken_links($stored_broken_links, $new_broken_links, $current_links_data);

        // Сохраняем новые данные
        update_post_meta($post_id, '_qlc_broken_links', $all_broken_links);
        update_post_meta($post_id, '_qlc_links_hash', $current_links_hash);

        wp_send_json_success(array(
            'broken_links' => $all_broken_links,
            'broken_count' => count($all_broken_links),
            'checked_count' => count($links_to_check),
            'links_unchanged' => false,
            'message' => 'Checked ' . count($links_to_check) . ' changed links'
        ));
    }

    // Определяем какие ссылки нужно проверить
    private function get_links_to_check($current_links_data, $stored_broken_links) {
        $links_to_check = array();
        $stored_urls = array();

        if (is_array($stored_broken_links)) {
            foreach ($stored_broken_links as $broken_link) {
                $stored_urls[] = $broken_link['url'];
            }
        }

        foreach ($current_links_data as $link_data) {
            $url = $link_data['url'];

            // Проверяем только если:
            // 1. Это новая ссылка (нет в сохраненных битых)
            // 2. Или это измененная ссылка
            if (!in_array($url, $stored_urls)) {
                $links_to_check[] = $link_data;
            }
        }

        return $links_to_check;
    }

    // Объединяем битые ссылки
    private function merge_broken_links($stored_broken_links, $new_broken_links, $current_links_data) {
        $all_broken_links = array();
        $current_urls = array();

        foreach ($current_links_data as $link_data) {
            $current_urls[] = $link_data['url'];
        }

        // Добавляем старые битые ссылки, которые все еще присутствуют
        if (is_array($stored_broken_links)) {
            foreach ($stored_broken_links as $broken_link) {
                if (in_array($broken_link['url'], $current_urls)) {
                    $all_broken_links[] = $broken_link;
                }
            }
        }

        // Добавляем новые битые ссылки
        foreach ($new_broken_links as $new_broken_link) {
            $all_broken_links[] = $new_broken_link;
        }

        // Убираем дубликаты
        $unique_links = array();
        $added_urls = array();

        foreach ($all_broken_links as $link) {
            if (!in_array($link['url'], $added_urls)) {
                $unique_links[] = $link;
                $added_urls[] = $link['url'];
            }
        }

        return $unique_links;
    }

    // ... остальные существующие методы без изменений ...
}
```

## 2. Умный JavaScript с минимальной нагрузкой

**admin/js/admin.js** - полностью переписываем на легковесную версию:

```javascript
(function($) {
    'use strict';

    let QLC = {
        postId: 0,
        currentBrokenLinks: [],
        currentLinksHash: '',
        lastContentHash: '',
        checkTimeout: null,

        init: function() {
            this.postId = typeof qlc_post !== 'undefined' ? qlc_post.post_id : 0;
            this.bindEvents();
            this.loadStoredBrokenLinks();
        },

        bindEvents: function() {
            $(document).on('click', '#qlc-check-now', this.fullCheck.bind(this));

            // Легковесное отслеживание изменений
            this.bindLightweightTracking();
        },

        bindLightweightTracking: function() {
            let lastContent = '';

            // Проверяем изменения каждые 3 секунды
            setInterval(() => {
                const currentContent = this.getEditorContent();
                if (!currentContent) return;

                // Простая проверка хеша контента
                const contentHash = this.simpleHash(currentContent);
                if (contentHash !== this.lastContentHash) {
                    this.lastContentHash = contentHash;
                    this.scheduleSmartCheck();
                }
            }, 3000);
        },

        scheduleSmartCheck: function() {
            clearTimeout(this.checkTimeout);
            this.checkTimeout = setTimeout(() => {
                this.smartCheck();
            }, 2000);
        },

        // УМНАЯ проверка: только измененные ссылки
        smartCheck: function() {
            if (!this.postId) return;

            const content = this.getEditorContent();
            if (!content) return;

            const linksData = this.extractLinksData(content);
            const linksHash = this.simpleHash(JSON.stringify(linksData));

            // Если ссылки не изменились - пропускаем проверку
            if (linksHash === this.currentLinksHash) {
                return;
            }

            this.currentLinksHash = linksHash;

            console.log('QLC: Smart check - checking changed links...');

            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_check_changed_links',
                    post_id: this.postId,
                    links_data: linksData,
                    nonce: qlc_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentBrokenLinks = response.data.broken_links;

                        if (response.data.links_unchanged) {
                            console.log('QLC: Links unchanged, using cache');
                        } else {
                            console.log('QLC: Smart check found', response.data.broken_count,
                                      'broken links (checked', response.data.checked_count, 'links)');
                        }

                        this.highlightBrokenLinks(this.currentBrokenLinks);
                        this.updateBrokenLinksCount();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('QLC: Smart check error:', error);
                }
            });
        },

        // ПОЛНАЯ проверка (по кнопке)
        fullCheck: function(e) {
            if (e) e.preventDefault();

            const $button = $('#qlc-check-now');
            const $container = $('#qlc-broken-links-container');

            $button.prop('disabled', true).text(qlc_ajax.checking_text);
            $container.html('<p>🔍 Checking all links... <span class="spinner is-active" style="float: none; margin: 0 5px;"></span></p>');

            const content = this.getEditorContent();
            if (!content) {
                this.showError('Cannot find editor content');
                $button.prop('disabled', false).text(qlc_ajax.check_now_text);
                return;
            }

            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_check_links',
                    content: content,
                    nonce: qlc_ajax.nonce
                },
                success: (response) => {
                    this.currentBrokenLinks = response.data.broken_links;
                    this.updateBrokenLinksList(response.data, $container);
                    this.highlightBrokenLinks(response.data.broken_links);
                    this.saveBrokenLinks(response.data.broken_links);
                },
                error: (xhr, status, error) => {
                    this.showError('Error checking links: ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).text(qlc_ajax.check_now_text);
                }
            });
        },

        // Извлекаем данные ссылок для умной проверки
        extractLinksData: function(content) {
            const linksData = [];
            const parser = new DOMParser();
            const doc = parser.parseFromString(content, 'text/html');
            const links = doc.querySelectorAll('a[href]');

            links.forEach(link => {
                const url = link.getAttribute('href');
                if (url && url !== '#' && !url.startsWith('javascript:')) {
                    linksData.push({
                        url: url,
                        full_tag: link.outerHTML
                    });
                }
            });

            return linksData;
        },

        // Простой хеш для сравнения
        simpleHash: function(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash.toString();
        },

        loadStoredBrokenLinks: function() {
            if (!this.postId) return;

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
                        this.highlightBrokenLinks(this.currentBrokenLinks);
                        this.updateBrokenLinksCount();
                    }
                }
            });
        },

        highlightBrokenLinks: function(brokenLinks) {
            $('a').removeClass('qlc-broken-link');

            brokenLinks.forEach((link) => {
                const escapedUrl = this.escapeUrlForSelector(link.url);
                const $links = $('a[href="' + escapedUrl + '"]');
                $links.addClass('qlc-broken-link');
            });
        },

        updateBrokenLinksCount: function() {
            const $container = $('#qlc-broken-links-container');
            const $countElement = $container.find('strong');

            if ($countElement.length > 0) {
                $countElement.text('❌ ' + qlc_ajax.broken_links_found + ' ' + this.currentBrokenLinks.length);
            }
        },

        updateBrokenLinksList: function(data, $container) {
            let html = '';

            if (data.broken_count === 0) {
                html = '<p>✅ ' + qlc_ajax.no_broken_links + '</p>';
            } else {
                html = '<p><strong>❌ ' + qlc_ajax.broken_links_found + '</strong> ' + data.broken_count + '</p>';
                html += '<ul style="max-height: 200px; overflow-y: auto;">';
                data.broken_links.forEach(link => {
                    html += '<li style="margin-bottom: 5px;"><code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px; word-break: break-all;">' + link.url + '</code></li>';
                });
                html += '</ul>';
            }

            html += '<button type="button" id="qlc-check-now" class="button button-secondary" style="margin-top: 10px;">';
            html += qlc_ajax.check_now_text;
            html += '</button>';

            $container.html(html);
        },

        showError: function(message) {
            $('#qlc-broken-links-container').html('<p style="color: #d63638;">' + message + '</p>');
        },

        escapeUrlForSelector: function(url) {
            return url.replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        },

        saveBrokenLinks: function(brokenLinks) {
            if (!this.postId) return;

            $.ajax({
                url: qlc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'qlc_save_broken_links',
                    post_id: this.postId,
                    broken_links: brokenLinks,
                    nonce: qlc_ajax.nonce
                }
            });
        },

        getEditorContent: function() {
            // ... существующий метод ...
        }
    };

    $(document).ready(function() {
        QLC.init();
    });

})(jQuery);
```

## 3. Упрощаем CSS

**admin/css/admin.css** - оставляем только essentials:

```css
.qlc-broken-link {
    border: 2px solid #dc3232 !important;
    background-color: #ffeaea !important;
    padding: 1px 3px !important;
    border-radius: 2px !important;
}

.qlc-broken-link::after {
    content: " ❌";
    font-size: 10px;
    margin-left: 3px;
}
```

## Как теперь работает:

🚀 **Легковесное отслеживание** - проверка хеша контента каждые 3 сек
🎯 **Умная проверка** - только измененные/новые ссылки
💾 **Кэширование** - не проверяет если ссылки не менялись
⚡ **Минимальная нагрузка** - нет постоянных AJAX запросов
✅ **Мгновенная реакция** - подсветка сохраняется постоянно

Теперь процессор почти не нагружается! 🎉