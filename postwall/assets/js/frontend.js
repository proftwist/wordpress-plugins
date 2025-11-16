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
            this.generateCalendar();
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
                'янв', 'фев', 'мар', 'апр', 'май', 'июн',
                'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'
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

                // Determine activity level (random for demo)
                const activityLevel = this.getActivityLevel(cellDate);

                if (activityLevel === 0) {
                    dayCell.className = 'day empty';
                } else {
                    dayCell.className = `day lvl-${activityLevel}`;
                }

                // Add tooltip
                dayCell.title = cellDate.toLocaleDateString();

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
         * Get activity level for a date (demo implementation)
         * In real implementation, this would query the site's API for post count on that date
         *
         * @param {Date} date The date to check
         * @return {number} Activity level (0-4)
         */
        getActivityLevel(date) {
            // Demo: simulate random activity
            // In production, this would make an AJAX call to get actual post data
            const random = Math.random();

            if (random < 0.3) return 0; // No posts
            if (random < 0.5) return 1; // Few posts
            if (random < 0.7) return 2; // Some posts
            if (random < 0.9) return 3; // Many posts
            return 4; // Lots of posts
        }

        /**
         * Get month name in Russian
         * @param {number} monthIndex - Month index (0-11)
         * @return {string} Month name
         */
        getMonthName(monthIndex) {
            const monthNames = [
                'янв', 'фев', 'мар', 'апр', 'май', 'июн',
                'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'
            ];
            return monthNames[monthIndex];
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