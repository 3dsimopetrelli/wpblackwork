<?php
/**
 * Empty state template.
 *
 * @var string $title
 * @var string $message
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="bw-reviews-empty">
    <h3 class="bw-reviews-empty__title"><?php echo esc_html( $title ); ?></h3>
    <p class="bw-reviews-empty__message"><?php echo esc_html( $message ); ?></p>
</div>
