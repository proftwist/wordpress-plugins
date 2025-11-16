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

    // Для демонстрации временно отключим валидацию
    // if (empty($site_url)) {
    //     return '<p>' . __('Please specify the site URL in the block settings.', 'postwall') . '</p>';
    // }

    // if (!filter_var($site_url, FILTER_VALIDATE_URL)) {
    //     return '<p>' . __('Invalid site URL format.', 'postwall') . '</p>';
    // }

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('postwall-');

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-site-url="' . esc_attr($site_url) . '" data-container-id="' . esc_attr($unique_id) . '"';

    // Возвращаем HTML контейнер для диаграммы
    return '<div class="postwall-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                <h3 class="postwall-title">' . __('Posts from the site for the last 12 months', 'postwall') . '</h3>
                <div class="postwall-loading">' . __('Loading post wall...', 'postwall') . '</div>
            </div>';
}

// Регистрируем блок при инициализации
add_action('init', 'postwall_register_block');