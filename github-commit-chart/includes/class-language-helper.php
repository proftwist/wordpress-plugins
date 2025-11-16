<?php
/**
 * Language Helper for GitHub Commit Chart plugin
 *
 * Handles language detection and fallback logic.
 *
 * @package GitHubCommitChart
 * @since 2.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class GitHubCommitChart_Language_Helper
 *
 * Provides language detection and fallback functionality.
 *
 * @package GitHubCommitChart
 * @since 2.0.0
 */
class GitHubCommitChart_Language_Helper {

    /**
     * Get the appropriate locale for the plugin
     *
     * @return string Locale code
     * @since 2.0.0
     */
    public static function get_plugin_locale() {
        // Используем WordPress функцию для определения локали
        if (function_exists('determine_locale')) {
            $locale = determine_locale();
        } else {
            // Fallback для старых версий WordPress
            $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        }

        // Если локаль английская или не определена - используем русский как fallback
        if (empty($locale) || self::is_english_locale($locale)) {
            $locale = 'ru_RU';
        }

        // Проверяем существование файла перевода
        $mofile = GCC_PLUGIN_PATH . 'languages/github-commit-chart-' . $locale . '.mo';
        if (!file_exists($mofile)) {
            $locale = 'ru_RU';
        }

        return apply_filters('github_commit_chart_locale', $locale);
    }

    /**
     * Check if locale is English
     *
     * @param string $locale Locale code
     * @return bool
     * @since 2.0.0
     */
    private static function is_english_locale($locale) {
        $english_locales = array(
            'en', 'en_US', 'en_GB', 'en_AU', 'en_CA', 'en_NZ', 'en_ZA'
        );

        return in_array($locale, $english_locales);
    }

    /**
     * Load plugin textdomain with proper fallback
     *
     * @since 2.0.0
     */
    public static function load_textdomain() {
        $locale = self::get_plugin_locale();
        $mofile = GCC_PLUGIN_PATH . 'languages/github-commit-chart-' . $locale . '.mo';

        load_textdomain('github-commit-chart', $mofile);
    }
}