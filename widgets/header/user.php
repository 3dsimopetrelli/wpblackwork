<?php
namespace BW\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Colors as Scheme_Color;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Header_User extends Widget_Base {

	public function get_name() {
		return 'sas-header-user';
	}
	// TITLE
	public function get_title() {
		return __( 'User', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eluser';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-header' ];
	}
	// JS
	public function get_script_depends() {
        return ['sas-smartmenu','sas-header-user'];
    }

	// CONTROLS **********
	protected function register_controls() {
		
		
		// CONTENT
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		
		
			$this->add_control(
				'user_title',
				[
					'label' => __( 'User Title', 'sas' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => 'My Admin'
				]
			);
			$this->add_control(
				'login_title',
				[
					'label' => __( 'Login Title', 'sas' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => 'Login'
				]
			);
			$this->add_responsive_control(
				'user_align',
				[
					'label' => __( 'Alignment', 'sas' ),
					'type' => \Elementor\Controls_Manager::CHOOSE,
					'default' => 'center',
					'options' => [
						'left' => [
							'left' => __( 'Left', 'sas' ),
							'icon' => 'eicon-h-align-left',
						],
						'center' => [
							'center' => __( 'Center', 'sas' ),
							'icon' => 'eicon-h-align-center',
						],
						'right' => [
							'title' => __( 'Right', 'sas' ),
							'icon' => 'eicon-h-align-right',
						],
					],
					'toggle' => false,
	             
	            'selectors'    => [
                    '.elementor-element-{{ID}} .wrap_login' => 'text-align: {{VALUE}};',
                ],
	                
	                
	                
				]
			);

	   			        
		$this->end_controls_section();
		// END CONTENT

		
	
	
		// STYLE =================================	
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		
			
			
		// ICON & TITLE COLOR
        $this->start_controls_tabs('social_tabs_style');

	        $this->start_controls_tab(
	            'social_tabs_style_normal',
	            [
	                'label' => __('Normal', 'sas'),
	            ]
	        );
	
			$this->add_control(
			    'user_title_color',
			    [
			        'label'     => __('Title Color', 'sas'),
			        'type'      => Controls_Manager::COLOR,
			        'global' => [
						'default' => Scheme_Color::COLOR_ACCENT,
					],
			        'default'   => '',
			        'selectors' => [
			            '.elementor-element-{{ID}} .cart-login a ' => 'color: {{VALUE}}',
			        ],
			    ]
			);
	       
	        $this->end_controls_tab();

	        $this->start_controls_tab(
	            'user_icon_title_color_hover',
	            [
	                'label' => __('Hover', 'sas'),
	            ]
	        );
	
		
	        $this->add_control(
	            'social_icon_title_color_hover',
	            [
	                'label'     => __('Title Color', 'sas'),
	                'type'      => Controls_Manager::COLOR,
					'global' => [
						'default' => Scheme_Color::COLOR_ACCENT,
					],
	                'default'   => '',
	                'selectors' => [
			            '.elementor-element-{{ID}} .cart-login a:hover' => 'color: {{VALUE}}',
	                ],
	              
	            ]
	        );
	    	        
	     
	        $this->end_controls_tab();

		$this->end_controls_tabs();

						
						
			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => __( 'Title Typography', 'sas' ),
					'name' => 'user_typo',
					'selector' => '.elementor-element-{{ID}} .cart-login',
				]
			);	
			
					
		

								
			
			$this->add_responsive_control(
	            'user_toggle_padding',
	            [
	                'label'      => __('Padding', 'sas'),
	                'type'       => Controls_Manager::DIMENSIONS,
	                'size_units' => ['px', 'em', '%'],
	                'devices'    => ['desktop', 'tablet'],
	                'selectors'  => [
	                    '.elementor-element-{{ID}} .cart-login' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
	                ],
	               
	            ]
	        );
			
	        	        
	     				
			
						
		$this->end_controls_section();
		// END STYLE
	}



// HTML
//*****************************************
	protected function render() {
		$settings = $this->get_settings_for_display();
		$user_title = $settings['user_title'];
		$login_title = $settings['login_title'];
		?>
		
		
		<?php
		$icon ='';
		if (!empty($settings['user_icon'])) {
			$icon = $settings['user_icon'];
		}

		?>
		<!-- HTML -->
		
		
		
		
	
		<div class="wrap_login">
				<div class="cart-login">
					<?php if(is_user_logged_in()) : ?>
						<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" title="<?php echo __('My Account', 'sparrow') ?>"><?php echo __($user_title, 'sas')?></a>
					<?php else: ?>
						<a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php echo __('Log In', 'sparrow') ?>"><?php echo __($login_title, 'sas')?></a>
					<?php endif; ?>
				</div>
		</div><!-- .wrap_login -->
		

		
				

	<?php 
		


		
	}
}
if (class_exists('woocommerce')) {
	$widgets_manager->register(new BW_Header_User());
}