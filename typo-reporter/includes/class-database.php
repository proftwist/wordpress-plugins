<?php
/**
 * Класс для работы с базой данных плагина Typo Reporter
 *
 * Отвечает за создание и управление таблицей репортов опечаток.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс TypoReporterDatabase
 */
class TypoReporterDatabase {

    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    private static $table_name = 'typo_reports';

    /**
     * Создание таблицы базы данных
     *
     * @since 1.0.0
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            selected_text text NOT NULL,
            error_description text,
            page_url varchar(255) NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status enum('new','resolved','dismissed') DEFAULT 'new' NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Обновляем версию плагина для отслеживания изменений в структуре БД
        update_option('typo_reporter_db_version', '1.0.0');
    }

    /**
     * Добавление нового репорта опечатки
     *
     * @param string $selected_text Выделенный текст
     * @param string $error_description Описание ошибки
     * @param string $page_url URL страницы
     * @return int|WP_Error ID нового репорта или ошибка
     * @since 1.0.0
     */
    public static function add_report($selected_text, $error_description, $page_url) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // Детальное логирование
        error_log('[TYPO REPORTER DB] Adding report to database:');
        error_log('[TYPO REPORTER DB] Selected text: "' . $selected_text . '"');
        error_log('[TYPO REPORTER DB] Error description: "' . $error_description . '"');
        error_log('[TYPO REPORTER DB] Page URL: "' . $page_url . '"');
        error_log('[TYPO REPORTER DB] Error description length: ' . strlen($error_description));

        $data = array(
            'selected_text' => $selected_text,
            'error_description' => $error_description,
            'page_url' => $page_url,
            'user_ip' => self::get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'status' => 'new'
        );

        $format = array(
            '%s', // selected_text
            '%s', // error_description
            '%s', // page_url
            '%s', // user_ip
            '%s', // user_agent
            '%s'  // status
        );

        error_log('[TYPO REPORTER DB] Data to insert: ' . print_r($data, true));
        error_log('[TYPO REPORTER DB] Format: ' . print_r($format, true));

        $result = $wpdb->insert($table_name, $data, $format);

        if ($result === false) {
            error_log('[TYPO REPORTER DB] INSERT FAILED: ' . $wpdb->last_error);
            error_log('[TYPO REPORTER DB] Last query: ' . $wpdb->last_query);
            return new WP_Error('db_insert_error', __('Failed to save typo report', 'typo-reporter'));
        }

        error_log('[TYPO REPORTER DB] INSERT SUCCESS. Report ID: ' . $wpdb->insert_id);

        // Проверим, что действительно сохранилось
        $saved_report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $wpdb->insert_id));
        if ($saved_report) {
            error_log('[TYPO REPORTER DB] VERIFICATION - Saved report:');
            error_log('[TYPO REPORTER DB] Selected text: "' . $saved_report->selected_text . '"');
            error_log('[TYPO REPORTER DB] Error description: "' . $saved_report->error_description . '"');
            error_log('[TYPO REPORTER DB] Error description length: ' . strlen($saved_report->error_description));
        }

        return $wpdb->insert_id;
    }

    /**
     * Получение всех репортов опечаток
     *
     * @param array $args Аргументы запроса
     * @return array Массив репортов
     * @since 1.0.0
     */
    public static function get_reports($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $defaults = array(
            'status' => '',
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array();
        $where_values = array();

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $order_clause = sprintf('ORDER BY %s %s', sanitize_sql_orderby($args['orderby']), $args['order'] === 'DESC' ? 'DESC' : 'ASC');

        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare('LIMIT %d, %d', $args['offset'], $args['limit']) : '';

        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name $where_clause $order_clause $limit_clause",
            $where_values
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Получение количества репортов
     *
     * @param string $status Статус репортов
     * @return int Количество репортов
     * @since 1.0.0
     */
    public static function get_reports_count($status = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        if (empty($status)) {
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        } else {
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", $status));
        }
    }

    /**
     * Обновление статуса репорта
     *
     * @param int $report_id ID репорта
     * @param string $status Новый статус
     * @return bool Успешно ли обновлено
     * @since 1.0.0
     */
    public static function update_report_status($report_id, $status) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $valid_statuses = array('new', 'resolved', 'dismissed');

        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => intval($report_id)),
            array('%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Удаление репорта
     *
     * @param int $report_id ID репорта
     * @return bool Успешно ли удалено
     * @since 1.0.0
     */
    public static function delete_report($report_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $result = $wpdb->delete(
            $table_name,
            array('id' => intval($report_id)),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Получение IP адреса пользователя
     *
     * @return string IP адрес
     * @since 1.0.0
     */
    private static function get_user_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Валидация IP адреса
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return 'unknown';
    }
}