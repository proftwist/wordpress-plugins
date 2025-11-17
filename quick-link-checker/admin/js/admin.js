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
        }
    };

    $(document).ready(function() {
        QLC.init();
    });

})(jQuery);