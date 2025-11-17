<?php
/**
 * Менеджер ресурсов для плагина Post Wall
 *
 * Управляет загрузкой и управлением CSS и JavaScript ресурсов.
 *
 * @package PostWall
 * @since 2.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс PostWall_Assets_Manager
 *
 * Управляет ресурсами плагина (CSS, JS) для админки и фронтенда.
 *
 * @package PostWall
 * @since 2.0.0
 */
class PostWall_Assets_Manager {

    /**
     * Инициализация менеджера ресурсов
     *
     * @since 2.0.0
     */
    public static function init() {
        // Регистрируем фронтенд ресурсы
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));

        // Регистрируем админ ресурсы
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    /**
     * Подключаем фронтенд ресурсы
     *
     * @since 2.0.0
     */
    public static function enqueue_frontend_assets() {
        // Фронтенд CSS
        wp_enqueue_style(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            POSTWALL_PLUGIN_VERSION
        );

        // Фронтенд JavaScript
        wp_enqueue_script(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            POSTWALL_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Подключаем админ ресурсы
     *
     * @param string $hook Текущий хук страницы админки
     * @since 2.0.0
     */
    public static function enqueue_admin_assets($hook) {
        // Загружаем только на релевантных страницах админки
        if (strpos($hook, 'postwall') === false) {
            return;
        }

        // Админ CSS
        wp_enqueue_style(
            'postwall-admin',
            POSTWALL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            POSTWALL_PLUGIN_VERSION
        );

        // Админ JavaScript
        wp_enqueue_script(
            'postwall-admin',
            POSTWALL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            POSTWALL_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Получить версию файла ресурса на основе времени модификации
     *
     * @param string $file_path Относительный путь к файлу ресурса
     * @return string Строка версии
     * @since 2.0.0
     */
    public static function get_asset_version($file_path) {
        $full_path = POSTWALL_PLUGIN_PATH . $file_path;
        return file_exists($full_path) ? filemtime($full_path) : POSTWALL_PLUGIN_VERSION;
    }
}