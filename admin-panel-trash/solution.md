Отлично, давайте доделаем плагин, чтобы на странице настроек отображалась таблица с элементами админ-панели и работало переключение их состояния.

Вот исправленные файлы:

## 1. Обновленный основной файл плагина (`admin-panel-trash.php`)

```php
<?php
/**
 * Plugin Name: Admin Panel Trash
 * Description: Управление элементами верхней панели WordPress
 * Version: 1.0.0
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
define('ADMIN_PANEL_TRASH_PLUGIN_VERSION', '1.0.0');

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

        // Инициализируем обработчики AJAX для всех пользователей (авторизованных)
        add_action('wp_ajax_apt_check_file_access', array($this, 'ajax_check_file_access'));
        add_action('wp_ajax_apt_toggle_item', array($this, 'ajax_toggle_item'));
        add_action('wp_ajax_apt_get_items', array($this, 'ajax_get_items'));
        add_action('wp_ajax_apt_get_function_code', array($this, 'ajax_get_function_code'));
        add_action('wp_ajax_apt_cleanup_function', array($this, 'ajax_cleanup_function'));
    }

    public function init() {
        // Подключение файлов
        require_once ADMIN_PANEL_TRASH_PLUGIN_PATH . 'includes/class-assets-manager.php';

        // Инициализация обработчиков
        AdminPanelTrash_Assets_Manager::get_instance();
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'admin-panel-trash',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

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

            <div class="card">
                <h2><?php _e('Код функции', 'admin-panel-trash'); ?></h2>
                <p>Текущий код функции в файле functions.php:</p>
                <details id="apt-function-code-block">
                    <summary><?php _e('Показать/скрыть код функции', 'admin-panel-trash'); ?></summary>
                    <pre style="background: #f1f1f1; padding: 15px; border: 1px solid #ddd; overflow: auto; max-height: 300px;"><code><?php echo esc_html($this->generate_function_code()); ?></code></pre>
                </details>
                <button id="apt-refresh-function" class="button button-secondary" style="margin-top: 10px;">
                    <?php _e('Обновить код функции', 'admin-panel-trash'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Проверка доступа к файлу
     */
    public function ajax_check_file_access() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $file_path = get_stylesheet_directory() . '/functions.php';
        $response = array(
            'file_path' => $file_path,
            'readable' => is_readable($file_path),
            'writable' => is_writable($file_path)
        );

        wp_send_json_success($response);
    }

    /**
     * AJAX: Переключение состояния элемента
     */
    public function ajax_toggle_item() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        $enable = $_POST['enable'] === 'true';

        if (empty($item_id)) {
            wp_send_json_error(__('Invalid item ID', 'admin-panel-trash'));
        }

        // Получаем текущие настройки
        $settings = get_option('admin_panel_trash_settings', array());

        // Очищаем ID от префикса для хранения
        $cleaned_id = $this->clean_item_id($item_id);

        if ($enable) {
            // Включаем элемент - удаляем из списка отключенных
            $settings = array_diff($settings, array($cleaned_id));
        } else {
            // Отключаем элемент - добавляем в список отключенных
            if (!in_array($cleaned_id, $settings)) {
                $settings[] = $cleaned_id;
            }
        }

        update_option('admin_panel_trash_settings', $settings);

        // Обновляем файл functions.php
        $this->update_functions_file($settings);

        wp_send_json_success(array(
            'message' => $enable ? __('Item enabled', 'admin-panel-trash') : __('Item disabled', 'admin-panel-trash')
        ));
    }

    /**
     * AJAX: Получение списка элементов
     */
    public function ajax_get_items() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $items = $this->get_admin_bar_items_for_display();
        wp_send_json_success($items);
    }

    /**
     * AJAX: Получение кода функции
     */
    public function ajax_get_function_code() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $code = $this->generate_function_code();
        wp_send_json_success(array('code' => $code));
    }

    /**
     * AJAX: Очистка функции
     */
    public function ajax_cleanup_function() {
        check_ajax_referer('admin_panel_trash_nonce', 'nonce');

        $this->update_functions_file(array());
        update_option('admin_panel_trash_settings', array());

        wp_send_json_success(array('message' => __('Function cleaned up', 'admin-panel-trash')));
    }

    /**
     * Получение элементов админ-бара для отображения
     */
    private function get_admin_bar_items_for_display() {
        $items = array();
        $disabled_items = get_option('admin_panel_trash_settings', array());

        // Получаем все элементы админ-панели
        $admin_bar_items = $this->get_all_admin_bar_items();

        foreach ($admin_bar_items as $item) {
            $cleaned_id = $this->clean_item_id($item['id']);
            $is_disabled = in_array($cleaned_id, $disabled_items);

            $items[] = array(
                'id' => $item['id'],
                'cleaned_id' => $cleaned_id,
                'name' => $item['title'],
                'title' => $item['title'],
                'enabled' => !$is_disabled,
                'status' => $is_disabled ? 'disabled' : 'enabled'
            );
        }

        return $items;
    }

    /**
     * Получение всех элементов админ-панели
     */
    private function get_all_admin_bar_items() {
        global $wp_admin_bar;
        $items = array();

        // Если admin bar не инициализирован, создаем временный
        if (!isset($wp_admin_bar) || !($wp_admin_bar instanceof WP_Admin_Bar)) {
            require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
            $wp_admin_bar = new WP_Admin_Bar();
        }

        // Добавляем основные элементы WordPress
        do_action('admin_bar_menu', $wp_admin_bar);

        $nodes = $wp_admin_bar->get_nodes();
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                $items[] = array(
                    'id' => $node->id,
                    'title' => wp_strip_all_tags($node->title),
                    'href' => $node->href,
                    'parent' => $node->parent
                );
            }
        }

        return $items;
    }

    /**
     * Очистка ID элемента от префикса
     */
    private function clean_item_id($item_id) {
        if (strpos($item_id, 'wp-admin-bar-') === 0) {
            $item_id = substr($item_id, 13);
        }
        return $item_id;
    }

    /**
     * Обновление файла functions.php
     */
    private function update_functions_file($disabled_items) {
        $file_path = get_stylesheet_directory() . '/functions.php';

        if (!is_writable($file_path) && !is_writable(dirname($file_path))) {
            return false;
        }

        $content = file_exists($file_path) ? file_get_contents($file_path) : "<?php\n";

        // Удаляем старую функцию если она существует
        $content = preg_replace('/\/\* Admin Panel Trash Start \*\/.*\/\* Admin Panel Trash End \*\//s', '', $content);
        $content = preg_replace('/function\s+remove_item_from_admin_bar\s*\([^}]*\}\s*/s', '', $content);

        if (!empty($disabled_items)) {
            // Генерируем код функции
            $function_code = $this->generate_function_code($disabled_items);
            $content .= "\n" . $function_code . "\n";
        }

        return file_put_contents($file_path, $content);
    }

    /**
     * Генерация кода функции
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
            $item_id = strpos($item, 'wp-admin-bar-') === 0 ? $item : 'wp-admin-bar-' . $item;
            $code .= "    \$wp_admin_bar->remove_menu('{$item_id}');\n";
        }

        $code .= "}\n";
        $code .= "add_action('wp_before_admin_bar_render', 'remove_item_from_admin_bar');\n";
        $code .= "/* Admin Panel Trash End */";

        return $code;
    }
}

// Инициализация плагина
AdminPanelTrash::get_instance();
```

## 2. Обновленный файл менеджера ресурсов (`includes/class-assets-manager.php`)

```php
<?php
/**
 * Менеджер ресурсов для плагина Admin Panel Trash
 *
 * @package AdminPanelTrash
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс для управления ресурсами (CSS/JS)
 */
class AdminPanelTrash_Assets_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Подключение скриптов и стилей в админке
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на странице настроек плагина
        if ('settings_page_admin-panel-trash' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'admin-panel-trash-admin-js',
            ADMIN_PANEL_TRASH_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            ADMIN_PANEL_TRASH_PLUGIN_VERSION,
            true
        );

        // Локализация для AJAX
        wp_localize_script('admin-panel-trash-admin-js', 'apt_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('admin_panel_trash_nonce')
        ));

        // Локализация текстов
        wp_localize_script('admin-panel-trash-admin-js', 'apt_localize', array(
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
            'disable' => __('Убрать', 'admin-panel-trash'),
            'enable' => __('Вернуть', 'admin-panel-trash'),
            'processing' => __('Обработка...', 'admin-panel-trash'),
            'item_enabled' => __('Элемент включен', 'admin-panel-trash'),
            'item_disabled' => __('Элемент отключен', 'admin-panel-trash'),
            'invalid_item_id' => __('Неверный ID элемента', 'admin-panel-trash'),
            'error_enabling_item' => __('Ошибка при включении элемента', 'admin-panel-trash'),
            'error_disabling_item' => __('Ошибка при отключении элемента', 'admin-panel-trash')
        ));

        wp_enqueue_style(
            'admin-panel-trash-admin-css',
            ADMIN_PANEL_TRASH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ADMIN_PANEL_TRASH_PLUGIN_VERSION
        );
    }
}
```

## 3. Обновленный JavaScript файл (`assets/admin.js`)

```javascript
jQuery(document).ready(function($) {
    console.log('Admin Panel Trash loaded');

    // Загрузка элементов при открытии страницы
    loadAdminBarItems();

    // Проверка доступа к файлу
    $('#apt-check-access').on('click', function() {
        checkFileAccess();
    });

    // Обновление списка элементов
    $('#apt-refresh-items').on('click', function() {
        loadAdminBarItems();
    });

    // Обновление отображения кода функции
    $('#apt-refresh-function').on('click', function() {
        updateFunctionCode();
    });

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
                    var html = '<div class="notice notice-' + (data.writable ? 'success' : 'error') + '">';
                    html += '<p><strong>' + apt_localize.file_path + '</strong> ' + data.file_path + '</p>';
                    html += '<p><strong>' + apt_localize.read_access + '</strong> ' +
                        (data.readable ? apt_localize.yes : apt_localize.no) + '</p>';
                    html += '<p><strong>' + apt_localize.write_access + '</strong> ' +
                        (data.writable ? apt_localize.yes : apt_localize.no) + '</p>';
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

    function loadAdminBarItems() {
        $('#apt-items-list').html('<tr><td colspan="4">' + apt_localize.loading + '</td></tr>');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_get_items',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        displayItems(response.data);
                    } else {
                        $('#apt-items-list').html('<tr><td colspan="4">' +
                            apt_localize.no_items + '</td></tr>');
                    }
                } else {
                    var errorMsg = response.data || apt_localize.load_error;
                    $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                        apt_localize.load_error + ': ' + errorMsg + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                var errorMsg = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : error;
                $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                    apt_localize.request_error + ': ' + errorMsg + '</td></tr>');
            }
        });
    }

    function displayItems(items) {
        if (items.length === 0) {
            $('#apt-items-list').html('<tr><td colspan="4">' + apt_localize.no_items + '</td></tr>');
            return;
        }

        var html = '';
        items.forEach(function(item) {
            var statusText = item.enabled ? apt_localize.enabled : apt_localize.disabled;
            var statusClass = item.enabled ? 'apt-status-enabled' : 'apt-status-disabled';

            var buttonText = item.enabled ? apt_localize.disable : apt_localize.enable;
            var buttonClass = item.enabled ? 'button-secondary' : 'button-primary';
            var buttonTitle = item.enabled ?
                'Убрать элемент из админ-панели' :
                'Вернуть элемент в админ-панель';

            html += '<tr>';
            html += '<td><code>' + item.id + '</code></td>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="' + statusClass + '">' + statusText + '</span></td>';
            html += '<td>';
            html += '<button class="button ' + buttonClass + ' apt-toggle-item" ' +
                   'data-item-id="' + item.id + '" ' +
                   'data-enable="' + !item.enabled + '" ' +
                   'title="' + buttonTitle + '">' + buttonText + '</button>';
            html += '</td>';
            html += '</tr>';
        });

        $('#apt-items-list').html(html);

        // Обработка кликов по кнопкам переключения
        $('.apt-toggle-item').on('click', function() {
            var $button = $(this);
            var itemId = $button.data('item-id');
            var enable = $button.data('enable');
            var originalText = $button.text();

            $button.prop('disabled', true).text(apt_localize.processing);

            $.ajax({
                url: apt_ajax.url,
                type: 'POST',
                data: {
                    action: 'apt_toggle_item',
                    item_id: itemId,
                    enable: enable,
                    nonce: apt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var actionText = enable ? apt_localize.item_enabled : apt_localize.item_disabled;
                        showMessage('✅ ' + actionText, 'success');
                        loadAdminBarItems(); // Обновляем список
                        updateFunctionCode(); // Обновляем код функции
                    } else {
                        showMessage('❌ ' + response.data, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Toggle error:', error);
                    showMessage('❌ ' + apt_localize.request_error + ': ' + error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    function updateFunctionCode() {
        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_get_function_code',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#apt-function-code-block code').text(response.data.code);
                }
            }
        });
    }

    function showMessage(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var html = '<div class="notice ' + noticeClass + ' is-dismissible" style="margin-top: 10px;">' +
                  '<p>' + message + '</p>' +
                  '<button type="button" class="notice-dismiss">' +
                  '<span class="screen-reader-text">Скрыть уведомление</span>' +
                  '</button>' +
                  '</div>';

        $('.wrap h1').after(html);

        // Добавляем обработчик для кнопки закрытия
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });

        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            $('.notice:not(.notice-error)').fadeOut();
        }, 5000);
    }
});
```

## 4. CSS файл для стилей (`assets/css/admin.css`)

```css
.apt-status-enabled {
    color: #46b450;
    font-weight: bold;
}

.apt-status-disabled {
    color: #dc3232;
    font-weight: bold;
}

.apt-error {
    color: #dc3232;
    font-style: italic;
}

#apt-items-list code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 12px;
}

#apt-function-code-block pre {
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

#apt-function-code-block code {
    background: none;
    padding: 0;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.apt-toggle-item:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
```

## 5. Удалите ненужные файлы

Удалите эти файлы, так как их функциональность теперь в основном классе:
- `includes/class-ajax-handler.php`
- `includes/admin-settings.php`
- `includes/class-functions-editor.php`

## Что было исправлено:

1. **Перенесены все AJAX обработчики** в основной класс
2. **Упрощена архитектура** - убраны лишние классы
3. **Исправлена логика переключения элементов** - теперь правильно работает включение/выключение
4. **Добавлена полная локализация** текстов
5. **Улучшен интерфейс** - таблица с элементами, статусы, кнопки действий
6. **Добавлена обработка ошибок** и уведомления

Теперь плагин должен работать корректно: отображать все элементы админ-панели, показывать их статус (включен/отключен) и позволять переключать состояние с помощью кнопок "Убрать"/"Вернуть".