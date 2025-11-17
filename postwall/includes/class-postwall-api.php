<?php
/**
 * API обработчик для плагина Post Wall
 *
 * Обрабатывает запросы к WordPress REST API для получения данных о постах.
 *
 * @package PostWall
 * @since 2.0.0
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PostWall_API')) {

    /**
     * Класс для обработки запросов к WordPress REST API
     *
     * Этот класс обрабатывает все взаимодействия с WordPress REST API для плагина Post Wall,
     * включая получение данных, кеширование и обработку ошибок.
     *
     * @package PostWall
     * @since 2.0.0
     */
    class PostWall_API {

        /**
         * Префикс ключа кэша для временных данных
         *
         * @var string
         * @since 2.0.0
         */
        private static $cache_key_prefix = 'postwall_data_';

        /**
         * Время жизни кэша в секундах (1 час)
         *
         * @var int
         * @since 2.0.0
         */
        private static $cache_expiration = 3600;

        /**
         * Получить заголовки для API запросов
         *
         * @return array Массив заголовков для API запросов
         * @since 2.0.0
         */
        private static function get_api_headers() {
            return array(
                'User-Agent' => 'Post-Wall-WordPress-Plugin',
                'Accept' => 'application/json'
            );
        }

        /**
         * Обработать ошибки API
         *
         * @param string $error_message Сообщение об ошибке
         * @return WP_Error Объект ошибки WordPress
         * @since 2.0.0
         */
        private static function handle_api_error($error_message) {
            return new WP_Error('postwall_api_error', 'Ошибка API: ' . $error_message);
        }

        /**
         * Получить посты с WordPress сайта через REST API
         *
         * @param string $site_url URL сайта для получения постов
         * @return array|WP_Error Массив постов или WP_Error при ошибке
         * @since 2.0.0
         */
        public static function get_posts_from_site($site_url) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'posts_' . md5($site_url);
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            // Вычисляем даты для последних 12 месяцев
            $end_date = new DateTime();
            $start_date = new DateTime();
            $start_date->modify('-12 months');

            // Формируем URL REST API
            $api_url = rtrim($site_url, '/') . '/wp-json/wp/v2/posts';
            $params = array(
                'per_page' => 100,
                'after' => $start_date->format('Y-m-d\TH:i:s'),
                'before' => $end_date->format('Y-m-d\TH:i:s'),
                '_embed' => 'false'
            );

            $request_url = add_query_arg($params, $api_url);

            // Выполняем запрос если функция доступна
            if (function_exists('wp_remote_get')) {
                $response = wp_remote_get($request_url, array(
                    'headers' => self::get_api_headers(),
                    'timeout' => 30
                ));

                if (is_wp_error($response)) {
                    return $response;
                }

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (wp_remote_retrieve_response_code($response) !== 200) {
                    $error_message = isset($data['message']) ? $data['message'] : 'Неизвестная ошибка API';
                    return self::handle_api_error($error_message);
                }

                // Проверяем, что получили массив
                if (!is_array($data)) {
                    return self::handle_api_error('Некорректный ответ от API');
                }

                // Кэшируем результат если функция доступна
                if (function_exists('set_transient')) {
                    $cache_key = self::$cache_key_prefix . 'posts_' . md5($site_url);
                    set_transient($cache_key, $data, self::$cache_expiration);
                }

                return $data;
            } else {
                // Если WordPress функции недоступны, возвращаем пустой массив
                return array();
            }
        }

        /**
         * Получить статистику постов по дням за последние 12 месяцев
         *
         * @param string $site_url URL сайта для получения статистики
         * @return array|WP_Error Массив статистики постов или WP_Error при ошибке
         * @since 2.0.0
         */
        public static function get_post_stats($site_url) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'stats_' . md5($site_url);
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            $posts = self::get_posts_from_site($site_url);

            // Проверяем ошибки
            if (function_exists('is_wp_error') && is_wp_error($posts)) {
                return $posts;
            }

            // Проверяем, является ли результат массивом с ошибкой
            if (is_array($posts) && isset($posts['error'])) {
                return self::handle_api_error($posts['error']);
            }

            // Создаем массив для статистики по дням за последние 12 месяцев
            $stats = array();
            $end_date = new DateTime();
            $start_date = new DateTime();
            $start_date->modify('-12 months');

            // Инициализируем все дни за период
            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                $stats[$current_date->format('Y-m-d')] = 0;
                $current_date->modify('+1 day');
            }

            // Подсчитываем посты по дням
            foreach ($posts as $post) {
                if (isset($post['date'])) {
                    $post_date = new DateTime($post['date']);
                    $post_date_str = $post_date->format('Y-m-d');

                    // Проверяем, что дата в диапазоне последних 12 месяцев
                    if ($post_date >= $start_date && $post_date <= $end_date) {
                        if (isset($stats[$post_date_str])) {
                            $stats[$post_date_str]++;
                        }
                    }
                }
            }

            // Кэшируем результат если функция доступна
            if (function_exists('set_transient')) {
                $cache_key = self::$cache_key_prefix . 'stats_' . md5($site_url);
                set_transient($cache_key, $stats, self::$cache_expiration);
            }

            return $stats;
        }

        /**
         * Проверить, доступен ли WordPress REST API на сайте
         *
         * @param string $site_url URL сайта для проверки
         * @return bool True если API доступен, false в противном случае
         * @since 2.0.0
         */
        public static function check_api_availability($site_url) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'api_check_' . md5($site_url);
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            // Выполняем запрос если функция доступна
            if (function_exists('wp_remote_get')) {
                $api_url = rtrim($site_url, '/') . '/wp-json/wp/v2/posts?per_page=1';
                $response = wp_remote_get($api_url, array(
                    'headers' => self::get_api_headers(),
                    'timeout' => 10
                ));

                if (is_wp_error($response)) {
                    $available = false;
                } else {
                    $status_code = wp_remote_retrieve_response_code($response);
                    $available = ($status_code === 200);
                }

                // Кэшируем результат на короткое время (5 минут) если функция доступна
                if (function_exists('set_transient')) {
                    $cache_key = self::$cache_key_prefix . 'api_check_' . md5($site_url);
                    set_transient($cache_key, $available, 300);
                }

                return $available;
            } else {
                // Если WordPress функции недоступны, возвращаем false
                return false;
            }
        }

        /**
         * Очистить кэш для сайта
         *
         * @param string $site_url URL сайта для очистки кэша
         * @since 2.0.0
         */
        public static function clear_cache($site_url) {
            // Удаляем кэш если функция доступна
            if (function_exists('delete_transient')) {
                $cache_key_prefix = self::$cache_key_prefix . md5($site_url);
                delete_transient($cache_key_prefix . '_posts');
                delete_transient($cache_key_prefix . '_stats');
                delete_transient($cache_key_prefix . '_api_check');
            }
        }
    }
}

// class_exists check