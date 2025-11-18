=== Easy Changelog ===
Contributors: vbychko
Donate link: http://bychko.ru
Tags: changelog, gutenberg, block, json
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Gutenberg block for creating beautiful changelogs with JSON data and live preview.

== Description ==

Easy Changelog is a WordPress plugin that provides a Gutenberg block for displaying beautiful changelogs. The block features two tabs:

1. **JSON Editor** - Where you can input your changelog data in JSON format
2. **Preview** - Live preview of how your changelog will look

The plugin supports multiple releases with version numbers, dates, and lists of added features. It's fully responsive and includes RTL support.

== Installation ==

1. Upload the `easy-changelog` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the "Easy Changelog" block to any post or page
4. Use the JSON Editor tab to input your changelog data
5. Switch to the Preview tab to see how it will look

== Changelog ==

= 1.0.0 =
* Initial release
* Gutenberg block with JSON editor and preview tabs
* Beautiful, responsive design
* RTL support
* Russian and English translations

== Frequently Asked Questions ==

= What JSON format should I use? =
Use the following format:

[
    {
        "version": "1.0.0",
        "date": "2024-01-15",
        "added": [
            "Initial release",
            "Basic functionality"
        ]
    }
]

= Can I customize the styling? =
Yes, you can override the CSS classes in your theme's stylesheet.

== Screenshots ==

1. The Easy Changelog block in the Gutenberg editor
2. JSON Editor tab with sample data
3. Preview tab showing the formatted changelog

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.