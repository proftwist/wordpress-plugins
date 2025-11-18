<?php
/**
 * Основной класс для работы прогресс бара на фронтенде
 */
class Scroll_Progress_Bar {

    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = Scroll_Progress_Bar_Settings::get_instance();
        $this->options = $settings->get_options();

        // Если плагин включен в настройках
        if (isset($this->options['enabled']) && $this->options['enabled']) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_head', array($this, 'add_inline_styles'));
            add_action('wp_footer', array($this, 'add_progress_bar_html'));
        }
    }

    /**
     * Отладочная информация (можно удалить в продакшене)
     */
    public function debug_info() {
        if (!current_user_can('manage_options') || !$this->should_display()) {
            return;
        }

        echo '<!-- Scroll Progress Bar Debug -->';
        echo '<!-- Enabled: ' . (isset($this->options['enabled']) ? 'Yes' : 'No') . ' -->';
        echo '<!-- Should Display: ' . ($this->should_display() ? 'Yes' : 'No') . ' -->';
        echo '<!-- Display On: ' . print_r($this->options['display_on'] ?? array(), true) . ' -->';
        echo '<!-- Current Page: ' . $this->get_current_page_type() . ' -->';
        echo '<!-- End Debug -->';
    }

    /**
     * Определяем тип текущей страницы
     */
    private function get_current_page_type() {
        if (is_home() || is_front_page()) return 'home';
        if (is_single()) return 'posts';
        if (is_page()) return 'pages';
        if (is_archive()) return 'archives';
        return 'other';
    }

    /**
     * Проверяем, нужно ли показывать прогресс бар на текущей странице - УЛУЧШЕННАЯ ВЕРСИЯ
     */
    private function should_display() {
        // Если плагин выключен
        if (empty($this->options['enabled'])) {
            return false;
        }

        // Если не заданы настройки отображения
        if (empty($this->options['display_on'])) {
            return false;
        }

        $display_on = $this->options['display_on'];
        $current_type = $this->get_current_page_type();

        // Проверяем соответствие типа страницы настройкам
        switch ($current_type) {
            case 'home':
                return in_array('home', $display_on);
            case 'posts':
                return in_array('posts', $display_on);
            case 'pages':
                return in_array('pages', $display_on);
            case 'archives':
                return in_array('archives', $display_on);
            default:
                return false;
        }
    }

    /**
     * Подключаем скрипты и стили
     */
    public function enqueue_scripts() {
        if (!$this->should_display()) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'scroll-progress-bar-css',
            SCROLL_PROGRESS_BAR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SCROLL_PROGRESS_BAR_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'scroll-progress-bar-js',
            SCROLL_PROGRESS_BAR_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            SCROLL_PROGRESS_BAR_VERSION,
            true
        );
    }

    /**
     * Добавляем инлайн стили с выбранным цветом
     */
    public function add_inline_styles() {
        if (!$this->should_display()) {
            return;
        }

        $color = isset($this->options['color']) ? $this->options['color'] : '#4facfe';
        ?>
        <style type="text/css">
            #scroll-progress-bar {
                background: <?php echo esc_attr($color); ?>;
            }
            #scroll-progress-bar.completed {
                background: <?php echo esc_attr($color); ?>;
            }
        </style>
        <?php
    }

    /**
     * Добавляем HTML прогресс бара
     */
    public function add_progress_bar_html() {
        if (!$this->should_display()) {
            return;
        }
        ?>
        <div id="scroll-progress-bar"></div>
        <?php
    }
}
?>