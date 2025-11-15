<?php
/**
 * Admin settings for GitHub Commit Chart plugin
 *
 * @package GitHubCommitChart
 * @since 1.0.0
 */

// Защита от прямого доступа к файлу
if (!defined('ABSPATH')) {
    exit;
}

// Проверяем, что мы в WordPress среде
if (!function_exists('add_action')) {
    return;
}

/**
 * Инициализация настроек плагина GitHub Commit Chart
 *
 * Регистрирует все необходимые настройки, секции и поля для страницы настроек в админке.
 * Вызывается через хук admin_init.
 *
 * @since 1.0.0
 */
function github_commit_chart_settings_init() {
    // Проверяем наличие необходимых функций
    if (!function_exists('register_setting') ||
        !function_exists('add_settings_section') ||
        !function_exists('add_settings_field')) {
        return;
    }

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
 *
 * @since 1.0.0
 */
function github_commit_chart_settings_section_callback() {
    _e('Введите ваш никнейм на GitHub для отображения статистики коммитов.<br>', 'github-commit-chart');
    _e('Вы можете использовать шорткод <code>[github-c github_profile="username"]</code> для отображения диаграммы в записях и страницах.<br>', 'github-commit-chart');
    _e('Если не указан параметр github_profile, будет использоваться значение из глобальных настроек.<br><br>', 'github-commit-chart');
    _e('<strong>GitHub токен:</strong> Добавление токена повышает лимиты на обращение к API, что очень важно, если на сайте много посетителей и диаграмму могут открыть много пользователей одновременно.<br>', 'github-commit-chart');
    printf(
        /* translators: %s: URL to GitHub documentation */
        __('Инструкция по созданию токена: <a href="%s" target="_blank">Managing your personal access tokens</a>', 'github-commit-chart'),
        'https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens'
    );
}

/**
 * Функция отрисовки поля ввода для профиля GitHub
 *
 * Выводит HTML элемент input с текущим значением настройки из базы данных.
 * Использует esc_attr для безопасного экранирования значения.
 *
 * @since 1.0.0
 */
function github_commit_chart_github_profile_render() {
    // Проверяем наличие необходимых функций
    if (!function_exists('get_option') || !function_exists('esc_attr') || !function_exists('wp_create_nonce')) {
        return;
    }

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
 * @since 1.0.0
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
 * @since 1.0.0
 */
function github_commit_chart_sanitize_checkbox($input) {
    return (bool) $input;
}

/**
 * Функция валидации GitHub токена
 *
 * @param string $input Входное значение токена
 * @return string Очищенное значение токена
 * @since 1.0.0
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
 *
 * @since 1.0.0
 */
function github_commit_chart_link_usernames_render() {
    // Проверяем наличие необходимых функций
    if (!function_exists('get_option') || !function_exists('checked')) {
        return;
    }

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
 *
 * @since 1.0.0
 */
function github_commit_chart_github_token_render() {
    // Проверяем наличие необходимых функций
    if (!function_exists('get_option') || !function_exists('esc_attr') || !function_exists('wp_create_nonce')) {
        return;
    }

    // Получаем текущее значение настройки из базы данных WordPress
    $github_token = get_option('github_commit_chart_github_token');
    ?>
    <!-- Поле ввода с экранированным значением для безопасности -->
    <input type='password' id='github_commit_chart_github_token' name='github_commit_chart_github_token' value='<?php echo esc_attr($github_token); ?>' placeholder='ghp_xxxxxxxxxxxxxxxxxxxx'>
    <button type="button" id="toggle_token_visibility" class="button">Показать токен</button>
    <button type="button" id="check_token_btn" class="button">Проверить токен</button>
    <span id="token_check_result"></span>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var tokenInput = $('#github_commit_chart_github_token');
        var toggleBtn = $('#toggle_token_visibility');

        // Переключение видимости токена
        toggleBtn.on('click', function() {
            if (tokenInput.attr('type') === 'password') {
                tokenInput.attr('type', 'text');
                toggleBtn.text('Скрыть токен');
            } else {
                tokenInput.attr('type', 'password');
                toggleBtn.text('Показать токен');
            }
        });

        $('#check_token_btn').on('click', function() {
            var token = tokenInput.val().trim();
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

/**
 * AJAX обработчик для проверки юзернейма GitHub
 *
 * @since 1.0.0
 */
function github_commit_chart_check_username() {
    // Проверяем наличие необходимых функций
    if (!function_exists('wp_verify_nonce') ||
        !function_exists('sanitize_text_field') ||
        !function_exists('wp_send_json_error') ||
        !function_exists('wp_send_json_success') ||
        !function_exists('wp_die')) {
        return;
    }

    // Логгируем начало обработки запроса
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('GitHub Commit Chart: Username check request received');
    }

    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'github_commit_chart_check_username')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Invalid nonce in username check');
        }
        wp_die('Недействительный запрос');
    }

    // Получаем юзернейм из POST данных
    $username = sanitize_text_field($_POST['username']);

    // Проверяем, что юзернейм не пустой
    if (empty($username)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Empty username in check request');
        }
        wp_send_json_error('Юзернейм не может быть пустым');
    }

    // Проверяем существование пользователя
    if (class_exists('GitHubCommitChart_API')) {
        if (GitHubCommitChart_API::check_user_exists($username)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GitHub Commit Chart: Username ' . $username . ' found');
            }
            wp_send_json_success('Профиль найден');
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GitHub Commit Chart: Username ' . $username . ' not found');
            }
            wp_send_json_error('Профиль не найден');
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: API class not found in username check');
        }
        wp_send_json_error('API class not found');
    }
}

/**
 * AJAX обработчик для проверки токена GitHub
 *
 * @since 1.0.0
 */
function github_commit_chart_check_token() {
    // Проверяем наличие необходимых функций
    if (!function_exists('wp_verify_nonce') ||
        !function_exists('sanitize_text_field') ||
        !function_exists('wp_remote_get') ||
        !function_exists('is_wp_error') ||
        !function_exists('wp_remote_retrieve_response_code') ||
        !function_exists('wp_remote_retrieve_body') ||
        !function_exists('wp_send_json_error') ||
        !function_exists('wp_send_json_success') ||
        !function_exists('wp_die')) {
        return;
    }

    // Логгируем начало обработки запроса
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('GitHub Commit Chart: Token check request received');
    }

    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'github_commit_chart_check_token')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Invalid nonce in token check');
        }
        wp_die('Недействительный запрос');
    }

    // Получаем токен из POST данных
    $token = sanitize_text_field($_POST['token']);

    // Проверяем, что токен не пустой
    if (empty($token)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Empty token in check request');
        }
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Network error in token check - ' . $response->get_error_message());
        }
        wp_send_json_error('Ошибка сети: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code === 200) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Token is valid');
        }
        wp_send_json_success('Токен действителен');
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $error_message = isset($data['message']) ? $data['message'] : 'Токен недействителен';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Commit Chart: Token is invalid - ' . $error_message);
        }

        wp_send_json_error('Ошибка: ' . $error_message);
    }
}

// Регистрируем AJAX обработчики
add_action('wp_ajax_github_commit_chart_check_username', 'github_commit_chart_check_username');
add_action('wp_ajax_github_commit_chart_check_token', 'github_commit_chart_check_token');

// Регистрируем хук для инициализации настроек при загрузке админ-панели
add_action('admin_init', 'github_commit_chart_settings_init');