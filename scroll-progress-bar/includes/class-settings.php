<?php
/**
 * Класс для работы с настройками плагина
 */
class Scroll_Progress_Bar_Settings {

    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('scroll_progress_bar_options');
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Добавляем пункт меню в админку
     */
    public function add_admin_menu() {
        add_options_page(
            __('Scroll Progress Bar', 'scroll-progress-bar'), // Заголовок страницы
            __('Scroll Progress Bar', 'scroll-progress-bar'), // Название в меню
            'manage_options',
            'scroll-progress-bar',
            array($this, 'options_page')
        );
    }

    /**
     * Подключаем скрипты и стили для админки
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_scroll-progress-bar' !== $hook) {
            return;
        }

        // Подключаем спектр цветов WordPress
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Наш скрипт для админки
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($){
                $(".color-picker").wpColorPicker();
            });
        ');
    }

    /**
     * Инициализация настроек
     */
    public function settings_init() {
        register_setting(
            'scrollProgressBarPage',
            'scroll_progress_bar_options',
            array($this, 'sanitize_options')
        );

        // Основная секция
        add_settings_section(
            'scroll_progress_bar_main_section',
            __('Основные настройки', 'scroll-progress-bar'),
            array($this, 'main_section_callback'),
            'scrollProgressBarPage'
        );

        // Поле: Включить/выключить
        add_settings_field(
            'enabled',
            __('Включить прогресс бар', 'scroll-progress-bar'),
            array($this, 'enabled_field_render'),
            'scrollProgressBarPage',
            'scroll_progress_bar_main_section'
        );

        // Поле: Цвет
        add_settings_field(
            'color',
            __('Цвет полоски', 'scroll-progress-bar'),
            array($this, 'color_field_render'),
            'scrollProgressBarPage',
            'scroll_progress_bar_main_section'
        );

        // Поле: Где показывать
        add_settings_field(
            'display_on',
            __('Показывать на', 'scroll-progress-bar'),
            array($this, 'display_on_field_render'),
            'scrollProgressBarPage',
            'scroll_progress_bar_main_section'
        );
    }

    /**
     * Поле: Включить/выключить
     */
    public function enabled_field_render() {
        $enabled = isset($this->options['enabled']) ? $this->options['enabled'] : 0;
        ?>
        <label>
            <input type="checkbox" name="scroll_progress_bar_options[enabled]" value="1" <?php checked(1, $enabled); ?>>
            <?php _e('Активировать Scroll Progress Bar', 'scroll-progress-bar'); ?>
        </label>
        <?php
    }

    /**
     * Поле: Цвет
     */
    public function color_field_render() {
        $color = isset($this->options['color']) ? $this->options['color'] : '#4facfe';
        ?>
        <input type="text" name="scroll_progress_bar_options[color]" value="<?php echo esc_attr($color); ?>" class="color-picker" data-default-color="#4facfe">
        <p class="description">
            <?php _e('Выберите цвет полоски прогресса в формате HEX', 'scroll-progress-bar'); ?>
        </p>
        <?php
    }

    /**
     * Поле: Где показывать
     */
    public function display_on_field_render() {
        $display_on = isset($this->options['display_on']) ? $this->options['display_on'] : array('posts', 'pages');
        $post_types = array(
            'posts' => __('Записи', 'scroll-progress-bar'),
            'pages' => __('Страницы', 'scroll-progress-bar'),
            'home' => __('Главная страница', 'scroll-progress-bar'),
            'archives' => __('Архивы', 'scroll-progress-bar')
        );

        foreach ($post_types as $key => $label) {
            $checked = in_array($key, $display_on) ? 'checked' : '';
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="scroll_progress_bar_options[display_on][]" value="<?php echo esc_attr($key); ?>" <?php echo $checked; ?>>
                <?php echo esc_html($label); ?>
            </label>
            <?php
        }
    }

    /**
     * Описание секции
     */
    public function main_section_callback() {
        echo __('Настройте внешний вид и поведение прогресс бара.', 'scroll-progress-bar');
    }

    /**
     * Валидация опций
     */
    public function sanitize_options($input) {
        $sanitized_input = array();

        // Включено/выключено
        $sanitized_input['enabled'] = isset($input['enabled']) ? 1 : 0;

        // Цвет
        $sanitized_input['color'] = sanitize_hex_color($input['color']);
        if (empty($sanitized_input['color'])) {
            $sanitized_input['color'] = '#4facfe';
        }

        // Где показывать
        $sanitized_input['display_on'] = isset($input['display_on']) ? (array) $input['display_on'] : array();
        $sanitized_input['display_on'] = array_map('sanitize_text_field', $sanitized_input['display_on']);

        return $sanitized_input;
    }

    /**
     * Страница настроек
     */
    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Scroll Progress Bar - Настройки', 'scroll-progress-bar'); ?></h1>

            <div style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 5px;">
                <h2><?php _e('О плагине', 'scroll-progress-bar'); ?></h2>
                <p>
                    <?php _e('Минималистичная полоска прогресса чтения вверху страницы. Улучшает UX длинных статей.', 'scroll-progress-bar'); ?>
                </p>
                <p>
                    <strong><?php _e('Автор', 'scroll-progress-bar'); ?>:</strong> Владимир Бычко<br>
                    <strong><?php _e('Сайт', 'scroll-progress-bar'); ?>:</strong>
                    <a href="https://bychko.ru" target="_blank">bychko.ru</a>
                </p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('scrollProgressBarPage');
                do_settings_sections('scrollProgressBarPage');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Получить настройки
     */
    public function get_options() {
        return $this->options;
    }
}
?>