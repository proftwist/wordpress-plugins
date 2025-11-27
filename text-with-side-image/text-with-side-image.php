<?php
/**
 * Plugin Name: Text with Side Image
 * Plugin URI: https://bychko.ru
 * Description: Блок с текстом и опциональной картинкой на полях. Block with text and optional side image.
 * Version: 1.2.0
 * Author: Владимир Бычко
 * Author URI: https://bychko.ru
 * Text Domain: tsi
 * Domain Path: /languages
 * License: GPL v2 or later
 */

defined( 'ABSPATH' ) || exit;

class TextWithSideImage {

    public function __construct() {
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'enqueue_block_assets', array( $this, 'block_assets' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'tsi',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    public function register_block() {
        register_block_type( __DIR__ . '/build' );
    }

    public function block_assets() {
        // Фронтенд стили
        if ( ! is_admin() ) {
            wp_enqueue_style(
                'tsi-frontend-style',
                plugin_dir_url( __FILE__ ) . 'build/style-index.css',
                array(),
                '1.0.0'
            );
        }

        // Стили редактора
        if ( is_admin() ) {
            wp_enqueue_style(
                'tsi-editor-style',
                plugin_dir_url( __FILE__ ) . 'build/index.css',
                array(),
                '1.0.0'
            );
        }
    }
}

new TextWithSideImage();