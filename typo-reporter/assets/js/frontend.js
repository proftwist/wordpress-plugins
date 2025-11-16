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
         * Текущее модальное окно
         */
        currentModal: null,

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
            // Закрываем предыдущее модальное окно если есть
            if (this.currentModal) {
                this.closeModal();
            }

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
            this.currentModal = $('.typo-reporter-modal-overlay');

            // Привязываем события для этого модального окна
            this.bindModalEvents();

            // Фокусируемся на поле описания ошибки
            setTimeout(function() {
                $('#typo-reporter-error-description').focus();
            }, 100);
        },

        /**
         * Привязка событий модального окна
         */
        bindModalEvents: function() {
            var self = this;

            // Закрытие по клику на оверлей
            this.currentModal.on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });

            // Закрытие по кнопке закрытия
            this.currentModal.find('.typo-reporter-modal-close').on('click', function() {
                self.closeModal();
            });

            // Отмена
            this.currentModal.find('.typo-reporter-cancel').on('click', function() {
                self.closeModal();
            });

            // Отправка
            this.currentModal.find('.typo-reporter-submit').on('click', function() {
                self.submitReport();
            });

            // Обработка клавиш в модальном окне
            this.currentModal.on('keydown', function(e) {
                // ESC для закрытия
                if (e.key === 'Escape') {
                    self.closeModal();
                }

                // Enter + Ctrl для отправки
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    self.submitReport();
                }
            });

            // Предотвращаем закрытие при клике внутри модального окна
            this.currentModal.find('.typo-reporter-modal').on('click', function(e) {
                e.stopPropagation();
            });
        },

        /**
         * Закрытие модального окна
         */
        closeModal: function() {
            if (this.currentModal) {
                this.currentModal.remove();
                this.currentModal = null;
            }
        },

        /**
         * Отправка репорта
         */
        submitReport: function() {
            if (!this.currentModal) return;

            var self = this;
            var selectedText = $('#typo-reporter-selected-text').val();
            var errorDescription = $('#typo-reporter-error-description').val();
            var pageUrl = window.location.href;


            // Валидация - только проверка на наличие выделенного текста
            if (!selectedText.trim()) {
                this.showMessage(typoReporterSettings.messages.emptyText, 'error');
                return;
            }

            var submitButton = this.currentModal.find('.typo-reporter-submit');
            var originalText = submitButton.text();

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
                    submitButton.prop('disabled', true).text(wp.i18n.__('Sending...', 'typo-reporter'));
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.closeModal();
                    } else {
                        self.showMessage(response.data.message, 'error');
                        submitButton.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    self.showMessage(typoReporterSettings.messages.error, 'error');
                    submitButton.prop('disabled', false).text(originalText);
                }
            });
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
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
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