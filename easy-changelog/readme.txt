=== Easy Changelog ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: changelog, github, automation, blocks, gutenberg, json, sync, webhook, version history
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg block for displaying changelog with automatic synchronization from GitHub via webhooks.

== Description ==

Easy Changelog is a WordPress Gutenberg block plugin that allows you to display changelog history with automatic synchronization from GitHub repositories. The plugin provides seamless integration between your development workflow and WordPress content management.

== Features ==

* Gutenberg block for displaying changelog history
* Automatic GitHub synchronization via webhooks for instant updates
* External JSON support from GitHub and other sources
* Three-tab editor interface: JSON, External JSON, Preview
* Real-time preview while editing changelog data
* Version tracking with date, added items (green markers), and fixes (blue markers)
* Database tracking system for automatic block updates
* Visual webhook setup interface with status indicators
* Responsive design for all devices
* Full localization in Russian and English
* REST API endpoints for external data fetching
* Background processing without performance impact
* Secure webhook payload validation

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the Easy Changelog block to your posts or pages
4. Configure external JSON URL or enable GitHub webhook synchronization

== Frequently Asked Questions ==

= How does automatic GitHub synchronization work? =

The plugin provides webhook endpoints that receive push events from GitHub. When you push changes to your repository, the webhook automatically updates the changelog block with fresh data from your JSON file.

= How do I set up GitHub webhook synchronization? =

1. Add an Easy Changelog block to your post/page
2. Click "Copy Webhook URL" in the block editor
3. In your GitHub repository, go to Settings → Webhooks → Add webhook
4. Paste the webhook URL and select "Just the push event"
5. Save - automatic synchronization is now active!

= What JSON format is supported? =

The plugin supports an array of releases with the following structure:
```
[
  {
    "version": "1.0.0",
    "date": "19.11.2025",
    "added": ["New feature", "Another addition"],
    "fixed": ["Bug fix", "Another correction"]
  }
]
```

= Does it work with external JSON files? =

Yes, you can link to external JSON files from GitHub or other sources. The plugin will automatically fetch and cache the data for optimal performance.

= Can I use it without GitHub integration? =

Absolutely! You can manually edit JSON data within the block editor or use static external JSON files without any webhook setup.

== Changelog ==

= 2.0.1 (20.11.2025): =
* Fixed critical database table creation issue for block tracking
* Moved activation/deactivation functions outside class for proper hook registration
* Added database version checking system via check_db_version()
* Improved table creation logic with process logging
* Added debug logging for block tracking during post saves
* Automatic database table creation on plugin update
* New functionality for tracking data removal from JSON
* Added empty block state handling with styled notifications
* Enhanced block update logging for diagnosis
* Block content update method now always updates post, even with empty data

= 2.0.0 (20.11.2025): =
* Complete automation with GitHub webhooks for instant data updates
* Block tracking system with external URLs via database
* Webhook endpoint /easy-changelog/v1/github-webhook for push events
* Automatic block data updates when GitHub repository changes
* Interface for copying webhook URL in block editor
* Visual synchronization status indicators in block
* Smart updating of only affected files during push events
* Background processing without performance impact
* Daily cleanup of outdated database records
* Improved caching logic (5 minutes for external data)
* Support for blockId attribute for unique block identification
* Nested block processing for complete coverage
* Refresh GitHub URL function to avoid caching
* Save post handler for automatic tracking
* Extended localization for new webhook functionality

= 1.3.0 (20.11.2025): =
* Support for external JSON files from GitHub and other sources
* Three-tab editor interface: JSON, External JSON, Preview
* REST API endpoint for secure external data loading
* External request caching for 1 hour for better performance
* Automatic GitHub URL to raw URL conversion
* Detailed instructions for obtaining GitHub links
* Graceful fallback to local data on loading errors
* URL and JSON data validation for security
* Extended localization with new text support
* Improved error handling with informative messages
* Support for jsonUrl and useExternalUrl attributes in block
* Visual loading status indicators (success/error/loading)

= 1.2.0 (20.11.2025): =
* Fixed styles for Gutenberg editor with improved compatibility
* Added support for fixed tag to display corrections
* Improved preview visualization in block editor
* Separated styles for added (green markers) and fixed (blue markers)
* Special styles for editor dark theme
* Improved mobile device adaptability
* Added animations and transitions for better UX
* High contrast and motion reduction support
* Improved CSS class semantics
* Updated default JSON with fixed usage examples
* Optimized styles for editor context work

= 1.1.0 (19.11.2025): =
* Gutenberg block for displaying changelog history with built-in JSON editor
* Real-time preview while editing JSON data
* JSON format validation with error display
* Support for release array with versions, dates, and change lists
* Block categorization in Gutenberg editor
* Interface localization in Russian and English
* Style loading only when block exists on page
* Resource versioning via filemtime
* Safe JSON data handling with escaping
* Responsive design for mobile devices
* WordPress Block API integration
* Multilingual support via wp_set_script_translations

== Upgrade Notice ==

= 2.0.1 =
Critical fix for database table creation. Update immediately if experiencing block tracking issues.

= 2.0.0 =
Major update with complete GitHub automation. Enables instant updates via webhooks.

== Technical Details ==

=== WordPress Hooks Used ===
* `init` - block initialization and localization
* `wp_enqueue_scripts` - frontend styles loading
* `enqueue_block_editor_assets` - editor scripts and styles
* `rest_api_init` - REST API endpoints registration
* `block_categories_all` - block category registration
* `save_post` - block tracking during post saves

=== REST API Endpoints ===
* `/easy-changelog/v1/fetch-external` - external data loading
* `/easy-changelog/v1/github-webhook` - GitHub webhook receiver
* `/easy-changelog/v1/webhook-url` - webhook URL generator

=== Database Tables ===
* `easy_changelog_blocks` - tracks blocks with external URLs for automatic updates

=== Security Features ===
* Webhook payload validation from GitHub
* User permission checking for admin functions
* URL validation to prevent SSRF attacks
* Secure JSON data handling with escaping
* WordPress nonce protection for AJAX requests

=== Performance Optimizations ===
* Background webhook processing without blocking
* Smart updating of only affected files
* Optimized caching for different data sources
* Lazy loading of assets only when needed
* Database optimization with proper indexing

== Support ==

For support and bug reports, please contact the plugin author through the website: https://bychko.ru