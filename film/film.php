<?php
/**
 * Plugin Name: Фотоплёнка
 * Description: Гутенберговский блок для создания галереи изображений в стиле классической фотоплёнки с горизонтальной прокруткой
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Version: 1.0.1
 * Text Domain: film
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

// Загружаем файлы локализации
add_action('init', 'film_load_textdomain');

/**
 * Загружает файлы переводов плагина
 *
 * @return void
 */
function film_load_textdomain() {
    load_plugin_textdomain('film', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Регистрируем Gutenberg блок
add_action('init', 'film_register_block');

/**
 * Регистрирует блок "Фотоплёнка" в Gutenberg редакторе
 *
 * @return void
 */
function film_register_block() {
    // Проверяем доступность функции регистрации блоков
    if (!function_exists('register_block_type')) {
        return;
    }

    // Путь к файлу с зависимостями и версией
    $asset_file_path = plugin_dir_path(__FILE__) . 'build/index.asset.php';

    // Проверяем существование файла с зависимостями
    if (!file_exists($asset_file_path)) {
        return;
    }

    // Загружаем зависимости и версию
    $asset_file = include($asset_file_path);

    // Регистрируем JavaScript скрипт редактора
    wp_register_script(
        'film-block-editor',
        plugins_url('build/index.js', __FILE__),
        $asset_file['dependencies'],
        $asset_file['version']
    );

    // Регистрируем CSS стили для редактора
    wp_register_style(
        'film-block-editor',
        plugins_url('build/style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'build/style.css')
    );

    // Регистрируем CSS стили для фронтенда
    wp_register_style(
        'film-block-frontend',
        plugins_url('build/style-frontend.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'build/style-frontend.css')
    );

    // Регистрируем сам блок
    register_block_type('film/film-gallery', array(
        'api_version' => 2,                    // Используем API версии 2
        'editor_script' => 'film-block-editor', // JS для редактора
        'editor_style' => 'film-block-editor',  // CSS для редактора
        'style' => 'film-block-frontend',      // CSS для фронтенда
        'render_callback' => 'film_render_callback', // Функция рендеринга
        'attributes' => array(                  // Атрибуты блока
            'images' => array(
                'type' => 'array',
                'default' => array()
            ),
            'height' => array(
                'type' => 'number',
                'default' => 500
            ),
            'linkTo' => array(
                'type' => 'string',
                'default' => 'none'
            ),
            'align' => array(
                'type' => 'string',
                'default' => 'none'
            )
        )
    ));
}

/**
 * Функция рендеринга блока на фронтенде
 *
 * @param array $attributes Атрибуты блока
 * @param string $content Контент блока
 *
 * @return string HTML разметка блока
 */
function film_render_callback($attributes, $content) {
    // Проверяем наличие изображений
    if (empty($attributes['images']) || !is_array($attributes['images'])) {
        return '<p>' . __('Выберите изображения для фотоплёнки', 'film') . '</p>';
    }

    // Получаем значения атрибутов
    $height = isset($attributes['height']) ? $attributes['height'] : 500;
    $linkTo = isset($attributes['linkTo']) ? $attributes['linkTo'] : 'none';
    $align = isset($attributes['align']) ? $attributes['align'] : 'none';

    // Формируем классы для выравнивания
    $wrapper_class = 'wp-block-film-film-gallery';
    if ($align && $align !== 'none') {
        $wrapper_class .= ' align' . $align;
    }

    // Атрибуты обёртки блока
    $wrapper_attributes = get_block_wrapper_attributes(array(
        'style' => 'height: ' . esc_attr($height) . 'px;',
        'class' => $wrapper_class
    ));

    $output = '<div ' . $wrapper_attributes . '>';
    $output .= '<div class="film-strip">';

    // Обрабатываем каждое изображение
    foreach ($attributes['images'] as $image) {
        if (empty($image['url'])) continue;

        $img_url = $image['url'];
        $img_alt = isset($image['alt']) ? $image['alt'] : '';

        // Определяем тип ссылки
        $link = '';
        switch ($linkTo) {
            case 'media':
                $link = $img_url;
                break;
            case 'attachment':
                $link = !empty($image['id']) ? get_attachment_link($image['id']) : $img_url;
                break;
            case 'none':
            default:
                $link = false;
                break;
        }

        // Добавляем ссылку если нужно
        if ($link) {
            $output .= '<a href="' . esc_url($link) . '" class="film-image-link" target="_blank" rel="noopener noreferrer">';
        }

        // Создаём кадр для изображения
        $output .= '<div class="film-frame">';
        $output .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" />';
        $output .= '</div>';

        // Закрываем ссылку
        if ($link) {
            $output .= '</a>';
        }
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

// Хук активации плагина
register_activation_hook(__FILE__, 'film_activation_check');

/**
 * Проверяет совместимость при активации плагина
 *
 * @return void
 */
function film_activation_check() {
    // Проверяем наличие поддержки блоков
    if (!function_exists('register_block_type')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Этот плагин требует WordPress версии 5.0 или выше с поддержкой Гутенберга.', 'film'));
    }
}