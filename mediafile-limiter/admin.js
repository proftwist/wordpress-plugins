jQuery(document).ready(function($) {
    // Динамическое обновление диагностики
    $('input[name="dipsic_max_upload_size_mb"]').on('input', function() {
        var customLimit = parseFloat($(this).val()) || 0;
        if (customLimit > 0) {
            $('.dipsic-limit-warning').show();
        } else {
            $('.dipsic-limit-warning').hide();
        }
    });

    // Предупреждение при включении агрессивного режима
    $('input[name="dipsic_aggressive_mode"]').on('change', function() {
        if ($(this).is(':checked')) {
            if (!confirm('Включение агрессивного режима может не работать на некоторых хостингах. Продолжить?')) {
                $(this).prop('checked', false);
            }
        }
    });
});