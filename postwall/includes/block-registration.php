<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Gutenberg-блока Post Wall
 *
 * Регистрирует динамический блок в редакторе Gutenberg с указанием
 * необходимых скриптов, стилей и обработчика рендеринга на сервере.
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
            )
        )
    ));
}

/**
 * Извлекает домен из URL
 *
 * @param string $url URL сайта
 * @return string Доменное имя
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

    // Извлекаем домен для заголовка
    $domain = postwall_extract_domain($site_url);

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('postwall-');

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-site-url="' . esc_attr($site_url) . '" data-container-id="' . esc_attr($unique_id) . '"';

    // Передаем отдельно домен и базовые тексты
    $base_title = __('Posts from the site for the last 12 months', 'postwall');
    $loading_text = __('Loading post wall...', 'postwall');

    // Возвращаем HTML контейнер для диаграммы с data-атрибутами
    return '<div class="postwall-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '
                data-base-title="' . esc_attr($base_title) . '"
                data-loading-text="' . esc_attr($loading_text) . '"
                data-domain="' . esc_attr($domain) . '">
                <h3 class="postwall-title">' . esc_html(generate_title_with_domain($base_title, $domain)) . '</h3>
                <div class="postwall-loading">' . esc_html($loading_text) . '</div>
            </div>';
}

/**
 * Генерирует заголовок с доменом
 *
 * @param string $base_title Базовый заголовок
 * @param string $domain Домен сайта
 * @return string Заголовок с доменом
 */
function generate_title_with_domain($base_title, $domain) {
    if (empty($domain)) {
        return $base_title;
    }

    // Для русского языка
    if (get_locale() === 'ru_RU') {
        return 'Посты сайта ' . $domain . ' за последние 12 месяцев';
    }

    // Для английского и других языков
    return 'Posts from the site ' . $domain . ' for the last 12 months';
}

// Регистрируем блок при инициализации
add_action('init', 'postwall_register_block');