/**
 * JavaScript админки для плагина Typo Reporter
 *
 * Отвечает за AJAX обработку действий в админке.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.TypoReporterAdmin = {

        /**
         * Инициализация админки
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Привязка событий
         */
        bindEvents: function() {
            // Обработчики для страницы репортов
            $(document).on('click', '.typo-reporter-delete-report', this.handleDeleteReport.bind(this));
            $(document).on('change', '.typo-reporter-status-select', this.handleStatusChange.bind(this));

            // Обработчики для настроек
            $(document).on('change', '#typo_reporter_enabled', this.handleSettingsChange.bind(this));
        },

        /**
         * Обработка удаления репорта
         *
         * @param {Event} e
         */
        handleDeleteReport: function(e) {
            e.preventDefault();

            if (!confirm(typoReporterAdminSettings.messages.confirmDelete)) {
                return;
            }

            var button = $(e.target);
            var reportId = button.data('report-id');
            var row = button.closest('tr');

            button.prop('disabled', true).text(wp.i18n.__('Deleting...', 'typo-reporter'));

            $.ajax({
                url: typoReporterAdminSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_delete_report',
                    report_id: reportId,
                    nonce: typoReporterAdminSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(function() {
                            row.remove();
                            // Обновляем счетчики если есть
                            if (typeof TypoReporterAdmin.updateStats === 'function') {
                                TypoReporterAdmin.updateStats();
                            }
                        });
                        TypoReporterAdmin.showMessage(response.data.message, 'success');
                    } else {
                        TypoReporterAdmin.showMessage(response.data.message, 'error');
                        button.prop('disabled', false).text(wp.i18n.__('Delete', 'typo-reporter'));
                    }
                },
                error: function() {
                    TypoReporterAdmin.showMessage(typoReporterAdminSettings.messages.deleteError, 'error');
                    button.prop('disabled', false).text(wp.i18n.__('Delete', 'typo-reporter'));
                }
            });
        },

        /**
         * Обработка изменения статуса репорта
         *
         * @param {Event} e
         */
        handleStatusChange: function(e) {
            var select = $(e.target);
            var reportId = select.data('report-id');
            var newStatus = select.val();
            var originalStatus = select.data('original-status') || select.find('option[selected]').val();

            // Сохраняем оригинальный статус для возможного отката
            if (!select.data('original-status')) {
                select.data('original-status', originalStatus);
            }

            select.prop('disabled', true);

            $.ajax({
                url: typoReporterAdminSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_update_status',
                    report_id: reportId,
                    status: newStatus,
                    nonce: typoReporterAdminSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        select.data('original-status', newStatus);
                        // Обновляем стилизацию строки
                        select.closest('tr').attr('data-status', newStatus);
                        // Обновляем счетчики если есть
                        if (typeof TypoReporterAdmin.updateStats === 'function') {
                            TypoReporterAdmin.updateStats();
                        }
                        TypoReporterAdmin.showMessage(response.data.message, 'success');
                    } else {
                        // Откатываем изменение
                        select.val(originalStatus);
                        TypoReporterAdmin.showMessage(response.data.message, 'error');
                    }
                    select.prop('disabled', false);
                },
                error: function() {
                    // Откатываем изменение
                    select.val(originalStatus);
                    TypoReporterAdmin.showMessage(typoReporterAdminSettings.messages.error, 'error');
                    select.prop('disabled', false);
                }
            });
        },

        /**
         * Обработка изменения настроек
         *
         * @param {Event} e
         */
        handleSettingsChange: function(e) {
            var checkbox = $(e.target);
            var settingName = checkbox.attr('name');
            var settingValue = checkbox.is(':checked') ? '1' : '0';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_option',
                    option_name: settingName,
                    option_value: settingValue,
                    _wpnonce: $('#_wpnonce').val() || ''
                },
                success: function(response) {
                    if (response.success) {
                        TypoReporterAdmin.showMessage(wp.i18n.__('Settings saved successfully.', 'typo-reporter'), 'success');
                    } else {
                        TypoReporterAdmin.showMessage(wp.i18n.__('Error saving settings.', 'typo-reporter'), 'error');
                    }
                },
                error: function() {
                    TypoReporterAdmin.showMessage(wp.i18n.__('Error saving settings.', 'typo-reporter'), 'error');
                }
            });
        },

        /**
         * Обновление статистики (если используется)
         */
        updateStats: function() {
            // Можно реализовать обновление счетчиков репортов
            // Этот метод вызывается после изменений
        },

        /**
         * Показ сообщения в админке
         *
         * @param {string} message
         * @param {string} type
         */
        showMessage: function(message, type) {
            // Используем стандартную систему уведомлений WordPress
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                wp.data.dispatch('core/notices').createNotice(
                    type === 'error' ? 'error' : 'success',
                    message,
                    {
                        isDismissible: true,
                        type: type === 'error' ? 'snackbar' : 'default'
                    }
                );
            } else {
                // Fallback для старых версий
                var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
                var noticeHtml = '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>';

                $('.wrap h1').after(noticeHtml);

                // Автоматическое скрытие через 5 секунд
                setTimeout(function() {
                    $('.notice').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        /**
         * Фильтрация репортов
         */
        filterReports: function(status, searchTerm) {
            var rows = $('.typo-reporter-reports-table tbody tr');

            rows.each(function() {
                var row = $(this);
                var rowStatus = row.find('.status-select').val();
                var rowText = row.find('.column-selected-text').text().toLowerCase() +
                             row.find('.column-error-description').text().toLowerCase();

                var statusMatch = !status || rowStatus === status;
                var searchMatch = !searchTerm || rowText.indexOf(searchTerm.toLowerCase()) !== -1;

                if (statusMatch && searchMatch) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }
    };

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        if (typeof typoReporterAdminSettings !== 'undefined') {
            TypoReporterAdmin.init();
        }
    });

})(jQuery);