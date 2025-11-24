=== Admin Panel Trash ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: admin bar, admin panel, admin interface, toolbar, admin menu, customize admin, remove elements, wp admin
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for convenient management of admin bar elements. Allows temporarily disabling/enabling individual admin panel elements without the need to edit theme files.

== Description ==

Admin Panel Trash is a WordPress plugin for efficient management of the top admin bar elements. The plugin allows you to temporarily disable/enable individual admin panel elements without the need to edit theme files.

== Features ==

* Complete list of all available admin bar elements
* Ability to temporarily disable unnecessary elements
* Visual interface showing the status of each element
* AJAX management without page reload
* Automatic code insertion into active theme's functions.php file
* File access permissions checking
* Secure AJAX request handling with nonce protection
* Full localization in Russian and English languages

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings' → 'Admin Panel Trash' for configuration

== Frequently Asked Questions ==

= Do I need to manually edit the theme file? =

No, the plugin automatically adds the necessary code to your theme's functions.php file. If the theme doesn't have a functions.php file, the plugin will create it automatically.

= Can I disable standard WordPress elements? =

Yes, the plugin allows you to disable any standard admin bar elements, including the WordPress logo, site name, dashboard, and others.

= Does the plugin work with multisite installations? =

Yes, the plugin is fully compatible with WordPress multisite installations.

= How do I disable the plugin functionality? =

Simply deactivate the plugin, and it will automatically remove its code from the functions.php file. Or use the "Cleanup" function on the plugin settings page.

== Changelog ==

= 2.0.0 (19.11.2025): =
* Complete code base modernization according to modern PHP standards
* Added detailed comments in Russian language
* Improved security with enhanced input data validation
* Optimized file system and database operations
* Improved Admin Bar element scanning algorithm
* Added support for all standard WordPress admin bar elements
* Implemented automatic synchronization between database and functions.php file
* Improved interface with more intuitive management
* Added AJAX endpoints for all operations
* Implemented automatic function cleanup system from theme file

= 1.0.0: =
* First version of the plugin
* Basic admin bar element management
* Support for core functions

== Upgrade Notice ==

= 2.0.0 =
Recommended to upgrade to version 2.0.0 for better performance, security, and new admin bar management features.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - plugin initialization
* `plugins_loaded` - loading translations
* `admin_menu` - settings page registration
* `admin_enqueue_scripts` - resources loading
* `wp_before_admin_bar_render` - element disabling

=== AJAX Endpoints ===
* `wp_ajax_apt_check_file_access` - file access checking
* `wp_ajax_apt_toggle_item` - element state toggling
* `wp_ajax_apt_get_items` - getting element list
* `wp_ajax_apt_get_function_code` - function code generation
* `wp_ajax_apt_cleanup_function` - function cleanup from file

=== Security ===
* WordPress nonce usage for AJAX request protection
* Sanitization of all input data
* User permissions checking (manage_options)
* Safe file system operations

=== Performance ===
* Lazy loading of resources only on plugin page
* Minimized HTTP requests via AJAX
* Efficient WordPress database operations

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru