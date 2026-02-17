<?php
/**
 * Supabase auth callback loader.
 *
 * @package WooCommerce/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="bw-auth-callback" data-bw-auth-callback>
	<div class="bw-auth-callback__card" role="status" aria-live="polite">
		<div class="bw-auth-callback__spinner" aria-hidden="true"></div>
		<h2 class="bw-auth-callback__title"><?php esc_html_e( 'Completing sign-in', 'bw' ); ?></h2>
		<p class="bw-auth-callback__text"><?php esc_html_e( 'Please wait a moment while we securely connect your account.', 'bw' ); ?></p>
	</div>
</section>
