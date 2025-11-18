Понял! Создам плагин с ориентацией на каталог `clipboard-image`. Вот полная инструкция:

## Структура плагина

```
wp-content/plugins/clipboard-image/
├── clipboard-image.php
├── assets/
│   └── js/
│       └── paste-handler.js
└── languages/
```

## 1. Основной файл плагина

**Файл:** `clipboard-image.php`

```php
<?php
/**
 * Plugin Name: Clipboard Image
 * Plugin URI: https://github.com/your-repo/clipboard-image
 * Description: Позволяет вставлять изображения из буфера обмена прямо в редактор WordPress. Allows pasting images from clipboard directly into WordPress editor.
 * Version: 1.0.0
 * Author: Your Name
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
                'invalid_type_text' => __('Only JPG and PNG images are allowed.', 'clipboard-image'),
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
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
        $file_info = wp_check_filetype_and_ext($uploaded_file['tmp_name'], $uploaded_file['name']);

        if (empty($file_info['type']) || !in_array($file_info['type'], $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Only JPG and PNG images are allowed.', 'clipboard-image')
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
```

## 2. JavaScript файл

**Файл:** `assets/js/paste-handler.js`

```javascript
(function($) {
    'use strict';

    // Переменные для управления состоянием
    var isUploading = false;
    var currentEditor = null;

    // Основная функция инициализации
    function initClipboardImage() {
        // Обработчик для классического редактора
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            initClassicEditor();
        }

        // Обработчик для блокового редактора (Gutenberg)
        if (isGutenbergActive()) {
            initGutenbergEditor();
        }

        // Общий обработчик для всего документа (как fallback)
        $(document).on('paste', handlePasteEvent);
    }

    // Инициализация для классического редактора
    function initClassicEditor() {
        tinymce.PluginManager.add('clipboard_image', function(editor) {
            editor.on('paste', function(e) {
                handlePasteInEditor(e, 'classic');
            });
        });

        // Добавляем плагин ко всем редакторам
        tinymce.each(tinymce.editors, function(editor) {
            editor.addPlugin('clipboard_image');
        });
    }

    // Инициализация для Gutenberg редактора
    function initGutenbergEditor() {
        // Gutenberg автоматически обрабатывает события paste в блоках
        // Добавляем общий обработчик
        console.log('Clipboard Image: Gutenberg editor detected');
    }

    // Проверка активности Gutenberg
    function isGutenbergActive() {
        return typeof wp !== 'undefined' &&
               typeof wp.blocks !== 'undefined' &&
               typeof wp.data !== 'undefined' &&
               document.querySelector('.block-editor-writing-flow');
    }

    // Обработчик события вставки
    function handlePasteEvent(e) {
        // Не обрабатываем, если уже идет загрузка
        if (isUploading) {
            return;
        }

        // Проверяем, что событие произошло в области редактора
        if (!isInEditorArea(e.target)) {
            return;
        }

        processPasteEvent(e, 'document');
    }

    // Обработчик вставки в редакторе
    function handlePasteInEditor(e, editorType) {
        if (isUploading) return;
        processPasteEvent(e, editorType);
    }

    // Основная обработка события вставки
    function processPasteEvent(e, source) {
        var clipboardData = e.originalEvent ? e.originalEvent.clipboardData : e.clipboardData;

        if (!clipboardData) {
            return;
        }

        // Ищем изображения среди вставленных данных
        for (var i = 0; i < clipboardData.items.length; i++) {
            var item = clipboardData.items[i];

            if (item.type.indexOf('image') !== -1) {
                e.preventDefault();
                e.stopPropagation();

                var file = item.getAsFile();
                if (file && isValidImageFile(file)) {
                    uploadImage(file, source);
                } else {
                    showMessage(clipboard_image_ajax.invalid_type_text, 'error');
                }
                break;
            }
        }
    }

    // Проверка валидности файла изображения
    function isValidImageFile(file) {
        var validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        return validTypes.indexOf(file.type) !== -1;
    }

    // Загрузка изображения на сервер
    function uploadImage(file, editorType) {
        isUploading = true;

        showMessage(clipboard_image_ajax.uploading_text, 'info');

        var formData = new FormData();
        formData.append('action', 'handle_pasted_image');
        formData.append('nonce', clipboard_image_ajax.nonce);
        formData.append('image', file);

        $.ajax({
            url: clipboard_image_ajax.url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                showMessage(clipboard_image_ajax.success_text, 'success');
                insertImageIntoEditor(response.data.image, editorType);
            } else {
                showMessage(response.data.message || clipboard_image_ajax.error_text, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Clipboard Image AJAX Error:', error);
            showMessage(clipboard_image_ajax.error_text, 'error');
        })
        .always(function() {
            isUploading = false;
        });
    }

    // Вставка изображения в редактор
    function insertImageIntoEditor(imageData, editorType) {
        switch (editorType) {
            case 'classic':
                insertIntoClassicEditor(imageData);
                break;
            case 'document':
                // Пытаемся определить тип редактора автоматически
                if (isGutenbergActive()) {
                    insertIntoGutenbergEditor(imageData);
                } else if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    insertIntoClassicEditor(imageData);
                } else {
                    insertIntoTextarea(imageData);
                }
                break;
            default:
                if (isGutenbergActive()) {
                    insertIntoGutenbergEditor(imageData);
                } else {
                    insertIntoClassicEditor(imageData);
                }
        }
    }

    // Вставка в классический редактор
    function insertIntoClassicEditor(imageData) {
        var editor = tinymce.activeEditor;
        if (editor) {
            var imgHtml = '<img src="' + imageData.url + '" alt="' + (imageData.title || 'Pasted image') + '" class="pasted-image" />';
            editor.execCommand('mceInsertContent', false, imgHtml);
        }
    }

    // Вставка в Gutenberg редактор
    function insertIntoGutenbergEditor(imageData) {
        if (typeof wp === 'undefined' || typeof wp.blocks === 'undefined' || typeof wp.data === 'undefined') {
            console.error('Clipboard Image: Gutenberg API not available');
            return;
        }

        var block = wp.blocks.createBlock('image', {
            url: imageData.url,
            alt: imageData.title || 'Pasted image',
            className: 'pasted-image'
        });

        wp.data.dispatch('core/block-editor').insertBlocks(block);
    }

    // Вставка в текстовую область (fallback)
    function insertIntoTextarea(imageData) {
        var textarea = document.getElementById('content');
        if (textarea) {
            var imgHtml = '<img src="' + imageData.url + '" alt="' + (imageData.title || 'Pasted image') + '" class="pasted-image" />';
            var startPos = textarea.selectionStart;
            var endPos = textarea.selectionEnd;
            var textBefore = textarea.value.substring(0, startPos);
            var textAfter = textarea.value.substring(endPos, textarea.value.length);

            textarea.value = textBefore + imgHtml + textAfter;
            textarea.selectionStart = textarea.selectionEnd = startPos + imgHtml.length;
            textarea.focus();
        }
    }

    // Проверка, находится ли элемент в области редактора
    function isInEditorArea(element) {
        var $element = $(element);

        // Проверяем различные области редактора
        return $element.closest('#wp-content-wrap, .block-editor-writing-flow, #content, .wp-editor-area').length > 0 ||
               $element.is('textarea#content') ||
               $element.hasClass('block-editor-rich-text__editable');
    }

    // Показ сообщений
    function showMessage(message, type) {
        // Используем стандартные уведомления WordPress
        if (type === 'success') {
            console.log('Clipboard Image:', message);
        } else if (type === 'error') {
            console.error('Clipboard Image:', message);
        }

        // Можно добавить красивый toast здесь при необходимости
    }

    // Инициализация при загрузке документа
    $(document).ready(function() {
        // Небольшая задержка для полной загрузки редакторов
        setTimeout(initClipboardImage, 100);
    });

    // Также инициализируем при событии turbolinks (если используется)
    $(document).on('page:load', initClipboardImage);

})(jQuery);
```

## 3. Файл перевода (.pot)

**Файл:** `languages/clipboard-image.pot`

```pot
# Copyright (C) 2024 Your Name
# This file is distributed under the same license as the Clipboard Image plugin.
msgid ""
msgstr ""
"Project-Id-Version: Clipboard Image 1.0.0\n"
"Report-Msgid-Bugs-To: \n"
"POT-Creation-Date: 2024-01-01 12:00+0000\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"PO-Revision-Date: 2024-01-01 12:00+0000\n"
"Language-Team: \n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: clipboard-image.php:64
msgid "Uploading image..."
msgstr ""

#: clipboard-image.php:65
msgid "Image uploaded successfully!"
msgstr ""

#: clipboard-image.php:66
msgid "Error uploading image."
msgstr ""

#: clipboard-image.php:67
msgid "Only JPG and PNG images are allowed."
msgstr ""

#: clipboard-image.php:68
msgid "File is too large. Maximum size: 5MB."
msgstr ""

#: clipboard-image.php:69
msgid "No image found in clipboard."
msgstr ""

#: clipboard-image.php:70
msgid "Security verification failed."
msgstr ""

#: clipboard-image.php:71
msgid "Insufficient permissions."
msgstr ""

#: clipboard-image.php:115
msgid "No file received."
msgstr ""

#: clipboard-image.php:169
msgid "The uploaded file exceeds the upload_max_filesize directive in php.ini."
msgstr ""

#: clipboard-image.php:170
msgid "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."
msgstr ""

#: clipboard-image.php:171
msgid "The uploaded file was only partially uploaded."
msgstr ""

#: clipboard-image.php:172
msgid "No file was uploaded."
msgstr ""

#: clipboard-image.php:173
msgid "Missing a temporary folder."
msgstr ""

#: clipboard-image.php:174
msgid "Failed to write file to disk."
msgstr ""

#: clipboard-image.php:175
msgid "A PHP extension stopped the file upload."
msgstr ""

#: clipboard-image.php:176
msgid "Unknown upload error."
msgstr ""
```

## Инструкция по установке

1. **Создайте папку плагина:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   mkdir clipboard-image
   cd clipboard-image
   ```

2. **Создайте структуру папок:**
   ```bash
   mkdir -p assets/js
   mkdir languages
   ```

3. **Создайте файлы:**
   - `clipboard-image.php` (основной код выше)
   - `assets/js/paste-handler.js` (JavaScript код выше)
   - `languages/clipboard-image.pot` (файл перевода)

4. **Активируйте плагин:**
   - Войдите в админку WordPress
   - Перейдите в "Плагины"
   - Найдите "Clipboard Image" и активируйте

## Использование

1. Откройте любой пост для редактирования
2. Скопируйте изображение в буфер обмена (Ctrl+C или скриншот)
3. Нажмите Ctrl+V в редакторе WordPress
4. Изображение автоматически загрузится в медиатеку и вставится в пост

## Особенности плагина

- ✅ Поддержка JPG и PNG
- ✅ Работает в классическом и блоковом редакторе
- ✅ Автоматическая загрузка в медиатеку
- ✅ Локализация готовка к переводу
- ✅ Проверка прав пользователя
- ✅ Ограничение размера файла (5MB)
- ✅ Защита nonce
- ✅ Генерация уникальных имен файлов

Плагин готов к использованию! После активации пользователи смогут вставлять изображения из буфера обмена прямо в редактор WordPress.