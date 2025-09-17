<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Box_Category extends Widget_Base {

	public function get_name() {
		return 'sas-box-category';
	}
	// TITLE
	public function get_title() {
		return __( 'Box category', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eltextscrolling';
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-box-category'];
	}
	// JS
	
	public function get_script_depends() {
		return [ 'sas-box-category' ];
	}
	
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content' ];
	}


	// CONTROLS **********
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Settings', 'sas' ),
			]
		);
	
						
			//Image
			$this->add_control(
				'image_two',
				[
					'label' => __( 'Image One', 'sas' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'default' => [
						'url' => \Elementor\Utils::get_placeholder_image_src(),
					],
					
				]
			);

			
			//Image
			$this->add_control(
				'image_one',
				[
					'label' => __( 'Image Two', 'sas' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'default' => [
						'url' => \Elementor\Utils::get_placeholder_image_src(),
					],
					
				]
			);

				
				
		
			//Image
			$this->add_control(
				'gif',
				[
					'label' => __( 'Gif', 'sas' ),
					'type' => Controls_Manager::SWITCHER,
					'default' => 'yes'
				]
			);

			$this->add_control(
				'link_category',
				[
					'label'       => __( 'Link', 'sas' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => __( 'Link Category', 'sas' ),
					'placeholder' => __( 'Link category', 'sas' ),
					'dynamic'     => [ 'active' => false ],
					//'description'  => sprintf(__('You can find a list of special characters at <a href="https://brajeshwar.me/entities/" target="_blank">this link</a>', 'sas')),
				]
			);

			$this->add_control(
				'name_category',
				[
					'label'       => __( 'Name', 'sas' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => __( 'Nome Category', 'sas' ),
					'placeholder' => __( 'Nome category', 'sas' ),
					'dynamic'     => [ 'active' => false ],
				]
			);
			
									

		$this->end_controls_section();

			

		// STYLE =================================	
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Image Style', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		
					
		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .box_cat_animation-inner img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
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
					'selector'    => '{{WRAPPER}} .box_cat_animation-inner img',
				]
			);
			  
			  
					
				
		$this->end_controls_section();
	    
	    
	    
		// Style Texts
		$this->start_controls_section(
			'section_style_style_text',
			[
				'label' => __( 'Texts', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
	
		
			  
			  
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Typography', 'sas' ),
					'name' => 'box_category___typo_1',
					'selector' => '.elementor-element-{{ID}} .c-themes__title',
				]
			);		
			
			$this->add_control(
				'box_category___typo_1_color',
				[
					'label'     => __( 'Text Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} .c-themes__title' => 'color: {{VALUE}};',
					],
				]
			);
			  
			  
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Typography', 'sas' ),
					'name' => 'box_category___typo_2',
					'selector' => '.elementor-element-{{ID}} .c-themes__links-wrapper',
				]
			);		
			  
			$this->add_control(
				'box_category___typo_2_color',
				[
					'label'     => __( 'Text Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} .c-themes__links-wrapper' => 'color: {{VALUE}};',
					],
				]
			);		

		$this->end_controls_section();
	}


	// HTML
	//*****************************************
	protected function render() {
		$settings = $this->get_settings_for_display();
		
		
		$link_category ='';
		if (!empty($settings['link_category'])) {
			$link_category = $settings['link_category'];
		}
		$name_category ='';
		if (!empty($settings['name_category'])) {
			$name_category = $settings['name_category'];
		}

		

				
		echo '<a class="box_cat_a_wrap" href= "';
		echo $link_category ;
		echo ' ">';
		
		if ($settings['gif']) {
			echo '<div class="box_cat_animation">';
			echo "<div class='box_cat_animation-inner'>" . wp_get_attachment_image($settings['image_one']['id'], 'full') . "</div>";
			echo "<div class='box_cat_animation-inner'>" . wp_get_attachment_image($settings['image_two']['id'], 'full') . "</div>";
			echo '</div>';
		} else {
			echo '<div class="box_cat_hover">';
			echo "<div class='box_cat_hover-inner'>" . wp_get_attachment_image($settings['image_one']['id'], 'full') . "</div>";
			echo "<div class='box_cat_hover-inner'>" . wp_get_attachment_image($settings['image_two']['id'], 'full') . "</div>";
			echo '</div>';
		}
		
		
		echo '<div class="c-themes__text-wrapper">';
		echo '	<div class="c-themes__title-wrapper">';
		echo '		<h2 class="c-themes__title">';
		echo            $name_category ;
		echo '      </h2>';
		echo '  </div>';

			
		echo '	<div class="c-themes__links-wrapper">';
			echo 'Explore -->';		
		

		echo '  </div>';
		echo '</div>';

		
		
		
		
		echo '</a>';

		?>
	
	


				
		
		<?php
		
		
		
	}
}
$widgets_manager->register(new BW_Box_Category());