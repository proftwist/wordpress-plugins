<?php
/**
 * Класс управления настройками плагина
 *
 * Отвечает за создание страницы настроек в админке WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс настроек плагина Quick Link Checker
 */
class QLC_Settings {

    /**
     * Конструктор класса
     *
     * Регистрирует хуки для создания страницы настроек
     */
    public function __construct() {
        // Добавление пункта меню в админку
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Регистрация настроек
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            __('Quick Link Checker', 'quick-link-checker'),
            __('Quick Link Checker', 'quick-link-checker'),
            'manage_options',
            'quick-link-checker',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('qlc_settings_group', 'qlc_enabled');

        add_settings_section(
            'qlc_main_section',
            __('Main Settings', 'quick-link-checker'),
            array($this, 'section_callback'),
            'quick-link-checker'
        );

        add_settings_field(
            'qlc_enabled',
            __('Enable Broken Link Checking', 'quick-link-checker'),
            array($this, 'enabled_field_callback'),
            'quick-link-checker',
            'qlc_main_section'
        );
    }

    public function section_callback() {
        echo '<p>' . __('Configure the Quick Link Checker settings.', 'quick-link-checker') . '</p>';
    }

    public function enabled_field_callback() {
        $enabled = get_option('qlc_enabled', '1');
        echo '<label>';
        echo '<input type="checkbox" name="qlc_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
        echo ' ' . __('Enable automatic link checking when saving posts', 'quick-link-checker');
        echo '</label>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Quick Link Checker Settings', 'quick-link-checker'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('qlc_settings_group');
                do_settings_sections('quick-link-checker');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}