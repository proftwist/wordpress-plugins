<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

function github_commit_chart_register_block() {
    // Регистрируем блок
    register_block_type('github-commit-chart/git-diagram', array(
        'editor_script' => 'github-commit-chart-block',
        'editor_style' => 'github-commit-chart-block-editor',
        'style' => 'github-commit-chart-frontend',
        'render_callback' => 'github_commit_chart_render_block',
        'attributes' => array(
            'githubProfile' => array(
                'type' => 'string',
                'default' => ''
            )
        )
    ));
}

function github_commit_chart_render_block($attributes, $content) {
    // Получаем имя пользователя из атрибутов блока или из глобальных настроек
    $github_profile = !empty($attributes['githubProfile']) ? $attributes['githubProfile'] : get_option('github_commit_chart_github_profile', '');

    if (empty($github_profile)) {
        return '<p>Пожалуйста, укажите путь к профилю GitHub в настройках плагина или в атрибутах блока.</p>';
    }

    // Генерируем уникальный ID для контейнера диаграммы
    $unique_id = uniqid('gcc-');

    // Передаем данные в скрипт через data-атрибуты
    $data_attributes = 'data-github-profile="' . esc_attr($github_profile) . '" data-container-id="' . esc_attr($unique_id) . '"';

    return '<div class="github-commit-chart-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                <div class="github-commit-chart-loading">Загрузка диаграммы коммитов...</div>
            </div>';
}

// Регистрируем блок при инициализации
add_action('init', 'github_commit_chart_register_block');