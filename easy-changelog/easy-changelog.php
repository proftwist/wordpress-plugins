<?php
/**
 * Plugin Name: Easy Changelog
 * Plugin URI: http://bychko.ru
 * Description: Gutenberg блок для отображения истории изменений (changelog) с поддержкой внешних JSON из GitHub.
 * Version: 1.3.0
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
 *
 * Управляет регистрацией блока, локализацией и рендерингом
 */
class EasyChangelog {

    /**
     * Конструктор класса
     *
     * Подключает все необходимые хуки WordPress для работы плагина
     */
    public function __construct() {
        // Инициализация плагина
        add_action('init', array($this, 'init'));

        // Подключение стилей для фронтенда
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // Подключение локализации для редактора блоков
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // Регистрация REST API для внешних JSON
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Добавление категории блоков в редактор
        add_filter('block_categories_all', array($this, 'add_block_category'), 10, 2);
    }

    /**
     * Инициализация плагина
     *
     * Загружает локализацию и регистрирует блок, если Gutenberg активен
     */
    public function init() {
        // Загружаем файлы переводов
        $this->load_textdomain();

        // Проверяем доступность Gutenberg и регистрируем блок
        if (function_exists('register_block_type')) {
            $this->register_block();
        }
    }

    /**
     * Загрузка текстового домена для локализации
     *
     * Подключает файлы переводов из папки languages/
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'easy-changelog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Подключение локализации для JavaScript редактора
     *
     * Передает переводы из PHP в JavaScript через wp_localize_script
     * для корректной работы локализации в Gutenberg
     */
    public function enqueue_block_editor_assets() {
        wp_localize_script(
            'easy-changelog-editor-script',
            'easyChangelogI18n',
            array(
                'title' => __('Easy Changelog', 'easy-changelog'),
                'description' => __('Блок для отображения changelog с редактором JSON и предпросмотром', 'easy-changelog'),
                'jsonEditor' => __('Редактор JSON', 'easy-changelog'),
                'preview' => __('Предпросмотр', 'easy-changelog'),
                'externalJson' => __('Внешний JSON', 'easy-changelog'),
                'changelogData' => __('Данные Changelog (JSON)', 'easy-changelog'),
                'jsonUrl' => __('URL внешнего JSON файла', 'easy-changelog'),
                'jsonHelp' => __('Введите данные в формате JSON. Каждый релиз должен содержать version, date, added и fixed.', 'easy-changelog'),
                'urlHelp' => __('Укажите прямую ссылку на JSON файл в GitHub или другом хранилище', 'easy-changelog'),
                'githubHelp' => __('Как получить ссылку на GitHub:', 'easy-changelog'),
                'githubInstructions' => __('1. Перейдите в репозиторий на GitHub → 2. Выберите ветку → 3. Найдите файл → 4. Нажмите "Raw" → 5. Скопируйте URL из адресной строки', 'easy-changelog'),
                'exampleUrl' => __('Пример: https://raw.githubusercontent.com/username/repo/main/changelog.json', 'easy-changelog'),
                'error' => __('Ошибка:', 'easy-changelog'),
                'invalidJson' => __('Некорректный JSON формат', 'easy-changelog'),
                'mustBeArray' => __('Данные должны быть массивом', 'easy-changelog'),
                'cannotPreview' => __('Невозможно отобразить предпросмотр из-за ошибок в JSON', 'easy-changelog'),
                'changelogTitle' => __('История изменений', 'easy-changelog'),
                'fetchError' => __('Ошибка загрузки внешнего JSON', 'easy-changelog'),
                'fetchSuccess' => __('Данные успешно загружены', 'easy-changelog'),
                'loadFromUrl' => __('Загрузить из URL', 'easy-changelog'),
            )
        );
    }

    /**
     * Регистрация Gutenberg блока
     *
     * Подключает скрипты, стили и регистрирует блок с атрибутами
     */
    public function register_block() {
        // Загружаем информацию о зависимостях из файла сборки
        $asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');

        // Регистрируем JavaScript редактора блока
        wp_register_script(
            'easy-changelog-editor-script',
            plugins_url('build/index.js', __FILE__),
            $asset_file['dependencies'],
            $asset_file['version']
        );

        // Регистрируем стили редактора блока
        wp_register_style(
            'easy-changelog-editor-style',
            plugins_url('build/style-index.css', __FILE__),
            array(),
            $asset_file['version']
        );

        // Регистрируем тип блока в Gutenberg
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
                )
            )
        ));
    }

    /**
     * Загрузка внешнего JSON с кешированием
     */
    private function fetch_external_json($url) {
        if (empty($url)) {
            return false;
        }

        $transient_key = 'easy_changelog_' . md5($url);
        $cached_data = get_transient($transient_key);

        // Кешируем на 1 час
        if ($cached_data !== false) {
            return $cached_data;
        }

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Easy-Changelog-WordPress-Plugin/1.3.0'
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
            set_transient($transient_key, $data, HOUR_IN_SECONDS);
            return $data;
        }

        return false;
    }

    /**
     * Рендеринг блока на фронтенде
     *
     * @param array $attributes Атрибуты блока из редактора
     * @return string HTML разметка блока
     */
    public function render_block($attributes) {
        $changelog_data = array();

        // Если используется внешний URL - загружаем оттуда
        if (!empty($attributes['useExternalUrl']) && !empty($attributes['jsonUrl'])) {
            $external_data = $this->fetch_external_json($attributes['jsonUrl']);
            if ($external_data !== false) {
                $changelog_data = $external_data;
            } else {
                // Fallback на локальные данные при ошибке загрузки
                $changelog_data = json_decode($attributes['changelogData'], true);
            }
        } else {
            // Используем локальные данные
            $changelog_data = json_decode($attributes['changelogData'], true);
        }

        // Проверяем корректность данных
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changelog_data)) {
            return '<div class="easy-changelog-error">' .
                   __('Некорректный формат данных changelog', 'easy-changelog') .
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
                        <?php if (isset($release['added']) && is_array($release['added'])): ?>
                            <ul class="easy-changelog-added">
                                <?php foreach ($release['added'] as $item): ?>
                                    <li class="easy-changelog-item easy-changelog-item-added"><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (isset($release['fixed']) && is_array($release['fixed'])): ?>
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

    /**
     * Подключение стилей для фронтенда
     *
     * Подключает стили только если блок присутствует на странице
     */
    public function enqueue_frontend_scripts() {
        if (has_block('easy-changelog/changelog')) {
            wp_enqueue_style(
                'easy-changelog-frontend-style',
                plugins_url('build/style-index.css', __FILE__),
                array(),
                '1.3.0'
            );
        }
    }

    /**
     * Регистрация REST API для загрузки внешних данных
     */
    public function register_rest_routes() {
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
    }

    /**
     * Обработчик REST API для загрузки внешнего JSON
     */
    public function handle_external_fetch($request) {
        $url = $request->get_param('url');
        $data = $this->fetch_external_json($url);

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
     * Добавление категории блоков в редактор
     *
     * @param array $categories Существующие категории блоков
     * @param WP_Post $post Текущий пост
     * @return array Обновленный список категорий
     */
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

// Создаем экземпляр класса для запуска плагина
new EasyChangelog();