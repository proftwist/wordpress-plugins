<?php
/**
 * Класс для фоновой проверки ссылок
 *
 * Обрабатывает асинхронные запросы на проверку ссылок без блокировки интерфейса
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс фоновой проверки ссылок
 */
class QLC_Background_Checker {

    /**
     * Конструктор класса
     *
     * Регистрирует AJAX обработчики для фоновой проверки
     */
    public function __construct() {
        // AJAX хуки для фоновой проверки (доступны для всех пользователей)
        add_action('wp_ajax_nopriv_qlc_background_check', array($this, 'background_check'));
        add_action('wp_ajax_qlc_background_check', array($this, 'background_check'));
    }

    /**
     * Обработчик AJAX запроса для фоновой проверки
     *
     * Обрабатывает входящий AJAX запрос, проверяет безопасность и запускает проверку
     *
     * @return void
     */
    public function background_check() {
        // Проверяем nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qlc_background_nonce')) {
            wp_die('Invalid nonce');
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_die('No post ID');
        }

        // Разрешаем выполнение в фоне и устанавливаем лимит времени
        ignore_user_abort(true);
        set_time_limit(60);

        // Запускаем процесс фоновой проверки
        $this->do_background_check($post_id);

        wp_die('OK');
    }

    /**
     * Выполняет фоновую проверку ссылок в посте
     *
     * Процесс проверки с ограничением по времени для предотвращения таймаутов
     *
     * @param int $post_id ID поста для проверки
     * @return void
     */
    private function do_background_check($post_id) {
        // Подключаем класс проверки ссылок
        require_once QLC_PLUGIN_PATH . 'includes/class-link-checker.php';
        $checker = new QLC_Link_Checker();

        // Получаем объект поста
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        // Извлекаем ссылки из контента
        $links = $checker->extract_links($post->post_content);
        $broken_links = array();

        // Параметры ограничения времени
        $start_time = time();
        $max_duration = 55; // Максимум 55 секунд

        // Проверяем каждую ссылку с контролем времени
        foreach ($links as $link) {
            // Прерываем если превышен лимит времени
            if ((time() - $start_time) >= $max_duration) {
                break;
            }

            // Проверяем доступность ссылки
            if (!$checker->check_link($link['url'])) {
                $broken_links[] = $link;
            }

            // Небольшая задержка между проверками
            usleep(50000); // 0.05 секунды
        }

        // Сохраняем результаты проверки
        update_post_meta($post_id, '_qlc_broken_links', $broken_links);

        // Очищаем временные блокировки
        delete_transient('qlc_check_running_' . $post_id);
        delete_transient('qlc_check_scheduled_' . $post_id);
    }
}