<?php
/**
 * Checkout coupon form - Custom BW layout
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package BW_MEW
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

if (!wc_coupons_enabled()) {
	return;
}

?>
<div class="checkout_coupon woocommerce-form-coupon" data-form-type="coupon">
	<div class="bw-coupon-fields">
		<div class="bw-coupon-input-wrapper">
			<input type="text" name="coupon_code" class="input-text bw-coupon-code-input" placeholder="" value="" />
			<label class="bw-floating-label" data-full="<?php esc_attr_e('Enter coupon code', 'woocommerce'); ?>"
				data-short="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>"><?php esc_html_e('Enter coupon code', 'woocommerce'); ?></label>
		</div>
		<button type="button" class="button bw-apply-button" name="apply_coupon"
			value="<?php esc_attr_e('Apply', 'woocommerce'); ?>"><?php esc_html_e('Apply', 'woocommerce'); ?></button>
	</div>
	<div class="bw-coupon-error" style="display: none;"></div>
	<input type="hidden" name="woocommerce-apply-coupon-nonce"
		value="<?php echo esc_attr(wp_create_nonce('woocommerce-apply-coupon')); ?>" />
	<input type="hidden" name="_wp_http_referer"
		value="<?php echo esc_attr(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>" />
</div>