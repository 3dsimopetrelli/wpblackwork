<?php
/**
 * BW Related Products Widget
 *
 * Display related products using the centralized product card component
 * with proportional image layout. Tablet and mobile always use 2 columns.
 *
 * @package BW_Main_Elementor_Widgets
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Related_Products_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-related-products';
	}

	public function get_title() {
		return esc_html__( 'BW-SP Related Products', 'bw-elementor-widgets' );
	}

	public function get_icon() {
		return 'eicon-product-related';
	}

	public function get_categories() {
		return array( 'blackwork' );
	}

	public function get_style_depends() {
		return array( 'bw-wallpost-style', 'bw-product-card-style', 'bw-related-products-style' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_style_controls();
	}

	/**
	 * Content Controls
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'query_by',
			array(
				'label'   => __( 'Query by', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'category',
				'options' => array(
					'category'    => __( 'Category', 'bw-elementor-widgets' ),
					'subcategory' => __( 'Subcategory', 'bw-elementor-widgets' ),
					'tag'         => __( 'Tag', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Numero di prodotti', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
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
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Layout Controls — columns (desktop only) + gap
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'       => __( 'Colonne (Desktop)', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '4',
				'options'     => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'   => array(
					'{{WRAPPER}} .bw-related-products-grid' => '--bw-rp-columns: {{VALUE}};',
				),
				'description' => __( 'Tablet e mobile: sempre 2 colonne.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-related-products-grid' => '--bw-rp-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'show_mobile_overlay_actions',
			array(
				'label'        => __( 'Show Overlay Actions on Tablet & Mobile', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw-elementor-widgets' ),
				'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Controls the tablet/mobile visibility of the View Product / Add to Cart overlay below 1025px.', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style Controls — typography only (title, description, price)
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_typography',
			array(
				'label' => __( 'Typography', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// Title
		$this->add_control(
			'title_typography_heading',
			array(
				'label' => __( 'Titolo', 'bw-elementor-widgets' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Colore titolo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-title-color: {{VALUE}};',
				),
			)
		);

		BW_Widget_Helper::add_typography_group( $this, 'title_typography', '{{WRAPPER}} .bw-product-card .bw-wallpost-title' );

		$this->add_responsive_control(
			'title_margin_top',
			array(
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin_bottom',
			array(
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// Description
		$this->add_control(
			'description_typography_heading',
			array(
				'label'     => __( 'Descrizione', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Colore descrizione', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-description-color: {{VALUE}};',
				),
			)
		);

		BW_Widget_Helper::add_typography_group( $this, 'description_typography', '{{WRAPPER}} .bw-product-card .bw-wallpost-description' );

		$this->add_responsive_control(
			'description_margin_top',
			array(
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'description_margin_bottom',
			array(
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// Price
		$this->add_control(
			'price_typography_heading',
			array(
				'label'     => __( 'Prezzo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Colore prezzo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-price-color: {{VALUE}};',
				),
			)
		);

		BW_Widget_Helper::add_typography_group( $this, 'price_typography', '{{WRAPPER}} .bw-product-card .bw-wallpost-price' );

		$this->add_responsive_control(
			'price_margin_top',
			array(
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'price_margin_bottom',
			array(
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -100,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_labels',
			array(
				'label' => __( 'Labels', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'archive_labels_padding',
			__( 'Badge Padding', 'bw-elementor-widgets' ),
			'{{WRAPPER}} .bw-related-products-widget .bw-product-labels--archive .bw-product-label',
			'padding',
			array( 'px', '%', 'em', 'rem' ),
			array(
				'top'      => 8,
				'right'    => 12,
				'bottom'   => 8,
				'left'     => 12,
				'unit'     => 'px',
				'isLinked' => false,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Get related products based on query type
	 */
	protected function get_related_products_by_type( $product, $query_by, $posts_per_page ) {
		$product_id  = $product->get_id();
		$related_ids = array();

		switch ( $query_by ) {
			case 'subcategory':
				$related_ids = $this->get_related_by_subcategory( $product_id );
				break;

			case 'tag':
				$related_ids = $this->get_related_by_tag( $product_id );
				break;

			case 'category':
			default:
				$related_ids = wc_get_related_products(
					$product_id,
					$posts_per_page,
					$product->get_upsell_ids()
				);
				break;
		}

		if ( count( $related_ids ) > $posts_per_page ) {
			$related_ids = array_slice( $related_ids, 0, $posts_per_page );
		}

		return $related_ids;
	}

	/**
	 * Get related products by subcategory
	 */
	protected function get_related_by_subcategory( $product_id ) {
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
			return array();
		}

		$subcategories = array();
		foreach ( $product_categories as $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
				$subcategories[] = $cat_id;
			}
		}

		if ( empty( $subcategories ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => 50,
			'post__not_in'   => array( $product_id ),
			'orderby'        => 'rand',
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $subcategories,
				),
			),
		);

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Get related products by tag
	 */
	protected function get_related_by_tag( $product_id ) {
		$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );

		if ( empty( $product_tags ) || is_wp_error( $product_tags ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => 50,
			'post__not_in'   => array( $product_id ),
			'orderby'        => 'rand',
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $product_tags,
					'operator' => 'IN',
				),
			),
		);

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Get current product context
	 */
	protected function get_current_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		global $product;

		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		if ( function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
			$resolved    = bw_tbl_resolve_product_context_id( array( '__widget_class' => __CLASS__ ) );
			$resolved_id = isset( $resolved['id'] ) ? absint( $resolved['id'] ) : 0;
			if ( $resolved_id > 0 ) {
				$maybe_product = wc_get_product( $resolved_id );
				if ( $maybe_product instanceof \WC_Product ) {
					return $maybe_product;
				}
			}
		}

		return null;
	}

	/**
	 * Get fallback products for editor preview
	 */
	protected function get_fallback_products( $limit = 4 ) {
		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		if ( ! function_exists( 'wc_get_product' ) || ! class_exists( 'BW_Product_Card_Component' ) ) {
			return;
		}

		$settings                    = $this->get_settings_for_display();
		$query_by                    = isset( $settings['query_by'] ) ? $settings['query_by'] : 'category';
		$posts_per_page              = isset( $settings['posts_per_page'] ) ? absint( $settings['posts_per_page'] ) : 4;
		$show_title                  = isset( $settings['show_title'] ) && 'yes' === $settings['show_title'];
		$show_description            = isset( $settings['show_description'] ) && 'yes' === $settings['show_description'];
		$show_price                  = isset( $settings['show_price'] ) && 'yes' === $settings['show_price'];
		$show_mobile_overlay_actions = isset( $settings['show_mobile_overlay_actions'] ) && 'yes' === $settings['show_mobile_overlay_actions'];

		$product   = $this->get_current_product();
		$is_editor = class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $product instanceof \WC_Product ) {
			if ( $is_editor ) {
				$related_product_ids = $this->get_fallback_products( $posts_per_page );
				if ( empty( $related_product_ids ) ) {
					echo '<div class="bw-related-products-widget bw-related-products-empty">';
					echo '<p>' . esc_html__( 'Nessun prodotto disponibile per l\'anteprima.', 'bw-elementor-widgets' ) . '</p>';
					echo '</div>';
					return;
				}
			} else {
				return;
			}
		} else {
			$related_product_ids = $this->get_related_products_by_type( $product, $query_by, $posts_per_page );
		}

		if ( empty( $related_product_ids ) ) {
			echo '<div class="bw-related-products-widget bw-related-products-empty">';
			echo '<p>' . esc_html__( 'Non ci sono prodotti correlati.', 'bw-elementor-widgets' ) . '</p>';
			echo '</div>';
			return;
		}

		$card_settings = array(
			'image_mode'       => 'proportional',
			'show_image'       => true,
			'show_hover_image' => true,
			'show_title'       => $show_title,
			'show_description' => $show_description,
			'show_price'       => $show_price,
			'show_buttons'     => true,
			'show_add_to_cart' => true,
			'open_cart_popup'  => false,
		);

		$wrapper_classes = array( 'bw-related-products-widget' );
		if ( ! $show_mobile_overlay_actions ) {
			$wrapper_classes[] = 'bw-related-products-widget--mobile-overlay-off';
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>">
			<div class="bw-related-products-grid">
				<?php
				foreach ( $related_product_ids as $related_product_id ) {
					echo BW_Product_Card_Component::render( $related_product_id, $card_settings );
				}
				?>
			</div>
		</div>
		<?php
	}
}
