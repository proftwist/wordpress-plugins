<?php
/**
 * Plugin Name: Admin Panel Trash
 * Description: Управление элементами верхней панели WordPress
 * Version: 2.0.0
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: admin-panel-trash
 * Domain Path: /languages
 *
 * @package AdminPanelTrash
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

// Определение констант
define('ADMIN_PANEL_TRASH_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ADMIN_PANEL_TRASH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADMIN_PANEL_TRASH_PLUGIN_VERSION', '2.0.0');

/**
 * Основной класс плагина Admin Panel Trash
 */
class AdminPanelTrash {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Регистрируем AJAX обработчики для авторизованных пользователей
        add_action('wp_ajax_apt_check_file_access', array($this, 'ajax_check_file_access'));
        add_action('wp_ajax_apt_toggle_item', array($this, 'ajax_toggle_item'));
        add_action('wp_ajax_apt_get_items', array($this, 'ajax_get_items'));
        add_action('wp_ajax_apt_get_function_code', array($this, 'ajax_get_function_code'));
        add_action('wp_ajax_apt_cleanup_function', array($this, 'ajax_cleanup_function'));
    }

    /**
     * Инициализация плагина
     *
     * Подключает необходимые файлы и инициализирует менеджер ресурсов.
     */
    public function init() {
        // Подключаем файл менеджера ресурсов
        require_once ADMIN_PANEL_TRASH_PLUGIN_PATH . 'includes/class-assets-manager.php';

        // Инициализируем менеджер ресурсов
        AdminPanelTrash_Assets_Manager::get_instance();
    }

    /**
     * Загрузка текстового домена для интернационализации
     *
     * Подключает файлы переводов из папки languages плагина.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'admin-panel-trash',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Добавление страницы настроек в меню админ-панели
     *
     * Регистрирует страницу плагина в разделе "Настройки" административной панели.
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

    public function admin_page() {
        // Проверяем права доступа к functions.php
        $functions_file = get_stylesheet_directory() . '/functions.php';
        $is_writable = file_exists($functions_file) ? is_writable($functions_file) : is_writable(get_stylesheet_directory());

        if (!$is_writable) {
            echo '<div class="notice notice-error"><p>';
            _e('Внимание: Файл functions.php вашей темы недоступен для записи. Плагин не сможет сохранять изменения.', 'admin-panel-trash');
            echo '</p></div>';
        }

        // Получаем элементы для отображения
        $items = $this->get_admin_bar_items_for_display();
        ?>
        <div class="wrap">
            <h1><?php _e('Admin Panel Trash', 'admin-panel-trash'); ?></h1>

            <div class="card">
                <h2><?php _e('Проверка доступа к файлу', 'admin-panel-trash'); ?></h2>
                <p><?php _e('Проверьте, доступен ли файл functions.php текущей темы для записи:', 'admin-panel-trash'); ?></p>
                <button id="apt-check-access" class="button button-primary">
                    <?php _e('Проверить доступ', 'admin-panel-trash'); ?>
                </button>
                <div id="apt-access-result" style="margin-top: 10px;"></div>
            </div>

            <div class="card">
                <h2><?php _e('Элементы админ-панели', 'admin-panel-trash'); ?></h2>
                <p><?php _e('Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы.', 'admin-panel-trash'); ?></p>

                <button id="apt-refresh-items" class="button button-secondary">
                    <?php _e('Обновить список', 'admin-panel-trash'); ?>
                </button>

                <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
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
            </div>

        </div>
        <?php
    }

    /**
     * AJAX: Проверка доступа к файлу functions.php
     *
     * Проверяет права доступа к файлу functions.php активной темы
     * и возвращает информацию о доступности файла.
     */
    public function ajax_check_file_access() {
        // Проверяем nonce для безопасности
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $file_path = get_stylesheet_directory() . '/functions.php';

        // Проверяем права доступа к файлу
        $response = array(
            'file_path' => $file_path,
            'readable' => is_readable($file_path),
            'writable' => is_writable($file_path)
        );

        wp_send_json_success($response);
    }

    /**
     * AJAX: Переключение состояния элемента админ-панели
     *
     * Обрабатывает запросы на включение/отключение элементов верхней панели администратора.
     * Сохраняет настройки в базу данных и обновляет файл functions.php темы.
     */
    public function ajax_toggle_item() {
        // Проверяем nonce для безопасности
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        // Получаем и валидируем входные данные
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        $enable = $_POST['enable'] === 'true';

        // Проверяем корректность ID элемента
        if (empty($item_id)) {
            wp_send_json_error(__('Invalid item ID', 'admin-panel-trash'));
        }

        // Получаем текущие настройки из базы данных
        $settings = get_option('admin_panel_trash_settings', array());

        // Очищаем ID от префикса для хранения
        $cleaned_id = $this->clean_item_id($item_id);

        // Обновляем настройки в зависимости от действия
        if ($enable) {
            // Включаем элемент - удаляем из списка отключенных
            $settings = array_diff($settings, array($cleaned_id));
        } else {
            // Отключаем элемент - добавляем в список отключенных
            if (!in_array($cleaned_id, $settings, true)) {
                $settings[] = $cleaned_id;
            }
        }

        // Сохраняем обновленные настройки
        update_option('admin_panel_trash_settings', $settings);

        // Обновляем файл functions.php активной темы
        $update_result = $this->update_functions_file($settings);

        // Возвращаем результат операции
        if ($update_result) {
            wp_send_json_success(array(
                'message' => $enable ? __('Item enabled', 'admin-panel-trash') : __('Item disabled', 'admin-panel-trash')
            ));
        } else {
            wp_send_json_error(__('Failed to update functions.php file', 'admin-panel-trash'));
        }
    }

    /**
     * AJAX: Получение списка элементов админ-панели
     *
     * Возвращает массив всех элементов админ-панели с их статусом включения/отключения.
     */
    public function ajax_get_items() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $items = $this->get_admin_bar_items_for_display();
        wp_send_json_success($items);
    }

    /**
     * AJAX: Получение сгенерированного кода функции
     *
     * Возвращает PHP код функции для отключения выбранных элементов админ-панели.
     */
    public function ajax_get_function_code() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $code = $this->generate_function_code();
        wp_send_json_success(array('code' => $code));
    }

    /**
     * AJAX: Очистка функции из файла functions.php
     *
     * Удаляет функцию плагина из файла functions.php и очищает настройки.
     */
    public function ajax_cleanup_function() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $this->update_functions_file(array());
        update_option('admin_panel_trash_settings', array());

        wp_send_json_success(array('message' => __('Function cleaned up', 'admin-panel-trash')));
    }

    /**
     * Получение элементов админ-бара для отображения в интерфейсе
     *
     * Собирает полный список элементов админ-панели, включая их статус
     * и информацию для отображения в административном интерфейсе.
     *
     * @return array Массив элементов с информацией для отображения
     */
    private function get_admin_bar_items_for_display() {
        $items = array();
        $disabled_items = get_option('admin_panel_trash_settings', array());

        // Получаем все элементы админ-панели
        $admin_bar_items = $this->get_all_admin_bar_items();

        // Получаем отключенные элементы из файла functions.php
        $file_disabled_items = $this->get_disabled_items_from_file();

        // Объединяем списки отключенных элементов
        $all_disabled_items = array_unique(array_merge($disabled_items, $file_disabled_items));

        // Если есть расхождения, обновляем настройки
        if ($file_disabled_items != $disabled_items) {
            update_option('admin_panel_trash_settings', $all_disabled_items);
            $disabled_items = $all_disabled_items;
        }

        // Создаем элементы из админ-панели
        foreach ($admin_bar_items as $item) {
            $cleaned_id = $this->clean_item_id($item['id']);
            $is_disabled = in_array($cleaned_id, $disabled_items);

            $items[] = array(
                'id' => $item['id'],
                'cleaned_id' => $cleaned_id,
                'display_id' => $cleaned_id, // ID для отображения (без префикса)
                'name' => $item['title'],
                'title' => $item['title'],
                'enabled' => !$is_disabled,
                'status' => $is_disabled ? 'disabled' : 'enabled'
            );
        }

        // Добавляем элементы из файла, которых нет в текущей админ-панели
        foreach ($file_disabled_items as $file_item) {
            $found = false;
            foreach ($items as $item) {
                if ($item['cleaned_id'] === $file_item) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $items[] = array(
                    'id' => 'wp-admin-bar-' . $file_item,
                    'cleaned_id' => $file_item,
                    'display_id' => $file_item, // ID для отображения (без префикса)
                    'name' => $file_item . ' (только в файле)',
                    'title' => $file_item . ' (только в файле)',
                    'enabled' => false,
                    'status' => 'disabled'
                );
            }
        }

        return $items;
    }

    /**
     * Получение всех элементов админ-панели WordPress
     *
     * Собирает полный список элементов верхней панели администратора,
     * включая стандартные элементы и динамически добавленные плагинами.
     *
     * @return array Массив элементов админ-панели с их свойствами
     */
    private function get_all_admin_bar_items() {
        $items = array();

        // Список стандартных элементов WordPress которые всегда есть
        $standard_items = array(
            'wp-logo' => 'Логотип WordPress',
            'site-name' => 'Название сайта',
            'dashboard' => 'Консоль',
            'appearance' => 'Внешний вид',
            'updates' => 'Обновления',
            'comments' => 'Комментарии',
            'new-content' => 'Добавить',
            'edit' => 'Редактировать',
            'user-info' => 'Информация пользователя',
            'user-actions' => 'Действия пользователя',
            'search' => 'Поиск',
            'my-account' => 'Мой аккаунт',
            'logout' => 'Выйти',
            'menu-toggle' => 'Переключение меню',
            'my-sites' => 'Мои сайты',
            'get-shortlink' => 'Получить короткую ссылку',
            'edit-profile' => 'Редактировать профиль'
        );

        // Сначала добавляем стандартные элементы
        foreach ($standard_items as $id => $title) {
            $full_id = 'wp-admin-bar-' . $id;
            $items[] = array(
                'id' => $full_id,
                'title' => $title,
                'href' => '',
                'parent' => ''
            );
        }

        // Затем добавляем элементы из текущего контекста
        global $wp_admin_bar;
        $original_admin_bar = isset($wp_admin_bar) ? $wp_admin_bar : null;

        // Создаем временный admin bar для сбора элементов
        require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
        $wp_admin_bar = new WP_Admin_Bar();

        // Собираем все возможные элементы
        do_action('admin_bar_menu', $wp_admin_bar);
        do_action('wp_before_admin_bar_render', $wp_admin_bar);

        $nodes = $wp_admin_bar->get_nodes();
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                // Проверяем, нет ли уже такого элемента
                $exists = false;
                foreach ($items as $existing_item) {
                    if ($existing_item['id'] === $node->id) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $items[] = array(
                        'id' => $node->id,
                        'title' => wp_strip_all_tags($node->title) ?: $node->id,
                        'href' => $node->href,
                        'parent' => $node->parent
                    );
                }
            }
        }

        // Восстанавливаем исходный admin bar
        if ($original_admin_bar) {
            $wp_admin_bar = $original_admin_bar;
        }

        return $items;
    }

    /**
     * Очистка ID элемента от префикса wp-admin-bar-
     *
     * Удаляет стандартный префикс WordPress из ID элементов админ-панели
     * для более удобного хранения и отображения.
     *
     * @param string $item_id Полный ID элемента с префиксом
     * @return string Очищенный ID без префикса
     */
    private function clean_item_id($item_id) {
        if (strpos($item_id, 'wp-admin-bar-') === 0) {
            $item_id = substr($item_id, 13);
        }
        return $item_id;
    }

    /**
     * Получение списка отключенных элементов из файла functions.php активной темы
     *
     * Анализирует содержимое файла functions.php для поиска ранее отключенных элементов
     * админ-панели и возвращает их список.
     *
     * @return array Массив ID отключенных элементов
     */
    private function get_disabled_items_from_file() {
        $file_path = get_stylesheet_directory() . '/functions.php';

        // Проверяем доступность файла
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return array();
        }

        $content = file_get_contents($file_path);
        $disabled_items = array();

        // Ищем функцию remove_item_from_admin_bar с любым содержимым
        if (preg_match('/function\s+remove_item_from_admin_bar\s*\([^)]*\)\s*\{([^}]+)\}/s', $content, $function_match)) {
            $function_body = $function_match[1];

            // Извлекаем все ID элементов из вызовов remove_menu
            if (preg_match_all('/\$wp_admin_bar->remove_menu\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*;/', $function_body, $matches)) {
                $disabled_items = $matches[1];
            }
        }

        return $disabled_items;
    }

    /**
     * Обновление файла functions.php активной темы
     *
     * Добавляет или обновляет функцию remove_item_from_admin_bar в файле functions.php
     * для отключения указанных элементов админ-панели.
     *
     * @param array $disabled_items Массив ID элементов для отключения
     * @return bool True при успешном обновлении, false в случае ошибки
     */
    private function update_functions_file($disabled_items) {
        $file_path = get_stylesheet_directory() . '/functions.php';

        // Проверяем права на запись
        if (!is_writable($file_path) && !is_writable(dirname($file_path))) {
            return false;
        }

        // Читаем существующий файл или создаем новый
        $content = file_exists($file_path) ? file_get_contents($file_path) : "<?php\n";

        // Удаляем закрывающий тег PHP если присутствует
        $content = preg_replace('/\?>\s*$/', '', $content);

        // Удаляем существующие функции плагина
        $content = preg_replace('/\/\*\s*Admin Panel Trash Start\s*\*\/.*?\/\*\s*Admin Panel Trash End\s*\*\//s', '', $content);
        $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^)]*\)\s*\{[^}]+\}\s*add_action\s*\(\s*[\'"]wp_before_admin_bar_render[\'"]\s*,\s*[\'"]remove_item_from_admin_bar[\'"]\s*\)\s*;/s', '', $content);

        // Очищаем лишние пустые строки
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        $content = trim($content);

        // Добавляем функцию если есть отключенные элементы
        if (!empty($disabled_items)) {
            $function_code = $this->generate_function_code($disabled_items);
            $content .= "\n\n" . $function_code . "\n";
        }

        // Добавляем закрывающий тег PHP
        $content .= "\n?>";

        // Записываем файл
        $result = file_put_contents($file_path, $content);

        return $result !== false;
    }

    /**
     * Генерация PHP кода функции для отключения элементов админ-панели
     *
     * Создает функцию remove_item_from_admin_bar с вызовами remove_menu()
     * для каждого отключенного элемента.
     *
     * @param array|null $disabled_items Массив ID элементов для отключения
     * @return string Сгенерированный PHP код
     */
    private function generate_function_code($disabled_items = null) {
        if ($disabled_items === null) {
            $disabled_items = get_option('admin_panel_trash_settings', array());
        }

        $code = "/* Admin Panel Trash Start */\n";
        $code .= "function remove_item_from_admin_bar() {\n";
        $code .= "    global \$wp_admin_bar;\n";
        $code .= "    if (!is_admin_bar_showing()) return;\n\n";

        foreach ($disabled_items as $item) {
            $code .= "    \$wp_admin_bar->remove_menu('{$item}');\n";
        }

        $code .= "}\n";
        $code .= "add_action('wp_before_admin_bar_render', 'remove_item_from_admin_bar');\n";
        $code .= "/* Admin Panel Trash End */";

        return $code;
    }
}

// Инициализация плагина
AdminPanelTrash::get_instance();