	<?php 
		global $woocommerce;
		$cart_count = is_object( $woocommerce->cart ) ? $woocommerce->cart->get_cart_contents_count() : 0;
	?>
	<a href='<?php echo wc_get_cart_url() ?>'>
		<div class='cart_menu'>
			<div title='View your shopping cart'>
			<?php if (!isset($settings['cart_icon']['value']['url'])) { ?>
				<i class='<?php echo $settings['cart_icon']['value']?> cart_icon'></i> 
			<?php } else { ?>
				<img alt="Cart Icon" title="View your shopping cart" src='<?php echo $settings['cart_icon']['value']['url'] ?>'>
			<?php } ?> 
			</div>
			<?php if ('yes' == $settings['cart_enable_count']): ?>
			<div>
				<div class='count_icon'><?php echo $cart_count?></div>
			</div>
			<?php endif; ?>
		</div>
	</a>