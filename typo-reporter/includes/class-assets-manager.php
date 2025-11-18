<?php
/**
 * Менеджер ресурсов для плагина Typo Reporter
 *
 * Отвечает за подключение JavaScript и CSS файлов.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Класс TypoReporterAssetsManager
 */
class TypoReporterAssetsManager {

    /**
     * Подключение ресурсов для фронтенда
     *
     * @since 1.0.0
     */
    public static function enqueue_frontend_assets() {
        // Пути к файлам
        $frontend_js = TR_PLUGIN_PATH . 'assets/js/frontend.js';
        $frontend_css = TR_PLUGIN_PATH . 'assets/css/frontend.css';

        // Подключение JavaScript для фронтенда
        wp_enqueue_script(
            'typo-reporter-frontend',
            TR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            file_exists($frontend_js) ? filemtime($frontend_js) : TR_PLUGIN_VERSION,
            true
        );

        // Установка переводов для фронтенд скрипта
        wp_set_script_translations('typo-reporter-frontend', 'typo-reporter', TR_PLUGIN_PATH . 'languages');

        // Генерация капчи
        $captcha = TypoReporterAjaxHandler::generate_math_captcha();
        
        // Передача настроек в JavaScript
        wp_localize_script('typo-reporter-frontend', 'typoReporterSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('typo_reporter_submit'),
            'captcha' => $captcha,
            'messages' => array(
                'success' => __('Report sent successfully!', 'typo-reporter'),
                'error' => __('Error sending report. Please try again.', 'typo-reporter'),
                'emptyText' => __('Please select some text first.', 'typo-reporter'),
                'emptyDescription' => __('Please describe the error.', 'typo-reporter'),
                'invalidCaptcha' => __('Invalid CAPTCHA answer. Please try again.', 'typo-reporter')
            )
        ));

        // Подключение CSS стилей
        wp_enqueue_style(
            'typo-reporter-frontend',
            TR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            file_exists($frontend_css) ? filemtime($frontend_css) : TR_PLUGIN_VERSION
        );
    }

    /**
     * Подключение ресурсов для админки
     *
     * @since 1.0.0
     */
    public static function enqueue_admin_assets() {
        // Подключаем только на странице настроек плагина
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'settings_page_typo-reporter') {
            return;
        }

        $admin_css = TR_PLUGIN_PATH . 'assets/css/admin.css';
        $admin_js = TR_PLUGIN_PATH . 'assets/js/admin.js';

        // Подключение CSS для админки
        wp_enqueue_style(
            'typo-reporter-admin',
            TR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            file_exists($admin_css) ? filemtime($admin_css) : TR_PLUGIN_VERSION
        );

        // Подключение JavaScript для админки
        wp_enqueue_script(
            'typo-reporter-admin',
            TR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            file_exists($admin_js) ? filemtime($admin_js) : TR_PLUGIN_VERSION,
            true
        );

        // Установка переводов для админ скрипта
        wp_set_script_translations('typo-reporter-admin', 'typo-reporter', TR_PLUGIN_PATH . 'languages');

        // Передача настроек в JavaScript админки
        wp_localize_script('typo-reporter-admin', 'typoReporterAdminSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('typo_reporter_admin'),
            'messages' => array(
                'confirmDelete' => __('Are you sure you want to delete this report?', 'typo-reporter'),
                'deleteSuccess' => __('Report deleted successfully.', 'typo-reporter'),
                'deleteError' => __('Error deleting report.', 'typo-reporter')
            )
        ));
    }
}