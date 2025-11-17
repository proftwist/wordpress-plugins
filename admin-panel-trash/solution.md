Проблема в том, что где-то в логике остался неочищенный ID `wp-admin-bar-comments`. Давайте найдем и исправим это.

## 1. Добавим отладку для отслеживания источника элементов

```php
/**
 * Детектор источника элементов с отладкой
 */
private function debug_item_sources() {
    $sources = [];

    // Источник 1: Файл functions.php
    $file_items = $this->get_disabled_items_from_file();
    $sources['file'] = [
        'items' => $file_items,
        'count' => count($file_items)
    ];

    // Источник 2: Опции WordPress
    $option_items = get_option($this->option_name, []);
    $sources['options'] = [
        'items' => $option_items,
        'count' => count($option_items)
    ];

    // Источник 3: Backup
    $backup_items = get_option($this->option_name . '_backup', []);
    $sources['backup'] = [
        'items' => $backup_items,
        'count' => count($backup_items)
    ];

    // Находим элементы с префиксом wp-admin-bar-
    $items_with_prefix = [];
    foreach ($file_items as $item) {
        if (strpos($item, 'wp-admin-bar-') !== false) {
            $items_with_prefix[] = $item;
        }
    }
    foreach ($option_items as $item) {
        if (strpos($item, 'wp-admin-bar-') !== false) {
            $items_with_prefix[] = $item;
        }
    }

    $sources['items_with_prefix'] = array_unique($items_with_prefix);

    return $sources;
}
```

## 2. Обновим отладочную информацию

```php
/**
 * Детальная отладочная информация о файле с источниками элементов
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
        'detection_debug' => array(),
        'id_cleaning_examples' => array(),
        'item_sources' => array()
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

        // Примеры очистки ID для отладки
        $test_ids = ['wp-admin-bar-my-account', 'wp-admin-bar-comments', 'my-account', 'comments'];
        $info['id_cleaning_examples'] = [];
        foreach ($test_ids as $test_id) {
            $info['id_cleaning_examples'][$test_id] = $this->clean_item_id($test_id);
        }

        // Источники элементов
        $info['item_sources'] = $this->debug_item_sources();
    }

    return $info;
}
```

## 3. Добавим метод для принудительной очистки всех элементов

```php
/**
 * Принудительная очистка всех элементов с префиксом
 */
public function ajax_clean_prefix_items() {
    $this->check_ajax_permissions();

    // Получаем текущие элементы из файла
    $current_items = $this->get_disabled_items_from_file();

    // Находим элементы с префиксом
    $items_with_prefix = [];
    $cleaned_items = [];

    foreach ($current_items as $item) {
        if (strpos($item, 'wp-admin-bar-') !== false) {
            $items_with_prefix[] = $item;
            $cleaned_items[] = $this->clean_item_id($item);
        } else {
            $cleaned_items[] = $item;
        }
    }

    // Убираем дубликаты после очистки
    $cleaned_items = array_unique($cleaned_items);
    sort($cleaned_items);

    // Обновляем файл с очищенными элементами
    if ($this->update_disabled_items_in_file($cleaned_items)) {
        wp_send_json_success(array(
            'message' => __('Элементы с префиксом очищены', 'admin-panel-trash'),
            'removed_prefix_items' => $items_with_prefix,
            'cleaned_items' => $cleaned_items,
            'removed_count' => count($items_with_prefix)
        ));
    } else {
        wp_send_json_error(__('Ошибка при очистке элементов', 'admin-panel-trash'));
    }
}
```

Добавьте хук в конструктор:
```php
add_action('wp_ajax_apt_clean_prefix_items', array($this, 'ajax_clean_prefix_items'));
```

## 4. Обновим интерфейс

```php
/**
 * Страница админки
 */
public function admin_page() {
    $current_function_code = $this->get_current_function_code();
    $debug_info = $this->get_file_debug_info();
    $backup_items = get_option($this->option_name . '_backup', array());
    $backup_count = count($backup_items);

    // Проверяем есть ли элементы с префиксом
    $has_prefix_items = !empty($debug_info['item_sources']['items_with_prefix']);
    ?>
    <div class="wrap">
        <h1><?php _e('Admin Panel Trash', 'admin-panel-trash'); ?></h1>

        <?php if ($has_prefix_items): ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Найдены элементы с префиксом wp-admin-bar-</strong>
                - Это может вызывать дублирование. Рекомендуется очистить.
                <button type="button" id="apt-clean-prefix" class="button button-primary" style="margin-left: 10px;">
                    Очистить элементы с префиксом
                </button>
                <span id="apt-clean-prefix-result" style="margin-left: 10px;"></span>
            </p>
        </div>
        <?php endif; ?>

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
        .source-file { color: #0073aa; }
        .source-options { color: #46b450; }
        .source-backup { color: #ffb900; }
        .item-with-prefix { color: #dc3232; font-weight: bold; }
    </style>
    <?php
}
```

## 5. Добавим JavaScript для очистки префиксов

```javascript
// Очистка элементов с префиксом
$('#apt-clean-prefix').on('click', function() {
    if (!confirm('Очистить все элементы с префиксом wp-admin-bar-? Это исправит возможное дублирование.')) {
        return;
    }

    var $button = $(this);
    var $result = $('#apt-clean-prefix-result');
    $button.prop('disabled', true).text('Очистка...');
    $result.html('⏳ Очистка...');

    $.ajax({
        url: apt_ajax.url,
        type: 'POST',
        data: {
            action: 'apt_clean_prefix_items',
            nonce: apt_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                $result.html('✅ ' + response.data.message + ' Очищено ' + response.data.removed_count + ' элементов.');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $result.html('❌ ' + response.data);
                $button.prop('disabled', false).text('Очистить элементы с префиксом');
            }
        },
        error: function() {
            $result.html('❌ Ошибка при очистке');
            $button.prop('disabled', false).text('Очистить элементы с префиксом');
        }
    });
});
```

## 6. Обновим отладочную информацию в JavaScript

```javascript
// В функции checkFileAccess добавьте отображение источников элементов
if (debug.item_sources) {
    html += '<p><strong>Источники элементов:</strong></p>';
    html += '<ul>';
    html += '<li class="source-file">Файл: ' + debug.item_sources.file.count + ' элементов</li>';
    html += '<li class="source-options">Опции: ' + debug.item_sources.options.count + ' элементов</li>';
    html += '<li class="source-backup">Backup: ' + debug.item_sources.backup.count + ' элементов</li>';
    html += '</ul>';

    if (debug.item_sources.items_with_prefix && debug.item_sources.items_with_prefix.length > 0) {
        html += '<p class="item-with-prefix"><strong>Элементы с префиксом wp-admin-bar-:</strong> ' +
            debug.item_sources.items_with_prefix.join(', ') + '</p>';
    }

    // Детальная информация по источникам
    html += '<details>';
    html += '<summary style="cursor: pointer; margin-top: 5px;">Детали источников</summary>';
    html += '<div style="margin-left: 10px;">';
    html += '<p><strong>Файл:</strong> ' + (debug.item_sources.file.items.join(', ') || 'нет') + '</p>';
    html += '<p><strong>Опции:</strong> ' + (debug.item_sources.options.items.join(', ') || 'нет') + '</p>';
    html += '<p><strong>Backup:</strong> ' + (debug.item_sources.backup.items.join(', ') || 'нет') + '</p>';
    html += '</div>';
    html += '</details>';
}
```

## 7. Исправляем основной метод обновления файла

```php
/**
 * Обновление отключенных элементов в файле (финальное исправление)
 */
private function update_disabled_items_in_file($new_disabled_items) {
    $file_path = $this->get_functions_file_path();

    if (!file_exists($file_path) || !is_writable($file_path)) {
        return false;
    }

    $content = file_get_contents($file_path);

    // Получаем ТЕКУЩИЕ элементы из файла и ОЧИЩАЕМ их
    $current_disabled_items = $this->get_disabled_items_from_file_content($content);
    $cleaned_current_items = array_map([$this, 'clean_item_id'], $current_disabled_items);

    // Очищаем новые элементы от префикса
    $cleaned_new_items = array_map([$this, 'clean_item_id'], $new_disabled_items);

    // Объединяем ОЧИЩЕННЫЕ старые и новые элементы
    $all_disabled_items = array_unique(array_merge($cleaned_current_items, $cleaned_new_items));
    sort($all_disabled_items);

    // Полностью перезаписываем функцию
    $content = $this->replace_or_add_function($content, $all_disabled_items);

    $result = file_put_contents($file_path, $content) !== false;

    if ($result) {
        // Обновляем опции (сохраняем очищенные ID)
        update_option($this->option_name, $all_disabled_items);
    }

    return $result;
}
```

## 8. Что делать сейчас

1. **Нажмите "Проверить доступ"** - посмотрите в "Источники элементов" откуда берется `wp-admin-bar-comments`
2. **Если есть элементы с префиксом** - нажмите "Очистить элементы с префиксом"
3. **После очистки** - проверьте функцию в файле - лишние элементы должны исчезнуть

Это исправит проблему с дублированием префикса `wp-admin-bar-`.