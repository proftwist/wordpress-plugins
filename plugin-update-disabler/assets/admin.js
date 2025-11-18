(function($) {
    'use strict';

    $(document).ready(function() {
        $(document).on('click', '.pud-toggle-block', function(e) {
            e.preventDefault();

            var $link = $(this);
            var plugin = $link.data('plugin');
            var action = $link.data('action');
            var currentText = $link.text();

            // Show loading
            var loadingText = action === 'block' ?
                pluginUpdateDisabler.blocking :
                pluginUpdateDisabler.unblocking;
            $link.text(loadingText).prop('disabled', true);

            if (action === 'unblock') {
                // Always show confirmation for unblocking
                if (!confirm(pluginUpdateDisabler.confirmUnblock)) {
                    $link.text(currentText).prop('disabled', false);
                    return;
                }
            }

            var data = {
                action: 'toggle_plugin_block',
                nonce: pluginUpdateDisabler.nonce,
                plugin: plugin,
                action_type: action,
                confirmed: 'true'
            };

            $.post(pluginUpdateDisabler.ajaxurl, data, function(response) {
                if (response.success) {
                    // Update link appearance
                    $link.data('action', response.data.new_action);
                    $link.text(response.data.new_text);
                    $link.removeClass('block-updates unblock-updates').addClass(response.data.new_class);
                } else if (response.data === 'confirmation_required') {
                    // This shouldn't happen with our logic, but handle it anyway
                    $link.text(currentText);
                }

                $link.prop('disabled', false);

                // Force refresh of update counts
                if (typeof wp.a11y !== 'undefined') {
                    wp.a11y.speak(pluginUpdateDisabler.updateComplete);
                }

            }).fail(function() {
                $link.text(currentText).prop('disabled', false);
                alert('Error: Could not update plugin block status.');
            });
        });
    });

})(jQuery);