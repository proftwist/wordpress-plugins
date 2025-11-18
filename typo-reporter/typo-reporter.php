<?php
/**
 * Plugin Name: Typo Reporter
 * Description: Позволяет пользователям сообщать об опечатках на сайте
 * Version: 2.1.0
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: typo-reporter
 * Domain Path: /languages
 *
 * @package TypoReporter
 */

// Защита от прямого доступа - предотвращает выполнение файла вне WordPress
defined('ABSPATH') || exit;

// Определение констант для удобства работы с путями
define('TR_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Абсолютный путь к папке плагина
define('TR_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL к папке плагина
define('TR_PLUGIN_VERSION', '2.1.0');                // Версия плагина

// Подключение вспомогательных файлов
require_once TR_PLUGIN_PATH . 'includes/class-database.php';          // Работа с базой данных
require_once TR_PLUGIN_PATH . 'includes/class-assets-manager.php';    // Менеджер ресурсов
require_once TR_PLUGIN_PATH . 'includes/class-ajax-handler.php';      // AJAX обработчики
require_once TR_PLUGIN_PATH . 'includes/admin-settings.php';          // Настройки админки

/**
 * Основной класс плагина Typo Reporter
 *
 * Отвечает за инициализацию плагина и регистрацию основных компонентов.
 *
 * @package TypoReporter
 * @since 1.0.0
 */
class TypoReporter {

    /**
     * Экземпляр класса TypoReporter
     *
     * @var TypoReporter
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса TypoReporter
     *
     * @return TypoReporter
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса TypoReporter
     *
     * Регистрирует основные хуки WordPress при инициализации плагина.
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Регистрация основных хуков WordPress
        add_action('init', array($this, 'init'));  // Инициализация плагина
        add_action('admin_menu', array($this, 'add_admin_menu')); // Меню в админке

        // Загрузка текстового домена
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Создание таблицы при активации плагина
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Загрузка текстового домена для локализации
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'typo-reporter',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Создание таблицы базы данных при активации плагина
     *
     * @since 1.0.0
     */
    public function activate() {
        TypoReporterDatabase::create_table();
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов.
     *
     * @since 1.0.0
     */
    public function init() {
        // Проверяем, включен ли плагин
        if (!get_option('typo_reporter_enabled', true)) {
            return;
        }

        // Регистрация ресурсов для сайта
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Добавляем обработчик клавиш для фронтенда
        add_action('wp_footer', array($this, 'add_keyboard_handler'));

        // Подключаем ресурсы админки
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Подключение ресурсов для фронтенда сайта
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        TypoReporterAssetsManager::enqueue_frontend_assets();
    }

    /**
     * Подключение ресурсов для админки
     *
     * @since 1.0.0
     */
    public function enqueue_admin_assets() {
        TypoReporterAssetsManager::enqueue_admin_assets();
    }

    /**
     * Добавление обработчика клавиш в футер страницы
     *
     * @since 1.0.0
     */
    public function add_keyboard_handler() {
        ?>
        <script>
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                var selectedText = window.getSelection().toString();
                if (selectedText.trim()) {
                    TypoReporterFrontend.showModal(selectedText);
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Добавление пункта меню в админке
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Добавляем подменю в раздел "Настройки"
        add_submenu_page(
            'options-general.php',
            __('Typo Reporter', 'typo-reporter'),
            __('Typo Reporter', 'typo-reporter'),
            'manage_options',
            'typo-reporter',
            array($this, 'options_page')
        );
    }

    /**
     * Отображение страницы настроек плагина
     *
     * @since 1.0.1
     */
    public function options_page() {
        typo_reporter_options_page();
    }

}

// Инициализация плагина
TypoReporter::get_instance();