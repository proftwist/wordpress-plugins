<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Link_Checker {

    public function __construct() {
        add_action('save_post', array($this, 'check_post_links'), 10, 3);
        add_action('wp_ajax_qlc_check_links', array($this, 'ajax_check_links'));
    }

    public function check_post_links($post_id, $post, $update) {
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

        // Выполняем проверку
        $this->async_check_links($post_id);
    }

    private function async_check_links($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $links = $this->extract_links($post->post_content);
        $broken_links = array();

        foreach ($links as $link) {
            if (!$this->check_link($link['url'])) {
                $broken_links[] = $link;
            }
            // Добавляем небольшую задержку чтобы не перегружать сервер
            usleep(100000); // 0.1 секунда
        }

        // Сохраняем результат в мета-поле
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        // Логируем для отладки
        error_log('QLC: Checked ' . count($links) . ' links, found ' . count($broken_links) . ' broken');
    }

    public function ajax_check_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $links = $this->extract_links($content);
        $broken_links = array();

        foreach ($links as $link) {
            if (!$this->check_link($link['url'])) {
                $broken_links[] = $link;
            }
            usleep(50000); // 0.05 секунда
        }

        wp_send_json_success(array(
            'broken_links' => $broken_links,
            'total_checked' => count($links),
            'broken_count' => count($broken_links)
        ));
    }

    private function extract_links($content) {
        $links = array();

        if (empty($content)) {
            return $links;
        }

        // Регулярное выражение для поиска ссылок
        $pattern = '/<a[^>]+href=(["\'])(.*?)\1[^>]*>/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $full_tag = $match[0];
            $url = $match[2];

            // Пропускаем пустые ссылки
            if (empty($url)) {
                continue;
            }

            $links[] = array(
                'full_tag' => $full_tag,
                'url' => $url,
                'is_anchor' => $this->is_anchor_link($url)
            );
        }

        return $links;
    }

    private function is_anchor_link($url) {
        return strpos($url, '#') === 0 || (strpos($url, '#') !== false && strpos($url, 'http') === false);
    }

    private function check_link($url) {
        // Проверка якорных ссылок
        if ($this->is_anchor_link($url)) {
            return $this->check_anchor_link($url);
        }

        // Проверка внешних ссылок
        return $this->check_external_link($url);
    }

    private function check_anchor_link($url) {
        // Для якорных ссылок всегда возвращаем true, так как проверить их сложно
        // без полного рендеринга страницы
        return true;
    }

    private function check_external_link($url) {
        // Пропускаем mailto:, tel: и другие не-HTTP ссылки
        if (preg_match('/^(mailto:|tel:|javascript:|#)/i', $url)) {
            return true;
        }

        // Добавляем протокол если отсутствует
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        // Проверяем валидность URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 10,
                'user_agent' => 'Quick Link Checker WordPress Plugin'
            )
        ));

        $headers = @get_headers($url, 1, $context);

        if (!$headers) {
            return false;
        }

        $status_line = $headers[0];
        $http_code = (int) substr($status_line, 9, 3);

        // Считаем успешными коды 2xx и 3xx
        return ($http_code >= 200 && $http_code < 400);
    }
}