=== Image Spoiler ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: spoiler, image, blur, gutenberg, block, sensitive content, content warning, privacy
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds blur spoiler functionality to Gutenberg Image blocks with optional warning text overlay.

== Description ==

Image Spoiler is a WordPress plugin that extends the standard Image block in Gutenberg editor by adding spoiler functionality. This feature allows you to hide potentially sensitive content by blurring the image until users hover over it with their mouse cursor.

== Features ==

* "Spoiler" button in the Image block toolbar
* Blur effect on images (20px) with smooth hover transition
* Customizable warning text overlay on blurred images
* Server-side rendering through PHP filters for better performance
* Preserves original image alignment (left, right, center, full)
* Visual indication in Gutenberg editor with dotted border
* Full localization in Russian and English
* Responsive design for mobile devices
* No JavaScript required on frontend - pure CSS solution
* Compatible with all WordPress themes
* Accessibility features with proper ARIA attributes

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add an Image block to your post or page
4. Click the "Spoiler" button in the block toolbar to enable blur effect
5. Optionally customize the warning text in the right sidebar settings

== Frequently Asked Questions ==

= How do I enable the spoiler effect on an image? =

Simply select an Image block and click the "Spoiler" button (eye icon) in the block toolbar. The image will be blurred with a default warning text.

= Can I customize the warning text? =

Yes, when the spoiler is enabled, you'll see "Spoiler Settings" in the right sidebar where you can change the warning text to whatever you prefer.

= Does it work on mobile devices? =

Yes, the plugin is fully responsive and the blur effect works on touch devices as well.

= Will it affect my site's performance? =

No, the plugin uses pure CSS for the blur effect and only loads JavaScript in the editor, ensuring minimal impact on frontend performance.

= Does it work with all image alignments? =

Yes, the plugin preserves the original image alignment (left, right, center, full width) and works correctly with all alignment options.

= Can I use it with other image blocks? =

Currently, the plugin works specifically with the standard Gutenberg Image block. Support for other blocks like Gallery or Cover may be added in future versions.

= Is the spoiler text accessible? =

Yes, the warning text includes proper ARIA attributes and is screen reader friendly while being visually hidden until needed.

== Changelog ==

= 1.0.1 (23.11.2024): =
* Server-side spoiler rendering through PHP render_block filter
* Proper Gutenberg localization via wp-cli JSON files
* Fixed image alignment issue - spoiler now preserves original block alignment
* Fixed image blur functionality - effect applied through server-side rendering
* Simplified JavaScript code - removed redundant output modification filters
* Optimized CSS styles for better compatibility with various WordPress themes
* Changed spoiler application mechanism to server-side processing via PHP

= 1.0.0 (23.11.2024): =
* "Spoiler" button in Image block toolbar
* Image blur on frontend with smooth hover transition
* "Spoiler Text" field in right sidebar settings
* Warning text display over blurred image
* Full localization in Russian and English languages
* Visual spoiler indication in Gutenberg editor
* Support for all image alignment variants
* Responsive design for mobile devices

== Upgrade Notice ==

= 1.0.1 =
Critical fixes for alignment and blur functionality. Server-side rendering provides better performance and compatibility.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - text domain loading
* `enqueue_block_editor_assets` - editor resources
* `wp_enqueue_scripts` - frontend styles
* `render_block` - server-side block modification

=== Gutenberg Filters ===
* `blocks.registerBlockType` - adding new attributes to Image block
* `editor.BlockEdit` - adding control elements to editor

=== Added Block Attributes ===
* `isSpoiler` (boolean) - spoiler enable flag
* `spoilerText` (string) - warning text

=== CSS Classes ===
* `.image-spoiler` - applied to img element
* `.has-image-spoiler` - applied to figure wrapper
* `.image-spoiler-text` - warning text container

=== Performance Features ===
* Pure CSS blur effects using GPU acceleration
* JavaScript loaded only in editor
* Frontend uses only CSS (no JavaScript)
* Compressed and minified production files
* Minimal impact on page load times

=== Browser Support ===
* Modern browsers with CSS3 filter support
* Mobile browsers with touch interaction
* Cross-platform compatibility
* Progressive enhancement approach

=== Security Features ===
* Direct file access protection
* All user data processed through WordPress API
* No direct database operations
* Minimal attack surface without admin panel

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru