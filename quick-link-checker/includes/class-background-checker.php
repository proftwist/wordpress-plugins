<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Background_Checker {

    public function __construct() {
        add_action('wp_ajax_nopriv_qlc_background_check', array($this, 'background_check'));
        add_action('wp_ajax_qlc_background_check', array($this, 'background_check'));
    }

    public function background_check() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qlc_background_nonce')) {
            wp_die('Invalid nonce');
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_die('No post ID');
        }

        // Убираем блокировку выполнения
        ignore_user_abort(true);
        set_time_limit(60);

        // Запускаем проверку
        $this->do_background_check($post_id);

        wp_die('OK');
    }

    private function do_background_check($post_id) {
        require_once QLC_PLUGIN_PATH . 'includes/class-link-checker.php';
        $checker = new QLC_Link_Checker();

        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $links = $checker->extract_links($post->post_content);
        $broken_links = array();

        // Проверяем ссылки с ограничением по времени
        $start_time = time();
        $max_duration = 55; // Макс 55 секунд

        foreach ($links as $link) {
            // Проверяем не превысили ли лимит времени
            if ((time() - $start_time) >= $max_duration) {
                error_log('QLC: Background check timeout for post ' . $post_id);
                break;
            }

            if (!$checker->check_link($link['url'])) {
                $broken_links[] = $link;
            }

            // Уменьшаем задержку для фоновой проверки
            usleep(50000); // 0.05 секунды
        }

        // Сохраняем результат
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        // Очищаем блокировку
        delete_transient('qlc_check_running_' . $post_id);
        delete_transient('qlc_check_scheduled_' . $post_id);

        error_log('QLC: Background check completed for post ' . $post_id .
                  ', checked ' . count($links) . ' links, found ' . count($broken_links) . ' broken');
    }
}