<?php
/**
 * AJAX Handler for GitHub Commit Chart plugin
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class GitHubCommitChart_Ajax_Handler
 *
 * Handles AJAX requests for commit data retrieval.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */
class GitHubCommitChart_Ajax_Handler {

    /**
     * Инициализация AJAX обработчиков
     *
     * @since 1.8.4
     */
    public static function init() {
        // AJAX обработчики для получения данных о коммитах GitHub
        // Регистрируем для авторизованных пользователей
        add_action('wp_ajax_gcc_get_commit_data', array(__CLASS__, 'handle_get_commit_data'));
        // Регистрируем для неавторизованных пользователей (для фронтенда)
        add_action('wp_ajax_nopriv_gcc_get_commit_data', array(__CLASS__, 'handle_get_commit_data'));
    }

    /**
     * Приватный метод для логгирования отладочной информации
     *
     * Выводит сообщения в лог только если включен WP_DEBUG.
     * Используется для отладки AJAX запросов и API вызовов.
     *
     * @param string $message Сообщение для логгирования
     * @param mixed  $data    Дополнительные данные (опционально)
     * @since 1.8.4
     */
    private static function log_debug($message, $data = null) {
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
     * @since 1.8.4
     */
    public static function handle_get_commit_data() {
        // Логгируем начало обработки запроса
        self::log_debug('AJAX request received', $_POST);

        // Проверяем токен безопасности (nonce) для защиты от CSRF атак
        if (!wp_verify_nonce($_POST['nonce'], 'gcc_get_commit_data')) {
            self::log_debug('Security check failed');
            wp_send_json_error(__('Security check failed', 'github-commit-chart'));
            return;
        }

        // Получаем и очищаем имя пользователя GitHub
        $github_profile = sanitize_text_field($_POST['github_profile']);
        self::log_debug('github_profile', $github_profile);

        // Получаем год (опционально, по умолчанию текущий год)
        $year = isset($_POST['year']) ? intval($_POST['year']) : null;
        self::log_debug('year', $year);

        // Валидация входных данных
        $validation_errors = self::validate_request_data($github_profile, $year);
        if (!empty($validation_errors)) {
            self::log_debug('Validation errors', $validation_errors);
            wp_send_json_error(implode(' ', $validation_errors));
            return;
        }

        // Получаем статистику коммитов через API
        $stats = GitHubCommitChart_API::get_commit_stats($github_profile, $year);
        self::log_debug('stats', $stats);

        // Обрабатываем ошибки API
        if (is_wp_error($stats)) {
            self::log_debug('WP_Error', $stats->get_error_message());
            wp_send_json_error($stats->get_error_message());
            return;
        }

        // Проверяем на ошибки в массиве данных
        if (is_array($stats) && isset($stats['error'])) {
            self::log_debug('Error array', $stats['error']);
            wp_send_json_error($stats['error']);
            return;
        }

        // Возвращаем успешный результат
        wp_send_json_success($stats);
    }

    /**
     * Валидация входных данных для AJAX запроса
     *
     * @param string   $github_profile Имя пользователя GitHub
     * @param int|null $year           Год для статистики
     * @return array Массив ошибок валидации
     * @since 1.8.4
     */
    private static function validate_request_data($github_profile, $year) {
        $errors = array();

        // Проверяем обязательность поля профиля GitHub
        if (empty($github_profile)) {
            $errors[] = __('GitHub profile is required', 'github-commit-chart');
        }

        // Проверяем формат github_profile
        if (!empty($github_profile) && !preg_match('/^[a-zA-Z0-9\-_]+$/', $github_profile)) {
            $errors[] = __('Invalid GitHub profile format', 'github-commit-chart');
        }

        // Проверяем год
        if ($year !== null && ($year < 2008 || $year > date('Y') + 1)) {
            $errors[] = __('Invalid year specified', 'github-commit-chart');
        }

        return $errors;
    }
}

// Инициализация AJAX обработчиков
GitHubCommitChart_Ajax_Handler::init();