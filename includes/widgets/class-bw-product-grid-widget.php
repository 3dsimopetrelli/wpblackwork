<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Product_Grid_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-product-grid';
    }

    public function get_title() {
        return esc_html__( 'BW Product Grid', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'imagesloaded', 'masonry', 'bw-product-grid-js' ];
    }

    public function get_style_depends() {
        return [ 'bw-product-grid-style' ];
    }

    protected function register_controls() {
        $this->register_filter_controls();
        $this->register_query_controls();
        $this->register_rebuild_layout_controls();
    }

    private function register_rebuild_layout_controls() {
        $this->start_controls_section( 'layout_rebuild_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'infinite_scroll', [
            'label'        => __( 'Infinite Scroll', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'initial_items', [
            'label'       => __( 'Initial Items', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'min'         => -1,
            'max'         => 100,
            'step'        => 1,
            'default'     => 12,
            'description' => __( 'Use -1 to render all items and disable infinite loading.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'load_batch_size', [
            'label'     => __( 'Load Batch Size', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 100,
            'step'      => 1,
            'default'   => 12,
            'condition' => [
                'infinite_scroll' => 'yes',
            ],
        ] );

        $this->add_control( 'desktop_columns', [
            'label'   => __( 'Desktop Columns', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '4',
            'options' => [
                '3' => '3',
                '4' => '4',
            ],
        ] );

        $this->add_control( 'container_max_width', [
            'label'       => __( 'Container Max Width (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 2000,
            'min'         => 800,
            'max'         => 4000,
            'step'        => 10,
            'description' => __( 'Full-width container with this max width cap.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'masonry_effect', [
            'label'        => __( 'Masonry Effect', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->end_controls_section();
    }

    private function register_filter_controls() {
        $this->start_controls_section( 'filter_section', [
            'label' => __( 'Filter Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'show_filters', [
            'label'        => __( 'Show Filters', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Show or hide filter UI. Query/grid output remains active.', 'bw-elementor-widgets' ),
        ] );

        // Get product categories for the dropdown
        $category_options = [ 'all' => __( 'All Categories', 'bw-elementor-widgets' ) ];
        $product_categories = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => 0, // Only top-level categories
            ]
        );
        if ( ! is_wp_error( $product_categories ) && ! empty( $product_categories ) ) {
            foreach ( $product_categories as $category ) {
                $category_options[ $category->term_id ] = $category->name;
            }
        }

        $this->add_control( 'default_category', [
            'label'       => __( 'Default Category', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT,
            'options'     => $category_options,
            'default'     => 'all',
            'description' => __( 'Limit the widget to a specific category. When selected, only subcategories and tags from this category will be shown.', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'show_categories', [
            'label'        => __( 'Show Categories', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_categories_title', [
            'label'       => __( 'Categories Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Categories', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'show_subcategories', [
            'label'        => __( 'Show Subcategories', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_subcategories_title', [
            'label'       => __( 'Subcategories Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Subcategories', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes', 'show_subcategories' => 'yes' ],
        ] );

        $this->add_control( 'show_tags', [
            'label'        => __( 'Show Tags', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_tags_title', [
            'label'       => __( 'Tags Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Tags', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes', 'show_tags' => 'yes' ],
        ] );

        $this->add_control( 'show_all_button', [
            'label'        => __( 'Show “All” Option', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function register_query_controls() {
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $post_type_options = BW_Widget_Helper::get_post_type_options();
        if ( empty( $post_type_options ) ) {
            $post_type_options = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        $post_type_keys    = array_keys( $post_type_options );
        $default_post_type = array_key_exists( 'product', $post_type_options ) ? 'product' : reset( $post_type_keys );

        $this->add_control( 'post_type', [
            'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $post_type_options,
            'default' => $default_post_type,
        ] );

        $this->add_control( 'parent_category', [
            'label'       => __( 'Categoria padre', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'multiple'    => false,
            'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
            'condition'   => [ 'post_type' => 'product' ],
        ] );

        $this->add_control( 'subcategory', [
            'label'       => __( 'Sotto-categoria', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'multiple'    => true,
            'options'     => [],
            'condition'   => [
                'post_type'        => 'product',
                'parent_category!' => '',
            ],
            'description' => __( 'Seleziona una o più sottocategorie della categoria padre scelta.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'specific_ids', [
            'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
            'description' => __( 'Inserisci gli ID separati da virgola.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'order_by', [
            'label'   => __( 'Ordina per', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date'     => __( 'Data pubblicazione', 'bw-elementor-widgets' ),
                'modified' => __( 'Data modifica', 'bw-elementor-widgets' ),
                'title'    => __( 'Titolo', 'bw-elementor-widgets' ),
                'rand'     => __( 'Casuale', 'bw-elementor-widgets' ),
                'ID'       => __( 'ID', 'bw-elementor-widgets' ),
            ],
        ] );

        $this->add_control( 'order', [
            'label'     => __( 'Direzione ordinamento', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'DESC',
            'options'   => [
                'ASC'  => __( 'Crescente (A → Z, 1 → 9, vecchio → nuovo)', 'bw-elementor-widgets' ),
                'DESC' => __( 'Decrescente (Z → A, 9 → 1, nuovo → vecchio)', 'bw-elementor-widgets' ),
            ],
            'condition' => [
                'order_by!' => 'rand',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $raw_settings = $this->get_settings();
        $widget_id = $this->get_id();

        $show_filters = isset( $settings['show_filters'] ) && 'yes' === $settings['show_filters'];

        // Render filters area
        $this->render_wrapper_start( $settings, $show_filters );

        if ( $show_filters ) {
            $this->render_filters( $settings, $widget_id );
        }

        $this->render_posts( $settings, $widget_id, $raw_settings );

        $this->render_wrapper_end( $settings );
    }

    private function render_wrapper_start( $settings, $show_filters = true ) {
        $wrapper_classes = [ 'bw-product-grid-wrapper', 'bw-fpw-layout-top' ];
        $responsive_breakpoint = 900;

        echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '" data-filter-breakpoint="' . esc_attr( $responsive_breakpoint ) . '">';
    }

    private function render_wrapper_end( $settings ) {
        echo '</div>';
    }

    private function render_filters( $settings, $widget_id ) {
        $post_type = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $categories_title      = isset( $settings['filter_categories_title'] ) ? $settings['filter_categories_title'] : __( 'Categories', 'bw-elementor-widgets' );
        $subcategories_title   = isset( $settings['filter_subcategories_title'] ) ? $settings['filter_subcategories_title'] : __( 'Subcategories', 'bw-elementor-widgets' );
        $tags_title            = isset( $settings['filter_tags_title'] ) ? $settings['filter_tags_title'] : __( 'Tags', 'bw-elementor-widgets' );
        $show_categories       = isset( $settings['show_categories'] ) ? 'yes' === $settings['show_categories'] : true;
        $show_subcategories    = isset( $settings['show_subcategories'] ) ? 'yes' === $settings['show_subcategories'] : true;
        $show_tags             = isset( $settings['show_tags'] ) ? 'yes' === $settings['show_tags'] : true;
        $show_all_button       = isset( $settings['show_all_button'] ) ? 'yes' === $settings['show_all_button'] : true;

        $default_category = isset( $settings['default_category'] ) && 'all' !== $settings['default_category']
            ? absint( $settings['default_category'] )
            : 'all';

        $taxonomy     = 'product' === $post_type ? 'product_cat' : 'category';

        // If a default category is set, only show that category or hide categories
        if ( 'all' !== $default_category ) {
            // When default category is set, hide the category filter or show only that one
            $parent_terms = $show_categories
                ? get_terms(
                    [
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => true,
                        'include'    => [ $default_category ],
                    ]
                )
                : [];
        } else {
            $parent_terms = $show_categories
                ? get_terms(
                    [
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => true,
                        'parent'     => 0,
                    ]
                )
                : [];
        }

        // Load initial subcategories and tags based on default category
        $initial_category = 'all' !== $default_category ? $default_category : 'all';
        $tags = $show_tags ? $this->get_related_tags( $post_type, $initial_category, [] ) : [];
        $initial_subcategories = $show_subcategories ? $this->get_subcategories_data( $post_type, $initial_category ) : [];

        $mobile_panel_title    = __( 'Filter products', 'bw-elementor-widgets' );
        $mobile_filters_title  = __( 'Filters', 'bw-elementor-widgets' );
        $mobile_show_results   = __( 'Show results', 'bw-elementor-widgets' );
        $mobile_button_classes = [ 'bw-fpw-mobile-filter-button' ];
        $apply_button_classes  = [ 'bw-fpw-mobile-apply', 'bw-fpw-mobile-filter-button' ];
        $icon_html             = '<svg class="bw-fpw-mobile-filter-button-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7H21M6 12H18M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        ?>

        <div class="bw-fpw-mobile-filter" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-category="<?php echo esc_attr( $default_category ); ?>">
            <button class="<?php echo esc_attr( implode( ' ', $mobile_button_classes ) ); ?>" type="button">
                <?php
                echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo esc_html( $mobile_filters_title );
                ?>
            </button>

            <div class="bw-fpw-mobile-filter-panel" aria-hidden="true">
                <div class="bw-fpw-mobile-filter-panel__header">
                    <span class="bw-fpw-mobile-filter-panel__title"><?php echo esc_html( $mobile_panel_title ); ?></span>
                    <button class="bw-fpw-mobile-filter-close" type="button" aria-label="<?php esc_attr_e( 'Close filters', 'bw-elementor-widgets' ); ?>">&times;</button>
                </div>

                <div class="bw-fpw-mobile-filter-panel__body">
                    <?php if ( $show_categories ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $categories_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-filter-options--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php $this->render_category_filter_items( $parent_terms, $default_category, $show_all_button, $post_type ); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_subcategories ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--subcategories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $subcategories_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-subcategories-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php foreach ( $initial_subcategories as $subcategory ) : ?>
                                        <button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="<?php echo esc_attr( $subcategory['term_id'] ); ?>">
                                            <span class="bw-fpw-option-label"><?php echo esc_html( $subcategory['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $subcategory['count'] ); ?>)</span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_tags ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--tags" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $tags_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-tag-options" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php if ( ! empty( $tags ) ) : ?>
                                        <?php foreach ( $tags as $tag ) : ?>
                                            <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr( $tag['term_id'] ); ?>">
                                                <span class="bw-fpw-option-label"><?php echo esc_html( $tag['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $tag['count'] ); ?>)</span>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bw-fpw-mobile-filter-panel__footer">
                    <button class="<?php echo esc_attr( implode( ' ', $apply_button_classes ) ); ?>" type="button"><?php echo esc_html( $mobile_show_results ); ?></button>
                </div>
            </div>
        </div>

        <div class="bw-fpw-filters" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-category="<?php echo esc_attr( $default_category ); ?>">
            <div class="bw-fpw-filter-rows">
                <?php if ( $show_categories ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $categories_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-filter-options--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php $this->render_category_filter_items( $parent_terms, $default_category, $show_all_button, $post_type ); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_subcategories ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--subcategories bw-fpw-filter-subcategories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $subcategories_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-subcategories-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php foreach ( $initial_subcategories as $subcategory ) : ?>
                                <button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="<?php echo esc_attr( $subcategory['term_id'] ); ?>">
                                    <span class="bw-fpw-option-label"><?php echo esc_html( $subcategory['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $subcategory['count'] ); ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_tags && ! empty( $tags ) ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--tags bw-fpw-filter-tags" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $tags_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-tag-options" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php foreach ( $tags as $tag ) : ?>
                                <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr( $tag['term_id'] ); ?>">
                                    <span class="bw-fpw-option-label"><?php echo esc_html( $tag['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $tag['count'] ); ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_category_filter_items( $parent_terms, $default_category, $show_all_button, $post_type ) {
        if ( $show_all_button && 'all' === $default_category ) :
            ?>
            <button class="bw-fpw-filter-option bw-fpw-cat-button active" data-category="all">
                <span class="bw-fpw-option-label"><?php echo esc_html( __( 'All', 'bw-elementor-widgets' ) ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $this->get_total_post_count( $post_type ) ); ?>)</span>
            </button>
            <?php
        endif;

        if ( empty( $parent_terms ) || is_wp_error( $parent_terms ) ) {
            return;
        }

        $has_active_category = $show_all_button && 'all' === $default_category;

        foreach ( $parent_terms as $category ) :
            $is_active = ( 'all' !== $default_category && $category->term_id === $default_category ) || ( ! $has_active_category );
            if ( $is_active ) {
                $has_active_category = true;
            }
            ?>
            <button class="bw-fpw-filter-option bw-fpw-cat-button<?php echo $is_active ? ' active' : ''; ?>" data-category="<?php echo esc_attr( $category->term_id ); ?>">
                <span class="bw-fpw-option-label"><?php echo esc_html( $category->name ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $category->count ); ?>)</span>
            </button>
            <?php
        endforeach;
    }

    private function render_posts( $settings, $widget_id, $raw_settings = [] ) {
        $post_type         = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'post';
        $available_post_types = BW_Widget_Helper::get_post_type_options();

        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $post_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $post_type      = array_key_exists( 'post', $available_post_types ) ? 'post' : reset( $post_type_keys );
        }

        $raw_settings = is_array( $raw_settings ) ? $raw_settings : [];

        $legacy_posts_per_page = isset( $raw_settings['posts_per_page'] )
            ? (int) $raw_settings['posts_per_page']
            : ( isset( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 12 );
        if ( 0 === $legacy_posts_per_page ) {
            $legacy_posts_per_page = -1;
        }

        $has_infinite_scroll_setting = array_key_exists( 'infinite_scroll', $raw_settings );
        $has_initial_items_setting   = array_key_exists( 'initial_items', $raw_settings );
        $has_load_batch_size_setting = array_key_exists( 'load_batch_size', $raw_settings );

        $initial_items = $has_initial_items_setting
            ? (int) $settings['initial_items']
            : $legacy_posts_per_page;
        if ( 0 === $initial_items ) {
            $initial_items = 12;
        }
        if ( $initial_items < -1 ) {
            $initial_items = -1;
        }
        if ( $initial_items > 100 ) {
            $initial_items = 100;
        }

        $infinite_enabled = $has_infinite_scroll_setting
            ? ( isset( $settings['infinite_scroll'] ) && 'yes' === $settings['infinite_scroll'] )
            : $legacy_posts_per_page > 0;

        if ( $initial_items <= 0 ) {
            $infinite_enabled = false;
        }

        $load_batch_size = $has_load_batch_size_setting
            ? (int) $settings['load_batch_size']
            : ( $legacy_posts_per_page > 0 ? $legacy_posts_per_page : 12 );
        if ( $load_batch_size < 1 ) {
            $load_batch_size = 12;
        }
        if ( $load_batch_size > 100 ) {
            $load_batch_size = 100;
        }

        $initial_query_size   = $infinite_enabled ? $initial_items + 1 : $initial_items;
        $pagination_per_page  = $infinite_enabled ? $load_batch_size : $initial_items;

        $desktop_columns = isset( $settings['desktop_columns'] ) ? absint( $settings['desktop_columns'] ) : 4;
        if ( ! in_array( $desktop_columns, [ 3, 4 ], true ) ) {
            $desktop_columns = 4;
        }
        $gap_desktop_size = 24;
        $breakpoint_tablet_min = 768;
        $breakpoint_tablet_max = 1024;
        $columns_tablet = 2;
        $gap_tablet_size = 20;
        $breakpoint_mobile_max = 767;
        $columns_mobile = 2;
        $gap_mobile_size = 16;

        $container_max_width = isset( $settings['container_max_width'] ) ? absint( $settings['container_max_width'] ) : 2000;
        if ( $container_max_width < 800 ) {
            $container_max_width = 800;
        }
        if ( $container_max_width > 4000 ) {
            $container_max_width = 4000;
        }

        $masonry_effect = isset( $settings['masonry_effect'] ) && 'yes' === $settings['masonry_effect'] ? 'yes' : 'no';
        $layout_mode    = 'yes' === $masonry_effect ? 'masonry' : 'css-grid';

        $image_toggle    = true;
        $image_size      = 'large';
        $image_mode      = 'proportional';
        $hover_effect    = true;
        $open_cart_popup = false;

        $include_ids = isset( $settings['specific_ids'] ) ? BW_Widget_Helper::parse_ids( $settings['specific_ids'] ) : [];

        $parent_category = isset( $settings['parent_category'] ) ? absint( $settings['parent_category'] ) : 0;
        $subcategories   = isset( $settings['subcategory'] ) ? array_filter( array_map( 'absint', (array) $settings['subcategory'] ) ) : [];

        // Get default category setting for filtering
        $default_category = isset( $settings['default_category'] ) && 'all' !== $settings['default_category']
            ? absint( $settings['default_category'] )
            : 0;

        // Get ordering settings
        $order_by = isset( $settings['order_by'] ) ? sanitize_key( $settings['order_by'] ) : 'date';
        $order    = isset( $settings['order'] ) ? strtoupper( sanitize_key( $settings['order'] ) ) : 'DESC';

        // Validate order_by
        $valid_order_by = [ 'date', 'modified', 'title', 'rand', 'ID' ];
        if ( ! in_array( $order_by, $valid_order_by, true ) ) {
            $order_by = 'date';
        }

        // Validate order
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        // For random order, ignore ASC/DESC
        if ( 'rand' === $order_by ) {
            $order = 'ASC';
        }

        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $initial_query_size,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'ignore_sticky_posts' => true,
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $post_type ) {
            $tax_query = [];

            // If default category is set, filter by it
            if ( $default_category > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $default_category ],
                ];
            } elseif ( ! empty( $subcategories ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ];
            } elseif ( $parent_category > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $parent_category ],
                ];
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        }

        $query = new \WP_Query( $query_args );

        $has_posts         = $query->have_posts();
        $has_more          = $infinite_enabled && $query->post_count > $initial_items;
        $current_page      = 1;
        $next_page         = $has_more ? 2 : 0;
        $next_offset       = $has_more ? $initial_items : 0;
        $load_trigger_px   = 300;

        $wrapper_classes = [ 'bw-product-grid' ];
        $wrapper_style   = '--bw-fpw-max-width:' . $container_max_width . 'px; --bw-fpw-desktop-columns:' . $desktop_columns . '; --bw-fpw-grid-gap:' . $gap_desktop_size . 'px;';
        $grid_attributes = [
            'class'                       => 'bw-fpw-grid',
            'data-layout-mode'            => $layout_mode,
            'data-masonry-effect'         => $masonry_effect,
            'data-widget-id'              => $widget_id,
            'data-post-type'              => $post_type,
            'data-columns-desktop'        => $desktop_columns,
            'data-gap-desktop'            => $gap_desktop_size,
            'data-breakpoint-tablet-min'  => $breakpoint_tablet_min,
            'data-breakpoint-tablet-max'  => $breakpoint_tablet_max,
            'data-columns-tablet'         => $columns_tablet,
            'data-gap-tablet'             => $gap_tablet_size,
            'data-breakpoint-mobile-max'  => $breakpoint_mobile_max,
            'data-columns-mobile'         => $columns_mobile,
            'data-gap-mobile'             => $gap_mobile_size,
            'data-open-cart-popup'        => 'no',
            'data-order-by'               => $order_by,
            'data-order'                  => $order,
            'data-initial-items'          => $initial_items,
            'data-load-batch-size'        => $load_batch_size,
            'data-per-page'               => $pagination_per_page,
            'data-current-page'           => $current_page,
            'data-next-page'              => $next_page,
            'data-loaded-count'           => $infinite_enabled ? min( $initial_items, max( 0, $query->post_count ) ) : max( 0, $query->post_count ),
            'data-next-offset'            => $next_offset,
            'data-has-more'               => $has_more ? '1' : '0',
            'data-infinite-enabled'       => $infinite_enabled ? 'yes' : 'no',
            'data-load-trigger-offset'    => $load_trigger_px,
        ];

        $grid_attr_html = '';
        foreach ( $grid_attributes as $attr => $value ) {
            if ( '' === $value && 0 !== $value ) {
                continue;
            }

            $grid_attr_html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( (string) $value ) );
        }

        $load_state_classes = [ 'bw-fpw-load-state' ];
        if ( ! $infinite_enabled ) {
            $load_state_classes[] = 'bw-fpw-load-state--disabled';
        } elseif ( ! $has_more ) {
            $load_state_classes[] = 'bw-fpw-load-state--complete';
        }

        $rendered_posts      = 0;
        $initial_eager_items = max( 1, min( $desktop_columns, $initial_items > 0 ? $initial_items : $desktop_columns ) );
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
            <div<?php echo $grid_attr_html; ?>>
                <?php if ( $has_posts ) : ?>
                    <?php
                    while ( $query->have_posts() ) :
                        $query->the_post();
                        if ( $infinite_enabled && $rendered_posts >= $initial_items ) {
                            break;
                        }
                        $image_loading       = $rendered_posts < $initial_eager_items ? 'eager' : 'lazy';
                        $hover_image_loading = 'lazy';
                        $this->render_post_item( $post_type, $open_cart_popup, $image_loading, $hover_image_loading );
                        $rendered_posts++;
                    endwhile;
                    ?>
                <?php else : ?>
                    <div class="bw-fpw-empty-state">
                        <p class="bw-fpw-empty-message"><?php esc_html_e( 'No content available', 'bw-elementor-widgets' ); ?></p>
                        <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php esc_html_e( 'RESET FILTERS', 'bw-elementor-widgets' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $load_state_classes ) ) ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-has-more="<?php echo $has_more ? '1' : '0'; ?>" aria-live="polite">
                <div class="bw-fpw-load-indicator" role="status">
                    <span class="bw-fpw-load-indicator__spinner" aria-hidden="true"></span>
                    <span class="bw-fpw-load-indicator__label"><?php esc_html_e( 'Loading more', 'bw-elementor-widgets' ); ?></span>
                </div>
                <div class="bw-fpw-load-sentinel" aria-hidden="true"></div>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function render_post_item( $post_type, $open_cart_popup, $image_loading = 'lazy', $hover_image_loading = 'lazy' ) {
        $post_id = get_the_ID();
        $image_size = 'large';
        $image_mode = 'proportional';

        if ( 'product' === $post_type && class_exists( 'BW_Product_Card_Component' ) && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );

            if ( $product ) {
                echo BW_Product_Card_Component::render(
                    $product,
                    [
                        'image_size'             => $image_size,
                        'image_mode'             => $image_mode,
                        'image_loading'          => $image_loading,
                        'hover_image_loading'    => $hover_image_loading,
                        'show_image'             => true,
                        'show_hover_image'       => true,
                        'hover_image_source'     => 'meta',
                        'show_title'             => true,
                        'show_description'       => true,
                        'description_mode'       => 'auto',
                        'show_price'             => true,
                        'show_buttons'           => true,
                        'show_add_to_cart'       => true,
                        'open_cart_popup'        => $open_cart_popup,
                        'wrapper_classes'        => 'bw-fpw-item',
                        'card_classes'           => 'bw-fpw-card',
                        'media_classes'          => 'bw-fpw-media',
                        'media_link_classes'     => 'bw-fpw-media-link',
                        'image_wrapper_classes'  => 'bw-fpw-image',
                        'content_classes'        => 'bw-fpw-content bw-slider-content',
                        'title_classes'          => 'bw-fpw-title',
                        'description_classes'    => 'bw-fpw-description',
                        'price_classes'          => 'bw-fpw-price price',
                        'overlay_classes'        => 'bw-fpw-overlay overlay-buttons has-buttons',
                        'overlay_buttons_classes'=> 'bw-fpw-overlay-buttons',
                        'view_button_classes'    => 'bw-fpw-overlay-button overlay-button overlay-button--view',
                        'cart_button_classes'    => 'bw-fpw-overlay-button overlay-button overlay-button--cart bw-btn-addtocart',
                        'placeholder_classes'    => 'bw-fpw-image-placeholder',
                    ]
                );
                return;
            }
        }

        $permalink = get_permalink( $post_id );
        $title     = get_the_title( $post_id );
        $excerpt   = get_the_excerpt( $post_id );

        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 30 );
        }

        if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
            $excerpt = '<p>' . $excerpt . '</p>';
        }

        $thumbnail_html = '';

        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_args = [
                'loading' => $image_loading,
                'class'   => 'bw-slider-main',
            ];

            $thumbnail_html = get_the_post_thumbnail( $post_id, $image_size, $thumbnail_args );
        }

        $hover_image_html = '';
        if ( 'product' === $post_type ) {
            $hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

            if ( $hover_image_id ) {
                $hover_image_html = wp_get_attachment_image(
                    $hover_image_id,
                    $image_size,
                    false,
                    [
                        'class'   => 'bw-slider-hover',
                        'loading' => $hover_image_loading,
                    ]
                );
            }
        }

        $price_html     = '';
        $has_add_to_cart = false;
        $add_to_cart_url = '';

        if ( 'product' === $post_type ) {
            $price_html = $this->get_price_markup( $post_id );

            if ( function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $post_id );

                if ( $product ) {
                    if ( $product->is_type( 'variable' ) ) {
                        $add_to_cart_url = $permalink;
                    } else {
                        $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                        if ( $cart_url ) {
                            $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
                        }
                    }

                    if ( ! $add_to_cart_url ) {
                        $add_to_cart_url = $permalink;
                    }

                    $has_add_to_cart = true;
                }
            }
        }

        $view_label = 'product' === $post_type
            ? esc_html__( 'View Product', 'bw-elementor-widgets' )
            : esc_html__( 'Read More', 'bw-elementor-widgets' );
        ?>
        <article <?php post_class( 'bw-fpw-item' ); ?>>
            <div class="bw-fpw-card">
                <div class="bw-slider-image-container">
                    <?php
                    $media_classes = [ 'bw-fpw-media' ];
                    if ( ! $thumbnail_html ) {
                        $media_classes[] = 'bw-fpw-media--placeholder';
                    }
                    ?>
                    <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                        <?php if ( $thumbnail_html ) : ?>
                            <a class="bw-fpw-media-link" href="<?php echo esc_url( $permalink ); ?>">
                                <div class="bw-fpw-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-fpw-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                    <?php echo wp_kses_post( $thumbnail_html ); ?>
                                    <?php if ( $hover_image_html ) : ?>
                                        <?php echo wp_kses_post( $hover_image_html ); ?>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="bw-fpw-overlay overlay-buttons has-buttons">
                                <div class="bw-fpw-overlay-buttons<?php echo $has_add_to_cart ? ' bw-fpw-overlay-buttons--double' : ''; ?>">
                                    <a class="bw-fpw-overlay-button overlay-button overlay-button--view" href="<?php echo esc_url( $permalink ); ?>">
                                        <span class="bw-fpw-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                    </a>
                                    <?php if ( 'product' === $post_type && $has_add_to_cart && $add_to_cart_url ) : ?>
                                        <a class="bw-fpw-overlay-button overlay-button overlay-button--cart bw-btn-addtocart" href="<?php echo esc_url( $add_to_cart_url ); ?>"<?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
                                            <span class="bw-fpw-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <span class="bw-fpw-image-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bw-fpw-content bw-slider-content">
                    <h3 class="bw-fpw-title">
                        <a href="<?php echo esc_url( $permalink ); ?>">
                            <?php echo esc_html( $title ); ?>
                        </a>
                    </h3>

                    <?php if ( ! empty( $excerpt ) ) : ?>
                        <div class="bw-fpw-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                    <?php endif; ?>

                    <?php if ( $price_html ) : ?>
                        <div class="bw-fpw-price price"><?php echo wp_kses_post( $price_html ); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Methods parse_ids(), get_slider_value_with_unit(), and get_post_type_options()
     * have been moved to BW_Widget_Helper class to reduce code duplication.
     * Use BW_Widget_Helper::method_name() instead.
     */

    private function get_total_post_count( $post_type ) {
        $counts = wp_count_posts( $post_type );

        if ( $counts && isset( $counts->publish ) ) {
            return (int) $counts->publish;
        }

        return 0;
    }

    private function get_filtered_post_ids( $post_type, $category, array $subcategories ) {
        $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

        $tax_query = [];

        if ( 'all' !== $category && absint( $category ) > 0 ) {
            if ( ! empty( $subcategories ) ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ];
            } else {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => [ absint( $category ) ],
                ];
            }
        }

        $query_args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => $tax_query,
        ];

        $query = new WP_Query( $query_args );

        return $query->posts;
    }

    private function get_subcategories_data( $post_type, $category = 'all' ) {
        $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ];

        if ( 'all' !== $category && ! empty( $category ) && absint( $category ) > 0 ) {
            $args['parent'] = absint( $category );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return [];
        }

        if ( 'all' === $category ) {
            $terms = array_filter(
                $terms,
                static function ( $term ) {
                    return (int) $term->parent > 0;
                }
            );
        }

        $results = [];

        foreach ( $terms as $term ) {
            $results[] = [
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'count'   => (int) $term->count,
            ];
        }

        return $results;
    }

    private function collect_terms_from_posts( $taxonomy, array $post_ids ) {
        if ( empty( $post_ids ) ) {
            return [];
        }

        $terms_map = [];

        foreach ( $post_ids as $post_id ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy );

            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $term_id = (int) $term->term_id;

                if ( ! isset( $terms_map[ $term_id ] ) ) {
                    $terms_map[ $term_id ] = [
                        'term_id' => $term_id,
                        'name'    => $term->name,
                        'count'   => 0,
                    ];
                }

                $terms_map[ $term_id ]['count']++;
            }
        }

        usort(
            $terms_map,
            static function ( $a, $b ) {
                return strcmp( $a['name'], $b['name'] );
            }
        );

        return $terms_map;
    }

    private function get_related_tags( $post_type, $category = 'all', array $subcategories = [] ) {
        $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

        if ( 'all' === $category || empty( $category ) ) {
            $terms = get_terms(
                [
                    'taxonomy'   => $tag_taxonomy,
                    'hide_empty' => true,
                ]
            );

            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                return [];
            }

            $results = [];

            foreach ( $terms as $term ) {
                $results[] = [
                    'term_id' => (int) $term->term_id,
                    'name'    => $term->name,
                    'count'   => (int) $term->count,
                ];
            }

            return $results;
        }

        $post_ids = $this->get_filtered_post_ids( $post_type, $category, $subcategories );

        return $this->collect_terms_from_posts( $tag_taxonomy, $post_ids );
    }

    private function get_price_markup( $post_id ) {
        return bw_fpw_get_price_markup( $post_id );
    }
}
