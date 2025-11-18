<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Функция отображения страницы админки
 */
function spd_render_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Slow Plugins Detector', 'slow-plugins-detector'); ?></h1>

        <div class="card">
            <h2><?php _e('Performance Test', 'slow-plugins-detector'); ?></h2>
            <p>
                <?php _e('This test will measure the load time of each active plugin on the frontend of your site. The test may take several minutes to complete.', 'slow-plugins-detector'); ?>
            </p>

            <button id="spd-run-test" class="button button-primary spd-button">
                <?php _e('Run Performance Test', 'slow-plugins-detector'); ?>
            </button>

            <div id="spd-loading" class="spd-loading">
                <p>
                    <span class="spinner is-active" style="float: none;"></span>
                    <strong><?php _e('Testing plugins... This may take a few minutes.', 'slow-plugins-detector'); ?></strong>
                </p>
            </div>
        </div>

        <div id="spd-results" class="spd-results" style="display: none;">
            <h2><?php _e('Test Results', 'slow-plugins-detector'); ?></h2>
            <div id="spd-results-content"></div>
        </div>

        <div class="card">
            <h3><?php _e('Important Notes', 'slow-plugins-detector'); ?></h3>
            <ul>
                <li><?php _e('Tests are performed on your site\'s homepage', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Each plugin is tested individually for accurate measurements', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Results may vary depending on server load and caching', 'slow-plugins-detector'); ?></li>
                <li><?php _e('Plugins labeled "Slow" may need optimization or replacement', 'slow-plugins-detector'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}