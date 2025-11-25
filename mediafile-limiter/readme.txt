=== File Size Limit (PRO) ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: file size, upload limit, media, files, restriction, limit, megabytes, MB, media files, aggressive, pro, bypass
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced file size limiter with aggressive system restrictions bypass for WordPress.

== Description ==

File Size Limit (PRO) is an advanced WordPress plugin that adds powerful tools for controlling the size of uploaded files with aggressive system restriction bypass capabilities. It integrates into the standard media settings page and allows administrators to set maximum file size in megabytes with the highest priority and system override attempts.

== Features ==

=== Core Features ===
* Integration into Settings → Media page
* Precise file size limit in megabytes (MB) with 0.1 MB precision
* Highest priority over other system limits
* Full localization support (Russian and English)
* Safe validation and sanitization of input data
* Display of current WordPress system limits
* Ability to disable limitation (value 0)
* Detailed tips and recommendations for users
* Protection against incorrect data

=== PRO Features ===
* **Aggressive System Override:** Attempts to change PHP system settings
* **System Diagnostics:** Real-time analysis of all system limits
* **Enhanced Security:** Validation with pre-upload file checking
* **Smart Notifications:** Warnings about system conflicts and recommendations
* **Maximum Priority Filters:** Priority 9999 for maximum control
* **Memory Management:** Automatic memory limit optimization
* **AJAX Upload Support:** Special handling for AJAX file uploads
* **Comprehensive Logging:** System override attempt logging

=== Technical Features ===
* Upload_max_filesize override attempts
* Post_max_size override attempts
* Memory_limit optimization for large files
* Max execution time extension
* Global WordPress limit overrides
* Real-time system diagnostic display
* Visual status indicators for all system limits

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Media
4. Find the "File Size Limit (PRO)" section
5. Set the desired maximum size in megabytes or 0 to disable the limit
6. Optionally enable "Aggressive Mode" to attempt system overrides
7. Save changes

== Frequently Asked Questions ==

= How do I set a file size limit? =

Go to Settings → Media and in the "File Size Limit (PRO)" section enter the desired size in megabytes. For example, 2 MB, 5.5 MB, etc.

= What is "Aggressive Mode"? =

Aggressive Mode attempts to forcibly change PHP system settings (upload_max_filesize, post_max_size, memory_limit) to allow larger file uploads. This may not work on restricted hosting providers.

= What does value 0 mean? =

The value 0 disables the limitation, allowing files of any size to be uploaded within WordPress system limits.

= What priority does this limitation have? =

The limitation has the highest priority (9999) and overrides all other WordPress limits, including system server settings when Aggressive Mode is enabled.

= Is the plugin safe? =

Yes, the plugin uses standard WordPress security functions, including data validation, protection against incorrect values, and safe system overrides with error handling.

= Does the plugin support translations? =

Yes, the plugin is fully localized into Russian and English languages using the standard WordPress translation system.

= What is system diagnostics? =

System diagnostics shows real-time status of all relevant PHP settings (upload_max_filesize, post_max_size, memory_limit) with visual indicators and recommendations.

= Does aggressive mode always work? =

No, aggressive mode attempts to change PHP settings but may fail on restricted hosting providers where such changes are not allowed.

== Changelog ==

= 2.0.0 (25.11.2025): =
=== MAJOR UPDATE - PRO VERSION ===
* **NEW:** Aggressive system override functionality
* **NEW:** System diagnostics with real-time monitoring
* **NEW:** Enhanced security with pre-upload file validation
* **NEW:** Smart notifications and conflict warnings
* **NEW:** Memory limit optimization for large files
* **NEW:** AJAX upload special handling
* **NEW:** Comprehensive system override logging
* **NEW:** Visual status indicators for system limits
* **NEW:** Maximum priority filters (9999) for ultimate control
* **IMPROVED:** Enhanced user interface with diagnostic panel
* **IMPROVED:** Better error handling and fallback mechanisms
* **IMPROVED:** Increased maximum limit to 512 MB
* **IMPROVED:** Enhanced localization with new PRO features
* **IMPROVED:** Added JavaScript interactivity for better UX
* **IMPROVED:** Comprehensive system limit analysis
* **UPDATED:** Text domain changed to 'file-size-limit-pro'
* **UPDATED:** All translations updated for PRO functionality
* **ADDED:** New settings section for aggressive configuration
* **ADDED:** Safe system override attempts with error logging
* **ADDED:** Global WordPress limit override functions

= 1.1.0 (25.11.2025): =
* Changed file size input from bytes to megabytes for better usability
* Updated user interface to display MB units
* Improved validation for megabyte values with step 0.1
* Enhanced WordPress system limit display to show both MB and formatted size
* Updated all translations to reflect megabyte-based interface
* Added new validation messages for size limits
* Improved backward compatibility with existing byte-based settings

= 1.0.0 (25.11.2025): =
* Added file size limit option in Settings → Media
* Set maximum file size in bytes with highest priority
* Full localization support for Russian and English languages
* Integration into standard WordPress settings page
* Input data validation with protection against negative values
* Display of current WordPress system limits for reference
* Ability to disable limitation by setting value to 0
* Detailed tips and recommendations for users
* Safe sanitization and escaping of all data
* Upload_size_limit filter with priority 999 for limit override

== Upgrade Notice ==

= 2.0.0 =
MAJOR UPDATE: File Size Limit PRO introduces aggressive system override capabilities, system diagnostics, enhanced security, and maximum priority controls. New Aggressive Mode attempts to change PHP settings for larger upload limits. Backward compatible with version 1.x settings.

= 1.1.0 =
Updated to use megabytes instead of bytes for better usability. All existing settings will be automatically converted from bytes to megabytes.

= 1.0.0 =
First release of File Size Limit plugin with full upload restriction functionality.

== Technical Details ==

=== WordPress Hooks Used ===
* `plugins_loaded` - text domain loading
* `admin_init` - settings registration
* `upload_size_limit` - upload limit setting (priority 9999)
* `wp_handle_upload_prefilter` - file validation (priority 9999)
* `init` - system override attempts
* `admin_enqueue_scripts` - JavaScript loading
* `admin_notices` - system notifications
* `wp_ajax_upload-attachment` - AJAX upload handling

=== PHP Functions Used ===
* `register_setting()` - setting registration
* `add_settings_section()` - section addition
* `add_settings_field()` - field addition
* `get_option()` - getting saved value
* `wp_max_upload_size()` - getting system limit
* `size_format()` - file size formatting
* `ini_get()` - getting PHP configuration
* `ini_set()` - attempting PHP configuration changes
* `load_plugin_textdomain()` - loading translations

=== Security Features ===
* Direct access check via ABSPATH
* Input data validation via floatval() and absint()
* Output sanitization via esc_attr()
* XSS protection in user interface
* Standard WordPress security functions
* Protection against extremely large values (max 512 MB)
* Safe system override attempts with error logging
* Pre-upload file validation

=== PRO Features ===
* Aggressive system override with `ini_set()` attempts
* System diagnostics panel with visual indicators
* Memory limit optimization for large file processing
* Enhanced file validation before upload
* Smart notification system for conflicts
* Global WordPress limit override functions
* Comprehensive error logging and reporting
* Real-time system status monitoring
* Maximum priority filter implementation (9999)
* Special AJAX upload handling

=== Configuration Options ===
* `dipsic_max_upload_size_mb` - maximum file size in megabytes
* `dipsic_aggressive_mode` - enable/disable aggressive system override
* Input values in megabytes (0.1 - 512 MB)
* 0 = no limit
* Aggressive mode attempts PHP setting overrides

=== File Structure ===
* `file-size-limit.php` - main plugin file (PRO version)
* `admin.js` - JavaScript for interactive interface
* `languages/file-size-limit-pro.pot` - translation template
* `languages/file-size-limit-pro-ru_RU.po` - Russian localization
* `languages/file-size-limit-pro-en_US.po` - English localization
* `languages/file-size-limit-pro-ru_RU.mo` - compiled Russian
* `languages/file-size-limit-pro-en_US.mo` - compiled English

=== System Requirements ===
* WordPress 5.0 or higher
* PHP 7.4 or higher
* Modern browser with JavaScript support
* Recommended: Access to PHP configuration (for Aggressive Mode)

=== Performance ===
* Minimal resource usage with intelligent caching
* Efficient system limit analysis
* Optimized for large file handling
* Asynchronous system override attempts
* Smart conflict detection and resolution

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru

== Pro Features Troubleshooting ==

=== Aggressive Mode Not Working ===
Aggressive Mode may not function on hosting providers that restrict PHP configuration changes. The plugin will log attempts and provide diagnostic information.

=== System Diagnostics Warnings ===
Red indicators in system diagnostics show conflicting limits. Enable Aggressive Mode or contact your hosting provider to resolve these conflicts.

=== Memory Limit Issues
The plugin automatically attempts to optimize memory limits when Aggressive Mode is enabled. For persistent issues, manual PHP configuration may be required.