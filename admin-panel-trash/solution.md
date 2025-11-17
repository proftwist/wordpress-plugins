Проблема в том, что регулярные выражения не находят функцию. Давайте упростим подход и сделаем его более надежным.

## 1. Упростим поиск функции

```php
/**
 * Упрощенный поиск функции в файле
 */
private function find_function_in_content($content) {
    // Пробуем несколько простых шаблонов
    $patterns = [
        // Простой поиск по имени функции
        '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
        // Поиск с любыми пробелами
        '/function\s*remove_items_from_admin_bar\s*\(\s*\)\s*\{[^}]+\}/s',
        // Поиск по remove_menu вызовам
        '/\{[^}]*\$wp_admin_bar->remove_menu[^}]*\}/s'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return $matches[0];
        }
    }

    return null;
}

/**
 * Получение отключенных элементов из содержимого файла (упрощенное)
 */
private function get_disabled_items_from_file_content($content) {
    $disabled_items = array();

    // Сначала ищем функцию
    $function_code = $this->find_function_in_content($content);

    if ($function_code) {
        // Ищем все remove_menu вызовы в найденной функции
        if (preg_match_all('/\$wp_admin_bar->remove_menu\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*;/', $function_code, $matches)) {
            $disabled_items = $matches[1];
        }
    }

    return $disabled_items;
}

/**
 * Получение ВСЕХ отключенных элементов ИЗ ФАЙЛА
 */
private function get_disabled_items_from_file() {
    $file_path = $this->get_functions_file_path();

    if (!file_exists($file_path) || !is_readable($file_path)) {
        return array();
    }

    $content = file_get_contents($file_path);
    $disabled_items = $this->get_disabled_items_from_file_content($content);

    // Синхронизируем с опциями
    $current_options = get_option($this->option_name, array());
    if ($disabled_items != $current_options) {
        update_option($this->option_name, $disabled_items);
    }

    return $disabled_items;
}
```

## 2. Добавим метод для принудительного поиска функции

```php
/**
 * Детектор функции с отладкой
 */
private function detect_function_with_debug($content) {
    $debug_info = [
        'function_found' => false,
        'method' => 'none',
        'function_code' => '',
        'patterns_tried' => []
    ];

    $patterns = [
        'exact_function' => '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
        'flexible_function' => '/function\s*remove_items_from_admin_bar\s*\(\s*\)\s*\{[^}]+\}/s',
        'remove_menu_calls' => '/\{[^}]*\$wp_admin_bar->remove_menu[^}]*\}/s',
        'any_function_with_remove' => '/function[^{]+\{[^}]*\$wp_admin_bar->remove_menu[^}]*\}/s'
    ];

    foreach ($patterns as $name => $pattern) {
        $debug_info['patterns_tried'][$name] = (bool) preg_match($pattern, $content);
        if (preg_match($pattern, $content, $matches)) {
            $debug_info['function_found'] = true;
            $debug_info['method'] = $name;
            $debug_info['function_code'] = $matches[0];
            break;
        }
    }

    return $debug_info;
}
```

## 3. Обновим отладочную информацию

```php
/**
 * Детальная отладочная информация о файле
 */
private function get_file_debug_info() {
    $file_path = $this->get_functions_file_path();
    $info = array(
        'path' => $file_path,
        'exists' => file_exists($file_path),
        'readable' => is_readable($file_path),
        'writable' => is_writable($file_path),
        'function_found' => false,
        'function_content' => '',
        'disabled_items' => array(),
        'file_size' => 0,
        'file_lines' => 0,
        'detection_debug' => array()
    );

    if ($info['exists'] && $info['readable']) {
        $content = file_get_contents($file_path);
        $info['file_size'] = strlen($content);
        $info['file_lines'] = substr_count($content, "\n") + 1;

        // Используем детектор с отладкой
        $detection_result = $this->detect_function_with_debug($content);
        $info['function_found'] = $detection_result['function_found'];
        $info['function_content'] = $detection_result['function_code'];
        $info['detection_debug'] = $detection_result;

        $info['disabled_items'] = $this->get_disabled_items_from_file_content($content);

        // Дополнительные проверки
        $info['has_remove_menu'] = strpos($content, '$wp_admin_bar->remove_menu') !== false;
        $info['has_wp_before_admin_bar_render'] = strpos($content, 'wp_before_admin_bar_render') !== false;
    }

    return $info;
}
```

## 4. Добавим инструмент для создания функции с нуля

```php
/**
 * Создание функции с нуля
 */
public function ajax_create_function() {
    $this->check_ajax_permissions();

    $file_path = $this->get_functions_file_path();

    if (!file_exists($file_path) || !is_writable($file_path)) {
        wp_send_json_error(__('Файл недоступен для записи', 'admin-panel-trash'));
    }

    $content = file_get_contents($file_path);

    // Получаем текущие элементы из опций (если есть)
    $disabled_items = get_option($this->option_name, array());

    // Проверяем, есть ли уже функция
    $function_exists = $this->find_function_in_content($content);

    if ($function_exists) {
        wp_send_json_error(__('Функция уже существует в файле', 'admin-panel-trash'));
    }

    // Добавляем функцию
    $new_content = $this->add_new_function($content, $disabled_items);

    if (file_put_contents($file_path, $new_content) !== false) {
        wp_send_json_success(array(
            'message' => __('Функция успешно создана', 'admin-panel-trash'),
            'items_count' => count($disabled_items),
            'items' => $disabled_items
        ));
    } else {
        wp_send_json_error(__('Ошибка при создании функции', 'admin-panel-trash'));
    }
}
```

Добавьте хук в конструктор:
```php
add_action('wp_ajax_apt_create_function', array($this, 'ajax_create_function'));
```

## 5. Обновим интерфейс

```php
/**
 * Страница админки
 */
public function admin_page() {
    $current_function_code = $this->get_current_function_code();
    $debug_info = $this->get_file_debug_info();
    $backup_items = get_option($this->option_name . '_backup', array());
    $backup_count = count($backup_items);
    ?>
    <div class="wrap">
        <h1><?php _e('Admin Panel Trash', 'admin-panel-trash'); ?></h1>

        <?php if (!$debug_info['function_found']): ?>
        <div class="notice notice-info">
            <p>
                <strong>ℹ️ Функция не найдена в файле</strong>
                - Необходимо создать функцию для управления элементами.
                <button type="button" id="apt-create-function" class="button button-primary" style="margin-left: 10px;">
                    Создать функцию
                </button>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($backup_count > 0): ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Найден backup элементов</strong>
                - В backup сохранено <?php echo $backup_count; ?> элементов.
                <button type="button" id="apt-view-backup" class="button button-small" style="margin-left: 10px;">
                    Просмотреть backup
                </button>
                <button type="button" id="apt-restore-backup" class="button button-small button-primary">
                    Восстановить из backup
                </button>
            </p>
            <div id="apt-backup-details" style="display: none; margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ccd0d4;"></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php _e('Управление файлом', 'admin-panel-trash'); ?></h2>
            <p><?php _e('Проверьте, доступен ли файл functions.php текущей темы для записи:', 'admin-panel-trash'); ?></p>
            <button id="apt-check-access" class="button button-primary">
                <?php _e('Проверить доступ', 'admin-panel-trash'); ?>
            </button>
            <?php if ($debug_info['function_found']): ?>
            <button id="apt-cleanup-function" class="button button-secondary" style="margin-left: 10px;">
                <?php _e('Пересоздать функцию', 'admin-panel-trash'); ?>
            </button>
            <?php endif; ?>
            <div id="apt-access-result" style="margin-top: 10px;"></div>
        </div>

        <!-- остальной код без изменений -->
    </div>

    <style>
        .apt-status-enabled { color: green; font-weight: bold; }
        .apt-status-disabled { color: red; font-weight: bold; }
        .apt-error { color: red; font-weight: bold; }
        .backup-item { background: #fff8e1; padding: 5px; margin: 2px; border-radius: 3px; }
        .pattern-match { color: green; }
        .pattern-no-match { color: red; }
    </style>
    <?php
}
```

## 6. Обновим JavaScript

```javascript
// Создание функции
$('#apt-create-function').on('click', function() {
    var $button = $(this);
    $button.prop('disabled', true).text('Создание...');

    $.ajax({
        url: apt_ajax.url,
        type: 'POST',
        data: {
            action: 'apt_create_function',
            nonce: apt_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                showMessage('✅ ' + response.data.message + ' Добавлено ' + response.data.items_count + ' элементов.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showMessage('❌ ' + response.data, 'error');
                $button.prop('disabled', false).text('Создать функцию');
            }
        },
        error: function() {
            showMessage('❌ Ошибка при создании функции', 'error');
            $button.prop('disabled', false).text('Создать функцию');
        }
    });
});

// Обновим функцию checkFileAccess для детальной отладки
function checkFileAccess() {
    $('#apt-check-access').prop('disabled', true).text(apt_localize.checking);
    $('#apt-access-result').html('<p>' + apt_localize.checking + '</p>');

    $.ajax({
        url: apt_ajax.url,
        type: 'POST',
        data: {
            action: 'apt_check_file_access',
            nonce: apt_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                var data = response.data;
                var debug = data.debug_info;
                var html = '<div class="notice notice-' + (data.writable ? 'success' : 'error') + '">';
                html += '<p><strong>' + apt_localize.file_path + '</strong> ' + data.path + '</p>';
                html += '<p><strong>' + apt_localize.read_access + '</strong> ' +
                    (data.readable ? apt_localize.yes : apt_localize.no) + '</p>';
                html += '<p><strong>' + apt_localize.write_access + '</strong> ' +
                    (data.writable ? apt_localize.yes : apt_localize.no) + '</p>';

                // Детальная отладочная информация
                html += '<details style="margin-top: 10px;">';
                html += '<summary style="cursor: pointer;"><strong>Детальная отладка:</strong></summary>';
                html += '<div style="margin-left: 10px; margin-top: 5px; font-size: 12px;">';
                html += '<p><strong>Функция найдена:</strong> ' + (debug.function_found ? '✅ Да' : '❌ Нет') + '</p>';
                html += '<p><strong>Отключенных элементов:</strong> ' + debug.disabled_items.length + '</p>';
                html += '<p><strong>Элементы:</strong> ' + (debug.disabled_items.join(', ') || 'нет') + '</p>';
                html += '<p><strong>Размер файла:</strong> ' + debug.file_size + ' байт</p>';
                html += '<p><strong>Строк в файле:</strong> ' + debug.file_lines + '</p>';
                html += '<p><strong>Содержит remove_menu:</strong> ' + (debug.has_remove_menu ? '✅ Да' : '❌ Нет') + '</p>';
                html += '<p><strong>Содержит wp_before_admin_bar_render:</strong> ' + (debug.has_wp_before_admin_bar_render ? '✅ Да' : '❌ Нет') + '</p>';

                if (debug.detection_debug && debug.detection_debug.patterns_tried) {
                    html += '<p><strong>Паттерны поиска:</strong></p>';
                    html += '<ul>';
                    for (var pattern in debug.detection_debug.patterns_tried) {
                        var matched = debug.detection_debug.patterns_tried[pattern];
                        html += '<li class="' + (matched ? 'pattern-match' : 'pattern-no-match') + '">';
                        html += pattern + ': ' + (matched ? '✅' : '❌');
                        html += '</li>';
                    }
                    html += '</ul>';
                }

                if (debug.function_content) {
                    html += '<p><strong>Найденная функция:</strong></p>';
                    html += '<pre style="background: #f1f1f1; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 200px; font-size: 10px;">' + debug.function_content + '</pre>';
                }

                html += '</div>';
                html += '</details>';
                html += '</div>';

                $('#apt-access-result').html(html);
            } else {
                $('#apt-access-result').html('<div class="notice notice-error"><p>' +
                    apt_localize.error + ': ' + response.data + '</p></div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Access check error:', error);
            $('#apt-access-result').html('<div class="notice notice-error"><p>' +
                apt_localize.request_error + ': ' + error + '</p></div>');
        },
        complete: function() {
            $('#apt-check-access').prop('disabled', false).text(apt_localize.check_access);
        }
    });
}
```

## 7. Что делать сейчас

1. **Нажмите "Проверить доступ"** - посмотрите в детальной отладке какие паттерны сработали
2. **Если функция не найдена** - нажмите "Создать функцию"
3. **После создания функции** - попробуйте отключить элемент

Этот подход сначала создаст функцию, а потом будет её корректно находить и модифицировать.