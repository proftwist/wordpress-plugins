<?php
/**
 * Plugin Name:       Популярные посты за год
 * Description:       Добавляет гутенберговский блок для отображения самых популярных постов за определённый год.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           1.1.0
 * Author:            Владимир Бычко
 * Author URI:        http://bychko.ru
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       most-popular
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если доступ прямой.
}

/**
 * Загрузка текстового домена для локализации.
 */
function most_popular_load_textdomain() {
	load_plugin_textdomain( 'most-popular', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'most_popular_load_textdomain' );

/**
 * Проверка, активен ли плагин Post Views Counter.
 * Если нет, выводит уведомление в админ-панели.
 */
function most_popular_check_dependencies() {
	if ( ! class_exists( 'Post_Views_Counter' ) ) {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: Plugin name */
					esc_html__( 'Плагин "%s" требует для работы плагин Post Views Counter. Пожалуйста, установите и активируйте его.', 'most-popular' ),
					'<strong>' . esc_html__( 'Популярные посты за год', 'most-popular' ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'most_popular_check_dependencies' );

/**
 * Рендеринг блока на стороне сервера.
 *
 * @param array $attributes Атрибуты блока.
 * @return string HTML-код блока.
 */
function most_popular_render_block( $attributes ) {
	// Проверяем, активен ли необходимый плагин.
	if ( ! class_exists( 'Post_Views_Counter' ) ) {
		return '<p>' . esc_html__( 'Для работы этого блока необходимо установить и активировать плагин Post Views Counter.', 'most-popular' ) . '</p>';
	}

	global $wpdb;

	$number_of_posts = isset( $attributes['numberOfPosts'] ) ? intval( $attributes['numberOfPosts'] ) : 10;
	$year            = isset( $attributes['year'] ) ? $attributes['year'] : 'current';

	if ( 'current' === $year ) {
		$current_year = date( 'Y' );
		$year_for_sql = $current_year;
	} else {
		$year_for_sql = intval( $year );
	}

	// Ключ для кеша.
	$cache_key   = 'most_popular_posts_' . $year_for_sql . '_' . $number_of_posts;
	$cached_html = get_transient( $cache_key );

	if ( false !== $cached_html ) {
		return $cached_html;
	}

	$post_views_table = $wpdb->prefix . 'post_views';

	// Исправленный запрос с использованием LIKE для поиска по началу периода
	$period_pattern = $year_for_sql . '%';
	
	$query = $wpdb->prepare(
		"SELECT p.ID, p.post_title, COALESCE(SUM(v.count), 0) AS view_count
		 FROM {$wpdb->posts} p
		 LEFT JOIN {$post_views_table} v ON p.ID = v.id AND v.type = 0 AND v.period LIKE %s
		 WHERE p.post_type = 'post'
		   AND p.post_status = 'publish'
		   AND YEAR(p.post_date) = %d
		 GROUP BY p.ID
		 ORDER BY view_count DESC
		 LIMIT %d",
		$period_pattern,
		$year_for_sql,
		$number_of_posts
	);

	$popular_posts = $wpdb->get_results( $query );

	ob_start();

	if ( ! empty( $popular_posts ) ) {
		?>
		<div class="wp-block-most-popular-most-popular">
			<h3 style="text-align: center;">
				<?php
				printf(
					/* translators: %s: Year */
					esc_html__( 'Популярные посты за %s год', 'most-popular' ),
					esc_html( $year_for_sql )
				);
				?>
			</h3>
			<table>
				<thead>
					<tr>
						<th><strong><?php esc_html_e( 'Пост', 'most-popular' ); ?></strong></th>
						<th><strong><?php esc_html_e( 'Количество просмотров', 'most-popular' ); ?></strong></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $popular_posts as $post_item ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>"><?php echo esc_html( $post_item->post_title ); ?></a></td>
							<td style="text-align: right;"><?php echo esc_html( number_format_i18n( $post_item->view_count ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	} else {
		?>
		<p><?php esc_html_e( 'За выбранный год посты не найдены.', 'most-popular' ); ?></p>
		<?php
	}

	$html = ob_get_clean();

	// Кешируем результат на 1 час.
	set_transient( $cache_key, $html, HOUR_IN_SECONDS );

	return $html;
}

/**
 * Регистрация REST API эндпоинта для получения годов.
 */
function most_popular_register_rest_routes() {
	register_rest_route(
		'most-popular/v1',
		'/get-years',
		array(
			'methods'             => 'GET',
			'callback'            => 'most_popular_get_available_years',
			'permission_callback' => '__return_true', // Доступно для всех, так как это неконфиденциальные данные.
		)
	);
}
add_action( 'rest_api_init', 'most_popular_register_rest_routes' );

/**
 * Получение списка годов, за которые есть посты.
 *
 * @return WP_REST_Response
 */
function most_popular_get_available_years() {
	global $wpdb;

	$cache_key = 'most_popular_available_years';
	$years     = get_transient( $cache_key );

	if ( false === $years ) {
		$years = $wpdb->get_col(
			"SELECT DISTINCT YEAR(post_date)
			 FROM {$wpdb->posts}
			 WHERE post_type = 'post' AND post_status = 'publish'
			 ORDER BY post_date DESC"
		);
		set_transient( $cache_key, $years, HOUR_IN_SECONDS );
	}

	// Класс WP_REST_Response должен всегда существовать в этом контексте,
	// но мы добавляем проверку для надежности и помощи инструментам статического анализа.
	if ( ! class_exists( 'WP_REST_Response' ) ) {
		// В нормальной среде WordPress этого произойти не должно.
		return new WP_Error( 'rest_no_class', __( 'Класс WP_REST_Response не найден.', 'most-popular' ), array( 'status' => 500 ) );
	}

	return new WP_REST_Response( $years, 200 );
}

/**
 * Инициализация блока.
 *
 * Регистрирует блок, его скрипты, стили и метаданные из файла `block.json`.
 */
function most_popular_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => 'most_popular_render_block',
		)
	);
}
add_action( 'init', 'most_popular_init' );
