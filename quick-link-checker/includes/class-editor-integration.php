<?php
/**
 * Класс интеграции с редактором WordPress
 *
 * Отвечает за добавление мета-боксов, стилей и скриптов в админку
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс интеграции с редактором постов
 */
class QLC_Editor_Integration {

    /**
     * Конструктор класса
     *
     * Регистрирует все необходимые хуки для интеграции с редактором
     */
    /**
     * Конструктор класса
     *
     * Регистрирует все необходимые хуки для интеграции с редактором
     */
    public function __construct() {
        // Проверяем, включен ли плагин
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        // Добавление мета-бокса со списком битых ссылок
        add_action('add_meta_boxes', array($this, 'add_meta_box'));

        // Добавление стилей в шапку админки
        add_action('admin_head', array($this, 'add_editor_styles'));

        // Подключение скриптов
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Отображение отладочной информации
        add_action('admin_notices', array($this, 'debug_info'));
    }

    public function add_meta_box() {
        $screens = array('post', 'page'); // Добавляем страницы тоже
        foreach ($screens as $screen) {
            add_meta_box(
                'qlc-broken-links',
                __('Broken Links', 'quick-link-checker'),
                array($this, 'render_meta_box'),
                $screen,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        $broken_links = get_post_meta($post->ID, '_qlc_broken_links', true);
        $enabled = get_option('qlc_enabled', '1');

        // Получаем общее количество ссылок для информации
        $post_content = $post->post_content;
        $total_links = 0;
        if (!empty($post_content)) {
            preg_match_all('/<a[^>]+href=(["\'])(.*?)\1[^>]*>/i', $post_content, $matches);
            $total_links = count($matches[0]);
        }

        echo '<div id="qlc-broken-links-container">';

        if (!$enabled) {
            echo '<p style="color: #d63638;">' . __('Link checking is disabled in settings.', 'quick-link-checker') . '</p>';
        } else if (empty($broken_links)) {
            echo '<p>✅ ' . __('No broken links found.', 'quick-link-checker') . '</p>';
            if ($total_links > 0) {
                echo '<p><small>Total links in post: ' . $total_links . '</small></p>';
            }
        } else {
            echo '<p><strong>❌ ' . sprintf(__('Found %d broken links:', 'quick-link-checker'), count($broken_links)) . '</strong></p>';
            echo '<ul style="max-height: 200px; overflow-y: auto;">';
            foreach ($broken_links as $link) {
                echo '<li style="margin-bottom: 5px;"><code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px; word-break: break-all;">' . esc_html($link['url']) . '</code></li>';
            }
            echo '</ul>';
            if ($total_links > 0) {
                echo '<p><small>Checked ' . $total_links . ' links total</small></p>';
            }
        }

        echo '<button type="button" id="qlc-check-now" class="button button-secondary" style="margin-top: 10px;">';
        echo __('Check Links Now', 'quick-link-checker');
        echo '</button>';

        echo '</div>';
    }

    public function add_editor_styles() {
        echo '<style>
            .qlc-broken-link {
                border: 2px solid #dc3232 !important;
                background-color: #ffeaea !important;
                padding: 2px !important;
            }
            #qlc-broken-links-container {
                margin: 10px 0;
            }
            #qlc-broken-links-container ul {
                margin: 8px 0;
                padding-left: 20px;
            }
        </style>';
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        // Проверяем, включен ли плагин
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        global $post;
        if (!$post) {
            return;
        }

        // Передаем ID поста в JavaScript для проверки при загрузке
        wp_localize_script('qlc-admin-js', 'qlc_post', array(
            'post_id' => $post->ID,
            'is_new_post' => ($hook === 'post-new.php')
        ));
    }

    public function debug_info() {
        // Показываем отладочную информацию только для администраторов
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        global $post;
        if ($post && in_array(get_current_screen()->base, array('post', 'post-new'))) {
            $broken_links = get_post_meta($post->ID, '_qlc_broken_links', true);
            echo '<div class="notice notice-info">';
            echo '<p><strong>QLC Debug:</strong> Post ' . $post->ID . ' has ' . (is_array($broken_links) ? count($broken_links) : '0') . ' broken links</p>';
            echo '</div>';
        }
    }
}