<?php
/**
 * Plugin Name: Image Spoiler
 * Plugin URI: http://bychko.ru
 * Description: Добавляет возможность размытия изображений в блоке Gutenberg с опциональным текстом предупреждения
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: image-spoiler
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('IMAGE_SPOILER_VERSION', '1.0.0');
define('IMAGE_SPOILER_PATH', plugin_dir_path(__FILE__));
define('IMAGE_SPOILER_URL', plugin_dir_url(__FILE__));

/**
 * Основной класс плагина Image Spoiler
 */
class Image_Spoiler {

    /**
     * Инициализация плагина
     */
    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Загрузка файлов локализации
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'image-spoiler',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Подключение ассетов для редактора
     */
    public function enqueue_editor_assets() {
        // Получаем информацию о зависимостях из сборки
        $asset_file = IMAGE_SPOILER_PATH . 'build/index.asset.php';

        if (!file_exists($asset_file)) {
            return;
        }

        $asset = require $asset_file;

        // Подключаем скрипт редактора
        wp_enqueue_script(
            'image-spoiler-editor',
            IMAGE_SPOILER_URL . 'build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        // Подключаем стили редактора
        wp_enqueue_style(
            'image-spoiler-editor',
            IMAGE_SPOILER_URL . 'build/index.css',
            array('wp-edit-blocks'),
            IMAGE_SPOILER_VERSION
        );

        // Передаем переводы для JS
        wp_set_script_translations(
            'image-spoiler-editor',
            'image-spoiler',
            IMAGE_SPOILER_PATH . 'languages'
        );
    }

    /**
     * Подключение ассетов для фронтенда
     */
    public function enqueue_frontend_assets() {
        // Подключаем стили для фронтенда
        wp_enqueue_style(
            'image-spoiler-frontend',
            IMAGE_SPOILER_URL . 'build/style-index.css',
            array(),
            IMAGE_SPOILER_VERSION
        );
    }
}

// Инициализация плагина
new Image_Spoiler();