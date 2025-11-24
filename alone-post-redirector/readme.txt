=== Alone Post Redirector ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: redirect, post redirect, single post, archive, 301 redirect
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirect to single post when only one post exists in archives, categories, tags, or date archives.

== Description ==

Alone Post Redirector is a WordPress plugin that automatically redirects users to the single post page when there's only one post in category, tag, or date archives. The plugin improves user experience by eliminating unnecessary navigation steps.

== Features ==

* Automatic redirection for category archives with one post
* Automatic redirection for tag archives with one post
* Automatic redirection for date archives with one post
* Configurable settings for each archive type
* Simple admin interface with checkbox controls
* Intuitive labels and descriptions
* Full multilingual support
* SEO-friendly HTTP 301 redirects
* Settings API integration for secure configuration

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings' → 'Alone Post Redirector' to configure
4. Enable the redirect types you want

== Frequently Asked Questions ==

= How does the plugin work? =

When a user visits an archive page (category, tag, or date) that contains only one post, the plugin automatically redirects them to the single post page using a 301 redirect.

= Can I disable redirect for specific archive types? =

Yes, the plugin provides separate checkbox controls for categories, tags, and date archives. You can enable or disable each type independently.

= Will this affect my SEO? =

No, the plugin uses HTTP 301 redirects, which are SEO-friendly and search engines understand them as permanent redirects.

= Does the plugin work with pretty permalinks? =

Yes, the plugin works best with pretty permalinks enabled. Make sure your WordPress permalink structure is set to anything other than the default "Plain" option.

= What if I want to temporarily disable the plugin? =

Simply deactivate the plugin from the 'Plugins' menu. When you reactivate it, your settings will be preserved.

== Changelog ==

= 1.0.0 (19.11.2025): =
* Automatic redirection to single post when only one post exists in archives
* Support for category archives with single post
* Support for tag archives with single post
* Support for date archives with single post
* Admin panel settings for managing redirect types
* HTTP 301 redirects for SEO optimization
* Post count verification before performing redirect
* Full localization in Russian and English languages
* WordPress Settings API integration
* Secure user permissions checking

== Upgrade Notice ==

= 1.0.0 =
First stable release of Alone Post Redirector plugin with automatic post redirection functionality.

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru