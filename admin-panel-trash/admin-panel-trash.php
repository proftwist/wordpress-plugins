<?php
/**
 * Plugin Name: Admin Panel Trash
 * Plugin URI: https://bychko.ru/admin-bar-remove/
 * Description: Управление элементами админ-панели WordPress
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Text Domain: admin-panel-trash
 * Domain Path: /languages
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

class AdminPanelTrash {

    private $option_name = 'admin_panel_trash_settings';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_apt_check_file_access', array($this, 'ajax_check_file_access'));
        add_action('wp_ajax_apt_toggle_item', array($this, 'ajax_toggle_item'));
        add_action('wp_ajax_apt_get_items', array($this, 'ajax_get_items'));
        add_action('wp_ajax_apt_get_function_code', array($this, 'ajax_get_function_code'));
        add_action('wp_ajax_apt_test', array($this, 'ajax_test'));
        add_action('wp_ajax_apt_restore_from_backup', array($this, 'ajax_restore_from_backup'));
        add_action('wp_ajax_apt_create_function', array($this, 'ajax_create_function'));
        add_action('wp_ajax_apt_view_backup', array($this, 'ajax_view_backup'));
    }

    /**
     * Загрузка переводов
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'admin-panel-trash',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Добавление пункта меню
     */
    public function add_admin_menu() {
        add_options_page(
            __('Admin Panel Trash', 'admin-panel-trash'),
            __('Admin Panel Trash', 'admin-panel-trash'),
            'manage_options',
            'admin-panel-trash',
            array($this, 'admin_page')
        );
    }

    /**
     * Подключение скриптов и стилей
     */
    public function enqueue_scripts($hook) {
        if ('settings_page_admin-panel-trash' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'admin-panel-trash-js',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Правильная локализация
        wp_localize_script('admin-panel-trash-js', 'apt_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('admin_panel_trash_nonce')
        ));

        // Локализация для текстов
        wp_localize_script('admin-panel-trash-js', 'apt_localize', array(
            'checking' => __('Проверка...', 'admin-panel-trash'),
            'file_path' => __('Путь к файлу:', 'admin-panel-trash'),
            'read_access' => __('Доступ на чтение:', 'admin-panel-trash'),
            'write_access' => __('Доступ на запись:', 'admin-panel-trash'),
            'yes' => __('Да', 'admin-panel-trash'),
            'no' => __('Нет', 'admin-panel-trash'),
            'error' => __('Ошибка', 'admin-panel-trash'),
            'request_error' => __('Ошибка запроса', 'admin-panel-trash'),
            'check_access' => __('Проверить доступ', 'admin-panel-trash'),
            'loading' => __('Загрузка...', 'admin-panel-trash'),
            'load_error' => __('Ошибка загрузки', 'admin-panel-trash'),
            'no_items' => __('Элементы не найдены', 'admin-panel-trash'),
            'enabled' => __('Включен', 'admin-panel-trash'),
            'disabled' => __('Отключен', 'admin-panel-trash'),
            'disable' => __('Отключить', 'admin-panel-trash'),
            'enable' => __('Включить', 'admin-panel-trash'),
            'processing' => __('Обработка...', 'admin-panel-trash')
        ));
    }

    /**
     * Получение элементов админ-панели (обновленный)
     */
    private function get_admin_bar_items() {
        $items = $this->get_admin_bar_items_direct();

        // Если первый способ не сработал, пробуем альтернативный
        if (empty($items)) {
            $items = $this->get_admin_bar_items_alternative();
        }

        // Если все еще пусто, возвращаем тестовые данные
        if (empty($items)) {
            $items = $this->get_sample_items();
        }

        return $items;
    }

    /**
     * Прямое получение через WP_Admin_Bar
     */
    private function get_admin_bar_items_direct() {
        $items = array();

        global $wp_admin_bar;

        // Убедимся что админ-панель инициализирована
        if (!did_action('admin_bar_init')) {
            do_action('admin_bar_init');
        }

        if ($wp_admin_bar && method_exists($wp_admin_bar, 'get_nodes')) {
            $nodes = $wp_admin_bar->get_nodes();

            if ($nodes) {
                foreach ($nodes as $node) {
                    // Пропускаем системные элементы
                    if (in_array($node->id, array('root', 'menu-toggle'))) {
                        continue;
                    }

                    $items[] = array(
                        'id' => $node->id,
                        'name' => $this->format_item_name($node->id),
                        'enabled' => !$this->is_item_disabled($node->id),
                        'title' => $node->title
                    );
                }
            }
        }

        return $items;
    }

    /**
     * Альтернативный метод получения через рендеринг
     */
    private function get_admin_bar_items_alternative() {
        $items = array();

        // Временно рендерим админ-панель чтобы получить её HTML
        ob_start();

        // Создаем экземпляр и рендерим
        global $wp_admin_bar;

        if (null === $wp_admin_bar) {
            require_once(ABSPATH . WPINC . '/class-wp-admin-bar.php');
            $wp_admin_bar = new WP_Admin_Bar();
            $wp_admin_bar->initialize();
            $wp_admin_bar->add_menus();
        }

        $wp_admin_bar->render();
        $html = ob_get_clean();

        // Парсим HTML для получения ID элементов
        if (preg_match_all('/id=[\'"]wp-admin-bar-([^\'"\s]+)[\'"]/', $html, $matches)) {
            foreach ($matches[1] as $item_id) {
                // Пропускаем общие контейнеры
                if (in_array($item_id, array('my-account', 'my-blogs', 'site-name', 'top-secondary'))) {
                    continue;
                }

                $items[] = array(
                    'id' => $item_id,
                    'name' => $this->format_item_name($item_id),
                    'enabled' => !$this->is_item_disabled($item_id),
                    'title' => $this->format_item_name($item_id)
                );
            }
        }

        return $items;
    }

    /**
     * Тестовые данные для отладки
     */
    private function get_sample_items() {
        return array(
            array(
                'id' => 'wp-admin-bar-wp-logo',
                'name' => 'WP Logo',
                'enabled' => true,
                'title' => 'WordPress'
            ),
            array(
                'id' => 'wp-admin-bar-site-name',
                'name' => 'Site Name',
                'enabled' => true,
                'title' => get_bloginfo('name')
            ),
            array(
                'id' => 'wp-admin-bar-my-account',
                'name' => 'My Account',
                'enabled' => true,
                'title' => 'My Account'
            ),
            array(
                'id' => 'wp-admin-bar-logout',
                'name' => 'Logout',
                'enabled' => true,
                'title' => 'Log Out'
            )
        );
    }

    /**
     * Форматирование имени элемента
     */
    private function format_item_name($item_id) {
        return ucfirst(str_replace(array('-', '_'), ' ', $item_id));
    }

    /**
     * Проверка, отключен ли элемент (обновленная)
     */
    private function is_item_disabled($item_id) {
        // Сначала проверяем файл, потом опции как резерв
        $disabled_items_from_file = $this->get_disabled_items_from_file();
        $disabled_items_from_options = get_option($this->option_name, array());

        // Объединяем оба источника для надежности
        $all_disabled_items = array_unique(array_merge(
            $disabled_items_from_file,
            $disabled_items_from_options
        ));

        return in_array($item_id, $all_disabled_items);
    }

    /**
     * Получение пути к functions.php текущей темы
     */
    private function get_functions_file_path() {
        $theme = wp_get_theme();
        return $theme->get_stylesheet_directory() . '/functions.php';
    }

    /**
     * Получение текущего кода функции для отображения
     */
    private function get_current_function_code() {
        $file_path = $this->get_functions_file_path();

        if (!file_exists($file_path) || !is_readable($file_path)) {
            return '';
        }

        $content = file_get_contents($file_path);

        // Ищем полный блок функции с add_action
        if (preg_match('/(add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_items_from_admin_bar[\'"]\s*,\s*99\s*\)\s*;[\s\S]*?function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\})/s', $content, $matches)) {
            return $matches[1];
        }

        // Если не нашли с add_action, ищем просто функцию
        if (preg_match('/(function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\})/s', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

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

    /**
     * Безопасное обновление файла с проверкой
     */
    private function safe_file_update($content) {
        $file_path = $this->get_functions_file_path();

        // Создаем backup
        $backup_path = $file_path . '.backup.' . date('Y-m-d-His');
        if (file_exists($file_path)) {
            copy($file_path, $backup_path);
        }

        // Пытаемся записать
        $result = file_put_contents($file_path, $content);

        if ($result === false) {
            // Восстанавливаем из backup если запись не удалась
            if (file_exists($backup_path)) {
                copy($backup_path, $file_path);
            }
            return false;
        }

        // Удаляем backup если все успешно
        if (file_exists($backup_path)) {
            unlink($backup_path);
        }

        return true;
    }

    /**
     * Улучшенный поиск функции
     */
    private function find_existing_function($content) {
        // Пробуем несколько вариантов регулярных выражений
        $patterns = [
            // Стандартный формат
            '/add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_items_from_admin_bar[\'"]\s*,\s*99\s*\)\s*;\s*function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
            // Без add_action
            '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
            // С разными пробелами
            '/function\s*remove_items_from_admin_bar\s*\(\s*\)\s*\{[^}]+\}/s'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Проверка доступа к файлу с отладочной информацией
     */
    public function ajax_check_file_access() {
        $this->check_ajax_permissions();

        $file_path = $this->get_functions_file_path();
        $debug_info = $this->get_file_debug_info();

        $response = array(
            'readable' => is_readable($file_path),
            'writable' => is_writable($file_path),
            'path' => $file_path,
            'debug_info' => $debug_info
        );

        wp_send_json_success($response);
    }

    /**
     * AJAX переключение элемента
     */
    public function ajax_toggle_item() {
        $this->check_ajax_permissions();

        $item_id = sanitize_text_field($_POST['item_id']);
        $enable = $_POST['enable'] === 'true';

        // Валидация item_id
        if (empty($item_id)) {
            wp_send_json_error(__('Неверный ID элемента', 'admin-panel-trash'));
        }

        if ($enable) {
            $result = $this->enable_item($item_id);
            $message = $result ?
                __('Элемент включен', 'admin-panel-trash') :
                __('Ошибка при включении элемента', 'admin-panel-trash');
        } else {
            $result = $this->disable_item($item_id);
            $message = $result ?
                __('Элемент отключен', 'admin-panel-trash') :
                __('Ошибка при отключении элемента', 'admin-panel-trash');
        }

        if ($result) {
            // Возвращаем обновленный код функции
            $function_code = $this->get_current_function_code();
            wp_send_json_success(array(
                'message' => $message,
                'function_code' => $function_code
            ));
        } else {
            wp_send_json_error($message);
        }
    }

    /**
     * AJAX получение текущего кода функции
     */
    public function ajax_get_function_code() {
        $this->check_ajax_permissions();
        $function_code = $this->get_current_function_code();
        wp_send_json_success(array('function_code' => $function_code));
    }

    /**
     * Отключение элемента (сохраняем существующие)
     */
    private function disable_item($item_id) {
        // Получаем текущие отключенные элементы ИЗ ФАЙЛА
        $disabled_items = $this->get_disabled_items_from_file();

        // Добавляем новый элемент если его еще нет
        if (!in_array($item_id, $disabled_items)) {
            $disabled_items[] = $item_id;
            sort($disabled_items);

            // Обновляем файл
            return $this->update_disabled_items_in_file($disabled_items);
        }

        return true; // Элемент уже отключен
    }

    /**
     * Включение элемента (сохраняем остальные)
     */
    private function enable_item($item_id) {
        // Получаем текущие отключенные элементы ИЗ ФАЙЛА
        $disabled_items = $this->get_disabled_items_from_file();

        // Удаляем элемент из массива
        $disabled_items = array_values(array_diff($disabled_items, array($item_id)));

        // Обновляем файл
        return $this->update_disabled_items_in_file($disabled_items);
    }

    /**
     * Генерация кода для удаления элементов
     */
    private function generate_remove_code($item_ids) {
        if (empty($item_ids)) {
            return "// Admin Panel Trash - No items to remove";
        }

        $items_code = '';
        foreach ($item_ids as $item_id) {
            $items_code .= "    \$wp_admin_bar->remove_menu( '$item_id' );\n";
        }

        return "add_action( 'wp_before_admin_bar_render', 'remove_items_from_admin_bar', 99 );\nfunction remove_items_from_admin_bar() {\n    global \$wp_admin_bar;\n" . $items_code . "}";
    }

    /**
     * Получение ВСЕХ отключенных элементов ИЗ ФАЙЛА (основной источник)
     */
    private function get_disabled_items_from_file() {
        $file_path = $this->get_functions_file_path();

        if (!file_exists($file_path) || !is_readable($file_path)) {
            return array();
        }

        $content = file_get_contents($file_path);
        $disabled_items = array();

        // Ищем функцию remove_items_from_admin_bar
        $function_pattern = '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{([^}]+)\}/s';
        if (preg_match($function_pattern, $content, $function_match)) {
            $function_body = $function_match[1];
            // Ищем все вызовы remove_menu в теле функции
            if (preg_match_all('/\$wp_admin_bar->remove_menu\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*;/', $function_body, $matches)) {
                $disabled_items = $matches[1];
            }
        }

        // Всегда синхронизируем с опциями WordPress
        $current_options = get_option($this->option_name, array());
        if ($disabled_items != $current_options) {
            update_option($this->option_name, $disabled_items);
        }

        return $disabled_items;
    }

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
     * Замена существующей функции или добавление новой
     */
    private function replace_or_add_function($content, $disabled_items) {
        // Удаляем существующую функцию (если есть)
        $content = $this->remove_existing_function_safe($content);

        // Добавляем новую функцию с ВСЕМИ элементами
        if (!empty($disabled_items)) {
            $content = $this->add_new_function($content, $disabled_items);
        }

        return $content;
    }

    /**
     * Безопасное удаление существующей функции (сохраняем элементы)
     */
    private function remove_existing_function_safe($content) {
        // Сначала извлекаем текущие элементы
        $current_items = $this->get_disabled_items_from_file_content($content);

        // Сохраняем их в опциях на всякий случай
        if (!empty($current_items)) {
            update_option($this->option_name . '_backup', $current_items);
        }

        // Удаляем функцию
        $patterns = [
            // add_action и функция
            '/add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_items_from_admin_bar[\'"]\s*,\s*99\s*\)\s*;[\s\r\n]*function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
            // только функция
            '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s'
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        // Убираем лишние пустые строки
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Обновление отключенных элементов в файле (БЕЗ потери существующих)
     */
    private function update_disabled_items_in_file($new_disabled_items) {
        $file_path = $this->get_functions_file_path();

        if (!file_exists($file_path) || !is_writable($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);

        // Получаем ТЕКУЩИЕ элементы из файла
        $current_disabled_items = $this->get_disabled_items_from_file_content($content);

        // Объединяем старые и новые элементы (убираем дубли)
        $all_disabled_items = array_unique(array_merge($current_disabled_items, $new_disabled_items));

        sort($all_disabled_items);

        // Полностью перезаписываем функцию
        $content = $this->replace_or_add_function($content, $all_disabled_items);

        $result = file_put_contents($file_path, $content) !== false;

        if ($result) {
            // Обновляем опции
            update_option($this->option_name, $all_disabled_items);
        }

        return $result;
    }

    /**
     * Безопасное пересоздание функции (сохраняем элементы)
     */
    public function ajax_cleanup_function() {
        $this->check_ajax_permissions();

        // Получаем текущие элементы ИЗ ФАЙЛА
        $current_items = $this->get_disabled_items_from_file();

        if (empty($current_items)) {
            wp_send_json_error(__('Нет элементов для сохранения', 'admin-panel-trash'));
        }

        // Создаем backup текущих элементов
        update_option($this->option_name . '_backup', $current_items);

        // Пересоздаем функцию
        $file_path = $this->get_functions_file_path();
        $content = file_get_contents($file_path);

        // Удаляем старую функцию и добавляем новую
        $content = $this->remove_existing_function_safe($content);
        $content = $this->add_new_function($content, $current_items);

        if (file_put_contents($file_path, $content) !== false) {
            wp_send_json_success(array(
                'message' => __('Функция пересоздана с сохранением элементов', 'admin-panel-trash'),
                'items_preserved' => $current_items,
                'items_count' => count($current_items)
            ));
        } else {
            wp_send_json_error(__('Ошибка при пересоздании функции', 'admin-panel-trash'));
        }
    }

    /**
     * Восстановление элементов из backup
     */
    public function ajax_restore_from_backup() {
        $this->check_ajax_permissions();

        $backup_items = get_option($this->option_name . '_backup', array());

        if (empty($backup_items)) {
            wp_send_json_error(__('Backup не найден', 'admin-panel-trash'));
        }

        if ($this->update_disabled_items_in_file($backup_items)) {
            wp_send_json_success(array(
                'message' => __('Элементы восстановлены из backup', 'admin-panel-trash'),
                'restored_items' => $backup_items,
                'count' => count($backup_items)
            ));
        } else {
            wp_send_json_error(__('Ошибка при восстановлении из backup', 'admin-panel-trash'));
        }
    }

    /**
     * Просмотр backup
     */
    public function ajax_view_backup() {
        $this->check_ajax_permissions();

        $backup_items = get_option($this->option_name . '_backup', array());
        $current_items = $this->get_disabled_items_from_file();

        wp_send_json_success(array(
            'backup_items' => $backup_items,
            'current_items' => $current_items,
            'backup_count' => count($backup_items),
            'current_count' => count($current_items)
        ));
    }

    /**
     * Обновление существующей функции
     */
    private function update_existing_function($content, $disabled_items) {
        if (empty($disabled_items)) {
            // Если нет отключенных элементов, удаляем всю функцию
            $content = preg_replace(
                '/add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_items_from_admin_bar[\'"]\s*,\s*99\s*\)\s*;\s*function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}\s*/s',
                '',
                $content
            );
        } else {
            // Создаем новое тело функции
            $new_function_body = "function remove_items_from_admin_bar() {\n    global \$wp_admin_bar;\n";
            foreach ($disabled_items as $item_id) {
                $new_function_body .= "    \$wp_admin_bar->remove_menu( '$item_id' );\n";
            }
            $new_function_body .= "}";

            // Заменяем старую функцию на новую
            $content = preg_replace(
                '/function\s+remove_items_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}/s',
                $new_function_body,
                $content
            );
        }

        return $content;
    }

    /**
     * Добавление новой функции
     */
    private function add_new_function($content, $disabled_items) {
        if (empty($disabled_items)) {
            return $content; // Нечего добавлять
        }

        // Создаем полный код функции с add_action
        $new_code = "\n\n// Убираем лишние пункты из админ бара\nadd_action( 'wp_before_admin_bar_render', 'remove_items_from_admin_bar', 99 );\n";
        $new_code .= "function remove_items_from_admin_bar() {\n    global \$wp_admin_bar;\n";
        foreach ($disabled_items as $item_id) {
            $new_code .= "    \$wp_admin_bar->remove_menu( '$item_id' );\n";
        }
        $new_code .= "}";

        // Удаляем закрывающий PHP тег если есть
        $content = preg_replace('/\?>\s*$/', '', $content);

        // Добавляем наш код
        $content .= $new_code . "\n";

        // Возвращаем закрывающий тег если он был
        if (strpos($content, '<?php') !== false) {
            $content .= "\n?>";
        }

        return $content;
    }

    /**
     * AJAX получение элементов
     */
    public function ajax_get_items() {
        $this->check_ajax_permissions();

        try {
            $items = $this->get_admin_bar_items();

            // Логируем для отладки
            error_log('Admin Panel Trash: Found ' . count($items) . ' items');

            wp_send_json_success($items);

        } catch (Exception $e) {
            error_log('Admin Panel Trash Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Проверка прав AJAX
     */
    private function check_ajax_permissions() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Недостаточно прав', 'admin-panel-trash'));
        }
    }

    /**
     * Тестовый AJAX для проверки
     */
    public function ajax_test() {
        $this->check_ajax_permissions();
        wp_send_json_success(array(
            'message' => 'AJAX работает!',
            'timestamp' => current_time('mysql'),
            'items_count' => count($this->get_admin_bar_items())
        ));
    }

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
                    - В backup сохранено <?php echo $backup_count; ?> элементов, которые были в файле ранее.
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

            <?php if (!empty($current_function_code)): ?>
            <div class="card">
                <h2><?php _e('Текущий код в functions.php', 'admin-panel-trash'); ?></h2>
                <details>
                    <summary style="cursor: pointer; color: #0073aa; margin-bottom: 10px;">
                        <?php _e('Показать/скрыть код функции', 'admin-panel-trash'); ?>
                    </summary>
                    <pre style="background: #f6f7f7; padding: 15px; border: 1px solid #ccd0d4; overflow: auto; max-height: 300px;"><code><?php echo esc_html($current_function_code); ?></code></pre>
                </details>
                <button id="apt-refresh-function" class="button">
                    <?php _e('Обновить код функции', 'admin-panel-trash'); ?>
                </button>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php _e('Элементы админ-панели', 'admin-panel-trash'); ?></h2>
                <p><?php _e('Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы.', 'admin-panel-trash'); ?></p>

                <table class="wp-list-table widefat fixed striped" id="apt-items-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID элемента', 'admin-panel-trash'); ?></th>
                            <th><?php _e('Название', 'admin-panel-trash'); ?></th>
                            <th><?php _e('Статус', 'admin-panel-trash'); ?></th>
                            <th><?php _e('Действия', 'admin-panel-trash'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="apt-items-list">
                        <tr>
                            <td colspan="4"><?php _e('Загрузка...', 'admin-panel-trash'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <button id="apt-refresh-items" class="button" style="margin-top: 10px;">
                    <?php _e('Обновить список', 'admin-panel-trash'); ?>
                </button>
            </div>
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
}

new AdminPanelTrash();