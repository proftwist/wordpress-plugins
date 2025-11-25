<?php
/**
 * Plugin Name: Ограничитель размера файлов (PRO)
 * Plugin URI: https://github.com/
 * Description: Мощный ограничитель размера файлов с обходом системных ограничений. Размер указывается в мегабайтах.
 * Version: 2.0.0
 * Author: Владимир Бычко
 * Text Domain: file-size-limit-pro
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Запрещаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

class Dipsic_File_Size_Limit_Pro {

    private $max_php_override = 512; // Максимальная попытка override в МБ

    public function __construct() {
        // Только базовые хуки
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('upload_size_limit', array($this, 'set_upload_size_limit'), 20); // Нормальный приоритет

        // УБЕРИТЕ эти проблемные хуки:
        // add_filter('wp_handle_upload_prefilter', array($this, 'validate_file_size'), 9999);
        // add_action('init', array($this, 'attempt_system_override'));
        // add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        // add_action('admin_notices', array($this, 'show_system_notices'));
    }

    /**
     * Загрузка файлов перевода
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'file-size-limit-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Инициализация админ-панели - ИСПРАВЛЕННАЯ версия
     */
    public function admin_init() {
        // Регистрируем настройку ПРАВИЛЬНО
        register_setting(
            'media',
            'dipsic_max_upload_size_mb',
            array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_max_upload_size'),
                'default' => 0
            )
        );

        // Сначала проверяем, существует ли секция media
        global $wp_settings_sections;
        if (!isset($wp_settings_sections['media'])) {
            return;
        }

        // Добавляем секцию настроек
        add_settings_section(
            'dipsic_file_size_section',
            __('Ограничение размера файлов (PRO)', 'file-size-limit-pro'),
            array($this, 'section_callback'),
            'media'
        );

        add_settings_field(
            'dipsic_max_upload_size_field',
            __('Максимальный размер файла', 'file-size-limit-pro'),
            array($this, 'field_callback'),
            'media',
            'dipsic_file_size_section',
            array('label_for' => 'dipsic_max_upload_size_mb')
        );

        // Регистрируем вторую настройку
        register_setting(
            'media',
            'dipsic_aggressive_mode',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '0'
            )
        );

        add_settings_section(
            'dipsic_aggressive_section',
            __('Агрессивные настройки', 'file-size-limit-pro'),
            array($this, 'aggressive_section_callback'),
            'media'
        );

        add_settings_field(
            'dipsic_aggressive_mode_field',
            __('Агрессивный режим', 'file-size-limit-pro'),
            array($this, 'aggressive_field_callback'),
            'media',
            'dipsic_aggressive_section',
            array('label_for' => 'dipsic_aggressive_mode')
        );
    }

    /**
     * Описание основной секции
     */
    public function section_callback() {
        echo '<p>' . __('<strong>PRO версия:</strong> Установите максимальный размер загружаемых файлов в мегабайтах. Плагин попытается обойти системные ограничения.', 'file-size-limit-pro') . '</p>';

        // Диагностика системных ограничений
        $this->display_system_diagnostics();
    }

    /**
     * Описание агрессивной секции
     */
    public function aggressive_section_callback() {
        echo '<p>' . __('<strong>Внимание:</strong> Эти настройки могут не работать на некоторых хостингах. Используйте с осторожностью.', 'file-size-limit-pro') . '</p>';
    }

    /**
     * Основное поле ввода - ИСПРАВЛЕННАЯ версия
     */
    public function field_callback() {
        $current_value_mb = get_option('dipsic_max_upload_size_mb', 0);

        echo '<input type="number" id="dipsic_max_upload_size_mb" name="dipsic_max_upload_size_mb" value="' . esc_attr($current_value_mb) . '" class="small-text" min="0" max="512" step="0.1" />';
        echo ' <span class="description">' . __('МБ (мегабайт)', 'file-size-limit-pro') . '</span>';
        echo '<p class="description">' . __('0 = без ограничения. Максимум: 512 МБ', 'file-size-limit-pro') . '</p>';

        // Текущие лимиты
        $this->display_current_limits();
    }

    /**
     * Поле агрессивного режима - ИСПРАВЛЕННАЯ версия
     */
    public function aggressive_field_callback() {
        $aggressive_mode = get_option('dipsic_aggressive_mode', '0');

        echo '<label for="dipsic_aggressive_mode">';
        echo '<input type="checkbox" id="dipsic_aggressive_mode" name="dipsic_aggressive_mode" value="1" ' . checked('1', $aggressive_mode, false) . ' />';
        echo __(' Включить агрессивный обход ограничений', 'file-size-limit-pro');
        echo '</label>';
        echo '<p class="description">' . __('Пытается принудительно изменить системные настройки PHP. Может не работать на ограниченных хостингах.', 'file-size-limit-pro') . '</p>';
    }

    /**
     * Валидация введенного значения - УПРОЩЕННАЯ версия
     */
    public function sanitize_max_upload_size($value) {
        if (!is_numeric($value)) {
            return 0;
        }

        $value = floatval($value);

        if ($value < 0) {
            return 0;
        }

        if ($value > 512) {
            return 512;
        }

        return $value;
    }

    /**
     * Попытка обхода системных ограничений - БЕЗОПАСНАЯ версия
     */
    public function attempt_system_override() {
        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);
        $aggressive_mode = get_option('dipsic_aggressive_mode', '0');

        if ($custom_limit_mb > 0 && $aggressive_mode === '1') {
            // Только безопасные настройки
            $this->safe_ini_set('max_execution_time', '300');
            $this->safe_ini_set('max_input_time', '300');

            // Memory limit увеличиваем осторожно
            $current_memory = $this->return_bytes(ini_get('memory_limit'));
            $required_memory = 256 * 1024 * 1024; // 256MB максимум

            if ($current_memory < $required_memory) {
                $this->safe_ini_set('memory_limit', '256M');
            }

            // upload_max_filesize и post_max_size НЕ меняем - это вызывает ошибки
        }
    }

    /**
     * Безопасное изменение ini настроек с обработкой ошибок
     */
    private function safe_ini_set($setting, $value) {
        // Проверяем, доступна ли функция ini_set
        if (!function_exists('ini_set')) {
            return false;
        }

        // Пытаемся изменить настройку с подавлением ошибок
        $result = @ini_set($setting, $value);

        if ($result === false) {
            error_log("File Size Limit PRO: FAILED to change $setting to $value");
            return false;
        }

        return true;
    }

    /**
     * Установка лимита загрузки - ПРОСТАЯ версия
     */
    public function set_upload_size_limit($default) {
        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);

        if ($custom_limit_mb > 0) {
            $custom_limit_bytes = $custom_limit_mb * 1024 * 1024;
            return min($custom_limit_bytes, $default);
        }

        return $default;
    }

    /**
     * Валидация файла перед загрузкой
     */
    public function validate_file_size($file) {
        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);

        if ($custom_limit_mb > 0 && isset($file['size'])) {
            $custom_limit_bytes = $custom_limit_mb * 1024 * 1024;

            if ($file['size'] > $custom_limit_bytes) {
                $file_size_mb = round($file['size'] / (1024 * 1024), 1);

                $file['error'] = sprintf(
                    __('Файл слишком большой. %s МБ > %s МБ (максимальный разрешенный размер)', 'file-size-limit-pro'),
                    $file_size_mb,
                    $custom_limit_mb
                );
            }
        }

        return $file;
    }

    /**
     * Диагностика системных ограничений - МИНИМАЛЬНАЯ версия
     */
    public function display_system_diagnostics() {
        $current_limit = get_option('dipsic_max_upload_size_mb', 0);
        $wp_limit = wp_max_upload_size();
        $wp_limit_mb = round($wp_limit / (1024 * 1024), 1);

        echo '<div style="background: #f0f0f1; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa;">';
        echo '<p><strong>' . __('Текущий статус:', 'file-size-limit-pro') . '</strong></p>';
        echo '<p>' . sprintf(__('Ваш лимит: %s МБ', 'file-size-limit-pro'), $current_limit) . '</p>';
        echo '<p>' . sprintf(__('Лимит WordPress: %s МБ', 'file-size-limit-pro'), $wp_limit_mb) . '</p>';
        echo '</div>';
    }

    /**
     * Получение системных лимитов - БЕЗОПАСНАЯ версия
     */
    private function get_system_limits() {
        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);

        // Безопасное получение ini значений
        $upload_max = @ini_get('upload_max_filesize');
        $post_max = @ini_get('post_max_size');
        $memory_limit = @ini_get('memory_limit');

        return array(
            'Ваш лимит' => array(
                'value' => $custom_limit_mb > 0 ? $custom_limit_mb . ' МБ' : 'не установлен',
                'note' => ''
            ),
            'PHP upload_max_filesize' => array(
                'value' => $upload_max ?: 'не доступно',
                'note' => ''
            ),
            'PHP post_max_size' => array(
                'value' => $post_max ?: 'не доступно',
                'note' => ''
            ),
            'PHP memory_limit' => array(
                'value' => $memory_limit ?: 'не доступно',
                'note' => ''
            ),
            'WordPress limit' => array(
                'value' => size_format(wp_max_upload_size()),
                'note' => ''
            )
        );
    }

    /**
     * Отображение текущих лимитов
     */
    private function display_current_limits() {
        $wp_max_size = wp_max_upload_size();
        $wp_max_size_mb = round($wp_max_size / (1024 * 1024), 1);

        echo '<div style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px;">';
        echo '<strong>' . __('Текущий эффективный лимит:', 'file-size-limit-pro') . '</strong> ';
        echo number_format($wp_max_size_mb, 1) . ' МБ (' . size_format($wp_max_size) . ')';
        echo '</div>';
    }

    /**
     * Системные уведомления
     */
    public function show_system_notices() {
        $screen = get_current_screen();
        if ($screen->id !== 'options-media') return;

        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);
        if ($custom_limit_mb === 0) return;

        $custom_limit_bytes = $custom_limit_mb * 1024 * 1024;
        $effective_limit = wp_max_upload_size();
        $effective_limit_mb = round($effective_limit / (1024 * 1024), 1);

        // Если эффективный лимит меньше установленного
        if ($effective_limit < $custom_limit_bytes) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>' . __('File Size Limit PRO:', 'file-size-limit-pro') . '</strong> ';
            printf(
                __('Вы установили лимит в %s МБ, но эффективный лимит составляет %s МБ. Включите "Агрессивный режим" или обратитесь к хостинг-провайдеру.', 'file-size-limit-pro'),
                number_format($custom_limit_mb, 1),
                number_format($effective_limit_mb, 1)
            );
            echo '</p></div>';
        }

        // Предупреждение о необходимости агрессивного режима
        $upload_max = $this->return_bytes(ini_get('upload_max_filesize'));
        if ($custom_limit_bytes > $upload_max && get_option('dipsic_aggressive_mode', '0') === '0') {
            echo '<div class="notice notice-info">';
            echo '<p><strong>' . __('File Size Limit PRO:', 'file-size-limit-pro') . '</strong> ';
            printf(
                __('Ваш лимит (%s МБ) превышает системный upload_max_filesize (%s МБ). Включите "Агрессивный режим".', 'file-size-limit-pro'),
                number_format($custom_limit_mb, 1),
                number_format($upload_max / (1024 * 1024), 1)
            );
            echo '</p></div>';
        }
    }

    /**
     * Подключение скриптов
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'options-media.php') return;

        wp_enqueue_script(
            'file-size-limit-pro',
            plugin_dir_url(__FILE__) . 'admin.js',
            array('jquery'),
            '2.0.0',
            true
        );
    }

    /**
     * Конвертирует размеры из строки в байты
     */
    private function return_bytes($size_str) {
        if (empty($size_str)) return 0;
        if ($size_str == '-1') return PHP_INT_MAX;

        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size_str);
        $size = floatval(preg_replace('/[^0-9\.]/', '', $size_str));

        if ($unit) {
            $size = $size * pow(1024, stripos('bkmgtpezy', $unit[0]));
        }

        return round($size);
    }

    /**
     * Форматирует байты в читаемый вид
     */
    private function format_bytes($bytes) {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . ' ГБ';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' МБ';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' КБ';
        } else {
            return $bytes . ' Б';
        }
    }
}

// Инициализируем плагин
new Dipsic_File_Size_Limit_Pro();