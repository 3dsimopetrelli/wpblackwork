<?php
/**
 * Reviews widget template.
 *
 * @var array<string,mixed> $view
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section
    class="bw-reviews"
    data-instance-id="<?php echo esc_attr( (string) $view['instance_id'] ); ?>"
    data-product-id="<?php echo esc_attr( (string) $view['product_id'] ); ?>"
    data-review-widget
>
    <script type="application/json" class="bw-reviews-config"><?php echo wp_json_encode( $view['config'] ); ?></script>

    <div class="bw-reviews__shell<?php echo empty( $view['has_reviews'] ) ? ' is-empty' : ''; ?>">
        <header class="bw-reviews__header">
            <div class="bw-reviews-summary">
                <button
                    type="button"
                    class="bw-reviews-summary__trigger<?php echo empty( $view['breakdown_interactive'] ) ? ' is-static' : ''; ?>"
                    data-review-summary-trigger
                    aria-expanded="false"
                    aria-controls="bw-reviews-breakdown-<?php echo esc_attr( (string) $view['instance_id'] ); ?>"
                    <?php disabled( empty( $view['breakdown_interactive'] ) ); ?>
                >
                    <span class="bw-reviews-summary__stars" aria-hidden="true">
                        <?php echo wp_kses_post( (string) $view['summary_stars_html'] ); ?>
                    </span>
                    <span class="bw-reviews-summary__count"><?php echo esc_html( (string) $view['approved_count_label'] ); ?></span>
                    <span class="bw-reviews-summary__chevron" aria-hidden="true">
                        <svg viewBox="0 0 20 20" focusable="false" aria-hidden="true">
                            <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>

                <?php if ( ! empty( $view['show_breakdown'] ) ) : ?>
                    <div
                        class="bw-reviews-breakdown<?php echo ! empty( $view['breakdown_interactive'] ) ? ' is-collapsible' : ' is-static'; ?>"
                        id="bw-reviews-breakdown-<?php echo esc_attr( (string) $view['instance_id'] ); ?>"
                        data-review-breakdown
                        <?php echo ! empty( $view['breakdown_interactive'] ) ? ' hidden' : ''; ?>
                    >
                        <div class="bw-reviews-breakdown__summary">
                            <span class="bw-reviews-breakdown__summary-star">★</span>
                            <span class="bw-reviews-breakdown__summary-score"><?php echo esc_html( (string) $view['average_label'] ); ?></span>
                        </div>
                        <?php foreach ( $view['breakdown'] as $row ) : ?>
                            <div class="bw-reviews-breakdown__row">
                                <span class="bw-reviews-breakdown__label"><?php echo esc_html( sprintf( __( '%d stars', 'bw' ), absint( $row['rating'] ) ) ); ?></span>
                                <span class="bw-reviews-breakdown__bar"><span style="width: <?php echo esc_attr( (string) $row['percent'] ); ?>%;"></span></span>
                                <span class="bw-reviews-breakdown__count"><?php echo esc_html( sprintf( '(%d)', absint( $row['count'] ) ) ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bw-reviews-controls">
                <?php if ( ! empty( $view['can_write_review'] ) ) : ?>
                    <button type="button" class="bw-reviews__write" data-review-open="create">
                        <?php echo esc_html( (string) $view['write_review_label'] ); ?>
                    </button>
                <?php endif; ?>

                <div class="bw-reviews-sort">
                    <button
                        type="button"
                        class="bw-reviews-sort__trigger"
                        data-review-sort-trigger
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e( 'Sort reviews', 'bw' ); ?>"
                        <?php disabled( empty( $view['has_reviews'] ) ); ?>
                    >
                        <svg viewBox="0 0 20 20" focusable="false" aria-hidden="true">
                            <path d="M4 5h12M7 10h9M10 15h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="6" cy="5" r="1.5" fill="currentColor"/>
                            <circle cx="9" cy="10" r="1.5" fill="currentColor"/>
                            <circle cx="12" cy="15" r="1.5" fill="currentColor"/>
                        </svg>
                    </button>
                    <div class="bw-reviews-sort__menu" data-review-sort-menu hidden>
                        <p class="bw-reviews-sort__title"><?php esc_html_e( 'Sort by', 'bw' ); ?></p>
                        <?php foreach ( $view['sort_options'] as $option ) : ?>
                            <button
                                type="button"
                                class="bw-reviews-sort__option<?php echo 'featured' === $option['value'] ? ' is-selected' : ''; ?>"
                                data-sort-value="<?php echo esc_attr( (string) $option['value'] ); ?>"
                            >
                                <span><?php echo esc_html( (string) $option['label'] ); ?></span>
                                <span class="bw-reviews-sort__check" aria-hidden="true">✓</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="bw-reviews-grid" data-review-grid>
            <?php
            if ( ! empty( $view['has_reviews'] ) ) {
                echo wp_kses_post( (string) $view['reviews_html'] );
            } else {
                $title   = (string) $view['empty_title'];
                $message = (string) $view['empty_message'];
                include __DIR__ . '/empty-state.php';
            }
            ?>
        </div>

        <?php if ( ! empty( $view['has_more'] ) ) : ?>
            <div class="bw-reviews__footer" data-review-footer>
                <button type="button" class="bw-reviews__more" data-review-load-more>
                    <?php echo esc_html( (string) $view['load_more_label'] ); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ( ! empty( $view['render_modal'] ) ) : ?>
    <?php include __DIR__ . '/modal.php'; ?>
<?php endif; ?>
