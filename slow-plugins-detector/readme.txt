=== Slow Plugins Detector - Plugin Performance Analysis ===
Contributors: proftwist
Donate link: https://www.tbank.ru/cf/8wiyPH9vAqH
Tags: performance, speed, optimization, plugins, loading time, performance analysis, site speed, plugin optimization
Requires at least: 4.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin detects and analyzes slow plugins on the frontend, measuring their loading time and impact on site performance.

== Description ==

**Slow Plugins Detector** is a WordPress site optimization tool that helps identify plugins negatively affecting loading speed. The plugin conducts detailed performance analysis of each active plugin, measuring execution time and creating reports with optimization recommendations.

### 🔍 Main Features:

* **Detailed performance analysis**: Measuring loading time of each plugin individually
* **Color indication**: Quick visual identification of problematic plugins
  * **🟢 Fast** (green): loading time < 50ms
  * **🟡 Moderate** (yellow): loading time 50-100ms
  * **🔴 Slow** (red): loading time > 100ms
* **Background testing**: Asynchronous tests do not block admin panel work
* **Cache clearing**: Automatic clearing of various cache types before each test
* **Administrative interface**: Convenient table with results and settings
* **Security**: All operations performed safely with access rights verification

### 📊 What the plugin analyzes:

* **Loading time**: Accurate measurement of each plugin's execution time
* **Frontend impact**: Analysis of plugins' influence on page loading speed
* **Performance statistics**: Comparative data for all active plugins
* **Recommendations**: Suggestions for optimization or replacement of slow plugins

### 🛠️ Technical Features:

* **Multiple measurements**: 3 measurements for each plugin with result averaging
* **Cross-compatibility**: Support for popular caching plugins
* **Automatic restoration**: Plugins are temporarily deactivated only during testing
* **Modular architecture**: Separate classes for testing and result display
* **AJAX interface**: Modern user interface with progress indication

### 🎯 Who benefits:

* **Web developers**: Identifying problematic plugins during site optimization
* **Site administrators**: Performance control after installing new plugins
* **SEO specialists**: Improving Core Web Vitals and site speed
* **Agencies**: Justifying replacement of slow plugins to clients

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → Slow Plugins Detector**
4. Click the **"Run Performance Test"** button
5. Wait for analysis completion (may take several minutes)
6. Study results in the table and take optimization measures

### System requirements:

* **WordPress**: 4.0 or higher
* **PHP**: 7.0 or higher
* **Access rights**: Administrator rights required (manage_options)
* **AJAX support**: For correct interface operation

### Recommended testing conditions:

* Conduct tests during periods of low server load
* Ensure the site is publicly accessible (does not require authentication)
* Temporarily disable other optimization plugins that may affect results

== Frequently Asked Questions ==

= How does the plugin measure performance? =

The plugin temporarily deactivates each active plugin individually, loads the site's main page, and measures loading time. Then it compares results with baseline loading without plugins.

= Is temporary plugin deactivation safe? =

Yes, the process is completely safe. Plugins are deactivated only during measurement and automatically restored. The original site state is preserved.

= Why does testing take a long time? =

Each plugin is tested individually with cache clearing and multiple measurements for accuracy. This ensures reliable performance analysis results.

= Can results vary between tests? =

Yes, results may vary depending on server load, network conditions, and caching. It is recommended to conduct tests under identical conditions for comparison.

= What to do with plugins marked as "Slow"? =

Consider the following options:
* Search for alternative plugins with better performance
* Contact plugin developers with a report of issues
* Configure caching to mitigate impact
* Optimize server configuration

= Does the plugin support multisite installations? =

Yes, the plugin works correctly with WordPress Multisite, analyzing plugin performance for each site in the network separately.

= Can tests be run automatically? =

Currently, tests are run manually. Automatic testing is planned for future plugin versions.

== Screenshots ==

1. Slow Plugins Detector administrative page
2. Performance test launch
3. Results table with color indication
4. Detailed information for each plugin
5. Background testing without interface blocking
6. Reports with optimization recommendations

== Changelog ==

= 1.0.0 (19.11.2025) =
* First stable plugin version
* WordPress slow plugin detection system
* Background plugin performance testing
* Results table with plugin execution time data
* Running tests in background mode without admin panel blocking
* Class for managing test results
* Class for launching and processing performance tests
* Administrative page with results display
* JavaScript interface for test management
* Interface localization in Russian language
* System for saving and displaying performance statistics
* Integration with WordPress admin_enqueue_scripts
* Modular architecture with component separation

== Upgrade Notice ==

= 1.0.0 =
First plugin version with complete plugin performance analysis functionality.

== Support ==

For technical support or bug reports:

* **Email**: vladimir@bychko.ru
* **Website**: https://bychko.ru

== Privacy Policy ==

The Slow Plugins Detector plugin does not collect, store, or transmit users' personal data. The plugin performs tests only locally on your website and does not interact with external servers or services.

All testing data (loading time, plugin status) is processed exclusively for performance analysis and is not transmitted to third parties.