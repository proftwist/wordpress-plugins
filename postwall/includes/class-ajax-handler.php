<?php
/**
 * AJAX обработчик для плагина Post Wall
 *
 * Обрабатывает все AJAX запросы плагина.
 *
 * @package PostWall
 * @since 2.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс PostWall_Ajax_Handler
 *
 * Обрабатывает AJAX запросы для получения данных о постах.
 *
 * @package PostWall
 * @since 2.0.0
 */
class PostWall_Ajax_Handler {

    /**
     * Инициализация AJAX обработчиков
     *
     * @since 2.0.0
     */
    public static function init() {
        // AJAX обработчики для получения данных о постах
        // Регистрируем для авторизованных пользователей
        add_action('wp_ajax_postwall_get_post_data', array(__CLASS__, 'handle_get_post_data'));
        // Регистрируем для неавторизованных пользователей (для фронтенда)
        add_action('wp_ajax_nopriv_postwall_get_post_data', array(__CLASS__, 'handle_get_post_data'));
    }


    /**
     * AJAX обработчик для получения данных о постах
     *
     * Обрабатывает AJAX запросы от фронтенда, получает статистику постов
     * через WordPress REST API и возвращает данные в формате JSON.
     * Включает проверки безопасности и валидацию данных.
     *
     * @since 2.0.0
     */
    public static function handle_get_post_data() {
        // Проверяем токен безопасности (nonce) для защиты от CSRF атак
        if (!wp_verify_nonce($_POST['nonce'], 'postwall_get_data')) {
            wp_send_json_error(__('Security check failed', 'postwall'));
            return;
        }

        // Получаем и очищаем URL сайта
        $site_url = sanitize_text_field($_POST['site_url']);

        // Валидация входных данных
        $validation_errors = self::validate_request_data($site_url);
        if (!empty($validation_errors)) {
            wp_send_json_error(implode(' ', $validation_errors));
            return;
        }

        // Получаем статистику постов через API
        $stats = PostWall_API::get_post_stats($site_url);

        // Обрабатываем ошибки API
        if (is_wp_error($stats)) {
            wp_send_json_error($stats->get_error_message());
            return;
        }

        // Проверяем на ошибки в массиве данных
        if (is_array($stats) && isset($stats['error'])) {
            wp_send_json_error($stats['error']);
            return;
        }

        // Возвращаем успешный результат
        wp_send_json_success($stats);
    }

    /**
     * Валидация входных данных для AJAX запроса
     *
     * @param string $site_url URL сайта
     * @return array Массив ошибок валидации
     * @since 2.0.0
     */
    private static function validate_request_data($site_url) {
        $errors = array();

        // Проверяем обязательность поля URL сайта
        if (empty($site_url)) {
            $errors[] = __('Site URL is required', 'postwall');
        }

        // Проверяем формат URL
        if (!empty($site_url) && !filter_var($site_url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Invalid site URL format', 'postwall');
        }

        return $errors;
    }
}

// Инициализация AJAX обработчиков
PostWall_Ajax_Handler::init();