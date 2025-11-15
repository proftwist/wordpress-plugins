<?php
/**
 * Plugin Name: Megalinks
 * Description: Добавляет всплывающие подсказки с цитатами для внутренних ссылок на посты и страницы
 * Version: 1.1.0
 * Author: Владимир Бычко
 * Author URL: https://bychko.ru
 * Text Domain: megalinks
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант
define('MEGALINKS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MEGALINKS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Основной класс плагина Megalinks
 *
 * Отвечает за инициализацию плагина, регистрацию хуков,
 * подключение скриптов и стилей, а также обработку AJAX-запросов
 * для получения цитат постов.
 */
class Megalinks {

    /**
     * Конструктор класса
     */
    public function __construct() {
        // Регистрация основных хуков
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Загружаем фронтенд ресурсы и AJAX обработчики всегда
        // Но проверяем настройку внутри функций
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_megalinks_get_excerpt', array($this, 'ajax_get_excerpt'));
        add_action('wp_ajax_nopriv_megalinks_get_excerpt', array($this, 'ajax_get_excerpt'));
        add_action('wp_ajax_megalinks_get_post_id_by_url', array($this, 'ajax_get_post_id_by_url'));
        add_action('wp_ajax_nopriv_megalinks_get_post_id_by_url', array($this, 'ajax_get_post_id_by_url'));
        add_action('wp_ajax_megalinks_get_thumbnail', array($this, 'ajax_get_thumbnail'));
        add_action('wp_ajax_nopriv_megalinks_get_thumbnail', array($this, 'ajax_get_thumbnail'));
    }

    /**
     * Подключение скриптов и стилей
     */
    public function enqueue_assets() {
        // Проверяем, что мы на фронтенде и не в админке
        if (is_admin()) {
            return;
        }

        // Проверяем, включен ли плагин
        if (get_option('megalinks_enabled', '1') !== '1') {
            return;
        }

        // Подключаем CSS
        wp_enqueue_style(
            'megalinks-styles',
            MEGALINKS_PLUGIN_URL . 'assets/css/megalinks.css',
            array(),
            '1.0.0'
        );

        // Подключаем JavaScript
        wp_enqueue_script(
            'megalinks-script',
            MEGALINKS_PLUGIN_URL . 'assets/js/megalinks.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Передаем данные в JavaScript с отдельными nonce для каждого действия
        wp_localize_script('megalinks-script', 'megalinksAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce_excerpt' => wp_create_nonce('megalinks_get_excerpt'),
            'nonce_post_id' => wp_create_nonce('megalinks_get_post_id_by_url'),
            'nonce_thumbnail' => wp_create_nonce('megalinks_get_thumbnail')
        ));
    }

    /**
     * Добавление пункта меню в админке
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',           // Родительское меню (Настройки)
            'Megalinks',                     // Заголовок страницы
            'Megalinks',                     // Название в меню
            'manage_options',                // Права доступа
            'megalinks-settings',           // Slug страницы
            array($this, 'options_page')     // Функция отображения
        );
    }

    /**
     * Регистрация настроек
     */
    public function register_settings() {
        register_setting('megalinks_settings', 'megalinks_enabled');

        add_settings_section(
            'megalinks_main_section',
            'Основные настройки',
            null,
            'megalinks_settings'
        );

        add_settings_field(
            'megalinks_enabled',
            'Включить плагин',
            array($this, 'enabled_field_callback'),
            'megalinks_settings',
            'megalinks_main_section'
        );
    }

    /**
     * Callback для поля включения плагина
     */
    public function enabled_field_callback() {
        $enabled = get_option('megalinks_enabled', '1');
        ?>
        <input type="checkbox"
               id="megalinks_enabled"
               name="megalinks_enabled"
               value="1"
               <?php checked('1', $enabled); ?> />
        <label for="megalinks_enabled">Включить всплывающие подсказки для внутренних ссылок</label>
        <?php
    }

    /**
     * Отображение страницы настроек
     */
    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Megalinks Настройки</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('megalinks_settings');
                do_settings_sections('megalinks_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX обработчик для получения цитаты поста
     */
    public function ajax_get_excerpt() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'megalinks_get_excerpt')) {
            wp_die('Security check failed');
        }

        // Получаем и валидируем ID поста
        $post_id = intval($_POST['post_id']);
        if (!$post_id || !is_numeric($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
        }

        // Получаем пост
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('post', 'page'), true)) {
            wp_send_json_error('Post not found or invalid type');
        }

        // Получаем цитату (excerpt)
        $excerpt = get_the_excerpt($post);
        if (empty(trim($excerpt))) {
            wp_send_json_error('No excerpt available');
        }

        // Очищаем и возвращаем цитату
        $clean_excerpt = wp_strip_all_tags($excerpt);
        wp_send_json_success(array('excerpt' => $clean_excerpt));
    }

    /**
     * AJAX обработчик для получения ID поста по URL
     */
    public function ajax_get_post_id_by_url() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'megalinks_get_post_id_by_url')) {
            wp_die('Security check failed');
        }

        // Получаем и очищаем URL
        $url = esc_url_raw($_POST['url']);
        if (empty($url)) {
            wp_send_json_error('Invalid URL');
        }

        // Преобразуем URL в относительный путь для WordPress
        $parsed_url = parse_url($url);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';

        // Удаляем базовый путь WordPress если он есть
        $site_url = parse_url(get_site_url());
        $site_path = isset($site_url['path']) ? $site_url['path'] : '';
        if (!empty($site_path) && strpos($path, $site_path) === 0) {
            $path = substr($path, strlen($site_path));
        }

        // Ищем пост по пути
        $post_id = url_to_postid($url);
        if (!$post_id) {
            // Попробуем альтернативный способ
            $post = get_page_by_path($path);
            if ($post) {
                $post_id = $post->ID;
            }
        }

        if (!$post_id) {
            wp_send_json_error('Post not found');
        }

        // Проверяем тип поста
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('post', 'page'), true)) {
            wp_send_json_error('Invalid post type');
        }

        wp_send_json_success(array('post_id' => (int) $post_id));
    }

    /**
     * AJAX обработчик для получения миниатюры поста
     */
    public function ajax_get_thumbnail() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'megalinks_get_thumbnail')) {
            wp_send_json_error('Security check failed');
        }

        // Получаем и валидируем ID поста
        $post_id = intval($_POST['post_id']);
        if (!$post_id || !is_numeric($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID: ' . $_POST['post_id']);
        }

        // Получаем пост
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('post', 'page'), true)) {
            wp_send_json_error('Post not found or invalid type: ' . $post_id);
        }

        // Получаем миниатюру поста
        $thumbnail_id = get_post_thumbnail_id($post_id);

        if (!$thumbnail_id) {
            wp_send_json_error('No thumbnail available for post: ' . $post_id);
        }

        // Получаем URL миниатюры (medium_large размер для лучшего качества)
        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium_large');

        if (!$thumbnail_url) {
            // Попробуем получить полный размер если thumbnail не существует
            $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            if (!$thumbnail_url) {
                // Попробуем получить attachment данные напрямую
                $attachment = get_post($thumbnail_id);
                if ($attachment && $attachment->guid) {
                    $thumbnail_url = $attachment->guid;
                } else {
                    wp_send_json_error('Thumbnail URL not found for attachment: ' . $thumbnail_id);
                }
            }
        }
        wp_send_json_success(array('thumbnail_url' => $thumbnail_url));
    }
}

// Инициализация плагина
new Megalinks();