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
                        this.showError(wp.i18n.__('Failed to load post data', 'postwall'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    this.showError(wp.i18n.__('Error loading data', 'postwall'));
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

            // Create the heatmap wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'heatmap-wrapper';
            console.log('Wrapper created');

            // Create months container
            const monthsContainer = document.createElement('div');
            monthsContainer.className = 'months';

            const now = new Date();
            const monthNames = [
                wp.i18n.__('Jan', 'postwall'), wp.i18n.__('Feb', 'postwall'), wp.i18n.__('Mar', 'postwall'),
                wp.i18n.__('Apr', 'postwall'), wp.i18n.__('May', 'postwall'), wp.i18n.__('Jun', 'postwall'),
                wp.i18n.__('Jul', 'postwall'), wp.i18n.__('Aug', 'postwall'), wp.i18n.__('Sep', 'postwall'),
                wp.i18n.__('Oct', 'postwall'), wp.i18n.__('Nov', 'postwall'), wp.i18n.__('Dec', 'postwall')
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

             // Add tooltip with post count - ИСПОЛЬЗУЕМ ЛОКАЛИЗОВАННУЮ ДАТУ
             const postCount = this.postData ?
                 (this.postData[cellDate.toISOString().split('T')[0]] || 0) : 0;

             // Форматируем дату согласно настройкам WordPress
             const formattedDate = this.formatDateAccordingToWordPress(cellDate);

             // Создаем локализованный текст для тултипа
             const postText = this.getPostsText(postCount);
             const tooltipText = formattedDate + ': ' + postCount + ' ' + postText;
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
         * Get month name in Russian
         * @param {number} monthIndex - Month index (0-11)
         * @return {string} Month name
         */
        getMonthName(monthIndex) {
            const monthNames = [
                wp.i18n.__('Jan', 'postwall'), wp.i18n.__('Feb', 'postwall'), wp.i18n.__('Mar', 'postwall'),
                wp.i18n.__('Apr', 'postwall'), wp.i18n.__('May', 'postwall'), wp.i18n.__('Jun', 'postwall'),
                wp.i18n.__('Jul', 'postwall'), wp.i18n.__('Aug', 'postwall'), wp.i18n.__('Sep', 'postwall'),
                wp.i18n.__('Oct', 'postwall'), wp.i18n.__('Nov', 'postwall'), wp.i18n.__('Dec', 'postwall')
            ];
            return monthNames[monthIndex];
        }
        
        /**
         * Get localized posts text with proper plural forms
         * @param {number} count Number of posts
         * @return {string} Localized posts text
         */
        getPostsText(count) {
            // Для русского языка - особые правила множественного числа
            if (postwallSettings.locale && postwallSettings.locale.startsWith('ru')) {
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
            
            // Для английского и других языков - простые правила
            return count === 1 ? wp.i18n.__('post', 'postwall') : wp.i18n.__('posts', 'postwall');
        }
        
        /**
         * Format date according to WordPress date format settings
         * @param {Date} date - Date to format
         * @return {string} Formatted date string
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
         * Format date using WordPress date format
         * @param {Date} date - Date to format
         * @param {string} format - WordPress date format string
         * @return {string} Formatted date
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
                'M': () => this.getMonthName(date.getMonth()).slice(0, 3)
            };

            let result = format;
            for (const [key, formatter] of Object.entries(replacements)) {
                result = result.replace(new RegExp(key, 'g'), formatter());
            }

            return result;
        }

        /**
         * Get localized date format as fallback
         * @param {Date} date - Date to format
         * @return {string} Formatted date
         */
        getLocalizedDateFormat(date) {
            const locale = postwallSettings.locale || 'en_US';

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