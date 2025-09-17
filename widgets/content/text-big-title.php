<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Text_Big_Title extends Widget_Base {

	public function get_name() {
		return 'sas-text-big-title';
	}
	// TITLE
	public function get_title() {
		return __( 'Big Title', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eltextscrolling';
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-text-big-title'];
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
						'.elementor-element-{{ID}} .sas_big_title' => 'color: {{VALUE}};',
					],
				]
			);
			$this->add_control(
				'text_scrolling__padding',
				[
					'label'      => __( 'Description Padding', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .sas_big_title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
			
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Typography', 'sas' ),
					'name' => 'text_scrolling___typo',
					'selector' => '.elementor-element-{{ID}} .sas_big_title',
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
					'selector'    => '{{WRAPPER}} .sas_big_title',
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
	
	
	
		<div class="sas_big_title">
			<?php echo $text_scrolling ?>			
		</div>
				
		
		
		
		
		<?php
		
		
		
	}
}
$widgets_manager->register(new BW_Text_Big_Title());