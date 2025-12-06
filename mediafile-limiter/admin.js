(function($) {
    'use strict';

    // Оптимизированная инициализация с проверкой на админ-страницу
    function initFileSizeLimit() {
        // Проверяем, что мы на странице настроек медиа
        if (typeof mediaSettingsPage === 'undefined' || !mediaSettingsPage) {
            return;
        }

        // Кэшируем селекторы для избежания повторных вычислений
        var $uploadSizeInput = $('#dipsic_max_upload_size_mb');
        var $aggressiveModeInput = $('#dipsic_aggressive_mode');

        // Проверяем существование элементов
        if ($uploadSizeInput.length === 0 && $aggressiveModeInput.length === 0) {
            return;
        }

        // Ленивое подключение обработчиков
        initUploadSizeHandler($uploadSizeInput);
        initAggressiveModeHandler($aggressiveModeInput);
    }

    // Оптимизированный обработчик размера файла
    function initUploadSizeHandler($input) {
        if ($input.length === 0) return;

        $input.on('input.dipsic', function() {
            var $this = $(this);
            var customLimit = parseFloat($this.val()) || 0;

            // Минимизируем DOM операции
            var $warning = $('.dipsic-limit-warning');
            if (customLimit > 0) {
                $warning.show();
            } else {
                $warning.hide();
            }
        });
    }

    // Оптимизированный обработчик агрессивного режима
    function initAggressiveModeHandler($input) {
        if ($input.length === 0) return;

        $input.on('change.dipsic', function(e) {
            var $this = $(this);

            if ($this.is(':checked')) {
                // Используем более эффективное подтверждение
                setTimeout(function() {
                    if (!confirm('Включение агрессивного режима может не работать на некоторых хостингах. Продолжить?')) {
                        $this.prop('checked', false);
                    }
                }, 100);
            }
        });
    }

    // Ленивая инициализация с проверкой на нужную страницу
    function checkAndInit() {
        // Проверяем, что скрипт загружен на правильной странице
        if ($('body').hasClass('options-media')) {
            initFileSizeLimit();
        }
    }

    // Используем более эффективную инициализацию
    $(document).ready(function() {
        // Задерживаем инициализацию для оптимизации загрузки страницы
        setTimeout(checkAndInit, 100);
    });

    // Очистка обработчиков при уходе со страницы (предотвращает утечки памяти)
    $(window).on('beforeunload.dipsic', function() {
        $(document).off('.dipsic');
    });

})(jQuery);