<?php
/**
 * BW Mosaic Slider Elementor widget.
 *
 * @package BW_Elementor_Widgets
 */

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BW Mosaic Slider Widget
 *
 * Embla-based mixed-content slider with:
 * - desktop asymmetric 5-item mosaic pages
 * - mobile linear 1-card slider fallback below 1000px
 * - product rendering delegated to BW_Product_Card_Component
 */
class BW_Mosaic_Slider_Widget extends Widget_Base {

	private const ITEMS_PER_PAGE    = 5;
	private const MOBILE_BREAKPOINT = 1000;

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bw-mosaic-slider';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'BW-UI Mosaic Slider', 'bw-elementor-widgets' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-post-slider';
	}

	/**
	 * Get Elementor categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'blackwork' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-mosaic-slider-script' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'bw-product-card-style', 'bw-embla-core-css', 'bw-mosaic-slider-style' );
	}

	/**
	 * Register all widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_query_controls();
		$this->register_layout_controls();
		$this->register_slider_controls();
		$this->register_card_controls();
		$this->register_style_controls();
	}

	/**
	 * Register query controls.
	 *
	 * @return void
	 */
	private function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'   => __( 'Source Type', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'product',
				'options' => array(
					'product' => __( 'Product', 'bw-elementor-widgets' ),
					'post'    => __( 'Post', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'product_parent_category',
			array(
				'label'       => __( 'Product Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => false,
				'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : array(),
				'condition'   => array( 'post_type' => 'product' ),
			)
		);

		$this->add_control(
			'product_subcategory',
			array(
				'label'       => __( 'Product Sub-category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => function_exists( 'bw_get_product_categories_options' ) ? bw_get_product_categories_options() : array(),
				'condition'   => array(
					'post_type'                => 'product',
					'product_parent_category!' => '',
				),
				'description' => __( 'Optional child categories. When selected, they override the parent category filter.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'post_parent_category',
			array(
				'label'       => __( 'Post Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => false,
				'options'     => $this->get_parent_post_category_options(),
				'condition'   => array( 'post_type' => 'post' ),
			)
		);

		$this->add_control(
			'post_subcategory',
			array(
				'label'       => __( 'Post Sub-category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->get_post_category_options(),
				'condition'   => array(
					'post_type'             => 'post',
					'post_parent_category!' => '',
				),
				'description' => __( 'Optional child categories. When selected, they override the parent category filter.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'specific_ids',
			array(
				'label'       => __( 'Manual IDs', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'e.g. 120, 125, 129', 'bw-elementor-widgets' ),
				'description' => __( 'Manual IDs override taxonomy and ordering filters. If Randomize is enabled, only this selected set is shuffled.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Item Count', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'     => __( 'Order By', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'     => __( 'Publish Date', 'bw-elementor-widgets' ),
					'modified' => __( 'Modified Date', 'bw-elementor-widgets' ),
					'title'    => __( 'Title', 'bw-elementor-widgets' ),
					'ID'       => __( 'ID', 'bw-elementor-widgets' ),
				),
				'condition' => array(
					'randomize!' => 'yes',
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'     => __( 'Order', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'ASC'  => __( 'Ascending', 'bw-elementor-widgets' ),
					'DESC' => __( 'Descending', 'bw-elementor-widgets' ),
				),
				'condition' => array(
					'randomize!' => 'yes',
				),
			)
		);

		$this->add_control(
			'randomize',
			array(
				'label'        => __( 'Randomize Items', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'When enabled, the query becomes randomized. Deterministic transient caching is skipped.', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register layout controls.
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'layout_variant',
			array(
				'label'   => __( 'Desktop Mosaic Variant', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => array(
					'center' => __( 'Big Post Center', 'bw-elementor-widgets' ),
					'left'   => __( 'Big Post Left', 'bw-elementor-widgets' ),
					'right'  => __( 'Big Post Right', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'mobile_breakpoint_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Below 1000px the desktop mosaic is disabled and the widget switches to a standard one-card Embla slider with equalized cards.', 'bw-elementor-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register slider controls.
	 *
	 * @return void
	 */
	private function register_slider_controls() {
		$this->start_controls_section(
			'section_slider',
			array(
				'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'infinite_loop',
			array(
				'label'        => __( 'Infinite Loop', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3500,
				'min'       => 1000,
				'max'       => 15000,
				'step'      => 250,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'drag_free',
			array(
				'label'        => __( 'Drag Free', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'touch_drag',
			array(
				'label'        => __( 'Touch Drag (Mobile & Tablet)', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'mouse_drag',
			array(
				'label'        => __( 'Mouse / Trackpad Drag (Desktop)', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show Arrows', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => __( 'Show Dots', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register card controls.
	 *
	 * @return void
	 */
	private function register_card_controls() {
		$this->start_controls_section(
			'section_cards',
			array(
				'label' => __( 'Card Settings', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show Title', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Show Description', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Price', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_control(
			'show_buttons',
			array(
				'label'        => __( 'Show Overlay Buttons', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => array(
					'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
					'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
					'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
					'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
					'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register style controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'tile_gap',
			array(
				'label'      => __( 'Gap Between Tiles', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tile_radius',
			array(
				'label'      => __( 'Tile Border Radius', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 48,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-tile-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings  = $this->get_settings_for_display();
		$widget_id = $this->get_id();
		$post_type = $this->resolve_post_type( $settings['post_type'] ?? 'product' );
		$is_editor = class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();

		$args       = $this->build_query_args( $settings, $post_type );
		$randomized = ( $settings['randomize'] ?? '' ) === 'yes';
		$posts      = null;
		$cache_key  = '';

		if ( ! $randomized && ! $is_editor ) {
			$key_data = array(
				'v'              => 1,
				'post_type'      => $args['post_type'],
				'posts_per_page' => $args['posts_per_page'],
				'orderby'        => $args['orderby'],
				'order'          => $args['order'] ?? 'DESC',
				'post__in'       => $args['post__in'] ?? array(),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy clauses are part of the cache identity.
				'tax_query'      => $args['tax_query'] ?? array(),
			);
			$cache_key = 'bw_ms_' . md5( wp_json_encode( $key_data ) );
			$cached    = get_transient( $cache_key );

			if ( is_array( $cached ) && ! empty( $cached ) ) {
				$posts = array_values( array_filter( array_map( 'get_post', $cached ) ) );
			}
		}

		if ( null === $posts ) {
			$query = new WP_Query( $args );
			$posts = $query->posts;
			wp_reset_postdata();

			if ( $cache_key && ! empty( $posts ) ) {
				set_transient( $cache_key, wp_list_pluck( $posts, 'ID' ), 5 * MINUTE_IN_SECONDS );
			}
		}

		if ( empty( $posts ) ) {
			$this->render_placeholder( __( 'No content found for the current Mosaic Slider query.', 'bw-elementor-widgets' ) );
			return;
		}

		$desktop_pages = array_chunk( $posts, self::ITEMS_PER_PAGE );
		$config        = array(
			'widgetId'     => $widget_id,
			'showArrows'   => ( $settings['show_arrows'] ?? 'yes' ) === 'yes',
			'showDots'     => ( $settings['show_dots'] ?? 'yes' ) === 'yes',
			'dotsPosition' => 'center',
			'desktop'      => $this->build_slider_config( $settings, 'desktop' ),
			'mobile'       => $this->build_slider_config( $settings, 'mobile' ),
		);

		$wrapper_classes = array( 'bw-mosaic-slider-wrapper' );
		if ( ( $settings['show_arrows'] ?? 'yes' ) !== 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-arrows';
		}
		if ( ( $settings['show_dots'] ?? 'yes' ) !== 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-dots';
		}

		$this->add_render_attribute(
			'wrapper',
			array(
				'class'          => implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ),
				'data-widget-id' => esc_attr( $widget_id ),
				'data-config'    => wp_json_encode( $config ),
			)
		);

		?>
		<div
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally.
			echo $this->get_render_attribute_string( 'wrapper' );
			?>
		>
			<?php $this->render_desktop_layout( $desktop_pages, $settings, $post_type ); ?>
			<?php $this->render_mobile_layout( $posts, $settings, $post_type ); ?>
		</div>
		<?php
	}

	/**
	 * Render the desktop mosaic viewport.
	 *
	 * @param array  $desktop_pages Grouped 5-item desktop pages.
	 * @param array  $settings      Widget settings.
	 * @param string $post_type     Active source type.
	 * @return void
	 */
	private function render_desktop_layout( array $desktop_pages, array $settings, $post_type ) {
		$variant    = $this->resolve_layout_variant( $settings['layout_variant'] ?? 'center' );
		$image_size = $settings['image_size'] ?? 'large';
		?>
		<div class="bw-ms-layout bw-ms-layout--desktop">
			<div class="bw-ms-desktop-shell">
				<div class="bw-embla-viewport bw-ms-embla-viewport bw-ms-desktop-viewport">
					<div class="bw-embla-container">
						<?php foreach ( $desktop_pages as $page_index => $page_posts ) : ?>
							<div class="bw-embla-slide bw-ms-desktop-slide">
								<div class="bw-ms-page bw-ms-page--<?php echo esc_attr( $variant ); ?>">
									<?php foreach ( $this->build_page_slots( $page_posts ) as $slot_name => $slot_post ) : ?>
										<?php
										$is_featured = 'featured' === $slot_name;
										$loading     = 0 === $page_index ? 'eager' : 'lazy';
										$priority    = ( 0 === $page_index && $is_featured ) ? 'high' : '';
										?>
										<div class="bw-ms-slot bw-ms-slot--<?php echo esc_attr( $slot_name ); ?>">
											<?php
											// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_item_card() and delegated renderers.
											echo $this->render_item_card( $slot_post, $settings, $post_type, $image_size, $loading, $priority, $is_featured, 'desktop' );
											?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="bw-ms-arrows-container bw-ms-arrows-container--desktop">
					<button class="bw-ms-arrow bw-ms-arrow-prev bw-ms-arrow-prev-desktop" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
					<button class="bw-ms-arrow bw-ms-arrow-next bw-ms-arrow-next-desktop" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
				</div>

				<div class="bw-ms-dots-container bw-ms-dots-container--desktop"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the mobile linear viewport.
	 *
	 * @param array  $posts     Queried posts.
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return void
	 */
	private function render_mobile_layout( array $posts, array $settings, $post_type ) {
		$image_size = $settings['image_size'] ?? 'large';
		?>
		<div class="bw-ms-layout bw-ms-layout--mobile">
			<div class="bw-ms-mobile-shell">
				<div class="bw-embla-viewport bw-ms-embla-viewport bw-ms-mobile-viewport">
					<div class="bw-embla-container">
						<?php foreach ( $posts as $index => $post ) : ?>
							<?php
							$loading  = 0 === $index ? 'eager' : 'lazy';
							$priority = 0 === $index ? 'high' : '';
							?>
							<div class="bw-embla-slide bw-ms-mobile-slide">
								<div class="bw-ms-mobile-card-shell">
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_item_card() and delegated renderers.
									echo $this->render_item_card( $post, $settings, $post_type, $image_size, $loading, $priority, false, 'mobile' );
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="bw-ms-arrows-container bw-ms-arrows-container--mobile">
					<button class="bw-ms-arrow bw-ms-arrow-prev bw-ms-arrow-prev-mobile" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
					<button class="bw-ms-arrow bw-ms-arrow-next bw-ms-arrow-next-mobile" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
				</div>

				<div class="bw-ms-dots-container bw-ms-dots-container--mobile"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Build shared slider config for a viewport mode.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $mode     Desktop or mobile mode.
	 * @return array
	 */
	private function build_slider_config( array $settings, $mode ) {
		$align = 'desktop' === $mode ? 'start' : 'start';

		return array(
			'infinite'        => ( $settings['infinite_loop'] ?? 'yes' ) === 'yes',
			'autoplay'        => ( $settings['autoplay'] ?? '' ) === 'yes',
			'autoplaySpeed'   => absint( $settings['autoplay_speed'] ?? 3500 ),
			'pauseOnHover'    => true,
			'dragFree'        => ( $settings['drag_free'] ?? '' ) === 'yes',
			'enableTouchDrag' => ( $settings['touch_drag'] ?? 'yes' ) === 'yes',
			'enableMouseDrag' => ( $settings['mouse_drag'] ?? 'yes' ) === 'yes',
			'align'           => $align,
		);
	}

	/**
	 * Build deterministic WP_Query arguments for the widget.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return array
	 */
	private function build_query_args( array $settings, $post_type ) {
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => max( 1, absint( $settings['posts_per_page'] ?? 10 ) ),
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		);

		$manual_ids = BW_Widget_Helper::parse_ids( $settings['specific_ids'] ?? '' );
		$randomize  = ( $settings['randomize'] ?? '' ) === 'yes';

		if ( ! empty( $manual_ids ) ) {
			if ( $randomize ) {
				shuffle( $manual_ids );
			}

			$args['post__in']       = $manual_ids;
			$args['posts_per_page'] = count( $manual_ids );
			$args['orderby']        = 'post__in';

			return $args;
		}

		if ( $randomize ) {
			$args['orderby'] = 'rand';
		} else {
			$args['orderby'] = $settings['order_by'] ?? 'date';
			$args['order']   = $settings['order'] ?? 'DESC';
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is the declared query contract for this widget.
		$tax_query = $this->build_tax_query( $settings, $post_type );
		if ( ! empty( $tax_query ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is the declared query contract for this widget.
				$args['tax_query'] = $tax_query;
		}

		return $args;
	}

	/**
	 * Build taxonomy query clauses from widget settings.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return array
	 */
	private function build_tax_query( array $settings, $post_type ) {
		if ( 'product' === $post_type ) {
			$subcats = array_filter( array_map( 'absint', (array) ( $settings['product_subcategory'] ?? array() ) ) );
			if ( ! empty( $subcats ) ) {
				return array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $subcats,
						'operator' => 'IN',
					),
				);
			}

			$parent = absint( $settings['product_parent_category'] ?? 0 );
			if ( $parent > 0 ) {
				return array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => array( $parent ),
						'include_children' => true,
						'operator'         => 'IN',
					),
				);
			}

			return array();
		}

		$subcats = array_filter( array_map( 'absint', (array) ( $settings['post_subcategory'] ?? array() ) ) );
		if ( ! empty( $subcats ) ) {
			return array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $subcats,
					'operator' => 'IN',
				),
			);
		}

		$parent = absint( $settings['post_parent_category'] ?? 0 );
		if ( $parent > 0 ) {
			return array(
				array(
					'taxonomy'         => 'category',
					'field'            => 'term_id',
					'terms'            => array( $parent ),
					'include_children' => true,
					'operator'         => 'IN',
				),
			);
		}

		return array();
	}

	/**
	 * Map a 5-item batch to the desktop slot contract.
	 *
	 * @param array $page_posts Posts in the current batch.
	 * @return array
	 */
	private function build_page_slots( array $page_posts ) {
		$slots      = array();
		$slot_names = array( 'featured', 'support-1', 'support-2', 'support-3', 'support-4' );

		foreach ( $page_posts as $index => $post ) {
			if ( ! isset( $slot_names[ $index ] ) ) {
				break;
			}

			$slots[ $slot_names[ $index ] ] = $post;
		}

		return $slots;
	}

	/**
	 * Render one product/editorial card item.
	 *
	 * @param WP_Post $post          Post object.
	 * @param array   $settings      Widget settings.
	 * @param string  $post_type     Active source type.
	 * @param string  $image_size    Requested image size.
	 * @param string  $image_loading Loading mode.
	 * @param string  $fetchpriority Fetch priority attribute.
	 * @param bool    $is_featured   Whether the tile is featured.
	 * @param string  $context       Desktop or mobile context.
	 * @return string
	 */
	private function render_item_card( WP_Post $post, array $settings, $post_type, $image_size, $image_loading, $fetchpriority, $is_featured, $context ) {
		if ( 'product' === $post_type && class_exists( 'BW_Product_Card_Component' ) && function_exists( 'wc_get_product' ) ) {
			$card_classes = array(
				'bw-ms-card',
				$is_featured ? 'bw-ms-card--featured' : 'bw-ms-card--support',
				'bw-ms-card--' . $context,
			);

			return BW_Product_Card_Component::render(
				$post->ID,
				array(
					'image_size'              => $image_size,
					'image_mode'              => 'cover',
					'image_loading'           => $image_loading,
					'hover_image_loading'     => 'lazy',
					'image_fetchpriority'     => $fetchpriority,
					'show_title'              => ( $settings['show_title'] ?? 'yes' ) === 'yes',
					'show_description'        => ( $settings['show_description'] ?? '' ) === 'yes',
					'description_mode'        => 'auto',
					'show_price'              => ( $settings['show_price'] ?? 'yes' ) === 'yes',
					'show_buttons'            => ( $settings['show_buttons'] ?? 'yes' ) === 'yes',
					'show_add_to_cart'        => true,
					'open_cart_popup'         => false,
					'hover_image_source'      => 'meta',
					'wrapper_classes'         => 'bw-ms-item bw-ms-item--product',
					'card_classes'            => implode( ' ', $card_classes ),
					'media_classes'           => 'bw-ms-media',
					'media_link_classes'      => 'bw-ms-media-link',
					'image_wrapper_classes'   => 'bw-ms-image',
					'content_classes'         => 'bw-ms-content bw-slider-content',
					'title_classes'           => 'bw-ms-title',
					'description_classes'     => 'bw-ms-description',
					'price_classes'           => 'bw-ms-price price',
					'overlay_classes'         => 'bw-ms-overlay overlay-buttons has-buttons',
					'overlay_buttons_classes' => 'bw-ms-overlay-buttons',
					'view_button_classes'     => 'bw-ms-overlay-button overlay-button overlay-button--view',
					'cart_button_classes'     => 'bw-ms-overlay-button overlay-button overlay-button--cart bw-btn-addtocart',
					'placeholder_classes'     => 'bw-ms-image-placeholder',
				)
			);
		}

		return $this->render_editorial_card( $post, $settings, $image_size, $image_loading, $fetchpriority, $is_featured, $context );
	}

	/**
	 * Render the local editorial card path for non-product content.
	 *
	 * @param WP_Post $post          Post object.
	 * @param array   $settings      Widget settings.
	 * @param string  $image_size    Requested image size.
	 * @param string  $image_loading Loading mode.
	 * @param string  $fetchpriority Fetch priority attribute.
	 * @param bool    $is_featured   Whether the tile is featured.
	 * @param string  $context       Desktop or mobile context.
	 * @return string
	 */
	private function render_editorial_card( WP_Post $post, array $settings, $image_size, $image_loading, $fetchpriority, $is_featured, $context ) {
		$post_id      = (int) $post->ID;
		$permalink    = get_permalink( $post_id );
		$title        = get_the_title( $post_id );
		$show_title   = ( $settings['show_title'] ?? 'yes' ) === 'yes';
		$show_excerpt = ( $settings['show_description'] ?? '' ) === 'yes';
		$excerpt      = '';

		if ( $show_excerpt ) {
			$excerpt = get_the_excerpt( $post_id );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 24 );
			}
		}

		$card_classes = array(
			'bw-ms-item',
			'bw-ms-item--editorial',
			'bw-ms-editorial-card',
			$is_featured ? 'bw-ms-card--featured' : 'bw-ms-card--support',
			'bw-ms-card--' . $context,
		);

		$image_html = '';
		if ( has_post_thumbnail( $post_id ) ) {
			$attrs = array(
				'class'   => 'bw-ms-editorial-image bw-embla-img',
				'loading' => $image_loading,
			);

			if ( '' !== $fetchpriority ) {
				$attrs['fetchpriority'] = $fetchpriority;
			}

			$image_html = get_the_post_thumbnail( $post_id, $image_size, $attrs );
		}

		ob_start();
		?>
		<article class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_classes ) ) ); ?>">
			<div class="bw-ms-editorial-shell">
				<a class="bw-ms-editorial-media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
					<?php if ( $image_html ) : ?>
						<?php echo wp_kses_post( $image_html ); ?>
					<?php else : ?>
						<span class="bw-ms-editorial-placeholder" aria-hidden="true"></span>
					<?php endif; ?>
				</a>

				<div class="bw-ms-content">
					<?php if ( $show_title ) : ?>
						<h3 class="bw-ms-title">
							<a href="<?php echo esc_url( $permalink ); ?>">
								<?php echo esc_html( $title ); ?>
							</a>
						</h3>
					<?php endif; ?>

					<?php if ( $show_excerpt && '' !== $excerpt ) : ?>
						<div class="bw-ms-description">
							<p><?php echo esc_html( $excerpt ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php

		return ob_get_clean();
	}

	/**
	 * Resolve the allowed post type.
	 *
	 * @param string $post_type Raw post type.
	 * @return string
	 */
	private function resolve_post_type( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );

		if ( ! in_array( $post_type, array( 'product', 'post' ), true ) ) {
			return 'product';
		}

		return $post_type;
	}

	/**
	 * Resolve the allowed desktop variant.
	 *
	 * @param string $variant Raw layout variant.
	 * @return string
	 */
	private function resolve_layout_variant( $variant ) {
		$variant = sanitize_key( (string) $variant );

		if ( ! in_array( $variant, array( 'center', 'left', 'right' ), true ) ) {
			return 'center';
		}

		return $variant;
	}

	/**
	 * Get top-level post categories.
	 *
	 * @return array
	 */
	private function get_parent_post_category_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$options = array();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get all post categories.
	 *
	 * @return array
	 */
	private function get_post_category_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$options = array();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Render a generic placeholder.
	 *
	 * @param string $message Placeholder message.
	 * @return void
	 */
	private function render_placeholder( $message ) {
		?>
		<div class="bw-ms-placeholder">
			<div class="bw-ms-placeholder__inner">
				<?php echo esc_html( $message ); ?>
			</div>
		</div>
		<?php
	}
}
