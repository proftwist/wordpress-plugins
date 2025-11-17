<?php

if (!defined('ABSPATH')) {
    exit;
}

class QLC_Link_Checker {

    private $checked_urls = array(); // Кэш проверенных URL

    public function __construct() {
        add_action('save_post', array($this, 'check_post_links'), 10, 3);
        add_action('wp_ajax_qlc_check_links', array($this, 'ajax_check_links'));
        add_action('wp_ajax_qlc_get_broken_links', array($this, 'ajax_get_broken_links'));
        add_action('wp_ajax_qlc_save_broken_links', array($this, 'ajax_save_broken_links'));
        add_action('wp_ajax_qlc_check_changed_links', array($this, 'ajax_check_changed_links')); // Новая умная проверка
    }

    // Проверка при сохранении - БЕЗ ЛИМИТОВ
    public function check_post_links($post_id, $post, $update) {
        if (!get_option('qlc_enabled', '1')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }

        // Запускаем проверку ВСЕХ ссылок
        $this->check_all_links($post_id);
    }

    // Проверка ВСЕХ ссылок без ограничений
    public function check_all_links($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $links = $this->extract_links($post->post_content);
        $broken_links = array();

        // ПРОВЕРЯЕМ ВСЕ ССЫЛКИ БЕЗ ЛИМИТОВ
        foreach ($links as $link) {
            // Используем кэш чтобы не проверять один URL дважды
            $url_hash = md5($link['url']);
            if (!isset($this->checked_urls[$url_hash])) {
                $this->checked_urls[$url_hash] = $this->check_link($link['url']);
            }

            if (!$this->checked_urls[$url_hash]) {
                $broken_links[] = $link;
            }

            // Минимальная задержка чтобы не перегружать сервер
            usleep(50000); // 0.05 секунды
        }

        // Сохраняем результат в мета-поле
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        error_log('QLC: Checked ALL ' . count($links) . ' links for post ' . $post_id .
                 ', found ' . count($broken_links) . ' broken');

        return $broken_links;
    }

    // AJAX проверка - тоже БЕЗ ЛИМИТОВ
    public function ajax_check_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $links = $this->extract_links($content);
        $broken_links = array();

        // ПРОВЕРЯЕМ ВСЕ ССЫЛКИ БЕЗ ОГРАНИЧЕНИЙ
        foreach ($links as $link) {
            $url_hash = md5($link['url']);
            if (!isset($this->checked_urls[$url_hash])) {
                $this->checked_urls[$url_hash] = $this->check_link($link['url']);
            }

            if (!$this->checked_urls[$url_hash]) {
                $broken_links[] = $link;
            }

            usleep(50000); // 0.05 секунды
        }

        wp_send_json_success(array(
            'broken_links' => $broken_links,
            'total_checked' => count($links),
            'broken_count' => count($broken_links),
            'message' => 'Checked all ' . count($links) . ' links completely'
        ));
    }


    // Новый AJAX метод для получения битых ссылок после сохранения
    public function ajax_get_broken_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('No post ID');
        }

        $broken_links = get_post_meta($post_id, '_qlc_broken_links', true);

        wp_send_json_success(array(
            'broken_links' => is_array($broken_links) ? $broken_links : array(),
            'broken_count' => is_array($broken_links) ? count($broken_links) : 0
        ));
    }

    // Добавляем метод сохранения битых ссылок
    public function ajax_save_broken_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $broken_links = isset($_POST['broken_links']) ? $_POST['broken_links'] : array();

        if (!$post_id) {
            wp_send_json_error('No post ID');
        }

        // Сохраняем битые ссылки
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        wp_send_json_success('Broken links saved');
    }

    // НОВЫЙ метод: проверяем только изменившиеся ссылки
    public function ajax_check_changed_links() {
        check_ajax_referer('qlc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $current_links_data = isset($_POST['links_data']) ? $_POST['links_data'] : array();

        if (!$post_id) {
            wp_send_json_error('No post ID');
        }

        // Получаем сохраненные битые ссылки
        $stored_broken_links = get_post_meta($post_id, '_qlc_broken_links', true);
        $stored_links_hash = get_post_meta($post_id, '_qlc_links_hash', true);

        // Создаем хеш текущих ссылок для сравнения
        $current_links_hash = md5(json_encode($current_links_data));

        // Если хеш не изменился - возвращаем сохраненные данные
        if ($stored_links_hash === $current_links_hash) {
            wp_send_json_success(array(
                'broken_links' => is_array($stored_broken_links) ? $stored_broken_links : array(),
                'broken_count' => is_array($stored_broken_links) ? count($stored_broken_links) : 0,
                'links_unchanged' => true,
                'message' => 'Links unchanged - using cached data'
            ));
        }

        // Если ссылки изменились - проверяем только новые/измененные
        $links_to_check = $this->get_links_to_check($current_links_data, $stored_broken_links);
        $new_broken_links = array();

        foreach ($links_to_check as $link_data) {
            if (!$this->check_link($link_data['url'])) {
                $new_broken_links[] = array(
                    'url' => $link_data['url'],
                    'full_tag' => $link_data['full_tag']
                );
            }
            usleep(50000); // 0.05 сек
        }

        // Объединяем с существующими битыми ссылками (которые все еще актуальны)
        $all_broken_links = $this->merge_broken_links($stored_broken_links, $new_broken_links, $current_links_data);

        // Сохраняем новые данные
        update_post_meta($post_id, '_qlc_broken_links', $all_broken_links);
        update_post_meta($post_id, '_qlc_links_hash', $current_links_hash);

        wp_send_json_success(array(
            'broken_links' => $all_broken_links,
            'broken_count' => count($all_broken_links),
            'checked_count' => count($links_to_check),
            'links_unchanged' => false,
            'message' => 'Checked ' . count($links_to_check) . ' changed links'
        ));
    }

    // Определяем какие ссылки нужно проверить
    private function get_links_to_check($current_links_data, $stored_broken_links) {
        $links_to_check = array();
        $stored_urls = array();

        if (is_array($stored_broken_links)) {
            foreach ($stored_broken_links as $broken_link) {
                $stored_urls[] = $broken_link['url'];
            }
        }

        foreach ($current_links_data as $link_data) {
            $url = $link_data['url'];

            // Проверяем только если:
            // 1. Это новая ссылка (нет в сохраненных битых)
            // 2. Или это измененная ссылка
            if (!in_array($url, $stored_urls)) {
                $links_to_check[] = $link_data;
            }
        }

        return $links_to_check;
    }

    // Объединяем битые ссылки
    private function merge_broken_links($stored_broken_links, $new_broken_links, $current_links_data) {
        $all_broken_links = array();
        $current_urls = array();

        foreach ($current_links_data as $link_data) {
            $current_urls[] = $link_data['url'];
        }

        // Добавляем старые битые ссылки, которые все еще присутствуют
        if (is_array($stored_broken_links)) {
            foreach ($stored_broken_links as $broken_link) {
                if (in_array($broken_link['url'], $current_urls)) {
                    $all_broken_links[] = $broken_link;
                }
            }
        }

        // Добавляем новые битые ссылки
        foreach ($new_broken_links as $new_broken_link) {
            $all_broken_links[] = $new_broken_link;
        }

        // Убираем дубликаты
        $unique_links = array();
        $added_urls = array();

        foreach ($all_broken_links as $link) {
            if (!in_array($link['url'], $added_urls)) {
                $unique_links[] = $link;
                $added_urls[] = $link['url'];
            }
        }

        return $unique_links;
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