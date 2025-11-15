<?php
/**
 * Assets Manager for GitHub Commit Chart plugin
 *
 * Handles all asset enqueueing for the plugin.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class GitHubCommitChart_Assets_Manager
 *
 * Manages all assets (CSS, JS) for the plugin.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */
class GitHubCommitChart_Assets_Manager {

    /**
     * Инициализация менеджера ресурсов
     *
     * @since 1.8.4
     */
    public static function init() {
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_block_editor_assets'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
    }

    /**
     * Подключение ресурсов для редактора блоков Gutenberg
     *
     * Загружает JavaScript и CSS файлы, необходимые для работы блока в редакторе:
     * - JavaScript: логика блока с зависимостями wp-blocks, wp-element, wp-block-editor, wp-components
     * - CSS: стили для блока в редакторе с зависимостью wp-edit-blocks
     * Использует filemtime для версионирования файлов (кеширование).
     *
     * @since 1.8.4
     */
    public static function enqueue_block_editor_assets() {
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
     * @since 1.8.4
     */
    public static function enqueue_frontend_assets() {
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
}

// Инициализация менеджера ресурсов
GitHubCommitChart_Assets_Manager::init();