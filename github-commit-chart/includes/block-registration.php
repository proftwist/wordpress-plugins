<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

function github_commit_chart_register_block() {
    // Проверяем существование необходимых функций
    if (!function_exists('wp_register_script') || !function_exists('wp_register_style') || !function_exists('register_block_type')) {
        return;
    }
    
    $plugin_dir_path = plugin_dir_path(dirname(__FILE__));
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    $index_js = $plugin_dir_path . 'build/index.js';
    $index_css = $plugin_dir_path . 'build/index.css';
    $style_css = $plugin_dir_path . 'build/style-index.css';
    
    wp_register_script(
        'github-commit-chart-block-editor',
        $plugin_url . 'build/index.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        file_exists($index_js) ? filemtime($index_js) : time(),
        true
    );
    
    wp_register_style(
        'github-commit-chart-block-editor',
        $plugin_url . 'build/index.css',
        array('wp-edit-blocks'),
        file_exists($index_css) ? filemtime($index_css) : time()
    );
    
    wp_register_style(
        'github-commit-chart-frontend',
        $plugin_url . 'build/style-index.css',
        array(),
        file_exists($style_css) ? filemtime($style_css) : time()
    );
    
    register_block_type('github-commit-chart/git-diagram', array(
        'editor_script' => 'github-commit-chart-block-editor',
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
    
    // Отладочный вывод
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('GitHub Commit Chart: github_profile = ' . $github_profile);
        error_log('GitHub Commit Chart: attributes = ' . print_r($attributes, true));
    }
    
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