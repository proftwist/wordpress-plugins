=== File Size Limit ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: file size, upload limit, media, files, restriction, limit, bytes, media files
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds an option to limit the size of uploaded files in WordPress Settings → Media.

== Description ==

File Size Limit is a WordPress plugin that adds a powerful tool for controlling the size of uploaded files. It integrates into the standard media settings page and allows administrators to set maximum file size in bytes with the highest priority.

== Features ==

* Integration into Settings → Media page
* Precise file size limit in bytes
* Highest priority over other system limits
* Full localization support (Russian and English)
* Safe validation and sanitization of input data
* Display of current WordPress system limits
* Ability to disable limitation (value 0)
* Detailed tips and recommendations for users
* Protection against incorrect data
* Simple and clear interface
* No external dependencies
* Minimal resource usage
* WordPress standards compliance

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Media
4. Find the "File Size Limit" section
5. Set the desired maximum size in bytes or 0 to disable the limit
6. Save changes

== Frequently Asked Questions ==

= How do I set a file size limit? =

Go to Settings → Media and in the "File Size Limit" section enter the desired size in bytes. For example, 1048576 bytes = 1 MB.

= What does the value 0 mean? =

The value 0 disables the limitation, allowing files of any size to be uploaded within WordPress system limits.

= What priority does this limitation have? =

The limitation has the highest priority and overrides all other WordPress limits, including system server settings.

= Is the plugin safe? =

Yes, the plugin uses standard WordPress security functions, including data validation and protection against incorrect values.

= Does the plugin support translations? =

Yes, the plugin is fully localized into Russian and English languages using the standard WordPress translation system.

= Does the plugin affect performance? =

No, the plugin minimally uses resources and works only during file uploads.

== Changelog ==

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

= 1.0.0 =
First release of File Size Limit plugin with full upload restriction functionality.

== Technical Details ==

=== WordPress Hooks Used ===
* `plugins_loaded` - text domain loading
* `admin_init` - settings registration
* `upload_size_limit` - upload limit setting (priority 999)

=== WordPress Functions Used ===
* `register_setting()` - setting registration
* `add_settings_section()` - section addition
* `add_settings_field()` - field addition
* `get_option()` - getting saved value
* `wp_max_upload_size()` - getting system limit
* `size_format()` - file size formatting
* `load_plugin_textdomain()` - loading translations

=== Security Features ===
* Direct access check via ABSPATH
* Input data validation via absint()
* Output sanitization via esc_attr()
* XSS protection in user interface
* Standard WordPress security functions

=== Settings API ===
* WordPress Settings API integration
* Sanitization callbacks for all settings
* Automatic value validation
* Settings storage in WordPress database

=== File Structure ===
* `file-size-limit.php` - main plugin file
* `languages/file-size-limit.pot` - translation template
* `languages/file-size-limit-ru_RU.po` - Russian localization
* `languages/file-size-limit-en_US.po` - English localization
* `languages/file-size-limit-ru_RU.mo` - compiled Russian
* `languages/file-size-limit-en_US.mo` - compiled English

=== Configuration Options ===
* `dipsic_max_upload_size` - maximum file size in bytes
* 0 = no limit
* Positive number = maximum size in bytes

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru