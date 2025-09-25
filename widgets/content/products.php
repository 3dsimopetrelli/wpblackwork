<?php
namespace BW\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography as Scheme_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Woo_Products extends Widget_Base {

	public function get_name() {
		return 'sas-products';
	}
	// TITLE
	public function get_title() {
		return __( 'Products', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-elproducts';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content' ];
	}
	// KEY
	public function get_keywords() {
		return [ 'product', 'woocommerce' ];
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-base' ];
	}
	//JS
        public function get_script_depends() {
                return [ 'isotope', 'sas-woo-products' ];
        }
	
	
	// CONTROLS **********
	protected function register_controls() {

		$this->start_controls_section(
			'section_woocommerce_layout',
			[
				'label' => esc_html__( 'Layout', 'sas' ),
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => esc_html__( 'Columns', 'sas' ),
				'type'           => Controls_Manager::SELECT,
				'devices'        => [ 'desktop', 'tablet', 'mobile' ],
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
			]
		);

		$this->add_responsive_control(
			'item_gap',
			[
				'label'   => esc_html__( 'Column Gap', 'sas' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 30,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-grid_gap.sas-grid' => 'margin-left: -{{SIZE}}px',
					'{{WRAPPER}} .sas-products_container .sas-grid_gap.sas-grid > *' => 'padding-left: {{SIZE}}px',
				],
				'render_type' => 'template',
			]
		);

		$this->add_responsive_control(
			'row_gap',
			[
				'label'   => esc_html__( 'Row Gap', 'sas' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 30,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-wc-products-wrapper.sas-grid'     => 'margin-top: -{{SIZE}}px',
					'{{WRAPPER}} .sas-products_container .sas-wc-products-wrapper.sas-grid > *' => 'margin-top: {{SIZE}}px',
				],
				'render_type' => 'template',
			]
		);
		
		$this->add_control(
			'title_alignment',
			[
				'label'   => esc_html__( 'Title Alignment', 'sas' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'sas' ),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'sas' ),
						'icon'  => 'fa fa-align-center',
					],
				
				],
                'prefix_class' => 'elementor-product-text__align-',

				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-woo_product_title_wrap,
					{{WRAPPER}} .product_after_shop_loop' => 'text-align: {{VALUE}}',
					
					
					'{{WRAPPER}} .sas-products_container .sas-wc-product .star-rating
					' => 'text-align: {{VALUE}}; display: inline-block !important',
				],
			]
		);
				
		$this->add_control(
			'price_alignment',
			[
				'label'   => esc_html__( 'Price Alignment', 'sas' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'options' => [
					'0' => [
						'title' => esc_html__( 'Left', 'sas' ),
						'icon'  => 'fa fa-align-left',
					],
					'auto' => [
						'title' => esc_html__( 'Center', 'sas' ),
						'icon'  => 'fa fa-align-center',
					],
				],

				'selectors' => [
					'{{WRAPPER}} .sas-wc-products-wrapper.products article .product_after_shop_loop_buttons,
					{{WRAPPER}} .sas-wc-products-wrapper.products article .product_after_shop_loop_price' => 'margin: 0 {{VALUE}}',
					
					
				],
			]
		);
		

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'product_image',
				'label'     => esc_html__( 'Image Size', 'sas' ),
				'exclude'   => [ 'custom' ],
				'default'   => 'medium',
			]
		);
		
/*
		$this->add_control(
			'show_filter_bar',
			[
				'label' => esc_html__( 'Show Filter', 'sas' ),
				'type'  => Controls_Manager::SWITCHER,
				'separator' => 'before',
			]
		);
*/
		
	

		$this->end_controls_section();





		// QUERY ****************************************************

		$this->start_controls_section(
			'section_content_query',
			[
				'label' => esc_html__( 'Query', 'sas' ),
			]
		);

		$this->add_control(
			'source',
			[
				'label'   => _x( 'Source', 'Posts Query Control', 'sas' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''        => esc_html__( 'Show All', 'sas' ),
					'by_name' => esc_html__( 'Manual Selection', 'sas' ),
				],
				'label_block' => true,
			]
		);


		$product_categories = get_terms( 'product_cat' );

		$options = [];
		foreach ( $product_categories as $category ) {
			$options[ $category->slug ] = $category->name;
		}

		$this->add_control(
			'product_categories',
			[
				'label'       => esc_html__( 'Categories', 'sas' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $options,
				'default'     => [],
				'label_block' => true,
				'multiple'    => true,
				'condition'   => [
					'source'    => 'by_name',
				],
			]
		);

		$this->add_control(
			'exclude_products',
			[
				'label'       => esc_html__( 'Exclude Product(s)', 'sas' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder'     => 'product_id',
				'label_block' => true,
				'description' => __( 'Write product id here, if you want to exclude multiple products so use comma as separator. Such as 1 , 2', '' ),
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => esc_html__( 'Product Limit', 'sas' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 8,
			]
		);

		$this->add_control(
			'meta_key',
			[
				'label'   => esc_html__( 'Meta Key', 'sas' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'total_sales',
				'options' => [
					'total_sales'    => esc_html__( 'Total Sales', 'sas' ),
					'_regular_price' => esc_html__( 'Regular Price', 'sas' ),
					'_sale_price'    => esc_html__( 'Sale Price', 'sas' ),
				],
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Order by', 'sas' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'     => esc_html__( 'Date', 'sas' ),
					'title'    => esc_html__( 'Title', 'sas' ),
					'category' => esc_html__( 'Category', 'sas' ),
					'rand'     => esc_html__( 'Random', 'sas' ),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => esc_html__( 'Order', 'sas' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC' => esc_html__( 'Descending', 'sas' ),
					'ASC'  => esc_html__( 'Ascending', 'sas' ),
				],
			]
		);

		$this->end_controls_section();


		//ADDITIONAL ****************************************************

		$this->start_controls_section(
			'section_woocommerce_additional',
			[
				'label' => esc_html__( 'Additional', 'sas' ),
			]
		);
	
			
			$this->add_control(
				'show_title',
				[
					'label'   => esc_html__( 'Title', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
	
			$this->add_control(
				'show_rating',
				[
					'label'   => esc_html__( 'Rating', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			
			$this->add_control(
				'show_price_cart',
				[
					'label'   => esc_html__( 'Price & Cart', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			
			if (class_exists('YITH_WCWL_Wishlist')) {
				$this->add_control(
					'show_wishlist',
					[
						'label'   => esc_html__( 'Wishlist', 'sas' ),
						'type'    => Controls_Manager::SWITCHER,
						'default' => 'yes',
					]
				);
			}
	
			
	
		$this->end_controls_section();




		// STYLE
		//*****************************************
		
		// IMAGE
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'image_border',
				'label'    => esc_html__( 'Image Border', 'sas' ),
				'selector' => '{{WRAPPER}} .sas-products_container .product_thumbnail_wrapper img',
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .product_thumbnail_wrapper img, {{WRAPPER}} .product_thumbnail.with_second_image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'    => 'image_shadow',
				'exclude' => [
					'shadow_position',
				],
				'selector' => '{{WRAPPER}} .sas-products_container .product_thumbnail_wrapper img',
			]
		);

		$this->end_controls_section();

		// HOVER IMAGE
		$this->start_controls_section(
			'section_style_hover_image',
			[
				'label' => esc_html__( 'Image Hover', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'hover_image_mask',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'
					{{WRAPPER}} .sas-products_container .tile:hover .box_mask,
					{{WRAPPER}} .sas-products_container .sas-wc-product-image a:hover .box_mask

					
					
					
					' => 'background-color: {{VALUE}};',
				],
			]
		);

	

		$this->end_controls_section();


		// TITLE
		$this->start_controls_section(
			'section_style_title',
			[
				'label'     => esc_html__( 'Title', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-products_container a h2.woocommerce-loop-product__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hover_title_color',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-products_container a h2.woocommerce-loop-product__title:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .sas-products_container a h2.woocommerce-loop-product__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .sas-products_container a h2.woocommerce-loop-product__title',
			]
		);

		$this->end_controls_section();


		// RATING
		$this->start_controls_section(
			'section_style_rating',
			[
				'label'     => esc_html__( 'Rating', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_rating' => 'yes',
				],
			]
		);

		$this->add_control(
			'rating_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e7e7e7',
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .star-rating:before' => 'color: {{VALUE}};',
				]
			]
		);

		$this->add_control(
			'active_rating_color',
			[
				'label'     => esc_html__( 'Active Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFCC00',
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .star-rating span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_margin',
			[
				'label'      => esc_html__( 'Margin', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .sas-products_container .sas-cont_rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
				
		$this->add_responsive_control(
			'rating_size',
			[
				'label'   => esc_html__( 'Size', 'sas' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 15,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .star-rating'     => 'font-size: {{SIZE}}px',
				],
				//'render_type' => 'template',
			]
		);


		$this->end_controls_section();


		// PRICE
		$this->start_controls_section(
			'section_style_price',
			[
				'label'     => esc_html__( 'Price', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_price_cart' => 'yes',
				],
			]
		);

		$this->add_control(
			'old_price_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .product_after_shop_loop_price del' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'old_price_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .sas-products_container .product_after_shop_loop_price del',
			]
		);

		$this->add_control(
			'sale_price_heading',
			[
				'label'     => esc_html__( 'Sale Price', 'sas' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sale_price_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-wc-product-price, {{WRAPPER}} .sas-products_container .product_after_shop_loop_price ins' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sale_price_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .sas-products_container .product_after_shop_loop_price, {{WRAPPER}} .sas-products_container .product_after_shop_loop_price ins',
			]
		);

		$this->end_controls_section();

		// CART
		$this->start_controls_section(
			'section_style_cart',
			[
				'label'     => esc_html__( 'Cart', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_price_cart' => 'yes',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_cart_style' );

		$this->start_controls_tab(
			'tab_cart_normal',
			[
				'label' => esc_html__( 'Normal', 'sas' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-wc-products-wrapper article .product_after_shop_loop_buttons a' => 'color: {{VALUE}};',
				],
			]
		);

	

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'cart_typography',
				'label'     => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector'  => '{{WRAPPER}} .sas-products_container .sas-wc-products-wrapper article .product_after_shop_loop_buttons a',
				'separator' => 'before',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => esc_html__( 'Hover', 'sas' ),
			]
		);

		$this->add_control(
			'hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-products_container .sas-wc-products-wrapper article .product_after_shop_loop_buttons a:hover' => 'color: {{VALUE}};',
				],
			]
		);
	
		$this->end_controls_tab();

		$this->end_controls_tabs();

		//end
		$this->end_controls_section();
	}
	
	

// HTML
//*****************************************
public function render_header() {

$settings = $this->get_settings();

$this->add_render_attribute(
	'products-container',
	[
		'id' => $this->get_id(),
		'class' => 'sas-products_container'
	]
	);

?>

<!-- ***** Start widget Grid Porduct ****** -->
<div <?php echo $this->get_render_attribute_string( 'products-container' ); ?>>
	
<?php 	

}
	



	
	// QUERY
	public function render_query() {
		$settings = $this->get_settings();

		if ( get_query_var('paged') ) { $paged = get_query_var('paged'); } 
		elseif ( get_query_var('page') ) { $paged = get_query_var('page'); } 
		else { $paged = 1; }

		$exclude_products = ($settings['exclude_products']) ? explode(',', $settings['exclude_products']) : [];

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $settings['posts_per_page'],
			'meta_key'            => $settings['meta_key'],
			'orderby'             => $settings['orderby'],
			'order'               => $settings['order'],
			'paged'               => $paged,
			'post__not_in'        => $exclude_products,
		);

		if ( 'by_name' === $settings['source'] and !empty($settings['product_categories']) ) {			  
			$args['tax_query'][] = array(
				'taxonomy'           => 'product_cat',
				'field'              => 'slug',
				'terms'              => $settings['product_categories'],
				'post__not_in'       => $exclude_products,
			);
		}

		$wp_query = new \WP_Query($args);

		return $wp_query;
	}

	//loop
	public function render_loop_item() {
		$settings = $this->get_settings();
		$id       = 'sas-wc-product-' . $this->get_id();

		$wp_query = $this->render_query();

		if($wp_query->have_posts()) {

		$this->add_render_attribute('wc-products-wrapper');

			$this->add_render_attribute(
				[
					'wc-products-wrapper' => [
						'class' => [
							'sas-wc-products-wrapper',
							'products',
							'sas-grid_gap', //style column gap and row gap
							'sas-grid',
							'sas-grid-medium',
							'sas-child-width-1-'. $settings['columns_mobile'],
							'sas-child-width-1-'. $settings['columns_tablet'] .'@s',
							'sas-child-width-1-'. $settings['columns'] .'@m',
						],
						'id' => esc_attr( $id )
					],
				]
			);

			?>
			
			
			<!-- ***** TOTAL WRAP ****** -->
			<div <?php echo $this->get_render_attribute_string( 'wc-products-wrapper' ); ?>>
			<?php			

			
			$this->add_render_attribute( 'wc-product', 'class', 'sas-wc-product' );

			//have_post
			while ( $wp_query->have_posts() ) : $wp_query->the_post(); ?>
				
			<?php global $product; ?>
		
			<article <?php wc_product_class( '', $product ); ?>>
			<?php

					$img_1 = get_field('preview_gif_image_1');
					$img_2 = get_field('preview_gif_image_2');
					$gif = get_field('gif');

					$link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
					$attachment_ids = $product->get_gallery_image_ids();
					if (!empty($attachment_ids)) {
						$image_link = wp_get_attachment_url( $attachment_ids[0], $settings['product_image_size'] );
					}

					$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), $settings['product_image_size']);
					
					if (!$image) {
						$image[0] = wc_placeholder_img_src( 'woocommerce_thumbnail' );
						$image_link = wc_placeholder_img_src( 'woocommerce_thumbnail' );
					}
					
					echo "<div class='product_thumbnail_wrapper'>";
					echo "<div class='product_thumbnail ";
					if (!empty($attachment_ids)) {
						echo "with_second_image second_image_loaded";
					}
					echo "' style='background-size: cover;'>";
					echo '<a href="' . esc_url( $link ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
					if (!empty($attachment_ids)) {
						echo "<span class='product_thumbnail_background' style='background-image:url($image_link); background-position: center;'></span>";
					}
					
					if ($gif == 'on') {
						echo '<div class="product_acf_gif_img_animation">';
						echo "	<div class='product_acf_gif_img_animation-inner'>" . wp_get_attachment_image($img_2, 'full') . "</div>";
						echo "	<div class='product_acf_gif_img_animation-inner'>" . wp_get_attachment_image($img_1, 'full') . "</div>";
						echo '</div>';
					} else {
						echo '<div class="product_acf_gif_img">';
						echo "	<div class='product_acf_gif_img-inner'>" . wp_get_attachment_image($img_2, 'full') . "</div>";
						echo "	<div class='product_acf_gif_img-inner'>" . wp_get_attachment_image($img_1, 'full') . "</div>";
						echo '</div>';
					}

					if ( !$product->is_in_stock() ) {
						echo '<div class="out_of_stock_badge_loop">' . esc_html__( 'Out of stock', 'sas' ) . '</div>';
					}

					$newness_days = 30;
					$created = strtotime( $product->get_date_created() );
					if ( ( time() - ( 60 * 60 * 24 * $newness_days ) ) < $created ) {
						echo '<span class="itsnew onsale">' . esc_html__( 'New!', 'sas' ) . '</span>';
					}
            
					echo "</div>";
					
					echo "<div class='sas-woo_product_title_wrap'>\n";
					
					if ('yes' == $settings['show_title']) {
						echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . get_the_title() . '</h2>';
					}
					
					if (class_exists('YITH_WCWL_Wishlist')) {
						if ('yes' == $settings['show_wishlist']) {
							echo do_shortcode('[yith_wcwl_add_to_wishlist]'); 
						}
					}

					echo "</div>\n";

					$terms = get_the_terms( $product->get_id(), 'product_cat' ) ? get_the_terms( $product->get_id(), 'product_cat' ) : get_the_terms( $product->get_parent_id(), 'product_cat' );
					$product_cat_id = "";

					if($terms) {
						foreach ($terms as $term) {
							if($product_cat_id == "") {
								$product_cat_id .= $term->name;
							} else {
								$product_cat_id .= ', ' . $term->name;
							}   
						}
					}
					
					if ('yes' == $settings['show_rating']) {
					echo "<div class='sas-cont_rating'>\n";
						wc_get_template( 'loop/rating.php' );
						echo "</div>";
					}
					
					$args['class'] = 'button product_type_simple add_to_cart_button ajax_add_to_cart';
					$args['attributes'] = array(
						'data-quantity' => 1,
						'data-product_id' => $product->get_id(),
						'data-product_sku' => $product->get_sku(),
					);
					$args['data-image'] = wp_get_attachment_image_src($product->get_image_id()) ? wp_get_attachment_image_src($product->get_image_id())[0] : wc_placeholder_img_src(); 
					$args['data-name'] = $product->get_name();

					if ('yes' == $settings['show_price_cart']) {
						echo "<div class='product_after_shop_loop'>\n";
						echo "	<div class='product_after_shop_loop_switcher'>\n";
						echo "		<div class='product_after_shop_loop_price'>\n";
						echo $product->get_price_html();
						echo "		</div>\n";
						echo "		<div class='product_after_shop_loop_buttons'>\n";
						echo $add_to_cart_html = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s data-image="%s" data-name="%s">%s</a>',
							esc_url( $product->add_to_cart_url() ),
							esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
							esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
							isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
							isset( $args['data-image'] ) ? $args['data-image'] : '',
							isset( $args['data-name'] ) ? $args['data-name'] : '',
							esc_html( $product->add_to_cart_text() )
						);
						echo "		</div>\n";
						echo "	</div>\n";
						echo "</div>\n";
					}?>

					<p class="ajax_shop_cat"><?php echo $product_cat_id; //CATEGORIA?></p>
					
					<?php
					echo '</a>';
					echo "</div>";
					
					echo "<div class='clear'></div>";
				?>
			</article>
								
				
			<?php endwhile;	?>
			</div> <!-- .sas-products_container -->
			<?php



			wp_reset_postdata();
			
		} else {
			echo '<div class="sas-alert-warning" sas-alert>' . esc_html__( 'Ops! There is no product.', 'sas' ) .'<div>';
		}

	}

	public function render() {
		$this->render_header();
		$this->render_loop_item();
	}
		
}
if (class_exists('woocommerce')) {
	$widgets_manager->register(new BW_Woo_Products());
}