<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Sales_Table extends Widget_Base {

	public function get_name() {
		return 'sas-sales-table';
	}
	// TITLE
	public function get_title() {
		return __( 'Sales table', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eltextscrolling';
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-sales-table'];
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content', 'sas-tweenmax' ];
	}


	// CONTROLS **********
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Settings', 'sas' ),
			]
		);
	
			$this->add_control(
				'text_scrolling',
				[
					'label'       => __( 'Text', 'sas' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => __( 'Type Your Text', 'sas' ),
					'placeholder' => __( 'Type Your Text', 'sas' ),
					'dynamic'     => [ 'active' => false ],
					//'description'  => sprintf(__('You can find a list of special characters at <a href="https://brajeshwar.me/entities/" target="_blank">this link</a>', 'sas')),
				]
			);
			
			$this->add_responsive_control(
				'text_scrolling_column',
				[
					'label' => __( 'Space', 'sas' ),
					'type'  => Controls_Manager::SLIDER,
					'default' => [
						'size' => 20,
					],
					'range' => [
						'px' => [
							'min' => 1,
							'max' => 100,
							'step' => 1,

						],
					],
					'selectors' => [
						'.elementor-element-{{ID}} .sas-marquee_content'  => 'width: {{SIZE}}vw;',
					],
				]
			);
			
			$this->add_responsive_control(
				'text_scrolling_time',
				[
					'label' => __( 'Time', 'sas' ),
					'type'  => Controls_Manager::SLIDER,
					'default' => [
						'size' => 15,
					],
					'range' => [
						'px' => [
							'min' => 3,
							'max' => 50,
							'step' => 1,
						],
					],
					'selectors' => [
						'.elementor-element-{{ID}} .sas-marquee_text'  => 'animation: sas-marquee {{SIZE}}s infinite linear;',
					],
				]
			);
			
			$this->add_responsive_control(
				'text_scrolling_text_repeater',
				[
					'label' => __( 'Repeat Number', 'sas' ),
					'type'  => Controls_Manager::SLIDER,
				'description' => esc_html__('Select how many times you want to repeat the text', 'sas'),
					'default' => [
						'size' => 8,
					],
					'range' => [
						'px' => [
							'min' => 1,
							'max' => 100,
							'step' => 1,

						],
					],
				]
			);
						

		$this->end_controls_section();

			

		// STYLE =================================	
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Text Style', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		
					
			$this->add_control(
				'text_scrolling__color',
				[
					'label'     => __( 'Text Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} p.sas-marquee_heading' => 'color: {{VALUE}};',
					],
				]
			);
			$this->add_group_control(
				Group_Control_Background::get_type(),
				[
					'name' => 'text_scrolling__background',
					'label' => __( 'Background', 'sas' ),
					'types' => [ 'classic','gradient' ],
				
					'selector' => '.elementor-element-{{ID}} .sas-marquee_wrap .sas-marquee_item',
					
				]
			);
			$this->add_control(
				'text_scrolling__padding',
				[
					'label'      => __( 'Description Padding', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} p.sas-marquee_heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
			
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Typography', 'sas' ),
					'name' => 'text_scrolling___typo',
					'selector' => '.elementor-element-{{ID}} p.sas-marquee_heading',
				]
			);					
				
		$this->end_controls_section();
		
	
		// BORDER
		$this->start_controls_section(
			'section_style_border_text_scrolling',
			[
				'label' => __( 'Border', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
	
			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name'        => 'text_scrolling_border',
					'label'       => esc_html__( 'Border', 'sas' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .sas-marquee_item',
				]
			);
			        
					
				
	    $this->end_controls_tab();

						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
		//End				

		$this->end_controls_section();
	}


	// HTML
	//*****************************************
	protected function render() {
		$settings = $this->get_settings_for_display();
		
		
		$text_scrolling ='';
		if (!empty($settings['text_scrolling'])) {
			$text_scrolling = $settings['text_scrolling'];
		}	
		?>
	
			
		<?php

// ACF CAMPO RIPETIZIONE Check rows existexists.
if( have_rows('sas_sales_table','option') ):
		echo "<div class='sales_table__container sales_table'> ";

    // Loop through rows.
    while( have_rows('sas_sales_table','option') ) : the_row();

          // Load sub field value.
        $sub_product_url = get_sub_field('product_url', 'option');
        $sub_product_name = get_sub_field('product_name', 'option');
        $sub_product_category = get_sub_field('product_category', 'option');
        $sub_original_price = get_sub_field('original_price', 'option');
        $sub_discounted_price = get_sub_field('discounted_price', 'option');
        $sub_discount_percentage = get_sub_field('discount_percentage', 'option');
        $sub_preview_image = get_sub_field('preview_image', 'option');
        $sub_animation = get_sub_field('animation', 'option');
        $sub_label_text = get_sub_field('label_text', 'option');
        $sub_color_label = get_sub_field('color_label', 'option');
        $sub_color_text_label = get_sub_field('color_text_label', 'option');

		echo "		<div class='sales_table__wrapper list-sales-table transition-colors'> ";
		echo "			<a href='$sub_product_url' class='collection'> ";
		
		
		echo "				<p class='colonne_f'> ";
		echo "					<span class='product__title colonne_w'>$sub_product_name </span> ";
		echo "					<span class='product__category'>$sub_product_category</span> ";
		echo "				</p>";
		
		
		
		echo "				<span class='md-show md-col-2 relative'>";
		echo "					<div class='sales_table__image  $sub_animation'>";
									if( !empty( $sub_preview_image ) ):
		echo "						<div class='image'>";
		echo 							wp_get_attachment_image($sub_preview_image, 'full', false, ['class' => 'image__element', 'style' => 'opacity: 1; position: relative; top: 0px; transition: opacity 0.3s ease 0s; width: 100%;']);
		echo "						</div>";
									endif;
		echo "					</div>";
		echo "				</span>";
		echo "				<p>";
		echo "					<span class='del'>$sub_original_price</span> ";
		echo "					<span class='actual__price'>$sub_discounted_price</span> ";
		
		
		echo "					<span class='offer'>$sub_discount_percentage</span>";
		
								if( !empty( $sub_label_text ) ):
		echo "					<span class='badge__new' style='background-color: $sub_color_label;color: $sub_color_text_label;'>";
		echo "						<marquee>$sub_label_text</marquee>";
		echo "					</span> ";
								endif;
		echo "		    	</p> ";
		echo "			</a>";
		echo "		</div>";

		
		


    // End loop.
    endwhile;
		echo "</div>";

// No value.
else :
    // Do something...
endif;
		
	?>	
				
		
		
		<?php
		
		
		
	}
}
$widgets_manager->register(new BW_Sales_Table());