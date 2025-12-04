(function($) {
    'use strict';

    $(document).ready(function() {

        $('#spd-run-test').on('click', function() {
            var $button = $(this);
            var $loading = $('#spd-loading');
            var $results = $('#spd-results');
            var $resultsContent = $('#spd-results-content');

            // Сохраняем оригинальный текст кнопки и блокируем её
            var originalText = $button.text();
            $button.prop('disabled', true).text(spd_ajax.testing_text);
            $loading.show();
            $results.hide();

            // AJAX запрос
            $.ajax({
                url: spd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spd_run_performance_test',
                    nonce: spd_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Отображаем результаты
                        displayResults(response.data);
                        $results.show();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
                complete: function() {
                    // Восстанавливаем кнопку и скрываем индикатор
                    $button.prop('disabled', false).text(originalText);
                    $loading.hide();
                }
            });
        });

        /**
         * Отображение результатов в таблице
         */
        function displayResults(results) {
            // Получаем список активных плагинов через AJAX
            var tableHtml = '<table class="spd-table wp-list-table widefat fixed striped">' +
                '<thead>' +
                    '<tr>' +
                        '<th>Plugin Name</th>' +
                        '<th>Load Time</th>' +
                        '<th>Status</th>' +
                        '<th>Actions</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody>';

            $.each(results, function(index, result) {
                var statusBadge = getStatusBadge(result.load_time);
                // Проверяем, активен ли плагин (получаем из результатов теста)
                var isActive = result.is_active !== undefined ? result.is_active : true;
                var actionButton = getActionButton(result.plugin, isActive);

                tableHtml += '<tr data-plugin="' + escapeHtml(result.plugin) + '">' +
                    '<td><strong>' + escapeHtml(result.name) + '</strong><br><small>' + escapeHtml(result.plugin) + '</small></td>' +
                    '<td>' + result.load_time.toFixed(2) + ' ms</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + actionButton + '</td>' +
                '</tr>';
            });

            tableHtml += '</tbody></table>';
            $('#spd-results-content').html(tableHtml);

            // Привязываем обработчики событий к кнопкам
            bindToggleButtons();
        }

        /**
         * Генерация кнопки действия
         */
        function getActionButton(pluginFile, isActive) {
            var buttonText = isActive ? spd_ajax.deactivate_text : spd_ajax.activate_text;
            var actionType = isActive ? 'deactivate' : 'activate';
            return '<button class="button spd-toggle-plugin" data-plugin="' + escapeHtml(pluginFile) + '" data-action="' + actionType + '" type="button">' + escapeHtml(buttonText) + '</button>';
        }

        /**
         * Привязка обработчиков событий к кнопкам переключения
         */
        function bindToggleButtons() {
            $('.spd-toggle-plugin').off('click').on('click', function() {
                var $button = $(this);
                var pluginFile = $button.data('plugin');
                var actionType = $button.data('action');
                var originalText = $button.text();

                // Блокируем кнопку и меняем текст
                $button.prop('disabled', true);
                $button.text(actionType === 'deactivate' ? spd_ajax.deactivating_text : spd_ajax.activating_text);

                // AJAX запрос
                $.ajax({
                    url: spd_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'spd_toggle_plugin',
                        nonce: spd_ajax.toggle_nonce,
                        plugin: pluginFile,
                        action_type: actionType
                    },
                    success: function(response) {
                        if (response.success) {
                            // Обновляем состояние кнопки
                            var newActionType = response.data.is_active ? 'deactivate' : 'activate';
                            var newButtonText = response.data.is_active ? spd_ajax.deactivate_text : spd_ajax.activate_text;

                            $button.data('action', newActionType);
                            $button.text(newButtonText);
                            $button.prop('disabled', false);
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                            $button.text(originalText);
                            $button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX Error: ' + error);
                        $button.text(originalText);
                        $button.prop('disabled', false);
                    }
                });
            });
        }

        /**
         * Генерация бейджа статуса
         */
        function getStatusBadge(loadTime) {
            if (loadTime > 100) {
                return '<span class="spd-warning">Slow</span>';
            } else if (loadTime > 50) {
                return '<span style="color: #dba617;">Moderate</span>';
            } else {
                return '<span class="spd-good">Fast</span>';
            }
        }

        /**
         * Экранирование HTML для безопасности
         */
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

})(jQuery);