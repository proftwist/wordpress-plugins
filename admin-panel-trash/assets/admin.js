jQuery(document).ready(function($) {
    console.log('Admin Panel Trash loaded'); // Для отладки

    // Загрузка элементов при открытии страницы
    loadAdminBarItems();

    // Проверка доступа к файлу
    $('#apt-check-access').on('click', function() {
        console.log('Check access clicked');
        checkFileAccess();
    });

    // Обновление списка элементов
    $('#apt-refresh-items').on('click', function() {
        console.log('Refresh items clicked');
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
                console.log('Access check response:', response);
                if (response.success) {
                    var data = response.data;
                    var debug = data.debug_info;
                    var html = '<div class="notice notice-' + (data.writable ? 'success' : 'error') + '">';
                    html += '<p><strong>' + apt_localize.file_path + '</strong> ' + data.path + '</p>';
                    html += '<p><strong>' + apt_localize.read_access + '</strong> ' +
                        (data.readable ? apt_localize.yes : apt_localize.no) + '</p>';
                    html += '<p><strong>' + apt_localize.write_access + '</strong> ' +
                        (data.writable ? apt_localize.yes : apt_localize.no) + '</p>';

                    // Детальная отладочная информация
                    html += '<details style="margin-top: 10px;">';
                    html += '<summary style="cursor: pointer;"><strong>Детальная отладка:</strong></summary>';
                    html += '<div style="margin-left: 10px; margin-top: 5px; font-size: 12px;">';
                    html += '<p><strong>Функция найдена:</strong> ' + (debug.function_found ? '✅ Да' : '❌ Нет') + '</p>';
                    html += '<p><strong>Отключенных элементов:</strong> ' + debug.disabled_items.length + '</p>';
                    html += '<p><strong>Элементы:</strong> ' + (debug.disabled_items.join(', ') || 'нет') + '</p>';
                    html += '<p><strong>Размер файла:</strong> ' + debug.file_size + ' байт</p>';
                    html += '<p><strong>Строк в файле:</strong> ' + debug.file_lines + '</p>';
                    html += '<p><strong>Содержит remove_menu:</strong> ' + (debug.has_remove_menu ? '✅ Да' : '❌ Нет') + '</p>';
                    html += '<p><strong>Содержит wp_before_admin_bar_render:</strong> ' + (debug.has_wp_before_admin_bar_render ? '✅ Да' : '❌ Нет') + '</p>';

                    if (debug.detection_debug && debug.detection_debug.patterns_tried) {
                        html += '<p><strong>Паттерны поиска:</strong></p>';
                        html += '<ul>';
                        for (var pattern in debug.detection_debug.patterns_tried) {
                            var matched = debug.detection_debug.patterns_tried[pattern];
                            html += '<li class="' + (matched ? 'pattern-match' : 'pattern-no-match') + '">';
                            html += pattern + ': ' + (matched ? '✅' : '❌');
                            html += '</li>';
                        }
                        html += '</ul>';
                    }

                    if (debug.function_content) {
                        html += '<p><strong>Найденная функция:</strong></p>';
                        html += '<pre style="background: #f1f1f1; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 200px; font-size: 10px;">' + debug.function_content + '</pre>';
                    }

                    html += '</div>';
                    html += '</details>';
                    html += '</div>';

                    $('#apt-access-result').html(html);
                } else {
                    $('#apt-access-result').html('<div class="notice notice-error"><p>' +
                        apt_localize.error + ': ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Access check error:', error);
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

        console.log('Loading admin bar items...');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_get_items',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                console.log('Items load response:', response);

                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        displayItems(response.data);
                    } else {
                        $('#apt-items-list').html('<tr><td colspan="4">' +
                            apt_localize.no_items + '</td></tr>');
                    }
                } else {
                    var errorMsg = response.data || apt_localize.load_error;
                    console.error('Server error:', errorMsg);
                    $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                        apt_localize.load_error + ': ' + errorMsg + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                var errorMsg = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : error;
                $('#apt-items-list').html('<tr><td colspan="4" class="apt-error">' +
                    apt_localize.request_error + ': ' + errorMsg + '</td></tr>');
            }
        });
    }

    function displayItems(items) {
        console.log('Displaying items:', items);

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

            html += '<tr>';
            html += '<td>' + item.id + '</td>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="' + statusClass + '">' + statusText + '</span></td>';
            html += '<td>';
            html += '<button class="button ' + buttonClass + ' apt-toggle-item" ' +
                   'data-item-id="' + item.id + '" ' +
                   'data-enable="' + !item.enabled + '">' + buttonText + '</button>';
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

            console.log('Toggle item:', itemId, 'Enable:', enable);

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
                    console.log('Toggle response:', response);
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        if (response.data.function_code) {
                            // Можно обновить отображение кода функции
                            console.log('New function code:', response.data.function_code);
                        }
                        loadAdminBarItems(); // Перезагружаем список
                    } else {
                        showMessage(response.data, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Toggle error:', error);
                    showMessage(apt_localize.request_error + ': ' + error, 'error');
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
                  '<span class="screen-reader-text">Скрыть уведомление</span>' +
                  '</button>' +
                  '</div>';

        $('.wrap h1').after(html);

        // Добавляем обработчик для кнопки закрытия
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });

        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }

    // Обработчик для кнопки обновления кода функции
    $('#apt-refresh-function').on('click', function() {
        location.reload(); // Просто перезагружаем страницу для простоты
    });

    // Пересоздание функции
    $('#apt-cleanup-function').on('click', function() {
        if (!confirm('Пересоздать функцию remove_items_from_admin_bar? Это сохранит все текущие отключенные элементы.')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('Пересоздание...');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_cleanup_function',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('✅ ' + response.data.message + ' Сохранено ' + response.data.items_count + ' элементов.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('❌ ' + response.data, 'error');
                    $button.prop('disabled', false).text('Пересоздать функцию');
                }
            },
            error: function(xhr, status, error) {
                console.error('Cleanup error:', error);
                showMessage('❌ Ошибка при пересоздании функции: ' + error, 'error');
                $button.prop('disabled', false).text('Пересоздать функцию');
            }
        });
    });

    // Просмотр backup
    $('#apt-view-backup').on('click', function() {
        var $details = $('#apt-backup-details');

        if ($details.is(':visible')) {
            $details.hide();
            return;
        }

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_view_backup',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<div><strong>Backup элементов (' + data.backup_count + '):</strong></div>';
                    html += '<div style="margin: 5px 0;">';
                    data.backup_items.forEach(function(item) {
                        html += '<span class="backup-item">' + item + '</span> ';
                    });
                    html += '</div>';
                    html += '<div><strong>Текущие элементы (' + data.current_count + '):</strong></div>';
                    html += '<div style="margin: 5px 0;">';
                    data.current_items.forEach(function(item) {
                        html += '<span class="backup-item">' + item + '</span> ';
                    });
                    html += '</div>';
                    html += '<div style="margin-top: 10px; font-size: 12px; color: #666;">';
                    html += 'Backup создан при последнем изменении функции. Восстановление добавит элементы из backup к текущим.';
                    html += '</div>';

                    $details.html(html).show();
                }
            },
            error: function(xhr, status, error) {
                console.error('View backup error:', error);
                showMessage('❌ Ошибка при просмотре backup: ' + error, 'error');
            }
        });
    });

    // Восстановление из backup
    $('#apt-restore-backup').on('click', function() {
        if (!confirm('Восстановить элементы из backup? Они будут добавлены к текущим отключенным элементам.')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('Восстановление...');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_restore_from_backup',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('✅ ' + response.data.message + ' Восстановлено ' + response.data.count + ' элементов.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('❌ ' + response.data, 'error');
                    $button.prop('disabled', false).text('Восстановить из backup');
                }
            },
            error: function(xhr, status, error) {
                console.error('Restore backup error:', error);
                showMessage('❌ Ошибка при восстановлении из backup: ' + error, 'error');
                $button.prop('disabled', false).text('Восстановить из backup');
            }
        });
    });

    // Создание функции
    $('#apt-create-function').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Создание...');

        $.ajax({
            url: apt_ajax.url,
            type: 'POST',
            data: {
                action: 'apt_create_function',
                nonce: apt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('✅ ' + response.data.message + ' Добавлено ' + response.data.items_count + ' элементов.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('❌ ' + response.data, 'error');
                    $button.prop('disabled', false).text('Создать функцию');
                }
            },
            error: function() {
                showMessage('❌ Ошибка при создании функции', 'error');
                $button.prop('disabled', false).text('Создать функцию');
            }
        });
    });
    });