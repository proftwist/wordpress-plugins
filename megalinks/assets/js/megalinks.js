/**
 * Megalinks Plugin JavaScript
 * Обрабатывает hover эффекты для внутренних ссылок на посты и страницы
 *
 * @param {object} megalinksAjax - Локализованные данные от сервера
 */

(function($) {
    'use strict';

    // Проверяем, что мы на десктопе
    var isDesktop = window.innerWidth > 768;

    if (!isDesktop) {
        return; // Отключаем на мобильных устройствах
    }

    var Megalinks = {

        // Кеш цитат для избежания повторных запросов
        excerptCache: {},

        // Кеш миниатюр
        thumbnailCache: {},

        // Кеш информации об изображениях
        imageCache: {},

        // Элемент всплывающего слоя
        tooltip: null,

        // Таймер для задержки показа
        showTimer: null,

        // Таймер для задержки скрытия
        hideTimer: null,

        /**
         * Инициализация плагина
         */
        init: function() {
            this.createTooltip();
            this.bindEvents();
        },

        /**
         * Создание элемента всплывающего слоя
         */
        createTooltip: function() {
            this.tooltip = $('<div class="megalinks-tooltip"></div>');
            $('body').append(this.tooltip);
        },

        /**
         * Привязка событий к ссылкам
         */
        bindEvents: function() {
            var self = this;

            // Используем делегирование событий для динамически загружаемого контента
            $(document).on('mouseenter', 'a[href]', function(e) {
                self.handleMouseEnter.call(self, e, this);
            });

            $(document).on('mouseleave', 'a[href]', function(e) {
                self.handleMouseLeave.call(self, e, this);
            });

            // Скрываем при движении мыши над всплывающим слоем
            $(document).on('mouseenter', '.megalinks-tooltip', function() {
                clearTimeout(self.hideTimer);
            });

            $(document).on('mouseleave', '.megalinks-tooltip', function() {
                self.hideTooltip();
            });
        },

        /**
         * Обработка наведения курсора на ссылку
         */
        handleMouseEnter: function(e, link) {
            var self = this;
            clearTimeout(this.hideTimer);

            // Проверяем, является ли ссылка подходящей
            if (!this.isValidLink(link)) {
                return;
            }

            // Задержка перед показом (50ms)
            this.showTimer = setTimeout(function() {
                self.showTooltip(link, e);
            }, 50);
        },

        /**
         * Обработка ухода курсора с ссылки
         */
        handleMouseLeave: function(e, link) {
            var self = this;
            clearTimeout(this.showTimer);

            // Задержка перед скрытием (200ms) для плавного перехода
            this.hideTimer = setTimeout(function() {
                self.hideTooltip();
            }, 200);
        },

        /**
         * Проверка, является ли ссылка подходящей для всплывающего слоя
         */
        isValidLink: function(link) {
            var $link = $(link);
            var href = $link.attr('href');

            // Пропускаем ссылки внутри изображений
            if ($link.find('img').length > 0 || $link.closest('img').length > 0) {
                return false;
            }

            // Пропускаем ссылки в навигационных меню
            if ($link.closest('.wp-block-navigation').length > 0 ||
                $link.closest('.wp-block-navigation__submenu-container').length > 0 ||
                $link.hasClass('wp-block-navigation-item__content')) {
                return false;
            }

            // Пропускаем ссылки внутри заголовков H1-H6
            if ($link.closest('h1, h2, h3, h4, h5, h6').length > 0) {
                return false;
            }

            // Пропускаем ссылки в блоках даты поста
            if ($link.closest('.wp-block-post-date').length > 0 || $link.hasClass('wp-block-post-date')) {
                return false;
            }

            // Пропускаем ссылки в блоках пагинации
            if ($link.closest('.wp-block-query-pagination-next').length > 0 ||
                $link.closest('.wp-block-query-pagination-previous').length > 0 ||
                $link.hasClass('wp-block-query-pagination-next') ||
                $link.hasClass('wp-block-query-pagination-previous') ||
                $link.hasClass('cresta-nav-previous')) {
                return false;
            }

            // Пропускаем ссылки внутри контейнера crestaPostsBoxContent
            if ($link.closest('.crestaPostsBoxContent').length > 0) {
                return false;
            }

            // Проверяем, является ли ссылка внутренней
            if (!this.isInternalLink(href)) {
                return false;
            }

            // Проверяем, ведет ли ссылка на пост, страницу или изображение
            if (!this.isPostOrPageLink(href) && !this.isImageLink(href)) {
                return false;
            }

            // Убираем браузерный tooltip для подходящих ссылок
            $link.attr('title', '');

            return true;
        },

        /**
         * Проверка, является ли ссылка ссылкой на изображение
         */
        isImageLink: function(href) {
            if (!href) return false;

            // Расширения изображений
            var imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'];
            var hrefLower = href.toLowerCase();

            // Проверяем расширение файла
            for (var i = 0; i < imageExtensions.length; i++) {
                if (hrefLower.indexOf(imageExtensions[i]) !== -1) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Проверка, является ли ссылка внутренней
         */
        isInternalLink: function(href) {
            if (!href) return false;

            // Абсолютные URL - проверяем origin
            if (href.indexOf('http') === 0) {
                return href.indexOf(window.location.origin) === 0;
            }

            // Относительные URL считаем внутренними, кроме якорей на текущей странице
            if (href.indexOf('#') === 0) {
                return false; // Якоря не считаем внутренними ссылками
            }

            // Относительные пути считаем внутренними
            return href.indexOf('/') === 0;
        },

        /**
         * Проверка, ведет ли ссылка на пост или страницу
         */
        isPostOrPageLink: function(href) {
            // Проверяем на наличие ID поста в URL (для стандартных permalink структур)
            var postIdMatch = href.match(/\/(\d+)\//) || href.match(/p=(\d+)/);
            if (postIdMatch) {
                return true;
            }

            // Для произвольных permalink структур проверяем на отсутствие архивных паттернов
            var archivePatterns = [
                '/category/',
                '/tag/',
                '/author/',
                '/date/',
                '/page/',
                '/feed/',
                '/search/',
                '/archives/',
                '/wp-admin/',
                '/wp-login.php',
                '/wp-content/',
                '/wp-includes/',
                '.php',
                '.jpg',
                '.png',
                '.gif',
                '.css',
                '.js'
            ];

            for (var i = 0; i < archivePatterns.length; i++) {
                if (href.indexOf(archivePatterns[i]) !== -1) {
                    return false;
                }
            }

            // Исключаем внешние ссылки
            if (href.indexOf('http') === 0 && href.indexOf(window.location.origin) !== 0) {
                return false;
            }

            return true;
        },

        /**
         * Показ всплывающего слоя
         */
        showTooltip: function(link, e) {
            var self = this;
            var $link = $(link);
            var href = $link.attr('href');

            // Проверяем, является ли ссылка ссылкой на изображение
            if (this.isImageLink(href)) {
                this.showImageTooltip(href, e);
                return;
            }

            // Извлекаем ID поста из URL
            var postId = this.extractPostId(href);

            if (!postId) {
                return;
            }

            // Проверяем кеш цитат и миниатюр
            if (this.excerptCache[postId] && this.thumbnailCache[postId] !== undefined) {
                this.displayTooltip(this.excerptCache[postId], e, postId, this.thumbnailCache[postId]);
                return;
            }

            // Сначала получаем цитату
            $.ajax({
                url: megalinksAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'megalinks_get_excerpt',
                    post_id: postId,
                    nonce: megalinksAjax.nonce_excerpt
                },
                success: function(response) {
                    if (response.success && response.data.excerpt) {
                        // Кешируем цитату
                        self.excerptCache[postId] = response.data.excerpt;

                        // Теперь проверяем миниатюру
                        $.ajax({
                            url: megalinksAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'megalinks_get_thumbnail',
                                post_id: postId,
                                nonce: megalinksAjax.nonce_thumbnail
                            },
                            success: function(thumbResponse) {
                                var hasThumbnail = thumbResponse.success && thumbResponse.data && thumbResponse.data.thumbnail_url;
                                self.thumbnailCache[postId] = hasThumbnail ? thumbResponse.data.thumbnail_url : false;
                                self.displayTooltip(self.excerptCache[postId], e, postId, hasThumbnail);
                            },
                            error: function(xhr, status, error) {
                                console.warn('Thumbnail AJAX error for post ID', postId, ':', status, error);
                                self.thumbnailCache[postId] = false;
                                self.displayTooltip(self.excerptCache[postId], e, postId, false);
                            }
                        });
                    } else {
                        console.warn('No excerpt available for post ID:', postId);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Excerpt AJAX error for post ID', postId, ':', status, error);
                }
            });
        },

        /**
         * Показ тултипа для изображения
         */
        showImageTooltip: function(imageUrl, e) {
            var self = this;

            // Проверяем кеш информации об изображении
            if (this.imageCache[imageUrl]) {
                this.displayImageTooltip(this.imageCache[imageUrl], e);
                return;
            }

            // Получаем информацию об изображении через AJAX
            $.ajax({
                url: megalinksAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'megalinks_get_image_info',
                    image_url: imageUrl,
                    nonce: megalinksAjax.nonce_image_info
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Кешируем информацию об изображении
                        self.imageCache[imageUrl] = response.data;
                        self.displayImageTooltip(response.data, e);
                    } else {
                        console.warn('Failed to get image info for URL:', imageUrl);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Image info AJAX error for URL', imageUrl, ':', status, error);
                }
            });
        },

        /**
         * Отображение тултипа с изображением
         */
        displayImageTooltip: function(imageInfo, e) {
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();
            var tooltipWidth = 400; // Максимальная ширина из CSS

            // Создаем HTML для тултипа с изображением
            var html = '<div class="megalinks-image-preview">';
            html += '<div class="image-info">';
            html += '<strong>Размеры:</strong> ' + imageInfo.width + '×' + imageInfo.height + 'px<br>';
            if (imageInfo.size) {
                var sizeKB = Math.round(imageInfo.size / 1024);
                html += '<strong>Размер файла:</strong> ' + sizeKB + ' KB<br>';
            }
            html += '<strong>Тип:</strong> ' + imageInfo.type;
            html += '</div>';
            html += '<div class="image-preview-container">';
            html += '<img src="' + imageInfo.original_url + '" alt="Image preview" loading="lazy" class="preview-image">';
            html += '</div>';
            html += '</div>';

            // Создаем временный tooltip для точного измерения высоты
            var tempTooltip = this.tooltip.clone().css({
                visibility: 'hidden',
                position: 'absolute',
                top: '-9999px',
                left: '-9999px',
                width: tooltipWidth + 'px'
            }).appendTo('body');

            tempTooltip.html(html);
            var tooltipHeight = tempTooltip.outerHeight();
            tempTooltip.remove();

            // Позиционирование
            var linkRect = e.target.getBoundingClientRect();
            var spaceAbove = linkRect.top - 20;
            var spaceBelow = windowHeight - linkRect.bottom - 20;

            var positionAbove = spaceAbove >= tooltipHeight;
            var positionBelow = !positionAbove && spaceBelow >= tooltipHeight;

            if (!positionAbove && !positionBelow) {
                positionAbove = spaceAbove >= spaceBelow;
                positionBelow = !positionAbove;
            }

            var top, arrowClass;

            if (positionAbove) {
                top = linkRect.top - tooltipHeight - 12 + scrollTop;
                arrowClass = 'top-arrow';
            } else {
                top = linkRect.bottom + 12 + scrollTop;
                arrowClass = 'bottom-arrow';
            }

            // Центрируем по горизонтали
            var left = e.clientX - (tooltipWidth / 2);

            if (left < 10) {
                left = 10;
            } else if (left + tooltipWidth > windowWidth - 10) {
                left = windowWidth - tooltipWidth - 10;
            }

            // Показываем тултип
            this.tooltip
                .html(html)
                .css({
                    left: left + 'px',
                    top: top + 'px',
                    width: tooltipWidth + 'px'
                })
                .removeClass('top-arrow bottom-arrow')
                .addClass(arrowClass + ' visible');
        },

        /**
         * Извлечение ID поста из URL
         */
        extractPostId: function(href) {
            // Сначала пробуем получить ID через AJAX, так как URL может быть в произвольной структуре
            // Для этого нам нужно отправить URL на сервер и получить ID поста

            // Для стандартных permalink структур типа /2023/01/01/post-name/
            var postIdMatch = href.match(/\/(\d+)\//);
            if (postIdMatch) {
                return parseInt(postIdMatch[1]);
            }

            // Для URL с параметрами типа ?p=123
            var paramMatch = href.match(/[?&]p=(\d+)/);
            if (paramMatch) {
                return parseInt(paramMatch[1]);
            }

            // Для произвольных структур - пробуем получить ID по URL через AJAX
            // Это fallback для случаев, когда ID не удается извлечь из URL
            return this.getPostIdByUrl(href);
        },

        /**
         * Получение ID поста по URL через AJAX (для произвольных структур)
         */
        getPostIdByUrl: function(href) {
            var postId = null;

            // Важно: синхронный запрос нужен для последовательной обработки
            // В будущем можно реорганизовать логику для полностью асинхронной работы
            $.ajax({
                url: megalinksAjax.ajaxurl,
                type: 'POST',
                async: false, // Синхронный запрос
                data: {
                    action: 'megalinks_get_post_id_by_url',
                    url: href,
                    nonce: megalinksAjax.nonce_post_id
                },
                success: function(response) {
                    if (response.success && response.data.post_id) {
                        postId = parseInt(response.data.post_id, 10);
                    }
                },
                error: function(xhr, status, error) {
                    console.warn('Failed to get post ID by URL:', status, error);
                }
            });

            return postId;
        },

        /**
         * Отображение всплывающего слоя с цитатой
         */
        displayTooltip: function(excerpt, e, postId, hasThumbnail) {
            var $link = $(e.target).closest('a');
            var linkRect = $link.get(0).getBoundingClientRect();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();
            var scrollLeft = $(window).scrollLeft();
            var tooltipWidth = 400; // Максимальная ширина из CSS

            // Создаем временный tooltip для точного измерения высоты
            var tempTooltip = this.tooltip.clone().css({
                visibility: 'hidden',
                position: 'absolute',
                top: '-9999px',
                left: '-9999px',
                width: tooltipWidth + 'px' // Фиксируем ширину для точного расчета
            }).appendTo('body');

            // Создаем HTML в зависимости от наличия миниатюры
            var finalHtml;
            if (hasThumbnail) {
                finalHtml = '<div class="megalinks-image"><div class="image-placeholder"></div></div><div class="megalinks-content">' + excerpt + '</div>';
            } else {
                finalHtml = '<div class="megalinks-content">' + excerpt + '</div>';
            }
            tempTooltip.html(finalHtml);

            var tooltipHeight = tempTooltip.outerHeight();
            tempTooltip.remove();

            // Добавляем отступ для стрелки
            var arrowHeight = 12;
            var verticalMargin = 10;

            // Рассчитываем доступное пространство
            var spaceAbove = linkRect.top - verticalMargin - arrowHeight;
            var spaceBelow = windowHeight - linkRect.bottom - verticalMargin - arrowHeight;

            // Определяем позиционирование
            var positionAbove = spaceAbove >= tooltipHeight;
            var positionBelow = !positionAbove && spaceBelow >= tooltipHeight;

            // Если места ни сверху, ни снизу недостаточно, выбираем сторону с большим пространством
            if (!positionAbove && !positionBelow) {
                positionAbove = spaceAbove >= spaceBelow;
                positionBelow = !positionAbove;
            }

            var top, arrowClass;

            if (positionAbove) {
                // Позиционируем сверху
                top = linkRect.top - tooltipHeight - arrowHeight - verticalMargin + scrollTop;
                arrowClass = 'top-arrow';
            } else {
                // Позиционируем снизу
                top = linkRect.bottom + arrowHeight + verticalMargin + scrollTop;
                arrowClass = 'bottom-arrow';
            }

            // Центрируем по горизонтали относительно курсора
            var left = e.clientX - (tooltipWidth / 2);

            // Проверяем горизонтальные границы
            if (left < 10) {
                left = 10;
            } else if (left + tooltipWidth > windowWidth - 10) {
                left = windowWidth - tooltipWidth - 10;
            }

            // Создаем HTML в зависимости от наличия миниатюры
            var finalHtml;
            if (hasThumbnail) {
                finalHtml = '<div class="megalinks-image"><div class="image-placeholder"></div></div><div class="megalinks-content">' + excerpt + '</div>';
            } else {
                finalHtml = '<div class="megalinks-content">' + excerpt + '</div>';
            }
            this.tooltip
                .html(finalHtml)
                .css({
                    left: left + 'px',
                    top: top + 'px',
                    width: tooltipWidth + 'px', // Фиксируем ширину
                    position: 'absolute'
                })
                .removeClass('top-arrow bottom-arrow')
                .addClass(arrowClass + ' visible');

            // Загружаем миниатюру поста
            this.loadPostThumbnail(postId);
        },

        /**
         * Загрузка миниатюры поста
         */
        loadPostThumbnail: function(postId) {
            var self = this;
            var $imageContainer = this.tooltip.find('.megalinks-image');

            // Проверяем кеш миниатюр
            if (this.thumbnailCache[postId] !== undefined) {
                if (this.thumbnailCache[postId]) {
                    // Миниатюра есть - показываем
                    $imageContainer.html('<img src="' + this.thumbnailCache[postId] + '" alt="Post thumbnail" loading="lazy">');
                }
                // Если миниатюры нет - ничего не делаем, контейнер уже скрыт
                return;
            }

            // Очищаем предыдущее содержимое и показываем заглушку
            $imageContainer.html('<div class="image-placeholder"></div>').show();

            $.ajax({
                url: megalinksAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'megalinks_get_thumbnail',
                    post_id: postId,
                    nonce: megalinksAjax.nonce_thumbnail
                },
                success: function(response) {
                    if (response.success && response.data && response.data.thumbnail_url) {
                        // Кешируем и показываем миниатюру
                        self.thumbnailCache[postId] = response.data.thumbnail_url;
                        $imageContainer.html('<img src="' + response.data.thumbnail_url + '" alt="Post thumbnail" loading="lazy">');
                    } else {
                        // Миниатюры нет - кешируем false, контейнер уже скрыт в displayTooltip
                        self.thumbnailCache[postId] = false;
                    }
                },
                error: function(xhr, status, error) {
                    // Ошибка - кешируем false, контейнер уже скрыт в displayTooltip
                    console.warn('Thumbnail AJAX error for post ID', postId, ':', status, error);
                    self.thumbnailCache[postId] = false;
                }
            });
        },

        /**
         * Скрытие всплывающего слоя
         */
        hideTooltip: function() {
            this.tooltip.removeClass('visible');
            // Не очищаем содержимое, чтобы при повторном показе не было прыжаний
        }
    };

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        Megalinks.init();
    });

    // Повторная инициализация при AJAX загрузке контента (для динамических ссылок)
    $(document).on('ajaxComplete', function() {
        // Не переинициализируем полностью, просто проверяем новые ссылки
    });

})(jQuery);