<?php
namespace SAS\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Colors as Scheme_Color;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class SAS_Mobile_Menu extends Widget_Base {

	private function get_available_menus() {
        $menus = wp_get_nav_menus();

        $options = [];

        foreach ($menus as $menu) {
            $options[$menu->slug] = $menu->name;
        }

        return $options;
    }

	public function get_name() {
		return 'sas-header-mobile-menu';
	}
	// TITLE
	public function get_title() {
		return __( 'Mobile Menu', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-eluser';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-header' ];
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-mobile-menu'];
	}
	// JS
	public function get_script_depends() {
		return [ 'sas-mobile-menu-js' ];
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
		
		$menus = $this->get_available_menus();
		if (!empty($menus)) {
            $this->add_control(
                'menu',
                [
                    'label'        => __('Select Menu', 'sas'),
                    'type'         => Controls_Manager::SELECT,
                    'options'      => $menus,
                    'default'      => array_keys($menus)[0],
                    'save_default' => true,
                    'separator'    => 'after',
                    'description'  => sprintf(__('Go to the <a href="%s" target="_blank">Menus screen</a> to manage your menus.', 'sas'), admin_url('nav-menus.php')),
                ]
            );
        } else {
            $this->add_control(
                'menu',
                [
                    'type'            => Controls_Manager::RAW_HTML,
                    'raw'             => sprintf(__('<strong>There are no menus in your site.</strong><br>Go to the <a href="%s" target="_blank">Menus screen</a> to create one.', 'sas'), admin_url('nav-menus.php?action=edit&menu=0')),
                    'separator'       => 'after',
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                ]
            );
        }

			$this->add_responsive_control(
				'menu_mobile_icon_align',
				[
					'label' => __( 'Icon Alignment', 'sas' ),
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
	                    '.elementor-element-{{ID}} .menu-mobile' => 'text-align: {{VALUE}};',
	                ],
                ]    
	        ); 
	               
	        $this->add_responsive_control(
				'menu_mobile_align',
				[
					'label' => __( 'Text Pop Up Alignment', 'sas' ),
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
                    '.elementor-element-{{ID}} .menu-mobile .menu-popup' => 'text-align: {{VALUE}};',
                ],
    	                
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
		// END CONTENT

		
	
	
		// STYLE =================================	
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'sas' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);		
			
		
	    $this->add_control(
            'mobile_menu_background',
            [
                'label'     => __('Background Color', 'sas'),
                'type'      => Controls_Manager::COLOR,
                'global' => [
					'default' => Scheme_Color::COLOR_ACCENT,
				],
                'default'   => '',
                'selectors' => [
		            '.elementor-element-{{ID}} .menu-mobile.show-popup .menu-popup' => 'background: {{VALUE}}',
                ],
              
            ]
        );
	
		$this->add_control(
            'mobile_menu_close_icon',
            [
                'label'     => __('Close icon Color', 'sas'),
                'type'      => Controls_Manager::COLOR,
				'global' => [
					'default' => Scheme_Color::COLOR_ACCENT,
				],
                'default'   => '',
                'selectors' => [
		            '.elementor-element-{{ID}} .close-icon line' => 'stroke: {{VALUE}}',
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
			            '.elementor-element-{{ID}} .menu-popup ul li a ' => 'color: {{VALUE}}',
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
			            '.elementor-element-{{ID}} .menu-popup ul li a:hover' => 'color: {{VALUE}}',
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
					'selector' => '.elementor-element-{{ID}} .menu-popup ul li a',
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
	                    '.elementor-element-{{ID}} .menu-popup ul li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
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
		$menu = $settings['menu'];
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
		
		
		<div class="menu-mobile">
			<button class="menu-toggle">
				<svg class="burger-icon" viewBox="0 0 81 53" width="30" height="30">
					<defs>
						<style>.cls-1{fill:none;stroke:#000;stroke-linecap:square;stroke-width:4px;}</style>
					</defs>
					<path class="cls-1" d="M2.5,2.5h77m-77,24h77m-77,25h77" transform="translate(-0.5 -0.5)"/>
				</svg>
			</button>
			<div class="menu-popup">
				<?php
					wp_nav_menu(array('menu' => $menu));
				?>
				<button class="close-popup">
					<svg class="close-icon" viewBox="0 0 52.83 54" width="30" height="30">
						<defs><style>.cls-1{fill:none;stroke:#000;stroke-width:4px;}</style></defs>
						<line class="cls-1" x1="1.41" y1="2.59" x2="51.41" y2="52.59"/>
						<line class="cls-1" x1="51.41" y1="1.41" x2="1.41" y2="51.41"/>
					</svg>
				</button>
			</div>
		</div>		

	<?php 
		


		
	}
}
if (class_exists('woocommerce')) {
	$widgets_manager->register(new SAS_Mobile_Menu());
}