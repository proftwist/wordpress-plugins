=== Plugin Update Disabler ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: plugin updates, disable updates, block updates, plugin management, security, maintenance
Requires at least: 4.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows to forcefully block updates for selected plugins and disable all update notifications.

== Description ==

Plugin Update Disabler is a WordPress plugin that allows you to block updates for specific plugins and hide all update notifications. This is particularly useful for maintaining plugin versions that have been tested and verified to work correctly with your site, preventing unexpected updates that might break functionality.

== Features ==

* Forcefully block updates for selected plugins
* Hide all update notifications for blocked plugins
* AJAX toggle interface for quick status changes without page reload
* Confirmation dialog when unblocking plugins to prevent accidental actions
* Integration with WordPress plugin action links
* Hide update counters for blocked plugins in admin dashboard
* Full localization support (Russian and English)
* Secure AJAX handling with nonce verification
* User permission checking (update_plugins capability)
* Safe plugin management through WordPress options

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Plugins page in your WordPress admin
4. Look for "Block updates" / "Unblock updates" links in each plugin's action row
5. Click to toggle the update blocking status

== Frequently Asked Questions ==

= How do I block a plugin's updates? =

Simply go to the Plugins page and click "Block updates" in the action links for any plugin. The update will be immediately blocked without page reload.

= Can I temporarily disable the blocking? =

Yes, click "Unblock updates" for any blocked plugin. You'll be asked to confirm this action to prevent accidental unblocking.

= Will blocked plugins still work? =

Yes, blocking updates doesn't affect the plugin's functionality. It only prevents WordPress from showing update notifications and downloading new versions.

= How do I know which plugins are blocked? =

Blocked plugins will show "Unblock updates" in their action links instead of "Block updates". The update counter in your admin bar will also exclude blocked plugins.

= Is this safe to use? =

Yes, the plugin includes safety measures like confirmation dialogs for unblocking and proper user permission checks. However, be cautious about blocking security plugin updates.

= Does it work with all plugins? =

The plugin works with all WordPress plugins that use the standard WordPress update system. Some plugins with custom update mechanisms might not be affected.

= Can I block multiple plugins at once? =

Yes, you can block updates for as many plugins as needed. Each plugin can be individually controlled.

= What happens when I update WordPress core? =

Plugin update blocking is independent of WordPress core updates. You can still update WordPress while keeping specific plugins at their current versions.

== Changelog ==

= 1.0.1 (19.11.2025): =
* Fixed localization - added Russian language support
* Improved user interface with better visual indicators
* Enhanced security checks for AJAX actions
* Better error handling and user feedback

= 1.0.0 (19.11.2025): =
* Forceful blocking of updates for selected WordPress plugins
* Disabling all update notifications for blocked plugins
* AJAX update blocker toggle in plugins list
* 'Block updates' / 'Unblock updates' buttons in plugin actions
* Hiding update counters for blocked plugins
* Confirmation when unblocking plugins to prevent accidental actions
* WordPress plugin_action_links integration
* site_transient_update_plugins filtering for update blocking
* JavaScript interface with localized strings
* Full localization in Russian and English languages
* Secure AJAX request handling with nonce verification
* Blocked plugins list management through WordPress options

== Upgrade Notice ==

= 1.0.1 =
Enhanced localization and improved user interface. Update for better Russian language support.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - plugin initialization and text domain loading
* `plugin_action_links` - adding action links to plugins
* `site_transient_update_plugins` - blocking plugin updates
* `wp_get_update_data` - hiding update notifications
* `wp_ajax_toggle_plugin_block` - AJAX action handler
* `admin_enqueue_scripts` - loading admin JavaScript

=== Security Features ===
* User permission checking (update_plugins capability)
* Input validation and sanitization
* CSRF protection through WordPress nonces
* Confirmation dialog for unblocking actions
* Secure AJAX request handling

=== AJAX Endpoints ===
* `toggle_plugin_block` - toggle plugin update blocking status

=== Performance Features ===
* Minimal impact on site performance
* Efficient database queries using WordPress options
* Client-side status updates without page reload
* Cached plugin lists for better performance

=== Browser Support ===
* Modern browsers with JavaScript support
* jQuery dependency for AJAX functionality
* Cross-platform compatibility

=== File Structure ===
* `plugin-update-disabler.php` - main plugin file
* `assets/admin.js` - JavaScript for admin interface
* `languages/` - translation files (.pot, .po, .mo)

=== Settings Storage ===
* Uses WordPress options API
* Option name: `plugin_update_disabler_blocked`
* Stores array of blocked plugin file paths

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru