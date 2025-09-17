<?php
/*
 * Plugin Name: Sparrow Core
 * Author: Sparrow&Snow
 * Author URI: https://www.blackwork.com
 * Version: 1.0.0
 * Text Domain: sparrow
 * Description: Core plugin required for Sparow Theme.
 */

/*
 *
 * PLUGIN MAP
 * 
 * sas-plugin
 * ├── framework
 * |   ├── vendors
 * |   |   ├── one-click-demo-import -> ocdi main files
 *
 */

if ( ! defined( "ABSPATH" ) ) {
	die( "You shouldnt be here" );
}

define( 'SAS_PLUGIN_PLUGIN_FILE', __FILE__);
define( 'SAS_PBNAME', plugin_basename(SAS_PLUGIN_PLUGIN_FILE) );
define( 'SAS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define( 'SAS_PLUGIN_URL', plugins_url("/", __FILE__ ));
define( 'SAS_PLUGIN_VERSION', '1.0.0');

class SAS_Plugin {

    /**
	 * Plugin Version
	 * @var string The plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Minimum Elementor Version
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Minimum PHP Version
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

    /**
     * @var SAS_Plugin
     */
    private static $instance;

    public function init() {

		function custom_product_search_query($query) {
			// Check if this is a search query and if the 'product_search' parameter is set
			if ($query->is_search && !empty($_GET['search_type'])) {
				// Modify the search query to include product attributes like title, category, and tags
				$query->set('post_type', 'product');
				$query->set('s', sanitize_text_field($_GET['s']));
			} else if ( $query->is_search && empty($_GET['search_type']) ) {
				$query->set('post_type', 'post');
			}
		}
		add_action('pre_get_posts', 'custom_product_search_query');

		function cc_mime_types($mimes) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		}
		add_filter('upload_mimes', 'cc_mime_types');

		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

		require_once 'classes/post-type.php';
		require_once 'classes/sas-purchasecodes.php';
		require_once 'classes/wc-variations-radio-buttons.php';

		add_action( 'init', array( $this, 'register_portfolio' ) );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		if (did_action( 'elementor/loaded' )) {
			require_once( 'sas-el-widgets.php' );
			require_once( 'framework/helper.php' );
			require_once( 'framework/query_helper.php' );
		}

        add_action( 'wp_enqueue_scripts', [ $this,'scripts_enqueue' ] );
	}

	public function scripts_enqueue() {
		wp_enqueue_script('sas-slickslider', trailingslashit(SAS_PLUGIN_URL) . 'widgets/assets/js/slick.js', array('jquery'), SAS_PLUGIN_VERSION, false);
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( SAS_PBNAME === $plugin_file ) {
			$row_meta = [
				'demo' => '<a target="_blank" href="https://demo.sasground.net/sas/demo" aria-label="' . esc_attr( __( 'All Demos', 'sas' ) ) . '" target="_blank">' . __( 'View Demos', 'sas' ) . '</a>',
				
				'doc' => '<a target="_blank" href="https://sasground.net/documentation/sas/" aria-label="' . esc_attr( __( 'All Documentation', 'sas' ) ) . '" target="_blank">' . __( 'Documentation', 'sas' ) . '</a>',
				
				'support' => '<a target="_blank" href="#" aria-label="' . esc_attr( __( 'Go for Get Support', 'sas' ) ) . '" target="_blank">' . __( 'Get Support', 'sas' ) . '</a>',
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}
    
	public function __construct() {

        add_action( 'init', [ $this,'sas_create_builders' ] );
        add_action( 'plugins_loaded', array( $this, 'init' ) );

    }


    public function sas_create_builders() {
        load_plugin_textdomain( 'sas' );
        if ( $this->plugin_is_active( 'elementor' ) ){
			require_once 'classes/megamenu/megamenu.php';
			require_once 'classes/megamenu/walker.megamenu.php';
            $this->sas_add_elementor_cpt('megamenu_builder');
			$this->sas_add_elementor_cpt('sas-portfolio');
			$this->sas_remove_elementor_colors();
        }
    }

    public function plugin_is_active($plugin_var) {
        $return_var = in_array( $plugin_var. '/' .$plugin_var. '.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
        return $return_var;
    }

    public function sas_add_elementor_cpt( $cpt ) {
        $cpt_support = get_option( 'elementor_cpt_support' );
        if( ! $cpt_support ) {
            $cpt_support = [ 'page', 'post', $cpt ];
            update_option( 'elementor_cpt_support', $cpt_support );
        }
        else if( ! in_array( $cpt, $cpt_support ) ) {
            $cpt_support[] = $cpt;
            update_option( 'elementor_cpt_support', $cpt_support );
        }
	}
	
	public function sas_remove_elementor_colors() {
        $color_scheme = get_option( 'elementor_disable_color_schemes' );
        if( $color_scheme ) {
            update_option( 'elementor_disable_color_schemes', 'yes' );
        }
    }

    /*
    *   ELEMENTOR ADMIN NOTICES
    */

    public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'elementor-hello-world' ),
			'<strong>' . esc_html__( 'SAS Elementor Widgets', 'elementor-hello-world' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'elementor-hello-world' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-hello-world' ),
			'<strong>' . esc_html__( 'SAS Elementor Widgets', 'elementor-hello-world' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'elementor-hello-world' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-hello-world' ),
			'<strong>' . esc_html__( 'SAS Elementor Widgets', 'elementor-hello-world' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'elementor-hello-world' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

    public static function getInstance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SAS_Plugin ) ) {
			self::$instance = new SAS_Plugin();
		}
		return self::$instance;
    }
    
    
/*
*   ELEMENTOR POST TYPE PORTFOLIO
*/
		
public function register_portfolio() {
	$labels = array(
		'name' => __('Portfolio', 'sas'),
		'singular_name' => __('Portfolio', 'sas'),
		'add_new' => __('Add New Item', 'sas'),
		'add_new_item' => __('Add New Item', 'sas'),
		'edit_item' => __('Edit Portfolio', 'sas'),
		'new_item' => __('New Portfolio', 'sas'),
		'view_item' => __('View Portfolio', 'sas'),
		'search_items' => __('Search Portfolio', 'sas'),
		'not_found' =>  __('No Portfolio found', 'sas'),
		'not_found_in_trash' => __('No Portfolio found in Trash', 'sas'),
		'parent_item_colon' => ''
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => 'portfolio'),
		'capability_type' => 'post',
		'show_in_nav_menus' => true,
		'hierarchical' => false,
		'exclude_from_search' => false,
		'has_archive' => false,
		'menu_position' => 5,
		'menu_icon' => 'dashicons-images-alt',
		'show_in_rest' => true,
		'supports' => array('title','editor','thumbnail','excerpt','comments', 'custom-fields')
	);
	register_post_type('sas-portfolio',$args);


	// Add the posts and pages columns filter. They can both use the same function.
	add_filter('manage_posts_columns', 'sas_add_featured_column', 5);
	add_filter('manage_sparrow-portfolio_columns', 'sas_add_featured_column', 5);
	add_filter('manage_edit-product_columns', 'sas_unset_woo_column', 5);

	// Add the column
	function sas_add_featured_column($cols){
		$cols['tcb_post_thumb'] = __('Featured Image');
	return $cols;
	}

	// Remove the column
	function sas_unset_woo_column( $columns ) {
		unset($columns['tcb_post_thumb']);
		return $columns;
	}

	// Hook into the posts an pages column managing. Sharing function callback again.
	add_action('manage_posts_custom_column', 'sas_display_featured_thumbnail', 5, 2);
	add_action('manage_sparrow-portfolio_column', 'sas_display_featured_thumbnail', 5, 2);

	// Grab featured-thumbnail size post thumbnail and display it.
	function sas_display_featured_thumbnail($col, $id){
		switch($col){
			case 'tcb_post_thumb':
			if( function_exists('the_post_thumbnail') )
				echo the_post_thumbnail( 'thumbnail' );
			else
				echo 'Not supported in theme';
			break;
		}
	}

    register_taxonomy("sas-portfolio-categories", "sas-portfolio",
		array(
			"hierarchical" => true,
			"label" => __( "Portfolio Categories", 'sas'),
			"singular_label" => __( "Category", 'sas'),
			"rewrite" => array( 'slug' => 'sas-portfolio-categories', 'hierarchical' => true),
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			)
		);
	}


    
    
// class SAS_Plugin   
}



/*
*   ELEMENTOR DO SHORT CODE (contactform7)
*/

	
if (!function_exists('sas_do_shortcode')) {

    function sas_do_shortcode($tag, array $atts = array(), $content = null) {
        global $shortcode_tags;

        if (!isset($shortcode_tags[$tag])) {
            return false;
        }

        return call_user_func($shortcode_tags[$tag], $atts, $content, $tag);
    }
}

/*
*   HTML tags (For Fancy Heading)
*/
	
	function element__html_tags() {
    $title_tags = [
        'h1'   => esc_html__( 'H1', 'sas' ),
        'h2'   => esc_html__( 'H2', 'sas' ),
        'h3'   => esc_html__( 'H3', 'sas' ),
        'h4'   => esc_html__( 'H4', 'sas' ),
        'h5'   => esc_html__( 'H5', 'sas' ),
        'h6'   => esc_html__( 'H6', 'sas' ),
        'div'  => esc_html__( 'div', 'sas' ),
        'span' => esc_html__( 'span', 'sas' ),
        'p'    => esc_html__( 'p', 'sas' ),
    ];

    return $title_tags;
}
	

SAS_Plugin::getInstance();