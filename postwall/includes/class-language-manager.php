<?php
/**
 * Language Manager for Post Wall plugin
 *
 * Handles language detection, loading, and switching functionality.
 *
 * @package PostWall
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class PostWall_Language_Manager
 *
 * Manages plugin internationalization and language switching.
 *
 * @package PostWall
 * @since 1.0.0
 */
class PostWall_Language_Manager {

    /**
     * Available languages array
     *
     * @var array
     * @since 1.0.0
     */
    private static $available_languages = null;

    /**
     * Current language
     *
     * @var string
     * @since 1.0.0
     */
    private static $current_language = null;

    /**
     * Initialize language manager
     *
     * @since 1.0.0
     */
    public static function init() {
        // Filter to set plugin locale
        add_filter('plugin_locale', array(__CLASS__, 'filter_plugin_locale'), 10, 2);

        // Load text domain after theme and plugins are loaded
        add_action('init', array(__CLASS__, 'load_textdomain'), 1);
    }

    /**
     * Get available languages from .mo files
     *
     * @return array Array of available language codes
     * @since 1.0.0
     */
    public static function get_available_languages() {
        if (self::$available_languages !== null) {
            return self::$available_languages;
        }

        self::$available_languages = array();

        $languages_dir = POSTWALL_PLUGIN_PATH . 'languages/';

        // Scan languages directory for .mo files
        if (is_dir($languages_dir)) {
            $files = scandir($languages_dir);
            foreach ($files as $file) {
                if (preg_match('/^postwall-([a-zA-Z_]+)\.mo$/', $file, $matches)) {
                    $lang_code = $matches[1];
                    self::$available_languages[$lang_code] = self::get_language_name($lang_code);
                }
            }
        }

        // Always include English and Russian as fallbacks
        if (!isset(self::$available_languages['ru_RU'])) {
            self::$available_languages['ru_RU'] = 'Русский';
        }
        if (!isset(self::$available_languages['en_US'])) {
            self::$available_languages['en_US'] = 'English (US)';
        }

        return self::$available_languages;
    }

    /**
     * Get human readable language name
     *
     * @param string $lang_code Language code
     * @return string Language name
     * @since 1.0.0
     */
    private static function get_language_name($lang_code) {
        $language_names = array(
            'en_US' => 'English (US)',
            'ru_RU' => 'Русский',
        );

        return isset($language_names[$lang_code]) ? $language_names[$lang_code] : $lang_code;
    }

    /**
     * Detect user language based on locale
     *
     * @return string Language code
     * @since 1.0.0
     */
    public static function detect_user_language() {
        $available_langs = array_keys(self::get_available_languages());

        // Auto-detect based on site locale
        $site_locale = get_locale();
        if (in_array($site_locale, $available_langs)) {
            return $site_locale;
        }

        // Check language part (e.g., 'en' from 'en_US')
        $lang_part = explode('_', $site_locale)[0];
        foreach ($available_langs as $available_lang) {
            if (strpos($available_lang, $lang_part . '_') === 0) {
                return $available_lang;
            }
        }

        // Default to Russian if no match found
        return 'ru_RU';
    }

    /**
     * Filter plugin locale
     *
     * @param string $locale Current locale
     * @param string $domain Text domain
     * @return string Filtered locale
     * @since 1.0.0
     */
    public static function filter_plugin_locale($locale, $domain) {
        // Only affect our plugin
        if ($domain === 'postwall') {
            // If WordPress locale is English, use Russian as default
            if ($locale === 'en_US') {
                return 'ru_RU';
            }
            // If locale is not available, default to Russian
            if (!in_array($locale, array_keys(self::get_available_languages()))) {
                return 'ru_RU';
            }
        }
        return $locale;
    }

    /**
     * Load text domain for the plugin
     *
     * Uses standard WordPress locale detection, but defaults to Russian
     *
     * @since 1.0.0
     */
    public static function load_textdomain() {
        // Store current language
        self::$current_language = get_locale();

        // Load the text domain (locale will be filtered by filter_plugin_locale)
        load_plugin_textdomain(
            'postwall',
            false,
            dirname(plugin_basename(POSTWALL_PLUGIN_PATH)) . '/languages/'
        );
    }

    /**
     * Get current language
     *
     * @return string Current language code
     * @since 1.0.0
     */
    public static function get_current_language() {
        return self::$current_language ?: self::detect_user_language();
    }

    /**
     * Force reload text domain after language change
     *
     * @since 1.0.0
     */
    public static function reload_textdomain() {
        // Clear translation cache for forced reload
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        unload_textdomain('postwall');
        self::load_textdomain();
    }
}