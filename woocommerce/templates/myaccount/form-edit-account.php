<?php
/**
 * Edit account form
 *
 * @package WooCommerce/Templates
 * @version 7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$customer_id = get_current_user_id();
$customer    = wp_get_current_user();
$countries   = new WC_Countries();
$address     = $countries->get_address_fields( get_user_meta( $customer_id, 'billing_country', true ), 'billing_' );

/**
 * Hook: woocommerce_before_edit_account_form.
 */
do_action( 'woocommerce_before_edit_account_form' );
?>
<div class="bw-settings">
    <header class="bw-page-header">
        <h2><?php esc_html_e( 'Settings', 'bw' ); ?></h2>
        <div class="bw-tab-switcher" role="tablist">
            <button class="bw-tab is-active" type="button" data-target="#bw-tab-profile" aria-selected="true"><?php esc_html_e( 'Profile', 'bw' ); ?></button>
            <button class="bw-tab" type="button" data-target="#bw-tab-billing" aria-selected="false"><?php esc_html_e( 'Billing & Payments', 'bw' ); ?></button>
        </div>
    </header>

    <div class="bw-tab-panels">
        <div class="bw-tab-panel is-active" id="bw-tab-profile">
            <section class="bw-settings-block">
                <h3><?php esc_html_e( 'Social accounts', 'bw' ); ?></h3>
                <div class="bw-social-row">
                    <span class="bw-social-label">Facebook</span>
                    <button class="bw-social-action" type="button"><?php esc_html_e( 'Link account with Facebook', 'bw' ); ?></button>
                </div>
                <div class="bw-social-row">
                    <span class="bw-social-label">Google</span>
                    <button class="bw-social-action" type="button"><?php esc_html_e( 'Unlink account from Google', 'bw' ); ?></button>
                </div>
            </section>

            <form class="woocommerce-EditAccountForm edit-account" action="" method="post">
                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Personal information', 'bw' ); ?></h3>
                    <div class="bw-grid">
                        <div class="bw-field">
                            <label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $customer->first_name ); ?>" />
                        </div>
                        <div class="bw-field">
                            <label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $customer->last_name ); ?>" />
                        </div>
                        <div class="bw-field">
                            <label for="account_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $customer->display_name ); ?>" />
                            <p class="form-row form-row-wide">
                                <span class="description"><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ); ?></span>
                            </p>
                        </div>
                        <div class="bw-field">
                            <label for="account_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="email" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $customer->user_email ); ?>" />
                        </div>
                    </div>
                </section>

                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Password change', 'woocommerce' ); ?></h3>
                    <div class="bw-grid">
                        <div class="bw-field">
                            <label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
                            <input type="password" name="password_current" id="password_current" autocomplete="off" />
                        </div>
                        <div class="bw-field">
                            <label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
                            <input type="password" name="password_1" id="password_1" autocomplete="off" />
                        </div>
                        <div class="bw-field">
                            <label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
                            <input type="password" name="password_2" id="password_2" autocomplete="off" />
                        </div>
                    </div>
                </section>

                <?php do_action( 'woocommerce_edit_account_form' ); ?>

                <p>
                    <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
                    <button type="submit" class="button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save Changes', 'woocommerce' ); ?></button>
                    <input type="hidden" name="action" value="save_account_details" />
                </p>

                <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
            </form>
        </div>

        <div class="bw-tab-panel" id="bw-tab-billing">
            <form class="woocommerce-EditAddressForm edit-address" action="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>" method="post">
                <?php do_action( 'woocommerce_before_edit_account_address_form' ); ?>
                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></h3>
                    <div class="bw-grid">
                        <?php foreach ( $address as $key => $field ) :
                            $value = get_user_meta( $customer_id, $key, true );
                            ?>
                            <div class="bw-field">
                                <?php woocommerce_form_field( $key, $field, $value ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                <input type="hidden" name="action" value="edit_address" />
                <input type="hidden" name="address" value="billing" />

                <p>
                    <button type="submit" class="button" name="save_address" value="<?php esc_attr_e( 'Save address', 'woocommerce' ); ?>"><?php esc_html_e( 'Save Address', 'woocommerce' ); ?></button>
                </p>

                <?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Hook: woocommerce_after_edit_account_form.
 */
do_action( 'woocommerce_after_edit_account_form' );
