<?php
// Защита от прямого доступа к файлу
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Инициализация настроек плагина GitHub Commit Chart
 *
 * Регистрирует все необходимые настройки, секции и поля для страницы настроек в админке.
 * Вызывается через хук admin_init.
 */
function github_commit_chart_settings_init() {
    // Регистрация основной настройки - профиля GitHub
    register_setting('github_commit_chart_settings', 'github_commit_chart_github_profile', array(
        'type' => 'string',                                     // Тип данных - строка
        'sanitize_callback' => 'github_commit_chart_sanitize_github_profile', // Функция очистки данных
        'default' => ''                                         // Значение по умолчанию
    ));

    // Добавление секции настроек
    add_settings_section(
        'github_commit_chart_settings_section',           // ID секции
        'Настройки GitHub Commit Chart',                  // Заголовок секции
        'github_commit_chart_settings_section_callback',  // Callback функция описания секции
        'github_commit_chart_settings'                    // ID страницы настроек
    );

    // Добавление поля для ввода профиля GitHub
    add_settings_field(
        'github_commit_chart_github_profile',               // ID поля
        'Юзернейм Github',                                  // Метка поля
        'github_commit_chart_github_profile_render',        // Функция отрисовки поля
        'github_commit_chart_settings',                     // ID страницы настроек
        'github_commit_chart_settings_section'              // ID секции
    );
}

/**
 * Callback функция для отображения описания секции настроек
 *
 * Вызывается WordPress для отображения описательного текста в секции настроек.
 * Поясняет пользователю, что нужно ввести в поле.
 */
function github_commit_chart_settings_section_callback() {
    echo 'Введите ваш никнейм на GitHub для отображения статистики коммитов';
}

/**
 * Функция отрисовки поля ввода для профиля GitHub
 *
 * Выводит HTML элемент input с текущим значением настройки из базы данных.
 * Использует esc_attr для безопасного экранирования значения.
 */
function github_commit_chart_github_profile_render() {
    // Получаем текущее значение настройки из базы данных WordPress
    $github_profile = get_option('github_commit_chart_github_profile');
    ?>
    <!-- Поле ввода с экранированным значением для безопасности -->
    <input type='text' name='github_commit_chart_github_profile' value='<?php echo esc_attr($github_profile); ?>' placeholder='например: username'>
    <?php
}

/**
 * Функция валидации и очистки значения профиля GitHub
 *
 * Обрабатывает введенные пользователем данные перед сохранением в базу данных.
 * Удаляет недопустимые символы и пробелы для безопасности.
 *
 * @param string $input Входное значение от пользователя
 * @return string Очищенное значение, безопасное для сохранения
 */
function github_commit_chart_sanitize_github_profile($input) {
    // Удаляем пробелы в начале и конце строки
    $input = trim($input);

    // Удаляем любые символы, кроме букв, цифр, дефисов и подчеркиваний
    // Это предотвращает XSS атаки и обеспечивает валидный формат username
    $input = preg_replace('/[^a-zA-Z0-9\-_]/', '', $input);

    return $input;
}

// Регистрируем хук для инициализации настроек при загрузке админ-панели
add_action('admin_init', 'github_commit_chart_settings_init');