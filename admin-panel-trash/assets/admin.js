jQuery(document).ready(function($) {
    // Инициализация плагина Admin Panel Trash

    // Загрузка элементов при открытии страницы
    loadAdminBarItems();

    // Проверка доступа к файлу
    $('#apt-check-access').on('click', function() {
        checkFileAccess();
    });

    // Обновление списка элементов
    $('#apt-refresh-items').on('click', function() {
        loadAdminBarItems();
    });

    function checkFileAccess() {
        $('#apt-check-access').prop('disabled', true).text(apt_localize.checking);
        $('#apt-access-result').html('<p>' + apt_localize.checking + '</p>');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_check_file_access',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<div class="notice notice-' + (data.writable ? 'success' : 'error') + '">';
                    html += '<p><strong>' + apt_localize.file_path + '</strong> ' + data.file_path + '</p>';
                    html += '<p><strong>' + apt_localize.read_access + '</strong> ' +
                        (data.readable ? apt_localize.yes : apt_localize.no) + '</p>';
                    html += '<p><strong>' + apt_localize.write_access + '</strong> ' +
                        (data.writable ? apt_localize.yes : apt_localize.no) + '</p>';
                    html += '</div>';

                    $('#apt-access-result').html(html);
                } else {
                    $('#apt-access-result').html('<div class="notice notice-error"><p>' +
                        apt_localize.error + ': ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                // Обработка ошибки проверки доступа
                $('#apt-access-result').html('<div class="notice notice-error"><p>' +
                    apt_localize.request_error + ': ' + error + '</p></div>');
            },
            complete: function() {
                $('#apt-check-access').prop('disabled', false).text(apt_localize.check_access);
            }
        });
    }

    function loadAdminBarItems() {
        $('#apt-items-list').html('<tr><td colspan="4">' + apt_localize.loading + '</td></tr>');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_get_items',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        displayItems(response.data);
                    } else {
                        $('#apt-items-list').html('<tr><td colspan="4">' +
                            apt_localize.no_items + '</td></tr>');
                    }
                } else {
                    var errorMsg = response.data || apt_localize.load_error;
                    $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                        apt_localize.load_error + ': ' + errorMsg + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                // Обработка AJAX ошибки при загрузке элементов
                var errorMsg = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : error;
                $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                    apt_localize.request_error + ': ' + errorMsg + '</td></tr>');
            }
        });
    }

    function displayItems(items) {
        if (items.length === 0) {
            $('#apt-items-list').html('<tr><td colspan="4">' + apt_localize.no_items + '</td></tr>');
            return;
        }

        var html = '';
        items.forEach(function(item) {
            var statusText = item.enabled ? apt_localize.enabled : apt_localize.disabled;
            var statusClass = item.enabled ? 'apt-status-enabled' : 'apt-status-disabled';

            var buttonText = item.enabled ? apt_localize.disable : apt_localize.enable;
            var buttonClass = item.enabled ? 'button-secondary' : 'button-primary';
            var buttonTitle = item.enabled ?
                'Убрать элемент из админ-панели' :
                'Вернуть элемент в админ-панель';

            // Используем display_id вместо id (без префикса wp-admin-bar-)
            var displayId = item.display_id || item.cleaned_id || item.id;

            html += '<tr>';
            html += '<td><code>' + displayId + '</code></td>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="' + statusClass + '">' + statusText + '</span></td>';
            html += '<td>';
            html += '<button class="button ' + buttonClass + ' apt-toggle-item" ' +
                   'data-item-id="' + item.id + '" ' +
                   'data-enable="' + !item.enabled + '" ' +
                   'title="' + buttonTitle + '">' + buttonText + '</button>';
            html += '</td>';
            html += '</tr>';
        });

        $('#apt-items-list').html(html);

        // Обработка кликов по кнопкам переключения
        $('.apt-toggle-item').on('click', function() {
            var $button = $(this);
            var itemId = $button.data('item-id');
            var enable = $button.data('enable');
            var originalText = $button.text();

            $button.prop('disabled', true).text(apt_localize.processing);

            $.ajax({
                url: apt_ajax.url,
                type: 'POST',
                data: {
                    action: 'apt_toggle_item',
                    item_id: itemId,
                    enable: enable,
                    nonce: apt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var actionText = enable ? apt_localize.item_enabled : apt_localize.item_disabled;
                        showMessage('✅ ' + actionText, 'success');
                        loadAdminBarItems(); // Обновляем список
                    } else {
                        showMessage('❌ ' + response.data, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    // Обработка ошибки переключения элемента
                    showMessage('❌ ' + apt_localize.request_error + ': ' + error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    function showMessage(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var html = '<div class="notice ' + noticeClass + ' is-dismissible" style="margin-top: 10px;">' +
                  '<p>' + message + '</p>' +
                  '<button type="button" class="notice-dismiss">' +
                  '<span class="screen-reader-text">Скрыть уведомление</span></button>' +
                  '</div>';

        $('.wrap h1').after(html);

        // Добавляем обработчик для кнопки закрытия
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });

        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            $('.notice:not(.notice-error)').fadeOut();
        }, 5000);
    }
});