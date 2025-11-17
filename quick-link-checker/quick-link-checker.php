<?php
/**
 * Plugin Name: Quick Link Checker
 * Plugin URI: http://bychko.ru
 * Description: Проверяет битые ссылки в постах и подсвечивает их в редакторе
 * Version: 2.0.0
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * License: GPL v2 or later
 * Text Domain: quick-link-checker
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('QLC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QLC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QLC_PLUGIN_VERSION', '2.0.0');

class QuickLinkChecker {

    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init')); // Меняем на init

        // Добавляем хук для проверки после сохранения
        add_action('wp_after_insert_post', array($this, 'after_post_save'), 10, 4);
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'quick-link-checker',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function init() {
        // Подключаем классы
        require_once QLC_PLUGIN_PATH . 'includes/class-link-checker.php';
        require_once QLC_PLUGIN_PATH . 'includes/class-editor-integration.php';
        require_once QLC_PLUGIN_PATH . 'admin/settings.php';
        require_once QLC_PLUGIN_PATH . 'includes/class-background-checker.php'; // Новый

        // Инициализируем компоненты
        new QLC_Link_Checker();
        new QLC_Editor_Integration();
        new QLC_Settings();
        new QLC_Background_Checker(); // Новый

        // Подключаем стили и скрипты
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        // Исправляем название хука
        if (!in_array($hook, array('post.php', 'post-new.php', 'settings_page_quick-link-checker'))) {
            return;
        }

        wp_enqueue_script(
            'qlc-admin-js',
            QLC_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            QLC_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'qlc-admin-css',
            QLC_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            QLC_PLUGIN_VERSION
        );

        // Локализация только на нужных страницах
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            global $post;
            wp_localize_script('qlc-admin-js', 'qlc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('qlc_nonce'),
                'checking_text' => __('Checking links...', 'quick-link-checker'),
                'broken_links_found' => __('Broken links found:', 'quick-link-checker'),
                'no_broken_links' => __('No broken links found', 'quick-link-checker'),
                'check_now_text' => __('Check Links Now', 'quick-link-checker')
            ));

            // Передаем ID поста в JavaScript
            if ($post) {
                wp_localize_script('qlc-admin-js', 'qlc_post', array(
                    'post_id' => $post->ID
                ));
            }
        }
    }

    public function after_post_save($post_id, $post, $update, $post_before) {
        // Проверяем, включена ли проверка
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Проверяем права и тип поста
        if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Проверяем только опубликованные посты и черновики
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }

        // Запускаем ФОНОВУЮ проверку без блокировки
        $this->schedule_background_check($post_id);
    }

    public function schedule_background_check($post_id) {
        // Используем транзиент для отложенной проверки
        set_transient('qlc_check_scheduled_' . $post_id, true, 60); // 60 секунд

        // Запускаем фоновую задачу
        add_action('shutdown', function() use ($post_id) {
            $this->start_background_check($post_id);
        });
    }

    public function start_background_check($post_id) {
        // Проверяем, не выполняется ли уже проверка
        if (get_transient('qlc_check_running_' . $post_id)) {
            return;
        }

        set_transient('qlc_check_running_' . $post_id, true, 30); // Блокировка на 30 сек

        // Запускаем асинхронный запрос
        $this->make_async_request($post_id);
    }

    private function make_async_request($post_id) {
        $url = admin_url('admin-ajax.php');
        $args = array(
            'timeout' => 0.01, // Таймаут 10ms - не ждем ответа
            'blocking' => false, // Не блокируем выполнение
            'sslverify' => false,
            'body' => array(
                'action' => 'qlc_background_check',
                'post_id' => $post_id,
                'nonce' => wp_create_nonce('qlc_background_nonce')
            )
        );

        wp_remote_post($url, $args);
    }
}

// Инициализация плагина
add_action('plugins_loaded', function() {
    QuickLinkChecker::getInstance();
});

// Хуки активации/деактивации
register_activation_hook(__FILE__, 'qlc_activate');
register_deactivation_hook(__FILE__, 'qlc_deactivate');

function qlc_activate() {
    // Добавляем опции по умолчанию
    add_option('qlc_enabled', '1');
}

function qlc_deactivate() {
    // Очистка при деактивации
}