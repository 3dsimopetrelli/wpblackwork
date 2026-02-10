<?php
/**
 * Cart Empty
 *
 * Custom empty-cart layout aligned with the BW cart popup visual language.
 *
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

$empty_cart_svg = (string) get_option( 'bw_cart_popup_empty_cart_svg', '' );
$return_shop_url = (string) get_option( 'bw_cart_popup_return_shop_url', '' );
if ( '' === $return_shop_url ) {
    $return_shop_url = wc_get_page_permalink( 'shop' );
}

if ( ! $return_shop_url ) {
    $return_shop_url = home_url( '/shop/' );
}

$allowed_svg_tags = [
    'svg'     => [
        'class'               => true,
        'xmlns'               => true,
        'width'               => true,
        'height'              => true,
        'viewbox'             => true,
        'viewBox'             => true,
        'fill'                => true,
        'stroke'              => true,
        'stroke-width'        => true,
        'stroke-linecap'      => true,
        'stroke-linejoin'     => true,
        'aria-hidden'         => true,
        'focusable'           => true,
        'role'                => true,
        'preserveAspectRatio' => true,
    ],
    'g'       => [
        'fill'            => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'transform'       => true,
        'fill-rule'       => true,
        'clip-rule'       => true,
    ],
    'path'    => [
        'd'               => true,
        'fill'            => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'transform'       => true,
        'fill-rule'       => true,
        'clip-rule'       => true,
    ],
    'circle'  => [
        'cx'           => true,
        'cy'           => true,
        'r'            => true,
        'fill'         => true,
        'stroke'       => true,
        'stroke-width' => true,
        'transform'    => true,
    ],
    'rect'    => [
        'x'            => true,
        'y'            => true,
        'width'        => true,
        'height'       => true,
        'rx'           => true,
        'ry'           => true,
        'fill'         => true,
        'stroke'       => true,
        'stroke-width' => true,
        'transform'    => true,
    ],
    'line'    => [
        'x1'              => true,
        'y1'              => true,
        'x2'              => true,
        'y2'              => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'transform'       => true,
    ],
    'polyline' => [
        'points'          => true,
        'fill'            => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'transform'       => true,
    ],
    'polygon' => [
        'points'          => true,
        'fill'            => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'transform'       => true,
    ],
];
?>

<div class="bw-cart-page-empty bw-cart-popup-empty-state">
    <div class="bw-cart-empty-icon">
        <?php if ( '' !== trim( $empty_cart_svg ) ) : ?>
            <?php echo wp_kses( $empty_cart_svg, $allowed_svg_tags ); ?>
        <?php else : ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle cx="9" cy="21" r="1" />
                <circle cx="20" cy="21" r="1" />
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
            </svg>
        <?php endif; ?>
    </div>

    <p class="bw-cart-empty-text">Your cart is currently empty</p>

    <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', $return_shop_url ) ); ?>" class="bw-cart-popup-return-shop elementor-button elementor-button-link elementor-size-md">
        Return to Shop
    </a>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
