<?php
namespace SAS\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography as Scheme_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class SAS_Blog_Search extends Widget_Base {

	public function get_name() {
		return 'sas-blog-search';
	}
	// TITLE
	public function get_title() {
		return __( 'Blog Search', 'sas' );
	}
	// ICON
	public function get_icon() {
		return 'icon-sas-elbloggrid';
	}
	// CATEGORIES
	public function get_categories() {
		return [ 'sas-content' ];
	}
	// KEYS
	public function get_keywords() {
		return [ 'post', 'grid', 'layout' ];
	}
    //JS
	public function get_script_depends() {
		return [ 'isotope', 'sas-blog-grid' ];
	}
    //CSS
	public function get_style_depends() {
		return [ 'sas-base' ];
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
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4'
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
					'{{WRAPPER}} .sas-grid_blog_container .sas-grid_gap.sas-grid'     => 'margin-left: -{{SIZE}}px',
					'{{WRAPPER}} .sas-grid_blog_container .sas-grid_gap.sas-grid > *' => 'padding-left: {{SIZE}}px',
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
					'{{WRAPPER}} .sas-grid_blog_container .sas-grid_gap.sas-grid'     => 'margin-top: -{{SIZE}}px',
					'{{WRAPPER}} .sas-grid_blog_container .sas-grid_gap.sas-grid > *' => 'margin-top: {{SIZE}}px',
				],
				'render_type' => 'template',
			]
		);
		
		$this->add_control(
			'enable_masonry',
			[
			'label' => __( 'Enable Masonry', 'sas' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
				'description' => __( 'Do not use this function if your site loads the easy load plugin (easyload.js)' ),
				'return_value' => 'true',
				'label_on' => __('On', 'sas'),
				'label_off' => __('Off', 'sas'),

			]
		);

		$this->add_control(
			'alignment',
			[
				'label'   => esc_html__( 'Alignment', 'sas' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'sas' ),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'sas' ),
						'icon'  => 'fa fa-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'sas' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sas-grid_blog_container article .grid__post--prev_wrap' => 'text-align: {{VALUE}}',				],
			]
		);
		
		
			$this->add_control(
				'preview_words',
				[
					'label'   => esc_html__( 'Text Preview Words Number', 'sas' ),
					'type'    => Controls_Manager::NUMBER,
					'default' => 20,
				]
			);
		

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				'label'     => esc_html__( 'Image Size', 'sas' ),
				'exclude'   => [ 'custom' ],
				'default'   => 'medium',
			]
		);
		
		$this->add_control(
			'enable_grid_navigation',
			[
				'label' => __( 'Enable Navigation', 'sas' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => 'none',
				'options'        => [
					'none' => 'None',
					'pagination' => 'Pagination',
					'load_more' => 'Load More',
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
				'blog_thumbnail',
				[
				'label' => __( 'Enable Thumbnail', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_categories',
				[
				'label' => __( 'Enable Categories', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_title',
				[
				'label' => __( 'Enable Title', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_content',
				[
				'label' => __( 'Enable Content', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_author',
				[
				'label' => __( 'Enable Author', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_comments',
				[
				'label' => __( 'Enable Comments', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_date',
				[
				'label' => __( 'Enable Date', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
			$this->add_control(
				'blog_button',
				[
				'label' => __( 'Enable Button', 'sas' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);
		
		$this->end_controls_section();




		// STYLE
		//*****************************************
		
			
		// BOX
		$this->start_controls_section(
			'section_style_box',
			[
				'label' => esc_html__( 'Box Style', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
		$this->add_control(
			'box_color',
			[
				'label'     => esc_html__( 'Background', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--prev_wrap' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label'      => esc_html__( 'Box Padding', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .grid__post--prev_wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	
		$this->end_controls_section();

		
		
		// IMAGE
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image', 'sas' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'blog_thumbnail' => 'yes',
				],			
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .grid__post--img img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		$this->end_controls_section();

		// CATEGORY
		$this->start_controls_section(
			'section_style_category',
			[
				'label'     => esc_html__( 'Category', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'blog_categories' => 'yes',
				],			
			]
		);

		$this->add_control(
			'category_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--category a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hover_category_color',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--category a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'category_margin',
			[
				'label'      => esc_html__( 'Margin', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .grid__post--category' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'category_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '.elementor-element-{{ID}} .grid__post--category a',
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
					'blog_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hover_title_color',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--title a:hover' => 'color: {{VALUE}};',
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
					'{{WRAPPER}} .grid__post--title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'selector' => '{{WRAPPER}} .grid__post--title',
			]
		);

		$this->end_controls_section();


		// EXCERPT
		$this->start_controls_section(
			'section_style_excerpt',
			[
				'label'     => esc_html__( 'Excerpt', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'blog_content' => 'yes',
				],			
			]
		);

		$this->add_control(
			'excerpt_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .grid__post--excerpt' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'excerpt_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .grid__post--excerpt',
			]
		);

		$this->end_controls_section();

		
		// META
		$this->start_controls_section(
			'section_style_meta',
			[
				'label'     => esc_html__( 'Meta', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
						
			]
		);

		$this->add_control(
			'meta_color',
			[
				'label'     => esc_html__( 'Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'
			    .elementor-element-{{ID}} .grid__post--content_holder .sas-meta_author,
				.elementor-element-{{ID}} .grid__post--content_holder .sas-meta_author a,
				.elementor-element-{{ID}} .grid__post--content_holder .sas-meta_date,
				.elementor-element-{{ID}} .grid__post--content_holder .sas-meta_date a,
				.elementor-element-{{ID}} .grid__post--content_holder .sas-meta_comments,
				.elementor-element-{{ID}} .grid__post--content_holder .sas-meta_comments a,
				.elementor-element-{{ID}} .grid__post--content_holder span:after			
			        ' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hover_meta_color',
			[
				'label'     => esc_html__( 'Hover Color', 'sas' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'.elementor-element-{{ID}} .sas-post_meta a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'meta_margin',
			[
				'label'      => esc_html__( 'Margin', 'sas' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .sas-post_meta' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typography',
				'label'    => esc_html__( 'Typography', 'sas' ),
				'global' => [
					'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .sas-post_meta',
			]
		);

		$this->end_controls_section();




		// BUTTON
		$this->start_controls_section(
			'section_style_button',
			[
				'label'     => esc_html__( 'Button', 'sas' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'blog_button' => 'yes',
				],			
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
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
						'.elementor-element-{{ID}} .grid__post--read_more a' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'background_color',
				[
					'label'     => esc_html__( 'Background Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button' => 'background-color: {{VALUE}};',
					],
				]
			);
		
			
			$this->add_control(
				'button_border_color',
				[
					'label'     => esc_html__( 'Border Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'condition' => [
						'button_border!' => 'yes',
					],
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button' => 'border-color: {{VALUE}};',
					],
				]
			);
			
	
			$this->add_control(
				'border_radius',
				[
					'label'      => esc_html__( 'Border Radius', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'button_padding',
				[
					'label'      => esc_html__( 'Padding', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .grid__post--read_more .sas-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'separator' => 'before',
				]
			);

			$this->add_control(
				'button_fullwidth',
				[
					'label'     => esc_html__( 'Fullwidth Button', 'sas' ),
					'type'      => Controls_Manager::SWITCHER,
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button' => 'width: 100%;',
					],
					'separator' => 'before',
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name'      => 'button_typography',
					'label'     => esc_html__( 'Typography', 'sas' ),
					'global' => [
						'default' => Scheme_Typography::TYPOGRAPHY_ACCENT,
					],
					'selector'  => '.elementor-element-{{ID}} .grid__post--read_more .sas-button',
					'separator' => 'before',
				]
			);
			
			// BORDER BUTTON
			$this->add_control(
				'button_border',
				[
					'label'     => esc_html__( 'Deactivate Border Button', 'sas' ),
					'type'      => Controls_Manager::SWITCHER,
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button' => 'border-width: 0;',
					],
					'separator' => 'before',
				]
			);
			
			$this->add_control(
				'button_border_width',
				[
					'label'      => esc_html__( 'Border Width', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .grid__post--read_more .sas-button' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'separator' => 'before',
					'condition' => [
						'button_border!' => 'yes',
					],
				]
			);			
			
		$this->end_controls_tab();
		
		// HOVER
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
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button:hover' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'button_background_hover_color',
				[
					'label'     => esc_html__( 'Background Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button:hover' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'button_hover_border_color',
				[
					'label'     => esc_html__( 'Border Color', 'sas' ),
					'type'      => Controls_Manager::COLOR,
					'condition' => [
						'button_border!' => 'yes',
					],
					'selectors' => [
						'.elementor-element-{{ID}} .grid__post--read_more a.sas-button:hover' => 'border-color: {{VALUE}};',
					],
				]
			);
			
			$this->add_control(
				'button_border_width_hover',
				[
					'label'      => esc_html__( 'Border Width', 'sas' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						'.elementor-element-{{ID}} .grid__post--read_more .sas-button:hover' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'separator' => 'before',
					'condition' => [
						'button_border!' => 'yes',
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
		$isotope_enable = $settings['enable_masonry'];
		if ($isotope_enable) {
			$isotope_enable = 'true';
		} else {
			$isotope_enable = 'false';
		}


		$this->add_render_attribute(
			'sas-grid_blog_cont',
			[
				'id' => $this->get_id(),
				'class' => 'sas-grid_blog_container',
				'data-enableisotope' => $isotope_enable
			]
			);

		?>

		<!-- ***** Start widget Grid Blog ****** -->
		<div <?php echo $this->get_render_attribute_string( 'sas-grid_blog_cont' ); ?>>

		<?php
	}
	
	
	//loop
	public function render_loop_item() {
		global $wp_query;

		$settings = $this->get_settings();
		$id       = 'sas-grid_id_' . $this->get_id();
				
		if ( get_query_var('paged') ) { $paged = get_query_var('paged'); $page = $paged-1; } 
		elseif ( get_query_var('page') ) { $paged = get_query_var('page'); $page = $paged-1; } 
		else { $paged = 1; $page=0; }

		$col_mobile = isset($settings['columns_mobile']) ? $settings['columns_mobile'] : $settings['columns'];
		$col_tablet = isset($settings['columns_tablet']) ? $settings['columns_tablet'] : $settings['columns'];
		
		if($wp_query->have_posts()) {

			$this->add_render_attribute('sas-render_attribute');

			$this->add_render_attribute(
				[
					'sas-render_attribute' => [
						'class' => [
							'sas-grid_gap', //style column gup and row gap
							'sas-grid',
							'sas-grid-medium',
							'sas-child-width-1-'. $col_mobile,
							'sas-child-width-1-'. $col_tablet .'@s',
							'sas-child-width-1-'. $settings['columns'] .'@m',
						],
						'data-settings' => [
							wp_json_encode(array_filter([
								'action'            => 'wordpress_post_ajax_load',
								'current_page'      => 1,
								'max_page' 		=> $wp_query->max_num_pages
							]))
						],
						'id' => esc_attr( $id ),
					],
				]
			);
			//Switches
			$navigation_type = $settings['enable_grid_navigation'];
			$preview_words = $settings['preview_words'];
			?>
			
			<div <?php echo $this->get_render_attribute_string( 'sas-render_attribute' ); ?>>
			<?php

			//have_post
			while ( $wp_query->have_posts() ) : 
				$wp_query->the_post();
				include plugin_dir_path( __DIR__ ) .'content/blog-templates/grid.php';
			endwhile;	?>
			</div> <!-- .sas-grid_blog_container -->
			<?php
			if ($navigation_type == 'pagination'){ 
				sas_post_pagination($wp_query);
			}
			wp_reset_postdata();
			
		} else {
			echo '<div class="sas-alert-warning">' . esc_html__( 'Ops! There is no post.', 'sas' ) .'<div>';
		}
	}


	public function render_loop_item_load_more() {
		global $wp_query;
		$settings = $this->get_settings();
		$id       = 'sas-grid_id_' . $this->get_id();

		if ( get_query_var('paged') ) { $paged = get_query_var('paged'); $page = $paged-1; } 
		elseif ( get_query_var('page') ) { $paged = get_query_var('page'); $page = $paged-1; } 
		else { $paged = 1; $page=0; }

		$col_mobile = isset($settings['columns_mobile']) ? $settings['columns_mobile'] : $settings['columns'];
		$col_tablet = isset($settings['columns_tablet']) ? $settings['columns_tablet'] : $settings['columns'];

		$this->add_render_attribute('sas-render_attribute');
		$this->add_render_attribute(
			[
				'sas-render_attribute' => [
					'class' => [
						'sas-grid_gap', //style column gup and row gap
						'sas-grid',
						'sas-grid-medium',
						'sas-child-width-1-'. $col_mobile,
						'sas-child-width-1-'. $col_tablet .'@s',
						'sas-child-width-1-'. $settings['columns'] .'@m',
					],
					'data-settings' => [
						wp_json_encode(array_filter([
							'action'              => 'wordpress_post_ajax_load',
							'current_page'        => 1,
							'max_page' 		=> $wp_query->max_num_pages,
							'blog_thumbnail' => $settings['blog_thumbnail'],
							'blog_title' => $settings['blog_title'],
							'blog_categories' => $settings['blog_categories'],
							'blog_content' => $settings['blog_content'],
							'blog_author' => $settings['blog_author'],
							'blog_date' => $settings['blog_date'],
							'blog_comments' => $settings['blog_comments'],
							'blog_button' => $settings['blog_button'],
							'navigation' => $settings['enable_grid_navigation'],
							'image_size' => $settings['image_size'],
							'blog_type' => 'grid',
							'preview_words' => $settings['preview_words']
						]))
					],
					'id' => esc_attr( $id ),
				],
			]
		);
		?>
		<div <?php echo $this->get_render_attribute_string( 'sas-render_attribute' ); ?>>
		<?php
			//have_post
			while ( $wp_query->have_posts() ) : 
				$wp_query->the_post();
				include plugin_dir_path( __DIR__ ) .'content/blog-templates/grid.php';
			endwhile;	
		?>
	
		</div>
		<div class="sas-loadmore_wrap">
			<a href="javascript:;" class="sas-post_loadmore sas-button">
				<span><?php esc_html_e('Load More','sas'); ?></span>
				<span><?php esc_html_e('Loading...','sas'); ?></span>
			</a>
		</div>
	<?php
	}

	public function render() {
		$this->render_header();
		if ($this->get_settings("enable_grid_navigation") == 'load_more') $this->render_loop_item_load_more();
		else $this->render_loop_item();
	}
}


$widgets_manager->register(new SAS_Blog_Search());