<?php
/**
 * Checkout fields admin settings for Blackwork.
 *
 * Settings are stored in a single option: bw_checkout_fields_settings.
 * Structure:
 * {
 *   version: 1,
 *   billing: { billing_first_name: { enabled, priority, width, label, required }, ... },
 *   shipping: { ... },
 *   order: { ... },
 *   account: { ... },
 *   section_headings: { free_order_message: "...", free_order_button_text: "..." }
 * }
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Checkout_Fields_Admin {
    const OPTION_NAME = 'bw_checkout_fields_settings';
    const OPTION_VERSION = 1;

    /**
     * @var BW_Checkout_Fields_Admin|null
     */
    private static $instance = null;

    /**
     * Initialize the admin module.
     */
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get instance.
     *
     * @return BW_Checkout_Fields_Admin
     */
    public static function get_instance() {
        return self::init();
    }

    private function __construct() {
        add_action( 'admin_init', [ $this, 'handle_post' ] );
    }

    /**
     * Handle settings save/reset for checkout fields.
     */
    public function handle_post() {
        error_log( '[BW Checkout Fields] handle_post called' );

        if ( empty( $_POST['bw_checkout_fields_submit'] ) && empty( $_POST['bw_checkout_fields_reset'] ) ) {
            error_log( '[BW Checkout Fields] No submit button found in POST' );
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( 'bw_checkout_fields_save', 'bw_checkout_fields_nonce' );

        $redirect_args = [
            'page'         => 'blackwork-site-settings',
            'tab'          => 'checkout',
            'checkout_tab' => 'fields',
        ];

        if ( ! empty( $_POST['bw_checkout_fields_reset'] ) ) {
            update_option( self::OPTION_NAME, [ 'version' => self::OPTION_VERSION ] );
            $redirect_args['bw_checkout_fields_reset'] = '1';
            wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
            exit;
        }

        $defaults = $this->get_checkout_fields();
        $raw      = isset( $_POST['bw_checkout_fields'] ) ? wp_unslash( $_POST['bw_checkout_fields'] ) : [];

        $warnings = false;
        $settings = [
            'version' => self::OPTION_VERSION,
        ];

        // Save section_headings settings
        $section_headings_raw = isset( $_POST['bw_section_headings'] ) ? wp_unslash( $_POST['bw_section_headings'] ) : [];

        // Debug logging
        error_log( '[BW Checkout Fields] POST data received: ' . print_r( $_POST['bw_section_headings'], true ) );

        $settings['section_headings'] = [
            'free_order_message'     => isset( $section_headings_raw['free_order_message'] )
                ? sanitize_textarea_field( $section_headings_raw['free_order_message'] )
                : '',
            'free_order_button_text' => isset( $section_headings_raw['free_order_button_text'] )
                ? sanitize_text_field( $section_headings_raw['free_order_button_text'] )
                : '',
        ];

        // Debug logging
        error_log( '[BW Checkout Fields] Saving section_headings: ' . print_r( $settings['section_headings'], true ) );

        foreach ( $defaults as $section => $fields ) {
            foreach ( $fields as $key => $field ) {
                $posted   = isset( $raw[ $section ][ $key ] ) ? (array) $raw[ $section ][ $key ] : [];
                $enabled  = ! empty( $posted['enabled'] );
                $required = ! empty( $posted['required'] );

                if ( ! $enabled && $required ) {
                    $required = false;
                    $warnings = true;
                }

                $priority = isset( $posted['priority'] ) ? absint( $posted['priority'] ) : 0;
                if ( 0 === $priority && isset( $field['priority'] ) ) {
                    $priority = absint( $field['priority'] );
                }

                $width = isset( $posted['width'] ) ? sanitize_key( $posted['width'] ) : $this->infer_field_width( $field );
                if ( ! in_array( $width, [ 'half', 'full' ], true ) ) {
                    $width = $this->infer_field_width( $field );
                }

                $label = isset( $posted['label'] ) ? sanitize_text_field( $posted['label'] ) : '';

                $settings[ $section ][ $key ] = [
                    'enabled'  => (bool) $enabled,
                    'priority' => $priority,
                    'width'    => $width,
                    'label'    => $label,
                    'required' => (bool) $required,
                ];
            }
        }

        update_option( self::OPTION_NAME, $settings );

        // Debug: verify the save
        $saved_data = get_option( self::OPTION_NAME );
        error_log( '[BW Checkout Fields] Saved to database: ' . print_r( $saved_data['section_headings'], true ) );

        $redirect_args['bw_checkout_fields_saved'] = '1';
        if ( $warnings ) {
            $redirect_args['bw_checkout_fields_warning'] = '1';
        }

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Render the Checkout Fields tab.
     */
    public function render_tab() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $fields   = $this->get_checkout_fields();
        $settings = $this->get_settings();

        if ( isset( $_GET['bw_checkout_fields_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_checkout_fields_saved'] ) ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Checkout fields saved.', 'bw' ) . '</strong></p></div>';
        }

        if ( isset( $_GET['bw_checkout_fields_reset'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_checkout_fields_reset'] ) ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Checkout fields reset to defaults.', 'bw' ) . '</strong></p></div>';
        }

        if ( isset( $_GET['bw_checkout_fields_warning'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_checkout_fields_warning'] ) ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Disabled required fields were marked optional to avoid validation errors.', 'bw' ) . '</strong></p></div>';
        }

        if ( $this->is_block_checkout() ) {
            echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Checkout Fields targets the classic checkout form. The Checkout Block is detected and will not be affected.', 'bw' ) . '</strong></p></div>';
        }

        if ( empty( $fields ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'WooCommerce checkout fields are unavailable. Ensure WooCommerce is active.', 'bw' ) . '</p></div>';
            return;
        }

        $sections = [
            'billing'  => __( 'Billing', 'bw' ),
            'shipping' => __( 'Shipping', 'bw' ),
            'order'    => __( 'Order', 'bw' ),
            'account'  => __( 'Account', 'bw' ),
        ];
        ?>

        <p class="description">
            <?php esc_html_e( 'Reorder, hide, or resize classic checkout fields. Changes apply only on the checkout form and keep WooCommerce validation intact.', 'bw' ); ?>
        </p>

        <input type="hidden" name="bw_checkout_fields_form" value="1" />
        <?php wp_nonce_field( 'bw_checkout_fields_save', 'bw_checkout_fields_nonce' ); ?>

        <?php
        // Get section_headings settings
        $section_headings = isset( $settings['section_headings'] ) ? $settings['section_headings'] : [];
        $free_order_message = isset( $section_headings['free_order_message'] ) && '' !== $section_headings['free_order_message']
            ? $section_headings['free_order_message']
            : __( 'Your order is free. Complete your details and click Place order.', 'bw' );
        $free_order_button_text = isset( $section_headings['free_order_button_text'] ) && '' !== $section_headings['free_order_button_text']
            ? $section_headings['free_order_button_text']
            : __( 'Confirm free order', 'bw' );
        ?>

        <h3><?php esc_html_e( 'Section Headings', 'bw' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_free_order_message"><?php esc_html_e( 'Free Order Message', 'bw' ); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_free_order_message"
                        name="bw_section_headings[free_order_message]"
                        rows="3"
                        class="large-text"
                        placeholder="<?php esc_attr_e( 'Your order is free. Complete your details and click Place order.', 'bw' ); ?>"
                    ><?php echo esc_textarea( $free_order_message ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'Shown when order total becomes 0 (e.g., after applying a 100% discount coupon). Stripe express buttons and divider will be hidden.', 'bw' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_free_order_button_text"><?php esc_html_e( 'Free Order Button Text', 'bw' ); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        id="bw_free_order_button_text"
                        name="bw_section_headings[free_order_button_text]"
                        value="<?php echo esc_attr( $free_order_button_text ); ?>"
                        class="regular-text"
                        placeholder="<?php esc_attr_e( 'Confirm free order', 'bw' ); ?>"
                    />
                    <p class="description">
                        <?php esc_html_e( 'Text for the Place Order button when order total is 0. Original button text is restored when total becomes greater than 0.', 'bw' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php foreach ( $sections as $section_key => $section_label ) : ?>
            <?php if ( empty( $fields[ $section_key ] ) ) : ?>
                <?php continue; ?>
            <?php endif; ?>

            <h3><?php echo esc_html( $section_label ); ?></h3>
            <table class="widefat striped bw-checkout-fields-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Enabled', 'bw' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Field Key', 'bw' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Label', 'bw' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Required', 'bw' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Priority', 'bw' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Column Layout', 'bw' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $fields[ $section_key ] as $field_key => $field ) : ?>
                        <?php
                        $config   = $this->get_field_config( $settings, $section_key, $field_key, $field );
                        $label    = $config['label'];
                        $priority = $config['priority'];
                        $width    = $config['width'];
                        $enabled  = $config['enabled'];
                        $required = $config['required'];
                        ?>
                        <tr>
                            <td>
                                <label class="screen-reader-text" for="bw-field-enabled-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>">
                                    <?php esc_html_e( 'Enable field', 'bw' ); ?>
                                </label>
                                <input type="checkbox"
                                    id="bw-field-enabled-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>"
                                    name="bw_checkout_fields[<?php echo esc_attr( $section_key ); ?>][<?php echo esc_attr( $field_key ); ?>][enabled]"
                                    value="1" <?php checked( $enabled ); ?> />
                            </td>
                            <td>
                                <code><?php echo esc_html( $field_key ); ?></code>
                            </td>
                            <td>
                                <label class="screen-reader-text" for="bw-field-label-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>">
                                    <?php esc_html_e( 'Custom label', 'bw' ); ?>
                                </label>
                                <input type="text"
                                    id="bw-field-label-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>"
                                    name="bw_checkout_fields[<?php echo esc_attr( $section_key ); ?>][<?php echo esc_attr( $field_key ); ?>][label]"
                                    value="<?php echo esc_attr( $label ); ?>"
                                    placeholder="<?php echo esc_attr( $this->get_field_label( $field ) ); ?>"
                                    class="regular-text" />
                            </td>
                            <td>
                                <label class="screen-reader-text" for="bw-field-required-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>">
                                    <?php esc_html_e( 'Required', 'bw' ); ?>
                                </label>
                                <input type="checkbox"
                                    id="bw-field-required-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>"
                                    name="bw_checkout_fields[<?php echo esc_attr( $section_key ); ?>][<?php echo esc_attr( $field_key ); ?>][required]"
                                    value="1" <?php checked( $required ); ?> />
                            </td>
                            <td>
                                <label class="screen-reader-text" for="bw-field-priority-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>">
                                    <?php esc_html_e( 'Priority', 'bw' ); ?>
                                </label>
                                <input type="number"
                                    id="bw-field-priority-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>"
                                    name="bw_checkout_fields[<?php echo esc_attr( $section_key ); ?>][<?php echo esc_attr( $field_key ); ?>][priority]"
                                    value="<?php echo esc_attr( $priority ); ?>"
                                    min="0" step="1" />
                            </td>
                            <td>
                                <label class="screen-reader-text" for="bw-field-width-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>">
                                    <?php esc_html_e( 'Column layout', 'bw' ); ?>
                                </label>
                                <select id="bw-field-width-<?php echo esc_attr( $section_key . '-' . $field_key ); ?>"
                                    name="bw_checkout_fields[<?php echo esc_attr( $section_key ); ?>][<?php echo esc_attr( $field_key ); ?>][width]">
                                    <option value="full" <?php selected( $width, 'full' ); ?>><?php esc_html_e( 'Full width (1 col)', 'bw' ); ?></option>
                                    <option value="half" <?php selected( $width, 'half' ); ?>><?php esc_html_e( 'Half width (2 col)', 'bw' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <div class="bw-checkout-fields-actions">
            <?php submit_button( __( 'Save Checkout Fields', 'bw' ), 'primary', 'bw_checkout_fields_submit', false ); ?>
            <?php submit_button( __( 'Reset to defaults', 'bw' ), 'secondary', 'bw_checkout_fields_reset', false ); ?>
        </div>
        <?php
    }

    /**
     * Get saved settings with defaults.
     *
     * @return array
     */
    private function get_settings() {
        $settings = get_option( self::OPTION_NAME, [ 'version' => self::OPTION_VERSION ] );
        if ( ! is_array( $settings ) ) {
            $settings = [ 'version' => self::OPTION_VERSION ];
        }

        return $settings;
    }

    /**
     * Retrieve checkout fields from WooCommerce.
     *
     * @return array
     */
    private function get_checkout_fields() {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
            return [];
        }

        $checkout = WC()->checkout();
        if ( ! $checkout ) {
            return [];
        }

        return (array) $checkout->get_checkout_fields();
    }

    /**
     * Determine default field width from WooCommerce classes.
     *
     * @param array $field Field data.
     *
     * @return string
     */
    private function infer_field_width( $field ) {
        $classes = isset( $field['class'] ) ? (array) $field['class'] : [];
        if ( in_array( 'form-row-first', $classes, true ) || in_array( 'form-row-last', $classes, true ) ) {
            return 'half';
        }

        return 'full';
    }

    /**
     * Get field label safely.
     *
     * @param array $field Field data.
     *
     * @return string
     */
    private function get_field_label( $field ) {
        return isset( $field['label'] ) ? (string) $field['label'] : '';
    }

    /**
     * Get field config by merging saved settings with defaults.
     *
     * @param array  $settings Saved settings.
     * @param string $section  Section key.
     * @param string $key      Field key.
     * @param array  $field    Field data.
     *
     * @return array
     */
    private function get_field_config( $settings, $section, $key, $field ) {
        $default = [
            'enabled'  => true,
            'priority' => isset( $field['priority'] ) ? absint( $field['priority'] ) : 0,
            'width'    => $this->infer_field_width( $field ),
            'label'    => '',
            'required' => ! empty( $field['required'] ),
        ];

        if ( isset( $settings[ $section ][ $key ] ) && is_array( $settings[ $section ][ $key ] ) ) {
            $saved = $settings[ $section ][ $key ];

            return [
                'enabled'  => isset( $saved['enabled'] ) ? (bool) $saved['enabled'] : $default['enabled'],
                'priority' => isset( $saved['priority'] ) ? absint( $saved['priority'] ) : $default['priority'],
                'width'    => isset( $saved['width'] ) ? sanitize_key( $saved['width'] ) : $default['width'],
                'label'    => isset( $saved['label'] ) ? (string) $saved['label'] : $default['label'],
                'required' => isset( $saved['required'] ) ? (bool) $saved['required'] : $default['required'],
            ];
        }

        return $default;
    }

    /**
     * Detect if the checkout page uses the WooCommerce Checkout block.
     *
     * @return bool
     */
    private function is_block_checkout() {
        if ( ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'has_block' ) ) {
            return false;
        }

        $checkout_page_id = wc_get_page_id( 'checkout' );
        if ( ! $checkout_page_id ) {
            return false;
        }

        $post = get_post( $checkout_page_id );
        if ( ! $post ) {
            return false;
        }

        return has_block( 'woocommerce/checkout', $post->post_content );
    }

    /**
     * Get free order message from settings.
     *
     * @return string
     */
    public static function get_free_order_message() {
        $settings = get_option( self::OPTION_NAME, [ 'version' => self::OPTION_VERSION ] );
        if ( ! is_array( $settings ) || empty( $settings['section_headings']['free_order_message'] ) ) {
            return __( 'Your order is free. Complete your details and click Place order.', 'bw' );
        }

        return $settings['section_headings']['free_order_message'];
    }

    /**
     * Get free order button text from settings.
     *
     * @return string
     */
    public static function get_free_order_button_text() {
        $settings = get_option( self::OPTION_NAME, [ 'version' => self::OPTION_VERSION ] );
        if ( ! is_array( $settings ) || empty( $settings['section_headings']['free_order_button_text'] ) ) {
            return __( 'Confirm free order', 'bw' );
        }

        return $settings['section_headings']['free_order_button_text'];
    }
}
