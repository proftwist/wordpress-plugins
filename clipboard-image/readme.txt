=== Clipboard Image ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: clipboard, image, editor, upload, gutenberg
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Paste images directly from clipboard into WordPress editor. Supports both classic and block editors.

== Description ==

Clipboard Image is a WordPress plugin that allows you to paste images directly from your clipboard into the WordPress editor. Whether you have screenshots, copied images, or other visual content in your clipboard, this plugin makes it easy to add them to your posts and pages.

== Features ==

* Direct paste from clipboard (Ctrl+V) in WordPress editor
* Support for JPG, PNG, and SVG image formats
* Automatic upload to WordPress Media Library
* Support for both Classic and Block (Gutenberg) editors
* User permission checking for secure access
* File size limit of 5MB for optimal performance
* Security protection with nonce verification
* Full localization ready for translation
* Unique filename generation to avoid conflicts
* Responsive image handling with multiple sizes

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Start using it immediately - no additional configuration required

== Frequently Asked Questions ==

= What image formats are supported? =

The plugin supports JPG, PNG, and SVG image formats for clipboard pasting.

= What is the maximum file size? =

Images are limited to 5MB to ensure optimal performance and prevent server overload.

= Does it work with Gutenberg? =

Yes, the plugin works with both the classic TinyMCE editor and the new Gutenberg block editor.

= How do I use it? =

Simply copy an image to your clipboard (right-click and copy, or take a screenshot), then place your cursor in the WordPress editor and press Ctrl+V (or Cmd+V on Mac).

= Do I need to configure anything? =

No configuration is required. The plugin works immediately after activation and automatically handles the upload process.

= Is it secure? =

Yes, the plugin includes proper security measures including nonce verification, user permission checks, and file type validation.

== Changelog ==

= 1.0.1 (24.11.2025): =
* Fixed security issue with $_FILES['image'] sanitization
* Added isset() check before using $_FILES variables
* Updated WordPress compatibility to version 6.8
* Reduced tags to 5 per WordPress.org requirements
* Enhanced file validation and error handling
* Updated POT translation file with latest strings

= 1.0.0 (19.11.2025): =
* Clipboard image paste functionality
* JavaScript handler for paste events (Ctrl+V)
* Automatic image upload to WordPress Media Library
* Unique filename generation for pasted images
* Support for various image formats (PNG, JPEG, SVG)
* User notifications for successful image uploads
* Error handling for unsupported file formats
* Full interface localization
* Modern browser compatibility
* Secure user data handling

== Upgrade Notice ==

= 1.0.0 =
First stable release of Clipboard Image plugin with direct clipboard paste functionality.

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru