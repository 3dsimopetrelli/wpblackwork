<?php
/**
 * My Account navigation
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$menu_items = wc_get_account_menu_items();
$current    = WC()->query->get_current_endpoint();

$orders_url = wc_get_account_endpoint_url( 'orders' );

$icons = [
    'dashboard'        => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="7" height="7" fill="#000"/><rect x="11" y="2" width="7" height="7" fill="#000"/><rect x="2" y="11" width="7" height="7" fill="#000"/><rect x="11" y="11" width="7" height="7" fill="#000"/></svg>',
    'downloads'        => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2v10" stroke="currentColor" stroke-width="2"/><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 15h14v3H3z" fill="currentColor"/></svg>',
    'coupons'          => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5h18v4a2 2 0 010 4v4H3v-4a2 2 0 010-4V5z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="8" cy="9" r="1" fill="currentColor"/><circle cx="8" cy="15" r="1" fill="currentColor"/><path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-dasharray="2 2"/></svg>',
    'orders'           => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 4h12l2 4v12a2 2 0 01-2 2H6a2 2 0 01-2-2V8l2-4z" stroke="currentColor" stroke-width="2"/><path d="M3 8h18" stroke="currentColor" stroke-width="2"/><path d="M9 12h6" stroke="currentColor" stroke-width="2"/></svg>',
    'edit-account'     => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12a5 5 0 100-10 5 5 0 000 10z" stroke="currentColor" stroke-width="2"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2"/></svg>',
    'customer-logout'  => '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 17l-1.5-1.5L12 12 8.5 8.5 10 7l5 5-5 5z" fill="currentColor"/><path d="M4 4h6v2H6v12h4v2H4z" fill="currentColor"/></svg>',
];
?>
<nav class="woocommerce-MyAccount-navigation">
    <ul>
        <?php foreach ( $menu_items as $endpoint => $label ) :
            $is_active    = ( $endpoint === $current ) || ( '' === $endpoint && ! $current );
            $endpoint_url = ( 'orders' === $endpoint ) ? $orders_url : wc_get_account_endpoint_url( $endpoint );
            ?>
            <li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?><?php echo $is_active ? ' is-active' : ''; ?>">
                <a href="<?php echo esc_url( $endpoint_url ); ?>">
                    <span class="bw-account-icon" aria-hidden="true"><?php echo wp_kses_post( $icons[ $endpoint ] ?? '' ); ?></span>
                    <span class="bw-account-label"><?php echo esc_html( strtolower( wp_strip_all_tags( $label ) ) ); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
