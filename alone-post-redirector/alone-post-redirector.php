<?php
/**
 * Plugin Name: Alone Post Redirector
 * Plugin URI: http://bychko.ru/
 * Description: Automatically redirects to the single post when there's only one post in categories, tags, or date archives.
 * Version: 1.0.0
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Text Domain: alone-post-redirector
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlonePostRedirector {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('template_redirect', array($this, 'check_redirect'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('alone-post-redirector', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('alone_post_redirector_options', 'alone_post_redirector_settings');

        add_settings_section(
            'alone_post_redirector_section',
            __('Redirect Settings', 'alone-post-redirector'),
            array($this, 'settings_section_callback'),
            'alone_post_redirector'
        );

        add_settings_field(
            'redirect_categories',
            __('Categories', 'alone-post-redirector'),
            array($this, 'redirect_categories_callback'),
            'alone_post_redirector',
            'alone_post_redirector_section'
        );

        add_settings_field(
            'redirect_tags',
            __('Tags', 'alone-post-redirector'),
            array($this, 'redirect_tags_callback'),
            'alone_post_redirector',
            'alone_post_redirector_section'
        );

        add_settings_field(
            'redirect_dates',
            __('Dates', 'alone-post-redirector'),
            array($this, 'redirect_dates_callback'),
            'alone_post_redirector',
            'alone_post_redirector_section'
        );
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Redirect to the single post when there is only one post in:', 'alone-post-redirector') . '</p>';
    }

    /**
     * Categories checkbox callback
     */
    public function redirect_categories_callback() {
        $options = get_option('alone_post_redirector_settings');
        $checked = isset($options['redirect_categories']) ? $options['redirect_categories'] : 0;
        echo '<input type="checkbox" name="alone_post_redirector_settings[redirect_categories]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="alone_post_redirector_settings[redirect_categories]">' . __('Enable redirect for categories', 'alone-post-redirector') . '</label>';
    }

    /**
     * Tags checkbox callback
     */
    public function redirect_tags_callback() {
        $options = get_option('alone_post_redirector_settings');
        $checked = isset($options['redirect_tags']) ? $options['redirect_tags'] : 0;
        echo '<input type="checkbox" name="alone_post_redirector_settings[redirect_tags]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="alone_post_redirector_settings[redirect_tags]">' . __('Enable redirect for tags', 'alone-post-redirector') . '</label>';
    }

    /**
     * Dates checkbox callback
     */
    public function redirect_dates_callback() {
        $options = get_option('alone_post_redirector_settings');
        $checked = isset($options['redirect_dates']) ? $options['redirect_dates'] : 0;
        echo '<input type="checkbox" name="alone_post_redirector_settings[redirect_dates]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="alone_post_redirector_settings[redirect_dates]">' . __('Enable redirect for date archives', 'alone-post-redirector') . '</label>';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Alone Post Redirector', 'alone-post-redirector'),
            __('Alone Post Redirector', 'alone-post-redirector'),
            'manage_options',
            'alone_post_redirector',
            array($this, 'options_page')
        );
    }

    /**
     * Options page
     */
    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Alone Post Redirector Settings', 'alone-post-redirector'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('alone_post_redirector_options');
                do_settings_sections('alone_post_redirector');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Check if redirect is needed
     */
    public function check_redirect() {
        if (is_admin() || !is_archive()) {
            return;
        }

        $options = get_option('alone_post_redirector_settings');

        // Check if we have posts
        if (have_posts()) {
            $post_count = 0;
            $redirect_url = '';

            // Count posts and get the first post URL
            while (have_posts()) {
                the_post();
                $post_count++;
                if ($post_count === 1) {
                    $redirect_url = get_permalink();
                }
            }

            // If there's exactly one post, check if we should redirect
            if ($post_count === 1 && $redirect_url) {
                $should_redirect = false;

                if (is_category() && isset($options['redirect_categories']) && $options['redirect_categories']) {
                    $should_redirect = true;
                } elseif (is_tag() && isset($options['redirect_tags']) && $options['redirect_tags']) {
                    $should_redirect = true;
                } elseif (is_date() && isset($options['redirect_dates']) && $options['redirect_dates']) {
                    $should_redirect = true;
                }

                if ($should_redirect) {
                    wp_redirect($redirect_url, 301);
                    exit;
                }
            }
        }
    }
}

// Initialize the plugin
new AlonePostRedirector();