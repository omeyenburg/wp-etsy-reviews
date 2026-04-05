<?php
/**
 * Copyright (C) 2026 omeyenburg
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

$api = WP_Etsy_Reviews_API::get_instance();
$total_reviews = $api->get_total_review_count();
$avg_rating = $api->get_average_rating();
$shop_url = 'https://www.etsy.com/de/shop/Piratenbande#reviews';

function render_stars_grid($rating, $size = 'large') {
    $full_stars = floor($rating);
    $has_half = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($has_half ? 1 : 0);

    $class = $size === 'large' ? 'etsy-stars-large' : 'etsy-review-stars';
    $width = $size === 'large' ? 30 : 17;
    $height = $size === 'large' ? 30 : 17;

    $html = '<div class="' . $class . '">';

    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<img class="etsy-star" src="https://cdn.trustindex.io/assets/platform/Amazon/star/f.svg" alt="star" width="' . $width . '" height="' . $height . '">';
    }

    if ($has_half) {
        $html .= '<img class="etsy-star" src="https://cdn.trustindex.io/assets/platform/Amazon/star/h.svg" alt="half star" width="' . $width . '" height="' . $height . '">';
    }

    for ($i = 0; $i < $empty_stars; $i++) {
        $html .= '<img class="etsy-star" src="https://cdn.trustindex.io/assets/platform/Amazon/star/e.svg" alt="empty star" width="' . $width . '" height="' . $height . '">';
    }

    $html .= '</div>';

    return $html;
}
?>
<div class="etsy-reviews-grid">
    <div class="etsy-grid-header">
        <div class="etsy-grid-header-content">
            <?php echo render_stars_grid(round($avg_rating * 2) / 2, 'large'); ?>
            <div class="etsy-grid-rating-text">
                <?php echo number_format($avg_rating, 1, ',', '.'); ?> von 5 Sternen
            </div>
            <div class="etsy-grid-logo">
                <a href="<?php echo esc_url($shop_url); ?>" target="_blank" rel="noopener noreferrer" title="Alle Bewertungen auf Etsy ansehen">
                    <img src="<?php echo WP_ETSY_REVIEWS_URL; ?>assets/etsy-logo.svg" alt="Etsy" width="120" height="25">
                </a>
            </div>
            <div class="etsy-grid-review-count">
                <span>Basierend auf <strong><?php echo esc_html($total_reviews); ?> Bewertungen</strong></span>
            </div>
        </div>
    </div>
    <div class="etsy-grid-container">
        <?php foreach($reviews as $review): ?>
            <div class="etsy-grid-card">
                <div class="etsy-grid-card-header">
                    <div class="etsy-grid-avatar">
                        <img src="https://www.etsy.com/images/avatars/default_avatar_75x75.png" alt="Verified buyer" loading="lazy">
                    </div>
                    <div class="etsy-grid-reviewer">
                        <div class="etsy-grid-name">Verifizierter Käufer</div>
                        <div class="etsy-grid-date">
                            <?php
                            $days_ago = floor((current_time('timestamp') - strtotime($review['date'])) / DAY_IN_SECONDS);
                            if ($days_ago == 0) {
                                echo 'Heute';
                            } elseif ($days_ago == 1) {
                                echo 'vor 1 Tag';
                            } elseif ($days_ago < 7) {
                                echo 'vor ' . $days_ago . ' Tagen';
                            } elseif ($days_ago < 14) {
                                echo 'vor 1 Woche';
                            } elseif ($days_ago < 30) {
                                echo 'vor ' . floor($days_ago / 7) . ' Wochen';
                            } elseif ($days_ago < 60) {
                                echo 'vor 1 Monat';
                            } elseif ($days_ago < 365) {
                                echo 'vor ' . floor($days_ago / 30) . ' Monaten';
                            } elseif ($days_ago < 730) {
                                echo 'vor 1 Jahr';
                            } else {
                                echo 'vor ' . floor($days_ago / 365) . ' Jahren';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="etsy-grid-platform-badge" title="Gepostet auf Etsy">
                        <a href="<?php echo esc_url($shop_url); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo WP_ETSY_REVIEWS_URL; ?>assets/etsy-logo.svg" alt="Etsy" width="24" height="24">
                        </a>
                    </div>
                </div>
                <?php echo render_stars_grid($review['rating'], 'small'); ?>
                <div class="etsy-grid-text">
                    <?php echo esc_html($review['comment']); ?>
                </div>
                <?php if (!empty($review['display_image']) && !empty($review['product_url'])): ?>
                    <div class="etsy-grid-image">
                        <a href="<?php echo esc_url($review['product_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($review['display_image']); ?>" alt="Review image" loading="lazy">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
