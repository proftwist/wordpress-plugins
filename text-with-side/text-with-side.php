<?php
/**
 * Plugin Name: Text with Side
 * Description: Gutenberg block for text with side image that floats in margins
 * Author: Vladimir Bychko
 * Author URI: http://bychko.ru
 * Version: 2.0.0
 * Text Domain: text-with-side
 * Domain Path: /languages
 */

// Security check - ensure script is running from WordPress
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class Text with Side
 *
 * This class is responsible for initialization and operation of the Gutenberg block
 * for displaying text with side image on page margins.
 */
class TextWithSidePlugin {

	/**
	 * Class constructor - initializes WordPress hooks
	 */
	public function __construct() {
		// Register block on WordPress initialization
		add_action( 'init', array( $this, 'init' ) );

		// Load translation files for internationalization - using proper hook
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Enqueue CSS styles for frontend (public site part)
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Enqueue JavaScript and CSS for Gutenberg editor
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Load translation files for multilingual support
	 *
	 * This function allows the plugin to automatically pick up
	 * translations depending on WordPress language.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'text-with-side', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue CSS styles for frontend
	 *
	 * These styles are applied only on the public part of the site,
	 * when user views the page.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'text-with-side-frontend',              // Unique style identifier
			plugins_url( 'assets/frontend.css', __FILE__ ), // Path to style file
			array(),                                // Dependencies (empty array)
			'2.0.0'                                 // Style version
		);
	}

	/**
	 * Enqueue JavaScript and CSS for Gutenberg editor
	 *
	 * These resources are loaded only in admin panel,
	 * when user edits content.
	 */
	public function enqueue_editor_assets() {
		// Enqueue JavaScript for block functionality
		wp_enqueue_script(
			'text-with-side-editor',                                         // Script identifier
			plugins_url( 'build/index.js', __FILE__ ),                      // Path to script
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), // WordPress dependencies
			'2.0.0'                                                          // Version
		);

		// Set up translations for JavaScript - ключевой шаг для локализации в Gutenberg
		wp_set_script_translations(
			'text-with-side-editor',      // handle скрипта
			'text-with-side',             // text-domain
			plugin_dir_path( __FILE__ ) . 'languages'
		);

		// Enqueue CSS styles for editor
		wp_enqueue_style(
			'text-with-side-editor',                  // Style identifier
			plugins_url( 'assets/editor.css', __FILE__ ), // Path to editor styles
			array(),                                  // Dependencies
			'2.0.0'                                   // Version
		);
	}

	/**
	 * Initialize Gutenberg block
	 *
	 * Registers new block type and its settings.
	 * Executed only if register_block_type function is available.
	 */
	public function init() {
		// Check that WordPress supports blocks (Gutenberg)
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register block with its settings and attributes
		register_block_type( 'text-with-side/text-with-side', array(
			'editor_script' => 'text-with-side-editor',      // JavaScript for editor
			'editor_style'  => 'text-with-side-editor',      // CSS for editor
			'render_callback' => array( $this, 'render_block' ), // Block rendering function

			// Block attributes - data saved to database
			'attributes' => array(
				'content' => array(                         // Text content of the block
					'type' => 'string',
					'default' => '',
				),
				'imageId' => array(                         // Image ID in media library
					'type' => 'number',
					'default' => 0,
				),
				'imageUrl' => array(                        // Image URL
					'type' => 'string',
					'default' => '',
				),
				'imageAlt' => array(                        // Alternative text for image
					'type' => 'string',
					'default' => '',
				),
				'position' => array(                        // Block position (left/right)
					'type' => 'string',
					'default' => 'left',
				),
				'imageLink' => array(                       // Image link type
					'type' => 'string',
					'default' => 'none',
				),
				'width' => array(                          // Image width
					'type' => 'string',
					'default' => '150px',
				),
			),
		) );
	}

	/**
	 * Block rendering function for frontend
	 *
	 * Gets block attributes and generates HTML for display
	 * on the public part of the site.
	 *
	 * @param array $attributes Block attributes from database
	 * @param string $content Block content (not used in this block)
	 * @return string HTML code of the block
	 */
	public function render_block( $attributes, $content ) {
		// Extract attributes into separate variables for convenience
		$content_text = $attributes['content'];
		$image_id = $attributes['imageId'];
		$image_url = $attributes['imageUrl'];
		$image_alt = $attributes['imageAlt'];
		$position = $attributes['position'];
		$image_link = $attributes['imageLink'];
		$width = $attributes['width'];

		// If there's no text or image - don't output the block
		if ( empty( $content_text ) && empty( $image_url ) ) {
			return '';
		}

		// Generate CSS classes for block based on position
		$wrapper_class = 'text-with-side-block text-with-side-' . esc_attr( $position );

		// Prepare HTML for image
		$image_html = '';
		if ( ! empty( $image_url ) ) {
			// Create basic HTML for image
			$image = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ) . '" style="width: ' . esc_attr( $width ) . ';" />';

			// Wrap image in link depending on settings
			if ( $image_link === 'media' && $image_id ) {
				// Link to media file (full-size image)
				$media_url = wp_get_attachment_url( $image_id );
				$image = '<a href="' . esc_url( $media_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} elseif ( $image_link === 'attachment' && $image_id ) {
				// Link to attachment page
				$attachment_url = get_attachment_link( $image_id );
				$image = '<a href="' . esc_url( $attachment_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} else {
				// Without link - just wrap in div
				$image = '<div class="text-with-side-image-link">' . $image . '</div>';
			}

			// Create container for image
			$image_html = '<div class="text-with-side-image">' . $image . '</div>';
		}

		// Prepare HTML for text content
		$text_html = '';
		if ( ! empty( $content_text ) ) {
			// Use wp_kses_post for security (allowed HTML tags)
			$text_html = '<div class="text-with-side-content">' . wp_kses_post( $content_text ) . '</div>';
		}

		// Assemble final block HTML
		$output = '<div class="' . $wrapper_class . '">';
		$output .= '<div class="text-with-side-inner">';
		$output .= $image_html;  // Image (if exists)
		$output .= $text_html;   // Text (if exists)
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}

// Create class instance to run the plugin
new TextWithSidePlugin();