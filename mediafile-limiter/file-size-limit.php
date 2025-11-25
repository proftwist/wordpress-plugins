<?php
/**
 * Plugin Name: Ограничитель размера файлов
 * Plugin URI: https://github.com/
 * Description: Добавляет опцию ограничения размера загружаемых файлов в Настройки → Медиафайлы
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Text Domain: file-size-limit
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Запрещаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

class Dipsic_File_Size_Limit {

    public function __construct() {
        // Инициализация плагина
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('upload_size_limit', array($this, 'set_upload_size_limit'), 999);
    }

    /**
     * Загрузка файлов перевода
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'file-size-limit',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Инициализация админ-панели
     */
    public function admin_init() {
        // Регистрируем настройку
        register_setting(
            'media',
            'dipsic_max_upload_size',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_max_upload_size'),
                'default' => 0
            )
        );

        // Добавляем секцию настроек
        add_settings_section(
            'dipsic_file_size_section',
            __('Ограничение размера файлов', 'file-size-limit'),
            array($this, 'section_callback'),
            'media'
        );

        // Добавляем поле настроек
        add_settings_field(
            'dipsic_max_upload_size_field',
            __('Максимальный размер файла', 'file-size-limit'),
            array($this, 'field_callback'),
            'media',
            'dipsic_file_size_section'
        );
    }

    /**
     * Описание секции настроек
     */
    public function section_callback() {
        echo '<p>' . __('Установите максимальный размер загружаемых файлов в байтах. Этот лимит будет иметь наивысший приоритет.', 'file-size-limit') . '</p>';
        echo '<p>' . __('<strong>Рекомендация:</strong> 1048576 байт = 1 МБ', 'file-size-limit') . '</p>';
    }

    /**
     * Поле ввода для настройки
     */
    public function field_callback() {
        $current_value = get_option('dipsic_max_upload_size', 0);
        echo '<input type="number" name="dipsic_max_upload_size" value="' . esc_attr($current_value) . '" class="small-text" min="0" step="1" />';
        echo '<p class="description">' . __('0 = без ограничения. Установленное здесь значение переопределит все другие лимиты.', 'file-size-limit') . '</p>';

        // Показываем текущие системные лимиты для справки
        $wp_max_size = wp_max_upload_size();
        echo '<p class="description">' . sprintf(
            __('Текущий лимит WordPress: %s байт (%s)', 'file-size-limit'),
            number_format_i18n($wp_max_size),
            size_format($wp_max_size)
        ) . '</p>';
    }

    /**
     * Валидация введенного значения
     */
    public function sanitize_max_upload_size($value) {
        $value = absint($value);

        // Проверяем, что значение не отрицательное
        if ($value < 0) {
            add_settings_error(
                'dipsic_max_upload_size',
                'invalid_size',
                __('Размер файла не может быть отрицательным.', 'file-size-limit')
            );
            return 0;
        }

        return $value;
    }

    /**
     * Установка лимита загрузки с наивысшим приоритетом
     */
    public function set_upload_size_limit($default) {
        $custom_limit = get_option('dipsic_max_upload_size', 0);

        // Если установлен кастомный лимит и он больше 0, используем его
        if ($custom_limit > 0) {
            return min($custom_limit, $default);
        }

        // Иначе возвращаем стандартный лимит
        return $default;
    }
}

// Инициализируем плагин
new Dipsic_File_Size_Limit();