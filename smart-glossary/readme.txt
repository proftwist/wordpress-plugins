=== Smart Glossary Autolinker ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: glossary, autolink, terms, definitions, abbr
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically finds terms in text and wraps them in abbr tags with definitions.

== Description ==

Smart Glossary Autolinker is a WordPress plugin that automatically finds predefined terms in your post content and wraps them in HTML `<abbr>` tags with tooltip definitions. This helps readers understand technical terms and jargon without cluttering your content.

== Features ==

* Automatic term detection in post content
* Smart matching that avoids links and HTML tags
* Case-insensitive term matching
* Visual highlighting with dotted underline and background color
* Tooltip definitions on hover
* Easy management interface in WordPress Settings
* Edit existing terms and definitions
* Full localization support (Russian and English)
* Enable/disable plugin functionality
* Sort terms by length to avoid partial matches

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Smart Glossary
4. Add terms and their definitions
5. The plugin will automatically highlight terms in your posts

== Frequently Asked Questions ==

= How does the plugin work? =

The plugin scans your post content and automatically wraps matching terms in `<abbr>` tags with title attributes. When users hover over highlighted terms, they see the definition in a tooltip.

= Will it replace terms inside links? =

No, the plugin is smart enough to skip terms that are already inside HTML links or other HTML tags.

= Can I disable the plugin without deactivating it? =

Yes, there's a checkbox in Settings → Smart Glossary to enable/disable the plugin functionality.

= What happens if I have overlapping terms? =

The plugin sorts terms by length (longest first) to ensure longer phrases are matched before shorter ones. For example, "WordPress Plugin" will be matched before "WordPress".

= Is it case-sensitive? =

No, term matching is case-insensitive. "WordPress" will match "wordpress", "WORDPRESS", etc.

== Changelog ==

= 1.1.0 (2024): =
* Added ability to edit terms and definitions in admin panel
* Edit button in terms list
* Edit form with pre-filled data
* Updated localization with new strings for editing

= 1.0.0 (01.10.2023): =
* Initial release
* Automatic term detection and linking
* Admin interface for managing terms
* Visual highlighting with CSS styles
* Full localization support (Russian and English)
* Settings page in WordPress Settings menu
* Enable/disable functionality
* Smart term matching that avoids HTML tags and links

== Upgrade Notice ==

= 1.1.0 =
Added ability to edit existing terms and definitions directly from the admin panel.

= 1.0.0 =
Initial release of Smart Glossary Autolinker.

== Technical Details ==

=== WordPress Hooks Used ===
* `admin_menu` - adds settings page
* `the_content` - filters post content to add abbr tags
* `plugins_loaded` - loads text domain for translations
* `wp_enqueue_scripts` - enqueues CSS styles

=== Database Tables ===
* `wp_smart_glossary` - stores terms and definitions

=== Security Features ===
* WordPress nonce protection for all forms
* Input sanitization for all user data
* Output escaping for all displayed content
* Capability checks for admin functions

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru
