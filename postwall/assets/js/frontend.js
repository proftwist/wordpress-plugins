/**
 * Frontend JavaScript для Post Wall
 *
 * Обрабатывает интерактивное отображение кафельной стенки постов на фронтенде.
 *
 * @package PostWall
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Класс PostWall для управления визуализацией календаря
     */
    class PostWall {
        constructor(containerElement) {
            this.container = containerElement;
            this.siteUrl = this.container.dataset.siteUrl;
            this.containerId = this.container.dataset.containerId;
            this.loadingElement = this.container.querySelector('.postwall-loading');

            // Получаем данные из data-атрибутов
            this.baseTitle = this.container.dataset.baseTitle || 'Posts from the site for the last 12 months';
            this.loadingText = this.container.dataset.loadingText || 'Loading post wall...';
            this.domain = this.container.dataset.domain || '';

            this.init();
        }

        /**
         * Инициализация кафельной стенки постов
         */
        init() {
            if (this.siteUrl) {
                this.fetchPostData();
            } else {
                this.generateCalendar();
            }
            this.attachClickHandlers();
        }

        /**
         * Прикрепить обработчики кликов к ячейкам дней
         */
        attachClickHandlers() {
            // Используем делегирование событий для контейнера
            this.container.addEventListener('click', (event) => {
                const target = event.target;

                // Проверяем, что клик был по квадратику дня с постами
                if (target.classList.contains('day') && target.hasAttribute('data-date') && !target.classList.contains('empty')) {
                    event.preventDefault();
                    const dateString = target.getAttribute('data-date');
                    this.navigateToDateArchive(dateString);
                }
            });
        }

        /**
         * Перейти на страницу архива даты для заданной даты
         * @param {string} dateString - Дата в формате YYYY-MM-DD
         */
        navigateToDateArchive(dateString) {
            // Формируем URL для архивной страницы даты на сайте из блока
            const archiveUrl = this.generateDateArchiveUrl(dateString);

            // Перенаправляем пользователя
            window.open(archiveUrl, '_blank');
        }

        /**
         * Сгенерировать URL для страницы архива даты
         * @param {string} dateString - Дата в формате YYYY-MM-DD
         * @return {string} URL архива
         */
        generateDateArchiveUrl(dateString) {
            // Разбираем дату
            const [year, month, day] = dateString.split('-');

            // Формируем URL для архивной страницы даты
            // Формат: /YYYY/MM/DD/ или /YYYY/MM/ в зависимости от темы
            // Используем siteUrl из блока
            return `${this.siteUrl}/${year}/${month.padStart(2, '0')}/${day.padStart(2, '0')}/`;
        }

        /**
         * Получить данные о постах через AJAX
         */
        fetchPostData() {

            $.ajax({
                url: postwallSettings.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'postwall_get_post_data',
                    nonce: postwallSettings.nonce,
                    site_url: this.siteUrl
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.postData = response.data;
                        this.generateCalendar();
                    } else {
                        this.showError(this.translate('Failed to load post data'));
                    }
                },
                error: (xhr, status, error) => {
                    this.showError(this.translate('Error loading data'));
                }
            });
        }

        /**
         * Показать сообщение об ошибке
         * @param {string} message Сообщение об ошибке для отображения
         */
        showError(message) {
            if (this.loadingElement) {
                this.loadingElement.textContent = message;
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'postwall-error';
                errorDiv.textContent = message;
                this.container.appendChild(errorDiv);
            }
        }

        /**
         * Генерировать сетку календаря по месяцам
         */
        generateCalendar() {
            // Убрать индикатор загрузки
            if (this.loadingElement) {
                this.loadingElement.remove();
            }

            // Создаем или обновляем заголовок
            this.createOrUpdateTitle();

            // Создать обёртку для тепловой карты
            const wrapper = document.createElement('div');
            wrapper.className = 'heatmap-wrapper';

            // Create months container
            const monthsContainer = document.createElement('div');
            monthsContainer.className = 'months';

            const now = new Date();
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];

            // Сгенерировать 12 месяцев от текущего назад до 12 месяцев назад
            for (let i = 11; i >= 0; i--) {
                const monthDate = new Date(now);
                monthDate.setMonth(now.getMonth() - i);

                // Устанавливаем дату на первый день месяца для правильного расчета
                monthDate.setDate(1);

                const monthDiv = this.createMonth(monthDate);
                monthsContainer.appendChild(monthDiv);
            }

            wrapper.appendChild(monthsContainer);
            this.container.appendChild(wrapper);
        }

        /**
         * Создать или обновить элемент заголовка
         */
        createOrUpdateTitle() {
            let titleElement = this.container.querySelector('.postwall-title');

            // Получаем тег заголовка из data-атрибута или используем значение по умолчанию
            const headingTag = this.container.getAttribute('data-heading-tag') || 'h3';

            // Создаем локализованный заголовок с доменом
            const translatedTitle = this.generateTitleWithDomain();

            if (!titleElement) {
                // Создаем элемент заголовка с выбранным тегом
                if (headingTag === 'div') {
                    titleElement = document.createElement('div');
                    titleElement.className = 'postwall-text';
                } else {
                    titleElement = document.createElement(headingTag);
                    titleElement.className = 'postwall-title';
                }
                this.container.insertBefore(titleElement, this.container.firstChild);
            }

            titleElement.textContent = translatedTitle;
        }

        /**
         * Сгенерировать заголовок с доменом
         * @return {string} Локализованный заголовок с доменом
         */
        generateTitleWithDomain() {
            if (!this.domain) {
                return this.translate(this.baseTitle);
            }

            // Простой способ - создаем заголовок в зависимости от языка
            if (this.getLocale().startsWith('ru')) {
                return 'Посты сайта ' + this.domain + ' за последние 12 месяцев';
            } else {
                return 'Posts from the site ' + this.domain + ' for the last 12 months';
            }
        }

        /**
         * Создать сетку месяца
         * @param {Date} monthDate - Дата, представляющая месяц для создания
         * @return {HTMLElement} Элемент контейнера месяца
         */
        createMonth(monthDate) {
           const monthDiv = document.createElement('div');
           monthDiv.className = 'month';

           const monthGrid = document.createElement('div');
           monthGrid.className = 'month-grid';

           const year = monthDate.getFullYear();
           const month = monthDate.getMonth();

           // Устанавливаем дату на первый день месяца для правильного расчета
           monthDate.setDate(1);

           // Get first day of month and what day of week it falls on
           const firstDay = new Date(year, month, 1);
           const firstDayOfWeek = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.

           // Adjust for Monday first (0 = Monday, 6 = Sunday)
           // ВАЖНО: Это правильное количество пустых клеток ДО первого дня
           const emptyCellsBefore = (firstDayOfWeek + 6) % 7;

           // Получить количество дней в месяце
           const daysInMonth = new Date(year, month + 1, 0).getDate();

           // Create cells
           // Empty cells before first day - ЛЕВЫЙ "РВАНЫЙ" КРАЙ
           for (let i = 0; i < emptyCellsBefore; i++) {
               const emptyCell = document.createElement('span');
               emptyCell.className = 'day empty';
               monthGrid.appendChild(emptyCell);
           }

           // Days of the month
           for (let day = 1; day <= daysInMonth; day++) {
               const dayCell = document.createElement('span');
               const cellDate = new Date(year, month, day);

               // Determine activity level
               const activityLevel = this.getActivityLevel(cellDate);

               if (activityLevel === 0) {
                   dayCell.className = 'day lvl-0';
               } else {
                   dayCell.className = `day lvl-${activityLevel}`;
                   // Добавляем data-date атрибут для квадратиков с постами
                   dayCell.setAttribute('data-date', cellDate.toISOString().split('T')[0]);
               }

               // Add tooltip with post count
               const postCount = this.postData ?
                   (this.postData[cellDate.toISOString().split('T')[0]] || 0) : 0;

               // Форматируем дату согласно настройкам WordPress
               const formattedDate = this.formatDateAccordingToWordPress(cellDate);

               // Создаем локализованный текст для тултипа
               const tooltipText = this.formatTooltip(formattedDate, postCount);
               dayCell.title = tooltipText;

               monthGrid.appendChild(dayCell);
           }

           monthDiv.appendChild(monthGrid);

           // Добавить метку месяца
           const monthLabel = document.createElement('div');
           monthLabel.className = 'month-label';
           monthLabel.textContent = this.getMonthName(month);
           monthDiv.appendChild(monthLabel);

           return monthDiv;
       }

        /**
         * Форматировать текст подсказки с правильной локализацией
         * @param {string} date Отформатированная дата
         * @param {number} postCount Количество постов
         * @return {string} Локализованный текст подсказки
         */
        formatTooltip(date, postCount) {
            // Получаем переведенное слово "posts" в правильной форме
            const postsText = this.getPostsText(postCount);
            return `${date}: ${postCount} ${postsText}`;
        }

        /**
         * Получить локализованный текст постов с правильными формами множественного числа
         * @param {number} count Количество постов
         * @return {string} Локализованный текст постов
         */
        getPostsText(count) {
            // Для русского языка - особые правила множественного числа
            if (this.getLocale().startsWith('ru')) {
                const lastDigit = count % 10;
                const lastTwoDigits = count % 100;

                if (lastDigit === 1 && lastTwoDigits !== 11) {
                    return 'пост';
                } else if (lastDigit >= 2 && lastDigit <= 4 && (lastTwoDigits < 12 || lastTwoDigits > 14)) {
                    return 'поста';
                } else {
                    return 'постов';
                }
            }

            // Для английского и других языков
            return this.translate('posts');
        }

        /**
         * Форматировать дату согласно настройкам формата даты WordPress
         * @param {Date} date - Дата для форматирования
         * @return {string} Отформатированная строка даты
         */
        formatDateAccordingToWordPress(date) {
            // Если WordPress передал формат даты, используем его
            if (postwallSettings.dateFormat) {
                return this.formatDateWithWordPressFormat(date, postwallSettings.dateFormat);
            }

            // Иначе используем локализованный формат по умолчанию
            return this.getLocalizedDateFormat(date);
        }

        /**
         * Форматировать дату используя формат даты WordPress
         * @param {Date} date - Дата для форматирования
         * @param {string} format - Строка формата даты WordPress
         * @return {string} Отформатированная дата
         */
        formatDateWithWordPressFormat(date, format) {
            const replacements = {
                'd': () => date.getDate().toString().padStart(2, '0'),
                'j': () => date.getDate(),
                'm': () => (date.getMonth() + 1).toString().padStart(2, '0'),
                'n': () => (date.getMonth() + 1),
                'Y': () => date.getFullYear(),
                'y': () => date.getFullYear().toString().slice(-2),
                'F': () => this.getMonthName(date.getMonth()),
                'M': () => this.translate(this.getMonthName(date.getMonth()).slice(0, 3))
            };

            let result = format;
            for (const [key, formatter] of Object.entries(replacements)) {
                result = result.replace(new RegExp(key, 'g'), formatter());
            }

            return result;
        }

        /**
         * Получить локализованный формат даты как запасной вариант
         * @param {Date} date - Дата для форматирования
         * @return {string} Отформатированная дата
         */
        getLocalizedDateFormat(date) {
            const locale = this.getLocale();

            const formats = {
                'ru_RU': () => {
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    return `${day}.${month}.${date.getFullYear()}`;
                },
                'en_US': () => {
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    return `${month}/${day}/${date.getFullYear()}`;
                }
            };

            return (formats[locale] || formats['en_US'])();
        }

        /**
         * Получить текущую локаль
         * @return {string} Текущая локаль
         */
        getLocale() {
            return postwallSettings.locale || 'en_US';
        }

        /**
         * Получить уровень активности для даты на основе реальных данных о постах
         *
         * @param {Date} date Дата для проверки
         * @return {number} Уровень активности (0-4)
         */
        getActivityLevel(date) {
            // Если у нас есть реальные данные, используем их
            if (this.postData) {
                const dateKey = date.toISOString().split('T')[0]; // Формат YYYY-MM-DD
                const postCount = this.postData[dateKey] || 0;

                // Определяем уровень активности на основе количества постов
                if (postCount === 0) return 0; // Нет постов
                if (postCount === 1) return 1; // 1 пост
                if (postCount === 2) return 2; // 2 поста
                if (postCount <= 4) return 3; // 3-4 поста
                return 4; // 5+ постов
            }

            // Запасной вариант с случайными данными для демонстрации, когда нет данных
            const random = Math.random();
            if (random < 0.3) return 0;
            if (random < 0.5) return 1;
            if (random < 0.7) return 2;
            if (random < 0.9) return 3;
            return 4;
        }

        /**
         * Получить локализованное название месяца
         * @param {number} monthIndex - Индекс месяца (0-11)
         * @return {string} Название месяца
         */
        getMonthName(monthIndex) {
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];
            return monthNames[monthIndex];
        }

        /**
         * Получить название дня для отладки
         * @param {number} dayOfWeek - День недели (0-6)
         * @return {string} Название дня
         */
        getDayName(dayOfWeek) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return days[dayOfWeek];
        }

        /**
         * Помощник перевода с запасным вариантом
         * @param {string} text Текст для перевода
         * @return {string} Переведенный текст
         */
        translate(text) {
            // Пробуем использовать wp.i18n если доступно
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(text, 'postwall');
            }

            // Запасной вариант: ручной перевод на основе локали
            if (this.getLocale().startsWith('ru')) {
                const russianTranslations = {
                    'Posts from the site for the last 12 months': 'Посты с сайта за последние 12 месяцев',
                    'Loading post wall...': 'Загрузка кафельной стенки...',
                    'Failed to load post data': 'Не удалось загрузить данные постов',
                    'Error loading data': 'Ошибка при загрузке данных',
                    'posts': 'постов', // базовая форма для множественного числа
                    'Jan': 'янв', 'Feb': 'фев', 'Mar': 'мар', 'Apr': 'апр',
                    'May': 'май', 'Jun': 'июн', 'Jul': 'июл', 'Aug': 'авг',
                    'Sep': 'сен', 'Oct': 'окт', 'Nov': 'ноя', 'Dec': 'дек'
                };
                return russianTranslations[text] || text;
            }

            // По умолчанию английский
            return text;
        }
    }

    /**
     * Инициализировать экземпляры PostWall при готовности DOM
     */
    $(document).ready(function() {
        $('.postwall-container').each(function() {
            new PostWall(this);
        });
    });

})(jQuery);