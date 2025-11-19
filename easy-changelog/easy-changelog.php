<?php
/**
 * Plugin Name: Easy Changelog
 * Plugin URI: http://bychko.ru
 * Description: Gutenberg блок для отображения истории изменений (changelog) с встроенным редактором JSON и предпросмотром в реальном времени.
 * Version: 1.1.0
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
                'changelogData' => __('Данные Changelog (JSON)', 'easy-changelog'),
                'jsonHelp' => __('Введите данные в формате JSON. Каждый релиз должен содержать version, date и added.', 'easy-changelog'),
                'error' => __('Ошибка:', 'easy-changelog'),
                'invalidJson' => __('Некорректный JSON формат', 'easy-changelog'),
                'mustBeArray' => __('Данные должны быть массивом', 'easy-changelog'),
                'cannotPreview' => __('Невозможно отобразить предпросмотр из-за ошибок в JSON', 'easy-changelog'),
                'changelogTitle' => __('История изменений', 'easy-changelog'),
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
    "added": ["Первоначальный релиз плагина", "Базовая функциональность блоков"]
  },
  {
    "version": "0.9.0",
    "date": "15.11.2025",
    "added": ["Бета-версия плагина", "Тестирование функциональности"]
  }
]'
                )
            )
        ));
    }

    /**
     * Рендеринг блока на фронтенде
     *
     * @param array $attributes Атрибуты блока из редактора
     * @return string HTML разметка блока
     */
    public function render_block($attributes) {
        // Распарсиваем JSON данные
        $changelog_data = json_decode($attributes['changelogData'], true);

        // Проверяем корректность JSON данных
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changelog_data)) {
            return '<div class="easy-changelog-error">' .
                   __('Некорректный формат данных changelog', 'easy-changelog') .
                   '</div>';
        }

        // Начинаем буферизацию вывода
        ob_start();
        ?>
        <div class="easy-changelog-block">
            <!-- Заголовок блока -->
            <h2 class="easy-changelog-title"><?php echo __('История изменений', 'easy-changelog'); ?></h2>

            <!-- Отображение релизов -->
            <?php foreach ($changelog_data as $release): ?>
                <div class="easy-changelog-release">
                    <!-- Версия релиза -->
                    <div class="easy-changelog-version">
                        <strong><?php echo esc_html($release['version'] ?? ''); ?></strong>
                    </div>

                    <!-- Дата релиза -->
                    <div class="easy-changelog-date">
                        <?php echo esc_html($release['date'] ?? ''); ?>
                    </div>

                    <!-- Список изменений -->
                    <div class="easy-changelog-added">
                        <?php if (isset($release['added']) && is_array($release['added'])): ?>
                            <ul>
                                <?php foreach ($release['added'] as $item): ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        // Возвращаем буферизованный контент
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
                '1.1.0'
            );
        }
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