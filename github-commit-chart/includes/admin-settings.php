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
    echo 'Введите ваш никнейм на GitHub для отображения статистики коммитов.<br>';
    echo 'Вы можете использовать шорткод <code>[github-c github_profile="username"]</code> для отображения диаграммы в записях и страницах.<br>';
    echo 'Если не указан параметр github_profile, будет использоваться значение из глобальных настроек.';
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
    <input type='text' id='github_commit_chart_github_profile' name='github_commit_chart_github_profile' value='<?php echo esc_attr($github_profile); ?>' placeholder='например: username'>
    <button type="button" id="check_username_btn" class="button">Проверить юзернейм</button>
    <span id="username_check_result"></span>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#check_username_btn').on('click', function() {
            var username = $('#github_commit_chart_github_profile').val().trim();
            var resultSpan = $('#username_check_result');

            if (username === '') {
                resultSpan.html('<span style="color: red;">Введите юзернейм</span>');
                return;
            }

            resultSpan.html('<span style="color: blue;">Проверяем...</span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'github_commit_chart_check_username',
                    nonce: '<?php echo wp_create_nonce("github_commit_chart_check_username"); ?>',
                    username: username
                },
                success: function(response) {
                    if (response.success) {
                        resultSpan.html('<span style="color: green;">' + response.data + '</span>');
                    } else {
                        resultSpan.html('<span style="color: red;">' + response.data + '</span>');
                    }
                },
                error: function() {
                    resultSpan.html('<span style="color: red;">Ошибка при проверке</span>');
                }
            });
        });
    });
    </script>
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

// AJAX обработчик для проверки юзернейма GitHub
/**
 * Function github_commit_chart_check_username.
 */
function github_commit_chart_check_username() {
    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'github_commit_chart_check_username')) {
        wp_die('Недействительный запрос');
    }

    // Получаем юзернейм из POST данных
    $username = sanitize_text_field($_POST['username']);

    // Проверяем, что юзернейм не пустой
    if (empty($username)) {
        wp_send_json_error('Юзернейм не может быть пустым');
    }

    // Проверяем существование пользователя
    if (GitHubCommitChart_API::check_user_exists($username)) {
        wp_send_json_success('Профиль найден');
    } else {
        wp_send_json_error('Профиль не найден');
    }
}

// Регистрируем AJAX обработчик
add_action('wp_ajax_github_commit_chart_check_username', 'github_commit_chart_check_username');

// Регистрируем хук для инициализации настроек при загрузке админ-панели
add_action('admin_init', 'github_commit_chart_settings_init');