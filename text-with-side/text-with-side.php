<?php
/**
 * Plugin Name: Text with Side
 * Description: Гутенберговский блок для текста с боковым изображением, который отображается на полях
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Version: 1.0.0
 * Text Domain: text-with-side
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TextWithSidePlugin {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_head', array( $this, 'add_inline_styles' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'text-with-side', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			'text-with-side-frontend',
			plugins_url( 'build/style.css', __FILE__ ),
			array(),
			'1.0.0'
		);
	}

	public function add_inline_styles() {
		// Рассчитываем ширину бокового блока на основе ширины контента
		$content_width = isset( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] : 1200;
		$side_width = min( 200, $content_width * 0.2 ); // 20% от ширины контента, но не более 200px
		$gap = 40; // Отступ между основным контентом и боковым блоком

		$css = "
			.text-with-side-block {
				--side-width: {$side_width}px;
				--side-gap: {$gap}px;
			}

			@media (max-width: 768px) {
				.text-with-side-block {
					--side-width: 100%;
					--side-gap: 20px;
				}
			}
		";

		wp_add_inline_style( 'text-with-side-frontend', $css );
	}

	public function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php' );

		wp_register_script(
			'text-with-side-editor',
			plugins_url( 'build/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		wp_register_style(
			'text-with-side-editor',
			plugins_url( 'build/index.css', __FILE__ ),
			array(),
			$asset_file['version']
		);

		register_block_type( 'text-with-side/text-with-side', array(
			'editor_script' => 'text-with-side-editor',
			'editor_style'  => 'text-with-side-editor',
			'render_callback' => array( $this, 'render_block' ),
			'attributes' => array(
				'content' => array(
					'type' => 'string',
					'default' => '',
				),
				'imageId' => array(
					'type' => 'number',
					'default' => 0,
				),
				'imageUrl' => array(
					'type' => 'string',
					'default' => '',
				),
				'imageAlt' => array(
					'type' => 'string',
					'default' => '',
				),
				'position' => array(
					'type' => 'string',
					'default' => 'left',
				),
				'imageLink' => array(
					'type' => 'string',
					'default' => 'none',
				),
				'width' => array(
					'type' => 'string',
					'default' => '150px',
				),
			),
		) );
	}

	public function render_block( $attributes, $content ) {
		$wrapper_class = 'text-with-side-block text-with-side-' . esc_attr( $attributes['position'] );

		return sprintf(
			'<div class="%s" data-position="%s">%s</div>',
			$wrapper_class,
			esc_attr( $attributes['position'] ),
			$content
		);
	}
}

new TextWithSidePlugin();