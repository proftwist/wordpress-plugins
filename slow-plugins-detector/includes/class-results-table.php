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
        ?>
        <table class="spd-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Plugin Name', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Load Time', 'slow-plugins-detector'); ?></th>
                    <th><?php _e('Status', 'slow-plugins-detector'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
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
}