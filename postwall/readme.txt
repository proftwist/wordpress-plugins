=== Post Wall - Display Post Activity Calendar Charts ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: post activity, calendar, gutenberg block, charts, activity chart, post wall, heat map, visual analytics, content timeline
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The plugin displays interactive post activity calendar charts through Gutenberg blocks or shortcodes.

== Description ==

**Post Wall** is a WordPress plugin designed for visualizing post activity on your website in the form of an interactive calendar chart (heat map).

### Main Features:

* **Gutenberg block**: Easy-to-use block for post activity visualization
* **Interactive calendar grid**: Visual representation of publication intensity
* **Period settings**: View data for the last 12 months or for a specific year
* **Clickable days and months**: Navigate to post archives for selected periods
* **Multilingual support**: Full localization for Russian and English languages
* **Tooltips**: Display post count when hovering over days
* **Responsive design**: Correct display on all devices

### Technical Features:

* **Data caching**: Automatic caching for 1 hour for performance optimization
* **AJAX loading**: Asynchronous data loading without page reload
* **Security**: Protection from CSRF attacks using nonce tokens
* **REST API integration**: Data retrieval via WordPress REST API
* **Resource versioning**: Automatic cache update on changes

### Perfect for:

* **Bloggers**: Tracking publication regularity
* **Content managers**: Analyzing content strategy intensity
* **Agencies**: Demonstrating client site activity
* **Corporate blogs**: Monitoring team content work

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. In the Gutenberg editor, find the "Post Wall" block and add it to the page
4. Configure the block by specifying the site URL for analysis (optional)

### For sites analyzing activity of other WordPress sites:

Ensure that the analyzed sites:
* Have publicly accessible REST API
* Allow post retrieval without authentication
* Work over HTTPS protocol (recommended)

== Frequently Asked Questions ==

= Can I analyze activity of other WordPress sites? =

Yes, the plugin supports activity analysis of any WordPress sites with publicly accessible REST API. Simply specify the site URL in the block settings.

= Does the plugin work with WordPress multisite installation? =

Yes, the plugin is compatible with WordPress multisite installation and can analyze activity of both the main site and individual sites in the network.

= Are there limits on the number of analyzed sites? =

There are no strict limits, but for optimal performance it is recommended not to analyze more than 10 sites simultaneously on one page.

= Does the plugin support classic WordPress editor? =

The plugin is specifically designed for Gutenberg editor. For classic editor, it is recommended to use the shortcode `[postwall]`.

= Can I customize the chart appearance? =

The chart appearance can be customized through the site's theme CSS. The plugin uses standard CSS classes for styling.

== Screenshots ==

1. "Post Wall" block in Gutenberg editor
2. Block settings in editor sidebar
3. Post activity calendar chart on frontend
4. Tooltips with post count on hover
5. Responsive display on mobile devices

== Changelog ==

= 2.2.0 (19.11.2025) =
* Added month clickability for archive navigation
* Improved interface localization
* Optimized data loading performance
* Added support for header tag configuration
* Fixed minor errors and improved stability

= 2.1.2 (15.11.2025) =
* Added ability to select specific year for analysis
* Improved API error handling
* Optimized database queries
* Added additional security checks

= 2.0.0 (10.11.2025) =
* Complete redesign with transition to Gutenberg blocks
* Modular architecture with separate classes
* Added AJAX handlers for asynchronous loading
* WordPress REST API integration
* Data caching system
* Multilingual support

= 1.0.0 (01.11.2025) =
* First plugin version
* Basic functionality for displaying post activity calendar

== Upgrade Notice ==

= 2.2.0 =
Recommended update for improved performance and month clickability.

= 2.1.2 =
Update adds the ability to analyze data for specific years and improves stability.

= 2.0.0 =
Major update with transition to modern Gutenberg block architecture.

== Support ==

For technical support or bug reports:

* **Email**: vladimir@bychko.ru
* **Website**: https://bychko.ru
* **GitHub**: https://github.com/bychkosoft/post-wall

== Privacy Policy ==

The plugin does not collect, store, or transmit users' personal data. All data is taken from publicly available sources (WordPress REST API).

The plugin can analyze activity of other WordPress sites, but only those that have publicly accessible REST API and do not require authentication for post data retrieval.