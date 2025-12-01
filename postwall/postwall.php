<?php
/**
 * Plugin Name: Post Wall
 * Description: Displays post wall charts as Gutenberg blocks or shortcodes
 * Version: 2.2.2
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: postwall
 * Domain Path: /languages
 *
 * @package PostWall
 */

// Защита от прямого доступа - предотвращает выполнение файла вне WordPress
defined('ABSPATH') || exit;

// Определение констант для удобства работы с путями
define('POSTWALL_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Абсолютный путь к папке плагина
define('POSTWALL_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL к папке плагина
define('POSTWALL_PLUGIN_VERSION', '2.2.2');                // Версия плагина

// Подключение вспомогательных файлов
require_once POSTWALL_PLUGIN_PATH . 'includes/class-assets-manager.php';       // Менеджер ресурсов
require_once POSTWALL_PLUGIN_PATH . 'includes/class-ajax-handler.php';          // AJAX обработчики
require_once POSTWALL_PLUGIN_PATH . 'includes/class-postwall-api.php';          // API для получения данных
require_once POSTWALL_PLUGIN_PATH . 'includes/block-registration.php';         // Регистрация Gutenberg-блока

/**
 * Основной класс плагина Post Wall
 *
 * Отвечает за инициализацию плагина и регистрацию основных компонентов.
 *
 * @package PostWall
 * @since 2.0.0
 */
class PostWall {

    /**
     * Экземпляр класса PostWall
     *
     * @var PostWall
     * @since 2.0.0
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса PostWall
     *
     * @return PostWall
     * @since 2.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса PostWall
     *
     * Регистрирует основные хуки WordPress при инициализации плагина.
     *
     * @since 2.0.0
     */
    private function __construct() {
        // Регистрация основных хуков WordPress
        add_action('init', array($this, 'init'));  // Инициализация плагина
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов.
     *
     * @since 2.0.0
     */
    public function init() {
        // Регистрация блока и подключение ресурсов
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets')); // Ресурсы для редактора
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));             // Ресурсы для сайта
    }

    /**
     * Подключение ресурсов для редактора блоков Gutenberg
     *
     * Загружает JavaScript и CSS файлы, необходимые для работы блока в редакторе:
     * - JavaScript: логика блока с зависимостями wp-blocks, wp-element, wp-block-editor, wp-components
     * - CSS: стили для блока в редакторе с зависимостью wp-edit-blocks
     * Использует filemtime для версионирования файлов (кеширование).
     *
     * @since 2.0.0
     */
    public function enqueue_block_editor_assets() {
        // Пути к файлам сборки
        $index_js = POSTWALL_PLUGIN_PATH . 'build/index.js';   // JavaScript для блока
        $index_css = POSTWALL_PLUGIN_PATH . 'build/index.css'; // CSS для блока

        // Подключение JavaScript для блока
        wp_enqueue_script(
            'postwall-block',        // Уникальный идентификатор скрипта
            POSTWALL_PLUGIN_URL . 'build/index.js',  // URL к файлу
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'), // Зависимости WordPress
            file_exists($index_js) ? filemtime($index_js) : time(), // Версия на основе времени изменения файла
            true // Загружать в футере
        );

        // Установка переводов для скрипта
        wp_set_script_translations('postwall-block', 'postwall', POSTWALL_PLUGIN_PATH . 'languages');

        // Подключение CSS для блока в редакторе
        wp_enqueue_style(
            'postwall-block-editor', // Уникальный идентификатор стилей
            POSTWALL_PLUGIN_URL . 'build/index.css', // URL к файлу
            array('wp-edit-blocks'),             // Зависимости стилей
            file_exists($index_css) ? filemtime($index_css) : time() // Версия на основе времени изменения файла
        );
    }

    /**
     * Подключение ресурсов для фронтенда сайта
     *
     * Загружает JavaScript и CSS файлы для отображения диаграммы на сайте:
     * - JavaScript: интерактивная диаграмма с зависимостью wp-element (React)
     * - CSS: стили для диаграммы на сайте
     * Передает настройки плагина в JavaScript через wp_localize_script.
     *
     * @since 2.0.0
     */
    public function enqueue_frontend_assets() {
        // Пути к файлам сборки для фронтенда
        $frontend_js = POSTWALL_PLUGIN_PATH . 'build/frontend.js';
        $style_css = POSTWALL_PLUGIN_PATH . 'build/style-index.css';

        // Принудительно обновляем версию при изменении файла
        $frontend_version = file_exists($frontend_js) ? filemtime($frontend_js) : time();

        // Подключение JavaScript для фронтенда
        wp_enqueue_script(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'build/frontend.js',
            array('jquery', 'wp-i18n'),
            $frontend_version, // Используем время изменения файла как версию
            true
        );

        // Установка переводов для фронтенд скрипта
        wp_set_script_translations('postwall-frontend', 'postwall', POSTWALL_PLUGIN_PATH . 'languages');

        // Передача настроек плагина в JavaScript
        wp_localize_script('postwall-frontend', 'postwallSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('postwall_get_data'),
            'locale' => get_locale(),
            'dateFormat' => get_option('date_format')
        ));

        // Подключение CSS стилей для фронтенда
        wp_enqueue_style(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'build/style-index.css',
            array(),
            file_exists($style_css) ? filemtime($style_css) : time()
        );
    }

    /**
     * Загрузка текстового домена
     *
     * @since 2.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'postwall',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

}

// Инициализация плагина
PostWall::get_instance();