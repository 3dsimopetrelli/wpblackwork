<?php
/**
 * Checkout coupon form - Custom BW layout
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package BW_MEW
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!wc_coupons_enabled()) {
	return;
}

?>
<form class="checkout_coupon woocommerce-form-coupon" method="post" id="woocommerce-checkout-form-coupon" data-form-type="coupon">
	<div class="bw-coupon-fields">
		<div class="bw-coupon-input-wrapper">
			<label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label>
			<input type="text" name="coupon_code" id="coupon_code" class="input-text bw-coupon-code-input" placeholder="" value="" autocomplete="off" />
			<label class="bw-floating-label" data-full="<?php esc_attr_e('Enter coupon code', 'woocommerce'); ?>"
				data-short="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>"><?php esc_html_e('Enter coupon code', 'woocommerce'); ?></label>
		</div>
		<button type="submit" class="button bw-apply-button" name="apply_coupon"
			value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply', 'woocommerce'); ?></button>
	</div>
	<div class="bw-coupon-error" style="display: none;"></div>
	<?php wp_nonce_field('woocommerce-apply-coupon', 'woocommerce-apply-coupon-nonce'); ?>
	<input type="hidden" name="_wp_http_referer"
		value="<?php echo esc_attr(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>" />
</form>
