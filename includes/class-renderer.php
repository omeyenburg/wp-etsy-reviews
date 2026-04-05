<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Etsy_Reviews_Renderer {

    public static function render($args) {
        $limit = isset($args['limit']) ? intval($args['limit']) : 12;
        $layout = isset($args['layout']) ? $args['layout'] : 'carousel';

        // Get the show_only_images setting
        $settings = get_option('wp_etsy_reviews_settings', []);
        $show_only_images = isset($settings['show_only_images']) ? $settings['show_only_images'] : 0;

        // Get layout from settings if not provided in args
        if (!isset($args['layout']) && isset($settings['layout'])) {
            $layout = $settings['layout'];
        }

        $api = WP_Etsy_Reviews_API::get_instance();

        if ($show_only_images) {
            // Get reviews that have customer images (up to 12)
            $reviews = $api->get_reviews_with_customer_images($limit);
        } else {
            // Get regular reviews (first 12)
            $reviews = $api->get_reviews($limit);
        }

        if (empty($reviews)) {
            return '<p>' . __('No reviews available. Please check the Etsy Shop Reviews settings and sync your reviews.', 'wp-etsy-reviews') . '</p>';
        }

        $reviews = self::format_reviews($reviews, $show_only_images);

        ob_start();

        $template_file = WP_ETSY_REVIEWS_PATH . 'templates/layout-' . $layout . '.php';
        if (!file_exists($template_file)) {
            $template_file = WP_ETSY_REVIEWS_PATH . 'templates/layout-carousel.php';
        }
        include $template_file;

        return ob_get_clean();
    }

    private static function format_reviews($raw_reviews, $show_only_customer_images = false) {
        $formatted = [];

        foreach ($raw_reviews as $review) {
            $listing_id = isset($review['listing_id']) ? $review['listing_id'] : '';
            $customer_image = isset($review['customer_image']) ? $review['customer_image'] : '';
            $product_images = isset($review['product_images']) ? $review['product_images'] : [];
            $product_url = isset($review['listing_url']) ? $review['listing_url'] : '';

            // Choose which image to display based on setting
            if ($show_only_customer_images && !empty($customer_image)) {
                $display_image = $customer_image;
            } else {
                $display_image = !empty($product_images) ? $product_images[0] : '';
            }

            $formatted[] = [
                'author' => '' . substr($review['buyer_user_id'], 0, 8),
                'rating' => isset($review['rating']) ? intval($review['rating']) : 0,
                'comment' => isset($review['review']) ? $review['review'] : '',
                'date' => isset($review['date']) ? $review['date'] : '',
                'display_image' => $display_image,
                'product_url' => $product_url,
                'listing_id' => $listing_id,
                'transaction_id' => isset($review['transaction_id']) ? $review['transaction_id'] : '',
            ];
        }

        return $formatted;
    }
}
