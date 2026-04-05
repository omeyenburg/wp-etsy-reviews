<?php
if (!defined('ABSPATH')) {
    exit;
}

$api = WP_Etsy_Reviews_API::get_instance();
$total_reviews = $api->get_total_review_count();
$avg_rating = $api->get_average_rating();
$shop_url = 'https://www.etsy.com/de/shop/Piratenbande#reviews';

function render_stars($rating, $size = 'large') {
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
<div class="etsy-reviews-carousel" data-autoplay="6">
    <div class="etsy-carousel-container">
        <div class="etsy-carousel-header">
            <div class="etsy-header-content">
                <?php echo render_stars(round($avg_rating * 2) / 2, 'large'); ?>
                <div class="etsy-rating-text">
                    <?php echo number_format($avg_rating, 1, ',', '.'); ?> von 5 Sternen
                </div>
                <div class="etsy-logo">
                    <a href="<?php echo esc_url($shop_url); ?>" target="_blank" rel="noopener noreferrer" title="Alle Bewertungen auf Etsy ansehen">
                        <img src="<?php echo WP_ETSY_REVIEWS_URL; ?>assets/etsy-logo.svg" alt="Etsy" width="120" height="25">
                    </a>
                </div>
                <div class="etsy-review-count">
                    <span>Basierend auf <strong><?php echo esc_html($total_reviews); ?> Bewertungen</strong></span>
                </div>
            </div>
        </div>
        <div class="etsy-carousel-reviews">
            <div class="etsy-carousel-controls">
                <button class="etsy-control-btn etsy-prev" aria-label="Vorherige Bewertung"><div class="etsy-arrow"></div></button>
                <button class="etsy-control-btn etsy-next" aria-label="Nächste Bewertung"><div class="etsy-arrow"></div></button>
            </div>
            <div class="etsy-carousel-track-wrapper">
                <div class="etsy-carousel-track">
                    <?php foreach($reviews as $review): ?>
                        <div class="etsy-review-card">
                            <div class="etsy-review-inner">
                                <div class="etsy-review-header">
                                    <div class="etsy-platform-badge" title="Gepostet auf Etsy">
                                        <a href="<?php echo esc_url($shop_url); ?>" target="_blank" rel="noopener noreferrer">
                                            <img src="<?php echo WP_ETSY_REVIEWS_URL; ?>assets/etsy-logo.svg" alt="Etsy" width="24" height="24">
                                        </a>
                                    </div>
                                    <div class="etsy-reviewer-avatar">
                                        <img src="https://www.etsy.com/images/avatars/default_avatar_75x75.png" alt="Verified buyer" loading="lazy">
                                    </div>
                                    <div class="etsy-reviewer-info">
                                        <div class="etsy-reviewer-name">Verifizierter Käufer</div>
                                        <div class="etsy-review-date">
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
                                </div>
                                <?php echo render_stars($review['rating'], 'small'); ?>
                                <div class="etsy-review-text">
                                    <?php echo esc_html($review['comment']); ?>
                                </div>
                                <?php if (!empty($review['display_image']) && !empty($review['product_url'])): ?>
                                    <div class="etsy-review-image">
                                        <a href="<?php echo esc_url($review['product_url']); ?>" target="_blank" rel="noopener noreferrer">
                                            <img src="<?php echo esc_url($review['display_image']); ?>" alt="Review image" loading="lazy">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
