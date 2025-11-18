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

        // Полностью игнорируем обработку в Gutenberg редакторе
        if (isGutenbergActive()) {
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
        var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
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
                    // Не вставляем изображение в Gutenberg - он сам обрабатывает вставку
                    console.log('Clipboard Image: Skipping insertion in Gutenberg editor');
                } else if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    insertIntoClassicEditor(imageData);
                } else {
                    insertIntoTextarea(imageData);
                }
                break;
            default:
                if (isGutenbergActive()) {
                    // Не вставляем изображение в Gutenberg - он сам обрабатывает вставку
                    console.log('Clipboard Image: Skipping insertion in Gutenberg editor');
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

        var block = wp.blocks.createBlock('core/image', {
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