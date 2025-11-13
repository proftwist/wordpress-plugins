<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Инициализация настроек плагина GitHub Commit Chart
 *
 * Регистрирует настройки, секции и поля для страницы настроек плагина.
 */
function github_commit_chart_settings_init() {
    register_setting('github_commit_chart_settings', 'github_commit_chart_github_profile', array(
        'type' => 'string',
        'sanitize_callback' => 'github_commit_chart_sanitize_github_profile',
        'default' => ''
    ));

    add_settings_section(
        'github_commit_chart_settings_section',
        'Настройки GitHub Commit Chart',
        'github_commit_chart_settings_section_callback',
        'github_commit_chart_settings'
    );

    add_settings_field(
        'github_commit_chart_github_profile',
        'Путь к профилю Github',
        'github_commit_chart_github_profile_render',
        'github_commit_chart_settings',
        'github_commit_chart_settings_section'
    );
}

/**
 * Callback функция для отображения описания секции настроек
 *
 * Выводит поясняющий текст для секции настроек плагина.
 */
function github_commit_chart_settings_section_callback() {
    echo 'Введите ваш никнейм на GitHub';
}

/**
 * Отображение поля ввода для профиля GitHub
 *
 * Выводит HTML-разметку поля ввода с текущим значением настройки.
 */
function github_commit_chart_github_profile_render() {
    $github_profile = get_option('github_commit_chart_github_profile');
    ?>
    <input type='text' name='github_commit_chart_github_profile' value='<?php echo esc_attr($github_profile); ?>' placeholder='например: username'>
    <?php
}

/**
 * Валидация значения профиля GitHub
 *
 * @param string $input Входное значение
 * @return string Очищенное значение
 */
function github_commit_chart_sanitize_github_profile($input) {
    // Удаляем пробелы в начале и конце
    $input = trim($input);
    
    // Удаляем любые символы, кроме букв, цифр, дефисов и подчеркиваний
    $input = preg_replace('/[^a-zA-Z0-9\-_]/', '', $input);
    
    return $input;
}

// Добавляем хук для инициализации настроек
add_action('admin_init', 'github_commit_chart_settings_init');