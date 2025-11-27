<?php
/**
 * Plugin Name: Text with Side
 * Description: Гутенберговский блок для текста с боковым изображением, который отображается на полях
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Version: 2.0.0
 * Text Domain: text-with-side
 * Domain Path: /languages
 */

// Защита от прямого доступа - проверяем, что скрипт запущен из WordPress
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Основной класс плагина Text with Side
 *
 * Этот класс отвечает за инициализацию и работу Gutenberg блока
 * для отображения текста с боковым изображением на полях страницы.
 */
class TextWithSidePlugin {

	/**
	 * Конструктор класса - инициализирует хуки WordPress
	 */
	public function __construct() {
		// Регистрируем блок при инициализации WordPress
		add_action( 'init', array( $this, 'init' ) );

		// Загружаем файлы переводов для интернационализации
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Подключаем CSS стили для фронтенда (публичной части сайта)
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Подключаем JavaScript и CSS для редактора Gutenberg
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Загрузка файлов переводов для поддержки многоязычности
	 *
	 * Эта функция позволяет плагину автоматически подхватывать
	 * переводы в зависимости от языка WordPress.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'text-with-side', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Подключение CSS стилей для фронтенда
	 *
	 * Эти стили применяются только на публичной части сайта,
	 * когда пользователь просматривает страницу.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'text-with-side-frontend',              // Уникальный идентификатор стиля
			plugins_url( 'assets/frontend.css', __FILE__ ), // Путь к файлу стилей
			array(),                                // Зависимости (пустой массив)
			'2.0.0'                                 // Версия стилей
		);
	}

	/**
	 * Подключение JavaScript и CSS для редактора Gutenberg
	 *
	 * Эти ресурсы загружаются только в админ-панели,
	 * когда пользователь редактирует контент.
	 */
	public function enqueue_editor_assets() {
		// Подключаем JavaScript для функциональности блока
		wp_enqueue_script(
			'text-with-side-editor',                                         // Идентификатор скрипта
			plugins_url( 'build/index.js', __FILE__ ),                      // Путь к скрипту
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), // Зависимости WordPress
			'2.0.0'                                                          // Версия
		);

		// Подключаем переводы для JavaScript
		// Это позволяет блоку отображаться на русском языке в редакторе
		wp_set_script_translations( 'text-with-side-editor', 'text-with-side', plugin_dir_path( __FILE__ ) . 'languages' );

		// Подключаем CSS стили для редактора
		wp_enqueue_style(
			'text-with-side-editor',                  // Идентификатор стиля
			plugins_url( 'assets/editor.css', __FILE__ ), // Путь к стилям редактора
			array(),                                  // Зависимости
			'2.0.0'                                   // Версия
		);
	}

	/**
	 * Инициализация Gutenberg блока
	 *
	 * Регистрирует новый тип блока и его настройки.
	 * Выполняется только если функция register_block_type доступна.
	 */
	public function init() {
		// Проверяем, что WordPress поддерживает блоки (Gutenberg)
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Регистрируем блок с его настройками и атрибутами
		register_block_type( 'text-with-side/text-with-side', array(
			'editor_script' => 'text-with-side-editor',      // JavaScript для редактора
			'editor_style'  => 'text-with-side-editor',      // CSS для редактора
			'render_callback' => array( $this, 'render_block' ), // Функция рендеринга блока

			// Атрибуты блока - данные, которые сохраняются в базе данных
			'attributes' => array(
				'content' => array(                         // Текстовое содержимое блока
					'type' => 'string',
					'default' => '',
				),
				'imageId' => array(                         // ID изображения в медиатеке
					'type' => 'number',
					'default' => 0,
				),
				'imageUrl' => array(                        // URL изображения
					'type' => 'string',
					'default' => '',
				),
				'imageAlt' => array(                        // Альтернативный текст изображения
					'type' => 'string',
					'default' => '',
				),
				'position' => array(                        // Позиция блока (слева/справа)
					'type' => 'string',
					'default' => 'left',
				),
				'imageLink' => array(                       // Тип ссылки на изображение
					'type' => 'string',
					'default' => 'none',
				),
				'width' => array(                          // Ширина изображения
					'type' => 'string',
					'default' => '150px',
				),
			),
		) );
	}

	/**
	 * Функция рендеринга блока для фронтенда
	 *
	 * Получает атрибуты блока и генерирует HTML для отображения
	 * на публичной части сайта.
	 *
	 * @param array $attributes Атрибуты блока из базы данных
	 * @param string $content Содержимое блока (не используется в данном блоке)
	 * @return string HTML код блока
	 */
	public function render_block( $attributes, $content ) {
		// Извлекаем атрибуты в отдельные переменные для удобства
		$content_text = $attributes['content'];
		$image_id = $attributes['imageId'];
		$image_url = $attributes['imageUrl'];
		$image_alt = $attributes['imageAlt'];
		$position = $attributes['position'];
		$image_link = $attributes['imageLink'];
		$width = $attributes['width'];

		// Если нет ни текста, ни изображения - не выводим блок
		if ( empty( $content_text ) && empty( $image_url ) ) {
			return '';
		}

		// Генерируем CSS классы для блока на основе позиции
		$wrapper_class = 'text-with-side-block text-with-side-' . esc_attr( $position );

		// Подготавливаем HTML для изображения
		$image_html = '';
		if ( ! empty( $image_url ) ) {
			// Создаем базовый HTML для изображения
			$image = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ) . '" style="width: ' . esc_attr( $width ) . ';" />';

			// Оборачиваем изображение в ссылку в зависимости от настроек
			if ( $image_link === 'media' && $image_id ) {
				// Ссылка на медиафайл (полноразмерное изображение)
				$media_url = wp_get_attachment_url( $image_id );
				$image = '<a href="' . esc_url( $media_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} elseif ( $image_link === 'attachment' && $image_id ) {
				// Ссылка на страницу вложения
				$attachment_url = get_attachment_link( $image_id );
				$image = '<a href="' . esc_url( $attachment_url ) . '" class="text-with-side-image-link">' . $image . '</a>';
			} else {
				// Без ссылки - просто оборачиваем в div
				$image = '<div class="text-with-side-image-link">' . $image . '</div>';
			}

			// Формируем контейнер для изображения
			$image_html = '<div class="text-with-side-image">' . $image . '</div>';
		}

		// Подготавливаем HTML для текстового содержимого
		$text_html = '';
		if ( ! empty( $content_text ) ) {
			// Используем wp_kses_post для безопасности (разрешенные HTML теги)
			$text_html = '<div class="text-with-side-content">' . wp_kses_post( $content_text ) . '</div>';
		}

		// Собираем финальный HTML блока
		$output = '<div class="' . $wrapper_class . '">';
		$output .= '<div class="text-with-side-inner">';
		$output .= $image_html;  // Изображение (если есть)
		$output .= $text_html;   // Текст (если есть)
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}

// Создаем экземпляр класса для запуска плагина
new TextWithSidePlugin();