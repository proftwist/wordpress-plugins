<?php
/**
 * Plugin Name: GitHub Commit Chart
 * Description: Отображает диаграмму коммитов GitHub в виде Gutenberg-блока
 * Version: 1.1.0
 * Author: Владимир Бычко
 * Text Domain: github-commit-chart
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант
define('GCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GCC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Подключение файлов с проверкой существования
$admin_settings_file = GCC_PLUGIN_PATH . 'includes/admin-settings.php';
$block_registration_file = GCC_PLUGIN_PATH . 'includes/block-registration.php';
$github_api_file = GCC_PLUGIN_PATH . 'includes/github-api.php';

if (file_exists($admin_settings_file)) {
    require_once $admin_settings_file;
}

if (file_exists($block_registration_file)) {
    require_once $block_registration_file;
}

if (file_exists($github_api_file)) {
    require_once $github_api_file;
}

// Инициализация плагина
/**
 * Основной класс плагина GitHub Commit Chart
 *
 * Отвечает за инициализацию плагина, регистрацию Gutenberg-блока,
 * подключение стилей и скриптов, а также обработку AJAX-запросов
 * для получения данных о коммитах GitHub.
 */
class GitHubCommitChart {

    /**
     * Конструктор класса GitHubCommitChart
     *
     * Регистрирует основные хуки WordPress при инициализации плагина.
     * Подключает обработчики AJAX для получения данных о коммитах.
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Регистрируем AJAX обработчики всегда, а не только в админке
        add_action('wp_ajax_gcc_get_commit_data', array($this, 'ajax_get_commit_data'));
        add_action('wp_ajax_nopriv_gcc_get_commit_data', array($this, 'ajax_get_commit_data'));
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов в редакторе блоков
     * и на фронтенде сайта.
     */
    public function init() {
        // Регистрация блока
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }


    /**
     * Подключение ресурсов для редактора блоков Gutenberg
     *
     * Загружает JavaScript и CSS файлы, необходимые для работы
     * блока диаграммы коммитов в редакторе блоков.
     */
    public function enqueue_block_editor_assets() {
        $index_js = GCC_PLUGIN_PATH . 'build/index.js';
        $index_css = GCC_PLUGIN_PATH . 'build/index.css';

        wp_enqueue_script(
            'github-commit-chart-block',
            GCC_PLUGIN_URL . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            file_exists($index_js) ? filemtime($index_js) : time(),
            true
        );

        wp_enqueue_style(
            'github-commit-chart-block-editor',
            GCC_PLUGIN_URL . 'build/index.css',
            array('wp-edit-blocks'),
            file_exists($index_css) ? filemtime($index_css) : time()
        );
    }

    /**
     * Подключение ресурсов для фронтенда
     *
     * Загружает JavaScript и CSS файлы, необходимые для отображения
     * диаграммы коммитов на сайте. Передает настройки в JavaScript.
     */
    public function enqueue_frontend_assets() {
        $frontend_js = GCC_PLUGIN_PATH . 'build/frontend.js';
        $style_css = GCC_PLUGIN_PATH . 'build/style-index.css';

        wp_enqueue_script(
            'github-commit-chart-frontend',
            GCC_PLUGIN_URL . 'build/frontend.js',
            array('wp-element'),
            file_exists($frontend_js) ? filemtime($frontend_js) : time(),
            true
        );

        // Передаем данные в скрипт
        wp_localize_script('github-commit-chart-frontend', 'githubCommitChartSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'githubProfile' => get_option('github_commit_chart_github_profile', ''),
            'nonce' => wp_create_nonce('gcc_get_commit_data')
        ));

        wp_enqueue_style(
            'github-commit-chart-frontend',
            GCC_PLUGIN_URL . 'build/style-index.css',
            array(),
            file_exists($style_css) ? filemtime($style_css) : time()
        );
    }

    /**
     * Добавление пункта меню в админке
     *
     * Создает подменю в разделе "Настройки" для управления настройками плагина.
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
     * AJAX обработчик для получения данных о коммитах
     */
    /**
     * Логгирование отладочной информации
     *
     * @param string $message Сообщение для логгирования
     * @param mixed $data Дополнительные данные (опционально)
     */
    private function log_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'GitHub Commit Chart: ' . $message;
            if ($data !== null) {
                $log_message .= ' = ' . print_r($data, true);
            }
            error_log($log_message);
        }
    }

    /**
     * AJAX обработчик для получения данных о коммитах
     */
    public function ajax_get_commit_data() {
        // Отладочный вывод
        $this->log_debug('AJAX request received', $_POST);

        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'], 'gcc_get_commit_data')) {
            $this->log_debug('Security check failed');
            wp_die('Security check failed');
        }

        $github_profile = sanitize_text_field($_POST['github_profile']);
        $this->log_debug('github_profile', $github_profile);

        if (empty($github_profile)) {
            wp_send_json_error('GitHub profile is required');
            return;
        }

        // Проверяем существование класса API
        if (!class_exists('GitHubCommitChart_API')) {
            $this->log_debug('API class not found');
            wp_send_json_error('API class not found');
            return;
        }

        // Получаем статистику коммитов
        $stats = GitHubCommitChart_API::get_commit_stats($github_profile);
        $this->log_debug('stats', $stats);

        // Проверяем ошибки
        if (is_wp_error($stats)) {
            $this->log_debug('WP_Error', $stats->get_error_message());
            wp_send_json_error($stats->get_error_message());
            return;
        }

        // Проверяем, является ли результат массивом с ошибкой
        if (is_array($stats) && isset($stats['error'])) {
            $this->log_debug('Error array', $stats['error']);
            wp_send_json_error($stats['error']);
            return;
        }

        wp_send_json_success($stats);
    }
}

// Инициализация плагина
new GitHubCommitChart();