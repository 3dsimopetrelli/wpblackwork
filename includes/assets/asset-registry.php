<?php
/**
 * Widget / shared asset registration and enqueue.
 *
 * Registers and conditionally enqueues CSS/JS for the Elementor widgets and a
 * few shared assets (embla, full-bleed, editor panel, smart header, WC loader
 * overrides). All asset handles, dependencies, versions (filemtime), localize
 * payloads and hook priorities are preserved exactly.
 *
 * Extracted verbatim from blackwork-core-plugin.php (Phase 1 bootstrap
 * decomposition, BW-TASK-20260623). Function names and hook registrations are
 * preserved unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bw_register_embla_assets() {
	$embla_core_file = __DIR__ . '/assets/js/vendor/embla-carousel.umd.js';
	$embla_core_ver  = file_exists( $embla_core_file ) ? filemtime( $embla_core_file ) : '8.6.0';

	wp_register_script(
		'embla-js',
		plugin_dir_url( __FILE__ ) . 'assets/js/vendor/embla-carousel.umd.js',
		array(),
		$embla_core_ver,
		true
	);

	$embla_autoplay_file = __DIR__ . '/assets/js/vendor/embla-carousel-autoplay.umd.js';
	$embla_autoplay_ver  = file_exists( $embla_autoplay_file ) ? filemtime( $embla_autoplay_file ) : '8.1.7';

	wp_register_script(
		'embla-autoplay-js',
		plugin_dir_url( __FILE__ ) . 'assets/js/vendor/embla-carousel-autoplay.umd.js',
		array( 'embla-js' ),
		$embla_autoplay_ver,
		true
	);

	$bw_embla_core_css_file = __DIR__ . '/assets/css/bw-embla-core.css';
	$bw_embla_core_css_ver  = file_exists( $bw_embla_core_css_file ) ? filemtime( $bw_embla_core_css_file ) : '1.0.0';

	wp_register_style(
		'bw-embla-core-css',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-embla-core.css',
		array(),
		$bw_embla_core_css_ver
	);

	$bw_embla_core_js_file = __DIR__ . '/assets/js/bw-embla-core.js';
	$bw_embla_core_js_ver  = file_exists( $bw_embla_core_js_file ) ? filemtime( $bw_embla_core_js_file ) : '1.0.0';

	wp_register_script(
		'bw-embla-core-js',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-embla-core.js',
		array( 'embla-js', 'embla-autoplay-js' ),
		$bw_embla_core_js_ver,
		true
	);
}
add_action( 'init', 'bw_register_embla_assets' );

function bw_register_fullbleed_style() {
	$bw_custom_class_css_file = __DIR__ . '/assets/css/bw-custom-class.css';
	$custom_class_version     = file_exists( $bw_custom_class_css_file ) ? filemtime( $bw_custom_class_css_file ) : '1.0.0';

	wp_register_style(
		'bw-fullbleed-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-custom-class.css',
		array(),
		$custom_class_version
	);
}
add_action( 'init', 'bw_register_fullbleed_style' );

function bw_enqueue_elementor_widget_panel_assets() {
	$panel_css_file    = __DIR__ . '/assets/css/bw-elementor-widget-panel.css';
	$panel_css_version = file_exists( $panel_css_file ) ? filemtime( $panel_css_file ) : '1.0.0';

	wp_enqueue_style(
		'bw-elementor-widget-panel-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-elementor-widget-panel.css',
		array(),
		$panel_css_version
	);

	$panel_js_file    = __DIR__ . '/assets/js/bw-elementor-widget-panel.js';
	$panel_js_version = file_exists( $panel_js_file ) ? filemtime( $panel_js_file ) : '1.0.0';

	wp_enqueue_script(
		'bw-elementor-widget-panel-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-elementor-widget-panel.js',
		array( 'jquery' ),
		$panel_js_version,
		true
	);

	wp_localize_script(
		'bw-elementor-widget-panel-script',
		'bwElementorWidgetPanelData',
		array(
			'productGridDesktopFilterGroupsByContext' => bw_get_product_grid_desktop_filter_groups_by_context(),
			'productCategoryContextByTermId'          => bw_get_product_category_context_map_for_editor(),
		)
	);
}
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_elementor_widget_panel_assets' );

function bw_register_divider_style() {
	$css_file = __DIR__ . '/assets/css/bw-divider.css';
	$version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-divider-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-divider.css',
		array(),
		$version
	);
}

function bw_enqueue_custom_class_assets() {
	wp_enqueue_style( 'bw-fullbleed-style' );
}

function bw_register_button_widget_assets() {
	bw_register_widget_assets( 'button' );
}

function bw_register_big_text_widget_assets() {
	bw_register_widget_assets( 'big-text', array(), false );
}

function bw_register_go_to_app_widget_assets() {
	bw_register_widget_assets( 'go-to-app', array(), false );
}

function bw_footer_template_contains_widget_slug( $widget_slug ) {
	if ( ! function_exists( 'bw_tbl_get_runtime_footer_template_id' ) ) {
		return false;
	}

	$template_id = absint( bw_tbl_get_runtime_footer_template_id() );
	if ( $template_id <= 0 ) {
		return false;
	}

	$widget_slug = sanitize_key( (string) $widget_slug );
	if ( '' === $widget_slug ) {
		return false;
	}

	$elementor_data = get_post_meta( $template_id, '_elementor_data', true );
	if ( is_string( $elementor_data ) && false !== strpos( $elementor_data, $widget_slug ) ) {
		return true;
	}

	if ( is_array( $elementor_data ) ) {
		$encoded = wp_json_encode( $elementor_data );
		if ( is_string( $encoded ) && false !== strpos( $encoded, $widget_slug ) ) {
			return true;
		}
	}

	$post_content = get_post_field( 'post_content', $template_id );

	return is_string( $post_content ) && false !== strpos( $post_content, $widget_slug );
}

function bw_maybe_enqueue_go_to_app_widget_runtime_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( ! bw_footer_template_contains_widget_slug( 'bw-go-to-app' ) ) {
		return;
	}

	if ( ! wp_style_is( 'bw-go-to-app-style', 'registered' ) ) {
		bw_register_go_to_app_widget_assets();
	}

	wp_enqueue_style( 'bw-go-to-app-style' );
}
add_action( 'wp_enqueue_scripts', 'bw_maybe_enqueue_go_to_app_widget_runtime_assets', 30 );

function bw_register_about_menu_widget_assets() {
	bw_register_widget_assets( 'about-menu', array() );
}

function bw_register_wallpost_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-wallpost.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-wallpost-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-wallpost.css',
		array(),
		$css_version
	);
}

function bw_register_related_products_widget_assets() {
	$product_labels_css_file    = __DIR__ . '/assets/css/bw-product-labels.css';
	$product_labels_css_version = file_exists( $product_labels_css_file ) ? filemtime( $product_labels_css_file ) : '1.0.0';

	wp_register_style(
		'bw-product-labels-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-product-labels.css',
		array(),
		$product_labels_css_version
	);

	// Register product card CSS (shared)
	$product_card_css_file    = __DIR__ . '/assets/css/bw-product-card.css';
	$product_card_css_version = file_exists( $product_card_css_file ) ? filemtime( $product_card_css_file ) : '1.0.0';
	$product_card_js_file     = __DIR__ . '/assets/js/bw-product-card.js';
	$product_card_js_version  = file_exists( $product_card_js_file ) ? filemtime( $product_card_js_file ) : '1.0.0';

	wp_register_style(
		'bw-product-card-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-product-card.css',
		array( 'bw-product-labels-style' ),
		$product_card_css_version
	);

	wp_register_script(
		'bw-product-card-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-product-card.js',
		array(),
		$product_card_js_version,
		true
	);

	// Register related products widget CSS
	$css_file    = __DIR__ . '/assets/css/bw-related-products.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-related-products-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-related-products.css',
		array( 'bw-wallpost-style', 'bw-product-card-style' ),
		$css_version
	);
}

function bw_enqueue_related_products_widget_assets() {
	if ( ! wp_style_is( 'bw-product-card-style', 'registered' ) || ! wp_style_is( 'bw-related-products-style', 'registered' ) ) {
		bw_register_related_products_widget_assets();
	}

	// Enqueue product card CSS first (dependency)
	if ( wp_style_is( 'bw-product-card-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-product-card-style' );
	}

	// Then enqueue related products CSS
	if ( wp_style_is( 'bw-related-products-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-related-products-style' );
	}
}

function bw_enqueue_about_menu_widget_assets() {
	if ( ! wp_style_is( 'bw-about-menu-style', 'registered' ) || ! wp_script_is( 'bw-about-menu-script', 'registered' ) ) {
		bw_register_about_menu_widget_assets();
	}

	if ( wp_style_is( 'bw-about-menu-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-about-menu-style' );
	}

	if ( wp_script_is( 'bw-about-menu-script', 'registered' ) ) {
		wp_enqueue_script( 'bw-about-menu-script' );
	}
}

function bw_register_product_grid_widget_assets() {
	static $product_grid_assets_localized = false;

	$css_file    = __DIR__ . '/assets/css/bw-product-grid.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-product-grid-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-product-grid.css',
		array( 'bw-wallpost-style', 'bw-product-card-style' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-product-grid.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-product-grid-js',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-product-grid.js',
		array( 'jquery', 'imagesloaded', 'masonry' ),
		$js_version,
		true
	);

	// Localize once to avoid duplicate globals/nonces across multi-hook registration.
	if ( ! $product_grid_assets_localized ) {
		wp_localize_script(
			'bw-product-grid-js',
			'bwProductGridAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bw_fpw_nonce' ),
			)
		);
		$product_grid_assets_localized = true;
	}
}

function bw_enqueue_product_grid_widget_assets() {
	if ( ! wp_style_is( 'bw-product-grid-style', 'registered' ) || ! wp_script_is( 'bw-product-grid-js', 'registered' ) ) {
		bw_register_product_grid_widget_assets();
	}

	if ( wp_style_is( 'bw-product-grid-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-product-grid-style' );
	}

	if ( wp_script_is( 'bw-product-grid-js', 'registered' ) ) {
		wp_enqueue_script( 'bw-product-grid-js' );
	}
}

function bw_enqueue_smart_header_assets() {
	// Checks if the new custom header is enabled to avoid conflicts.
	// The new header uses 'includes/modules/header/assets/js/header-init.js' which now includes Dark Zone logic.
	if ( function_exists( 'bw_header_is_enabled' ) && bw_header_is_enabled() ) {
		return;
	}

	// Non caricare nell'admin di WordPress
	if ( is_admin() ) {
		return;
	}

	// Non caricare nell'editor di Elementor
	if ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		return;
	}

	// Registra e carica CSS
	$css_file    = __DIR__ . '/assets/css/bw-smart-header.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '2.0.0';

	wp_enqueue_style(
		'bw-smart-header-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-smart-header.css',
		array(),
		$css_version
	);

	// Registra e carica JavaScript
	$js_file    = __DIR__ . '/assets/js/bw-smart-header.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '2.0.0';

	wp_enqueue_script(
		'bw-smart-header-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-smart-header.js',
		array( 'jquery' ),
		$js_version,
		true // Carica nel footer
	);

	// Passa configurazione al JavaScript tramite wp_localize_script
	wp_localize_script(
		'bw-smart-header-script',
		'bwSmartHeaderConfig',
		array(
			'scrollDownThreshold' => 100,  // Pixel prima di nascondere header (scroll giù)
			'scrollUpThreshold'   => 0,    // IMMEDIATO (anche 1px verso l'alto)
			'scrollDelta'         => 1,    // Sensibilità rilevamento scroll
			'blurThreshold'       => 50,   // Pixel prima di attivare blur
			'throttleDelay'       => 16,   // ~60fps
			'headerSelector'      => '.smart-header',
			'debug'               => false, // Imposta true per debug in console
		)
	);
}

/**
 * Lightweight WooCommerce Loader Overrides
 */
function bw_enqueue_wc_loader_overrides() {
	if ( get_option( 'bw_loading_global_spinner_hidden', 1 ) ) {
		$custom_css = '
            /* Hide default WooCommerce spinner and masks */
            .blockUI.blockOverlay {
                display: none !important;
                background: transparent !important;
                opacity: 0 !important;
            }
            .blockUI.blockMsg.blockElement,
            .woocommerce .loader,
            .woocommerce .blockUI.blockMsg::before {
                display: none !important;
                opacity: 0 !important;
            }
        ';
		wp_add_inline_style( 'woocommerce-general', $custom_css );
	}
}
add_action( 'wp_enqueue_scripts', 'bw_enqueue_wc_loader_overrides', 20 );

function bw_register_animated_banner_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-animated-banner.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-animated-banner-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-animated-banner.css',
		array(),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-animated-banner.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-animated-banner-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-animated-banner.js',
		array( 'jquery' ),
		$js_version,
		true
	);
}

function bw_enqueue_animated_banner_widget_assets() {
	if ( ! wp_style_is( 'bw-animated-banner-style', 'registered' ) || ! wp_script_is( 'bw-animated-banner-script', 'registered' ) ) {
		bw_register_animated_banner_widget_assets();
	}

	if ( wp_style_is( 'bw-animated-banner-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-animated-banner-style' );
	}

	if ( wp_script_is( 'bw-animated-banner-script', 'registered' ) ) {
		wp_enqueue_script( 'bw-animated-banner-script' );
	}
}

function bw_register_static_showcase_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-static-showcase.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-static-showcase-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-static-showcase.css',
		array(),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-static-showcase.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-static-showcase-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-static-showcase.js',
		array(),
		$js_version,
		true
	);
}

function bw_register_psychadelic_banner_widget_assets() {
	bw_register_widget_assets( 'psychadelic-banner', array(), false );
}

function bw_register_price_variation_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-price-variation.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-price-variation-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-price-variation.css',
		array(),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-price-variation.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-price-variation-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-price-variation.js',
		array( 'jquery' ),
		$js_version,
		true
	);
}

function bw_register_trust_box_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-trust-box.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-trust-box-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-trust-box.css',
		array( 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-trust-box.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-trust-box-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-trust-box.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_register_reviews_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-reviews.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-reviews-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-reviews.css',
		array(),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-reviews.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-reviews-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-reviews.js',
		array( 'jquery' ),
		$js_version,
		true
	);
}

/**
 * Localize bw-price-variation-script on any page where the widget may appear.
 */
function bw_localize_price_variation_widget_assets() {
	if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
		return;
	}

	wp_localize_script(
		'bw-price-variation-script',
		'bwPriceVariation',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'bw_price_variation_nonce' ),
			'priceFormat' => array(
				'symbol'             => html_entity_decode( get_woocommerce_currency_symbol() ),
				'decimals'           => wc_get_price_decimals(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'format'             => html_entity_decode( get_woocommerce_price_format() ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bw_localize_price_variation_widget_assets', 15 );

function bw_register_product_details_widget_assets() {
	bw_register_widget_assets( 'product-details', array( 'jquery' ) );
}

function bw_register_presentation_slide_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-presentation-slide.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-presentation-slide-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-presentation-slide.css',
		array( 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-presentation-slide.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-presentation-slide-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-presentation-slide.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_enqueue_presentation_slide_widget_assets() {
	if ( ! wp_style_is( 'bw-presentation-slide-style', 'registered' ) || ! wp_script_is( 'bw-presentation-slide-script', 'registered' ) ) {
		bw_register_presentation_slide_widget_assets();
	}

	if ( wp_style_is( 'bw-presentation-slide-style', 'registered' ) ) {
		wp_enqueue_style( 'bw-presentation-slide-style' );
	}

	if ( wp_script_is( 'bw-presentation-slide-script', 'registered' ) ) {
		wp_enqueue_script( 'bw-presentation-slide-script' );
	}
}

function bw_register_basic_slide_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-basic-slide.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-basic-slide-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-basic-slide.css',
		array( 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-basic-slide.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-basic-slide-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-basic-slide.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_register_product_slider_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-product-slider.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-product-slider-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-product-slider.css',
		array( 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-product-slider.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-product-slider-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-product-slider.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_register_showcase_slide_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-showcase-slide.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-showcase-slide-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-showcase-slide.css',
		array( 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-showcase-slide.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-showcase-slide-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-showcase-slide.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_register_mosaic_slider_widget_assets() {
	$css_file    = __DIR__ . '/assets/css/bw-mosaic-slider.css';
	$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

	wp_register_style(
		'bw-mosaic-slider-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/bw-mosaic-slider.css',
		array( 'bw-product-card-style', 'bw-embla-core-css' ),
		$css_version
	);

	$js_file    = __DIR__ . '/assets/js/bw-mosaic-slider.js';
	$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

	wp_register_script(
		'bw-mosaic-slider-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/bw-mosaic-slider.js',
		array( 'jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js' ),
		$js_version,
		true
	);
}

function bw_register_hero_slide_widget_assets() {
	bw_register_widget_assets( 'hero-slide', array( 'jquery' ), true );
}

add_action( 'init', 'bw_register_divider_style' );
add_action( 'init', 'bw_register_button_widget_assets' );
add_action( 'init', 'bw_register_about_menu_widget_assets' );
add_action( 'init', 'bw_register_wallpost_widget_assets' );
// about-menu: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_about_menu_widget_assets' );
// bw-custom-class.css (full-section + layout utility classes)
add_action( 'elementor/frontend/after_enqueue_styles', 'bw_enqueue_custom_class_assets' );
add_action( 'elementor/editor/after_enqueue_styles', 'bw_enqueue_custom_class_assets' );
add_action( 'wp_enqueue_scripts', 'bw_enqueue_custom_class_assets', 35 );
add_action( 'init', 'bw_register_product_grid_widget_assets' );
// product-grid: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_product_grid_widget_assets' );
add_action( 'init', 'bw_register_animated_banner_widget_assets' );
add_action( 'init', 'bw_register_psychadelic_banner_widget_assets' );
// animated-banner: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets' );
add_action( 'wp_enqueue_scripts', 'bw_enqueue_smart_header_assets' );
add_action( 'init', 'bw_register_static_showcase_widget_assets' );
add_action( 'init', 'bw_register_related_products_widget_assets' );
// related-products: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_related_products_widget_assets' );
add_action( 'init', 'bw_register_price_variation_widget_assets' );
add_action( 'init', 'bw_register_trust_box_widget_assets' );
add_action( 'init', 'bw_register_presentation_slide_widget_assets' );
add_action( 'init', 'bw_register_basic_slide_widget_assets' );
add_action( 'init', 'bw_register_product_slider_widget_assets' );
add_action( 'init', 'bw_register_showcase_slide_widget_assets' );
add_action( 'init', 'bw_register_mosaic_slider_widget_assets' );
add_action( 'init', 'bw_register_hero_slide_widget_assets' );
add_action( 'init', 'bw_register_product_details_widget_assets' );
add_action( 'init', 'bw_register_reviews_widget_assets' );
