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
    private $system_cache = array(); // Кэш для системных настроек
    private $is_admin_page = false; // Флаг определения админ-страницы

    public function __construct() {
        // Оптимизированные хуки - только необходимые
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Условная загрузка админ функций
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_notices', array($this, 'show_system_notices'));
        }

        // Фронтенд хуки - только нужные
        add_filter('upload_size_limit', array($this, 'set_upload_size_limit'), 20);
        add_filter('wp_handle_upload_prefilter', array($this, 'validate_file_size'), 999);

        // Агрессивный режим - только при необходимости
        add_action('init', array($this, 'attempt_system_override'), 5);
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
     * Основное поле ввода - ОПТИМИЗИРОВАННАЯ версия
     */
    public function field_callback() {
        $current_value_mb = get_option('dipsic_max_upload_size_mb', 0);

        echo '<input type="number" id="dipsic_max_upload_size_mb" name="dipsic_max_upload_size_mb" value="' . esc_attr($current_value_mb) . '" class="small-text" min="0" max="512" step="0.1" />';
        echo ' <span class="description">' . __('МБ (мегабайт)', 'file-size-limit-pro') . '</span>';
        echo '<p class="description">' . __('0 = без ограничения. Максимум: 512 МБ', 'file-size-limit-pro') . '</p>';

        // Ленивое подключение отображения лимитов - только при необходимости
        add_action('admin_footer', array($this, 'lazy_load_current_limits'));
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
     * Попытка обхода системных ограничений - ОПТИМИЗИРОВАННАЯ версия
     */
    public function attempt_system_override() {
        // Кэшируем результат для избежания повторных вычислений
        $cache_key = 'dipsic_system_override_applied';

        // Проверяем, не применяли ли уже настройки
        if (get_transient($cache_key)) {
            return;
        }

        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);
        $aggressive_mode = get_option('dipsic_aggressive_mode', '0');

        if ($custom_limit_mb > 0 && $aggressive_mode === '1') {
            // Применяем только самые безопасные настройки
            $this->safe_ini_set('max_execution_time', '300');
            $this->safe_ini_set('max_input_time', '300');

            // Кэшируем успешное применение на 600 секунд (10 минут)
            set_transient($cache_key, true, 600);
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
     * Диагностика системных ограничений - ОПТИМИЗИРОВАННАЯ версия с lazy loading
     */
    public function display_system_diagnostics() {
        // Кэшируем результаты на 300 секунд (5 минут)
        $cache_key = 'dipsic_system_diagnostics';
        $cached_result = get_transient($cache_key);

        if ($cached_result !== false) {
            echo $cached_result;
            return;
        }

        // Ленивая загрузка только необходимых данных
        $current_limit = get_option('dipsic_max_upload_size_mb', 0);
        $wp_limit = wp_max_upload_size();
        $wp_limit_mb = round($wp_limit / (1024 * 1024), 1);

        $output = '<div style="background: #f0f0f1; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa;">';
        $output .= '<p><strong>' . __('Текущий статус:', 'file-size-limit-pro') . '</strong></p>';
        $output .= '<p>' . sprintf(__('Ваш лимит: %s МБ', 'file-size-limit-pro'), $current_limit) . '</p>';
        $output .= '<p>' . sprintf(__('Лимит WordPress: %s МБ', 'file-size-limit-pro'), $wp_limit_mb) . '</p>';
        $output .= '</div>';

        // Кэшируем результат
        set_transient($cache_key, $output, 300);

        echo $output;
    }

    /**
     * Получение системных лимитов - ОПТИМИЗИРОВАННАЯ версия с кэшированием
     */
    private function get_system_limits() {
        // Проверяем кэш системных настроек
        $cache_key = 'dipsic_system_limits';
        $cached_limits = get_transient($cache_key);

        if ($cached_limits !== false) {
            return $cached_limits;
        }

        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);

        // Кэшируем системные настройки на 600 секунд (10 минут)
        $limits = array(
            'Ваш лимит' => array(
                'value' => $custom_limit_mb > 0 ? $custom_limit_mb . ' МБ' : 'не установлен',
                'note' => ''
            ),
            'PHP upload_max_filesize' => array(
                'value' => $this->safe_ini_get('upload_max_filesize'),
                'note' => ''
            ),
            'PHP post_max_size' => array(
                'value' => $this->safe_ini_get('post_max_size'),
                'note' => ''
            ),
            'PHP memory_limit' => array(
                'value' => $this->safe_ini_get('memory_limit'),
                'note' => ''
            ),
            'WordPress limit' => array(
                'value' => size_format(wp_max_upload_size()),
                'note' => ''
            )
        );

        set_transient($cache_key, $limits, 600);

        return $limits;
    }

    /**
     * Безопасное получение ini настроек с кэшированием
     */
    private function safe_ini_get($setting) {
        // Используем кэш для избежания повторных @ini_get вызовов
        $cache_key = 'dipsic_ini_' . $setting;

        // Проверяем кэш сначала
        $cached_value = wp_cache_get($cache_key, 'dipsic_file_limit');
        if ($cached_value !== false) {
            return $cached_value;
        }

        // Получаем значение и кэшируем его
        $value = @ini_get($setting);
        if (empty($value)) {
            $value = 'не доступно';
        }

        // Кэшируем на 3600 секунд (1 час)
        wp_cache_set($cache_key, $value, 'dipsic_file_limit', 3600);

        return $value;
    }

    /**
     * Ленивая загрузка текущих лимитов - оптимизированная версия
     */
    public function lazy_load_current_limits() {
        // Проверяем кэш лимитов для избежания повторных вычислений
        $cache_key = 'dipsic_current_limits';
        $cached_limits = get_transient($cache_key);

        if ($cached_limits !== false) {
            echo '<div class="dipsic-current-limits" style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px; display: none;">';
            echo '<strong>' . __('Текущий эффективный лимит:', 'file-size-limit-pro') . '</strong> ';
            echo $cached_limits;
            echo '</div>';
            return;
        }

        // Кэшируем результат на 600 секунд (10 минут)
        $wp_max_size = wp_max_upload_size();
        $wp_max_size_mb = round($wp_max_size / (1024 * 1024), 1);

        $limit_output = number_format($wp_max_size_mb, 1) . ' МБ (' . size_format($wp_max_size) . ')';
        set_transient($cache_key, $limit_output, 600);

        echo '<div class="dipsic-current-limits" style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px; display: none;">';
        echo '<strong>' . __('Текущий эффективный лимит:', 'file-size-limit-pro') . '</strong> ';
        echo $limit_output;
        echo '</div>';
    }

    /**
     * Отображение текущих лимитов - УДАЛЕНО, заменено на lazy_load_current_limits
     *
     * @deprecated
     */
    private function display_current_limits() {
        // Метод оставлен для обратной совместимости
        $this->lazy_load_current_limits();
    }

    /**
     * Системные уведомления - ОПТИМИЗИРОВАННАЯ версия
     */
    public function show_system_notices() {
        // Проверяем, что мы на правильной странице
        $screen = get_current_screen();
        if ($screen->id !== 'options-media') return;

        // Проверяем кэш уведомлений - не показываем их слишком часто
        $cache_key = 'dipsic_system_notices_last_shown';
        $last_shown = get_transient($cache_key);

        // Показываем уведомления не чаще, чем раз в 30 минут
        if ($last_shown && (time() - $last_shown < 1800)) {
            return;
        }

        $custom_limit_mb = get_option('dipsic_max_upload_size_mb', 0);
        if ($custom_limit_mb === 0) return;

        $custom_limit_bytes = $custom_limit_mb * 1024 * 1024;
        $effective_limit = wp_max_upload_size();
        $effective_limit_mb = round($effective_limit / (1024 * 1024), 1);

        $notices_shown = false;

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
            $notices_shown = true;
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
            $notices_shown = true;
        }

        // Обновляем кэш если показали уведомления
        if ($notices_shown) {
            set_transient($cache_key, time(), 1800);
        }
    }

    /**
     * Подключение скриптов - ОПТИМИЗИРОВАННАЯ версия
     */
    public function enqueue_scripts($hook) {
        // Дополнительная проверка для оптимизации
        if ($hook !== 'options-media.php') return;

        // Условная загрузка только на странице медиа
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'options-media') return;

        wp_enqueue_script(
            'file-size-limit-pro',
            plugin_dir_url(__FILE__) . 'admin.js',
            array('jquery'),
            '2.1.0', // Обновленная версия для принудительной загрузки нового кода
            true
        );

        // Передаем данные в JavaScript для дополнительной оптимизации
        wp_localize_script('file-size-limit-pro', 'mediaSettingsPage', array(
            'page' => 'media-settings',
            'nonce' => wp_create_nonce('dipsic_media_settings_nonce')
        ));
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