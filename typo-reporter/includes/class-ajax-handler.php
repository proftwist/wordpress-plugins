<?php
/**
 * AJAX обработчик для плагина Typo Reporter
 *
 * Отвечает за обработку AJAX запросов от фронтенда и админки.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс TypoReporterAjaxHandler
 */
class TypoReporterAjaxHandler {

    /**
     * Генерация математической капчи
     *
     * @return array Массив с данными капчи
     * @since 2.1.0
     */
    public static function generate_math_captcha() {
        // Генерируем два случайных числа от 1 до 10
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        
        // Вычисляем правильный ответ
        $answer = $num1 + $num2;
        
        // Создаем хэш ответа для хранения в сессии
        $hash = hash_hmac('sha256', $answer, wp_salt('nonce'));
        
        // Возвращаем данные капчи
        return array(
            'num1' => $num1,
            'num2' => $num2,
            'hash' => $hash
        );
    }

    /**
     * Проверка математической капчи
     *
     * @param string $user_answer Ответ пользователя
     * @param string $captcha_hash Хэш капчи
     * @return bool Результат проверки
     * @since 2.1.0
     */
    public static function verify_math_captcha($user_answer, $captcha_hash) {
        // Проверяем, что ответ является числом
        if (!is_numeric($user_answer)) {
            return false;
        }
        
        // Проверяем хэш
        $expected_hash = hash_hmac('sha256', $user_answer, wp_salt('nonce'));
        return hash_equals($expected_hash, $captcha_hash);
    }

    /**
     * Инициализация AJAX обработчиков
     *
     * @since 1.0.0
     */
    public static function init() {
        // AJAX обработчики для фронтенда
        add_action('wp_ajax_typo_reporter_submit', array(__CLASS__, 'handle_submit_report'));
        add_action('wp_ajax_nopriv_typo_reporter_submit', array(__CLASS__, 'handle_submit_report'));

        // AJAX обработчики для админки
        add_action('wp_ajax_typo_reporter_delete_report', array(__CLASS__, 'handle_delete_report'));
        add_action('wp_ajax_typo_reporter_update_status', array(__CLASS__, 'handle_update_status'));
        add_action('wp_ajax_typo_reporter_clear_table', array(__CLASS__, 'handle_clear_table'));
    }

    /**
     * Обработка отправки репорта опечатки
     *
     * @since 1.0.0
     */
    public static function handle_submit_report() {
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'typo_reporter_submit')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'typo-reporter')));
            return;
        }

        // Проверка капчи
        $captcha_hash = $_POST['captcha_hash'] ?? '';
        $user_captcha = sanitize_text_field($_POST['captcha'] ?? '');

        if (empty($captcha_hash) || !self::verify_math_captcha($user_captcha, $captcha_hash)) {
            wp_send_json_error(array('message' => __('Invalid CAPTCHA answer. Please try again.', 'typo-reporter')));
            return;
        }

        // Получаем данные
        $selected_text = sanitize_text_field($_POST['selected_text'] ?? '');
        $error_description = sanitize_textarea_field($_POST['error_description'] ?? '');
        $page_url = esc_url_raw($_POST['page_url'] ?? '');

        // Валидация
        if (empty($selected_text)) {
            wp_send_json_error(array('message' => __('Selected text is required.', 'typo-reporter')));
            return;
        }

        if (strlen($error_description) > 1000) {
            wp_send_json_error(array('message' => __('Error description is too long. Maximum 1000 characters allowed.', 'typo-reporter')));
            return;
        }

        if (empty($page_url)) {
            $page_url = home_url($_SERVER['REQUEST_URI'] ?? '/');
        }

        // Сохранение репорта в базу данных
        $result = TypoReporterDatabase::add_report($selected_text, $error_description, $page_url);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }

        wp_send_json_success(array(
            'message' => __('Report submitted successfully!', 'typo-reporter'),
            'report_id' => $result
        ));
    }

    /**
     * Обработка удаления репорта
     *
     * @since 1.0.0
     */
    public static function handle_delete_report() {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'typo-reporter')));
            return;
        }

        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'typo_reporter_admin')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'typo-reporter')));
            return;
        }

        $report_id = intval($_POST['report_id'] ?? 0);

        if (!$report_id) {
            wp_send_json_error(array('message' => __('Invalid report ID.', 'typo-reporter')));
            return;
        }

        $result = TypoReporterDatabase::delete_report($report_id);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete report.', 'typo-reporter')));
            return;
        }

        wp_send_json_success(array('message' => __('Report deleted successfully.', 'typo-reporter')));
    }

    /**
     * Обработка обновления статуса репорта
     *
     * @since 1.0.0
     */
    public static function handle_update_status() {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'typo-reporter')));
            return;
        }

        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'typo_reporter_admin')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'typo-reporter')));
            return;
        }

        $report_id = intval($_POST['report_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (!$report_id) {
            wp_send_json_error(array('message' => __('Invalid report ID.', 'typo-reporter')));
            return;
        }

        $valid_statuses = array('new', 'resolved', 'dismissed');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'typo-reporter')));
            return;
        }

        $result = TypoReporterDatabase::update_report_status($report_id, $status);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update report status.', 'typo-reporter')));
            return;
        }

        wp_send_json_success(array('message' => __('Report status updated successfully.', 'typo-reporter')));
    }

    /**
     * Обработка очистки таблицы репортов
     *
     * @since 2.0.0
     */
    public static function handle_clear_table() {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'typo-reporter')));
            return;
        }

        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'typo_reporter_admin')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'typo-reporter')));
            return;
        }

        $result = TypoReporterDatabase::clear_table();

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to clear table.', 'typo-reporter')));
            return;
        }

        wp_send_json_success(array('message' => __('Table cleared successfully.', 'typo-reporter')));
    }
}

// Инициализация AJAX обработчиков
TypoReporterAjaxHandler::init();