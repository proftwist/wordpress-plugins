=== GitHub Commit Chart ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: github, commits, chart, visualization, gutenberg, block, api, developer, activity, heatmap
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays GitHub commit charts as Gutenberg blocks or shortcodes with 6-year activity history.

== Description ==

GitHub Commit Chart is a WordPress plugin that allows you to display GitHub commit activity charts directly on your website. The plugin provides an interactive visualization of developer activity using both Gutenberg blocks and shortcodes, with complete support for contributions over a 6-year period.

== Features ==

* Interactive commit activity heatmap with Chart.js
* Complete 6-year commit history (2020-2025) using GitHub Search API
* Support for contributions in external repositories (not just your own)
* Gutenberg block for modern WordPress editor
* Shortcode support for classic editor and page builders
* GitHub token support for increased API limits (up to 5000 requests/hour)
* Year selector for switching between different periods
* Bilingual support (Russian and English)
* Caching system for optimal performance
* Responsive design for all devices
* Clickable GitHub profile links
* Clean, professional visualization
* Module-based architecture for easy maintenance

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → GitHub Commit Chart to configure your GitHub profile
4. Optionally add your GitHub token to increase API rate limits
5. Add the "GitHub Commit Chart" block to your posts/pages or use shortcodes

== Frequently Asked Questions ==

= How do I get my GitHub username? =

Your GitHub username is displayed on your GitHub profile page. It's the name that appears after github.com/ in your profile URL.

= What is the difference between using a GitHub token or not? =

Without a token, you can make 60 API requests per hour. With a personal access token, you can make up to 5000 requests per hour. This is especially useful for sites with high traffic or when displaying charts for multiple users.

= How do I get a GitHub personal access token? =

1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Click "Generate new token (classic)"
3. Select the "public_repo" scope (minimal permissions needed)
4. Copy the token and paste it in the plugin settings

= Can I use this with page builders like Elementor? =

Yes! Use the shortcode: `[github-c github_profile="yourusername"]`

= Does it work with private repositories? =

The plugin displays public contributions only. For private repositories, you'll need appropriate permissions, but the standard usage shows public activity.

= Why doesn't it show my complete history? =

The plugin shows 6 years of history (2020-2025) by default. Use the year selector to view different periods. Data comes directly from GitHub's Search API for maximum accuracy.

== Changelog ==

= 2.1.1 (19.11.2025): =
* Complete 6-year commit history implementation via GitHub Search API
* New get_all_user_commits() method for finding all user commits
* Support for contributions in external repositories (not just own repositories)
* Optimized queries by specific year for minimal API load
* Separate caching of all_commits for each year (2020-2025)
* Extended year validation with 6-year range in AJAX handler
* Accurate GitHub contributions matching via Search API
* Protection from infinite loops - maximum 10 pages (1000 commits)
* Updated documentation and technical specification

= 2.1.0 (19.11.2025): =
* Full GitHub Events API integration for tracking all activity
* Support for commits, Issues, Pull Requests, reviews and comments
* Smart activity weighting system with different weights for event types
* Fixed filtering by author instead of committer for accuracy
* Included forked repositories in activity analysis
* Pagination support: up to 1000 commits and 500 events
* New API methods: get_user_events(), is_relevant_event(), calculate_event_weight()
* Updated caching system with events_ and activity_ keys
* Comprehensive input data validation and error protection
* Removed problematic usleep() function for compatibility
* Protection from fatal PHP errors with graceful degradation
* Complete technical documentation (350 lines)

= 2.0.1 (10.11.2025): =
* Improved localization system with automatic language switching
* Language synchronization between admin and frontend
* Correct display of month and day names in charts
* Optimized translation loading system
* Improved WordPress language settings handling

= 1.8.4 (25.10.2024): =
* Complete architectural refactor to modular system
* Creation of specialized classes: Assets Manager, AJAX Handler, Shortcode Handler
* Full WP-L10n support for multilingual functionality
* Enhanced input data validation with sanitize_text_field()
* Improved nonce handling for AJAX requests
* Optimized caching and resource versioning
* Better error handling and edge cases

= 1.8.0 (01.10.2024): =
* Year selector for switching between commit periods
* Display of 6 previous years + current year
* Centering selector relative to chart
* Lazy loading for previous years commits
* Smooth loading animation without interface jumps

= 1.7.0 (20.09.2024): =
* GitHub token support for increased API limits
* AJAX token validation in real time
* Rate limit increase from 60 to 5000 requests/hour
* Token configuration in plugin admin panel

= 1.6.0 (10.09.2024): =
* Clickable links to GitHub profiles in chart headers
* Links open in new tab
* Setting to enable/disable links in admin panel

= 1.5.0 (20.08.2024): =
* Header tag selection in Gutenberg editor: H2, H3, H4 or text without tag
* Centering of all header variants for aesthetic presentation
* Header configuration through block interface

= 1.4.0 (10.08.2024): =
* Shortcode support for classic editor
* Basic shortcode [github-c github_profile="username"]
* Page builder compatibility (Elementor and others)
* Shortcode usage documentation

= 1.3.0 (25.07.2024): =
* Interactive Chart.js-based diagram
* Developer activity heatmap
* Weekly activity display
* Commit intensity color gradient
* Responsive design for mobile devices

= 1.2.0 (15.07.2024): =
* Gutenberg block for modern WordPress editor
* Block configuration interface in editor
* Chart preview in editor
* WordPress Block API integration

= 1.1.0 (05.07.2024): =
* Caching system via WordPress transients
* Commit data caching for 1 hour
* Improved GitHub API error handling
* GitHub username validation
* Protection from API rate limit exceeding

= 1.0.0 (25.06.2024): =
* Initial plugin release
* Basic GitHub commit display functionality
* GitHub REST API v3 integration
* Year commit data retrieval
* Bar chart visualization
* CSS and JavaScript resource connection
* Basic admin panel settings
* Direct access protection and basic security measures

== Upgrade Notice ==

= 2.1.1 =
Major update with complete 6-year commit history via Search API. Includes contributions in external repositories and optimized performance.

= 2.1.0 =
Full GitHub Events API integration with support for all activity types. Enhanced accuracy and performance.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - plugin initialization
* `admin_menu` - admin menu registration
* `plugins_loaded` - text domain loading
* `enqueue_block_editor_assets` - block editor resources
* `wp_enqueue_scripts` - frontend resources

=== GitHub API Endpoints ===
* Search Commits API - for complete commit history
* User Repositories API - for repository data
* Rate Limit API - for API usage monitoring

=== AJAX Endpoints ===
* `gcc_get_commit_data` - chart data retrieval
* `gcc_check_github_token` - token validation
* `gcc_clear_cache` - cache clearing

=== Shortcode Usage ===
Basic: `[github-c github_profile="username"]`
With year: `[github-c github_profile="username" year="2023"]`

=== Performance Features ===
* Intelligent caching with TTL
* Pagination for large data volumes
* Batch repository processing
* API rate limit protection
* Lazy loading for previous years
* Optimized compiled resources

=== Security Features ===
* GitHub API token validation
* WordPress nonce for all AJAX requests
* Input data sanitization
* CSRF protection
* Graceful error handling
* Username format validation

=== Caching System ===
* Separate cache keys for each year (2020-2025)
* 1-hour TTL for all cache types
* Automatic cache invalidation
* Efficient storage via WordPress transients

=== Browser Support ===
* Modern browsers with ES6+ support
* Mobile browsers with touch interaction
* Cross-platform compatibility
* Progressive enhancement

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru