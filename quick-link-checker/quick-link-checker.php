<?php
/**
 * Plugin Name: Quick Link Checker
 * Plugin URI: http://bychko.ru
 * Description: Проверяет битые ссылки в постах и подсвечивает их в редакторе
 * Version: 2.0.2
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
define('QLC_PLUGIN_VERSION', '2.0.2');

/**
 * Основной класс плагина Quick Link Checker
 *
 * Отвечает за инициализацию и управление жизненным циклом плагина
 */
class QuickLinkChecker {

    /**
     * Экземпляр класса (паттерн Singleton)
     *
     * @var QuickLinkChecker|null
     */
    private static $instance = null;

    /**
     * Получение единственного экземпляра класса
     *
     * @return QuickLinkChecker
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса
     *
     * Регистрирует основные хуки плагина
     */
    private function __construct() {
        // Загрузка переводов
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Основная инициализация
        add_action('init', array($this, 'init'));

        // Проверка ссылок после сохранения поста
        add_action('wp_after_insert_post', array($this, 'after_post_save'), 10, 4);

        // Убираем автоматическую проверку при сохранении поста чтобы не конфликтовать с фоновой
        remove_action('save_post', array($this, 'check_post_links'), 10);
    }

    /**
     * Загрузка текстового домена для интернационализации
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'quick-link-checker',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Основная инициализация плагина
     *
     * Подключает все необходимые классы и регистрирует хуки
     *
     * @return void
     */
    public function init() {
        // Подключение основных классов плагина
        require_once QLC_PLUGIN_PATH . 'includes/class-link-checker.php';
        require_once QLC_PLUGIN_PATH . 'includes/class-editor-integration.php';
        require_once QLC_PLUGIN_PATH . 'admin/settings.php';
        require_once QLC_PLUGIN_PATH . 'includes/class-background-checker.php';

        // Инициализация компонентов
        new QLC_Link_Checker();
        new QLC_Editor_Integration();
        new QLC_Settings();
        new QLC_Background_Checker();

        // Подключение стилей и скриптов в админке
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Подключение стилей и скриптов в админке
     *
     * Регистрирует и подключает JavaScript и CSS файлы только на нужных страницах
     *
     * @param string $hook Текущий хук страницы
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        // Проверяем, включен ли плагин
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Подключаем скрипты только на страницах редактирования постов и настроек плагина
        if (!in_array($hook, array('post.php', 'post-new.php', 'settings_page_quick-link-checker'))) {
            return;
        }

        // Подключение основного JavaScript файла
        wp_enqueue_script(
            'qlc-admin-js',
            QLC_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            QLC_PLUGIN_VERSION,
            true
        );

        // Подключение CSS стилей
        wp_enqueue_style(
            'qlc-admin-css',
            QLC_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            QLC_PLUGIN_VERSION
        );

        // Локализация скриптов только на страницах редактирования постов
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            global $post;

            // Передача AJAX данных и текстовых строк
            wp_localize_script('qlc-admin-js', 'qlc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('qlc_nonce'),
                'checking_text' => __('Checking links...', 'quick-link-checker'),
                'broken_links_found' => __('Broken links found:', 'quick-link-checker'),
                'no_broken_links' => __('No broken links found', 'quick-link-checker'),
                'check_now_text' => __('Check Links Now', 'quick-link-checker'),
                'enabled' => get_option('qlc_enabled', '1')
            ));

            // Передача ID текущего поста в JavaScript
            if ($post) {
                wp_localize_script('qlc-admin-js', 'qlc_post', array(
                    'post_id' => $post->ID
                ));
            }
        }
    }

    /**
     * Обработка события сохранения поста
     *
     * Запускает фоновую проверку ссылок после сохранения поста
     *
     * @param int $post_id ID сохраненного поста
     * @param WP_Post $post Объект поста после сохранения
     * @param bool $update Флаг обновления существующего поста
     * @param WP_Post|null $post_before Объект поста до сохранения
     * @return void
     */
    public function after_post_save($post_id, $post, $update, $post_before) {
        // Проверяем, включена ли автоматическая проверка ссылок
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Проверяем права пользователя и тип поста
        if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Проверяем только опубликованные посты, черновики и посты в ожидании
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }

        // Запускаем асинхронную проверку ссылок
        $this->schedule_background_check($post_id);
    }

    /**
     * Планирование фоновой проверки ссылок
     *
     * Использует транзиент для предотвращения одновременных проверок
     *
     * @param int $post_id ID поста для проверки
     * @return void
     */
    public function schedule_background_check($post_id) {
        // Устанавливаем флаг планирования проверки на 60 секунд
        set_transient('qlc_check_scheduled_' . $post_id, true, 60);

        // Запускаем проверку после завершения выполнения скрипта
        add_action('shutdown', function() use ($post_id) {
            $this->start_background_check($post_id);
        });
    }

    /**
     * Запуск фоновой проверки ссылок
     *
     * Проверяет блокировку и запускает асинхронный запрос
     *
     * @param int $post_id ID поста для проверки
     * @return void
     */
    public function start_background_check($post_id) {
        // Проверяем, не выполняется ли уже проверка для этого поста
        if (get_transient('qlc_check_running_' . $post_id)) {
            return;
        }

        // Устанавливаем блокировку на 30 секунд
        set_transient('qlc_check_running_' . $post_id, true, 30);

        // Запускаем асинхронный HTTP запрос
        $this->make_async_request($post_id);
    }

    /**
     * Создание асинхронного HTTP запроса
     *
     * Отправляет запрос на фоновую проверку без блокировки основного потока
     *
     * @param int $post_id ID поста для проверки
     * @return void
     */
    private function make_async_request($post_id) {
        $url = admin_url('admin-ajax.php');

        $args = array(
            'timeout' => 0.01, // Минимальный таймаут - не ждем ответа
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