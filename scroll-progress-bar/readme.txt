=== Scroll Progress Bar - Visual Reading Progress Indicator ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: progress bar, reading progress, scroll indicator, ux, user experience, reading experience, article progress
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimalist reading progress bar at the top of the page. Improves UX for long articles and shows scroll progress.

== Description ==

**Scroll Progress Bar** is a WordPress plugin that adds a visual reading progress indicator in the form of a thin colored bar at the top of the screen. The plugin helps your website visitors understand what part of the article they have already read, significantly improving the user experience when reading long content.

### ✨ Main Features:

* **Visual progress indication**: Thin colored bar at the top of the screen
* **Smooth animation**: Dynamic width change on scroll
* **Full configuration**: Simple interface in WordPress admin panel
* **Color selection**: Configure bar color through WordPress Color Picker
* **Flexible display**: Choose page types to show (posts, pages, archives)
* **Localization**: Full support for Russian and English languages
* **Responsiveness**: Correct work on all devices

### 🎯 Perfect for:

* **Blogs with long articles** - helps readers assess content volume
* **News portals** - improves navigation through news
* **Educational sites** - facilitates reading of educational materials
* **Corporate websites** - adds professional appearance

### 🔧 Technical Features:

* **High performance**: Asynchronous updates with requestAnimationFrame
* **Security**: Using WordPress Settings API and data sanitization
* **Fast loading**: Minimal file size (~2.5KB JS + ~500B CSS)
* **Compatibility**: Works with all modern themes and plugins
* **SEO-friendly**: Does not affect indexing or loading speed

### ⚙️ Plugin Settings:

1. **Enable/disable**: Main activation toggle
2. **Bar color**: Choose any color through Color Picker
3. **Page types**: Choose where to show the progress bar:
   - Posts
   - Pages
   - Home page
   - Archives

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings > Scroll Progress Bar**
4. Configure parameters:
   * Enable the plugin
   * Select bar color
   * Mark page types for display
5. Save changes

### Color Configuration

The plugin uses standard WordPress Color Picker for color selection:
* Select color from palette or enter HEX code
* Color is automatically applied to progress bar
* All standard HEX formats are supported (#RRGGBB)

### Page Types Selection

Configure where the progress bar will be shown:
* **Posts**: All blog posts
* **Pages**: Static site pages
* **Home**: Home page
* **Archives**: Archive pages, categories, tags

== Frequently Asked Questions ==

= Does the plugin affect site loading speed? =

No, the plugin is very lightweight and optimized. JavaScript file size is only ~2.5KB, CSS ~500 bytes. Resources are loaded only on selected page types.

= Will the progress bar work on mobile devices? =

Yes, the plugin is fully responsive and works correctly on all mobile devices. Bar height is automatically adjusted for touch interfaces.

= Can I change the bar appearance? =

Yes, the bar color can be changed through plugin settings in the admin panel. For deeper customization, you can use your theme's CSS styles.

= Is the plugin compatible with my theme? =

Yes, the plugin is developed with compatibility in mind with all WordPress themes. It uses standard WordPress hooks and does not conflict with other plugins.

= Does the plugin support multilingual sites? =

Yes, the plugin is fully localized for Russian and English languages. Settings interface automatically switches depending on admin panel language.

= How to disable progress bar on specific pages? =

In plugin settings, select only those page types where the progress bar should be shown. You can disable it for all page types.

== Screenshots ==

1. Plugin settings in WordPress admin panel
2. Color selection through WordPress Color Picker
3. Page types selection for display
4. Progress bar on long article
5. Progress bar on mobile device
6. Various progress bar colors

== Changelog ==

= 1.0.0 (19.11.2025) =
* First stable plugin version
* Added visual reading progress bar
* Implemented settings through WordPress admin panel
* Added WordPress Color Picker support
* Implemented page types selection for display
* Full localization for Russian and English languages
* Responsive design for mobile devices
* High performance with requestAnimationFrame
* Security through WordPress Settings API
* Compatibility with all modern themes

== Upgrade Notice ==

= 1.0.0 =
First plugin version with complete progress bar functionality.

== Support ==

For support or bug reports:

* **Email**: vladimir@bychko.ru
* **Website**: https://bychko.ru

== Privacy Policy ==

The Scroll Progress Bar plugin does not collect, store, or transmit any users' personal data. It works only locally on your website and does not send data to external servers.

The plugin does not use cookies, does not create log files, and does not interact with external APIs or analytics services.