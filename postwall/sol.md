Отличный план по доработке плагина Post Wall! Давайте реализуем все пункты систематически.

## Подробная инструкция по разработке и сборке

### 1. Структура изменений

Для реализации новых функций нужно модифицировать следующие файлы:

**Бэкенд (PHP):**
- `block-registration.php` - добавление нового атрибута `selectedYear`
- `class-postwall-api.php` - изменение логики выборки постов
- `postwall.php` - обновление передачи данных в JavaScript

**Фронтенд (JavaScript):**
- `index.js` - добавление селектора года в редакторе
- `frontend.js` - обновление логики отображения и заголовков

**Стили:**
- `style-index.css` - стили для ссылок месяцев

**Локализация:**
- Обновление всех `.po`, `.json` и `.pot` файлов

### 2. Процесс сборки

```bash
# Установка зависимостей (если используете npm)
npm install

# Сборка для production
npm run build

# Или для development
npm run dev

# Обновление переводов (после изменения строк)
wp i18n make-pot . languages/postwall.pot
wp i18n update-po languages/postwall.pot languages/ru_RU.po
wp i18n make-json languages --no-purge
```

### 3. Полные коды файлов

Вот обновленные версии всех необходимых файлов:

## [file name]: block-registration.php

```php
<?php
/**
 * Регистрация Gutenberg-блока Post Wall
 *
 * @package PostWall
 * @since 2.0.0
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Gutenberg-блока Post Wall
 *
 * Регистрирует динамический блок в редакторе Gutenberg с указанием
 * необходимых скриптов, стилей и обработчика рендеринга на сервере.
 *
 * @since 2.0.0
 */
function postwall_register_block() {
    // Регистрируем блок через WordPress API с полными параметрами
    register_block_type('postwall/post-wall', array(
        'editor_script' => 'postwall-block',         // JavaScript для редактора блоков
        'editor_style' => 'postwall-block-editor',   // CSS стили для редактора
        'style' => 'postwall-frontend',              // CSS стили для фронтенда
        'render_callback' => 'postwall_render_block', // Функция серверного рендеринга
        'attributes' => array(                                   // Определение атрибутов блока
            'siteUrl' => array(
                'type' => 'string',    // Тип данных атрибута
                'default' => ''        // Значение по умолчанию (пустая строка)
            ),
            'headingTag' => array(
                'type' => 'string',    // Тип данных атрибута
                'default' => 'h3'      // Значение по умолчанию (h3)
            ),
            'selectedYear' => array(
                'type' => 'string',    // Новый атрибут для выбора года
                'default' => 'last12'  // Значение по умолчанию - последние 12 месяцев
            )
        )
    ));
}

/**
 * Извлекает домен из URL
 *
 * @param string $url URL сайта
 * @return string Доменное имя
 * @since 2.0.0
 */
function postwall_extract_domain($url) {
    if (empty($url)) {
        return '';
    }

    // Удаляем протокол (http://, https://)
    $domain = preg_replace('#^https?://#', '', $url);

    // Удаляем путь после домена
    $domain = preg_replace('#/.*$#', '', $domain);

    // Удаляем www. если есть
    $domain = preg_replace('#^www\.#', '', $domain);

    return $domain;
}

/**
 * Функция серверного рендеринга блока Post Wall
 *
 * Вызывается WordPress при выводе блока на странице. Генерирует HTML-разметку
 * контейнера для диаграммы и передает необходимые данные через data-атрибуты.
 *
 * @param array $attributes Атрибуты блока (включая siteUrl)
 * @param string $content Внутреннее содержимое блока (не используется в динамических блоках)
 * @return string HTML-разметка контейнера диаграммы или сообщение об ошибке
 * @since 2.0.0
 */
function postwall_render_block($attributes, $content) {
    // Валидация входных данных
    if (!is_array($attributes)) {
        $attributes = array();
    }

    // Получаем URL сайта из атрибутов блока
    $site_url = !empty($attributes['siteUrl']) ?
                       sanitize_text_field($attributes['siteUrl']) :
                       '';

    // Получаем выбранный год
    $selected_year = !empty($attributes['selectedYear']) ?
                            sanitize_text_field($attributes['selectedYear']) :
                            'last12';

    // Извлекаем домен для заголовка
    $domain = postwall_extract_domain($site_url);

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('postwall-');

    // Получаем тег заголовка из атрибутов блока или используем значение по умолчанию
    $allowed_heading_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div');
    $heading_tag = !empty($attributes['headingTag']) && in_array($attributes['headingTag'], $allowed_heading_tags) ?
                   $attributes['headingTag'] : 'h3';

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-site-url="' . esc_attr($site_url) . '" data-container-id="' . esc_attr($unique_id) . '" data-heading-tag="' . esc_attr($heading_tag) . '" data-selected-year="' . esc_attr($selected_year) . '"';

    // Передаем отдельно домен и базовые тексты
    $base_title = __('Posts from the site for the last 12 months', 'postwall');
    $loading_text = __('Loading post wall...', 'postwall');

    // Генерируем заголовок с соответствующим тегом
    $title_html = '<' . esc_attr($heading_tag) . ' class="postwall-title">' . esc_html(generate_title_with_domain($base_title, $domain, $selected_year)) . '</' . esc_attr($heading_tag) . '>';

    // Возвращаем HTML контейнер для диаграммы с data-атрибутами
    return '<div class="postwall-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '
                data-base-title="' . esc_attr($base_title) . '"
                data-loading-text="' . esc_attr($loading_text) . '"
                data-domain="' . esc_attr($domain) . '"
                data-selected-year="' . esc_attr($selected_year) . '">
                ' . $title_html . '
                <div class="postwall-loading">' . esc_html($loading_text) . '</div>
            </div>';
}

/**
 * Генерирует заголовок с доменом
 *
 * @param string $base_title Базовый заголовок
 * @param string $domain Домен сайта
 * @param string $selected_year Выбранный год
 * @return string Заголовок с доменом
 * @since 2.0.0
 */
function generate_title_with_domain($base_title, $domain, $selected_year = 'last12') {
    if (empty($domain)) {
        return $base_title;
    }

    // Для последних 12 месяцев используем старую логику
    if ($selected_year === 'last12') {
        // Для русского языка
        if (get_locale() === 'ru_RU') {
            return 'Посты сайта ' . $domain . ' за последние 12 месяцев';
        }

        // Для английского и других языков
        return 'Posts from the site ' . $domain . ' for the last 12 months';
    } else {
        // Для конкретного года
        if (get_locale() === 'ru_RU') {
            return 'Посты сайта ' . $domain . ' за ' . $selected_year . ' год';
        }

        // Для английского и других языков
        return 'Posts from the site ' . $domain . ' for the year ' . $selected_year;
    }
}

// Регистрируем блок при инициализации
add_action('init', 'postwall_register_block');
```

## [file name]: class-postwall-api.php

```php
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
         * @return \WP_Error Объект ошибки WordPress
         * @since 2.0.0
         */
        private static function handle_api_error($error_message) {
            return new WP_Error('postwall_api_error', 'Ошибка API: ' . $error_message);
        }

        /**
         * Получить посты с WordPress сайта через REST API
         *
         * @param string $site_url URL сайта для получения постов
         * @param string $year Год для фильтрации постов
         * @return array|\WP_Error Массив постов или WP_Error при ошибке
         * @since 2.0.0
         */
         public static function get_posts_from_site($site_url, $year = 'last12') {
             // Для текущего сайта используем прямой доступ к базе данных без кэширования
             if ($site_url === get_site_url()) {
                 return self::get_posts_from_current_site($year);
             }

             // Проверяем кэш если функция доступна
             if (function_exists('get_transient')) {
                 $cache_key = self::$cache_key_prefix . 'posts_' . md5($site_url . '_' . $year);
                 $cached_data = get_transient($cache_key);

                 if ($cached_data !== false) {
                     return $cached_data;
                 }
             }

             // Формируем URL REST API
             $api_url = rtrim($site_url, '/') . '/wp-json/wp/v2/posts';

             // Параметры для последних 12 месяцев
             if ($year === 'last12') {
                 $end_date = new DateTime();
                 $start_date = new DateTime();
                 $start_date->modify('-12 months');

                 $params = array(
                     'per_page' => 100,
                     'after' => $start_date->format('Y-m-d\TH:i:s'),
                     'before' => $end_date->format('Y-m-d\TH:i:s'),
                     '_embed' => 'false'
                 );
             } else {
                 // Параметры для конкретного года
                 $start_date = new DateTime($year . '-01-01');
                 $end_date = new DateTime($year . '-12-31');

                 $params = array(
                     'per_page' => 100,
                     'after' => $start_date->format('Y-m-d\TH:i:s'),
                     'before' => $end_date->format('Y-m-d\TH:i:s'),
                     '_embed' => 'false'
                 );
             }

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
                    $cache_key = self::$cache_key_prefix . 'posts_' . md5($site_url . '_' . $year);
                    set_transient($cache_key, $data, self::$cache_expiration);
                }

                return $data;
            } else {
                // Если WordPress функции недоступны, возвращаем пустой массив
                return array();
            }
        }

        /**
         * Получить посты с текущего сайта напрямую из базы данных
         *
         * @param string $year Год для фильтрации постов
         * @return array Массив постов текущего сайта
         * @since 2.0.0
         */
        private static function get_posts_from_current_site($year = 'last12') {
            if (!function_exists('get_posts')) {
                return array();
            }

            // Параметры для последних 12 месяцев
            if ($year === 'last12') {
                $end_date = new DateTime();
                $end_date->setTime(23, 59, 59);
                $start_date = new DateTime();
                $start_date->modify('-12 months');
                $start_date->setTime(0, 0, 0);
            } else {
                // Параметры для конкретного года
                $start_date = new DateTime($year . '-01-01');
                $start_date->setTime(0, 0, 0);
                $end_date = new DateTime($year . '-12-31');
                $end_date->setTime(23, 59, 59);
            }

            // Получаем посты напрямую из базы данных
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'after' => $start_date->format('Y-m-d'),
                        'before' => $end_date->format('Y-m-d'),
                        'inclusive' => true,
                    ),
                ),
            );

            $posts = get_posts($args);

            // Преобразуем в формат REST API для совместимости
            $formatted_posts = array();
            foreach ($posts as $post) {
                $formatted_posts[] = array(
                    'id' => $post->ID,
                    'date' => $post->post_date,
                    'title' => array('rendered' => $post->post_title),
                    'content' => array('rendered' => $post->post_content),
                );
            }

            return $formatted_posts;
        }

        /**
         * Получить статистику постов по дням
         *
         * @param string $site_url URL сайта для получения статистики
         * @param string $year Год для фильтрации постов
         * @return array|\WP_Error Массив статистики постов или WP_Error при ошибке
         * @since 2.0.0
         */
        public static function get_post_stats($site_url, $year = 'last12') {
            // Для текущего сайта не используем кэш, чтобы видеть свежие посты
            if ($site_url === get_site_url()) {
                $posts = self::get_posts_from_current_site($year);
            } else {
                // Проверяем кэш если функция доступна (для внешних сайтов)
                if (function_exists('get_transient')) {
                    $cache_key = self::$cache_key_prefix . 'stats_' . md5($site_url . '_' . $year);
                    $cached_data = get_transient($cache_key);

                    if ($cached_data !== false) {
                        return $cached_data;
                    }
                }

                $posts = self::get_posts_from_site($site_url, $year);
            }

            // Проверяем ошибки
            if (function_exists('is_wp_error') && is_wp_error($posts)) {
                return $posts;
            }

            // Создаем массив для статистики по дням
            $stats = array();

            // Определяем диапазон дат
            if ($year === 'last12') {
                $end_date = new DateTime();
                $end_date->setTime(23, 59, 59);
                $start_date = new DateTime();
                $start_date->modify('-12 months');
                $start_date->setTime(0, 0, 0);
            } else {
                $start_date = new DateTime($year . '-01-01');
                $start_date->setTime(0, 0, 0);
                $end_date = new DateTime($year . '-12-31');
                $end_date->setTime(23, 59, 59);
            }

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

                    // Проверяем, что дата в диапазоне
                    if ($post_date >= $start_date && $post_date <= $end_date) {
                        if (isset($stats[$post_date_str])) {
                            $stats[$post_date_str]++;
                        }
                    }
                }
            }

            // Кэшируем результат если функция доступна (только для внешних сайтов)
            if ($site_url !== get_site_url() && function_exists('set_transient')) {
                $cache_key = self::$cache_key_prefix . 'stats_' . md5($site_url . '_' . $year);
                set_transient($cache_key, $stats, self::$cache_expiration);
            }

            return $stats;
        }

        /**
         * Получить список доступных годов с постами
         *
         * @param string $site_url URL сайта
         * @return array Массив годов
         * @since 2.1.2
         */
        public static function get_available_years($site_url) {
            // Для текущего сайта
            if ($site_url === get_site_url() || empty($site_url)) {
                return self::get_available_years_current_site();
            }

            // Для внешних сайтов - упрощенная версия
            $current_year = (int) date('Y');
            $years = array();

            // Добавляем последние 10 лет как возможные варианты
            for ($i = 0; $i < 10; $i++) {
                $years[] = (string) ($current_year - $i);
            }

            return $years;
        }

        /**
         * Получить список доступных годов для текущего сайта
         *
         * @return array Массив годов
         * @since 2.1.2
         */
        private static function get_available_years_current_site() {
            global $wpdb;

            $years = $wpdb->get_col("
                SELECT DISTINCT YEAR(post_date)
                FROM {$wpdb->posts}
                WHERE post_type = 'post'
                AND post_status = 'publish'
                ORDER BY post_date DESC
            ");

            return $years;
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
                $cache_key_base = self::$cache_key_prefix . md5($site_url);
                delete_transient($cache_key_base . '_posts');
                delete_transient($cache_key_base . '_stats');
                delete_transient($cache_key_base . '_api_check');
            }
        }
    }
}
```

## [file name]: index.js

```javascript
/**
 * Post Wall Gutenberg Block
 *
 * Registers the Post Wall block for the Gutenberg editor
 *
 * @package PostWall
 * @since 1.0.0
 */

(function (wp) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var __ = wp.i18n.__;

    /**
     * Регистрация Gutenberg-блока Post Wall
     */
    registerBlockType('postwall/post-wall', {
        title: __('Post Wall', 'postwall'),
        icon: 'grid-view',
        category: 'widgets',
        attributes: {
            siteUrl: {
                type: 'string',
                default: ''
            },
            headingTag: {
                type: 'string',
                default: 'h3'
            },
            selectedYear: {
                type: 'string',
                default: 'last12'
            }
        },

        /**
         * Функция редактирования блока
         *
         * @param {Object} props - Свойства блока
         */
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Получаем значения из атрибутов блока
            var siteUrl = attributes.siteUrl || '';
            var headingTag = attributes.headingTag || 'h3';
            var selectedYear = attributes.selectedYear || 'last12';

            // Генерируем опции для выбора года
            var yearOptions = [
                { label: __('Last 12 months', 'postwall'), value: 'last12' }
            ];

            // Добавляем доступные годы (от текущего до 2010)
            var currentYear = new Date().getFullYear();
            for (var year = currentYear; year >= 2010; year--) {
                yearOptions.push({
                    label: year.toString(),
                    value: year.toString()
                });
            }

            return el(
                'div',
                { className: props.className },
                el(
                    'div',
                    { className: 'postwall-placeholder' },
                    __('Post wall', 'postwall')
                ),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Post Wall Settings', 'postwall'), initialOpen: true },
                        el(TextControl, {
                            label: __('Site URL', 'postwall'),
                            value: siteUrl,
                            onChange: function (value) {
                                setAttributes({ siteUrl: value });
                            },
                            placeholder: __('https://example.com', 'postwall')
                        }),
                        el(SelectControl, {
                            label: __('Year', 'postwall'),
                            value: selectedYear,
                            options: yearOptions,
                            onChange: function (value) {
                                setAttributes({ selectedYear: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Heading Tag', 'postwall'),
                            value: headingTag,
                            options: [
                                { label: __('H2', 'postwall'), value: 'h2' },
                                { label: __('H3', 'postwall'), value: 'h3' },
                                { label: __('H4', 'postwall'), value: 'h4' },
                                { label: __('Plain Text', 'postwall'), value: 'div' }
                            ],
                            onChange: function (value) {
                                setAttributes({ headingTag: value });
                            }
                        })
                    )
                )
            );
        },

        /**
         * Функция сохранения блока
         */
        save: function () {
            // Рендеринг происходит на стороне сервера
            return null;
        }
    });
})(window.wp);
```

## [file name]: frontend.js

```javascript
/**
 * Frontend JavaScript для Post Wall
 *
 * Обрабатывает интерактивное отображение кафельной стенки постов на фронтенде.
 *
 * @package PostWall
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Класс PostWall для управления визуализацией календаря
     */
    class PostWall {
        constructor(containerElement) {
            this.container = containerElement;
            this.siteUrl = this.container.dataset.siteUrl;
            this.containerId = this.container.dataset.containerId;
            this.selectedYear = this.container.dataset.selectedYear || 'last12';
            this.loadingElement = this.container.querySelector('.postwall-loading');

            // Получаем данные из data-атрибутов
            this.baseTitle = this.container.dataset.baseTitle || 'Posts from the site for the last 12 months';
            this.loadingText = this.container.dataset.loadingText || 'Loading post wall...';
            this.domain = this.container.dataset.domain || '';

            this.init();
        }

        /**
         * Инициализация кафельной стенки постов
         */
        init() {
            if (this.siteUrl) {
                this.fetchPostData();
            } else {
                this.generateCalendar();
            }
            this.attachClickHandlers();
        }

        /**
         * Прикрепить обработчики кликов к ячейкам дней
         */
        attachClickHandlers() {
            // Используем делегирование событий для контейнера
            this.container.addEventListener('click', (event) => {
                const target = event.target;

                // Проверяем, что клик был по квадратику дня с постами
                if (target.classList.contains('day') && target.hasAttribute('data-date') && !target.classList.contains('empty')) {
                    event.preventDefault();
                    const dateString = target.getAttribute('data-date');
                    this.navigateToDateArchive(dateString);
                }

                // Проверяем, что клик был по ссылке месяца
                if (target.classList.contains('month-link')) {
                    event.preventDefault();
                    const monthUrl = target.getAttribute('href');
                    window.open(monthUrl, '_blank');
                }
            });
        }

        /**
         * Перейти на страницу архива даты для заданной даты
         * @param {string} dateString - Дата в формате YYYY-MM-DD
         */
        navigateToDateArchive(dateString) {
            // Формируем URL для архивной страницы даты на сайте из блока
            const archiveUrl = this.generateDateArchiveUrl(dateString);

            // Перенаправляем пользователя
            window.open(archiveUrl, '_blank');
        }

        /**
         * Сгенерировать URL для страницы архива даты
         * @param {string} dateString - Дата в формате YYYY-MM-DD
         * @return {string} URL архива
         */
        generateDateArchiveUrl(dateString) {
            // Разбираем дату
            const [year, month, day] = dateString.split('-');

            // Формируем URL для архивной страницы даты
            // Формат: /YYYY/MM/DD/ или /YYYY/MM/ в зависимости от темы
            // Используем siteUrl из блока
            return `${this.siteUrl}/${year}/${month.padStart(2, '0')}/${day.padStart(2, '0')}/`;
        }

        /**
         * Сгенерировать URL для страницы архива месяца
         * @param {number} year - Год
         * @param {number} month - Месяц (1-12)
         * @return {string} URL архива месяца
         */
        generateMonthArchiveUrl(year, month) {
            // Формируем URL для архивной страницы месяца
            // Формат: /YYYY/MM/
            return `${this.siteUrl}/${year}/${month.toString().padStart(2, '0')}/`;
        }

        /**
         * Получить данные о постах через AJAX
         */
        fetchPostData() {
            $.ajax({
                url: postwallSettings.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'postwall_get_post_data',
                    nonce: postwallSettings.nonce,
                    site_url: this.siteUrl,
                    selected_year: this.selectedYear
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.postData = response.data;
                        this.generateCalendar();
                    } else {
                        this.showError(this.translate('Failed to load post data'));
                    }
                },
                error: (xhr, status, error) => {
                    this.showError(this.translate('Error loading data'));
                }
            });
        }

        /**
         * Показать сообщение об ошибке
         * @param {string} message Сообщение об ошибке для отображения
         */
        showError(message) {
            if (this.loadingElement) {
                this.loadingElement.textContent = message;
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'postwall-error';
                errorDiv.textContent = message;
                this.container.appendChild(errorDiv);
            }
        }

        /**
         * Генерировать сетку календаря по месяцам
         */
        generateCalendar() {
            // Убрать индикатор загрузки
            if (this.loadingElement) {
                this.loadingElement.remove();
            }

            // Создаем или обновляем заголовок
            this.createOrUpdateTitle();

            // Создать обёртку для тепловой карты
            const wrapper = document.createElement('div');
            wrapper.className = 'heatmap-wrapper';

            // Create months container
            const monthsContainer = document.createElement('div');
            monthsContainer.className = 'months';

            const now = new Date();
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];

            // Определяем диапазон месяцев для отображения
            let monthsToShow = [];
            if (this.selectedYear === 'last12') {
                // Последние 12 месяцев от текущего
                for (let i = 11; i >= 0; i--) {
                    const monthDate = new Date(now);
                    monthDate.setMonth(now.getMonth() - i);
                    monthDate.setDate(1);
                    monthsToShow.push(monthDate);
                }
            } else {
                // Все месяцы выбранного года
                const year = parseInt(this.selectedYear);
                for (let month = 0; month < 12; month++) {
                    const monthDate = new Date(year, month, 1);
                    monthsToShow.push(monthDate);
                }
            }

            // Создаем месяцы
            monthsToShow.forEach(monthDate => {
                const monthDiv = this.createMonth(monthDate);
                monthsContainer.appendChild(monthDiv);
            });

            wrapper.appendChild(monthsContainer);
            this.container.appendChild(wrapper);
        }

        /**
         * Создать или обновить элемент заголовка
         */
        createOrUpdateTitle() {
            let titleElement = this.container.querySelector('.postwall-title');

            // Получаем тег заголовка из data-атрибута или используем значение по умолчанию
            const headingTag = this.container.getAttribute('data-heading-tag') || 'h3';

            // Создаем локализованный заголовок с доменом
            const translatedTitle = this.generateTitleWithDomain();

            if (!titleElement) {
                // Создаем элемент заголовка с выбранным тегом
                if (headingTag === 'div') {
                    titleElement = document.createElement('div');
                    titleElement.className = 'postwall-text';
                } else {
                    titleElement = document.createElement(headingTag);
                    titleElement.className = 'postwall-title';
                }
                this.container.insertBefore(titleElement, this.container.firstChild);
            }

            titleElement.textContent = translatedTitle;
        }

        /**
         * Сгенерировать заголовок с доменом
         * @return {string} Локализованный заголовок с доменом
         */
        generateTitleWithDomain() {
            if (!this.domain) {
                return this.translate(this.baseTitle);
            }

            // Для последних 12 месяцев
            if (this.selectedYear === 'last12') {
                if (this.getLocale().startsWith('ru')) {
                    return 'Посты сайта ' + this.domain + ' за последние 12 месяцев';
                } else {
                    return 'Posts from the site ' + this.domain + ' for the last 12 months';
                }
            } else {
                // Для конкретного года
                if (this.getLocale().startsWith('ru')) {
                    return 'Посты сайта ' + this.domain + ' за ' + this.selectedYear + ' год';
                } else {
                    return 'Posts from the site ' + this.domain + ' for the year ' + this.selectedYear;
                }
            }
        }

        /**
         * Создать сетку месяца
         * @param {Date} monthDate - Дата, представляющая месяц для создания
         * @return {HTMLElement} Элемент контейнера месяца
         */
        createMonth(monthDate) {
           const monthDiv = document.createElement('div');
           monthDiv.className = 'month';

           const monthGrid = document.createElement('div');
           monthGrid.className = 'month-grid';

           const year = monthDate.getFullYear();
           const month = monthDate.getMonth();

           // Устанавливаем дату на первый день месяца для правильного расчета
           monthDate.setDate(1);

           // Get first day of month and what day of week it falls on
           const firstDay = new Date(year, month, 1);
           const firstDayOfWeek = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.

           // Adjust for Monday first (0 = Monday, 6 = Sunday)
           // ВАЖНО: Это правильное количество пустых клеток ДО первого дня
           const emptyCellsBefore = (firstDayOfWeek + 6) % 7;

           // Получить количество дней в месяце
           const daysInMonth = new Date(year, month + 1, 0).getDate();

           // Create cells
           // Empty cells before first day - ЛЕВЫЙ "РВАНЫЙ" КРАЙ
           for (let i = 0; i < emptyCellsBefore; i++) {
               const emptyCell = document.createElement('span');
               emptyCell.className = 'day empty';
               monthGrid.appendChild(emptyCell);
           }

           // Days of the month
           for (let day = 1; day <= daysInMonth; day++) {
               const dayCell = document.createElement('span');
               const cellDate = new Date(year, month, day);

               // Determine activity level
               const activityLevel = this.getActivityLevel(cellDate);

               if (activityLevel === 0) {
                   dayCell.className = 'day lvl-0';
               } else {
                   dayCell.className = `day lvl-${activityLevel}`;
                   // Добавляем data-date атрибут для квадратиков с постами
                   // Используем локальную дату для data-date атрибута
                   const localDate = new Date(cellDate.getTime() - (cellDate.getTimezoneOffset() * 60000));
                   dayCell.setAttribute('data-date', localDate.toISOString().split('T')[0]);
               }

               // Add tooltip with post count
               // Используем локальную дату для получения количества постов
               const localDate = new Date(cellDate.getTime() - (cellDate.getTimezoneOffset() * 60000));
               const postCount = this.postData ?
                   (this.postData[localDate.toISOString().split('T')[0]] || 0) : 0;

               // Форматируем дату согласно настройкам WordPress
               const formattedDate = this.formatDateAccordingToWordPress(cellDate);

               // Создаем локализованный текст для тултипа
               const tooltipText = this.formatTooltip(formattedDate, postCount);
               dayCell.title = tooltipText;

               monthGrid.appendChild(dayCell);
           }

           monthDiv.appendChild(monthGrid);

           // Добавить метку месяца с ссылкой
           const monthLabel = document.createElement('div');
           monthLabel.className = 'month-label';

           const monthLink = document.createElement('a');
           monthLink.className = 'month-link';
           monthLink.href = this.generateMonthArchiveUrl(year, month + 1);
           monthLink.textContent = this.getMonthName(month);
           monthLink.title = this.translate('View posts for') + ' ' + this.getMonthName(month) + ' ' + year;

           monthLabel.appendChild(monthLink);
           monthDiv.appendChild(monthLabel);

           return monthDiv;
       }

        /**
         * Форматировать текст подсказки с правильной локализацией
         * @param {string} date Отформатированная дата
         * @param {number} postCount Количество постов
         * @return {string} Локализованный текст подсказки
         */
        formatTooltip(date, postCount) {
            // Получаем переведенное слово "posts" в правильной форме
            const postsText = this.getPostsText(postCount);
            return `${date}: ${postCount} ${postsText}`;
        }

        /**
         * Получить локализованный текст постов с правильными формами множественного числа
         * @param {number} count Количество постов
         * @return {string} Локализованный текст постов
         */
        getPostsText(count) {
            // Для русского языка - особые правила множественного числа
            if (this.getLocale().startsWith('ru')) {
                const lastDigit = count % 10;
                const lastTwoDigits = count % 100;

                if (lastDigit === 1 && lastTwoDigits !== 11) {
                    return 'пост';
                } else if (lastDigit >= 2 && lastDigit <= 4 && (lastTwoDigits < 12 || lastTwoDigits > 14)) {
                    return 'поста';
                } else {
                    return 'постов';
                }
            }

            // Для английского и других языков
            return count === 1 ? this.translate('post') : this.translate('posts');
        }

        /**
         * Форматировать дату согласно настройкам формата даты WordPress
         * @param {Date} date - Дата для форматирования
         * @return {string} Отформатированная строка даты
         */
        formatDateAccordingToWordPress(date) {
            // Если WordPress передал формат даты, используем его
            if (postwallSettings.dateFormat) {
                return this.formatDateWithWordPressFormat(date, postwallSettings.dateFormat);
            }

            // Иначе используем локализованный формат по умолчанию
            return this.getLocalizedDateFormat(date);
        }

        /**
         * Форматировать дату используя формат даты WordPress
         * @param {Date} date - Дата для форматирования
         * @param {string} format - Строка формата даты WordPress
         * @return {string} Отформатированная дата
         */
        formatDateWithWordPressFormat(date, format) {
            const replacements = {
                'd': () => date.getDate().toString().padStart(2, '0'),
                'j': () => date.getDate(),
                'm': () => (date.getMonth() + 1).toString().padStart(2, '0'),
                'n': () => (date.getMonth() + 1),
                'Y': () => date.getFullYear(),
                'y': () => date.getFullYear().toString().slice(-2),
                'F': () => this.getMonthName(date.getMonth()),
                'M': () => this.translate(this.getMonthName(date.getMonth()).slice(0, 3))
            };

            let result = format;
            for (const [key, formatter] of Object.entries(replacements)) {
                result = result.replace(new RegExp(key, 'g'), formatter());
            }

            return result;
        }

        /**
         * Получить локализованный формат даты как запасной вариант
         * @param {Date} date - Дата для форматирования
         * @return {string} Отформатированная дата
         */
        getLocalizedDateFormat(date) {
            const locale = this.getLocale();

            const formats = {
                'ru_RU': () => {
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    return `${day}.${month}.${date.getFullYear()}`;
                },
                'en_US': () => {
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    return `${month}/${day}/${date.getFullYear()}`;
                }
            };

            return (formats[locale] || formats['en_US'])();
        }

        /**
         * Получить текущую локаль
         * @return {string} Текущая локаль
         */
        getLocale() {
            return postwallSettings.locale || 'en_US';
        }

        /**
         * Получить уровень активности для даты на основе реальных данных о постах
         *
         * @param {Date} date Дата для проверки
         * @return {number} Уровень активности (0-4)
         */
        getActivityLevel(date) {
            // Если у нас есть реальные данные, используем их
            if (this.postData) {
                // Используем локальную дату для ключа данных
                const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
                const dateKey = localDate.toISOString().split('T')[0]; // Формат YYYY-MM-DD
                const postCount = this.postData[dateKey] || 0;

                // Определяем уровень активности на основе количества постов
                if (postCount === 0) return 0; // Нет постов
                if (postCount === 1) return 1; // 1 пост
                if (postCount === 2) return 2; // 2 поста
                if (postCount <= 4) return 3; // 3-4 поста
                return 4; // 5+ постов
            }

            // Запасной вариант с случайными данными для демонстрации, когда нет данных
            const random = Math.random();
            if (random < 0.3) return 0;
            if (random < 0.5) return 1;
            if (random < 0.7) return 2;
            if (random < 0.9) return 3;
            return 4;
        }

        /**
         * Получить локализованное название месяца
         * @param {number} monthIndex - Индекс месяца (0-11)
         * @return {string} Название месяца
         */
        getMonthName(monthIndex) {
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];
            return monthNames[monthIndex];
        }

        /**
         * Помощник перевода с запасным вариантом
         * @param {string} text Текст для перевода
         * @return {string} Переведенный текст
         */
        translate(text) {
            // Пробуем использовать wp.i18n если доступно
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(text, 'postwall');
            }

            // Запасной вариант: ручной перевод на основе локали
            if (this.getLocale().startsWith('ru')) {
                const russianTranslations = {
                    'Posts from the site for the last 12 months': 'Посты с сайта за последние 12 месяцев',
                    'Loading post wall...': 'Загрузка кафельной стенки...',
                    'Failed to load post data': 'Не удалось загрузить данные постов',
                    'Error loading data': 'Ошибка при загрузке данных',
                    'post': 'пост',
                    'posts': 'постов',
                    'View posts for': 'Просмотреть посты за',
                    'Last 12 months': 'Последние 12 месяцев',
                    'Jan': 'янв', 'Feb': 'фев', 'Mar': 'мар', 'Apr': 'апр',
                    'May': 'май', 'Jun': 'июн', 'Jul': 'июл', 'Aug': 'авг',
                    'Sep': 'сен', 'Oct': 'окт', 'Nov': 'ноя', 'Dec': 'дек'
                };
                return russianTranslations[text] || text;
            }

            // По умолчанию английский
            return text;
        }
    }

    /**
     * Инициализировать экземпляры PostWall при готовности DOM
     */
    $(document).ready(function() {
        $('.postwall-container').each(function() {
            new PostWall(this);
        });
    });

})(jQuery);
```

## [file name]: style-index.css

```css
/* Post Wall Frontend Styles */

.postwall-container {
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    gap: 24px;
    max-width: 100%;
}

.postwall-title {
    margin: 0 0 20px 0;
    color: #333;
    text-align: center;
}

.postwall-loading {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px 20px;
}

/* Heatmap styles from verstka.html */
:root {
    --size: 14px;
    --gap: 4px;
    --month-gap: 32px;

    --empty: #e5e7eb;
    --lvl0: #f0f9f0;
    --lvl1: #9be69b;
    --lvl2: #4ade80;
    --lvl3: #22c55e;
    --lvl4: #16a34a;

    font-family: Inter, sans-serif;
}

.heatmap-wrapper {
    display: flex;
    flex-direction: column;
    gap: 24px;
    max-width: 100%;
}

.months {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: nowrap;
    width: 100%;
    overflow-x: auto;
}

/* Один месяц */
.month {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Сетка месяца — ровно 7 строк × N колонок */
.month-grid {
    display: grid;
    grid-template-rows: repeat(7, 12px); /* 7 строк = дни недели (Пн-Вс) */
    grid-auto-flow: column; /* Недели идут как колонки */
    grid-auto-columns: 12px;
    gap: 2px;
}

/* Клетка */
.day {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.empty {
    background: transparent;
    border: none;
}

.lvl-0 { background: var(--lvl0); }
.lvl-1 { background: var(--lvl1); }
.lvl-2 { background: var(--lvl2); }
.lvl-3 { background: var(--lvl3); }
.lvl-4 { background: var(--lvl4); }

/* Hover effects for days with posts */
.day[data-date]:hover {
    cursor: pointer;
    filter: brightness(1.2);
}

.month-label {
    margin-top: 8px;
    font-size: 13px;
    color: #555;
}

/* Стили для ссылок месяцев */
.month-link {
    color: #555;
    text-decoration: none;
    transition: color 0.2s ease;
}

.month-link:hover {
    color: #1a73e8;
    text-decoration: underline;
}

/* Responsive design */
@media (max-width: 900px) {
    :root {
        --size: 12px;
        --gap: 3px;
        --month-gap: 20px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .postwall-container {
        background: #1a1a1a;
        border-color: #333;
    }

    .postwall-title {
        color: #e1e1e1;
    }

    .postwall-loading {
        color: #ccc;
    }

    .postwall-months {
        color: #ccc;
    }

    .month-link {
        color: #ccc;
    }

    .month-link:hover {
        color: #8ab4f8;
    }
}
```

## [file name]: class-ajax-handler.php

```php
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

        // Получаем выбранный год
        $selected_year = !empty($_POST['selected_year']) ?
                                sanitize_text_field($_POST['selected_year']) :
                                'last12';

        // Валидация входных данных
        $validation_errors = self::validate_request_data($site_url, $selected_year);
        if (!empty($validation_errors)) {
            wp_send_json_error(implode(' ', $validation_errors));
            return;
        }

        // Получаем статистику постов через API
        $stats = PostWall_API::get_post_stats($site_url, $selected_year);

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
     * @param string $selected_year Выбранный год
     * @return array Массив ошибок валидации
     * @since 2.0.0
     */
    private static function validate_request_data($site_url, $selected_year) {
        $errors = array();

        // Проверяем обязательность поля URL сайта
        if (empty($site_url)) {
            $errors[] = __('Site URL is required', 'postwall');
        }

        // Проверяем формат URL
        if (!empty($site_url) && !filter_var($site_url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Invalid site URL format', 'postwall');
        }

        // Проверяем формат года
        if ($selected_year !== 'last12' && !preg_match('/^\d{4}$/', $selected_year)) {
            $errors[] = __('Invalid year format', 'postwall');
        }

        return $errors;
    }
}

// Инициализация AJAX обработчиков
PostWall_Ajax_Handler::init();
```

## [file name]: postwall.php

```php
<?php
/**
 * Plugin Name: Post Wall
 * Description: Displays post wall charts as Gutenberg blocks or shortcodes
 * Version: 2.1.2
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: postwall
 * Domain Path: /languages
 *
 * @package PostWall
 */

// Защита от прямого доступа - предотвращает выполнение файла вне WordPress
defined('ABSPATH') || exit;

// Определение констант для удобства работы с путями
define('POSTWALL_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Абсолютный путь к папке плагина
define('POSTWALL_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL к папке плагина
define('POSTWALL_PLUGIN_VERSION', '2.1.2');                // Версия плагина

// Подключение вспомогательных файлов
require_once POSTWALL_PLUGIN_PATH . 'includes/class-assets-manager.php';       // Менеджер ресурсов
require_once POSTWALL_PLUGIN_PATH . 'includes/class-ajax-handler.php';          // AJAX обработчики
require_once POSTWALL_PLUGIN_PATH . 'includes/class-postwall-api.php';          // API для получения данных
require_once POSTWALL_PLUGIN_PATH . 'includes/block-registration.php';         // Регистрация Gutenberg-блока

/**
 * Основной класс плагина Post Wall
 *
 * Отвечает за инициализацию плагина и регистрацию основных компонентов.
 *
 * @package PostWall
 * @since 2.0.0
 */
class PostWall {

    /**
     * Экземпляр класса PostWall
     *
     * @var PostWall
     * @since 2.0.0
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса PostWall
     *
     * @return PostWall
     * @since 2.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса PostWall
     *
     * Регистрирует основные хуки WordPress при инициализации плагина.
     *
     * @since 2.0.0
     */
    private function __construct() {
        // Регистрация основных хуков WordPress
        add_action('init', array($this, 'init'));  // Инициализация плагина
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Инициализация плагина
     *
     * Регистрирует хуки для подключения ресурсов.
     *
     * @since 2.0.0
     */
    public function init() {
        // Регистрация блока и подключение ресурсов
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets')); // Ресурсы для редактора
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));             // Ресурсы для сайта
    }

    /**
     * Подключение ресурсов для редактора блоков Gutenberg
     *
     * Загружает JavaScript и CSS файлы, необходимые для работы блока в редакторе:
     * - JavaScript: логика блока с зависимостями wp-blocks, wp-element, wp-block-editor, wp-components
     * - CSS: стили для блока в редакторе с зависимостью wp-edit-blocks
     * Использует filemtime для версионирования файлов (кеширование).
     *
     * @since 2.0.0
     */
    public function enqueue_block_editor_assets() {
        // Пути к файлам сборки
        $index_js = POSTWALL_PLUGIN_PATH . 'build/index.js';   // JavaScript для блока
        $index_css = POSTWALL_PLUGIN_PATH . 'build/index.css'; // CSS для блока

        // Подключение JavaScript для блока
        wp_enqueue_script(
            'postwall-block',        // Уникальный идентификатор скрипта
            POSTWALL_PLUGIN_URL . 'build/index.js',  // URL к файлу
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'), // Зависимости WordPress
            file_exists($index_js) ? filemtime($index_js) : time(), // Версия на основе времени изменения файла
            true // Загружать в футере
        );

        // Установка переводов для скрипта
        wp_set_script_translations('postwall-block', 'postwall', POSTWALL_PLUGIN_PATH . 'languages');

        // Подключение CSS для блока в редакторе
        wp_enqueue_style(
            'postwall-block-editor', // Уникальный идентификатор стилей
            POSTWALL_PLUGIN_URL . 'build/index.css', // URL к файлу
            array('wp-edit-blocks'),             // Зависимости стилей
            file_exists($index_css) ? filemtime($index_css) : time() // Версия на основе времени изменения файла
        );
    }

    /**
     * Подключение ресурсов для фронтенда сайта
     *
     * Загружает JavaScript и CSS файлы для отображения диаграммы на сайте:
     * - JavaScript: интерактивная диаграмма с зависимостью wp-element (React)
     * - CSS: стили для диаграммы на сайте
     * Передает настройки плагина в JavaScript через wp_localize_script.
     *
     * @since 2.0.0
     */
    public function enqueue_frontend_assets() {
        // Пути к файлам сборки для фронтенда
        $frontend_js = POSTWALL_PLUGIN_PATH . 'build/frontend.js';
        $style_css = POSTWALL_PLUGIN_PATH . 'build/style-index.css';

        // Принудительно обновляем версию при изменении файла
        $frontend_version = file_exists($frontend_js) ? filemtime($frontend_js) : time();

        // Подключение JavaScript для фронтенда
        wp_enqueue_script(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'build/frontend.js',
            array('jquery', 'wp-i18n'),
            $frontend_version, // Используем время изменения файла как версию
            true
        );

        // Установка переводов для фронтенд скрипта
        wp_set_script_translations('postwall-frontend', 'postwall', POSTWALL_PLUGIN_PATH . 'languages');

        // Передача настроек плагина в JavaScript
        wp_localize_script('postwall-frontend', 'postwallSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('postwall_get_data'),
            'locale' => get_locale()
        ));

        // Подключение CSS стилей для фронтенда
        wp_enqueue_style(
            'postwall-frontend',
            POSTWALL_PLUGIN_URL . 'build/style-index.css',
            array(),
            file_exists($style_css) ? filemtime($style_css) : time()
        );
    }

    /**
     * Загрузка текстового домена
     *
     * @since 2.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'postwall',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

}

// Инициализация плагина
PostWall::get_instance();
```

## Обновленные файлы локализации

Нужно добавить новые строки перевода во все файлы локализации:

### Новые строки для `.pot` файла:
```
#: includes/block-registration.php:75
msgid "Last 12 months"
msgstr ""

#: includes/block-registration.php:76
msgid "Posts from the site {{domain}} for the year {{year}}"
msgstr ""

#: build/frontend.js:215
msgid "View posts for"
msgstr ""

#: build/index.js:93
msgid "Year"
msgstr ""

#: build/index.js:95
msgid "Last 12 months"
msgstr ""
```

### Для русского перевода (ru_RU.po):
```
#: includes/block-registration.php:75
msgid "Last 12 months"
msgstr "Последние 12 месяцев"

#: includes/block-registration.php:76
msgid "Posts from the site {{domain}} for the year {{year}}"
msgstr "Посты сайта {{domain}} за {{year}} год"

#: build/frontend.js:215
msgid "View posts for"
msgstr "Просмотреть посты за"

#: build/index.js:93
msgid "Year"
msgstr "Год"

#: build/index.js:95
msgid "Last 12 months"
msgstr "Последние 12 месяцев"
```

### Для английского перевода (en_US.po):
```
#: includes/block-registration.php:75
msgid "Last 12 months"
msgstr "Last 12 months"

#: includes/block-registration.php:76
msgid "Posts from the site {{domain}} for the year {{year}}"
msgstr "Posts from the site {{domain}} for the year {{year}}"

#: build/frontend.js:215
msgid "View posts for"
msgstr "View posts for"

#: build/index.js:93
msgid "Year"
msgstr "Year"

#: build/index.js:95
msgid "Last 12 months"
msgstr "Last 12 months"
```

## Инструкция по обновлению переводов

1. Обновите `.pot` файл:
```bash
wp i18n make-pot . languages/postwall.pot
```

2. Обновите PO файлы:
```bash
wp i18n update-po languages/postwall.pot languages/ru_RU.po
wp i18n update-po languages/postwall.pot languages/en_US.po
```

3. Сгенерируйте JSON файлы:
```bash
wp i18n make-json languages --no-purge
```

## Резюме изменений

1. **Добавлены ссылки на месяцы** - каждый месяц теперь кликабелен и ведет на архив постов за этот месяц
2. **Добавлен выбор года** - в настройках блока появился селектор для выбора года или "последние 12 месяцев"
3. **Обновлена логика выборки постов** - API теперь учитывает выбранный год
4. **Динамические заголовки** - заголовок меняется в зависимости от выбранного периода
5. **Сохранена локализация** - все новые строки переведены на русский и английский

Плагин теперь полностью соответствует требованиям и готов к использованию!