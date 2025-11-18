Отличная идея! Создадим полезный инструмент для диагностики производительности WordPress. Вот полная, подробная инструкция с полностью прокомментированными кодами.

## Структура плагина

```
wp-content/plugins/slow-plugins-detector/
├── slow-plugins-detector.php
├── admin/
│   └── admin-page.php
├── includes/
│   ├── class-test-runner.php
│   └── class-results-table.php
├── assets/
│   └── js/
│       └── admin.js
└── languages/
    (файлы перевода будут созданы автоматически)
```

## 1. Главный файл плагина

**Файл:** `slow-plugins-detector/slow-plugins-detector.php`

```php
<?php
/**
 * Plugin Name: Slow Plugins Detector
 * Description: Detects and analyzes slow loading plugins on the frontend
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: slow-plugins-detector
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('SPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SPD_VERSION', '1.0.0');

/**
 * Основной класс плагина
 */
class Slow_Plugins_Detector {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Загрузка зависимостей
        $this->includes();
    }

    /**
     * Загрузка файлов перевода
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'slow-plugins-detector',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Подключение зависимых файлов
     */
    private function includes() {
        require_once SPD_PLUGIN_PATH . 'includes/class-test-runner.php';
        require_once SPD_PLUGIN_PATH . 'includes/class-results-table.php';
        require_once SPD_PLUGIN_PATH . 'admin/admin-page.php';
    }

    /**
     * Добавление пункта меню в админке
     */
    public function add_admin_menu() {
        add_options_page(
            __('Slow Plugins Detector', 'slow-plugins-detector'), // Заголовок страницы
            __('Slow Plugins Detector', 'slow-plugins-detector'), // Название в меню
            'manage_options', // Требуемые права доступа
            'slow-plugins-detector', // SLUG страницы
            'spd_render_admin_page' // Функция отображения
        );
    }

    /**
     * Подключение скриптов и стилей
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на нашей странице
        if ('settings_page_slow-plugins-detector' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'spd-admin-js',
            SPD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SPD_VERSION,
            true
        );

        // Локализация для AJAX
        wp_localize_script('spd-admin-js', 'spd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spd_run_test'),
            'testing_text' => __('Testing...', 'slow-plugins-detector'),
            'complete_text' => __('Test Complete!', 'slow-plugins-detector')
        ));

        // Базовые стили
        wp_add_inline_style('wp-admin', '
            .spd-results { margin-top: 20px; }
            .spd-table { width: 100%; border-collapse: collapse; }
            .spd-table th, .spd-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            .spd-table th { background-color: #f8f9fa; font-weight: 600; }
            .spd-loading { display: none; color: #2271b1; }
            .spd-button { margin: 10px 0; }
            .spd-warning { color: #d63638; font-weight: 600; }
            .spd-good { color: #00a32a; }
        ');
    }
}

// Инициализация плагина
function slow_plugins_detector_init() {
    return Slow_Plugins_Detector::get_instance();
}
add_action('plugins_loaded', 'slow_plugins_detector_init');

// Регистрация AJAX обработчиков
add_action('wp_ajax_spd_run_performance_test', 'spd_handle_ajax_test');

/**
 * Обработчик AJAX запроса для запуска теста
 */
function spd_handle_ajax_test() {
    // Проверка nonce для безопасности
    check_ajax_referer('spd_run_test', 'nonce');

    // Проверка прав пользователя
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'slow-plugins-detector'));
    }

    $test_runner = new SPD_Test_Runner();
    $results = $test_runner->run_frontend_test();

    wp_send_json_success($results);
}
```

## 2. Класс для выполнения тестов

**Файл:** `slow-plugins-detector/includes/class-test-runner.php`

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для выполнения тестов производительности плагинов
 */
class SPD_Test_Runner {

    private $active_plugins;
    private $test_results = array();

    public function __construct() {
        $this->active_plugins = get_option('active_plugins');
    }

    /**
     * Основной метод запуска теста на фронтенде
     */
    public function run_frontend_test() {
        // Получаем домашнюю страницу для тестирования
        $home_url = home_url();

        // Тестируем каждый плагин по отдельности
        foreach ($this->active_plugins as $plugin) {
            $this->test_single_plugin($plugin, $home_url);
        }

        // Сортируем результаты по времени (от большего к меньшему)
        usort($this->test_results, function($a, $b) {
            return $b['load_time'] <=> $a['load_time'];
        });

        return $this->test_results;
    }

    /**
     * Тестирование одного плагина
     */
    private function test_single_plugin($plugin, $test_url) {
        // Деактивируем все плагины
        $this->deactivate_all_plugins();

        // Активируем только тестируемый плагин
        $this->activate_single_plugin($plugin);

        // Очищаем кеш для чистоты измерений
        $this->clear_caches();

        // Измеряем время загрузки
        $load_time = $this->measure_load_time($test_url);

        // Восстанавливаем все плагины
        $this->restore_plugins();

        // Добавляем результат
        $this->test_results[] = array(
            'plugin' => $plugin,
            'name' => $this->get_plugin_name($plugin),
            'load_time' => $load_time
        );
    }

    /**
     * Деактивация всех плагинов
     */
    private function deactivate_all_plugins() {
        update_option('active_plugins', array());
    }

    /**
     * Активация одного плагина для тестирования
     */
    private function activate_single_plugin($plugin) {
        update_option('active_plugins', array($plugin));
    }

    /**
     * Восстановление исходного состояния плагинов
     */
    private function restore_plugins() {
        update_option('active_plugins', $this->active_plugins);
    }

    /**
     * Очистка различных кешей
     */
    private function clear_caches() {
        // Очистка кеша WordPress
        wp_cache_flush();

        // Попытка очистки популярных кеширующих плагинов
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all(); // W3 Total Cache
        }

        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache(); // WP Super Cache
        }

        if (class_exists('Endurance_Page_Cache')) {
            // Кеш хостинга
            $epc = new Endurance_Page_Cache();
            $epc->purge_all();
        }
    }

    /**
     * Измерение времени загрузки страницы
     */
    private function measure_load_time($url) {
        $times = array();

        // Делаем 3 замера для усреднения
        for ($i = 0; $i < 3; $i++) {
            $start_time = microtime(true);

            // Используем wp_remote_get для измерения времени ответа
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'sslverify' => false,
                'headers' => array(
                    'Cache-Control' => 'no-cache'
                )
            ));

            $end_time = microtime(true);

            if (!is_wp_error($response)) {
                $times[] = ($end_time - $start_time) * 1000; // Конвертируем в миллисекунды
            }

            // Небольшая пауза между замерами
            if ($i < 2) {
                sleep(1);
            }
        }

        // Возвращаем среднее значение
        return !empty($times) ? array_sum($times) / count($times) : 0;
    }

    /**
     * Получение читаемого имени плагина
     */
    private function get_plugin_name($plugin_file) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
        return $plugin_data['Name'] ?: $plugin_file;
    }
}
```

## 3. Класс для отображения таблицы результатов

**Файл:** `slow-plugins-detector/includes/class-results-table.php`

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для генерации таблицы результатов
 */
class SPD_Results_Table {

    /**
     * Отображение таблицы с результатами
     */
    public static function display($results = array()) {
        if (empty($results)) {
            echo '<p>' . __('No test results available. Run a test first.', 'slow-plugins-detector') . '</p>';
            return;
        }
        ?>
        <table class="spd-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Plugin Name', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Load Time', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Status', 'slow-plugins-detector'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($result['name']); ?></strong>
                            <br><small><?php echo esc_html($result['plugin']); ?></small>
                        </td>
                        <td>
                            <?php echo number_format($result['load_time'], 2); ?> ms
                        </td>
                        <td>
                            <?php echo self::get_status_badge($result['load_time']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Генерация бейджа статуса на основе времени загрузки
     */
    private static function get_status_badge($load_time) {
        if ($load_time > 100) {
            return '<span class="spd-warning">' . __('Slow', 'slow-plugins-detector') . '</span>';
        } elseif ($load_time > 50) {
            return '<span style="color: #dba617;">' . __('Moderate', 'slow-plugins-detector') . '</span>';
        } else {
            return '<span class="spd-good">' . __('Fast', 'slow-plugins-detector') . '</span>';
        }
    }
}
```

## 4. Страница админки

**Файл:** `slow-plugins-detector/admin/admin-page.php`

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Функция отображения страницы админки
 */
function spd_render_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Slow Plugins Detector', 'slow-plugins-detector'); ?></h1>

        <div class="card">
            <h2><?php _e('Performance Test', 'slow-plugins-detector'); ?></h2>
            <p>
                <?php _e('This test will measure the load time of each active plugin on the frontend of your site. The test may take several minutes to complete.', 'slow-plugins-detector'); ?>
            </p>

            <button id="spd-run-test" class="button button-primary spd-button">
                <?php _e('Run Performance Test', 'slow-plugins-detector'); ?>
            </button>

            <div id="spd-loading" class="spd-loading">
                <p>
                    <span class="spinner is-active" style="float: none;"></span>
                    <strong><?php _e('Testing plugins... This may take a few minutes.', 'slow-plugins-detector'); ?></strong>
                </p>
            </div>
        </div>

        <div id="spd-results" class="spd-results" style="display: none;">
            <h2><?php _e('Test Results', 'slow-plugins-detector'); ?></h2>
            <div id="spd-results-content"></div>
        </div>

        <div class="card">
            <h3><?php _e('Important Notes', 'slow-plugins-detector'); ?></h3>
            <ul>
                <li><?php _e('Tests are performed on your site\'s homepage', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Each plugin is tested individually for accurate measurements', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Results may vary depending on server load and caching', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Plugins labeled "Slow" may need optimization or replacement', 'slow-plugins-detector'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}
```

## 5. JavaScript для AJAX взаимодействия

**Файл:** `slow-plugins-detector/assets/js/admin.js`

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {

        $('#spd-run-test').on('click', function() {
            var $button = $(this);
            var $loading = $('#spd-loading');
            var $results = $('#spd-results');
            var $resultsContent = $('#spd-results-content');

            // Блокируем кнопку и показываем индикатор загрузки
            $button.prop('disabled', true).text(spd_ajax.testing_text);
            $loading.show();
            $results.hide();

            // AJAX запрос
            $.ajax({
                url: spd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spd_run_performance_test',
                    nonce: spd_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Отображаем результаты
                        displayResults(response.data);
                        $results.show();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
                complete: function() {
                    // Восстанавливаем кнопку и скрываем индикатор
                    $button.prop('disabled', false).text('Run Performance Test');
                    $loading.hide();
                }
            });
        });

        /**
         * Отображение результатов в таблице
         */
        function displayResults(results) {
            var tableHtml = '<table class="spd-table wp-list-table widefat fixed striped">' +
                '<thead>' +
                    '<tr>' +
                        '<th>Plugin Name</th>' +
                        '<th>Load Time</th>' +
                        '<th>Status</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody>';

            $.each(results, function(index, result) {
                var statusBadge = getStatusBadge(result.load_time);
                tableHtml += '<tr>' +
                    '<td><strong>' + escapeHtml(result.name) + '</strong><br><small>' + escapeHtml(result.plugin) + '</small></td>' +
                    '<td>' + result.load_time.toFixed(2) + ' ms</td>' +
                    '<td>' + statusBadge + '</td>' +
                '</tr>';
            });

            tableHtml += '</tbody></table>';
            $('#spd-results-content').html(tableHtml);
        }

        /**
         * Генерация бейджа статуса
         */
        function getStatusBadge(loadTime) {
            if (loadTime > 100) {
                return '<span class="spd-warning">Slow</span>';
            } else if (loadTime > 50) {
                return '<span style="color: #dba617;">Moderate</span>';
            } else {
                return '<span class="spd-good">Fast</span>';
            }
        }

        /**
         * Экранирование HTML для безопасности
         */
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

})(jQuery);
```

## 6. Файлы перевода

Создайте папку `languages` и добавьте туда `.pot` файл для перевода. Вы можете использовать плагины типа Loco Translate или создать файлы вручную.

## Установка и использование

1. Создайте папку `slow-plugins-detector` в `/wp-content/plugins/`
2. Разместите все файлы по соответствующим папкам
3. Активируйте плагин в админке WordPress
4. Перейдите в "Настройки" → "Slow Plugins Detector"
5. Нажмите "Run Performance Test"

## Важные примечания

- **Безопасность**: Плагин использует nonce проверки и проверки прав доступа
- **Производительность**: Тест может занять несколько минут на сайтах с большим количеством плагинов
- **Кеширование**: Плагин пытается очистить популярные кеши, но результаты могут отличаться
- **Локализация**: Все текстовые строки подготовлены для перевода

Плагин готов к использованию! Он поможет выявить самые медленные плагины и оптимизировать производительность вашего сайта.