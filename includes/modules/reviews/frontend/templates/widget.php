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
>
    <script type="application/json" class="bw-reviews-config"><?php echo wp_json_encode( $view['config'] ); ?></script>

    <?php if ( ! empty( $view['notice'] ) && is_array( $view['notice'] ) ) : ?>
        <div class="bw-reviews-notice is-<?php echo esc_attr( (string) $view['notice']['type'] ); ?>" role="status" aria-live="polite">
            <?php echo esc_html( (string) $view['notice']['message'] ); ?>
        </div>
    <?php endif; ?>

    <div class="bw-reviews__shell<?php echo empty( $view['has_reviews'] ) ? ' is-empty' : ''; ?>">
        <header class="bw-reviews__header">
            <div class="bw-reviews-summary">
                <div class="bw-reviews-summary__headline">
                    <span class="bw-reviews-summary__score"><?php echo esc_html( (string) $view['average_label'] ); ?></span>
                    <div class="bw-reviews-summary__copy">
                        <div class="bw-reviews-summary__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Average rating %s out of 5', 'bw' ), (string) $view['average_label'] ) ); ?>">
                            <?php echo wp_kses_post( (string) $view['summary_stars_html'] ); ?>
                        </div>
                        <div class="bw-reviews-summary__count">
                            <?php echo esc_html( (string) $view['approved_count_label'] ); ?>
                        </div>
                    </div>
                </div>

                <?php if ( ! empty( $view['breakdown_interactive'] ) ) : ?>
                    <button type="button" class="bw-reviews-summary__toggle" aria-expanded="false">
                        <?php esc_html_e( 'Rating summary', 'bw' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="bw-reviews-controls">
                <div class="bw-reviews-sort">
                    <button
                        type="button"
                        class="bw-reviews-sort__trigger"
                        aria-expanded="false"
                        <?php disabled( empty( $view['has_reviews'] ) ); ?>
                    >
                        <span class="bw-reviews-sort__label"><?php echo esc_html( (string) $view['sort_label'] ); ?></span>
                    </button>
                    <div class="bw-reviews-sort__menu" hidden>
                        <?php foreach ( $view['sort_options'] as $option ) : ?>
                            <button
                                type="button"
                                class="bw-reviews-sort__option<?php echo 'featured' === $option['value'] ? ' is-active' : ''; ?>"
                                data-sort-value="<?php echo esc_attr( (string) $option['value'] ); ?>"
                            >
                                <?php echo esc_html( (string) $option['label'] ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ( ! empty( $view['can_write_review'] ) ) : ?>
                    <button type="button" class="bw-reviews__write" data-review-open="create">
                        <?php echo esc_html( (string) $view['write_review_label'] ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <?php if ( ! empty( $view['show_breakdown'] ) ) : ?>
            <div
                class="bw-reviews-breakdown<?php echo empty( $view['has_reviews'] ) ? ' is-static' : ' is-collapsible'; ?>"
                <?php echo ! empty( $view['breakdown_interactive'] ) ? 'style="display:none;"' : ''; ?>
            >
                <?php foreach ( $view['breakdown'] as $row ) : ?>
                    <div class="bw-reviews-breakdown__row">
                        <span class="bw-reviews-breakdown__label"><?php echo esc_html( sprintf( __( '%d stars', 'bw' ), absint( $row['rating'] ) ) ); ?></span>
                        <span class="bw-reviews-breakdown__bar"><span style="width: <?php echo esc_attr( (string) $row['percent'] ); ?>%;"></span></span>
                        <span class="bw-reviews-breakdown__count"><?php echo esc_html( (string) $row['count'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
            <div class="bw-reviews__footer">
                <button type="button" class="bw-reviews__more" data-review-load-more>
                    <?php echo esc_html( (string) $view['load_more_label'] ); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/modal.php'; ?>
