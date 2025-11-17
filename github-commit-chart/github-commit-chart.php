<?php
/**
 * Plugin Name: GitHub Commit Chart
 * Description: Displays GitHub commit charts as Gutenberg blocks or shortcodes
 * Version: 2.0.2
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: github-commit-chart
 * Domain Path: /languages
 *
 * @package GitHubCommitChart
 */

// Защита от прямого доступа - предотвращает выполнение файла вне WordPress
defined('ABSPATH') || exit;

// Определение констант для удобства работы с путями
define('GCC_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Абсолютный путь к папке плагина
define('GCC_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL к папке плагина
define('GCC_PLUGIN_VERSION', '2.0.0');                // Версия плагина

// Подключение вспомогательных файлов
require_once GCC_PLUGIN_PATH . 'includes/class-assets-manager.php';       // Менеджер ресурсов
require_once GCC_PLUGIN_PATH . 'includes/class-ajax-handler.php';         // AJAX обработчики
require_once GCC_PLUGIN_PATH . 'includes/class-shortcode-handler.php';    // Обработчик шорткодов
require_once GCC_PLUGIN_PATH . 'includes/admin-settings.php';             // Файл настроек администратора
require_once GCC_PLUGIN_PATH . 'includes/block-registration.php';         // Регистрация Gutenberg-блока
require_once GCC_PLUGIN_PATH . 'includes/github-api.php';                 // Работа с GitHub API

/**
 * Основной класс плагина GitHub Commit Chart
 *
 * Отвечает за инициализацию плагина и регистрацию основных компонентов.
 * Весь функционал теперь распределен между специализированными классами.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */
class GitHubCommitChart {

    /**
     * Экземпляр класса GitHubCommitChart
     *
     * @var GitHubCommitChart
     * @since 1.8.4
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса GitHubCommitChart
     *
     * @return GitHubCommitChart
     * @since 1.8.4
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса GitHubCommitChart
     *
     * Регистрирует основные хуки WordPress при инициализации плагина.
     *
     * @since 1.8.4
     */
    private function __construct() {
        // Регистрация основных хуков WordPress
        add_action('init', array($this, 'init'));  // Инициализация плагина
        add_action('admin_menu', array($this, 'add_admin_menu')); // Меню в админке

        // Загрузка текстового домена
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Загрузка текстового домена для локализации
     *
     * @since 2.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'github-commit-chart',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов.
     *
     * @since 1.8.4
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
     * @since 1.0.0
     */
    public function enqueue_block_editor_assets() {
        // Пути к файлам сборки
        $index_js = GCC_PLUGIN_PATH . 'build/index.js';   // JavaScript для блока
        $index_css = GCC_PLUGIN_PATH . 'build/index.css'; // CSS для блока

        // Подключение JavaScript для блока
        wp_enqueue_script(
            'github-commit-chart-block',        // Уникальный идентификатор скрипта
            GCC_PLUGIN_URL . 'build/index.js',  // URL к файлу
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'), // Зависимости WordPress
            file_exists($index_js) ? filemtime($index_js) : time(), // Версия на основе времени изменения файла
            true // Загружать в футере
        );

        // Установка переводов для скрипта
        wp_set_script_translations('github-commit-chart-block', 'github-commit-chart', GCC_PLUGIN_PATH . 'languages');

        // Подключение CSS для блока в редакторе
        wp_enqueue_style(
            'github-commit-chart-block-editor', // Уникальный идентификатор стилей
            GCC_PLUGIN_URL . 'build/index.css', // URL к файлу
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
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        // Пути к файлам сборки для фронтенда
        $frontend_js = GCC_PLUGIN_PATH . 'build/frontend.js';     // JavaScript для интерактивной диаграммы
        $style_css = GCC_PLUGIN_PATH . 'build/style-index.css';   // CSS стили для диаграммы

        // Подключение JavaScript для фронтенда
        wp_enqueue_script(
            'github-commit-chart-frontend',     // Уникальный идентификатор скрипта
            GCC_PLUGIN_URL . 'build/frontend.js', // URL к файлу
            array('wp-element', 'wp-i18n'),                 // Зависимость React для работы с компонентами
            file_exists($frontend_js) ? filemtime($frontend_js) : time(), // Версия файла
            true                                 // Загружать в футере
        );

        // Установка переводов для фронтенд скрипта
        wp_set_script_translations('github-commit-chart-frontend', 'github-commit-chart', GCC_PLUGIN_PATH . 'languages');

        // Передача настроек плагина в JavaScript
        // Создает глобальный объект githubCommitChartSettings доступный в JS
        wp_localize_script('github-commit-chart-frontend', 'githubCommitChartSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),    // URL для AJAX запросов
            'githubProfile' => get_option('github_commit_chart_github_profile', ''), // Профиль GitHub из настроек
            'nonce' => wp_create_nonce('gcc_get_commit_data'), // Токен безопасности для AJAX
            'linkUsernames' => get_option('github_commit_chart_link_usernames', false), // Настройка для ссылок на профили
            'locale' => get_locale(), // Передаем локаль для фронтенда
            'dateFormat' => get_option('date_format') // Передаем формат даты из WordPress настроек
        ));

        // Подключение CSS стилей для фронтенда
        wp_enqueue_style(
            'github-commit-chart-frontend',     // Уникальный идентификатор стилей
            GCC_PLUGIN_URL . 'build/style-index.css', // URL к файлу стилей
            array(),                             // Без зависимостей
            file_exists($style_css) ? filemtime($style_css) : time() // Версия файла
        );
    }

    /**
     * Добавление пункта меню в админке
     *
     * Создает подменю в раздел "Настройки" для управления настройками плагина.
     *
     * @since 1.8.4
     */
    public function add_admin_menu() {
        // Добавляем подменю в раздел "Настройки"
        add_submenu_page(
            'options-general.php',
            __('GitHub Commit Chart Settings', 'github-commit-chart'),
            __('GitHub Commit Chart', 'github-commit-chart'),
            'manage_options',
            'github-commit-chart',
            array($this, 'options_page')
        );
    }

    /**
     * Отображение страницы настроек плагина
     *
     * Выводит HTML-разметку страницы настроек в админке WordPress,
     * включая форму с настройками плагина.
     *
     * @since 1.8.4
     */
    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('GitHub Commit Chart Settings', 'github-commit-chart'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('github_commit_chart_settings');
                do_settings_sections('github_commit_chart_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

}

// Инициализация плагина
GitHubCommitChart::get_instance();