<?php
/**
 * Plugin Name: Easy Changelog
 * Plugin URI: http://bychko.ru
 * Description: Гутенберговский блок для создания красивого чейнджлога с предпросмотром
 * Version: 1.0.1
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Text Domain: easy-changelog
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('EASY_CHANGELOG_VERSION', '1.0.1');
define('EASY_CHANGELOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EASY_CHANGELOG_PLUGIN_PATH', plugin_dir_path(__FILE__));

class EasyChangelog {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_action('init', array($this, 'load_textdomain'));
    }

    public function init() {
        // Регистрируем скрипты и стили
        $this->register_assets();

        // Регистрируем блок
        register_block_type('easy-changelog/changelog', array(
            'api_version' => 2,
            'editor_script' => 'easy-changelog-editor-script',
            'editor_style' => 'easy-changelog-editor-style',
            'style' => 'easy-changelog-frontend-style',
            'render_callback' => array($this, 'render_block'),
        ));
    }

    public function register_assets() {
        // Скрипты для редактора
        wp_register_script(
            'easy-changelog-editor-script',
            EASY_CHANGELOG_PLUGIN_URL . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            EASY_CHANGELOG_VERSION,
            true
        );

        // Локализация для JavaScript
        wp_set_script_translations('easy-changelog-editor-script', 'easy-changelog', EASY_CHANGELOG_PLUGIN_PATH . 'build');

        // Стили для редактора
        wp_register_style(
            'easy-changelog-editor-style',
            EASY_CHANGELOG_PLUGIN_URL . 'build/index.css',
            array('wp-edit-blocks'),
            EASY_CHANGELOG_VERSION
        );

        // Стили для фронтенда
        wp_register_style(
            'easy-changelog-frontend-style',
            EASY_CHANGELOG_PLUGIN_URL . 'build/style-index.css',
            array(),
            EASY_CHANGELOG_VERSION
        );
    }

    public function enqueue_block_assets() {
        if (has_block('easy-changelog/changelog')) {
            wp_enqueue_style('easy-changelog-frontend-style');
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'easy-changelog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function render_block($attributes) {
        if (empty($attributes['changelogData'])) {
            return '<p>' . __('No changelog data provided', 'easy-changelog') . '</p>';
        }

        $changelog_data = json_decode($attributes['changelogData'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changelog_data)) {
            return '<p>' . __('Invalid changelog data', 'easy-changelog') . '</p>';
        }

        ob_start();
        ?>
        <div class="easy-changelog-block">
            <h3 class="easy-changelog-title"><?php _e('Changelog', 'easy-changelog'); ?></h3>
            <div class="easy-changelog-list">
                <?php foreach ($changelog_data as $release): ?>
                    <?php if (isset($release['version']) && isset($release['date'])): ?>
                        <div class="easy-changelog-release">
                            <div class="easy-changelog-header">
                                <strong class="easy-changelog-version"><?php echo esc_html($release['version']); ?></strong>
                                <span class="easy-changelog-date"><?php echo esc_html($this->format_date($release['date'])); ?></span>
                            </div>
                            <?php if (!empty($release['added']) && is_array($release['added'])): ?>
                                <div class="easy-changelog-section">
                                    <h4 class="easy-changelog-section-title"><?php _e('Added', 'easy-changelog'); ?></h4>
                                    <ul class="easy-changelog-items">
                                        <?php foreach ($release['added'] as $item): ?>
                                            <li class="easy-changelog-item"><?php echo esc_html($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Форматирование даты в российском формате (DD.MM.YYYY)
     */
    private function format_date($date_string) {
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('d.m.Y', $timestamp);
        }
        return $date_string; // Возвращаем оригинальную строку, если парсинг не удался
    }
}

new EasyChangelog();