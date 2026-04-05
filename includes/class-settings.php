<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Etsy_Reviews_Settings {
    private static $instance = null;
    private $option_name = 'wp_etsy_reviews_settings';
    private $api_settings_name = 'wp_etsy_reviews_api_settings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_wp_etsy_reviews_sync_now', [$this, 'handle_manual_sync']);
    }

    public function add_settings_page() {
        add_options_page(
            __('Etsy Shop Reviews Settings', 'wp-etsy-reviews'),
            __('Etsy Reviews', 'wp-etsy-reviews'),
            'manage_options',
            'wp-etsy-reviews',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('wp_etsy_reviews_group', $this->option_name, [$this, 'sanitize_settings']);
        register_setting('wp_etsy_reviews_group', $this->api_settings_name, [$this, 'sanitize_api_settings']);

        // API settings section
        add_settings_section(
            'wp_etsy_reviews_api',
            __('API Key', 'wp-etsy-reviews'),
            [$this, 'section_api_callback'],
            'wp-etsy-reviews'
        );

        add_settings_field(
            'keystring',
            __('Keystring', 'wp-etsy-reviews'),
            [$this, 'field_keystring_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_api'
        );

        add_settings_field(
            'shared_secret',
            __('Shared Secret', 'wp-etsy-reviews'),
            [$this, 'field_shared_secret_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_api'
        );

        add_settings_field(
            'shop_id',
            __('Shop ID', 'wp-etsy-reviews'),
            [$this, 'field_shop_id_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_api'
        );

        // Display settings section
        add_settings_section(
            'wp_etsy_reviews_general',
            __('Display Settings', 'wp-etsy-reviews'),
            null,
            'wp-etsy-reviews'
        );

        add_settings_field(
            'show_only_images',
            __('Reviews with Images', 'wp-etsy-reviews'),
            [$this, 'field_show_only_images_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_general'
        );

        add_settings_field(
            'card_limit',
            __('Number of reviews', 'wp-etsy-reviews'),
            [$this, 'field_card_limit_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_general'
        );

        add_settings_field(
            'layout',
            __('Layout Style', 'wp-etsy-reviews'),
            [$this, 'field_layout_callback'],
            'wp-etsy-reviews',
            'wp_etsy_reviews_general'
        );
    }

    public function section_api_callback() {
        echo '<p>';
        echo __('To display your Etsy shop reviews, you need to connect to the Etsy API using your credentials.', 'wp-etsy-reviews') . '<br>';
        echo sprintf(
            __('Apply for an Etsy API key at: %s', 'wp-etsy-reviews'),
            '<a href="https://www.etsy.com/developers/register" target="_blank">https://www.etsy.com/developers/register</a>'
        );
        echo '</p>';

        // Show sync status and stats
        $api = WP_Etsy_Reviews_API::get_instance();
        $last_sync = $api->get_last_sync_time();
        $reviews = $api->get_reviews();
        $avg_rating = $api->get_average_rating();

        if ($last_sync > 0) {
            echo '<div style="border-top: 1px solid #ddd;">';
            echo '<h4>' . __('Sync Status', 'wp-etsy-reviews') . '</h4>';
            echo '<p><strong>' . __('Last sync:', 'wp-etsy-reviews') . '</strong> ' .
                 date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync) . '</p>';
            echo '<p><strong>' . __('Total reviews:', 'wp-etsy-reviews') . '</strong> ' . count($reviews) . '</p>';
            echo '<p><strong>' . __('Average rating:', 'wp-etsy-reviews') . '</strong> ' . $avg_rating . '</p>';
            echo '<p><strong>' . __('Automatic Sync:', 'wp-etsy-reviews') . '</strong> ' . __('Reviews are automatically synced daily.', 'wp-etsy-reviews') . '</p>';
            echo '<p>';
            echo '</div>';
        }

        echo '<a href="' . admin_url('admin-post.php?action=wp_etsy_reviews_sync_now') . '" class="button">' .
             __('Sync Now', 'wp-etsy-reviews') . '</a>';
        echo '</p>';
    }

    public function field_keystring_callback() {
        $options = get_option($this->api_settings_name, []);
        $keystring = isset($options['keystring']) ? $options['keystring'] : '';
        ?>
        <input type="text" name="<?php echo $this->api_settings_name; ?>[keystring]"
               value="<?php echo esc_attr($keystring); ?>" size="50" style="width: 45%; display: inline-block; margin-right: 2%;" />
        <?php
    }

    public function field_shared_secret_callback() {
        $options = get_option($this->api_settings_name, []);
        $shared_secret = isset($options['shared_secret']) ? $options['shared_secret'] : '';
        ?>
        <input type="text" name="<?php echo $this->api_settings_name; ?>[shared_secret]"
               value="<?php echo esc_attr($shared_secret); ?>" size="50" style="width: 45%; display: inline-block;" />
        <?php
    }

    public function field_shop_id_callback() {
        $options = get_option($this->api_settings_name, []);
        $shop_id = isset($options['shop_id']) ? $options['shop_id'] : '';
        ?>
        <input type="text" name="<?php echo $this->api_settings_name; ?>[shop_id]"
               value="<?php echo esc_attr($shop_id); ?>" />
        <?php
    }

    public function field_show_only_images_callback() {
        $options = get_option($this->option_name, []);
        $show_only_images = isset($options['show_only_images']) ? $options['show_only_images'] : 0;
        ?>
        <label>
            <input type="checkbox" name="<?php echo $this->option_name; ?>[show_only_images]"
                   value="1" <?php checked($show_only_images, 1); ?> />
            <?php _e('Only display reviews that have customer images attached', 'wp-etsy-reviews'); ?>
        </label>
        <?php
    }

    public function field_card_limit_callback() {
        $options = get_option($this->option_name, []);
        $card_limit = isset($options['card_limit']) ? $options['card_limit'] : 12;
        ?>
        <input type="number" name="<?php echo $this->option_name; ?>[card_limit]"
               value="<?php echo esc_attr($card_limit); ?>" min="1" max="50" />
        <?php
    }

    public function field_layout_callback() {
        $options = get_option($this->option_name, []);
        $layout = isset($options['layout']) ? $options['layout'] : 'carousel';
        ?>
        <select name="<?php echo $this->option_name; ?>[layout]">
            <option value="carousel" <?php selected($layout, 'carousel'); ?>><?php _e('Carousel', 'wp-etsy-reviews'); ?></option>
            <option value="grid" <?php selected($layout, 'grid'); ?>><?php _e('Grid', 'wp-etsy-reviews'); ?></option>
            <option value="list" <?php selected($layout, 'list'); ?>><?php _e('List', 'wp-etsy-reviews'); ?></option>
        </select>
        <?php
    }

    public function sanitize_settings($input) {
        $sanitized = [];

        if (isset($input['show_only_images'])) {
            $sanitized['show_only_images'] = intval($input['show_only_images']);
        }

        if (isset($input['card_limit'])) {
            $card_limit = intval($input['card_limit']);
            $sanitized['card_limit'] = max(1, min(50, $card_limit));
        }

        if (isset($input['layout'])) {
            $allowed_layouts = ['carousel', 'grid', 'list'];
            $sanitized['layout'] = in_array($input['layout'], $allowed_layouts) ? $input['layout'] : 'carousel';
        }

        return $sanitized;
    }

    public function sanitize_api_settings($input) {
        $sanitized = [];

        if (isset($input['keystring'])) {
            $sanitized['keystring'] = sanitize_text_field($input['keystring']);
        }

        if (isset($input['shared_secret'])) {
            $sanitized['shared_secret'] = sanitize_text_field($input['shared_secret']);
        }

        if (isset($input['shop_id'])) {
            $sanitized['shop_id'] = sanitize_text_field($input['shop_id']);
        }

        return $sanitized;
    }

    public function handle_manual_sync() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-etsy-reviews'));
        }

        $api = WP_Etsy_Reviews_API::get_instance();
        $result = $api->sync_reviews();

        $redirect_url = add_query_arg(
            ['page' => 'wp-etsy-reviews', 'sync' => $result ? 'success' : 'failed'],
            admin_url('options-general.php')
        );

        wp_redirect($redirect_url);
        exit;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['sync'])) {
            $message = $_GET['sync'] === 'success'
                ? __('Reviews synced successfully!', 'wp-etsy-reviews')
                : __('Failed to sync reviews. Please check your API settings.', 'wp-etsy-reviews');
            $class = $_GET['sync'] === 'success' ? 'updated' : 'error';
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_etsy_reviews_group');
                do_settings_sections('wp-etsy-reviews');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function get_options() {
        return get_option($this->option_name, []);
    }
}
