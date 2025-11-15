<?php
/**
 * Shortcode Handler for GitHub Commit Chart plugin
 *
 * Handles shortcode registration and processing.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Class GitHubCommitChart_Shortcode_Handler
 *
 * Handles the [github-c] shortcode functionality.
 *
 * @package GitHubCommitChart
 * @since 1.8.4
 */
class GitHubCommitChart_Shortcode_Handler {

    /**
     * Инициализация обработчика шорткодов
     *
     * @since 1.8.4
     */
    public static function init() {
        add_shortcode('github-c', array(__CLASS__, 'handle_shortcode'));
    }

    /**
     * Обработчик шорткода [github-c]
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-разметка диаграммы
     * @since 1.8.4
     */
    public static function handle_shortcode($atts) {
        // Объединяем атрибуты шорткода с значениями по умолчанию
        $atts = shortcode_atts(array(
            'github_profile' => '',
        ), $atts, 'github-c');

        // Получаем профиль GitHub из атрибутов или из глобальных настроек плагина
        $github_profile = !empty($atts['github_profile']) ?
                          $atts['github_profile'] :
                          get_option('github_commit_chart_github_profile', '');

        // Валидация github_profile
        if (empty($github_profile)) {
            return '<p>' . __('Пожалуйста, укажите путь к профилю GitHub в настройках плагина или в атрибутах шорткода.', 'github-commit-chart') . '</p>';
        }

        // Валидация формата профиля
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $github_profile)) {
            return '<p>' . __('Неверный формат профиля GitHub.', 'github-commit-chart') . '</p>';
        }

        // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
        $unique_id = uniqid('gcc-');

        // Формируем data-атрибуты для передачи данных в JavaScript
        // Безопасно экранируем значения функцией esc_attr
        $data_attributes = 'data-github-profile="' . esc_attr($github_profile) . '" data-container-id="' . esc_attr($unique_id) . '"';

        // Возвращаем HTML контейнер для диаграммы
        return '<div class="github-commit-chart-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '>
                    <div class="github-commit-chart-loading">' . __('Загрузка диаграммы коммитов...', 'github-commit-chart') . '</div>
                </div>';
    }
}

// Инициализация обработчика шорткодов
GitHubCommitChart_Shortcode_Handler::init();