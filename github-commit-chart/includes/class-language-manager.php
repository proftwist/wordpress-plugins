<?php
/**
 * Language Manager for GitHub Commit Chart plugin
 *
 * Handles language detection, loading, and switching functionality.
 *
 * @package GitHubCommitChart
 * @since 1.8.5
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class GitHubCommitChart_Language_Manager
 *
 * Manages plugin internationalization and language switching.
 *
 * @package GitHubCommitChart
 * @since 1.8.5
 */
class GitHubCommitChart_Language_Manager {

    /**
     * Available languages array
     *
     * @var array
     * @since 1.8.5
     */
    private static $available_languages = null;

    /**
     * Current language
     *
     * @var string
     * @since 1.8.5
     */
    private static $current_language = null;

    /**
     * Initialize language manager
     *
     * @since 1.8.5
     */
    public static function init() {
        // Load text domain after theme and plugins are loaded
        add_action('init', array(__CLASS__, 'load_textdomain'), 1);

        // Add language settings to admin
        add_action('admin_init', array(__CLASS__, 'register_language_setting'));
    }

    /**
     * Get available languages from .mo files
     *
     * @return array Array of available language codes
     * @since 1.8.5
     */
    public static function get_available_languages() {
        if (self::$available_languages !== null) {
            return self::$available_languages;
        }

        self::$available_languages = array();

        $languages_dir = GCC_PLUGIN_PATH . 'languages/';

        // Scan languages directory for .mo files
        if (is_dir($languages_dir)) {
            $files = scandir($languages_dir);
            foreach ($files as $file) {
                if (preg_match('/^github-commit-chart-([a-zA-Z_]+)\.mo$/', $file, $matches)) {
                    $lang_code = $matches[1];
                    self::$available_languages[$lang_code] = self::get_language_name($lang_code);
                }
            }
        }

        // Always include English as fallback
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
     * @since 1.8.5
     */
    private static function get_language_name($lang_code) {
        $language_names = array(
            'en_US' => 'English (US)',
            'ru_RU' => 'Русский',
            'es_ES' => 'Español',
            'fr_FR' => 'Français',
            'de_DE' => 'Deutsch',
            'it_IT' => 'Italiano',
            'pt_BR' => 'Português (Brasil)',
            'ja' => '日本語',
            'zh_CN' => '中文 (简体)',
            'zh_TW' => '中文 (繁體)',
            'ko_KR' => '한국어',
            'ar' => 'العربية',
            'hi_IN' => 'हिन्दी',
        );

        return isset($language_names[$lang_code]) ? $language_names[$lang_code] : $lang_code;
    }

    /**
     * Detect user language based on locale
     *
     * @return string Language code
     * @since 1.8.5
     */
    public static function detect_user_language() {
        $available_langs = array_keys(self::get_available_languages());

        // Check if user manually set language
        $saved_lang = get_option('github_commit_chart_language', '');
        if (!empty($saved_lang) && in_array($saved_lang, $available_langs)) {
            return $saved_lang;
        }

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

        // Fallback to English
        return 'en_US';
    }

    /**
     * Load text domain for the plugin
     *
     * Uses standard WordPress locale detection
     *
     * @since 1.8.5
     */
    public static function load_textdomain() {
        // Store current language
        self::$current_language = get_locale();

        // Load the text domain
        load_plugin_textdomain(
            'github-commit-chart',
            false,
            dirname(plugin_basename(GCC_PLUGIN_PATH)) . '/languages/'
        );
    }

    /**
     * Register language setting
     *
     * @since 1.8.5
     */
    public static function register_language_setting() {
        register_setting(
            'github_commit_chart_settings',
            'github_commit_chart_language',
            array(
                'type' => 'string',
                'sanitize_callback' => array(__CLASS__, 'sanitize_language'),
                'default' => ''
            )
        );
    }

    /**
     * Sanitize language setting
     *
     * @param string $input Language code
     * @return string Sanitized language code
     * @since 1.8.5
     */
    public static function sanitize_language($input) {
        $available_langs = array_keys(self::get_available_languages());

        if (empty($input) || !in_array($input, $available_langs)) {
            return ''; // Auto-detect
        }

        return $input;
    }

    /**
     * Get current language
     *
     * @return string Current language code
     * @since 1.8.5
     */
    public static function get_current_language() {
        return self::$current_language ?: self::detect_user_language();
    }

    /**
     * Force reload text domain after language change
     *
     * @since 1.8.5
     */
    public static function reload_textdomain() {
        // Очищаем кеш переводов для принудительной перезагрузки
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        unload_textdomain('github-commit-chart');
        self::load_textdomain();
    }
}