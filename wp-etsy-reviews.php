<?php
/**
 * Plugin Name: Etsy Shop Reviews
 * Plugin URI: https://github.com/omeyenburg/wp-etsy-reviews
 * Description: Unofficial plugin to show shop reviews from Etsy as a widget with a shortcode.
 * Version: 1.0.0
 * Author: omeyenburg
 * Author URI: https://github.com/omeyenburg
 * License: GPL-2.0-or-later
 * Text Domain: wp-etsy-reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_ETSY_REVIEWS_VERSION', '1.0.0');
define('WP_ETSY_REVIEWS_PATH', plugin_dir_path(__FILE__));
define('WP_ETSY_REVIEWS_URL', plugin_dir_url(__FILE__));

class WP_Etsy_Reviews {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once WP_ETSY_REVIEWS_PATH . 'includes/class-etsy-api.php';
        require_once WP_ETSY_REVIEWS_PATH . 'includes/class-settings.php';
        require_once WP_ETSY_REVIEWS_PATH . 'includes/class-renderer.php';
        require_once WP_ETSY_REVIEWS_PATH . 'includes/class-shortcode.php';
        require_once WP_ETSY_REVIEWS_PATH . 'includes/class-assets.php';
    }

    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'init']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        WP_Etsy_Reviews_API::get_instance();
        WP_Etsy_Reviews_Settings::get_instance();
        WP_Etsy_Reviews_Shortcode::get_instance();
        WP_Etsy_Reviews_Assets::get_instance();
    }

    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wp-etsy-reviews') . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function activate() {
        // Schedule daily sync on activation
        WP_Etsy_Reviews_API::get_instance();
    }

    public function deactivate() {
        // Clean up scheduled events on deactivation
        $api = WP_Etsy_Reviews_API::get_instance();
        $api->unschedule_sync();
    }
}

function wp_etsy_reviews() {
    return WP_Etsy_Reviews::get_instance();
}

wp_etsy_reviews();
