<?php
/**
 * Настройки админки для плагина Typo Reporter
 *
 * Отвечает за регистрацию настроек и отображение страницы админки.
 *
 * @package TypoReporter
 * @since 1.0.0
 */

// Защита от прямого доступа
defined('ABSPATH') || exit;

/**
 * Регистрация настроек плагина
 *
 * @since 1.0.0
 */
function typo_reporter_register_settings() {
    // Регистрация основной группы настроек
    register_setting(
        'typo_reporter_settings',
        'typo_reporter_enabled',
        array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean'
        )
    );

    // Добавление секции настроек
    add_settings_section(
        'typo_reporter_main_settings',
        __('Main Settings', 'typo-reporter'),
        'typo_reporter_main_settings_callback',
        'typo_reporter_settings'
    );

    // Добавление поля настройки включения/выключения плагина
    add_settings_field(
        'typo_reporter_enabled',
        __('Enable Typo Reporter', 'typo-reporter'),
        'typo_reporter_enabled_callback',
        'typo_reporter_settings',
        'typo_reporter_main_settings'
    );
}

/**
 * Отображение страницы настроек плагина
 *
 * Выводит HTML-разметку страницы настроек в админке WordPress,
 * включая форму с настройками плагина и таблицу репортов.
 *
 * @since 1.0.1
 */
function typo_reporter_options_page() {
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    ?>
    <div class="wrap">
        <h1><?php _e('Typo Reporter', 'typo-reporter'); ?></h1>

        <!-- Настройки -->
        <h2><?php _e('Settings', 'typo-reporter'); ?></h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('typo_reporter_settings');
            do_settings_sections('typo_reporter_settings');
            submit_button();
            ?>
        </form>

        <hr>

        <!-- Репорты опечаток -->
        <h2><?php _e('Typo Reports', 'typo-reporter'); ?></h2>

        <!-- Кнопка очистки таблицы -->
        <div class="typo-reporter-clear-table-container">
            <button type="button" id="clear-reports-table" class="button button-secondary"><?php _e('Clear Table', 'typo-reporter'); ?></button>
            <p class="description"><?php _e('This will permanently delete all typo reports from the database.', 'typo-reporter'); ?></p>
        </div>

        <?php typo_reporter_display_reports_content(); ?>
    </div>
    <?php
}

/**
 * Содержимое таблицы репортов (для основной страницы настроек)
 *
 * @since 1.0.1
 */
function typo_reporter_display_reports_content() {
    // Получение параметров фильтрации
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    // Получение репортов
    $reports = TypoReporterDatabase::get_reports(array(
        'status' => $status_filter,
        'limit' => $per_page,
        'offset' => $offset,
        'orderby' => 'created_at',
        'order' => 'DESC'
    ));

    // Получение общего количества
    $total_reports = TypoReporterDatabase::get_reports_count($status_filter);
    $total_pages = ceil($total_reports / $per_page);

    ?>
    <!-- Фильтры -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="status_filter" id="status_filter">
                <option value=""><?php _e('All statuses', 'typo-reporter'); ?></option>
                <option value="new" <?php selected($status_filter, 'new'); ?>><?php _e('New', 'typo-reporter'); ?></option>
                <option value="resolved" <?php selected($status_filter, 'resolved'); ?>><?php _e('Resolved', 'typo-reporter'); ?></option>
                <option value="dismissed" <?php selected($status_filter, 'dismissed'); ?>><?php _e('Dismissed', 'typo-reporter'); ?></option>
            </select>
            <button type="button" class="button" id="filter-reports"><?php _e('Filter', 'typo-reporter'); ?></button>
        </div>

        <div class="tablenav-pages">
            <?php if ($total_pages > 1): ?>
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_reports, 'typo-reporter'), number_format_i18n($total_reports)); ?></span>
                <span class="pagination-links">
                    <?php if ($paged > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url(add_query_arg(array('paged' => 1))); ?>">&laquo;</a>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $paged - 1))); ?>">&lsaquo;</a>
                    <?php endif; ?>

                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'typo-reporter'); ?></label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $paged; ?>" size="1" aria-describedby="table-paging" />
                        <span class="tablenav-paging-text"> <?php _e('of', 'typo-reporter'); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
                    </span>

                    <?php if ($paged < $total_pages): ?>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $paged + 1))); ?>">&rsaquo;</a>
                        <a class="last-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $total_pages))); ?>">&raquo;</a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Таблица репортов -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id"><?php _e('ID', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-selected-text"><?php _e('Selected Text', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-error-description"><?php _e('Error Description', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-page-url"><?php _e('Page URL', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-status"><?php _e('Status', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-created-at"><?php _e('Created At', 'typo-reporter'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'typo-reporter'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7"><?php _e('No reports found.', 'typo-reporter'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <tr data-report-id="<?php echo esc_attr($report->id); ?>">
                        <td><?php echo esc_html($report->id); ?></td>
                        <td><?php echo esc_html($report->selected_text); ?></td>
                        <td><?php echo esc_html($report->error_description); ?></td>
                        <td><a href="<?php echo esc_url($report->page_url); ?>" target="_blank"><?php echo esc_html($report->page_url); ?></a></td>
                        <td>
                            <select class="status-select" data-report-id="<?php echo esc_attr($report->id); ?>">
                                <option value="new" <?php selected($report->status, 'new'); ?>><?php _e('New', 'typo-reporter'); ?></option>
                                <option value="resolved" <?php selected($report->status, 'resolved'); ?>><?php _e('Resolved', 'typo-reporter'); ?></option>
                                <option value="dismissed" <?php selected($report->status, 'dismissed'); ?>><?php _e('Dismissed', 'typo-reporter'); ?></option>
                            </select>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($report->created_at))); ?></td>
                        <td>
                            <button type="button" class="button delete-report" data-report-id="<?php echo esc_attr($report->id); ?>"><?php _e('Delete', 'typo-reporter'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    jQuery(document).ready(function($) {
        // Фильтрация по статусу
        $('#filter-reports').on('click', function() {
            var status = $('#status_filter').val();
            var url = window.location.href.split('?')[0];
            var params = new URLSearchParams(window.location.search);
            if (status) {
                params.set('status', status);
            } else {
                params.delete('status');
            }
            params.delete('paged'); // Сбрасываем пагинацию при фильтрации
            window.location.href = url + (params.toString() ? '?' + params.toString() : '');
        });

        // Обновление статуса
        $('.status-select').on('change', function() {
            var reportId = $(this).data('report-id');
            var status = $(this).val();

            $.ajax({
                url: typoReporterAdminSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_update_status',
                    report_id: reportId,
                    status: status,
                    nonce: typoReporterAdminSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Можно добавить уведомление об успехе
                    } else {
                        alert(response.data.message);
                        // Восстанавливаем предыдущее значение
                        location.reload();
                    }
                },
                error: function() {
                    alert(typoReporterAdminSettings.messages.error);
                    location.reload();
                }
            });
        });

        // Удаление репорта
        $('.delete-report').on('click', function() {
            if (!confirm(typoReporterAdminSettings.messages.confirmDelete)) {
                return;
            }

            var reportId = $(this).data('report-id');
            var $row = $(this).closest('tr');

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
                        $row.fadeOut(function() {
                            $row.remove();
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(typoReporterAdminSettings.messages.deleteError);
                }
            });
        });

        // Очистка таблицы
        $('#clear-reports-table').on('click', function() {
            if (!confirm(typoReporterAdminSettings.messages.confirmClearTable)) {
                return;
            }

            var $button = $(this);
            var originalText = $button.text();

            $.ajax({
                url: typoReporterAdminSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_clear_table',
                    nonce: typoReporterAdminSettings.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text(typoReporterAdminSettings.messages.clearing);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(typoReporterAdminSettings.messages.clearError);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_init', 'typo_reporter_register_settings');

/**
 * Callback функция для секции основных настроек
 *
 * @since 1.0.0
 */
function typo_reporter_main_settings_callback() {
    echo '<p>' . __('Configure the main settings for Typo Reporter plugin.', 'typo-reporter') . '</p>';
}

/**
 * Callback функция для поля включения плагина
 *
 * @since 1.0.0
 */
function typo_reporter_enabled_callback() {
    $value = get_option('typo_reporter_enabled', true);
    echo '<input type="checkbox" id="typo_reporter_enabled" name="typo_reporter_enabled" value="1" ' . checked(1, $value, false) . ' />';
    echo '<label for="typo_reporter_enabled">' . __('Enable typo reporting functionality on the frontend', 'typo-reporter') . '</label>';
}

/**
 * Отображение списка репортов опечаток в админке
 *
 * @since 1.0.0
 */
function typo_reporter_display_reports() {
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Получение параметров фильтрации
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    // Получение репортов
    $reports = TypoReporterDatabase::get_reports(array(
        'status' => $status_filter,
        'limit' => $per_page,
        'offset' => $offset,
        'orderby' => 'created_at',
        'order' => 'DESC'
    ));

    // Получение общего количества
    $total_reports = TypoReporterDatabase::get_reports_count($status_filter);
    $total_pages = ceil($total_reports / $per_page);

    ?>
    <div class="wrap">
        <h1><?php _e('Typo Reports', 'typo-reporter'); ?></h1>

        <!-- Фильтры -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="status_filter" id="status_filter">
                    <option value=""><?php _e('All statuses', 'typo-reporter'); ?></option>
                    <option value="new" <?php selected($status_filter, 'new'); ?>><?php _e('New', 'typo-reporter'); ?></option>
                    <option value="resolved" <?php selected($status_filter, 'resolved'); ?>><?php _e('Resolved', 'typo-reporter'); ?></option>
                    <option value="dismissed" <?php selected($status_filter, 'dismissed'); ?>><?php _e('Dismissed', 'typo-reporter'); ?></option>
                </select>
                <button type="button" class="button" id="filter-reports"><?php _e('Filter', 'typo-reporter'); ?></button>
            </div>

            <div class="tablenav-pages">
                <?php if ($total_pages > 1): ?>
                    <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_reports, 'typo-reporter'), number_format_i18n($total_reports)); ?></span>
                    <span class="pagination-links">
                        <?php if ($paged > 1): ?>
                            <a class="first-page button" href="<?php echo esc_url(add_query_arg(array('paged' => 1))); ?>">&laquo;</a>
                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $paged - 1))); ?>">&lsaquo;</a>
                        <?php endif; ?>

                        <span class="paging-input">
                            <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'typo-reporter'); ?></label>
                            <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $paged; ?>" size="1" aria-describedby="table-paging" />
                            <span class="tablenav-paging-text"> <?php _e('of', 'typo-reporter'); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
                        </span>

                        <?php if ($paged < $total_pages): ?>
                            <a class="next-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $paged + 1))); ?>">&rsaquo;</a>
                            <a class="last-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $total_pages))); ?>">&raquo;</a>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Таблица репортов -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-id"><?php _e('ID', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-selected-text"><?php _e('Selected Text', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-error-description"><?php _e('Error Description', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-page-url"><?php _e('Page URL', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-created-at"><?php _e('Created At', 'typo-reporter'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'typo-reporter'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7"><?php _e('No reports found.', 'typo-reporter'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr data-report-id="<?php echo esc_attr($report->id); ?>">
                            <td><?php echo esc_html($report->id); ?></td>
                            <td><?php echo esc_html($report->selected_text); ?></td>
                            <td><?php echo esc_html($report->error_description); ?></td>
                            <td><a href="<?php echo esc_url($report->page_url); ?>" target="_blank"><?php echo esc_html($report->page_url); ?></a></td>
                            <td>
                                <select class="status-select" data-report-id="<?php echo esc_attr($report->id); ?>">
                                    <option value="new" <?php selected($report->status, 'new'); ?>><?php _e('New', 'typo-reporter'); ?></option>
                                    <option value="resolved" <?php selected($report->status, 'resolved'); ?>><?php _e('Resolved', 'typo-reporter'); ?></option>
                                    <option value="dismissed" <?php selected($report->status, 'dismissed'); ?>><?php _e('Dismissed', 'typo-reporter'); ?></option>
                                </select>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($report->created_at))); ?></td>
                            <td>
                                <button type="button" class="button delete-report" data-report-id="<?php echo esc_attr($report->id); ?>"><?php _e('Delete', 'typo-reporter'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Фильтрация по статусу
        $('#filter-reports').on('click', function() {
            var status = $('#status_filter').val();
            var url = window.location.href.split('?')[0];
            var params = new URLSearchParams(window.location.search);
            if (status) {
                params.set('status', status);
            } else {
                params.delete('status');
            }
            params.delete('paged'); // Сбрасываем пагинацию при фильтрации
            window.location.href = url + (params.toString() ? '?' + params.toString() : '');
        });

        // Обновление статуса
        $('.status-select').on('change', function() {
            var reportId = $(this).data('report-id');
            var status = $(this).val();

            $.ajax({
                url: typoReporterAdminSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'typo_reporter_update_status',
                    report_id: reportId,
                    status: status,
                    nonce: typoReporterAdminSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Можно добавить уведомление об успехе
                    } else {
                        alert(response.data.message);
                        // Восстанавливаем предыдущее значение
                        location.reload();
                    }
                },
                error: function() {
                    alert(typoReporterAdminSettings.messages.error);
                    location.reload();
                }
            });
        });

        // Удаление репорта
        $('.delete-report').on('click', function() {
            if (!confirm(typoReporterAdminSettings.messages.confirmDelete)) {
                return;
            }

            var reportId = $(this).data('report-id');
            var $row = $(this).closest('tr');

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
                        $row.fadeOut(function() {
                            $row.remove();
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(typoReporterAdminSettings.messages.deleteError);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Добавление пункта меню для просмотра репортов (удалено - все на одной странице)
 *
 * @since 1.0.0
 * @deprecated 1.0.1 Все функционал объединен на основной странице настроек
 */