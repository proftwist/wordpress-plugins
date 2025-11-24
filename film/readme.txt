=== Film Strip ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: gallery, images, film, photos, gutenberg, block, horizontal scroll, vintage, media
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg block for creating image galleries in classic film strip style with horizontal scrolling.

== Description ==

Film Strip is a WordPress Gutenberg block plugin that allows you to create beautiful image galleries in the style of classic film strips with horizontal scrolling. Perfect for showcasing photo collections, portfolios, or any series of images in an elegant vintage-style layout.

== Features ==

* Classic film strip design with perforation edges
* Horizontal scrolling gallery with smooth navigation
* Customizable film height (100-1000px with 10px steps)
* Multiple image selection from WordPress Media Library
* Three link options: no links, link to image file, link to attachment page
* Full width support (alignfull) for edge-to-edge display
* White frame around each image for authentic film look
* Dark film texture background
* Mobile-responsive design with smooth scrolling
* Perfect image scaling with object-fit: cover
* Complete localization
* Performance optimized with separated styles for editor and frontend

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the "Film Strip" block to your posts or pages
4. Select images and configure height and link settings

== Frequently Asked Questions ==

= How do I add images to the film strip? =

Click "Select Images" in the block editor to open the WordPress Media Library. You can select multiple images at once and they will be arranged in the film strip.

= Can I adjust the height of the film strip? =

Yes, you can set the height from 100px to 1000px in 10px increments using the height control in the block sidebar.

= What link options are available? =

You can choose from three options:
- No links: Images are not clickable
- Link to file: Opens the original image in a new tab
- Attachment page: Links to the WordPress attachment page

= Does it work on mobile devices? =

Yes, the plugin is fully responsive and includes optimized mobile scrolling for a great user experience on all devices.

= Can I make it full width? =

Yes, use the block alignment controls in the toolbar to make the film strip full width for edge-to-edge display.

= What image formats are supported? =

The plugin supports all image formats supported by WordPress, including JPG, PNG, GIF, and WebP.

== Changelog ==

= 1.1.1 (21.11.2025): =
* Added support for full-width display (alignfull mode)
* Implemented CSS rules using calc(50% - 50vw) and width: 100vw for complete width coverage
* Fixed issue with full-width mode not extending to the right page border
* Corrected margin-left and margin-right for alignfull mode
* Fixed error in check-translations.js script when reading PHP file

= 1.1.0 (21.11.2025): =
* Reduced image size in editor to match frontend display
* Added white frame wrapper through .film-frame::before pseudo-element
* Decreased film height in editor to 300px for better display
* Improved CSS structure with positioning and z-index for images
* Maintained object-fit: cover scaling in editor

= 1.0.5 (21.11.2025): =
* Simplified React component architecture using React.createElement instead of JSX
* Improved code structure with editing functions separated into individual methods
* Added detailed Russian comments for all functions
* Overhauled error handling system with wp.media availability checking
* Removed all hover effects from plugin for improved performance
* Created separate style-frontend.css file for frontend
* Added film_disable_hover_effects() function for forced animation disabling

= 1.0.4 (21.11.2025): =
* Added white frame for images through new .film-image-wrapper element
* Increased bottom image frame to 12px for improved appearance
* Added detailed Russian comments throughout the code
* Improved JavaScript code structure with documented functions
* Created src/index.js with block source code
* Created src/style.css with editor styles
* Added editor styles with hover effect

= 1.0.1 (20.11.2025): =
* Fixed frontend - added separate styles for editor and frontend
* Photo sorting functionality in inspector panel with "Move up/down" buttons
* Width selection buttons - alignment toolbar (column width, wide, full width)
* Image numbering in editor for easier work
* Improved image management interface with overlays
* Support for align attribute in block for alignment
* Registered separate style for frontend
* Added new style-frontend.css file for frontend
* Quick image management option through toolbar

= 1.0.0 (20.11.2025): =
* Film strip block with horizontal image scrolling
* Gutenberg editor integration
* Film height settings (100-1000px)
* Image link settings (no links, file, attachment page)
* Multiple image selection from Media Library
* Russian language localization
* Responsive design for all devices
* Classic film strip styles with perforation

== Upgrade Notice ==

= 1.1.1 =
Enhanced full-width support and improved alignment. Update for better display options.

= 1.1.0 =
Improved editor experience with better image scaling and white frame styling.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - text domain loading and script translations
* `register_activation_hook` - compatibility checking during activation

=== Block Attributes ===
* `images` - array of selected images with URLs and alt text
* `height` - film strip height in pixels (100-1000)
* `linkTo` - link behavior (none, media, attachment)
* `align` - block alignment (none, wide, full)

=== Performance Features ===
* Optimized CSS structure without redundant rules
* Minified JavaScript and CSS files
* Separated styles for editor and frontend
* Removed hover effects for better performance
* Efficient image scaling with object-fit: cover

=== Security Features ===
* All data sanitized through esc_attr() and esc_url()
* Direct file access protection
* WordPress capability checks
* Safe media library integration

=== Browser Support ===
* Modern browsers with CSS3 support
* Mobile browsers with touch scrolling
* Cross-platform compatibility
* Progressive enhancement

=== CSS Classes ===
* `.wp-block-film-film-gallery` - main block wrapper
* `.film-strip` - film strip container
* `.film-frame` - individual image frame
* `.film-image-wrapper` - image container with white frame
* `.film-image-link` - clickable image link

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru