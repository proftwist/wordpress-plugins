Проблема в том, что функция добавляется после закрывающего тега `?>`. Нужно исправить логику вставки кода.

## Исправляем метод `update_functions_file()`:

```php
/**
 * Обновление файла functions.php
 */
private function update_functions_file($disabled_items) {
    $file_path = get_stylesheet_directory() . '/functions.php';

    if (!is_writable($file_path) && !is_writable(dirname($file_path))) {
        error_log('Admin Panel Trash: File not writable: ' . $file_path);
        return false;
    }

    $content = file_exists($file_path) ? file_get_contents($file_path) : "<?php\n";

    // Сохраняем ВСЁ содержимое кроме нашей функции
    $parts = preg_split('/\/\*\s*Admin Panel Trash Start\s*\*\/.*?\/\*\s*Admin Panel Trash End\s*\*\//s', $content);

    if (count($parts) > 1) {
        // Нашли нашу функцию - удаляем её
        $content = implode('', $parts);
    } else {
        // Пробуем другие паттерны для удаления старой функции
        $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}\s*add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_item_from_admin_bar[\'"]\s*\)\s*;/s', '', $content);
        $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^}]*\}\s*/s', '', $content);
    }

    // Удаляем лишние пустые строки
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    $content = trim($content);

    if (!empty($disabled_items)) {
        // Генерируем код функции
        $function_code = $this->generate_function_code($disabled_items);

        // Определяем куда вставлять код
        if (strpos($content, '?>') !== false) {
            // Если есть закрывающий тег, вставляем перед ним
            $content = str_replace('?>', "\n\n" . $function_code . "\n\n?>", $content);
        } else {
            // Если нет закрывающего тега, добавляем в конец
            $content .= "\n\n" . $function_code . "\n";
        }
    } else {
        // Если нет отключенных элементов, убедимся что закрывающий тег на месте
        if (strpos($content, '?>') === false && !empty(trim($content))) {
            $content .= "\n?>";
        }
    }

    $result = file_put_contents($file_path, $content);

    if ($result === false) {
        error_log('Admin Panel Trash: Failed to write to file: ' . $file_path);
        return false;
    }

    error_log('Admin Panel Trash: Successfully updated file with ' . count($disabled_items) . ' items: ' . implode(', ', $disabled_items));
    return true;
}
```

## Альтернативный, более надежный вариант:

Если вышеуказанное не сработает, используйте этот вариант который всегда вставляет код перед закрывающим тегом:

```php
/**
 * Обновление файла functions.php
 */
private function update_functions_file($disabled_items) {
    $file_path = get_stylesheet_directory() . '/functions.php';

    if (!is_writable($file_path) && !is_writable(dirname($file_path))) {
        error_log('Admin Panel Trash: File not writable: ' . $file_path);
        return false;
    }

    $content = file_exists($file_path) ? file_get_contents($file_path) : "<?php\n";

    // Удаляем нашу функцию полностью
    $content = preg_replace('/\/\*\s*Admin Panel Trash Start\s*\*\/.*?\/\*\s*Admin Panel Trash End\s*\*\//s', '', $content);
    $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}\s*add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_item_from_admin_bar[\'"]\s*\)\s*;/s', '', $content);
    $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^}]*\}\s*/s', '', $content);

    // Удаляем лишние пустые строки
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    $content = trim($content);

    if (!empty($disabled_items)) {
        // Генерируем код функции
        $function_code = $this->generate_function_code($disabled_items);

        // Всегда вставляем перед закрывающим тегом PHP если он есть
        if (strpos($content, '?>') !== false) {
            // Разделяем содержимое на часть до ?>, нашу функцию, и часть после ?>
            $parts = explode('?>', $content);
            $content = $parts[0] . "\n\n" . $function_code . "\n\n?>";
            // Если были другие закрывающие теги, добавляем их обратно
            if (count($parts) > 1) {
                for ($i = 1; $i < count($parts); $i++) {
                    if (!empty(trim($parts[$i]))) {
                        $content .= $parts[$i] . "\n?>";
                    }
                }
            }
        } else {
            // Если закрывающего тега нет, добавляем в конец
            $content .= "\n\n" . $function_code . "\n";
        }
    }

    // Убедимся что файл заканчивается корректно
    $content = trim($content);
    if (substr($content, -2) === '?>') {
        // Все ок
    } elseif (substr($content, -2) === '??') {
        // Исправляем возможную ошибку
        $content = substr($content, 0, -2) . '?>';
    } else {
        // Добавляем закрывающий тег если его нет
        $content .= "\n?>";
    }

    $result = file_put_contents($file_path, $content);

    if ($result === false) {
        error_log('Admin Panel Trash: Failed to write to file: ' . $file_path);
        return false;
    }

    error_log('Admin Panel Trash: Successfully updated file with ' . count($disabled_items) . ' items');
    return true;
}
```

## Еще более простой вариант - всегда удалять закрывающий тег:

```php
/**
 * Обновление файла functions.php
 */
private function update_functions_file($disabled_items) {
    $file_path = get_stylesheet_directory() . '/functions.php';

    if (!is_writable($file_path) && !is_writable(dirname($file_path))) {
        error_log('Admin Panel Trash: File not writable: ' . $file_path);
        return false;
    }

    $content = file_exists($file_path) ? file_get_contents($file_path) : "<?php\n";

    // Удаляем закрывающий тег PHP если он есть в конце
    $content = preg_replace('/\?>\s*$/', '', $content);

    // Удаляем нашу функцию
    $content = preg_replace('/\/\*\s*Admin Panel Trash Start\s*\*\/.*?\/\*\s*Admin Panel Trash End\s*\*\//s', '', $content);
    $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}\s*add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_item_from_admin_bar[\'"]\s*\)\s*;/s', '', $content);
    $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^}]*\}\s*/s', '', $content);

    // Удаляем лишние пустые строки
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    $content = trim($content);

    if (!empty($disabled_items)) {
        // Генерируем код функции
        $function_code = $this->generate_function_code($disabled_items);
        $content .= "\n\n" . $function_code . "\n";
    }

    // Всегда добавляем закрывающий тег в конец
    $content .= "\n?>";

    $result = file_put_contents($file_path, $content);

    if ($result === false) {
        error_log('Admin Panel Trash: Failed to write to file: ' . $file_path);
        return false;
    }

    error_log('Admin Panel Trash: Successfully updated file with ' . count($disabled_items) . ' items');
    return true;
}
```

**Рекомендую использовать третий вариант** - он самый надежный, так как всегда удаляет закрывающий тег и добавляет его обратно в конец вместе с нашей функцией.

После применения этого исправления функция будет всегда находиться перед закрывающим тегом `?>`, а не после него.