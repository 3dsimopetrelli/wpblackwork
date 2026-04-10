<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_get_active_elementor_search_results_kit_id() {
    if (
        ! class_exists( '\Elementor\Plugin' ) ||
        ! isset( \Elementor\Plugin::$instance->kits_manager ) ||
        ! method_exists( \Elementor\Plugin::$instance->kits_manager, 'get_kit_for_frontend' )
    ) {
        return 0;
    }

    $kit = \Elementor\Plugin::$instance->kits_manager->get_kit_for_frontend();

    if ( ! $kit || ! method_exists( $kit, 'get_main_id' ) ) {
        return 0;
    }

    return absint( $kit->get_main_id() );
}

function bw_ss_maybe_enqueue_elementor_search_results_kit_css( $kit_id ) {
    $kit_id = absint( $kit_id );

    if ( $kit_id <= 0 ) {
        return;
    }

    $kit_style_handle = 'elementor-post-' . $kit_id;

    if ( wp_style_is( $kit_style_handle, 'enqueued' ) ) {
        return;
    }

    if ( wp_style_is( $kit_style_handle, 'registered' ) ) {
        wp_enqueue_style( $kit_style_handle );
        return;
    }

    if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
        $kit_css = new \Elementor\Core\Files\CSS\Post( $kit_id );
        $kit_css->enqueue();
    }
}

function bw_ss_maybe_enqueue_search_results_assets() {
    if ( ! bw_ss_is_search_results_request() ) {
        return;
    }

    // The virtual /search/ route bypasses the normal template_include + builder-content
    // flow, so we need to explicitly enqueue both Elementor frontend base styles and
    // the active kit CSS that defines the global typography/color variables used by the page.
    if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
        $elementor = \Elementor\Plugin::$instance;
        $kit_id    = bw_ss_get_active_elementor_search_results_kit_id();

        $elementor->frontend->enqueue_styles();

        if ( isset( $elementor->kits_manager ) && method_exists( $elementor->kits_manager, 'frontend_before_enqueue_styles' ) ) {
            $elementor->kits_manager->frontend_before_enqueue_styles();
        }

        if ( wp_style_is( 'elementor-frontend', 'registered' ) ) {
            wp_enqueue_style( 'elementor-frontend' );
        }

        // The /search/ page does not pass through Elementor's normal template flow, so
        // keep the active kit handle explicit and verifiable here in case future runtime
        // changes stop enqueueing it indirectly.
        bw_ss_maybe_enqueue_elementor_search_results_kit_css( $kit_id );
    }

    if ( function_exists( 'bw_enqueue_product_grid_widget_assets' ) ) {
        bw_enqueue_product_grid_widget_assets();
    }

    // Keep the /search/ page-specific shell reset CSS separate from the main
    // Search Surface stylesheet to avoid handle collisions with the overlay asset.
    $css_file = BW_MEW_PATH . 'assets/css/bw-search-surface.css';
    $css_url  = BW_MEW_URL . 'assets/css/bw-search-surface.css';
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    wp_enqueue_style( 'bw-search-results-page-style', $css_url, [ 'bw-product-grid-style' ], $version );
}

function bw_ss_disable_canonical_redirect_for_results_route( $redirect_url, $requested_url ) {
    if ( bw_ss_is_search_results_request() ) {
        return false;
    }

    return $redirect_url;
}

function bw_ss_filter_search_results_document_title( $title ) {
    if ( ! bw_ss_is_search_results_request() ) {
        return $title;
    }

    return bw_ss_build_search_results_title( bw_ss_build_search_results_state_from_url() );
}

function bw_ss_get_elementor_search_results_body_classes() {
    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return [];
    }

    $classes = [ 'elementor-default' ];
    $kit_id  = bw_ss_get_active_elementor_search_results_kit_id();

    if ( $kit_id > 0 ) {
        $classes[] = 'elementor-kit-' . $kit_id;
    }

    return array_values( array_unique( array_filter( $classes ) ) );
}

function bw_ss_filter_search_results_body_class( $classes ) {
    if ( ! bw_ss_is_search_results_request() ) {
        return $classes;
    }

    $classes   = is_array( $classes ) ? $classes : [];
    $classes[] = 'bw-search-results-page';
    $classes[] = 'bw-search-results-page--plugin-owned';
    $classes   = array_merge( $classes, bw_ss_get_elementor_search_results_body_classes() );

    return array_values( array_unique( $classes ) );
}

function bw_ss_get_search_results_render_settings() {
    return bw_ss_normalize_headless_product_grid_settings(
        [
            'widget_id'            => 'bw-search-results-grid',
            'post_type'            => 'product',
            'show_description'     => false,
            'show_search'          => false,
            'show_order_by'        => true,
            'show_desktop_filter_icon' => false,
            'order_trigger_style'  => 'dropdown',
            'desktop_filter_groups'=> [ 'types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years' ],
            'desktop_filter_order' => [ 'types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years' ],
            'search_placeholder'   => __( 'Search in products...', 'bw-elementor-widgets' ),
        ]
    );
}

function bw_ss_render_search_results_page() {
    global $wp_query;

    $state         = bw_ss_build_search_results_state_from_url();
    $render_result = bw_ss_render_headless_product_grid(
        [
            'state'    => $state,
            'settings' => bw_ss_get_search_results_render_settings(),
        ]
    );

    if ( $wp_query instanceof WP_Query ) {
        $wp_query->is_404    = false;
        $wp_query->is_search = false;
        $wp_query->is_page   = true;
    }

    status_header( 200 );
    get_header();
    ?>
    <main id="primary" class="site-main bw-tbl-runtime-template bw-search-results-page__main">
        <div class="bw-tbl-runtime-template-content bw-search-results-page__container">
            <?php echo $render_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </main>
    <?php
    get_footer();
}

function bw_ss_maybe_render_search_results_page() {
    if ( ! bw_ss_is_search_results_request() ) {
        return;
    }

    bw_ss_render_search_results_page();
    exit;
}
