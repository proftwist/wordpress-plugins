<?php
/**
 * Plugin Name: Image Spoiler
 * Plugin URI: http://bychko.ru
 * Description: Добавляет возможность размытия изображений в блоке Gutenberg с опциональным текстом предупреждения
 * Version: 1.0.1
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
define('IMAGE_SPOILER_VERSION', '1.0.1');
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

/**
 * Модификация вывода блока Image для добавления спойлера
 *
 * @param string $block_content Содержимое блока
 * @param array $block Данные блока
 * @return string Модифицированное содержимое
 */
function image_spoiler_render_block($block_content, $block) {
    // Проверяем, что это блок Image
    if ('core/image' !== $block['blockName']) {
        return $block_content;
    }

    // Проверяем, включен ли спойлер
    if (empty($block['attrs']['isSpoiler'])) {
        return $block_content;
    }

    // Получаем текст спойлера
    $spoiler_text = isset($block['attrs']['spoilerText']) ? $block['attrs']['spoilerText'] : '';

    // Используем DOMDocument для корректной модификации HTML
    $dom = new DOMDocument('1.0', 'UTF-8');
    // Подавляем предупреждения о невалидном HTML
    libxml_use_internal_errors(true);

    // Добавляем мета-теги для правильной работы с UTF-8
    $dom->loadHTML('<?xml encoding="UTF-8">' . $block_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Находим элемент img
    $images = $dom->getElementsByTagName('img');

    if ($images->length === 0) {
        return $block_content;
    }

    $img = $images->item(0);

    // Добавляем класс спойлера к изображению
    $current_class = $img->getAttribute('class');
    $new_class = trim($current_class . ' image-spoiler');
    $img->setAttribute('class', $new_class);

    // Находим figure (обертку блока)
    $figures = $dom->getElementsByTagName('figure');

    if ($figures->length > 0) {
        $figure = $figures->item(0);

        // Добавляем класс к figure
        $current_figure_class = $figure->getAttribute('class');
        $new_figure_class = trim($current_figure_class . ' has-image-spoiler');
        $figure->setAttribute('class', $new_figure_class);

        // Если есть текст спойлера, добавляем его
        if (!empty($spoiler_text)) {
            $text_div = $dom->createElement('div');
            $text_div->setAttribute('class', 'image-spoiler-text');
            $text_div->setAttribute('aria-hidden', 'true');
            $text_node = $dom->createTextNode($spoiler_text);
            $text_div->appendChild($text_node);

            // Добавляем текст после img
            $img->parentNode->insertBefore($text_div, $img->nextSibling);
        }
    }

    // Возвращаем модифицированный HTML
    $output = $dom->saveHTML();

    // Убираем добавленные теги
    $output = str_replace('<?xml encoding="UTF-8">', '', $output);

    return $output;
}

add_filter('render_block', 'image_spoiler_render_block', 10, 2);

// Инициализация плагина
new Image_Spoiler();