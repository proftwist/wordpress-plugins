/**
 * Фронтенд JavaScript для плагина Typo Reporter
 *
 * Отвечает за выделение текста и показ модального окна.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.TypoReporterFrontend = {

        /**
         * Инициализация фронтенда
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Привязка событий
         */
        bindEvents: function() {
            // Обработчик клавиш для выделения текста
            $(document).on('keydown', this.handleKeydown.bind(this));

            // Обработчики для модального окна
            $(document).on('click', '.typo-reporter-modal-overlay', this.closeModal.bind(this));
            $(document).on('click', '.typo-reporter-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.typo-reporter-modal .typo-reporter-cancel', this.closeModal.bind(this));
            $(document).on('click', '.typo-reporter-modal .typo-reporter-submit', this.submitReport.bind(this));
            $(document).on('keydown', '.typo-reporter-modal', this.handleModalKeydown.bind(this));

            // Предотвращаем закрытие модального окна при клике внутри него
            $(document).on('click', '.typo-reporter-modal', function(e) {
                e.stopPropagation();
            });

            // Предотвращаем закрытие модального окна при фокусе на полях
            $(document).on('focus', '.typo-reporter-modal textarea', function(e) {
                e.stopPropagation();
            });

            $(document).on('focus', '.typo-reporter-modal input', function(e) {
                e.stopPropagation();
            });
        },

        /**
         * Обработчик нажатия клавиш
         *
         * @param {Event} e
         */
        handleKeydown: function(e) {
            // Проверяем Ctrl+Enter
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                var selectedText = this.getSelectedText();

                if (selectedText.trim()) {
                    this.showModal(selectedText);
                } else {
                    this.showMessage(typoReporterSettings.messages.emptyText, 'error');
                }
            }
        },

        /**
         * Получение выделенного текста
         *
         * @return {string}
         */
        getSelectedText: function() {
            var text = '';

            if (window.getSelection) {
                text = window.getSelection().toString();
            } else if (document.selection && document.selection.type !== 'Control') {
                text = document.selection.createRange().text;
            }

            return text;
        },

        /**
         * Показ модального окна
         *
         * @param {string} selectedText
         */
        showModal: function(selectedText) {
            // Создаем HTML модального окна
            var modalHtml = `
                <div class="typo-reporter-modal-overlay">
                    <div class="typo-reporter-modal">
                        <div class="typo-reporter-modal-header">
                            <h3>${wp.i18n.__('Report Typo', 'typo-reporter')}</h3>
                            <button type="button" class="typo-reporter-modal-close">&times;</button>
                        </div>
                        <div class="typo-reporter-modal-body">
                            <div class="typo-reporter-field">
                                <label for="typo-reporter-selected-text">${wp.i18n.__('Selected Text:', 'typo-reporter')}</label>
                                <textarea id="typo-reporter-selected-text" readonly>${this.escapeHtml(selectedText)}</textarea>
                            </div>
                            <div class="typo-reporter-field">
                                <label for="typo-reporter-error-description">${wp.i18n.__('Error Description:', 'typo-reporter')}</label>
                                <textarea id="typo-reporter-error-description" placeholder="${wp.i18n.__('Please describe what\'s wrong with the text...', 'typo-reporter')}"></textarea>
                            </div>
                        </div>
                        <div class="typo-reporter-modal-footer">
                            <button type="button" class="button typo-reporter-cancel">${wp.i18n.__('Cancel', 'typo-reporter')}</button>
                            <button type="button" class="button button-primary typo-reporter-submit">${wp.i18n.__('Submit Report', 'typo-reporter')}</button>
                        </div>
                    </div>
                </div>
            `;

            // Добавляем модальное окно в body
            $('body').append(modalHtml);

            // Фокусируемся на поле описания ошибки
            setTimeout(function() {
                $('#typo-reporter-error-description').focus();
            }, 100);
        },

        /**
         * Закрытие модального окна
         */
        closeModal: function() {
            $('.typo-reporter-modal-overlay').remove();
        },

        /**
         * Отправка репорта
         */
        submitReport: function() {
            var selectedText = $('#typo-reporter-selected-text').val();
            var errorDescription = $('#typo-reporter-error-description').val().trim();
            var pageUrl = window.location.href;

            // Валидация - только проверка на наличие выделенного текста
            if (!selectedText.trim()) {
                this.showMessage(typoReporterSettings.messages.emptyText, 'error');
                return;
            }

            // Отправляем AJAX запрос
            $.ajax({
                url: typoReporterSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_submit',
                    selected_text: selectedText,
                    error_description: errorDescription,
                    page_url: pageUrl,
                    nonce: typoReporterSettings.nonce
                },
                beforeSend: function() {
                    $('.typo-reporter-submit').prop('disabled', true).text(wp.i18n.__('Sending...', 'typo-reporter'));
                },
                success: this.handleSubmitSuccess.bind(this),
                error: this.handleSubmitError.bind(this)
            });
        },

        /**
         * Обработка успешной отправки
         *
         * @param {Object} response
         */
        handleSubmitSuccess: function(response) {
            if (response.success) {
                this.showMessage(response.data.message, 'success');
                this.closeModal();
            } else {
                this.showMessage(response.data.message, 'error');
                $('.typo-reporter-submit').prop('disabled', false).text(wp.i18n.__('Submit Report', 'typo-reporter'));
            }
        },

        /**
         * Обработка ошибки отправки
         */
        handleSubmitError: function() {
            this.showMessage(typoReporterSettings.messages.error, 'error');
            $('.typo-reporter-submit').prop('disabled', false).text(wp.i18n.__('Submit Report', 'typo-reporter'));
        },

        /**
         * Обработчик клавиш в модальном окне
         *
         * @param {Event} e
         */
        handleModalKeydown: function(e) {
            // ESC для закрытия
            if (e.key === 'Escape') {
                this.closeModal();
            }

            // Enter в поле описания для отправки
            if (e.key === 'Enter' && e.ctrlKey && e.target.id === 'typo-reporter-error-description') {
                e.preventDefault();
                this.submitReport();
            }
        },

        /**
         * Показ сообщения пользователю
         *
         * @param {string} message
         * @param {string} type
         */
        showMessage: function(message, type) {
            // Удаляем предыдущие сообщения
            $('.typo-reporter-message').remove();

            // Создаем новое сообщение
            var messageHtml = `
                <div class="typo-reporter-message typo-reporter-message-${type}">
                    ${this.escapeHtml(message)}
                </div>
            `;

            // Добавляем в начало body
            $('body').prepend(messageHtml);

            // Автоматически скрываем через 5 секунд
            setTimeout(function() {
                $('.typo-reporter-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Экранирование HTML символов
         *
         * @param {string} text
         * @return {string}
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&',
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '&#039;'
            };

            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }
    };

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        TypoReporterFrontend.init();
    });

})(jQuery);