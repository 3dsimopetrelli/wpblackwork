<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BW_Elementor_Widgets {
	public function __construct() {
        add_action( 'elementor/widgets/register', array( $this, 'include_widgets' ) );
    	// Add categories
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );


        add_action( 'wp', [ $this, 'register_scripts_frontend' ] );
        
		// Register widget styles
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
        
		// Register styles to admin for icons
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_editor_styles' ] );
    }



	// Register styles to admin for icons
	public function enqueue_editor_styles() {
		wp_enqueue_style( 'sas-el-widget-font-icons', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/el-widget-icons.css');
		

	}
		

	public function elementor_fix_notice() {
		if (function_exists('WC')) {
			remove_action('woocommerce_cart_is_empty', 'woocommerce_output_all_notices', 5);
			remove_action('woocommerce_shortcode_before_product_cat_loop', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_account_content', 'woocommerce_output_all_notices', 10);
			remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
		}
    }
    
	
    
    public function widget_styles() {
		wp_enqueue_style( 'sas-base', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/base-style.css', __FILE__ );
		
		// Content
		wp_enqueue_style( 'sas-sales-table', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/sales-table-style.css', __FILE__ );
		wp_enqueue_style( 'sas-text-big-title', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/text-big-title.css', __FILE__ );
		
		wp_enqueue_style( 'sas-box-category', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/box-category.css', __FILE__ );
		wp_enqueue_style( 'sas-tags', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/sparrow-tags.css', __FILE__ );

		wp_enqueue_style( 'sas-productslider-style', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/products-slider.css', __FILE__ );

		wp_enqueue_style( 'sas-postslider-style', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/postslider-style.css', __FILE__ );
		
		wp_enqueue_style( 'sas-rotellina', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/rotellina.css', __FILE__ );
		
		wp_enqueue_style( 'sas-fixed-price-bar-style', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/fixed-price-bar.css', __FILE__ );

		wp_enqueue_style( 'sas-mobile-menu', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/mobile-menu.css', __FILE__ );

		wp_enqueue_style( 'sas-search', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/sas-search.css', __FILE__ );

		wp_enqueue_style( 'sas-slick-style', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/css/slick.css', __FILE__ );
		
    }




	function register_scripts_frontend() {
		$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
        $api_settings    = get_option( 'sas' );
        
    // Header widget  
        wp_register_script('sas-header-user', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/user.js', array('jquery'), BW_PLUGIN_VERSION, true);
        wp_register_script('smartmenu', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/smartmenu.js', array(), BW_PLUGIN_VERSION, true);
		

    // Content
		wp_register_script('isotope', trailingslashit(BW_PLUGIN_URL) . 'assets/js/isotope.js', array(),'',true);
		wp_register_script('sas-productslider', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/products-slider.js', array('jquery'), BW_PLUGIN_VERSION, true);
	    wp_register_script('sas-woo-products', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/woo-products.js', array('jquery'), BW_PLUGIN_VERSION, true);

	    wp_register_script('sas-mobile-menu-js', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/mobile-menu.js', array('jquery'), BW_PLUGIN_VERSION, true);

   
    // Grid isotope layout
	    wp_register_script('sas-products', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/products.js', array('jquery'), BW_PLUGIN_VERSION, true);
	    wp_register_script('sas-blog-grid', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/blog-grid.js', array('jquery'), BW_PLUGIN_VERSION, true);
	    wp_register_script('sas-fixed-price-bar', trailingslashit(BW_PLUGIN_URL) . 'widgets/assets/js/fixed-price-bar.js', array('jquery'), BW_PLUGIN_VERSION, true);
        
		wp_localize_script( 'sas-blog-grid', 'ajax_object', array('ajaxurl' => admin_url( 'admin-ajax.php' )));
        
    }

    /**
     * @param $widgets_manager Elementor\Widgets_Manager
     */
    public function include_widgets($widgets_manager) {
        $files = glob(trailingslashit(BW_PLUGIN_PATH) . '/widgets/header/*.php');
        foreach ($files as $file){
            if(file_exists($file)){
                require_once  $file;
            }
        }

        $files = glob(trailingslashit(BW_PLUGIN_PATH) . '/widgets/content/*.php');
        foreach ($files as $file){
            if(file_exists($file)){
                require_once  $file;
            }
        }
    }
    
    public function add_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'sas-header',
			[
				'title' => __( 'BW Header', 'sas' ),
            ]);
        $elements_manager->add_category(    
			'sas-content',
			[
				'title' => __( 'BW Theme', 'sas' ),
            ]);
        $elements_manager->add_category(
			'sas-footer',
			[
				'title' => __( 'BW Footer', 'sas' ),
			]);
	}
}

new BW_Elementor_Widgets();

