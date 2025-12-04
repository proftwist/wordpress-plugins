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

        // Проверяем, активен ли плагин сейчас
        $active_plugins = get_option('active_plugins', array());
        $is_active = in_array($plugin, $active_plugins);

        // Добавляем результат
        $this->test_results[] = array(
            'plugin' => $plugin,
            'name' => $this->get_plugin_name($plugin),
            'load_time' => $load_time,
            'is_active' => $is_active
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