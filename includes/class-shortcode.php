<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Etsy_Reviews_Shortcode {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('etsy_reviews', [$this, 'render_shortcode']);

        // Hook into output buffering to fix final HTML
        add_action('template_redirect', [$this, 'start_output_buffering']);
    }

    public function start_output_buffering() {
        ob_start([$this, 'fix_final_html_output']);
    }

    public function fix_final_html_output($html) {
        // Only process if our widget is present
        if (strpos($html, 'etsy-reviews-carousel') === false && 
            strpos($html, 'etsy-reviews-grid') === false && 
            strpos($html, 'etsy-reviews-list') === false) {
            return $html;
        }

        // Fix the specific patterns we identified
        // Fix broken button structure: <button...></p><div...></div><p></button>
        $html = preg_replace('/(<button[^>]*>)\s*<\/p>\s*(<div[^>]*>.*?<\/div>)\s*<p>\s*(<\/button>)/s', '$1$2$3', $html);

        // Fix broken link structure: <a...><br/><img...><br/></a>
        $html = preg_replace('/(<a[^>]*>)\s*<br\s*\/?>\s*(<img.*?>)\s*<br\s*\/?>\s*(<\/a>)/s', '$1$2$3', $html);

        // Remove empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);

        // Remove stray </p></div> patterns
        $html = preg_replace('/<\/p>\s*<\/div>/i', '</div>', $html);

        // Remove stray <p> before </div>
        $html = preg_replace('/<p>\s*<\/div>/i', '</div>', $html);

        // Remove stray </p> before <div>
        $html = preg_replace('/<\/p>\s*<div/i', '<div', $html);

        // Remove stray <br> after buttons
        $html = preg_replace('/(<\/button>)\s*<br\s*\/?>/i', '$1', $html);

        return $html;
    }

    public function render_shortcode($atts) {
        $settings = WP_Etsy_Reviews_Settings::get_instance()->get_options();

        $atts = shortcode_atts([
            'layout' => isset($settings['layout']) ? $settings['layout'] : 'carousel',
            'limit' => isset($settings['card_limit']) ? $settings['card_limit'] : 12,
            'show_ratings' => isset($settings['show_ratings']) ? $settings['show_ratings'] : 1,
        ], $atts, 'etsy_reviews');

        return WP_Etsy_Reviews_Renderer::render($atts);
    }
}
