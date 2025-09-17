<?php
namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography as Scheme_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BW_Products_Slider extends Widget_Base {

	public function get_name() {
		return 'sas-products-slider';
	}
	// TITLE
	public function get_title() {
		return __( 'Product Slider', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-postslider';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content' ];
	}
	// KEYS
	public function get_keywords() {
		return [ 'product', 'slide', 'layout' ];
	}
	// JS
	public function get_script_depends() {
		return [ 'sas-productslider' ];
	}
	// CSS
	public function get_style_depends() {
		return [ 'sas-postslider-style' ];
	}
	
	

	// CONTROLS *************
	protected function register_controls() {

		$this->start_controls_section(
			'section_postslider_layout',
			[
				'label' => esc_html__( 'Layout', 'sas' ),
			]
		);

			$this->add_responsive_control(
				'postslider_style',
				[
					'label'          => esc_html__( 'Slider Style', 'sas' ),
					'type'           => Controls_Manager::SELECT,
					'default'        => '1',
					'options'        => [
						'1' => __( '1 Column', 'sas' ),
						'2' => __( '2 Columns', 'sas' ),
						'3' => __( '3 Columns', 'sas' ),
						'4' => __( '4 Columns', 'sas' ),
						'5' => __( '5 Columns', 'sas' ),
					],
				]
			);


			$this->add_control(
				'active_central_slide',
				[
				'label' => __( 'Prev/Next Preview', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => '',
				]
			);
			
			
			
			$this->add_control(
				'active_variable_width',
				[
				'label' => __( 'Variable Width', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => '',
				]
			);
			$this->add_responsive_control(
				'slidepost_width',
				[
					'label'   => esc_html__( 'Post Width', 'sas' ),
					'type'    => Controls_Manager::SLIDER,
					'default' => [
						'size' => 600,
					],
					'range' => [
						'px' => [
							'min'  => 300,
							'max'  => 800,
							'step' => 5,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .sas-total_wrap_post_slider img' => 'width: {{SIZE}}px',
					],
					'render_type' => 'template',
					'condition'   => [
						'active_variable_width'    => 'yes',
					],
				]
			);
	

			$this->add_responsive_control(
				'item_gap',
				[
					'label'   => esc_html__( 'Column Gap', 'sas' ),
					'type'    => Controls_Manager::SLIDER,
					'devices' => [ 'desktop', 'tablet', 'mobile' ],
					'desktop_default' => [
						'size' => 80,
						'unit' => 'px',
					],
					'tablet_default' => [
						'size' => 0,
						'unit' => 'px',
					],
					'mobile_default' => [
						'size' => 0,
						'unit' => 'px',
					],
					'range' => [
						'px' => [
							'min'  => 0,
							'max'  => 100,
							'step' => 5,
						],
					],
					'render_type' => 'template',
					'selectors' => [
						'{{WRAPPER}} .slick-slide' => 'margin-left: {{SIZE}}px'
					],
				]
			);


			$this->add_responsive_control(
				'slidepost_max_height',
				[
					'label'   => esc_html__( 'Slide Height', 'sas' ),
					'type'    => Controls_Manager::SLIDER,
					'default' => [
						'size' => 600,
					],
					'range' => [
						'px' => [
							'min'  => 300,
							'max'  => 800,
							'step' => 5,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .slick-slide img' => 'max-height: {{SIZE}}px',
					],
					'render_type' => 'template',
				]
			);

				
			$this->add_group_control(
				Group_Control_Image_Size::get_type(),
				[
					'name'      => 'image',
					'label'     => esc_html__( 'Image Size', 'sas' ),
					'exclude'   => [ 'custom' ],
					'default'   => 'full',
				]
			);
			
		

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
	
			$categories_terms = get_terms( 'category' );
	
			$options = [];
			foreach ( $categories_terms as $category ) {
				$options[ $category->slug ] = $category->name;
			}
	
			$this->add_control(
				'post_categories',
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
				'exclude_posts',
				[
					'label'       => esc_html__( 'Exclude Post(s)', 'sas' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder'     => 'post_id',
					'label_block' => true,
					'description' => __( 'Write post id here, if you want to exclude multiple posts so use comma as separator. Such as 1 , 2', '' ),
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
			
			$this->add_control(
				'blog_posts_offset',
				[
					'label' => __( 'Posts Offset', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 9999,
					'step' => 1,
					'default' => 0,
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
				'label' => __( 'Enable Title', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'show_categories',
				[
				'label' => __( 'Enable Categories', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'show_date',
				[
				'label' => __( 'Enable Date', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
		
			
		$this->end_controls_section();


		// JS Slide Settings ****************************************************
	
		$this->start_controls_section(
			'section_content_slide',
			[
				'label' => __( 'Slide Settings', 'sas' ),
			]
		);

			$this->add_control(
	            'switcher_slidepost_autoplay',
	            [
	                'label'   => __('Enable Autoplay', 'sas'),
	                'type'    => Controls_Manager::SWITCHER,
	                'default' => 'true',
					'return_value' => 'true',
					'label_on' => __( 'True', 'sas' ),
					'label_off' => __( 'False', 'sas' ),
	            ]
	        );			
			$this->add_control(
	            'switcher_slidepost_infinite',
	            [
	                'label'   => __('Enable Infinite Loop', 'sas'),
	                'type'    => Controls_Manager::SWITCHER,
	                'default' => 'true',
					'return_value' => 'true',
					'label_on' => __( 'True', 'sas' ),
					'label_off' => __( 'False', 'sas' ),
	            ]
	        );
	        $this->add_control(
	            'switcher_slidepost_dots',
	            [
	                'label'   => __('Enable Dots', 'sas'),
	                'type'    => Controls_Manager::SWITCHER,
	                'default' => 'false',
					'return_value' => 'true',
					'label_on' => __( 'True', 'sas' ),
					'label_off' => __( 'False', 'sas' ),
	            ]
	        );			
		
			$this->add_responsive_control(
				'slidepost_speed_slider',
				[
					'label'     => __( 'Slider Speed', 'sas' ),
					'type'      => Controls_Manager::SLIDER,
					'dynamic'     => [ 'active' => false ],
					'default' => [
						'size' => 300,
					],					
					'range' => [
						'px' => [
							'min' => 10,
							'max' => 1000,
							'step' => 2,
						],
					],			
					
				]
			);
		
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


		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} img.slick-slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
			
			

		// ARROW 
		$this->start_controls_section(
			'section_style_arrow',
			[
				'label'     => esc_html__( 'Arrow', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'postslider_style!' => '5',
				],
			]
		);
		
		
		$this->start_controls_tabs('tabs_arrow_switcher_style');

        $this->start_controls_tab(
            'tab_arrow_switcher_style_icon',
            [
                'label' => __('Icon', 'sas'),
            ]
        );

			// ICON ARROW
			$this->add_control(
				'arrow_prev',
				[
					'label'   => __( 'Prev Arrow Icon', 'sas' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
				]
			);
			$this->add_control(
				'arrow_prev_x',
				[
					'label'   => __( 'Prev Arrow Icon X', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);
			$this->add_control(
				'arrow_prev_y',
				[
					'label'   => __( 'Prev Arrow Icon Y', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);

			$this->add_control(
				'arrow_next',
				[
					'label'   => __( 'Next Arrow Icon', 'sas' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
				]
			);
			$this->add_control(
				'arrow_next_x',
				[
					'label'   => __( 'Next Arrow Icon X', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);
			$this->add_control(
				'arrow_next_y',
				[
					'label'   => __( 'Next Arrow Icon Y', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);

			$this->add_control(
				'pointer_center',
				[
					'label'   => __( 'Certer Slide Pointer', 'sas' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
				]
			);
			$this->add_control(
				'pointer_center_x',
				[
					'label'   => __( 'Certer Slide Pointer X', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);
			$this->add_control(
				'pointer_center_y',
				[
					'label'   => __( 'Certer Slide Pointer Y', 'sas' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'default' => 0
				]
			);
			
			
			$this->add_responsive_control(
				'arrow_icon_size',
				[
					'label'   => esc_html__( 'Icon Size', 'sas' ),
					'type'    => Controls_Manager::SLIDER,
					'default' => [
						'size' => 45,
					],
					'range' => [
						'px' => [
							'min'  => 20,
							'max'  => 100,
							'step' => 1,
						],
					],
					'render_type' => 'template',
					'selectors' => [
						'{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev i:before, {{WRAPPER}} .sas-total_wrap_post_slider button.slick-next i:before'     => 'font-size: {{SIZE}}px',
					],
				]
			);
		
		
        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_arrow_switcher_style_text',
            [
                'label' => __('Text', 'sas'),
            ]
        );
			
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name'     => 'arrow_typography',
					'label'    => esc_html__( 'Typography', 'sas' ),
					'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
					'selector' => '{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev, {{WRAPPER}} .sas-total_wrap_post_slider button.slick-next',
				]
			);
	
	
			
		$this->end_controls_tab();

    	$this->end_controls_tabs();
		
		
		
		
		
		
		// Arrow Colors
		$this->add_control(
			'arrow_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-next,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev i,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-next i' => 'color: {{VALUE}};',
				],
			]
		);
		
		$this->add_control(
			'arrow_color_hover',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev:hover,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-next:hover,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev i:hover,
					{{WRAPPER}} .sas-total_wrap_post_slider button.slick-next i:hover' => 'color: {{VALUE}};',
				],			
			]
		);
		
		
		$this->add_responsive_control(
			'opacity_arrow',
			[
				'label'   => esc_html__( 'Opacity Arrows', 'sas' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'render_type' => 'template',
				'selectors' => [
					'{{WRAPPER}} .sas-total_wrap_post_slider .slick-prev, 
					{{WRAPPER}} .sas-total_wrap_post_slider .slick-next'    
					 => 'opacity: {{SIZE}}',
				],
			]
		);
		
		$this->add_responsive_control(
			'opacity_arrow_hover',
			[
				'label'   => esc_html__( 'Opacity Arrows Hover', 'sas' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 1,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'render_type' => 'template',
				'selectors' => [
					'{{WRAPPER}} .sas-total_wrap_post_slider.slick-slider:hover .slick-prev, 
					{{WRAPPER}} .sas-total_wrap_post_slider.slick-slider:hover .slick-next,
					{{WRAPPER}} .sas-total_wrap_post_slider .slick-prev:hover:before,  
					{{WRAPPER}} .sas-total_wrap_post_slider .slick-prev:focus:before'  
					 => 'opacity: {{SIZE}}',
				],
			]
		);


		// Arrow Position
		$this->add_responsive_control(
			'arrow_prev_ab_position',
			[
				'label'      => esc_html__( 'Prev Arrow Position', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
						'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .sas-total_wrap_post_slider button.slick-prev' => '
					top: {{TOP}}{{UNIT}};
					right: {{RIGHT}}{{UNIT}};
					bottom: {{BOTTOM}}{{UNIT}};
					left: {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'arrow_next_ab_position',
			[
				'label'      => esc_html__( 'Next Arrow Position', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
						'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .sas-total_wrap_post_slider button.slick-next' => '
					top: {{TOP}}{{UNIT}};
					right: {{RIGHT}}{{UNIT}};
					bottom: {{BOTTOM}}{{UNIT}};
					left: {{LEFT}}{{UNIT}};',
				],
			]
		);
	
		$this->end_controls_section();
				
			
			
			
			
		//end
	}
	
	

// HTML
//*****************************************
public function render_header() {
		$settings = $this->get_settings();	?>
			<!-- ***** Start widget Post Slider ****** -->
			<div>
<?php 	
}
	
	
	//loop
	public function render_loop_item() {
		global $product;
		$settings = $this->get_settings();
		$id       = 'sas-grid_id_' . $this->get_id();
		
		$dots = ( $settings['switcher_slidepost_dots'] ? 'true' : 'false' );
		$autoplay = ( $settings['switcher_slidepost_autoplay'] ? 'true' : 'false' );
		$central = ( $settings['active_central_slide'] ? 'true' : 'false' );
		$infinite = ( $settings['switcher_slidepost_infinite'] ? 'true' : 'false' );
		$varwidth = ( $settings['active_variable_width'] ? 'true' : 'false' );
		$speed = ( $settings['slidepost_speed_slider'] ? $settings['slidepost_speed_slider']['size'] : 300 );
		$prev = ( $settings['arrow_prev'] ? $settings['arrow_prev']['url'] : '' );
		$next = ( $settings['arrow_next'] ? $settings['arrow_next']['url'] : '' );
		$center = ( $settings['pointer_center'] ? $settings['pointer_center']['url'] : '');
		$cursor_positions = array(
			'p_x' => $settings['arrow_prev_x'],
			'p_y' => $settings['arrow_prev_x'],
			'n_x' => $settings['arrow_next_x'],
			'n_y' => $settings['arrow_next_x'],
			'c_x' => $settings['pointer_center_x'],
			'c_y' => $settings['pointer_center_x'],
		);


		$product = wc_get_product();
			if ( empty( $product ) ) {
				return;
			}
			$attachment_ids = $product->get_gallery_image_ids();
			$prod_images = array();

			

			foreach($attachment_ids as $image_id) {
				$prod_images[]['src'] = wp_get_attachment_image_url($image_id, 'full');
			}

			$this->add_render_attribute('sas-render_attribute');

			$this->add_render_attribute(
				[
					'sas-render_attribute' => [
						'class' => [
							
							'slick',
							'sas-total_wrap_post_slider',
							
							'sas-grid_gap', //style column gap and row gap
							'sas-grid',
							'sas-grid-medium',
						],
						'id' => [ esc_attr( $id ), 'prodlightgallery' ],
						'data-slidercolumns' => $settings['postslider_style'],
						'data-sliderdots' => $dots,
						'data-slidercentralmode' => $central,
						'data-sliderautoplay' => $autoplay,
						'data-sliderinfinite' => $infinite,
						'data-sliderspeed' => $speed,
						'data-sliderwidth' => $varwidth,
						'data-slidernext' => $next,
						'data-sliderprev' => $prev,
						'data-slidercenter' => $center,
						'data-cursorpositions' => json_encode($cursor_positions),
						'data-images' => json_encode($prod_images),
					],
				]
			);

			//Switches
			$switch_title = $settings['show_title'];
			$switch_categories = $settings['show_categories'];
			$show_date = $settings['show_date'];
	
			?>

			<!-- ***** TOTAL WRAP ****** -->
			
			<?php 
				// Gallery Overlay
			?>
			<div class="product-image-overlay">
				<div class="product-image-overlay-header">
					<div class="product-image-overlay-header-space"></div>
					<div class="product-image-overlay-header-container">
						<?php 
							global $product;

							the_title(
								"<h2 
									data-cursor-hover='true' 
									data-cursor-hover-background='rgba(105,102,255,0,678)'>", 
								"</h2>"
							); 
						?>
						
					<div class="product-image-overlay-header-close">
						<button class="product-image-overlay-header-close-btn" 
							data-cursor-hover='true' 
							data-cursor-hover-background='rgba(105,102,255,0,678)'
							>
							<svg class="close-icon" viewBox="0 0 40 40" width="50" height="50">
								<line x1="10" y1="10" x2="30" y2="30"></line>
								<line x1="30" y1="10" x2="10" y2="30"></line>
							</svg>
						</button>
					</div>
						
						
						
					</div>
					
				</div>

				<div class="product-image-overlay-gallery">
					<?php
						foreach($attachment_ids as $image_id) {
							echo wp_get_attachment_image($image_id, $settings['image_size'], false, ['data-id' => $image_id]);
						}
					?>
				</div>
			</div>


			<?php 
				// Slick Gallery
			?>
			<div <?php echo $this->get_render_attribute_string( 'sas-render_attribute' ); ?>>
			<?php
				foreach($attachment_ids as $image_id) {
					echo wp_get_attachment_image($image_id, $settings['image_size'], false, ['data-id' => $image_id]);
				}
			?>

			</div> <!-- .sas-portfolio_container -->
			<div class="bottom-bar">
				<span class="pagingInfo"></span>
				<div class="slideButtons">
					<button class="btn-prevSlide">
						<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							viewBox="0 0 47 16" style="enable-background:new 0 0 47 16;" xml:space="preserve">
						<path d="M0.3,7.3c-0.4,0.4-0.4,1,0,1.4l6.4,6.4c0.4,0.4,1,0.4,1.4,0c0.4-0.4,0.4-1,0-1.4L2.4,8l5.7-5.7c0.4-0.4,0.4-1,0-1.4
							c-0.4-0.4-1-0.4-1.4,0L0.3,7.3z M47,7H1v2h46V7z"/>
						</svg>
					</button>
					<button class="btn-nextSlide">	
						<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							viewBox="0 0 47 16" style="enable-background:new 0 0 47 16;" xml:space="preserve">
						<path d="M46.7,8.7c0.4-0.4,0.4-1,0-1.4l-6.4-6.4c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4L44.6,8l-5.7,5.7c-0.4,0.4-0.4,1,0,1.4
							c0.4,0.4,1,0.4,1.4,0L46.7,8.7z M0,9l46,0V7L0,7L0,9z"/>
						</svg>
					</button>
				</div>
			</div>
			<?php
	}

	public function render() {
		$this->render_header();
		$this->render_loop_item();
	}
		
}
$widgets_manager->register(new BW_Products_Slider());