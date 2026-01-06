<?php
/**
 * Checkout coupon form - Custom BW layout
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package BW_MEW
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) {
	return;
}

?>
<form class="checkout_coupon woocommerce-form-coupon" method="post">
	<div class="bw-coupon-fields">
		<div class="bw-coupon-input-wrapper">
			<input type="text" name="coupon_code" class="input-text" placeholder="" id="coupon_code" value="" />
			<label for="coupon_code" class="bw-floating-label" data-full="<?php esc_attr_e( 'Enter coupon code', 'woocommerce' ); ?>" data-short="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>"><?php esc_html_e( 'Enter coupon code', 'woocommerce' ); ?></label>
		</div>
		<button type="submit" class="button bw-apply-button" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
	</div>
	<div class="bw-coupon-error" style="display: none;"></div>
	<?php wp_nonce_field( 'woocommerce-apply-coupon', 'woocommerce-apply-coupon-nonce' ); ?>
</form>
