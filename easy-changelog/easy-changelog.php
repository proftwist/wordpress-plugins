<?php
/**
 * Plugin Name: Easy Changelog
 * Plugin URI: http://bychko.ru
 * Description: Gutenberg блок для отображения истории изменений (changelog) с автоматической синхронизацией из GitHub.
 * Version: 2.0.1
 * Author: Владимир Бычко
 * License: GPL v2 or later
 * Text Domain: easy-changelog
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Основной класс плагина Easy Changelog
 */
class EasyChangelog {

    private $version = '2.0.1';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('block_categories_all', array($this, 'add_block_category'), 10, 2);

        // Ежедневная очистка устаревших записей
        add_action('easy_changelog_cleanup', array($this, 'cleanup_old_records'));

        // Проверяем нужно ли обновить БД
        add_action('plugins_loaded', array($this, 'check_db_version'));
    }

    /**
     * Проверка и создание БД при необходимости
     */
    public function check_db_version() {
        $current_db_version = get_option('easy_changelog_db_version', '0');

        if (version_compare($current_db_version, $this->version, '<')) {
            $this->create_tables();
            update_option('easy_changelog_db_version', $this->version);
        }

        // Планируем очистку если не запланирована
        if (!wp_next_scheduled('easy_changelog_cleanup')) {
            wp_schedule_event(time(), 'daily', 'easy_changelog_cleanup');
        }
    }

    /**
     * Создание таблиц БД для отслеживания блоков
     */
    private function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'easy_changelog_blocks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            block_id varchar(100) NOT NULL,
            json_url varchar(500) NOT NULL,
            last_hash varchar(64) DEFAULT '',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY json_url (json_url(191)),
            KEY last_updated (last_updated)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Логируем создание таблицы
        error_log('Easy Changelog: Database table created - ' . $table_name);
    }

    public function init() {
        $this->load_textdomain();

        if (function_exists('register_block_type')) {
            $this->register_block();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'easy-changelog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function enqueue_block_editor_assets() {
        wp_localize_script(
            'easy-changelog-editor-script',
            'easyChangelogI18n',
            array(
                'title' => __('Easy Changelog', 'easy-changelog'),
                'description' => __('Блок для отображения changelog с автоматической синхронизацией из GitHub', 'easy-changelog'),
                'jsonEditor' => __('Редактор JSON', 'easy-changelog'),
                'preview' => __('Предпросмотр', 'easy-changelog'),
                'externalJson' => __('Внешний JSON', 'easy-changelog'),
                'changelogData' => __('Данные Changelog (JSON)', 'easy-changelog'),
                'jsonUrl' => __('URL внешнего JSON файла', 'easy-changelog'),
                'webhookUrl' => __('Webhook URL для автоматического обновления', 'easy-changelog'),
                'jsonHelp' => __('Введите данные в формате JSON. Каждый релиз должен содержать version, date, added и fixed.', 'easy-changelog'),
                'urlHelp' => __('Укажите прямую ссылку на JSON файл в GitHub или другом хранилище', 'easy-changelog'),
                'webhookHelp' => __('Добавьте этот URL как webhook в настройках вашего GitHub репозитория для автоматического обновления', 'easy-changelog'),
                'githubHelp' => __('Как получить ссылку на GitHub:', 'easy-changelog'),
                'githubInstructions' => __('1. Перейдите в репозиторий на GitHub → 2. Выберите ветку → 3. Найдите файл → 4. Нажмите "Raw" → 5. Скопируйте URL из адресной строки', 'easy-changelog'),
                'webhookInstructions' => __('Для автоматического обновления: Settings → Webhooks → Add webhook → Payload URL → выберите "Just the push event"', 'easy-changelog'),
                'exampleUrl' => __('Пример: https://raw.githubusercontent.com/username/repo/main/changelog.json', 'easy-changelog'),
                'error' => __('Ошибка:', 'easy-changelog'),
                'invalidJson' => __('Некорректный JSON формат', 'easy-changelog'),
                'mustBeArray' => __('Данные должны быть массивом', 'easy-changelog'),
                'cannotPreview' => __('Невозможно отобразить предпросмотр из-за ошибок в JSON', 'easy-changelog'),
                'changelogTitle' => __('История изменений', 'easy-changelog'),
                'fetchError' => __('Ошибка загрузки внешнего JSON', 'easy-changelog'),
                'fetchSuccess' => __('Данные успешно загружены', 'easy-changelog'),
                'loadFromUrl' => __('Загрузить из URL', 'easy-changelog'),
                'autoSyncEnabled' => __('Автосинхронизация включена', 'easy-changelog'),
                'copyWebhookUrl' => __('Скопировать Webhook URL', 'easy-changelog'),
                'webhookCopied' => __('Webhook URL скопирован в буфер обмена', 'easy-changelog'),
            )
        );
    }

    public function register_block() {
        $asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');

        wp_register_script(
            'easy-changelog-editor-script',
            plugins_url('build/index.js', __FILE__),
            $asset_file['dependencies'],
            $asset_file['version']
        );

        wp_register_style(
            'easy-changelog-editor-style',
            plugins_url('build/style-index.css', __FILE__),
            array(),
            $asset_file['version']
        );

        register_block_type('easy-changelog/changelog', array(
            'editor_script' => 'easy-changelog-editor-script',
            'editor_style'  => 'easy-changelog-editor-style',
            'render_callback' => array($this, 'render_block'),
            'attributes' => array(
                'changelogData' => array(
                    'type' => 'string',
                    'default' => '[
  {
    "version": "1.0.0",
    "date": "19.11.2025",
    "added": ["Первоначальный релиз плагина", "Базовая функциональность блоков"],
    "fixed": ["Исправлена ошибка валидации JSON", "Улучшена обработка дат"]
  },
  {
    "version": "0.9.0",
    "date": "15.11.2025",
    "added": ["Бета-версия плагина", "Тестирование функциональности"],
    "fixed": ["Устранены проблемы с локализацией"]
  }
]'
                ),
                'jsonUrl' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'useExternalUrl' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'blockId' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));

        // Регистрируем обработчик для сохранения блоков
        add_action('save_post', array($this, 'track_changelog_blocks'), 10, 3);
    }

    /**
     * Отслеживание блоков changelog при сохранении поста
     */
    public function track_changelog_blocks($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;

        error_log('Easy Changelog: Tracking blocks for post ' . $post_id);

        $blocks = parse_blocks($post->post_content);
        $changelog_blocks = 0;

        foreach ($blocks as $block) {
            if ($block['blockName'] === 'easy-changelog/changelog') {
                $changelog_blocks++;
            }
        }

        error_log('Easy Changelog: Found ' . $changelog_blocks . ' changelog blocks');

        $this->process_blocks_for_tracking($post_id, $blocks);
    }

    /**
     * Обработка блоков для отслеживания
     */
    private function process_blocks_for_tracking($post_id, $blocks) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'easy_changelog_blocks';

        foreach ($blocks as $block) {
            if ($block['blockName'] === 'easy-changelog/changelog') {
                $attributes = $block['attrs'];

                if (!empty($attributes['useExternalUrl']) && !empty($attributes['jsonUrl'])) {
                    $block_id = !empty($attributes['blockId']) ? $attributes['blockId'] : wp_generate_uuid4();

                    // Сохраняем/обновляем запись
                    $wpdb->replace(
                        $table_name,
                        array(
                            'post_id' => $post_id,
                            'block_id' => $block_id,
                            'json_url' => $attributes['jsonUrl'],
                            'last_updated' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s')
                    );

                    // Обновляем blockId в атрибутах, если его не было
                    if (empty($attributes['blockId'])) {
                        $this->update_block_id($post_id, $block, $block_id);
                    }
                }
            }

            // Рекурсивно обрабатываем вложенные блоки
            if (!empty($block['innerBlocks'])) {
                $this->process_blocks_for_tracking($post_id, $block['innerBlocks']);
            }
        }
    }

    /**
     * Обновление blockId в контенте поста
     */
    private function update_block_id($post_id, $block, $block_id) {
        $post = get_post($post_id);
        $blocks = parse_blocks($post->post_content);

        $this->update_block_id_in_blocks($blocks, $block, $block_id);

        $updated_content = serialize_blocks($blocks);
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $updated_content
        ));
    }

    /**
     * Рекурсивное обновление blockId в массиве блоков
     */
    private function update_block_id_in_blocks(&$blocks, $target_block, $block_id) {
        foreach ($blocks as &$block) {
            if ($block['blockName'] === $target_block['blockName'] &&
                $block['attrs']['jsonUrl'] === $target_block['attrs']['jsonUrl'] &&
                empty($block['attrs']['blockId'])) {

                $block['attrs']['blockId'] = $block_id;
                return true;
            }

            if (!empty($block['innerBlocks'])) {
                if ($this->update_block_id_in_blocks($block['innerBlocks'], $target_block, $block_id)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Регистрация REST API endpoints
     */
    public function register_rest_routes() {
        // Endpoint для загрузки внешних данных
        register_rest_route('easy-changelog/v1', '/fetch-external', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_external_fetch'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => array(
                'url' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_URL) !== false;
                    }
                ),
            ),
        ));

        // Webhook endpoint для GitHub
        register_rest_route('easy-changelog/v1', '/github-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_github_webhook'),
            'permission_callback' => '__return_true', // GitHub не аутентифицируется
        ));

        // Endpoint для получения webhook URL
        register_rest_route('easy-changelog/v1', '/webhook-url', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_webhook_url'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * Получение webhook URL
     */
    public function get_webhook_url() {
        $webhook_url = rest_url('easy-changelog/v1/github-webhook');
        return new WP_REST_Response(array('url' => $webhook_url), 200);
    }

    /**
     * Обработчик GitHub webhook
     */
    public function handle_github_webhook($request) {
        $payload = $request->get_body();
        $signature = $request->get_header('X-Hub-Signature-256');

        // Валидация payload (опционально - можно настроить секрет в GitHub)
        // if (!$this->validate_webhook_signature($payload, $signature)) {
        //     return new WP_REST_Response(array('error' => 'Invalid signature'), 403);
        // }

        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_REST_Response(array('error' => 'Invalid JSON'), 400);
        }

        // Обрабатываем push event
        if (isset($data['ref']) && isset($data['repository'])) {
            $this->process_github_push($data);
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Обработка push event от GitHub
     */
    private function process_github_push($data) {
        $branch = str_replace('refs/heads/', '', $data['ref']);
        $repo_url = $data['repository']['html_url'];
        $raw_base_url = str_replace('github.com', 'raw.githubusercontent.com', $repo_url) . '/' . $branch;

        // Получаем все измененные файлы
        $modified_files = array();
        foreach ($data['commits'] as $commit) {
            $modified_files = array_merge(
                $modified_files,
                $commit['added'],
                $commit['modified'],
                $commit['removed']
            );
        }

        $modified_files = array_unique($modified_files);

        // Находим блоки, которые ссылаются на измененные файлы
        $this->update_affected_blocks($raw_base_url, $modified_files);
    }

    /**
     * Обновление блоков, затронутых изменениями
     */
    private function update_affected_blocks($raw_base_url, $modified_files) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'easy_changelog_blocks';

        foreach ($modified_files as $file) {
            $file_url = $raw_base_url . '/' . $file;

            // Находим блоки, которые используют этот URL
            $blocks = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE json_url LIKE %s",
                $file_url . '%'
            ));

            foreach ($blocks as $block) {
                $this->update_block_data($block->post_id, $block->block_id, $block->json_url);
            }
        }
    }

    /**
     * Обновление данных блока
     */
    private function update_block_data($post_id, $block_id, $json_url) {
        $this->log_webhook('🔄 UPDATE_BLOCK_DATA_START', array(
            'post_id' => $post_id,
            'block_id' => $block_id,
            'json_url' => $json_url
        ));

        $post = get_post($post_id);
        if (!$post) {
            $this->log_webhook('❌ POST_NOT_FOUND', $post_id);
            return;
        }

        $this->log_webhook('📄 POST_CONTENT_BEFORE', 'Post content length: ' . strlen($post->post_content));

        $blocks = parse_blocks($post->post_content);
        $updated = $this->update_block_content($blocks, $block_id, $json_url);

        $this->log_webhook('🔄 UPDATE_RESULT', array(
            'updated' => $updated,
            'blocks_processed' => count($blocks)
        ));

        if ($updated) {
            $updated_content = serialize_blocks($blocks);
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $updated_content
            ));

            $this->log_webhook('💾 POST_UPDATED', array(
                'post_id' => $post_id,
                'result' => $result,
                'has_errors' => is_wp_error($result),
                'new_content_length' => strlen($updated_content)
            ));

            // Обновляем время последнего обновления
            global $wpdb;
            $table_name = $wpdb->prefix . 'easy_changelog_blocks';
            $wpdb->update(
                $table_name,
                array('last_updated' => current_time('mysql')),
                array('post_id' => $post_id, 'block_id' => $block_id),
                array('%s'),
                array('%d', '%s')
            );

            $this->log_webhook('✅ UPDATE_COMPLETE', 'Block data successfully updated');
        } else {
            $this->log_webhook('⚠️ NO_UPDATE', 'Block content was not updated - possible issue');
        }
    }

    /**
     * Логирование webhook активности
     */
    private function log_webhook($action, $data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Easy Changelog Webhook: ' . $action . ' - ' . print_r($data, true));
        }
    }

    /**
     * Обновление контента блока - ВСЕГДА обновляем при изменении внешних данных
     */
    private function update_block_content(&$blocks, $block_id, $json_url) {
        $updated = false;

        foreach ($blocks as &$block) {
            if ($block['blockName'] === 'easy-changelog/changelog' &&
                $block['attrs']['blockId'] === $block_id) {

                $new_data = $this->fetch_external_json($json_url, false);

                if ($new_data !== false) {
                    $new_json_data = json_encode($new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    // ВСЕГДА обновляем, даже если данные пустые
                    $block['attrs']['changelogData'] = $new_json_data;
                    $updated = true;

                    $this->log_webhook('🔄 BLOCK_DATA_UPDATED', array(
                        'block_id' => $block_id,
                        'data_length' => strlen($new_json_data),
                        'items_count' => count($new_data)
                    ));
                } else {
                    $this->log_webhook('❌ FETCH_FAILED', 'Could not fetch data from: ' . $json_url);
                }
            }

            if (!empty($block['innerBlocks'])) {
                if ($this->update_block_content($block['innerBlocks'], $block_id, $json_url)) {
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Загрузка внешнего JSON
     */
    private function fetch_external_json($url, $use_cache = true) {
        if (empty($url)) return false;

        // Для автоматических обновлений не используем кеш
        if (!$use_cache) {
            $url = $this->refreshGitHubUrl($url);
        }

        $transient_key = 'easy_changelog_' . md5($url);

        if ($use_cache) {
            $cached_data = get_transient($transient_key);
            if ($cached_data !== false) return $cached_data;
        }

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Easy-Changelog-WordPress-Plugin/' . $this->version
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            if ($use_cache) {
                set_transient($transient_key, $data, 5 * MINUTE_IN_SECONDS); // 5 минут кеш
            }
            return $data;
        }

        return false;
    }

    /**
     * Обновление GitHub URL для избежания кеширования
     */
    private function refreshGitHubUrl($url) {
        if (strpos($url, 'raw.githubusercontent.com') !== false) {
            return $url . '?t=' . time();
        }
        return $url;
    }

    /**
     * Обработчик REST API для загрузки внешнего JSON
     */
    public function handle_external_fetch($request) {
        $url = $request->get_param('url');
        $data = $this->fetch_external_json($url, false); // Всегда свежие данные

        if ($data === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Не удалось загрузить данные из указанного URL', 'easy-changelog')
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data,
            'message' => __('Данные успешно загружены', 'easy-changelog')
        ));
    }

    /**
     * Очистка устаревших записей
     */
    public function cleanup_old_records() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'easy_changelog_blocks';

        // Удаляем записи для постов, которые больше не существуют
        $wpdb->query("
            DELETE ecb FROM $table_name ecb
            LEFT JOIN {$wpdb->posts} p ON ecb.post_id = p.ID
            WHERE p.ID IS NULL
        ");

        // Удаляем записи старше 30 дней
        $wpdb->query($wpdb->prepare("
            DELETE FROM $table_name
            WHERE last_updated < DATE_SUB(%s, INTERVAL 30 DAY)
        ", current_time('mysql')));
    }

    /**
     * Рендеринг блока
     */
    public function render_block($attributes) {
        $changelog_data = array();

        if (!empty($attributes['useExternalUrl']) && !empty($attributes['jsonUrl'])) {
            $external_data = $this->fetch_external_json($attributes['jsonUrl'], true);
            if ($external_data !== false) {
                $changelog_data = $external_data;
            } else {
                // Fallback на локальные данные
                $changelog_data = json_decode($attributes['changelogData'], true);
            }
        } else {
            $changelog_data = json_decode($attributes['changelogData'], true);
        }

        // Проверяем корректность данных
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changelog_data)) {
            return '<div class="easy-changelog-error">' .
                   __('Некорректный формат данных changelog', 'easy-changelog') .
                   '</div>';
        }

        // Если данных нет - показываем сообщение
        if (empty($changelog_data)) {
            return '<div class="easy-changelog-empty">' .
                   __('История изменений пока пуста', 'easy-changelog') .
                   '</div>';
        }

        ob_start();
        ?>
        <div class="easy-changelog-block">
            <h2 class="easy-changelog-title"><?php echo __('История изменений', 'easy-changelog'); ?></h2>

            <?php foreach ($changelog_data as $release): ?>
                <div class="easy-changelog-release">
                    <div class="easy-changelog-version">
                        <strong><?php echo esc_html($release['version'] ?? ''); ?></strong>
                    </div>

                    <div class="easy-changelog-date">
                        <?php echo esc_html($release['date'] ?? ''); ?>
                    </div>

                    <div class="easy-changelog-content">
                        <?php if (isset($release['added']) && is_array($release['added']) && !empty($release['added'])): ?>
                            <ul class="easy-changelog-added">
                                <?php foreach ($release['added'] as $item): ?>
                                    <li class="easy-changelog-item easy-changelog-item-added"><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (isset($release['fixed']) && is_array($release['fixed']) && !empty($release['fixed'])): ?>
                            <ul class="easy-changelog-fixed">
                                <?php foreach ($release['fixed'] as $item): ?>
                                    <li class="easy-changelog-item easy-changelog-item-fixed"><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_frontend_scripts() {
        if (has_block('easy-changelog/changelog')) {
            wp_enqueue_style(
                'easy-changelog-frontend-style',
                plugins_url('build/style-index.css', __FILE__),
                array(),
                $this->version
            );
        }
    }

    public function add_block_category($categories, $post) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'easy-changelog',
                    'title' => __('Easy Changelog', 'easy-changelog'),
                ),
            )
        );
    }
}

/**
 * Функции активации/деактивации ВНЕ класса
 */
function easy_changelog_activate() {
    $plugin = new EasyChangelog();
    $plugin->check_db_version();
}

function easy_changelog_deactivate() {
    wp_clear_scheduled_hook('easy_changelog_cleanup');
}

// Регистрируем хуки активации/деактивации
register_activation_hook(__FILE__, 'easy_changelog_activate');
register_deactivation_hook(__FILE__, 'easy_changelog_deactivate');

// Инициализируем плагин
new EasyChangelog();