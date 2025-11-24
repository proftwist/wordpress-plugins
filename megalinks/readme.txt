=== Megalinks ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: tooltip, links, internal links, preview, excerpt, thumbnail, popup, hover, ajax
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds interactive popup tooltips with post excerpts and thumbnails for internal links on posts and pages.

== Description ==

Megalinks is a WordPress plugin that creates interactive popup tooltips for internal links to posts and pages. The tooltip automatically loads the post excerpt and thumbnail, providing a quick content preview that enhances user engagement and navigation.

== Features ==

* Interactive popup tooltips on hover for internal links
* Real-time AJAX loading of post excerpts
* Post thumbnail support for visual tooltips
* Smart URL-to-post-ID resolution for various permalink structures
* Admin panel settings for enable/disable functionality
* Language selection support (Russian and English)
* Dynamic resource loading only on frontend
* Multiple AJAX handlers with separate nonces for security
* Modular architecture with centralized settings management
* Text domain caching with forced translation reload
* Responsive CSS design for tooltips
* Full localization of all interface strings
* Client-side data caching for performance
* Smart positioning (up, fallback to down if needed)
* Desktop-only functionality (width > 768px)
* Dark themed tooltip design
* Support for any WordPress permalink structure

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Megalinks to configure the plugin
4. Enable the plugin and choose your preferred language
5. Tooltips will automatically appear when hovering over internal links

== Frequently Asked Questions ==

= How do the tooltips work? =

When you hover over internal links to posts or pages, a tooltip will appear showing the post excerpt and thumbnail (if available). The data is loaded dynamically via AJAX for optimal performance.

= Can I disable the plugin temporarily? =

Yes, you can easily disable the plugin from Settings → Megalinks by unchecking the "Enable plugin" option.

= Do tooltips work on mobile devices? =

Currently, tooltips are designed for desktop use only (width > 768px) to ensure optimal user experience on touch devices.

= What types of links are supported? =

The plugin processes only valid internal links to posts and pages, excluding:
- External URLs
- Archives, categories, tags
- Links within images
- Links within WordPress menus
- Anchor links (#anchor)
- Links in headers H1-H6
- Dated blocks
- Pagination elements

= Can I customize the appearance? =

The plugin includes a dark-themed tooltip design with responsive CSS. The appearance is optimized for readability and user experience.

= Is it compatible with all themes? =

Yes, the plugin works with any WordPress theme and supports all permalink structures.

= How does the caching work? =

The plugin caches thumbnail and excerpt data client-side to prevent redundant requests when hovering over the same links multiple times.

== Changelog ==

= 2.0.0 (19.11.2025): =
* Popup tooltips with excerpts for internal links to posts and pages
* AJAX real-time post excerpt retrieval
* Post thumbnail support for visual tooltips
* Post ID resolution by URL with various link format handling
* Admin panel settings for plugin enable/disable
* Interface language selection with Russian and English support
* Dynamic resource loading only on frontend
* Multiple AJAX handlers with separate nonces for security
* Modular architecture with centralized settings management
* Text domain caching with forced translation reload
* Responsive CSS design for tooltips
* Full localization of all interface strings

== Upgrade Notice ==

= 2.0.0 =
Initial release of Megalinks plugin with interactive tooltip functionality for internal links.

== Technical Details ==

=== WordPress Hooks Used ===
* `plugins_loaded` - text domain loading
* `admin_menu` - admin menu registration
* `admin_init` - settings registration
* `wp_enqueue_scripts` - frontend resources

=== AJAX Endpoints ===
* `megalinks_get_excerpt` - retrieve post excerpt
* `megalinks_get_post_id_by_url` - resolve post ID from URL
* `megalinks_get_thumbnail` - get post thumbnail

=== Security Features ===
* Individual nonces for each AJAX endpoint
* HTML cleaning with wp_strip_all_tags()
* Strict input argument validation
* Complete XSS/CSRF protection
* URL sanitization and validation

=== Performance Features ===
* Client-side data caching
* Exclusion of unnecessary links before processing
* Lazy loading of images
* Event delegation for reduced load
* Fixed container dimensions (no height jumps)

=== Browser Support ===
* Modern browsers with AJAX support
* Desktop browsers (width > 768px)
* Cross-platform compatibility
* Progressive enhancement approach

=== Settings API ===
* WordPress Settings API integration
* Sanitization callbacks for all settings
* Automatic fallback language handling
* Cache clearing on language change

=== File Structure ===
* `megalinks.php` - main logic, settings registration, AJAX, filters
* `assets/js/megalinks.js` - tooltip logic, hover, AJAX, positioning
* `assets/css/megalinks.css` - tooltip appearance

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru