<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Rotellina extends Widget_Base {

	public function get_name() {
		return 'sas-rotellina';
	}
	// TITLE
	public function get_title() {
		return __( 'Rotellina', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eltextscrolling';
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-rotellina'];
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content'];
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
	
	<div data-role="green" style="transform: scale(1);" bis_skin_checked="1">
      <a href="https://www.sparrowandsnow.com/shop/">
        <div class="nature-lettering" bis_skin_checked="1">
          <div id="changingword" bis_skin_checked="1" style="display: block;"></div>
        </div>
            
        
       
    
    
	
		<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" width="105.355mm" height="105.289mm" viewBox="0 0 298.643 298.457">
  <path d="M285.4,153.5a.91.91,0,0,1,0-1.2l13-16.5q1.95-2.4-.3-4.2l-16.5-13.3a.954.954,0,0,1-.3-1.2l9.4-21.2a.992.992,0,0,0-.5-1.3L269,85.2a1.078,1.078,0,0,1-.6-1.1l3.5-22.7a.991.991,0,0,0-.8-1.1l-22.9-3.6a1.063,1.063,0,0,1-.8-.9l-2.5-22.9a.993.993,0,0,0-1.1-.9l-23,2.4a.95.95,0,0,1-1-.6l-8.3-21.6a.961.961,0,0,0-1.3-.6l-21.7,8.5a.954.954,0,0,1-1.2-.3L173.8,1.1a1.075,1.075,0,0,0-1.4-.2L153.7,14.5a.91.91,0,0,1-1.2,0L135.9,1.3c-1.5-1.2-2.9-1.1-4.1.4L118.6,18.2a.954.954,0,0,1-1.2.3L96.1,9.2a.992.992,0,0,0-1.3.5L85.6,30.9a1.078,1.078,0,0,1-1.1.6L61.4,27.9a.991.991,0,0,0-1.1.8L56.9,51.6a1.075,1.075,0,0,1-.9.8L33,54.9a.945.945,0,0,0-.9,1.1l2.5,23a.95.95,0,0,1-.6,1L12.6,88.2a1.04,1.04,0,0,0-.6,1.3l8.4,21.6a1.093,1.093,0,0,1-.4,1.2L1.2,125.9a1.075,1.075,0,0,0-.2,1.4l13.5,18.5a.91.91,0,0,1,0,1.2L1.3,163.6a2.848,2.848,0,0,0-.5,2.7c.6,2,14.9,12.5,17.5,14.6a.954.954,0,0,1,.3,1.2L9.2,203.3a.992.992,0,0,0,.5,1.3l21.2,9.3a1.078,1.078,0,0,1,.6,1.1L28,237.9a.991.991,0,0,0,.8,1.1l22.8,3.5a1.22,1.22,0,0,1,.8.9l2.5,23.1a.993.993,0,0,0,1.1.9l23-2.5a.95.95,0,0,1,1,.6l8.3,21.6a1.04,1.04,0,0,0,1.3.6l21.7-8.4a1.093,1.093,0,0,1,1.2.4L126,298.4a1.075,1.075,0,0,0,1.4.2l18.9-13.7a.91.91,0,0,1,1.2,0L163.8,298c1.6,1.4,3.1,1.2,4.4-.4l13.3-16.4a.954.954,0,0,1,1.2-.3l21.1,9.4a.992.992,0,0,0,1.3-.5l9.2-21.2a1.078,1.078,0,0,1,1.1-.6l23,3.6a.991.991,0,0,0,1.1-.8L243,248a.961.961,0,0,1,.9-.8l22.9-2.5a.993.993,0,0,0,.9-1.1l-2.4-23a.95.95,0,0,1,.6-1l21.5-8.3a1.04,1.04,0,0,0,.6-1.3l-8.2-21.5a1.093,1.093,0,0,1,.4-1.2l18.7-13.8a1.075,1.075,0,0,0,.2-1.4Z" transform="translate(-0.662 -0.479)"style="fill:#000; "/>
</svg>
		
		<!-- stroke:#000; stroke-miterlimit:10; stroke-width:10px;  -->
		     
        
      </a>
    </div>
    

		
		
		<?php
		
		
		
	}
}
$widgets_manager->register(new BW_Rotellina());