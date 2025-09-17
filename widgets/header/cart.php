<?php
namespace BW\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Colors as Scheme_Color;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Header_Cart extends Widget_Base {

	public function get_name() {
		return 'sas-header-cart';
	}
	// TITLE
	public function get_title() {
		return __( 'Cart', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-elcart';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-header' ];
	}

	// CSS
	public function get_style_depends() {
		return [ 'sas-base' ];
	}
	
	// ******** CONTROLS
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content Cart', 'sas' ),
			]
		);


			$this->add_control(
				'cart_icon',
				[
					'label' => __( 'Icon', 'sas' ),
					'type' => \Elementor\Controls_Manager::ICONS,
					'default' => [
						'value' => 'fa fa-shopping-cart',
						'library' => 'solid',
					],
				]
			);

			$this->add_control(
				'cart_title',
				[
					'label' => __( 'Mouse Hover Title', 'sas' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => 'View Your Shopping Cart'
				]
			);


			$this->add_control(
	            'cart_enable_count',
	            [
	                'label'   => __('Enable Counter', 'sas'),
	                'type'    => Controls_Manager::SWITCHER,
	                'default' => 'yes',
	            ]
	        );
	        
	      

		$this->end_controls_section();


			// User Tab
		$this->start_controls_section(
			'section_content_user',
			[
				'label' => __( 'Content User', 'sas' ),
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


		$this->end_controls_section();
	
		// STYLE =================================	

		
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Cart', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		

		// Icon  
		$this->add_control(
			'cart_size',
			[
				'label' => __( 'Icon Size', 'sas' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 100,
						'step' => 1,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors' => [
					'.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu i.cart_icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu div img' => 'width: {{SIZE}}{{UNIT}};',
					
					
				],
			]
		);
		
        $this->start_controls_tabs('cart_icon_style');

	        $this->start_controls_tab(
	            'cart_icon_style_normal',
	            [
	                'label' => __('Normal', 'sas'),
	            ]
	        );
	
	   	        
				$this->add_control(
				    'cart_icon_color',
				    [
				        'label'     => __('Icon Color', 'sas'),
				        'type'      => Controls_Manager::COLOR,
						'global' => [
							'default' => Scheme_Color::COLOR_ACCENT,
						],
				        'default'   => '',
				        'selectors' => [
				            '.elementor-element-{{ID}} .cart_menu i' => 'color: {{VALUE}}',
				        ],
				    ]
				);
		        	       
		        $this->end_controls_tab();

		        $this->start_controls_tab(
		            'cart_icon_color_hover',
		            [
		                'label' => __('Hover', 'sas'),
		            ]
		        );
		
		        $this->add_control(
		            'social_icon_color_hover',
		            [
		                'label'     => __('Icon Color', 'sas'),
		                'type'      => Controls_Manager::COLOR,
		                'global' => [
							'default' => Scheme_Color::COLOR_ACCENT,
						],
		                'default'   => '',
		                'selectors' => [
				            '.elementor-element-{{ID}} .cart_menu i:hover ' => 'color: {{VALUE}}',
		                ],
		              
		            ]
		        );
		     
		        $this->end_controls_tab();
        $this->end_controls_tabs();



		// Cart Box
		$this->add_control(
            'cart_heading_box',
            [
                'label'     => __('Cart Box', 'sas'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
				'condition' => [
					'cart_enable_count' => 'yes'
				],
            ]

        );
        	
     
				$this->add_control(
				    'cart_box_color',
				    [
				        'label'     => __('Box Color', 'sas'),
				        'type'      => Controls_Manager::COLOR,
				        'global' => [
							'default' => Scheme_Color::COLOR_ACCENT,
						],
				        'default'   => '',
				        'selectors' => [
				            '.elementor-element-{{ID}} .sas-cart_container' => 'background-color: {{VALUE}}',
				        ],
						'condition' => [
							'cart_enable_count' => 'yes'
						],
				    ]
				);
	   	        						
			
			$this->add_responsive_control(
	            'cart_box_padding',
	            [
	                'label'      => __('Padding', 'sas'),
	                'type'       => Controls_Manager::DIMENSIONS,
	                'size_units' => ['px', 'em', '%'],
	                'devices'    => ['desktop', 'tablet'],
	                'selectors'  => [
	                    '.elementor-element-{{ID}} .sas-cart_container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
	                ],
	               
	            ]
	        );
			

		$this->add_responsive_control(
			'cart_box_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .sas-cart_container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		
		
		
		
		
			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name'        => 'text_scrolling_border',
					'label'       => esc_html__( 'Border', 'sas' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .sas-cart_container',
				]
			);
			  
			  
			  
			  
			        
			        
		
			$this->add_responsive_control(
				'box_align',
				[
					'label' => __( 'Alignment', 'sas' ),
					'type' => \Elementor\Controls_Manager::CHOOSE,
					'default' => 'center',
					'options' => [
						'flex-start' => [
							'title' => __( 'left', 'sas' ),
							'icon' => 'eicon-h-align-left',
						],
						'center' => [
							'title' => __( 'center', 'sas' ),
							'icon' => 'eicon-h-align-center',
						],
						'flex-end' => [
							'title' => __( 'right', 'sas' ),
							'icon' => 'eicon-h-align-right',
						],
					],
					'toggle' => false,
	             
		            'selectors'    => [
	                    '.elementor-element-{{ID}} .sas-cart_wrap_container' => 'justify-content: {{VALUE}};',
	                ],
	                
				],
	 
			);






		// Cart Cont
		$this->add_control(
            'cart_heading_count',
            [
                'label'     => __('Cart Counter', 'sas'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
				'condition' => [
					'cart_enable_count' => 'yes'
				],
            ]

        );
        	
    	
	   	        
				$this->add_control(
				    'cart_count_number_color',
				    [
				        'label'     => __('Number Color', 'sas'),
				        'type'      => Controls_Manager::COLOR,
				        'global' => [
							'default' => Scheme_Color::COLOR_ACCENT,
						],
				        'default'   => '',
				        'selectors' => [
				            '.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu div.count_icon' => 'color: {{VALUE}}',
				        ],
						'condition' => [
							'cart_enable_count' => 'yes'
						],
				    ]
				);
	   	        
				$this->add_control(
				    'cart_count_bg_color',
				    [
				        'label'     => __('Number Background Color', 'sas'),
				        'type'      => Controls_Manager::COLOR,
				        'global' => [
							'default' => Scheme_Color::COLOR_ACCENT,
						],
				        'default'   => '',
				        'selectors' => [
				            '.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu div.count_icon' => 'background-color: {{VALUE}}',
				        ],
						'condition' => [
							'cart_enable_count' => 'yes'
						],
				    ]
				);
								
		   $this->add_responsive_control(
	            'cart_count_padding',
	            [
	                'label'      => __('Padding', 'sas'),
	                'type'       => Controls_Manager::DIMENSIONS,
	                'size_units' => ['px', 'em', '%'],
	                'devices'    => ['desktop', 'tablet'],
	                'selectors'  => [
	                    '.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu div.count_icon'                                     => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
	                ],
					'condition' => [
						'cart_enable_count' => 'yes'
					],
	               
	            ]
	        );

		   $this->add_responsive_control(
	            'cart_count_position',
	            [
	                'label'      => __('Counter Position', 'sas'),
	                'type'       => Controls_Manager::DIMENSIONS,
	                'size_units' => ['px', 'em', '%'],
	                'devices'    => ['desktop', 'tablet'],
	                'selectors'  => [
	                    '.elementor-element-{{ID}} .cart-icon-wrapper .cart_menu div.count_icon'                                     => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
	                ],
					'condition' => [
						'cart_enable_count' => 'yes'
					],
	               
	            ]
	        );
	        
	        
	        
	        

		// Style User
		$this->add_control(
            'user_style',
            [
                'label'     => __('User Style', 'sas'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
				'condition' => [
				//	'cart_enable_count' => 'yes'
				],
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
			
        
        
        
        
        

				

		
	// End
	$this->end_controls_section();

	
	
	}


// HTML
//*****************************************

	protected function render() {

		$settings = $this->get_settings_for_display();
		$user_title = $settings['user_title'];
		$login_title = $settings['login_title'];
		
		$this->add_render_attribute(
			[
				'wc-cart-wrapper' => [
					'class' => [
						'cart-icon-wrapper',
						'sm',
					],
					'id' => 'cart-menu',
					'data-settings' => [
						wp_json_encode(array_filter([
							'action' => 'sas_add_to_cart',
							'cart_enable_count' => $settings['cart_enable_count'],
							'cart_icon' => $settings['cart_icon'],
						]))
					],
				],
			]
		);

		?>
		
		<?php
		$icon ='';
		if (!empty($settings['user_icon'])) {
			$icon = $settings['user_icon'];
		}

		?>
		
		<!-- ***** TOTAL WRAP  Widget > Content > blog-templates > Cart ****** -->
		<div class="sas-cart_wrap_container">
			<div class="sas-cart_container">
				
				<div class="wrap_login">
						<div class="cart-login">
							<?php if(is_user_logged_in()) : ?>
								<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" title="<?php echo __('My Account', 'sparrow') ?>"><?php echo __($user_title, 'sas')?></a>
							<?php else: ?>
								<a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php echo __('Log In', 'sparrow') ?>"><?php echo __($login_title, 'sas')?></a>
							<?php endif; ?>
						</div>
				</div><!-- .wrap_login -->
				
	
				<ul <?php echo $this->get_render_attribute_string( 'wc-cart-wrapper' ); ?>>
		
					<?php include plugin_dir_path( __DIR__ ) .'content/blog-templates/cart.php'; ?>
					
				</ul>
			</div>
		</div>
		<?php
	}

}
if (class_exists('woocommerce')) {
	$widgets_manager->register(new BW_Header_Cart());
}
