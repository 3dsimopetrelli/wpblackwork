<?php
/**
 * Checkout fields frontend handler for Blackwork.
 *
 * Hooks used:
 * - woocommerce_checkout_fields: adjust priorities, required flags, labels, and visibility.
 * - wp_enqueue_scripts: load checkout field layout styles.
 * - body_class: add a scoped class when settings are active.
 *
 * To add new field mappings later, ensure the field exists in WC()->checkout()->get_checkout_fields(),
 * then it will automatically appear in the admin table and be processed here.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Checkout_Fields_Frontend {
    const OPTION_NAME = 'bw_checkout_fields_settings';
    const OPTION_VERSION = 1;

    /**
     * Initialize the frontend module.
     */
    public static function init() {
        $instance = new self();
        return $instance;
    }

    private function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'apply_checkout_fields' ], 20, 1 );
        add_filter( 'woocommerce_checkout_fields', [ $this, 'inject_address_heading' ], 40, 1 );
        add_filter( 'woocommerce_form_field', [ $this, 'render_heading_field' ], 10, 4 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 25 );
        add_filter( 'body_class', [ $this, 'add_body_class' ] );
    }

    /**
     * Apply configured checkout field settings.
     *
     * @param array $fields Checkout fields.
     *
     * @return array
     */
    public function apply_checkout_fields( $fields ) {
        if ( ! $this->should_apply() ) {
            return $fields;
        }

        $settings = $this->get_settings();
        if ( empty( $settings ) ) {
            return $fields;
        }

        foreach ( $settings as $section => $section_fields ) {
            if ( 'version' === $section || empty( $section_fields ) || empty( $fields[ $section ] ) ) {
                continue;
            }

            foreach ( $section_fields as $field_key => $config ) {
                if ( empty( $fields[ $section ][ $field_key ] ) ) {
                    continue;
                }

                if ( empty( $config['enabled'] ) ) {
                    unset( $fields[ $section ][ $field_key ] );
                    continue;
                }

                $field = $fields[ $section ][ $field_key ];

                if ( ! empty( $config['label'] ) ) {
                    $field['label'] = $config['label'];
                }

                if ( isset( $config['required'] ) ) {
                    $field['required'] = (bool) $config['required'];
                }

                if ( isset( $config['priority'] ) ) {
                    $field['priority'] = absint( $config['priority'] );
                }

                $field['class'] = $this->apply_width_class( isset( $field['class'] ) ? (array) $field['class'] : [], $config );

                $fields[ $section ][ $field_key ] = $field;
            }
        }

        return $fields;
    }

    /**
     * Enqueue checkout field layout styles.
     */
    public function enqueue_assets() {
        if ( ! $this->should_apply() ) {
            return;
        }

        $css_file = BW_MEW_PATH . 'assets/css/bw-checkout-fields.css';
        $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_enqueue_style(
            'bw-checkout-fields',
            BW_MEW_URL . 'assets/css/bw-checkout-fields.css',
            [ 'bw-checkout' ],
            $version
        );
    }

    /**
     * Add a body class when checkout field settings are active.
     *
     * @param array $classes Body classes.
     *
     * @return array
     */
    public function add_body_class( $classes ) {
        if ( $this->should_apply() ) {
            $classes[] = 'bw-checkout-fields-active';

            $section_headings = $this->get_section_headings();
            if ( ! empty( $section_headings['hide_billing_details'] ) ) {
                $classes[] = 'bw-hide-billing-heading';
            }
        }

        return $classes;
    }

    /**
     * Determine if settings should be applied.
     *
     * @return bool
     */
    private function should_apply() {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return false;
        }

        if ( ! function_exists( 'is_checkout' ) ) {
            return false;
        }

        if ( ! is_checkout() && ! ( defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) ) {
            return false;
        }

        if ( $this->is_block_checkout() ) {
            return false;
        }

        return $this->has_settings();
    }

    /**
     * Get checkout field settings.
     *
     * @return array
     */
    private function get_settings() {
        $settings = get_option( self::OPTION_NAME, [ 'version' => self::OPTION_VERSION ] );
        if ( ! is_array( $settings ) ) {
            return [];
        }

        return $settings;
    }

    /**
     * Get section heading settings.
     *
     * @return array
     */
    private function get_section_headings() {
        $settings = $this->get_settings();
        $defaults = [
            'hide_billing_details' => 0,
            'address_heading_text' => __( 'Delivery', 'bw' ),
        ];

        if ( isset( $settings['section_headings'] ) && is_array( $settings['section_headings'] ) ) {
            $merged = array_merge( $defaults, $settings['section_headings'] );
            $merged['hide_billing_details'] = ! empty( $merged['hide_billing_details'] ) ? 1 : 0;
            $merged['address_heading_text'] = sanitize_text_field( $merged['address_heading_text'] );

            if ( empty( $merged['address_heading_text'] ) ) {
                $merged['address_heading_text'] = $defaults['address_heading_text'];
            }

            return $merged;
        }

        return $defaults;
    }

    /**
     * Inject address heading field after the email/newsletter area.
     *
     * @param array $fields Checkout fields.
     *
     * @return array
     */
    public function inject_address_heading( $fields ) {
        if ( ! $this->should_apply() ) {
            return $fields;
        }

        if ( $this->is_block_checkout() ) {
            return $fields;
        }

        if ( empty( $fields['billing'] ) ) {
            return $fields;
        }

        if ( ! $this->has_address_fields( $fields['billing'] ) ) {
            return $fields;
        }

        $section_headings = $this->get_section_headings();
        $label            = $section_headings['address_heading_text'];

        if ( ! $this->cart_needs_shipping() && __( 'Delivery', 'bw' ) === $label ) {
            $label = __( 'Address', 'bw' );
        }

        $priority = isset( $fields['billing']['billing_email']['priority'] ) ? absint( $fields['billing']['billing_email']['priority'] ) + 7 : 117;
        if ( isset( $fields['billing']['bw_subscribe_newsletter']['priority'] ) ) {
            $priority = absint( $fields['billing']['bw_subscribe_newsletter']['priority'] ) + 2;
        }

        $fields['billing']['bw_checkout_address_heading'] = [
            'type'     => 'bw_heading',
            'label'    => $label,
            'required' => false,
            'priority' => $priority,
            'class'    => [ 'form-row-wide', 'bw-checkout-address-heading' ],
            'clear'    => true,
        ];

        return $fields;
    }

    /**
     * Render the custom heading field type.
     *
     * @param string $field  Field HTML.
     * @param string $key    Field key.
     * @param array  $args   Field args.
     * @param mixed  $value  Field value.
     *
     * @return string
     */
    public function render_heading_field( $field, $key, $args, $value ) {
        if ( 'bw_heading' !== $args['type'] ) {
            return $field;
        }

        $label = isset( $args['label'] ) ? $args['label'] : '';
        if ( '' === $label ) {
            return '';
        }

        return '<div class="bw-checkout-section-heading"><h2>' . esc_html( $label ) . '</h2></div>';
    }

    /**
     * Check if billing address fields are available.
     *
     * @param array $billing_fields Billing fields.
     *
     * @return bool
     */
    private function has_address_fields( $billing_fields ) {
        $keys = [
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_state',
            'billing_country',
        ];

        foreach ( $keys as $key ) {
            if ( isset( $billing_fields[ $key ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cart needs shipping.
     *
     * @return bool
     */
    private function cart_needs_shipping() {
        if ( function_exists( 'WC' ) && WC()->cart ) {
            return WC()->cart->needs_shipping();
        }

        return true;
    }

    /**
     * Determine if any configured field settings exist.
     *
     * @return bool
     */
    private function has_settings() {
        $settings = $this->get_settings();
        foreach ( $settings as $section => $section_fields ) {
            if ( 'version' === $section ) {
                continue;
            }

            if ( ! empty( $section_fields ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply column width classes to a field.
     *
     * @param array $classes Existing classes.
     * @param array $config  Field config.
     *
     * @return array
     */
    private function apply_width_class( $classes, $config ) {
        $classes = array_diff( $classes, [ 'form-row-first', 'form-row-last', 'form-row-wide' ] );
        $classes[] = 'bw-checkout-field';

        $width = isset( $config['width'] ) ? $config['width'] : 'full';
        if ( 'half' === $width ) {
            $classes[] = 'bw-checkout-field--half';
        } else {
            $classes[] = 'bw-checkout-field--full';
        }

        return array_values( array_unique( $classes ) );
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
}
