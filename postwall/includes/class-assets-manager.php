<?php
/**
 * Assets Manager for Post Wall plugin
 *
 * Handles loading and management of CSS and JavaScript assets.
 *
 * @package PostWall
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class PostWall_Assets_Manager
 *
 * Manages plugin assets (CSS, JS) for both admin and frontend.
 *
 * @package PostWall
 * @since 1.0.0
 */
class PostWall_Assets_Manager {

    /**
     * Initialize assets manager
     *
     * @since 1.0.0
     */
    public static function init() {
        // Register frontend assets
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));

        // Register admin assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue frontend assets
     *
     * @since 1.0.0
     */
    public static function enqueue_frontend_assets() {
        // Frontend CSS
        wp_enqueue_style(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            POSTWALL_PLUGIN_VERSION
        );

        // Frontend JavaScript
        wp_enqueue_script(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            POSTWALL_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on relevant admin pages
        if (strpos($hook, 'postwall') === false) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'postwall-admin',
            POSTWALL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            POSTWALL_PLUGIN_VERSION
        );

        // Admin JavaScript
        wp_enqueue_script(
            'postwall-admin',
            POSTWALL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            POSTWALL_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Get asset file version based on file modification time
     *
     * @param string $file_path Relative path to asset file
     * @return string Version string
     * @since 1.0.0
     */
    public static function get_asset_version($file_path) {
        $full_path = POSTWALL_PLUGIN_PATH . $file_path;
        return file_exists($full_path) ? filemtime($full_path) : POSTWALL_PLUGIN_VERSION;
    }
}