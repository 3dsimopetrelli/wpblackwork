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
<form class="checkout_coupon woocommerce-form-coupon" method="post" style="display:none">
	<div class="bw-coupon-fields">
		<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
		<input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Enter Coupon Code', 'woocommerce' ); ?>" id="coupon_code" value="" />
		<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
	</div>
</form>
