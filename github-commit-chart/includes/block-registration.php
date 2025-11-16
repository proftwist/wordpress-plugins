<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Gutenberg-блока GitHub Commit Chart
 *
 * Регистрирует динамический блок в редакторе Gutenberg с указанием
 * необходимых скриптов, стилей и обработчика рендеринга на сервере.
 */
function github_commit_chart_register_block() {
    // Регистрируем блок через WordPress API с полными параметрами
    register_block_type('github-commit-chart/git-diagram', array(
        'editor_script' => 'github-commit-chart-block',         // JavaScript для редактора блоков
        'editor_style' => 'github-commit-chart-block-editor',   // CSS стили для редактора
        'style' => 'github-commit-chart-frontend',              // CSS стили для фронтенда
        'render_callback' => 'github_commit_chart_render_block', // Функция серверного рендеринга
        'attributes' => array(                                   // Определение атрибутов блока
            'githubProfile' => array(
                'type' => 'string',    // Тип данных атрибута
                'default' => ''        // Значение по умолчанию (пустая строка)
            ),
            'headingTag' => array(
                'type' => 'string',    // Тип данных атрибута
                'default' => 'h3'      // Значение по умолчанию (h3)
            )
        )
    ));
}

/**
 * Функция серверного рендеринга блока GitHub Commit Chart
 *
 * Вызывается WordPress при выводе блока на странице. Генерирует HTML-разметку
 * контейнера для диаграммы и передает необходимые данные через data-атрибуты.
 *
 * @param array $attributes Атрибуты блока (включая githubProfile)
 * @param string $content Внутреннее содержимое блока (не используется в динамических блоках)
 * @return string HTML-разметка контейнера диаграммы или сообщение об ошибке
 */
function github_commit_chart_render_block($attributes, $content) {
    // Валидация входных данных
    if (!is_array($attributes)) {
        $attributes = array();
    }

    // Получаем профиль GitHub из атрибутов блока или из глобальных настроек плагина
    $github_profile = !empty($attributes['githubProfile']) ?
                      sanitize_text_field($attributes['githubProfile']) :
                      get_option('github_commit_chart_github_profile', '');

    // Проверяем, указан ли профиль GitHub
    if (empty($github_profile)) {
        return '<p>' . __('Please specify GitHub profile path in plugin settings or block attributes.', 'github-commit-chart') . '</p>';
    }

    // Валидация формата github_profile
    if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $github_profile)) {
        return '<p>' . __('Invalid GitHub profile format.', 'github-commit-chart') . '</p>';
    }

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('gcc-');

    // Получаем тег заголовка из атрибутов блока или используем значение по умолчанию
    $allowed_heading_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    $heading_tag = !empty($attributes['headingTag']) && in_array($attributes['headingTag'], $allowed_heading_tags) ?
                   $attributes['headingTag'] : 'h3';

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-github-profile="' . esc_attr($github_profile) . '" data-container-id="' . esc_attr($unique_id) . '" data-heading-tag="' . esc_attr($heading_tag) . '"';

    // Возвращаем HTML контейнер для диаграммы
    return '<div class="github-commit-chart-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                <div class="github-commit-chart-loading">' . __('Loading commit chart...', 'github-commit-chart') . '</div>
            </div>';
}

// Регистрируем блок при инициализации
add_action('init', 'github_commit_chart_register_block');