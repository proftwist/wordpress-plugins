<?php
/**
 * Plugin Name: Slow Plugins Detector
 * Description: Detects and analyzes slow loading plugins on the frontend
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Author URI: https://bychko.ru
 * Text Domain: slow-plugins-detector
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('SPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SPD_VERSION', '1.0.0');

/**
 * Основной класс плагина
 */
class Slow_Plugins_Detector {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Загрузка зависимостей
        $this->includes();
    }

    /**
     * Загрузка файлов перевода
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'slow-plugins-detector',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Подключение зависимых файлов
     */
    private function includes() {
        require_once SPD_PLUGIN_PATH . 'includes/class-test-runner.php';
        require_once SPD_PLUGIN_PATH . 'includes/class-results-table.php';
        require_once SPD_PLUGIN_PATH . 'admin/admin-page.php';
    }

    /**
     * Добавление пункта меню в админке
     */
    public function add_admin_menu() {
        add_options_page(
            __('Slow Plugins Detector', 'slow-plugins-detector'), // Заголовок страницы
            __('Slow Plugins Detector', 'slow-plugins-detector'), // Название в меню
            'manage_options', // Требуемые права доступа
            'slow-plugins-detector', // SLUG страницы
            'spd_render_admin_page' // Функция отображения
        );
    }

    /**
     * Подключение скриптов и стилей
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на нашей странице
        if ('settings_page_slow-plugins-detector' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'spd-admin-js',
            SPD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SPD_VERSION,
            true
        );

        // Локализация для AJAX
        wp_localize_script('spd-admin-js', 'spd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spd_run_test'),
            'testing_text' => __('Testing...', 'slow-plugins-detector'),
            'complete_text' => __('Test Complete!', 'slow-plugins-detector')
        ));

        // Базовые стили
        wp_add_inline_style('wp-admin', '
            .spd-results { margin-top: 20px; }
            .spd-table { width: 100%; border-collapse: collapse; }
            .spd-table th, .spd-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            .spd-table th { background-color: #f8f9fa; font-weight: 600; }
            .spd-loading { display: none; color: #2271b1; }
            .spd-button { margin: 10px 0; }
            .spd-warning { color: #d63638; font-weight: 600; }
            .spd-good { color: #00a32a; }
        ');
    }
}

// Инициализация плагина
function slow_plugins_detector_init() {
    return Slow_Plugins_Detector::get_instance();
}
add_action('plugins_loaded', 'slow_plugins_detector_init');

// Регистрация AJAX обработчиков
add_action('wp_ajax_spd_run_performance_test', 'spd_handle_ajax_test');

/**
 * Обработчик AJAX запроса для запуска теста
 */
function spd_handle_ajax_test() {
    // Проверка nonce для безопасности
    check_ajax_referer('spd_run_test', 'nonce');

    // Проверка прав пользователя
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'slow-plugins-detector'));
    }

    $test_runner = new SPD_Test_Runner();
    $results = $test_runner->run_frontend_test();

    wp_send_json_success($results);
}