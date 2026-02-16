<?php
/**
 * Email Header (Blackwork override).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$store_name                 = $store_name ?? get_bloginfo( 'name', 'display' );
$email_id                   = isset( $email->id ) ? (string) $email->id : '';
$order                      = isset( $email->object ) && is_a( $email->object, 'WC_Order' ) ? $email->object : null;
$is_customer_order_email    = $order && 0 === strpos( $email_id, 'customer_' );
$customer_first_name        = '';
$order_label                = '';

if ( $order ) {
    $customer_first_name = trim( (string) $order->get_billing_first_name() );

    if ( '' === $customer_first_name ) {
        $customer_first_name = trim( (string) $order->get_shipping_first_name() );
    }

    if ( '' === $customer_first_name ) {
        $customer_first_name = __( 'there', 'woocommerce' );
    }

    $order_label = sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() );
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <title><?php echo esc_html( $store_name ); ?></title>
    </head>
    <body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <table width="100%" id="outer_wrapper" role="presentation">
            <tr>
                <td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
                <td width="600">
                    <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
                        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" role="presentation">
                            <tr>
                                <td align="center" valign="top">
                                    <?php
                                    $img = get_option( 'woocommerce_email_header_image' );
                                    /**
                                     * This filter is documented in templates/emails/email-styles.php
                                     *
                                     * @since 9.6.0
                                     */
                                    if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
                                        $img_transient = get_transient( 'woocommerce_email_header_image' );
                                        $img           = false !== $img_transient ? $img_transient : $img;
                                    }

                                    if ( ! $is_customer_order_email ) :
                                        if ( $email_improvements_enabled ) :
                                            ?>
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
                                                <tr>
                                                    <td id="template_header_image">
                                                        <?php
                                                        if ( $img ) {
                                                            echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" /></p>';
                                                        } else {
                                                            echo '<p class="email-logo-text">' . esc_html( $store_name ) . '</p>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php else : ?>
                                            <div id="template_header_image">
                                                <?php
                                                if ( $img ) {
                                                    echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" /></p>';
                                                }
                                                ?>
                                            </div>
                                            <?php
                                        endif;
                                    endif;
                                    ?>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" role="presentation">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- Header -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
                                                    <tr>
                                                        <td id="header_wrapper">
                                                            <?php if ( $is_customer_order_email ) : ?>
                                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="bw-email-hero-top" role="presentation">
                                                                    <tr>
                                                                        <td class="bw-email-hero-top__logo text-align-left" valign="middle">
                                                                            <?php if ( $img ) : ?>
                                                                                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" class="bw-email-hero-logo" />
                                                                            <?php else : ?>
                                                                                <span class="bw-email-hero-logo-text"><?php echo esc_html( $store_name ); ?></span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="bw-email-hero-top__order text-align-right" valign="middle">
                                                                            <?php echo esc_html( strtoupper( $order_label ) ); ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                                <p class="bw-email-hero-greeting">
                                                                    <?php echo esc_html( sprintf( __( 'Dear %s, thanks for your order!', 'woocommerce' ), $customer_first_name ) ); ?>
                                                                </p>
                                                                <p class="bw-email-hero-subtitle">
                                                                    <?php esc_html_e( 'Please find all the details below. We will notify you when it has been sent.', 'woocommerce' ); ?>
                                                                </p>
                                                            <?php else : ?>
                                                                <h1><?php echo esc_html( $email_heading ); ?></h1>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Header -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- Body -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
                                                    <tr>
                                                        <td valign="top" id="body_content">
                                                            <!-- Content -->
                                                            <table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
                                                                <tr>
                                                                    <td valign="top" id="body_content_inner_cell">
                                                                        <div id="body_content_inner">
