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

    // Регистрация настройки для прикрепления ссылок к именам пользователей
    register_setting('github_commit_chart_settings', 'github_commit_chart_link_usernames', array(
        'type' => 'boolean',                                    // Тип данных - булево
        'sanitize_callback' => 'github_commit_chart_sanitize_checkbox', // Функция очистки данных
        'default' => false                                      // Значение по умолчанию
    ));

    // Регистрация настройки для GitHub токена
    register_setting('github_commit_chart_settings', 'github_commit_chart_github_token', array(
        'type' => 'string',                                     // Тип данных - строка
        'sanitize_callback' => 'github_commit_chart_sanitize_github_token', // Функция очистки данных
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

    // Добавление поля для чекбокса прикрепления ссылок
    add_settings_field(
        'github_commit_chart_link_usernames',               // ID поля
        'Прикрепить к имени пользователя ссылку на гитхаб-профиль', // Метка поля
        'github_commit_chart_link_usernames_render',        // Функция отрисовки поля
        'github_commit_chart_settings',                     // ID страницы настроек
        'github_commit_chart_settings_section'              // ID секции
    );

    // Добавление поля для ввода GitHub токена
    add_settings_field(
        'github_commit_chart_github_token',                 // ID поля
        'GitHub токен',                                     // Метка поля
        'github_commit_chart_github_token_render',          // Функция отрисовки поля
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
    echo 'Если не указан параметр github_profile, будет использоваться значение из глобальных настроек.<br><br>';
    echo '<strong>GitHub токен:</strong> Добавление токена повышает лимиты на обращение к API, что очень важно, если на сайте много посетителей и диаграмму могут открыть много пользователей одновременно.<br>';
    echo 'Инструкция по созданию токена: <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens" target="_blank">Managing your personal access tokens</a>';
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

/**
 * Функция валидации чекбокса
 *
 * @param mixed $input Входное значение от чекбокса
 * @return bool Булево значение
 */
function github_commit_chart_sanitize_checkbox($input) {
    return (bool) $input;
}

/**
 * Функция валидации GitHub токена
 *
 * @param string $input Входное значение токена
 * @return string Очищенное значение токена
 */
function github_commit_chart_sanitize_github_token($input) {
    // Удаляем пробелы в начале и конце строки
    $input = trim($input);

    // Проверяем формат токена (обычно начинается с ghp_ или github_pat_)
    if (!empty($input) && !preg_match('/^(ghp_|github_pat_)/', $input)) {
        // Если токен не начинается с правильного префикса, возвращаем пустую строку
        return '';
    }

    return $input;
}

/**
 * Функция отрисовки чекбокса для прикрепления ссылок к именам пользователей
 *
 * Выводит HTML элемент checkbox с текущим значением настройки из базы данных.
 */
function github_commit_chart_link_usernames_render() {
    // Получаем текущее значение настройки из базы данных WordPress
    $link_usernames = get_option('github_commit_chart_link_usernames', false);
    ?>
    <!-- Чекбокс для прикрепления ссылок -->
    <input type='checkbox' id='github_commit_chart_link_usernames' name='github_commit_chart_link_usernames' value='1' <?php checked($link_usernames, true); ?>>
    <label for='github_commit_chart_link_usernames'>Включить ссылки на GitHub-профили в диаграммах</label>
    <?php
}

/**
 * Функция отрисовки поля ввода для GitHub токена
 *
 * Выводит HTML элемент input с текущим значением настройки из базы данных.
 * Использует esc_attr для безопасного экранирования значения.
 */
function github_commit_chart_github_token_render() {
    // Получаем текущее значение настройки из базы данных WordPress
    $github_token = get_option('github_commit_chart_github_token');
    ?>
    <!-- Поле ввода с экранированным значением для безопасности -->
    <input type='text' id='github_commit_chart_github_token' name='github_commit_chart_github_token' value='<?php echo esc_attr($github_token); ?>' placeholder='ghp_xxxxxxxxxxxxxxxxxxxx'>
    <button type="button" id="check_token_btn" class="button">Проверить токен</button>
    <span id="token_check_result"></span>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#check_token_btn').on('click', function() {
            var token = $('#github_commit_chart_github_token').val().trim();
            var resultSpan = $('#token_check_result');

            if (token === '') {
                resultSpan.html('<span style="color: red;">Введите токен</span>');
                return;
            }

            resultSpan.html('<span style="color: blue;">Проверяем...</span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'github_commit_chart_check_token',
                    nonce: '<?php echo wp_create_nonce("github_commit_chart_check_token"); ?>',
                    token: token
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

// AJAX обработчик для проверки токена GitHub
/**
 * Function github_commit_chart_check_token.
 */
function github_commit_chart_check_token() {
    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'github_commit_chart_check_token')) {
        wp_die('Недействительный запрос');
    }

    // Получаем токен из POST данных
    $token = sanitize_text_field($_POST['token']);

    // Проверяем, что токен не пустой
    if (empty($token)) {
        wp_send_json_error('Токен не может быть пустым');
    }

    // Проверяем токен через API
    $response = wp_remote_get('https://api.github.com/user', array(
        'headers' => array(
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'GitHub-Commit-Chart-WordPress-Plugin'
        ),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Ошибка сети: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code === 200) {
        wp_send_json_success('Токен действителен');
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $error_message = isset($data['message']) ? $data['message'] : 'Токен недействителен';
        wp_send_json_error('Ошибка: ' . $error_message);
    }
}

// Регистрируем AJAX обработчики
add_action('wp_ajax_github_commit_chart_check_username', 'github_commit_chart_check_username');
add_action('wp_ajax_github_commit_chart_check_token', 'github_commit_chart_check_token');

// Регистрируем хук для инициализации настроек при загрузке админ-панели
add_action('admin_init', 'github_commit_chart_settings_init');