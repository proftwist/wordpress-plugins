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
    // Получаем профиль GitHub из атрибутов блока или из глобальных настроек плагина
    $github_profile = !empty($attributes['githubProfile']) ?
                     $attributes['githubProfile'] :
                     get_option('github_commit_chart_github_profile', '');

    // Проверяем, указан ли профиль GitHub
    if (empty($github_profile)) {
        return '<p>Пожалуйста, укажите путь к профилю GitHub в настройках плагина или в атрибутах блока.</p>';
    }

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('gcc-');

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-github-profile="' . esc_attr($github_profile) . '" data-container-id="' . esc_attr($unique_id) . '"';

    // Возвращаем HTML контейнер для диаграммы
    return '<div class="github-commit-chart-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                <div class="github-commit-chart-loading">Загрузка диаграммы коммитов...</div>
            </div>';
}

// Регистрируем блок при инициализации
add_action('init', 'github_commit_chart_register_block');