<?php
/**
 * Plugin Name: Scroll Progress Bar
 * Plugin URI: https://bychko.ru
 * Description: Минималистичная полоска прогресса чтения вверху страницы. Улучшает UX длинных статей.
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Author URI: https://bychko.ru
 * Text Domain: scroll-progress-bar
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('SCROLL_PROGRESS_BAR_VERSION', '1.0.0');
define('SCROLL_PROGRESS_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCROLL_PROGRESS_BAR_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Основной класс плагина
 */
class Scroll_Progress_Bar_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_plugin'));
    }

    /**
     * Загрузка переводов
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'scroll-progress-bar',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Инициализация плагина
     */
    public function init_plugin() {
        // Подключаем классы
        require_once SCROLL_PROGRESS_BAR_PLUGIN_PATH . 'includes/class-settings.php';
        require_once SCROLL_PROGRESS_BAR_PLUGIN_PATH . 'includes/class-scroll-progress.php';

        // Инициализируем классы
        Scroll_Progress_Bar_Settings::get_instance();
        Scroll_Progress_Bar::get_instance();
    }
}

// Запуск плагина
Scroll_Progress_Bar_Plugin::get_instance();
?>