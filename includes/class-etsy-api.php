<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Etsy_Reviews_API {
    private static $instance = null;
    private $option_name = 'wp_etsy_reviews_reviews';
    private $api_settings_name = 'wp_etsy_reviews_api_settings';
    private $base_url = 'https://openapi.etsy.com/v3/application/shops/';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_etsy_reviews_daily_sync', [$this, 'sync_reviews']);
        $this->schedule_daily_sync();
    }

    public function sync_reviews() {
        $api_settings = get_option($this->api_settings_name, []);
        $keystring = isset($api_settings['keystring']) ? $api_settings['keystring'] : '';
        $shared_secret = isset($api_settings['shared_secret']) ? $api_settings['shared_secret'] : '';
        $shop_id = isset($api_settings['shop_id']) ? $api_settings['shop_id'] : '';

        if (empty($keystring) || empty($shared_secret) || empty($shop_id)) {
            return false;
        }

        $api_key = $keystring . ':' . $shared_secret;

        $all_reviews = [];
        $offset = 0;
        $limit = 100;

        do {
            $url = "https://openapi.etsy.com/v3/application/shops/{$shop_id}/reviews?limit={$limit}&offset={$offset}";

            $response = wp_remote_get($url, [
                'headers' => [
                    'x-api-key' => $api_key,
                ],
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                break;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                break;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!isset($data['results']) || empty($data['results'])) {
                break;
            }

            $all_reviews = array_merge($all_reviews, $data['results']);
            $offset += $limit;

        } while (count($data['results']) === $limit);

        if (!empty($all_reviews)) {
            $processed_reviews = $this->process_reviews($all_reviews);
            update_option($this->option_name, $processed_reviews);
            update_option($this->option_name . '_last_sync', current_time('timestamp'));
            return true;
        }

        return false;
    }

    private function process_reviews($reviews) {
        $unique_reviews = [];
        $seen = [];
        $processed_count = 0;
        $max_with_images = 12; // Only fetch product images for first 12 reviews

        $api_settings = get_option($this->api_settings_name, []);
        $keystring = isset($api_settings['keystring']) ? $api_settings['keystring'] : '';
        $shared_secret = isset($api_settings['shared_secret']) ? $api_settings['shared_secret'] : '';
        $api_key = (!empty($keystring) && !empty($shared_secret)) ? $keystring . ':' . $shared_secret : '';

        foreach ($reviews as $review) {
            $buyer_id = isset($review['buyer_user_id']) ? $review['buyer_user_id'] : '';
            $review_text = isset($review['review']) ? trim($review['review']) : '';
            $rating = isset($review['rating']) ? intval($review['rating']) : 0;

            $key = $buyer_id . '|' . $review_text;

            if (!isset($seen[$key])) {
                $seen[$key] = true;

                $listing_id = isset($review['listing_id']) ? $review['listing_id'] : '';
                $listing_url = '';
                $customer_image = isset($review['image_url_fullxfull']) ? $review['image_url_fullxfull'] : '';

                // Only fetch product images for first 12 reviews
                $product_images = [];
                if ($listing_id) {
                    $listing_url = "https://www.etsy.com/listing/{$listing_id}";

                    if ($processed_count < $max_with_images && $api_key) {
                        $product_images = $this->get_listing_images($listing_id, $api_key);
                    }
                }

                $unique_reviews[] = [
                    'buyer_user_id' => $buyer_id,
                    'rating' => $rating,
                    'review' => $review_text,
                    'created_timestamp' => isset($review['created_timestamp']) ? $review['created_timestamp'] : 0,
                    'transaction_id' => isset($review['transaction_id']) ? $review['transaction_id'] : '',
                    'listing_id' => $listing_id,
                    'listing_url' => $listing_url,
                    'customer_image' => $customer_image,  // Keep original customer image
                    'product_images' => $product_images,  // Store product images (only for first 12)
                    'date' => isset($review['created_timestamp']) ? date('Y-m-d', $review['created_timestamp']) : ''
                ];

                $processed_count++;
            }
        }

        error_log("Processed $processed_count reviews, fetched product images for " . min($processed_count, $max_with_images) . " reviews");
        return $unique_reviews;
    }

    private function fetch_images_for_reviews($reviews) {
        $processed_reviews = [];

        foreach ($reviews as $review) {
            $listing_id = isset($review['listing_id']) ? $review['listing_id'] : '';
            $images = [];

            if ($listing_id && !isset($fetched_listings[$listing_id])) {
                $fetched_listings[$listing_id] = $this->get_listing_images($listing_id);
            }

            if ($listing_id) {
                $images = isset($fetched_listings[$listing_id]) ? $fetched_listings[$listing_id] : [];
                $review['listing_url'] = "https://www.etsy.com/listing/{$listing_id}";
            }

            $review['all_images'] = $images;
            $review['image_url_fullxfull'] = !empty($images) ? $images[0] : '';

            $processed_reviews[] = $review;
        }

        error_log("Fetched images for " . count($fetched_listings) . " unique listings");
        return $processed_reviews;
    }

    public function get_reviews($limit = null) {
        $reviews = get_option($this->option_name, []);

        if ($limit && is_numeric($limit)) {
            $reviews = array_slice($reviews, 0, intval($limit));
        }

        return $reviews;
    }

    public function get_reviews_with_customer_images($limit = 12) {
        $all_reviews = get_option($this->option_name, []);

        // Find reviews that have customer images
        $reviews_with_images = [];
        foreach ($all_reviews as $review) {
            $customer_image = isset($review['customer_image']) ? $review['customer_image'] : '';

            if (!empty($customer_image) && trim($customer_image) !== '') {
                $reviews_with_images[] = $review;

                // Stop when we have enough reviews with images
                if (count($reviews_with_images) >= $limit) {
                    break;
                }
            }
        }

        return $reviews_with_images;
    }

    public function get_average_rating($before_dedupe = false) {
        if ($before_dedupe) {
            $api_settings = get_option($this->api_settings_name, []);
            $keystring = isset($api_settings['keystring']) ? $api_settings['keystring'] : '';
            $shared_secret = isset($api_settings['shared_secret']) ? $api_settings['shared_secret'] : '';
            $shop_id = isset($api_settings['shop_id']) ? $api_settings['shop_id'] : '';

            if (empty($keystring) || empty($shared_secret) || empty($shop_id)) {
                return 0;
            }

            $api_key = $keystring . ':' . $shared_secret;

            $url = "https://openapi.etsy.com/v3/application/shops/{$shop_id}/reviews?limit=100&offset=0";

            $response = wp_remote_get($url, [
                'headers' => [
                    'x-api-key' => $api_key,
                ],
                'timeout' => 30,
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return 0;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!isset($data['results']) || empty($data['results'])) {
                return 0;
            }

            $total = 0;
            $count = 0;

            foreach ($data['results'] as $review) {
                if (isset($review['rating'])) {
                    $total += intval($review['rating']);
                    $count++;
                }
            }

            return $count > 0 ? round($total / $count, 2) : 0;
        }

        $reviews = $this->get_reviews();

        if (empty($reviews)) {
            return 0;
        }

        $total = 0;
        $count = 0;

        foreach ($reviews as $review) {
            if (isset($review['rating'])) {
                $total += intval($review['rating']);
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : 0;
    }

    public function get_last_sync_time() {
        return get_option($this->option_name . '_last_sync', 0);
    }

    public function get_total_review_count() {
        $reviews = get_option($this->option_name, []);
        return count($reviews);
    }

    private function schedule_daily_sync() {
        if (!wp_next_scheduled('wp_etsy_reviews_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'wp_etsy_reviews_daily_sync');
        }
    }

    public function unschedule_sync() {
        $timestamp = wp_next_scheduled('wp_etsy_reviews_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wp_etsy_reviews_daily_sync');
        }
    }

    private function get_listing_images($listing_id) {
        if (empty($listing_id)) {
            return [];
        }

        $api_settings = get_option($this->api_settings_name, []);
        $keystring = isset($api_settings['keystring']) ? $api_settings['keystring'] : '';
        $shared_secret = isset($api_settings['shared_secret']) ? $api_settings['shared_secret'] : '';

        if (empty($keystring) || empty($shared_secret)) {
            return [];
        }

        $api_key = $keystring . ':' . $shared_secret;

        $url = "https://openapi.etsy.com/v3/application/listings/{$listing_id}/images";

        $response = wp_remote_get($url, [
            'headers' => [
                'x-api-key' => $api_key,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('Etsy Listing Images API Error for ' . $listing_id . ': ' . $response->get_error_message());
            return [];
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log('Etsy Listing Images API HTTP Error for ' . $listing_id . ': ' . wp_remote_retrieve_response_code($response));
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $images = [];
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $image) {
                if (isset($image['url_fullxfull'])) {
                    $images[] = $image['url_fullxfull'];
                }
                // Limit to 12 images max
                if (count($images) >= 12) {
                    break;
                }
            }
            error_log('Found ' . count($images) . ' images for listing ' . $listing_id);
        } else {
            error_log('No image results for listing ' . $listing_id . ': ' . wp_remote_retrieve_body($response));
        }

        return $images;
    }
}
