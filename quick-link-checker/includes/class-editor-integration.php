<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Editor_Integration {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_head', array($this, 'add_editor_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts')); // Новый хук
        add_action('admin_notices', array($this, 'debug_info')); // Добавляем отладку
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

        echo '<div id="qlc-broken-links-container">';

        if (!$enabled) {
            echo '<p style="color: #d63638;">' . __('Link checking is disabled in settings.', 'quick-link-checker') . '</p>';
        } else if (empty($broken_links)) {
            echo '<p>' . __('No broken links found. Click button to check.', 'quick-link-checker') . '</p>';
        } else {
            echo '<p><strong>' . sprintf(__('Found %d broken links:', 'quick-link-checker'), count($broken_links)) . '</strong></p>';
            echo '<ul style="max-height: 200px; overflow-y: auto;">';
            foreach ($broken_links as $link) {
                echo '<li style="margin-bottom: 5px;"><code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px; word-break: break-all;">' . esc_html($link['url']) . '</code></li>';
            }
            echo '</ul>';
        }

        echo '<button type="button" id="qlc-check-now" class="button button-secondary" style="margin-top: 10px;">';
        echo __('Check Links Now', 'quick-link-checker');
        echo '</button>';

        // Отладочная информация
        echo '<div style="margin-top: 10px; padding: 8px; background: #f0f0f1; border-radius: 4px; font-size: 12px;">';
        echo '<strong>Debug:</strong> ';
        echo 'Post ID: ' . $post->ID . ' | ';
        echo 'Enabled: ' . ($enabled ? 'Yes' : 'No') . ' | ';
        echo 'Links: ' . (is_array($broken_links) ? count($broken_links) : '0');
        echo '</div>';

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

        global $post;
        if (!$post) {
            return;
        }

        // Передаем ID поста в JavaScript
        wp_localize_script('qlc-admin-js', 'qlc_post', array(
            'post_id' => $post->ID
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