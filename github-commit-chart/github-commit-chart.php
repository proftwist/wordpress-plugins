<?php
/**
 * Plugin Name: GitHub Commit Chart
 * Description: Отображает диаграмму коммитов GitHub в виде Gutenberg-блока или шорткода
 * Version: 1.8.3
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: github-commit-chart
 * 
 * @package GitHubCommitChart
 */

// Защита от прямого доступа - предотвращает выполнение файла вне WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант для удобства работы с путями
define('GCC_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Абсолютный путь к папке плагина
define('GCC_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL к папке плагина
define('GCC_PLUGIN_VERSION', '1.8.3');                // Версия плагина

// Подключение вспомогательных файлов
require_once GCC_PLUGIN_PATH . 'includes/admin-settings.php';       // Файл настроек администратора
require_once GCC_PLUGIN_PATH . 'includes/block-registration.php'; // Регистрация Gutenberg-блока
require_once GCC_PLUGIN_PATH . 'includes/github-api.php';          // Работа с GitHub API

/**
 * Основной класс плагина GitHub Commit Chart
 *
 * Отвечает за инициализацию плагина, регистрацию Gutenberg-блока,
 * подключение стилей и скриптов, а также обработку AJAX-запросов
 * для получения данных о коммитах GitHub.
 * 
 * @package GitHubCommitChart
 * @since 1.0.0
 */
class GitHubCommitChart {

    /**
     * Экземпляр класса GitHubCommitChart
     *
     * @var GitHubCommitChart
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса GitHubCommitChart
     *
     * @return GitHubCommitChart
     * @since 1.0.0
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
     * Регистрирует основные хуки WordPress при инициализации плагина:
     * - init: основная инициализация плагина
     * - admin_menu: добавление пункта меню в админку
     * - wp_ajax_*: обработчики AJAX для получения данных о коммитах (для авторизованных и неавторизованных пользователей)
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Регистрация основных хуков WordPress
        add_action('init', array($this, 'init'));  // Инициализация плагина
        add_action('admin_menu', array($this, 'add_admin_menu')); // Меню в админке

        // AJAX обработчики для получения данных о коммитах GitHub
        // Регистрируем для авторизованных пользователей
        add_action('wp_ajax_gcc_get_commit_data', array($this, 'ajax_get_commit_data'));
        // Регистрируем для неавторизованных пользователей (для фронтенда)
        add_action('wp_ajax_nopriv_gcc_get_commit_data', array($this, 'ajax_get_commit_data'));

        // Регистрация шорткода
        add_shortcode('github-c', array($this, 'shortcode_handler'));
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов:
     * - enqueue_block_editor_assets: загрузка скриптов и стилей для редактора блоков
     * - wp_enqueue_scripts: загрузка скриптов и стилей для фронтенда сайта
     * 
     * @since 1.0.0
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
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'), // Зависимости WordPress
            file_exists($index_js) ? filemtime($index_js) : time(), // Версия на основе времени изменения файла
            true // Загружать в футере
        );

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
            array('wp-element'),                 // Зависимость React для работы с компонентами
            file_exists($frontend_js) ? filemtime($frontend_js) : time(), // Версия файла
            true                                 // Загружать в футере
        );

        // Передача настроек плагина в JavaScript
        // Создает глобальный объект githubCommitChartSettings доступный в JS
        wp_localize_script('github-commit-chart-frontend', 'githubCommitChartSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),    // URL для AJAX запросов
            'githubProfile' => get_option('github_commit_chart_github_profile', ''), // Профиль GitHub из настроек
            'nonce' => wp_create_nonce('gcc_get_commit_data'), // Токен безопасности для AJAX
            'linkUsernames' => get_option('github_commit_chart_link_usernames', false) // Настройка для ссылок на профили
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
     * Создает подменю в разделе "Настройки" для управления настройками плагина.
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Добавляем подменю в раздел "Настройки"
        add_submenu_page(
            'options-general.php',
            'Git-диаграмма',
            'Git-диаграмма',
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
     * @since 1.0.0
     */
    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Git-диаграмма Настройки</h1>
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

    /**
     * Приватный метод для логгирования отладочной информации
     *
     * Выводит сообщения в лог только если включен WP_DEBUG.
     * Используется для отладки AJAX запросов и API вызовов.
     *
     * @param string $message Сообщение для логгирования
     * @param mixed $data Дополнительные данные (опционально)
     * @since 1.0.0
     */
    private function log_debug($message, $data = null) {
        // Логгируем только в режиме отладки
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'GitHub Commit Chart: ' . $message;
            if ($data !== null) {
                $log_message .= ' = ' . print_r($data, true);
            }
            error_log($log_message);
        }
    }

    /**
     * AJAX обработчик для получения данных о коммитах GitHub
     *
     * Обрабатывает AJAX запросы от фронтенда, получает статистику коммитов
     * через GitHub API и возвращает данные в формате JSON.
     * Включает проверки безопасности и валидацию данных.
     * 
     * @since 1.0.0
     */
    public function ajax_get_commit_data() {
        // Логгируем начало обработки запроса
        $this->log_debug('AJAX request received', $_POST);

        // Проверяем токен безопасности (nonce) для защиты от CSRF атак
        if (!wp_verify_nonce($_POST['nonce'], 'gcc_get_commit_data')) {
            $this->log_debug('Security check failed');
            wp_send_json_error('Security check failed');
            return;
        }

        // Получаем и очищаем имя пользователя GitHub
        $github_profile = sanitize_text_field($_POST['github_profile']);
        $this->log_debug('github_profile', $github_profile);

        // Получаем год (опционально, по умолчанию текущий год)
        $year = isset($_POST['year']) ? intval($_POST['year']) : null;
        $this->log_debug('year', $year);

        // Проверяем обязательность поля профиля GitHub
        if (empty($github_profile)) {
            wp_send_json_error('GitHub profile is required');
            return;
        }

        // Проверяем доступность класса GitHub API
        if (!class_exists('GitHubCommitChart_API')) {
            $this->log_debug('API class not found');
            wp_send_json_error('API class not found');
            return;
        }

        // Получаем статистику коммитов через API
        $stats = GitHubCommitChart_API::get_commit_stats($github_profile, $year);
        $this->log_debug('stats', $stats);

        // Обрабатываем ошибки API
        if (is_wp_error($stats)) {
            $this->log_debug('WP_Error', $stats->get_error_message());
            wp_send_json_error($stats->get_error_message());
            return;
        }

        // Проверяем на ошибки в массиве данных
        if (is_array($stats) && isset($stats['error'])) {
            $this->log_debug('Error array', $stats['error']);
            wp_send_json_error($stats['error']);
            return;
        }

        // Возвращаем успешный результат
        wp_send_json_success($stats);
    }

    /**
     * Обработчик шорткода [github-c]
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-разметка диаграммы
     * @since 1.4.0
     */
    public function shortcode_handler($atts) {
        // Объединяем атрибуты шорткода с значениями по умолчанию
        $atts = shortcode_atts(array(
            'github_profile' => '',
        ), $atts, 'github-c');

        // Получаем профиль GitHub из атрибутов или из глобальных настроек плагина
        $github_profile = !empty($atts['github_profile']) ?
                         $atts['github_profile'] :
                         get_option('github_commit_chart_github_profile', '');

        // Проверяем, указан ли профиль GitHub
        if (empty($github_profile)) {
            return '<p>Пожалуйста, укажите путь к профилю GitHub в настройках плагина или в атрибутах шорткода.</p>';
        }

        // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
        $unique_id = uniqid('gcc-');

        // Формируем data-атрибуты для передачи данных в JavaScript
        // Безопасно экранируем значения функцией esc_attr
        $data_attributes = 'data-github-profile="' . esc_attr($github_profile) . '" data-container-id="' . esc_attr($unique_id) . '"';

        // Возвращаем HTML контейнер для диаграммы
        return '<div class="github-commit-chart-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                    <div class="github-commit-chart-loading">Загрузка диаграммы коммитов...</div>
                </div>';
    }
}

// Инициализация плагина
GitHubCommitChart::get_instance();