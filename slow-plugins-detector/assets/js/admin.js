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
            var tableHtml = '<table class="spd-table wp-list-table widefat fixed striped">' +
                '<thead>' +
                    '<tr>' +
                        '<th>Plugin Name</th>' +
                        '<th>Load Time</th>' +
                        '<th>Status</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody>';

            $.each(results, function(index, result) {
                var statusBadge = getStatusBadge(result.load_time);
                tableHtml += '<tr>' +
                    '<td><strong>' + escapeHtml(result.name) + '</strong><br><small>' + escapeHtml(result.plugin) + '</small></td>' +
                    '<td>' + result.load_time.toFixed(2) + ' ms</td>' +
                    '<td>' + statusBadge + '</td>' +
                '</tr>';
            });

            tableHtml += '</tbody></table>';
            $('#spd-results-content').html(tableHtml);
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