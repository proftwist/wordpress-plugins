/**
 * Post Wall Frontend JavaScript
 *
 * Handles the interactive post wall display on the frontend.
 *
 * @package PostWall
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * PostWall class for managing the calendar visualization
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
         * Initialize the post wall
         */
        init() {
            console.log('PostWall init called - UPDATED VERSION');
            if (this.siteUrl) {
                this.fetchPostData();
            } else {
                this.generateCalendar();
            }
        }

        /**
         * Fetch post data via AJAX
         */
        fetchPostData() {
            console.log('Fetching post data for', this.siteUrl);

            $.ajax({
                url: postwallSettings.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'postwall_get_post_data',
                    nonce: postwallSettings.nonce,
                    site_url: this.siteUrl
                },
                success: (response) => {
                    console.log('AJAX success:', response);
                    if (response.success && response.data) {
                        this.postData = response.data;
                        this.generateCalendar();
                    } else {
                        this.showError(this.translate('Failed to load post data'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    this.showError(this.translate('Error loading data'));
                }
            });
        }

        /**
         * Show error message
         * @param {string} message Error message to display
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
         * Generate the calendar grid by months
         */
        generateCalendar() {
            console.log('generateCalendar called - UPDATED VERSION');
            // Remove loading indicator
            if (this.loadingElement) {
                this.loadingElement.remove();
                console.log('Loading element removed');
            }

            // Создаем или обновляем заголовок
            this.createOrUpdateTitle();

            // Create the heatmap wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'heatmap-wrapper';
            console.log('Wrapper created');

            // Create months container
            const monthsContainer = document.createElement('div');
            monthsContainer.className = 'months';

            const now = new Date();

            // Generate 12 months from current back to 12 months ago
            console.log('Generating months...');
            for (let i = 11; i >= 0; i--) {
                const monthDate = new Date(now);
                monthDate.setMonth(now.getMonth() - i);

                // Устанавливаем дату на первый день месяца для правильного расчета
                monthDate.setDate(1);

                const monthDiv = this.createMonth(monthDate);
                monthsContainer.appendChild(monthDiv);
                console.log(`Month ${i} added:`, monthDate.toLocaleDateString());
            }

            wrapper.appendChild(monthsContainer);
            this.container.appendChild(wrapper);
            console.log('Calendar appended to container');
        }

        /**
         * Create or update the title element
         */
        createOrUpdateTitle() {
            let titleElement = this.container.querySelector('.postwall-title');

            // Создаем локализованный заголовок с доменом
            const translatedTitle = this.generateTitleWithDomain();

            if (!titleElement) {
                titleElement = document.createElement('h3');
                titleElement.className = 'postwall-title';
                this.container.insertBefore(titleElement, this.container.firstChild);
            }

            titleElement.textContent = translatedTitle;
            console.log('Final title:', translatedTitle);
        }

        /**
         * Generate title with domain
         * @return {string} Localized title with domain
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
         * Create GitHub-style month with weeks as columns, days as rows
         */
        createMonth(monthDate) {
            const monthDiv = document.createElement('div');
            monthDiv.className = 'month';

            const monthGrid = document.createElement('div');
            monthGrid.className = 'month-grid';

            const year = monthDate.getFullYear();
            const month = monthDate.getMonth();

            // Первый день месяца
            const firstDay = new Date(year, month, 1);
            // Последний день месяца
            const lastDay = new Date(year, month + 1, 0);

            // Находим понедельник недели, в которой начинается месяц
            const startDate = new Date(firstDay);
            const dayOfWeek = (firstDay.getDay() + 6) % 7; // Понедельник = 0
            startDate.setDate(firstDay.getDate() - dayOfWeek);

            // Находим воскресенье недели, в которой заканчивается месяц
            const endDate = new Date(lastDay);
            const lastDayOfWeek = (lastDay.getDay() + 6) % 7;
            endDate.setDate(lastDay.getDate() + (6 - lastDayOfWeek));

            // Создаем ячейки для всех дней от startDate до endDate
            const currentDate = new Date(startDate);

            while (currentDate <= endDate) {
                const dayCell = document.createElement('span');

                // Проверяем, принадлежит ли день текущему месяцу
                const isCurrentMonth = currentDate.getMonth() === month;

                if (isCurrentMonth) {
                    // День текущего месяца - рассчитываем активность
                    const activityLevel = this.getActivityLevel(currentDate);
                    const postCount = this.postData ?
                        (this.postData[currentDate.toISOString().split('T')[0]] || 0) : 0;

                    dayCell.className = `day lvl-${activityLevel}`;

                    // Добавляем тултип
                    const formattedDate = this.formatDateAccordingToWordPress(currentDate);
                    dayCell.title = this.formatTooltip(formattedDate, postCount);
                } else {
                    // День соседнего месяца - пустая ячейка
                    dayCell.className = 'day empty';
                }

                monthGrid.appendChild(dayCell);
                currentDate.setDate(currentDate.getDate() + 1);
            }

            monthDiv.appendChild(monthGrid);

            // Добавляем label месяца (уже есть в вашем коде)
            const monthLabel = document.createElement('div');
            monthLabel.className = 'month-label';
            monthLabel.textContent = this.getMonthName(month);
            monthDiv.appendChild(monthLabel);

            return monthDiv;
        }

        /**
         * Get day name for debugging
         * @param {number} dayOfWeek - Day of week (0-6)
         * @return {string} Day name
         */
        getDayName(dayOfWeek) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return days[dayOfWeek];
        }

        /**
         * Format date according to WordPress date format settings
         * @param {Date} date - Date to format
         * @return {string} Formatted date string
         */
        formatDateAccordingToWordPress(date) {
            // Получаем локаль из настроек
            const locale = this.getLocale();

            // Базовые форматы дат для разных локалей
            const dateFormats = {
                'ru_RU': {
                    // Русский формат: DD.MM.YYYY
                    format: (d) => {
                        const day = d.getDate().toString().padStart(2, '0');
                        const month = (d.getMonth() + 1).toString().padStart(2, '0');
                        const year = d.getFullYear();
                        return `${day}.${month}.${year}`;
                    }
                },
                'en_US': {
                    // Американский формат: MM/DD/YYYY
                    format: (d) => {
                        const day = d.getDate().toString().padStart(2, '0');
                        const month = (d.getMonth() + 1).toString().padStart(2, '0');
                        const year = d.getFullYear();
                        return `${month}/${day}/${year}`;
                    }
                },
                'en_GB': {
                    // Британский формат: DD/MM/YYYY
                    format: (d) => {
                        const day = d.getDate().toString().padStart(2, '0');
                        const month = (d.getMonth() + 1).toString().padStart(2, '0');
                        const year = d.getFullYear();
                        return `${day}/${month}/${year}`;
                    }
                },
                'de_DE': {
                    // Немецкий формат: DD.MM.YYYY
                    format: (d) => {
                        const day = d.getDate().toString().padStart(2, '0');
                        const month = (d.getMonth() + 1).toString().padStart(2, '0');
                        const year = d.getFullYear();
                        return `${day}.${month}.${year}`;
                    }
                },
                'fr_FR': {
                    // Французский формат: DD/MM/YYYY
                    format: (d) => {
                        const day = d.getDate().toString().padStart(2, '0');
                        const month = (d.getMonth() + 1).toString().padStart(2, '0');
                        const year = d.getFullYear();
                        return `${day}/${month}/${year}`;
                    }
                }
            };

            // Используем формат для текущей локали или fallback
            const localeFormat = dateFormats[locale] || dateFormats['en_US'];
            return localeFormat.format(date);
        }

        /**
         * Format tooltip text with proper localization
         * @param {string} date Formatted date
         * @param {number} postCount Number of posts
         * @return {string} Localized tooltip text
         */
        formatTooltip(date, postCount) {
            // Получаем переведенное слово "posts" в правильной форме
            const postsText = this.getPostsText(postCount);
            return `${date}: ${postCount} ${postsText}`;
        }

        /**
         * Get localized posts text with proper plural forms
         * @param {number} count Number of posts
         * @return {string} Localized posts text
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
         * Get current locale
         * @return {string} Current locale
         */
        getLocale() {
            return postwallSettings.locale || 'en_US';
        }

        /**
         * Get activity level for a date based on real post data
         *
         * @param {Date} date The date to check
         * @return {number} Activity level (0-4)
         */
        getActivityLevel(date) {
            // If we have real data, use it
            if (this.postData) {
                const dateKey = date.toISOString().split('T')[0]; // YYYY-MM-DD format
                const postCount = this.postData[dateKey] || 0;

                // Determine activity level based on post count
                if (postCount === 0) return 0; // No posts
                if (postCount === 1) return 1; // 1 post
                if (postCount === 2) return 2; // 2 posts
                if (postCount <= 4) return 3; // 3-4 posts
                return 4; // 5+ posts
            }

            // Fallback to random for demo when no data available
            const random = Math.random();
            if (random < 0.3) return 0;
            if (random < 0.5) return 1;
            if (random < 0.7) return 2;
            if (random < 0.9) return 3;
            return 4;
        }

        /**
         * Get localized month name
         * @param {number} monthIndex - Month index (0-11)
         * @return {string} Month name
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
         * Translation helper with fallback
         * @param {string} text Text to translate
         * @return {string} Translated text
         */
        translate(text) {
            // Try to use wp.i18n if available
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(text, 'postwall');
            }

            // Fallback: manual translation based on locale
            if (this.getLocale().startsWith('ru')) {
                const russianTranslations = {
                    'Posts from the site for the last 12 months': 'Посты с сайта за последние 12 месяцев',
                    'Loading post wall...': 'Загрузка кафельной стенки...',
                    'Failed to load post data': 'Не удалось загрузить данные постов',
                    'Error loading data': 'Ошибка при загрузке данных',
                    'posts': 'постов',
                    'Jan': 'янв', 'Feb': 'фев', 'Mar': 'мар', 'Apr': 'апр',
                    'May': 'май', 'Jun': 'июн', 'Jul': 'июл', 'Aug': 'авг',
                    'Sep': 'сен', 'Oct': 'окт', 'Nov': 'ноя', 'Dec': 'дек'
                };
                return russianTranslations[text] || text;
            }

            // Default to English
            return text;
        }
    }

    /**
     * Initialize PostWall instances when DOM is ready
     */
    $(document).ready(function() {
        $('.postwall-container').each(function() {
            new PostWall(this);
        });
    });

})(jQuery);