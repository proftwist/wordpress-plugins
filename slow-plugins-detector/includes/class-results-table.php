<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для генерации таблицы результатов
 */
class SPD_Results_Table {

    /**
     * Отображение таблицы с результатами
     */
    public static function display($results = array()) {
        if (empty($results)) {
            echo '<p>' . __('No test results available. Run a test first.', 'slow-plugins-detector') . '</p>';
            return;
        }

        $active_plugins = get_option('active_plugins', array());
        ?>
        <table class="spd-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Plugin Name', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Load Time', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Status', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Actions', 'slow-plugins-detector'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result):
                    $is_active = in_array($result['plugin'], $active_plugins);
                ?>
                    <tr data-plugin="<?php echo esc_attr($result['plugin']); ?>">
                        <td>
                            <strong><?php echo esc_html($result['name']); ?></strong>
                            <br><small><?php echo esc_html($result['plugin']); ?></small>
                        </td>
                        <td>
                            <?php echo number_format($result['load_time'], 2); ?> ms
                        </td>
                        <td>
                            <?php echo self::get_status_badge($result['load_time']); ?>
                        </td>
                        <td>
                            <?php echo self::get_action_button($result['plugin'], $is_active); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Генерация бейджа статуса на основе времени загрузки
     */
    private static function get_status_badge($load_time) {
        if ($load_time > 100) {
            return '<span class="spd-warning">' . __('Slow', 'slow-plugins-detector') . '</span>';
        } elseif ($load_time > 50) {
            return '<span style="color: #dba617;">' . __('Moderate', 'slow-plugins-detector') . '</span>';
        } else {
            return '<span class="spd-good">' . __('Fast', 'slow-plugins-detector') . '</span>';
        }
    }

    /**
     * Генерация кнопки действия (деактивация/активация)
     */
    private static function get_action_button($plugin_file, $is_active) {
        $button_class = 'button spd-toggle-plugin';
        $button_text = $is_active ? __('Deactivate', 'slow-plugins-detector') : __('Activate', 'slow-plugins-detector');
        $action_type = $is_active ? 'deactivate' : 'activate';

        return sprintf(
            '<button class="%s" data-plugin="%s" data-action="%s" type="button">%s</button>',
            esc_attr($button_class),
            esc_attr($plugin_file),
            esc_attr($action_type),
            esc_html($button_text)
        );
    }
}