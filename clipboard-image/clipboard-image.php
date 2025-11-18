<?php
/**
 * Plugin Name: Clipboard Image
 * Plugin URI: https://bychko.ru/clipboard-image
 * Description: Позволяет вставлять изображения из буфера обмена прямо в редактор WordPress. Allows pasting images from clipboard directly into WordPress editor.
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Author URI: https://bychko.ru
 * Text Domain: clipboard-image
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Запрещаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

class ClipboardImage {

    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Загрузка текстового домена для локализации
        load_plugin_textdomain(
            'clipboard-image',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

        // Подключаем скрипты только в админке
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Обработчик AJAX для загрузки изображений
        add_action('wp_ajax_handle_pasted_image', array($this, 'handle_ajax_upload'));
    }

    public function enqueue_scripts($hook) {
        // Подключаем только на страницах создания/редактирования поста
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        // Проверяем права пользователя
        if (!current_user_can('upload_files')) {
            return;
        }

        // Регистрируем и подключаем JS-файл
        wp_register_script(
            'clipboard-image-js',
            plugin_dir_url(__FILE__) . 'assets/js/paste-handler.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Локализация данных для JS
        wp_localize_script(
            'clipboard-image-js',
            'clipboard_image_ajax',
            array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clipboard_image_nonce'),
                'uploading_text' => __('Uploading image...', 'clipboard-image'),
                'success_text' => __('Image uploaded successfully!', 'clipboard-image'),
                'error_text' => __('Error uploading image.', 'clipboard-image'),
                'invalid_type_text' => __('Only JPG, PNG and SVG images are allowed.', 'clipboard-image'),
                'file_too_large_text' => __('File is too large. Maximum size: 5MB.', 'clipboard-image'),
                'no_image_text' => __('No image found in clipboard.', 'clipboard-image'),
                'security_error_text' => __('Security verification failed.', 'clipboard-image'),
                'permission_error_text' => __('Insufficient permissions.', 'clipboard-image'),
            )
        );

        wp_enqueue_script('clipboard-image-js');
    }

    public function handle_ajax_upload() {
        // Проверяем nonce для безопасности
        if (!check_ajax_referer('clipboard_image_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security verification failed.', 'clipboard-image')
            ));
        }

        // Проверяем права пользователя
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'clipboard-image')
            ));
        }

        // Проверяем, что файл был отправлен
        if (empty($_FILES['image'])) {
            wp_send_json_error(array(
                'message' => __('No file received.', 'clipboard-image')
            ));
        }

        $uploaded_file = $_FILES['image'];

        // Проверяем ошибки загрузки
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($uploaded_file['error']);
            wp_send_json_error(array('message' => $error_message));
        }

        // Проверяем тип файла
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml', 'image/svg');
        $file_info = wp_check_filetype_and_ext($uploaded_file['tmp_name'], $uploaded_file['name']);

        if (empty($file_info['type']) || !in_array($file_info['type'], $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Only JPG, PNG and SVG images are allowed.', 'clipboard-image')
            ));
        }

        // Проверяем размер файла (максимум 5MB)
        $max_size = 5 * 1024 * 1024;
        if ($uploaded_file['size'] > $max_size) {
            wp_send_json_error(array(
                'message' => __('File is too large. Maximum size: 5MB.', 'clipboard-image')
            ));
        }

        // Подготавливаем файл для загрузки
        $file = array(
            'name' => $this->generate_filename($uploaded_file['name']),
            'type' => $uploaded_file['type'],
            'tmp_name' => $uploaded_file['tmp_name'],
            'error' => $uploaded_file['error'],
            'size' => $uploaded_file['size']
        );

        // Загружаем файл в медиатеку
        $attachment_id = media_handle_sideload($file, 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array(
                'message' => $attachment_id->get_error_message()
            ));
        }

        // Получаем URL загруженного изображения
        $image_url = wp_get_attachment_url($attachment_id);

        // Получаем данные для разных размеров
        $image_data = array(
            'url' => $image_url,
            'id' => $attachment_id,
            'full' => $image_url,
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'large' => wp_get_attachment_image_url($attachment_id, 'large'),
            'title' => get_the_title($attachment_id)
        );

        // Возвращаем успешный ответ
        wp_send_json_success(array(
            'message' => __('Image uploaded successfully!', 'clipboard-image'),
            'image' => $image_data
        ));
    }

    private function get_upload_error_message($error_code) {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'clipboard-image'),
            UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'clipboard-image'),
            UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded.', 'clipboard-image'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'clipboard-image'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', 'clipboard-image'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'clipboard-image'),
            UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload.', 'clipboard-image'),
        );

        return isset($messages[$error_code]) ? $messages[$error_code] : __('Unknown upload error.', 'clipboard-image');
    }

    private function generate_filename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $timestamp = current_time('timestamp');
        $random_string = wp_generate_password(6, false);

        return sprintf('pasted-image-%s-%s.%s', $timestamp, $random_string, $extension);
    }
}

new ClipboardImage();