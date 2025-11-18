<?php
/**
 * Plugin Name: Plugin Update Disabler
 * Description: Allows to forcefully block updates for selected plugins and disable all update notifications
 * Plugin URI: https://bychko.ru
 * Version: 1.0.1
 * Author: Владимир Бычко
 * Text Domain: plugin-update-disabler
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PluginUpdateDisabler {

    private $blocked_plugins;
    private $option_name = 'plugin_update_disabler_blocked';

    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Load translations
        load_plugin_textdomain('plugin-update-disabler', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Get blocked plugins list
        $this->blocked_plugins = get_option($this->option_name, array());

        // Add hooks
        $this->add_hooks();
    }

    private function add_hooks() {
        // Add plugin action links
        add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 4);

        // Block plugin updates
        add_filter('site_transient_update_plugins', array($this, 'block_plugin_updates'));

        // Hide update notifications
        add_filter('wp_get_update_data', array($this, 'hide_update_notifications'), 10, 2);

        // Handle AJAX actions
        add_action('wp_ajax_toggle_plugin_block', array($this, 'handle_ajax_toggle'));

        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }

        wp_enqueue_script(
            'plugin-update-disabler',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('plugin-update-disabler', 'pluginUpdateDisabler', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('plugin_update_disabler_nonce'),
            'confirmUnblock' => __('Updating this plugin may harm the site. Continue?', 'plugin-update-disabler'),
            'blocking' => __('Blocking updates...', 'plugin-update-disabler'),
            'unblocking' => __('Unblocking updates...', 'plugin-update-disabler'),
            'updateComplete' => __('Update block status changed.', 'plugin-update-disabler')
        ));
    }

    public function add_plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
        if (is_network_admin()) {
            return $actions;
        }

        $is_blocked = in_array($plugin_file, $this->blocked_plugins);
        $text = $is_blocked ?
            __('Unblock updates', 'plugin-update-disabler') :
            __('Block updates', 'plugin-update-disabler');

        $class = $is_blocked ? 'unblock-updates' : 'block-updates';

        $actions['block_updates'] = sprintf(
            '<a href="#" class="pud-toggle-block %s" data-plugin="%s" data-action="%s">%s</a>',
            esc_attr($class),
            esc_attr($plugin_file),
            $is_blocked ? 'unblock' : 'block',
            esc_html($text)
        );

        return $actions;
    }

    public function block_plugin_updates($transient) {
        if (empty($transient->response) || empty($this->blocked_plugins)) {
            return $transient;
        }

        foreach ($this->blocked_plugins as $blocked_plugin) {
            if (isset($transient->response[$blocked_plugin])) {
                unset($transient->response[$blocked_plugin]);
            }
        }

        return $transient;
    }

    public function hide_update_notifications($update_data, $titles) {
        if (empty($this->blocked_plugins) || empty($update_data['counts']['plugins'])) {
            return $update_data;
        }

        $blocked_count = count(array_intersect($this->blocked_plugins, array_keys(get_plugins())));
        $update_data['counts']['plugins'] = max(0, $update_data['counts']['plugins'] - $blocked_count);
        $update_data['counts']['total'] = max(0, $update_data['counts']['total'] - $blocked_count);

        return $update_data;
    }

    public function handle_ajax_toggle() {
        check_ajax_referer('plugin_update_disabler_nonce', 'nonce');

        if (!current_user_can('update_plugins')) {
            wp_die(-1);
        }

        $plugin_file = sanitize_text_field($_POST['plugin'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? '');

        if (empty($plugin_file)) {
            wp_send_json_error(__('Plugin not specified', 'plugin-update-disabler'));
        }

        if ($action === 'block') {
            $this->block_plugin($plugin_file);
            wp_send_json_success(array(
                'new_action' => 'unblock',
                'new_text' => __('Unblock updates', 'plugin-update-disabler'),
                'new_class' => 'unblock-updates'
            ));
        } elseif ($action === 'unblock') {
            // Always require confirmation for unblocking
            if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
                $this->unblock_plugin($plugin_file);
                wp_send_json_success(array(
                    'new_action' => 'block',
                    'new_text' => __('Block updates', 'plugin-update-disabler'),
                    'new_class' => 'block-updates'
                ));
            } else {
                wp_send_json_error('confirmation_required');
            }
        } else {
            wp_send_json_error(__('Invalid action', 'plugin-update-disabler'));
        }
    }

    private function block_plugin($plugin_file) {
        if (!in_array($plugin_file, $this->blocked_plugins)) {
            $this->blocked_plugins[] = $plugin_file;
            update_option($this->option_name, $this->blocked_plugins);
        }
    }

    private function unblock_plugin($plugin_file) {
        $key = array_search($plugin_file, $this->blocked_plugins);
        if ($key !== false) {
            unset($this->blocked_plugins[$key]);
            $this->blocked_plugins = array_values($this->blocked_plugins);
            update_option($this->option_name, $this->blocked_plugins);
        }
    }
}

// Initialize the plugin
new PluginUpdateDisabler();