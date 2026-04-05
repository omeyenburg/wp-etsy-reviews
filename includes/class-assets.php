<?php
/**
 * Copyright (C) 2026 omeyenburg
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Etsy_Reviews_Assets {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        // Get layout setting
        $settings = get_option('wp_etsy_reviews_settings', []);
        $layout = isset($settings['layout']) ? $settings['layout'] : 'carousel';

        // Enqueue layout-specific CSS
        if ($layout === 'grid') {
            wp_enqueue_style(
                'wp-etsy-reviews-grid',
                WP_ETSY_REVIEWS_URL . 'assets/grid.css',
                [],
                WP_ETSY_REVIEWS_VERSION
            );
        } elseif ($layout === 'list') {
            wp_enqueue_style(
                'wp-etsy-reviews-list',
                WP_ETSY_REVIEWS_URL . 'assets/list.css',
                [],
                WP_ETSY_REVIEWS_VERSION
            );
        } else {
            // Default carousel
            wp_enqueue_style(
                'wp-etsy-reviews-carousel',
                WP_ETSY_REVIEWS_URL . 'assets/carousel.css',
                [],
                WP_ETSY_REVIEWS_VERSION
            );

            wp_enqueue_script(
                'wp-etsy-reviews-carousel',
                WP_ETSY_REVIEWS_URL . 'assets/carousel.js',
                [],
                WP_ETSY_REVIEWS_VERSION,
                true
            );
        }
    }
}
