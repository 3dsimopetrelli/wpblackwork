<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WooCommerce email style customizations for BW.
 */
function bw_mew_register_email_styles() {
    add_filter( 'woocommerce_email_styles', 'bw_mew_customize_woocommerce_email_styles', 20, 2 );
    add_action( 'woocommerce_email_after_order_table', 'bw_mew_render_order_email_primary_cta', 20, 4 );
    add_action( 'woocommerce_email', 'bw_mew_disable_new_order_mobile_messaging', 20, 1 );
    bw_mew_override_woocommerce_email_header_renderer();
}
add_action( 'init', 'bw_mew_register_email_styles' );

/**
 * Replace WooCommerce default email header renderer so templates receive $email object.
 */
function bw_mew_override_woocommerce_email_header_renderer() {
    if ( ! function_exists( 'WC' ) || ! WC() || ! method_exists( WC(), 'mailer' ) ) {
        return;
    }

    $mailer = WC()->mailer();
    if ( ! $mailer || ! is_a( $mailer, 'WC_Emails' ) ) {
        return;
    }

    remove_action( 'woocommerce_email_header', [ $mailer, 'email_header' ] );
    add_action( 'woocommerce_email_header', 'bw_mew_render_woocommerce_email_header', 10, 2 );
}

/**
 * Render WooCommerce email header with support for $email context.
 *
 * @param string        $email_heading Header heading.
 * @param WC_Email|null $email         Email object when available.
 */
function bw_mew_render_woocommerce_email_header( $email_heading, $email = null ) {
    wc_get_template(
        'emails/email-header.php',
        [
            'email_heading' => $email_heading,
            'email'         => $email,
            'store_name'    => get_bloginfo( 'name', 'display' ),
        ]
    );
}

/**
 * Disable WooCommerce app promo line in new order admin email footer.
 *
 * @param WC_Emails $mailer WooCommerce mailer instance.
 */
function bw_mew_disable_new_order_mobile_messaging( $mailer ) {
    if ( ! $mailer || ! isset( $mailer->emails ) || ! is_array( $mailer->emails ) ) {
        return;
    }

    foreach ( $mailer->emails as $email ) {
        if ( ! $email || ! is_a( $email, 'WC_Email_New_Order' ) ) {
            continue;
        }

        remove_action( 'woocommerce_email_footer', [ $email, 'mobile_messaging' ], 9 );
    }
}

/**
 * Inject custom CSS in WooCommerce HTML emails.
 *
 * @param string   $css   Existing email CSS.
 * @param WC_Email $email Email object.
 *
 * @return string
 */
function bw_mew_customize_woocommerce_email_styles( $css, $email ) {
    if ( ! $email || ! is_a( $email, 'WC_Email' ) ) {
        return $css;
    }

    $custom_css = '
#template_container {
    border: 0 !important;
    border-radius: 0 !important;
    overflow: visible !important;
    background: transparent !important;
    box-shadow: none !important;
}

#template_header {
    background: transparent !important;
    border-bottom: 0 !important;
    text-align: center !important;
}

#header_wrapper {
    padding: 20px !important;
}

#template_header h1 {
    color: #111111 !important;
    font-size: 30px !important;
    line-height: 1.2 !important;
    font-weight: 700 !important;
    text-align: center !important;
    margin-bottom: 20px !important;
}

table.bw-email-hero-top {
    width: 100% !important;
    margin: 0 0 16px !important;
}

table.bw-email-hero-top td {
    border: 0 !important;
    padding: 0 !important;
}

.bw-email-hero-logo {
    width: 100px !important;
    max-width: 100px !important;
    height: auto !important;
    display: block !important;
}

.bw-email-hero-logo-text {
    color: #111111 !important;
    font-size: 28px !important;
    font-weight: 700 !important;
    letter-spacing: 0.02em !important;
}

.bw-email-hero-top__order {
    color: #9a9a9a !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    letter-spacing: 0.02em !important;
}

.bw-email-hero-greeting {
    margin: 0 0 12px !important;
    color: #111111 !important;
    font-size: 28px !important;
    line-height: 1.25 !important;
    font-weight: 500 !important;
    text-align: left !important;
}

.bw-email-hero-subtitle {
    margin: 0 0 8px !important;
    color: #7a7a7a !important;
    font-size: 18px !important;
    line-height: 1.4 !important;
    font-weight: 400 !important;
    text-align: left !important;
}

h2,
h3 {
    color: #111111 !important;
    font-weight: 700 !important;
}

h2.email-order-detail-heading {
    margin-top: 20px !important;
}

a {
    color: #111111 !important;
}

.address-title,
#addresses h2 {
    display: block !important;
    margin-bottom: 10px !important;
}

#body_content_inner,
td#body_content_inner_cell {
    padding: 0 !important;
}

table.td + table.td {
    margin-top: 20px !important;
}

table.td {
    width: 100% !important;
    margin: 0 !important;
    border: 1px solid #d6d6d6 !important;
    border-radius: 16px !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    overflow: hidden !important;
}

table.bw-email-downloads-table th:first-child,
table.bw-email-downloads-table td:first-child {
    width: 68% !important;
}

table.bw-email-downloads-table th:last-child,
table.bw-email-downloads-table td:last-child {
    width: 32% !important;
    text-align: right !important;
}

table.bw-email-downloads-table .bw-email-download-product-link {
    text-decoration: none !important;
}

table.td thead th {
    background: #f7f7f7 !important;
    color: #111111 !important;
    font-weight: 700 !important;
    border-bottom: 1px solid #dcdcdc !important;
    padding: 14px 12px !important;
}

table.td tbody td,
table.td tfoot th,
table.td tfoot td {
    border-color: #e4e4e4 !important;
    padding: 12px !important;
}

table.td.email-order-details th:first-child,
table.td.email-order-details td:first-child {
    padding-left: 20px !important;
}

table.td.email-order-details th:last-child,
table.td.email-order-details td:last-child {
    padding-right: 20px !important;
}

table.td tfoot tr:last-child th,
table.td tfoot tr:last-child td {
    border-top: 2px solid #111111 !important;
    font-size: 18px !important;
    font-weight: 700 !important;
}

table.td.email-order-details tr.order-totals th,
table.td.email-order-details tr.order-totals td {
    padding-left: 20px !important;
    padding-right: 20px !important;
}

table.td.email-order-details tr.order-totals.order-totals-subtotal th,
table.td.email-order-details tr.order-totals.order-totals-subtotal td {
    padding-top: 22px !important;
}

table.td.email-order-details tr.order-customer-note td {
    padding: 20px !important;
}

address {
    border: 1px solid #d6d6d6 !important;
    border-radius: 14px !important;
    padding: 14px !important;
    background: #fafafa !important;
    margin-bottom: 10px !important;
}

#addresses address.address {
    min-height: 250px !important;
    box-sizing: border-box !important;
}

a.button,
.button,
a.bw-email-cta {
    background: #79ff00 !important;
    color: #111111 !important;
    border: 1px solid #111111 !important;
    border-radius: 999px !important;
    text-decoration: none !important;
    display: inline-block !important;
    font-size: 20px !important;
    line-height: 1.2 !important;
    font-weight: 500 !important;
    padding: 16px 30px !important;
}

a.bw-email-download-btn {
    font-size: 16px !important;
    line-height: 1 !important;
    font-weight: 600 !important;
    padding: 12px 20px !important;
    white-space: nowrap !important;
}

a.bw-email-download-btn .bw-email-download-btn__icon {
    display: inline-block !important;
    margin-right: 8px !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
}

@media screen and (max-width: 600px) {
    #template_header h1 {
        font-size: 24px !important;
    }

    .bw-email-hero-logo {
        width: 100px !important;
        max-width: 100px !important;
    }

    .bw-email-hero-top__order {
        font-size: 14px !important;
    }

    .bw-email-hero-greeting {
        font-size: 22px !important;
    }

    .bw-email-hero-subtitle {
        font-size: 16px !important;
    }

    a.button,
    .button,
    a.bw-email-cta {
        width: 100% !important;
        box-sizing: border-box !important;
        text-align: center !important;
        font-size: 18px !important;
    }
}
';

    return $css . $custom_css;
}

/**
 * Render a primary CTA button after order table for target emails.
 *
 * @param WC_Order $order         Order object.
 * @param bool     $sent_to_admin Whether the email is sent to admin.
 * @param bool     $plain_text    Plain text mode flag.
 * @param WC_Email $email         Email object.
 */
function bw_mew_render_order_email_primary_cta( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $plain_text || ! $email || ! is_a( $email, 'WC_Email' ) ) {
        return;
    }

    $email_id = isset( $email->id ) ? (string) $email->id : '';

    if ( $sent_to_admin && 'new_order' !== $email_id ) {
        return;
    }

    if ( ! $sent_to_admin && ! in_array( $email_id, [ 'customer_processing_order', 'customer_on_hold_order' ], true ) ) {
        return;
    }

    if ( $sent_to_admin ) {
        $url   = admin_url( 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' );
        $label = __( 'Open order in admin', 'bw' );
        $wrap_style = 'margin:30px 0 0; padding-bottom:30px; text-align:center;';
    } else {
        $url   = wc_get_page_permalink( 'myaccount' );
        $label = __( 'Go to your account', 'bw' );
        $wrap_style = 'margin:24px 0 10px; text-align:center;';
    }

    if ( ! $url ) {
        return;
    }

    echo '<p style="' . esc_attr( $wrap_style ) . '">';
    echo '<a class="bw-email-cta" href="' . esc_url( $url ) . '" style="background:#79ff00;color:#111111;border:1px solid #111111;border-radius:999px;display:inline-block;padding:16px 30px;font-size:20px;line-height:1.2;font-weight:500;text-decoration:none;">' . esc_html( $label ) . '</a>';
    echo '</p>';
}
