<?php
/**
 * Plugin Name: Text with Side
 * Description: Гутенберговский блок для текста с боковым изображением, который отображается на полях
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Version: 1.1.0
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'text-with-side', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'text-with-side-frontend',
			plugins_url( 'assets/frontend.css', __FILE__ ),
			array(),
			'1.1.0'
		);
	}

	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'text-with-side-editor',
			plugins_url( 'build/index.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'1.1.0'
		);

		wp_enqueue_style(
			'text-with-side-editor',
			plugins_url( 'assets/editor.css', __FILE__ ),
			array(),
			'1.1.0'
		);
	}

	public function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

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
		$content_text = $attributes['content'];
		$image_id = $attributes['imageId'];
		$image_url = $attributes['imageUrl'];
		$image_alt = $attributes['imageAlt'];
		$position = $attributes['position'];
		$image_link = $attributes['imageLink'];
		$width = $attributes['width'];

		if ( empty( $content_text ) && empty( $image_url ) ) {
			return '';
		}

		$wrapper_class = 'text-with-side-block text-with-side-' . esc_attr( $position );

		$image_html = '';
		if ( ! empty( $image_url ) ) {
			$image = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ) . '" style="width: ' . esc_attr( $width ) . ';" />';

			if ( $image_link === 'media' && $image_id ) {
				$media_url = wp_get_attachment_url( $image_id );
				$image = '<a href="' . esc_url( $media_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} elseif ( $image_link === 'attachment' && $image_id ) {
				$attachment_url = get_attachment_link( $image_id );
				$image = '<a href="' . esc_url( $attachment_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} else {
				$image = '<div class="text-with-side-image-link">' . $image . '</div>';
			}

			$image_html = '<div class="text-with-side-image">' . $image . '</div>';
		}

		$text_html = '';
		if ( ! empty( $content_text ) ) {
			$text_html = '<div class="text-with-side-content">' . wp_kses_post( $content_text ) . '</div>';
		}

		$output = '<div class="' . $wrapper_class . '">';
		$output .= '<div class="text-with-side-inner">';
		$output .= $image_html;
		$output .= $text_html;
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}

new TextWithSidePlugin();