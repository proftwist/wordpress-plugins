=== Typo Reporter - System for Reporting Typos ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: typo, error reporting, content quality, user feedback, text correction, proofreading, content editing
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows users to report typos on the website. Select text with error and press Ctrl+Enter to submit a report.

== Description ==

**Typo Reporter** is a WordPress plugin that allows your website visitors to easily report typos and errors in text. The plugin creates a convenient feedback system to improve content quality and engage the audience in the editing process.

### ✨ Main Features:

* **Simple report submission**: Select text with error and press `Ctrl+Enter`
* **Modal window**: Convenient form for describing the found error
* **Administrative panel**: View and moderate all incoming reports
* **Status management**: Mark reports as new, resolved, or dismissed
* **Detailed information**: Save page URL, IP address, and User-Agent
* **Filtering and search**: Easy work with large number of reports
* **Data cleanup**: Ability to delete processed reports

### 🎯 How it works:

1. **For visitors**:
   * Select text with suspected error
   * Press `Ctrl + Enter` key combination
   * Optionally describe the error in the appearing window
   * Click "Send Report"

2. **For administrators**:
   * All reports come to a special table in admin panel
   * You can view, filter, and change statuses
   * Convenient moderation of accepted and dismissed reports

### 🔧 Technical features:

* **Database**: Creating a separate table for storing reports
* **AJAX interface**: Fast submission without page reload
* **Security**: XSS protection, validation, and data sanitization
* **Localization**: Full support for Russian and English languages
* **Responsiveness**: Correct work on all devices
* **Performance**: Optimized SQL queries and caching

### 📊 Report Management:

* **View all reports**: Complete list with detailed information
* **Status filtering**: New, resolved, dismissed
* **Text search**: Quick search among large number of reports
* **Status changes**: Mark reports as processed
* **Report deletion**: Clean processed data
* **Mass cleanup**: Delete entire table with confirmation

### 🛡️ Security and privacy:

* **Data protection**: All user data is validated and sanitized
* **XSS protection**: HTML escaping to prevent attacks
* **Access rights**: Only administrators can view reports
* **Privacy**: Saving only necessary technical information

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → Typo Reporter**
4. Enable the plugin by checking "Enable Typo Reporter"
5. Save settings

### Plugin settings:

* **Enable/disable**: Main functionality switch
* **Location**: Settings are in "Settings" → "Typo Reporter" section
* **Access rights**: Administrator rights required (manage_options)

### First steps:

1. Activate the plugin and enable it in settings
2. Go to the website and select any text
3. Press `Ctrl+Enter` to test functionality
4. Check report arrival in admin panel

== Frequently Asked Questions ==

= How can users send a typo report? =

Users need to:
1. Select text with suspected error on the page
2. Press `Ctrl + Enter` key combination
3. Optionally describe the error in the appearing window
4. Click "Send Report" button

= Where can administrators view reports? =

All reports are available in WordPress administrative panel:
* **Location**: Settings → Typo Reporter
* **Access rights**: Only for users with manage_options rights
* **Interface**: Table with filtering, search, and status management

= What information is saved when sending a report? =

The following information is saved:
* **Selected text**: Text with suspected error
* **Error description**: User comment (optional)
* **Page URL**: Page where error was found
* **IP address**: Technical information for analytics
* **User-Agent**: Browser information of the user
* **Date and time**: When the report was sent

= Can hotkeys be configured? =

The current version uses standard combination `Ctrl+Enter`. Hotkey configuration capability is planned for future versions.

= How to manage report statuses? =

Admin panel provides three statuses:
* **New**: Just received reports
* **Resolved**: Reports that have been processed
* **Dismissed**: Reports that were rejected as irrelevant

= Can reports be exported? =

Report export is not implemented in current version. This functionality is planned for future plugin versions.

= Does the plugin affect website performance? =

No, the plugin is optimized for minimal performance impact:
* Resource loading only on pages with enabled functionality
* AJAX processing without blocking user interface
* Optimized SQL queries to database

== Screenshots ==

1. Modal window for submitting typo report
2. Administrative panel with report table
3. Plugin settings in admin panel
4. Report filtering by statuses
5. Detailed report view
6. Report submission from mobile device

== Changelog ==

= 2.1.0 (19.11.2025) =
* Plugin version updated
* Improved work stability
* Optimized performance

= 2.0.1 (15.11.2025) =
* Added function for clearing entire table in admin panel
* Improved error description length validation (1000 character limit)
* Removed excessive logging from code
* Fixed minor interface errors

= 2.0.0 (10.11.2025) =
* Complete plugin redesign with improved architecture
* Added modular structure with separate classes
* Improved database management system
* Optimized resource loading
* Expanded report moderation capabilities

= 1.0.0 (01.11.2025) =
* First plugin version
* Basic typo report submission functionality
* Simple administrative panel
* Main report storage system

== Upgrade Notice ==

= 2.1.0 =
Recommended update for improved stability and performance.

= 2.0.1 =
Update adds table clearing function and improves data validation.

= 2.0.0 =
Major update with complete plugin architecture redesign.

== Support ==

For technical support or bug reports:

* **Email**: vladimir@bychko.ru
* **Website**: https://bychko.ru

== Privacy Policy ==

Typo Reporter plugin collects and stores only technical information necessary for the report system functionality:

* **Selected text**: Text that user marks as containing error
* **Error description**: Additional user comment (if provided)
* **Page URL**: Page where error was discovered
* **IP address**: For analytics and abuse prevention
* **User-Agent**: Browser information of the user

The plugin does NOT collect users' personal data such as names, email addresses, or other identifying information. All data is used exclusively for improving content quality on the website and managing the report system.

Data is stored only locally on your website and is not transferred to third parties or external services.