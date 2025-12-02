<?php
/**
 * Plugin Name: Smart Glossary Autolinker
 * Description: Автоматически находит термины в тексте и оборачивает их в тег abbr с определением.
 * Version: 1.1.0
 * Author: Vladimir Bychko
 * Author URI: http://bychko.ru
 * Text Domain: smart-glossary
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Константы путей
define( 'SG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SG_TABLE_NAME', 'smart_glossary' );

// Подключение классов
require_once SG_PLUGIN_DIR . 'includes/class-sg-admin.php';
require_once SG_PLUGIN_DIR . 'includes/class-sg-frontend.php';

class Smart_Glossary {

    public function __construct() {
        // Инициализация админки
        if ( is_admin() ) {
            new SG_Admin();
        }

        // Инициализация фронтенда
        new SG_Frontend();

        // Загрузка переводов
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Стили (для красивого отображения abbr)
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'smart-glossary', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'sg-style', SG_PLUGIN_URL . 'css/style.css', array(), '1.0.0' );
    }

    // Активация плагина: создание таблицы
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . SG_TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            term varchar(255) NOT NULL,
            definition text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Добавляем опцию включения по умолчанию
        add_option( 'sg_enabled', '1' );
    }
}

// Запуск
new Smart_Glossary();
register_activation_hook( __FILE__, array( 'Smart_Glossary', 'activate' ) );
