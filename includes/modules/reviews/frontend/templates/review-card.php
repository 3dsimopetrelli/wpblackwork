<?php
/**
 * Review card template.
 *
 * @var array<string,mixed> $card
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<article class="bw-reviews-card<?php echo ! empty( $card['featured'] ) ? ' is-featured' : ''; ?>" data-review-id="<?php echo esc_attr( (string) $card['id'] ); ?>">
    <header class="bw-reviews-card__header">
        <div class="bw-reviews-card__identity">
            <div class="bw-reviews-card__heading">
                <h3 class="bw-reviews-card__name"><?php echo esc_html( (string) $card['reviewer_name'] ); ?></h3>
                <?php if ( ! empty( $card['verified_purchase'] ) ) : ?>
                    <span class="bw-reviews-card__badge"><?php esc_html_e( 'Verified', 'bw' ); ?></span>
                <?php endif; ?>
            </div>
            <div class="bw-reviews-card__meta">
                <?php if ( ! empty( $card['date_label'] ) ) : ?>
                    <span class="bw-reviews-card__date"><?php echo esc_html( (string) $card['date_label'] ); ?></span>
                <?php endif; ?>
            </div>
            <div class="bw-reviews-card__rating" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'bw' ), absint( $card['rating'] ) ) ); ?>">
                <?php echo wp_kses_post( (string) $card['stars_html'] ); ?>
            </div>
        </div>
    </header>

    <div class="bw-reviews-card__body">
        <?php echo wp_kses_post( (string) $card['content_html'] ); ?>
    </div>

    <?php if ( ! empty( $card['show_product_context'] ) && ! empty( $card['product_name'] ) ) : ?>
        <div class="bw-reviews-card__product">
            <?php if ( ! empty( $card['product_image_url'] ) ) : ?>
                <div class="bw-reviews-card__product-thumb">
                    <img src="<?php echo esc_url( (string) $card['product_image_url'] ); ?>" alt="<?php echo esc_attr( (string) $card['product_name'] ); ?>" loading="lazy" />
                </div>
            <?php endif; ?>
            <div class="bw-reviews-card__product-name"><?php echo esc_html( (string) $card['product_name'] ); ?></div>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $card['editable'] ) ) : ?>
        <footer class="bw-reviews-card__footer">
            <button type="button" class="bw-reviews-card__edit" data-review-edit="<?php echo esc_attr( (string) $card['id'] ); ?>">
                <?php esc_html_e( 'Edit review', 'bw' ); ?>
            </button>
        </footer>
    <?php endif; ?>
</article>
