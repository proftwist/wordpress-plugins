<?php
/**
 * Менеджер ресурсов для плагина Admin Panel Trash
 *
 * @package AdminPanelTrash
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс для управления ресурсами (CSS/JS)
 */
class AdminPanelTrash_Assets_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Подключение скриптов и стилей в админке
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на странице настроек плагина
        if ('settings_page_admin-panel-trash' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'admin-panel-trash-admin-js',
            ADMIN_PANEL_TRASH_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            ADMIN_PANEL_TRASH_PLUGIN_VERSION,
            true
        );

        // Локализация для AJAX
        wp_localize_script('admin-panel-trash-admin-js', 'apt_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('admin_panel_trash_nonce')
        ));

        // Локализация текстов
        wp_localize_script('admin-panel-trash-admin-js', 'apt_localize', array(
            'checking' => __('Проверка...', 'admin-panel-trash'),
            'file_path' => __('Путь к файлу:', 'admin-panel-trash'),
            'read_access' => __('Доступ на чтение:', 'admin-panel-trash'),
            'write_access' => __('Доступ на запись:', 'admin-panel-trash'),
            'yes' => __('Да', 'admin-panel-trash'),
            'no' => __('Нет', 'admin-panel-trash'),
            'error' => __('Ошибка', 'admin-panel-trash'),
            'request_error' => __('Ошибка запроса', 'admin-panel-trash'),
            'check_access' => __('Проверить доступ', 'admin-panel-trash'),
            'loading' => __('Загрузка...', 'admin-panel-trash'),
            'load_error' => __('Ошибка загрузки', 'admin-panel-trash'),
            'no_items' => __('Элементы не найдены', 'admin-panel-trash'),
            'enabled' => __('Включен', 'admin-panel-trash'),
            'disabled' => __('Отключен', 'admin-panel-trash'),
            'disable' => __('Убрать', 'admin-panel-trash'),
            'enable' => __('Вернуть', 'admin-panel-trash'),
            'processing' => __('Обработка...', 'admin-panel-trash'),
            'item_enabled' => __('Элемент включен', 'admin-panel-trash'),
            'item_disabled' => __('Элемент отключен', 'admin-panel-trash'),
            'invalid_item_id' => __('Неверный ID элемента', 'admin-panel-trash'),
            'error_enabling_item' => __('Ошибка при включении элемента', 'admin-panel-trash'),
            'error_disabling_item' => __('Ошибка при отключении элемента', 'admin-panel-trash')
        ));

        wp_enqueue_style(
            'admin-panel-trash-admin-css',
            ADMIN_PANEL_TRASH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ADMIN_PANEL_TRASH_PLUGIN_VERSION
        );
    }
}