<?php
/**
 * Plugin Name: Ограничитель размера файлов
 * Plugin URI: https://github.com/
 * Description: Добавляет опцию ограничения размера загружаемых файлов в Настройки → Медиафайлы. Размер указывается в мегабайтах.
 * Version: 1.1.1
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
        // Регистрируем настройку без принудительного типа
        register_setting(
            'media',
            'dipsic_max_upload_size',
            array(
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
        echo '<p>' . __('Установите максимальный размер загружаемых файлов в мегабайтах. Этот лимит будет иметь наивысший приоритет.', 'file-size-limit') . '</p>';
        echo '<p>' . __('<strong>Примечание:</strong> Значение указывается в мегабайтах (МБ).', 'file-size-limit') . '</p>';
    }

    /**
     * Поле ввода для настройки
     */
    public function field_callback() {
        // Получаем сохраненное значение в байтах и переводим в мегабайты
        $saved_bytes = get_option('dipsic_max_upload_size', 0);
        $current_value_mb = $saved_bytes > 0 ? ($saved_bytes / (1024 * 1024)) : 0;

        // Отладочная информация
        error_log("Saved bytes: " . $saved_bytes . " -> MB: " . $current_value_mb);

        echo '<input type="number" name="dipsic_max_upload_size" value="' . esc_attr($current_value_mb) . '" class="small-text" min="0" step="0.1" />';
        echo ' <span class="description">' . __('МБ (мегабайт)', 'file-size-limit') . '</span>';
        echo '<p class="description">' . __('0 = без ограничения. Установленное здесь значение переопределит все другие лимиты.', 'file-size-limit') . '</p>';

        // Показываем текущие системные лимиты для справки
        $wp_max_size = wp_max_upload_size();
        $wp_max_size_mb = $wp_max_size / (1024 * 1024);
        echo '<p class="description">' . sprintf(
            __('Текущий лимит WordPress: %s МБ (%s)', 'file-size-limit'),
            $wp_max_size_mb,
            size_format($wp_max_size)
        ) . '</p>';
    }

    /**
     * Валидация введенного значения
     */
    public function sanitize_max_upload_size($value) {
        error_log("Input value: " . $value);

        // Получаем значение как число с плавающей точкой
        $value = floatval($value);

        // Проверяем, что значение не отрицательное
        if ($value < 0) {
            add_settings_error(
                'dipsic_max_upload_size',
                'invalid_size',
                __('Размер файла не может быть отрицательным.', 'file-size-limit')
            );
            return 0;
        }

        // Проверяем, что значение не слишком большое (безопасность)
        if ($value > 1024) {
            add_settings_error(
                'dipsic_max_upload_size',
                'too_large',
                __('Размер файла не может превышать 1024 МБ.', 'file-size-limit')
            );
            return 0;
        }

        // Конвертируем мегабайты в байты и сохраняем
        $bytes = $value > 0 ? intval(round($value * 1024 * 1024)) : 0;

        error_log("Converted to bytes: " . $bytes);

        return $bytes;
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