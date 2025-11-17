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
            console.log('PostWall init called');
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
            console.log('generateCalendar called');
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
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];

            // Generate 12 months from current back to 12 months ago
            console.log('Generating months...');
            for (let i = 11; i >= 0; i--) {
                const monthDate = new Date(now);
                monthDate.setMonth(now.getMonth() - i);

                const monthDiv = this.createMonth(monthDate);
                monthsContainer.appendChild(monthDiv);
                console.log(`Month ${i} added`);
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
         * Create a month grid
         * @param {Date} monthDate - The date representing the month to create
         * @return {HTMLElement} The month container element
         */
        createMonth(monthDate) {
            console.log('createMonth called for', monthDate);
            const monthDiv = document.createElement('div');
            monthDiv.className = 'month';

            const monthGrid = document.createElement('div');
            monthGrid.className = 'month-grid';

            const year = monthDate.getFullYear();
            const month = monthDate.getMonth();

            // Get first day of month and what day of week it falls on
            const firstDay = new Date(year, month, 1);
            const firstDayOfWeek = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.

            // Adjust for Monday first (0 = Monday, 6 = Sunday)
            const adjustedFirstDay = (firstDayOfWeek + 6) % 7;

            // Get number of days in month
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            console.log(`Month ${month + 1}, days: ${daysInMonth}, first day offset: ${adjustedFirstDay}`);

            // Create cells
            // Empty cells before first day
            for (let i = 0; i < adjustedFirstDay; i++) {
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
                    dayCell.className = 'day empty';
                } else {
                    dayCell.className = `day lvl-${activityLevel}`;
                }

                // Add tooltip with post count - ЛОКАЛИЗУЕМ ТУТ!
                const postCount = this.postData ?
                    (this.postData[cellDate.toISOString().split('T')[0]] || 0) : 0;
                
                // Форматируем дату в локализованном формате
                const formattedDate = cellDate.toLocaleDateString(this.getLocale());
                
                // Создаем локализованный текст для тултипа
                const tooltipText = this.formatTooltip(formattedDate, postCount);
                dayCell.title = tooltipText;

                monthGrid.appendChild(dayCell);
            }

            monthDiv.appendChild(monthGrid);

            // Add month label
            const monthLabel = document.createElement('div');
            monthLabel.className = 'month-label';
            monthLabel.textContent = this.getMonthName(month);
            monthDiv.appendChild(monthLabel);
            console.log('Month created:', this.getMonthName(month));

            return monthDiv;
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
                if (count === 1) {
                    return 'пост';
                } else if (count >= 2 && count <= 4) {
                    return 'поста';
                } else {
                    return 'постов';
                }
            }
            
            // Для английского и других языков - простые правила
            const postsText = this.translate('posts');
            return postsText;
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
                    'posts': 'постов', // базовая форма для множественного числа
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