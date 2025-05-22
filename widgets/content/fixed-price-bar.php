<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class SAS_Fixed_Price_Bar extends Widget_Base {

	public function get_name() {
		return 'sas-fixed-price-bar';
	}
	// TITLE
	public function get_title() {
		return __( 'Fixed Price Bar', 'sas' );
	}
	// ICON
	public function get_icon() {
		return '';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content' ];
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-fixed-price-bar-style'];
	}
	// JS
	public function get_script_depends() {
		return [ 'sas-fixed-price-bar' ];
	}


	// CONTROLS **********
	protected function register_controls() {

		$product = wc_get_product( get_the_ID() );

		if ( $product->is_type( 'variable' ) ) {
			$current_products = $product->get_children();

			$variations = array();

			if (!empty($current_products)) {
				foreach ($current_products as $value) {
					$single_variation = new \WC_Product_Variation($value);
					$variations[$value] = $single_variation->get_formatted_name();
				}
			}
			

			$this->start_controls_section(
				'section_content',
				[
					'label' => __( 'Settings', 'sas' ),
				]
			);
		
			$this->add_control(
				'price-bar-id',
				[
					'label'       => __( 'Product Variation', 'sas' ),
					'type'        => Controls_Manager::SELECT,
					'options'	  => $variations
				]
			);
				
			$this->end_controls_section();
		
		}	

			

		// STYLE =================================	
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Text Style', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		
					
			$this->add_control(
				'price-bar__color',
				[
					'label'     => __( 'Text Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} .sas-circle_text' => 'color: {{VALUE}};',
					],
				]
			);
			$this->add_group_control(
				Group_Control_Background::get_type(),
				[
					'name' => 'price-bar__background',
					'label' => __( 'Background', 'sas' ),
					'types' => [ 'classic','gradient' ],
				
					'selector' => '.elementor-element-{{ID}} .sas-circle_text',
					
				]
			);
			
					
			$this->add_control(
				'price-bar__padding',
				[
					'label'      => __( 'Bar Padding', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .sas-circle_text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
			
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Title Typography', 'sas' ),
					'name' => 'price-bar-title___typo',
					'selector' => '.elementor-element-{{ID}} .sas-circle_text',
				]
			);	
			
				$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Price Typography', 'sas' ),
					'name' => 'price-bar-price___typo',
					'selector' => '.elementor-element-{{ID}} .sas-circle_text',
				]
			);						
				
		//End				

		$this->end_controls_section();
	}


	// HTML
	//*****************************************
	protected function render() {
		$settings = $this->get_settings_for_display();
		$product = wc_get_product( get_the_ID() );
		$site_url = site_url();
		if ( $product->is_type( 'variable' ) ) {
			$price_bar_id = 0;
			if (!empty($settings['price-bar-id'])) {
				$price_bar_id = $settings['price-bar-id'];
			}
		} else {
			$price_bar_id = get_the_ID();
		}
		
		?>
	

	<!-- =======================================
		RELATED PRODUCTS
	======================================= -->


<div class="fixed_price_bar bar_fade elementor-section elementor-section-boxed">
	<div class="elementor-container">
		
		
			<div class="Grid Grid_price_bar flex_middle">
				<div class="Grid-cell">
						<h3><?php echo wc_get_product( $price_bar_id )->get_name(); ?></h3>
				</div> <!-- Grid-cell -->

				<div class="Grid-cell">
					<div class="bar_price textalignleft">
						$ <?php $price = get_post_meta( $price_bar_id, '_regular_price', true); echo $price; ?>.00
						
						<a href="<?php echo $site_url?>/cart/?add-to-cart=<?php echo $price_bar_id; ?>" title="View Showcase" class="read-more black_bt">ADD TO CART</a>
					
					</div>
				</div> <!-- Grid-cell -->
			</div> <!-- .Grid .Grid--gutters -->
			
			
	</div><!-- .sas-container -->
</div><!-- .fixed_price_bar -->

		<?php
		
	}
}

if (is_woocommerce()) {
	$widgets_manager->register(new SAS_Fixed_Price_Bar());
}