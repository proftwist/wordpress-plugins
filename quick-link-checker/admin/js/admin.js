(function($) {
    'use strict';

    let QLC = {
        init: function() {
            this.bindEvents();
            // Ждем загрузки редактора перед проверкой
            setTimeout(() => {
                this.checkOnLoad();
            }, 2000);
        },

        bindEvents: function() {
            $(document).on('click', '#qlc-check-now', this.checkLinksNow.bind(this));
        },

        checkOnLoad: function() {
            // Проверяем только если есть битые ссылки в мета-поле
            const $container = $('#qlc-broken-links-container');
            if ($container.find('ul').length > 0) {
                this.checkLinksNow();
            }
        },

        checkLinksNow: function(e) {
            if (e) e.preventDefault();

            const $button = $('#qlc-check-now');
            const $container = $('#qlc-broken-links-container');

            $button.prop('disabled', true).text(qlc_ajax.checking_text);

            // Получаем контент из всех возможных редакторов
            let content = this.getEditorContent();

            if (!content) {
                console.error('QLC: Cannot find editor content');
                $container.html('<p style="color: #d63638;">Error: Cannot find editor content. Please save the post first.</p>');
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
                    this.updateBrokenLinksList(response.data, $container);
                    this.highlightBrokenLinks(response.data.broken_links);
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

            // 4. Пробуем элемент для блоков
            const $blockEditor = $('.editor-post-text-editor');
            if ($blockEditor.length > 0) {
                content = $blockEditor.val();
                if (content) {
                    console.log('QLC: Got content from block editor textarea');
                    return content;
                }
            }

            // 5. Последняя попытка - ищем любой textarea с контентом
            $('textarea').each(function() {
                const $textarea = $(this);
                const val = $textarea.val();
                if (val && val.length > 100 && val.indexOf('<') !== -1) {
                    content = val;
                    console.log('QLC: Got content from generic textarea');
                    return false; // break loop
                }
            });

            return content;
        },

        updateBrokenLinksList: function(data, $container) {
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

            $container.html(html);
        },

        highlightBrokenLinks: function(brokenLinks) {
            // Сначала снимаем все подсветки
            $('a').removeClass('qlc-broken-link');

            console.log('QLC: Highlighting', brokenLinks.length, 'broken links');

            // Подсвечиваем битые ссылки
            brokenLinks.forEach((link) => {
                // Экранируем специальные символы для селектора
                const escapedUrl = link.url.replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
                const $links = $('a[href="' + escapedUrl + '"]');

                if ($links.length > 0) {
                    $links.addClass('qlc-broken-link');
                    console.log('QLC: Highlighted link:', link.url, 'found:', $links.length);
                } else {
                    console.log('QLC: Link not found in editor:', link.url);

                    // Пробуем найти без экранирования (на всякий случай)
                    const $linksUnescaped = $('a').filter(function() {
                        return $(this).attr('href') === link.url;
                    });

                    if ($linksUnescaped.length > 0) {
                        $linksUnescaped.addClass('qlc-broken-link');
                        console.log('QLC: Highlighted link (unescaped):', link.url);
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        QLC.init();
    });

})(jQuery);