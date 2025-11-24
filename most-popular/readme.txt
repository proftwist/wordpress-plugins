=== Most Popular Posts by Year ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: popular posts, gutenberg, block, post views, statistics, year, table, ranking
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg block for displaying most popular posts by view count for a specific year in table format.

== Description ==

Most Popular Posts by Year is a WordPress Gutenberg block plugin that displays the most viewed posts for a selected year in a clean table format. The block shows post titles with links and their view counts, helping visitors discover your most engaging content.

== Features ==

* Gutenberg block "Most Popular Posts by Year"
* Configurable number of posts to display (5-20 posts)
* Year selection dropdown with current year and all available years
* Clean table display with post titles and view counts
* Automatic caching for 1 hour to reduce database load
* Requires Post Views Counter plugin for view count data
* Full localization support (Russian and English)
* Responsive table design
* Error handling for missing data
* Admin notifications for missing dependencies

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and activate the "Post Views Counter" plugin (required dependency)
4. Add the "Most Popular Posts by Year" block to your posts or pages
5. Configure the number of posts and select the desired year

== Frequently Asked Questions ==

= Do I need any additional plugins? =

Yes, this plugin requires the "Post Views Counter" plugin to be installed and activated. It uses this plugin's data to determine which posts are most popular.

= How does it determine "popular" posts? =

The plugin uses view count data from the Post Views Counter plugin to rank posts by the number of views they received during the selected year.

= Can I customize the appearance? =

The block displays posts in a clean table format. You can style the table using your theme's CSS or custom styles.

= What if there are no posts for a selected year? =

The plugin will display a message indicating that no posts were found for the selected year.

= Is the plugin translated? =

Yes, the plugin includes full localization support and is available in Russian and English.

= How often is the data updated? =

The plugin caches results for 1 hour to improve performance. Data will refresh automatically after the cache expires.

= Can I use this with custom post types? =

Currently, the plugin only works with standard WordPress posts (post_type = 'post').

== Changelog ==

= 1.1.0 (2025-11-24): =
* Fixed SQL query error that prevented displaying data for the current year
* Now correctly sums views for all days of the selected year
* Improved database query performance
* Enhanced error handling for edge cases

= 1.0.0 (2025-11-24): =
* Initial plugin release
* Gutenberg block "Most Popular Posts by Year"
* Settings for number of posts and year selection
* Data caching for 1 hour
* Localization for Russian and English languages
* Table display with post titles and view counts
* Admin notifications for missing dependencies

== Upgrade Notice ==

= 1.1.0 =
Critical fix for current year data display. Update immediately if using the current year option.

== Technical Details ==

=== Required Dependencies ===
* Post Views Counter plugin (must be installed and activated)

=== WordPress Hooks Used ===
* `plugins_loaded` - text domain loading
* `admin_notices` - dependency checking
* `rest_api_init` - REST API routes registration
* `init` - block registration

=== REST API Endpoints ===
* `/most-popular/v1/get-years` - retrieve available years list

=== Database Queries ===
* Uses Post Views Counter's `post_views` table for view count data
* Optimized SQL with proper JOINs and GROUP BY clauses
* Cached results to reduce database load

=== Caching System ===
* WordPress transients for HTML output caching
* 1-hour cache duration for popular posts data
* Separate cache key for available years list
* Automatic cache invalidation

=== Performance Features ===
* Efficient database queries with proper indexing
* Client-side caching of year list
* Minimal impact on page load times
* Optimized for sites with large numbers of posts

=== Security Features ===
* Proper data sanitization and escaping
* SQL injection protection through prepared statements
* Nonce verification for AJAX requests
* Input validation for all user parameters

=== Browser Support ===
* Modern browsers with ES6 support
* Responsive design for mobile devices
* Cross-platform compatibility

=== File Structure ===
* `most-popular.php` - main plugin file with block registration
* `build/` - compiled block assets (JavaScript and CSS)
* `src/` - source files for block development
* `languages/` - translation files

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru