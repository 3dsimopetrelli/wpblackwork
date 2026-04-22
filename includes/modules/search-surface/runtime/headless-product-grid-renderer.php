<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_get_empty_state_message( $search_query = '' ) {
    $search_query = trim( (string) $search_query );

    if ( '' !== $search_query ) {
        return __( 'No results found.', 'bw-elementor-widgets' );
    }

    return __( 'There is nothing in this archive yet.', 'bw-elementor-widgets' );
}

function bw_ss_get_result_count_label( $result_count ) {
    $result_count = max( 0, (int) $result_count );

    return sprintf(
        /* translators: %s is the result count. */
        _n( '%s result', '%s results', $result_count, 'bw-elementor-widgets' ),
        number_format_i18n( $result_count )
    );
}

function bw_ss_state_has_active_filters( $state ) {
    $state = is_array( $state ) ? $state : [];

    if ( ! empty( $state['query'] ) ) {
        return true;
    }

    if ( ! empty( $state['category'] ) && 'all' !== (string) $state['category'] ) {
        return true;
    }

    if ( ! empty( $state['subcategories'] ) ) {
        return true;
    }

    if ( ! empty( $state['tags'] ) ) {
        return true;
    }

    if ( ! empty( $state['year']['from'] ) || ! empty( $state['year']['to'] ) ) {
        return true;
    }

    if ( ! empty( $state['advanced'] ) ) {
        foreach ( (array) $state['advanced'] as $values ) {
            if ( ! empty( $values ) ) {
                return true;
            }
        }
    }

    return false;
}

function bw_ss_get_default_headless_product_grid_settings() {
    return [
        // Temporary parity defaults for Milestone 1 until Search admin settings exist.
        'widget_id'                   => 'bw-search-results-grid',
        'post_type'                   => 'product',
        'desktop_columns'             => 4,
        'tablet_columns'              => 2,
        'mobile_columns'              => 2,
        'per_page'                    => function_exists( 'bw_fpw_get_default_per_page' ) ? bw_fpw_get_default_per_page() : 24,
        'image_size'                  => 'large',
        'image_mode'                  => 'proportional',
        'hover_effect'                => true,
        'open_cart_popup'             => false,
        'show_title'                  => true,
        'show_description'            => true,
        'show_price'                  => true,
        'show_search'                 => true,
        'show_order_by'               => true,
        'show_visible_filters'        => true,
        'responsive_filter_mode'      => true,
        'responsive_filter_breakpoint'=> 1130,
        'drawer_side'                 => 'left',
        'order_trigger_style'         => 'icon',
        'desktop_filter_groups'       => [ 'types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years' ],
        'desktop_filter_order'        => [ 'types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years' ],
        'show_desktop_filter_icon'    => true,
        'container_max_width'         => 2000,
        'gap_x_desktop'               => 10,
        'gap_y_desktop'               => 10,
        'gap_x_tablet'                => 10,
        'gap_y_tablet'                => 10,
        'gap_x_mobile'                => 10,
        'gap_y_mobile'                => 10,
        'layout_mode'                 => 'css-grid',
        'masonry_effect'              => 'no',
        'disable_hover_on_touch'      => false,
        'default_sort_key'            => function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ? bw_fpw_get_discovery_sort_default_key() : 'newest',
        'default_order_by'            => 'date',
        'default_order'               => 'DESC',
        'infinite_enabled'            => true,
        'load_trigger_offset'         => 300,
        'search_placeholder'          => __( 'Search in collections...', 'bw-elementor-widgets' ),
    ];
}

function bw_ss_normalize_headless_product_grid_settings( $settings = [] ) {
    $settings = wp_parse_args( is_array( $settings ) ? $settings : [], bw_ss_get_default_headless_product_grid_settings() );

    $settings['widget_id']                    = function_exists( 'bw_fpw_normalize_widget_id' ) ? bw_fpw_normalize_widget_id( $settings['widget_id'] ) : sanitize_text_field( (string) $settings['widget_id'] );
    $settings['post_type']                    = function_exists( 'bw_fpw_normalize_post_type' ) ? bw_fpw_normalize_post_type( $settings['post_type'] ) : 'product';
    $settings['desktop_columns']              = max( 3, min( 6, absint( $settings['desktop_columns'] ) ) );
    $settings['tablet_columns']               = max( 1, min( 4, absint( $settings['tablet_columns'] ) ) );
    $settings['mobile_columns']               = max( 1, min( 3, absint( $settings['mobile_columns'] ) ) );
    $settings['per_page']                     = function_exists( 'bw_fpw_normalize_positive_int' ) ? bw_fpw_normalize_positive_int( $settings['per_page'], bw_fpw_get_default_per_page(), 1, bw_fpw_get_max_per_page() ) : max( 1, absint( $settings['per_page'] ) );
    $settings['image_size']                   = function_exists( 'bw_fpw_normalize_image_size' ) ? bw_fpw_normalize_image_size( $settings['image_size'] ) : 'large';
    $settings['image_mode']                   = function_exists( 'bw_fpw_normalize_image_mode' ) ? bw_fpw_normalize_image_mode( $settings['image_mode'] ) : 'proportional';
    $settings['hover_effect']                 = ! empty( $settings['hover_effect'] );
    $settings['open_cart_popup']              = ! empty( $settings['open_cart_popup'] );
    $settings['show_title']                   = ! empty( $settings['show_title'] );
    $settings['show_description']             = ! empty( $settings['show_description'] );
    $settings['show_price']                   = ! empty( $settings['show_price'] );
    $settings['show_search']                  = ! empty( $settings['show_search'] );
    $settings['show_order_by']                = ! empty( $settings['show_order_by'] );
    $settings['show_visible_filters']         = ! empty( $settings['show_visible_filters'] );
    $settings['responsive_filter_mode']       = ! empty( $settings['responsive_filter_mode'] );
    $settings['responsive_filter_breakpoint'] = max( 320, absint( $settings['responsive_filter_breakpoint'] ) );
    $settings['drawer_side']                  = in_array( $settings['drawer_side'], [ 'left', 'right' ], true ) ? $settings['drawer_side'] : 'left';
    $settings['order_trigger_style']          = in_array( $settings['order_trigger_style'], [ 'icon', 'dropdown' ], true ) ? $settings['order_trigger_style'] : 'icon';
    $settings['desktop_filter_groups']        = array_values( array_unique( array_map( 'sanitize_key', (array) $settings['desktop_filter_groups'] ) ) );
    $settings['desktop_filter_order']         = array_values( array_unique( array_map( 'sanitize_key', (array) $settings['desktop_filter_order'] ) ) );
    $settings['show_desktop_filter_icon']     = ! empty( $settings['show_desktop_filter_icon'] );
    $settings['container_max_width']          = max( 800, min( 4000, absint( $settings['container_max_width'] ) ) );
    $settings['gap_x_desktop']                = max( 0, absint( $settings['gap_x_desktop'] ) );
    $settings['gap_y_desktop']                = max( 0, absint( $settings['gap_y_desktop'] ) );
    $settings['gap_x_tablet']                 = max( 0, absint( $settings['gap_x_tablet'] ) );
    $settings['gap_y_tablet']                 = max( 0, absint( $settings['gap_y_tablet'] ) );
    $settings['gap_x_mobile']                 = max( 0, absint( $settings['gap_x_mobile'] ) );
    $settings['gap_y_mobile']                 = max( 0, absint( $settings['gap_y_mobile'] ) );
    $settings['layout_mode']                  = 'masonry' === $settings['layout_mode'] ? 'masonry' : 'css-grid';
    $settings['masonry_effect']               = 'masonry' === $settings['layout_mode'] ? 'yes' : 'no';
    $settings['disable_hover_on_touch']       = ! empty( $settings['disable_hover_on_touch'] );
    $settings['default_sort_key']             = function_exists( 'bw_fpw_normalize_sort_key' ) ? bw_fpw_normalize_sort_key( $settings['default_sort_key'] ) : ( function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ? bw_fpw_get_discovery_sort_default_key() : 'newest' );
    $settings['default_order_by']             = function_exists( 'bw_fpw_normalize_order_by' ) ? bw_fpw_normalize_order_by( $settings['default_order_by'] ) : 'date';
    $settings['default_order']                = function_exists( 'bw_fpw_normalize_order' ) ? bw_fpw_normalize_order( $settings['default_order'] ) : 'DESC';
    $settings['infinite_enabled']             = ! empty( $settings['infinite_enabled'] );
    $settings['load_trigger_offset']          = max( 0, absint( $settings['load_trigger_offset'] ) );
    $settings['search_placeholder']           = sanitize_text_field( (string) $settings['search_placeholder'] );

    return $settings;
}

function bw_ss_build_headless_product_grid_request( $state, $settings, $page = null ) {
    $page                = null === $page ? (int) $state['page'] : max( 1, (int) $page );
    $per_page            = (int) $settings['per_page'];
    $offset              = $page > 1 ? $per_page * ( $page - 1 ) : 0;
    $advanced_artist     = isset( $state['advanced']['artist'] ) ? wp_list_pluck( (array) $state['advanced']['artist'], 'label' ) : [];
    $advanced_author     = isset( $state['advanced']['author'] ) ? wp_list_pluck( (array) $state['advanced']['author'], 'label' ) : [];
    $advanced_publisher  = isset( $state['advanced']['publisher'] ) ? wp_list_pluck( (array) $state['advanced']['publisher'], 'label' ) : [];
    $advanced_source     = isset( $state['advanced']['source'] ) ? wp_list_pluck( (array) $state['advanced']['source'], 'label' ) : [];
    $advanced_technique  = isset( $state['advanced']['technique'] ) ? wp_list_pluck( (array) $state['advanced']['technique'], 'label' ) : [];
    $year_from           = isset( $state['year']['from'] ) ? $state['year']['from'] : null;
    $year_to             = isset( $state['year']['to'] ) ? $state['year']['to'] : null;

    return bw_fpw_build_engine_request(
        [
            'widget_id'       => $settings['widget_id'],
            'post_type'       => $settings['post_type'],
            'context_slug'    => $state['context_slug'],
            'category'        => $state['category'],
            'subcategories'   => isset( $state['subcategories'] ) ? (array) $state['subcategories'] : [],
            'tags'            => isset( $state['tags'] ) ? (array) $state['tags'] : [],
            'search_enabled'  => 'yes',
            'search'          => $state['query'],
            'artist'          => $advanced_artist,
            'author'          => $advanced_author,
            'publisher'       => $advanced_publisher,
            'source'          => $advanced_source,
            'technique'       => $advanced_technique,
            'year_from'       => $year_from,
            'year_to'         => $year_to,
            'image_toggle'    => 'yes',
            'image_size'      => $settings['image_size'],
            'image_mode'      => $settings['image_mode'],
            'hover_effect'    => $settings['hover_effect'] ? 'yes' : 'no',
            'open_cart_popup' => $settings['open_cart_popup'] ? 'yes' : 'no',
            'sort_key'        => $settings['default_sort_key'],
            'order_by'        => $settings['default_order_by'],
            'order'           => $settings['default_order'],
            'per_page'        => $per_page,
            'page'            => $page,
            'offset'          => $offset,
            'request_profile' => 'full',
            'include_filter_ui' => $page > 1,
        ]
    );
}

function bw_ss_get_headless_product_grid_results( $state, $settings ) {
    $requested_request = bw_ss_build_headless_product_grid_request( $state, $settings, (int) $state['page'] );
    $requested_result  = bw_fpw_execute_search( $requested_request );

    return [
        'requested_request' => $requested_request,
        'requested_result'  => $requested_result,
        'ui_request'        => $requested_request,
        'ui_result'         => $requested_result,
    ];
}

function bw_ss_build_headless_discovery_bootstrap_payload( $state, $settings, $ui_result ) {
    $filter_ui = isset( $ui_result['filter_ui'] ) && is_array( $ui_result['filter_ui'] ) ? $ui_result['filter_ui'] : [];

    return [
        'show_types'           => true,
        'show_tags'            => true,
        'show_years'           => ! empty( $filter_ui['year']['supported'] ),
        'search_enabled'       => ! empty( $settings['show_search'] ),
        'show_order_by'        => ! empty( $settings['show_order_by'] ),
        'show_visible_filters' => ! empty( $settings['show_visible_filters'] ),
        'order_trigger_style'  => $settings['order_trigger_style'],
        'default_sort_key'     => $settings['default_sort_key'],
        // "mixed" here is UI metadata only; engine requests already normalize
        // All scope to an explicit empty context slug at the consumer boundary.
        'context'              => $state['context_slug'] ? $state['context_slug'] : 'mixed',
        'types'                => isset( $filter_ui['types'] ) && is_array( $filter_ui['types'] ) ? array_values( $filter_ui['types'] ) : [],
        'tags'                 => isset( $filter_ui['tags'] ) && is_array( $filter_ui['tags'] ) ? array_values( $filter_ui['tags'] ) : [],
        'year'                 => isset( $filter_ui['year'] ) && is_array( $filter_ui['year'] ) ? $filter_ui['year'] : [ 'supported' => false ],
        'advanced'             => isset( $filter_ui['advanced'] ) && is_array( $filter_ui['advanced'] ) ? $filter_ui['advanced'] : [],
        'result_count'         => isset( $ui_result['result_count'] ) ? (int) $ui_result['result_count'] : 0,
        'selected'             => [
            'category'      => $state['category'],
            'search'        => $state['query'],
            'subcategories' => isset( $state['subcategories'] ) ? array_values( array_filter( array_map( 'absint', (array) $state['subcategories'] ) ) ) : [],
            'tags'          => isset( $state['tags'] ) ? array_values( array_filter( array_map( 'absint', (array) $state['tags'] ) ) ) : [],
            'year'          => [
                'from' => isset( $state['year']['from'] ) ? $state['year']['from'] : null,
                'to'   => isset( $state['year']['to'] ) ? $state['year']['to'] : null,
            ],
            'advanced'      => [
                'artist'    => isset( $state['advanced']['artist'] ) ? wp_list_pluck( (array) $state['advanced']['artist'], 'value' ) : [],
                'author'    => isset( $state['advanced']['author'] ) ? wp_list_pluck( (array) $state['advanced']['author'], 'value' ) : [],
                'publisher' => isset( $state['advanced']['publisher'] ) ? wp_list_pluck( (array) $state['advanced']['publisher'], 'value' ) : [],
                'source'    => isset( $state['advanced']['source'] ) ? wp_list_pluck( (array) $state['advanced']['source'], 'value' ) : [],
                'technique' => isset( $state['advanced']['technique'] ) ? wp_list_pluck( (array) $state['advanced']['technique'], 'value' ) : [],
            ],
        ],
    ];
}

function bw_ss_render_headless_discovery_toolbar( $settings, $state, $widget_id, $bootstrap_payload, $active_chips = [] ) {
    $default_category  = $state['category'];
    $result_count      = isset( $bootstrap_payload['result_count'] ) ? (int) $bootstrap_payload['result_count'] : 0;
    $result_label      = bw_ss_get_result_count_label( $result_count );
    $mobile_filters_title = __( 'Filters', 'bw-elementor-widgets' );
    $drawer_title         = __( 'Filters', 'bw-elementor-widgets' );
    $mobile_show_results  = __( 'Show results', 'bw-elementor-widgets' );
    $filter_icon_html     = '<svg class="bw-fpw-mobile-filter-button-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M10 5H3"/><path d="M12 19H3"/><path d="M14 3v4"/><path d="M16 17v4"/><path d="M21 12h-9"/><path d="M21 19h-5"/><path d="M21 5h-7"/><path d="M8 10v4"/><path d="M8 12H3"/></svg>';
    $sort_chevron_html     = function_exists( 'bw_fpw_get_discovery_sort_chevron_svg' ) ? bw_fpw_get_discovery_sort_chevron_svg() : '<svg class="bw-fpw-sort-trigger__chevron-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';
    $sort_check_html       = '<svg class="bw-fpw-sort-option__check-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>';
    $discovery_sort_options = function_exists( 'bw_fpw_get_discovery_sort_options' ) ? bw_fpw_get_discovery_sort_options() : [];
    $default_sort_key       = function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ? bw_fpw_get_discovery_sort_default_key() : 'newest';
    $default_sort_option    = isset( $discovery_sort_options[ $default_sort_key ] ) ? $discovery_sort_options[ $default_sort_key ] : [];
    $default_sort_label     = isset( $default_sort_option['trigger_label'] ) ? $default_sort_option['trigger_label'] : __( 'Default', 'bw-elementor-widgets' );
    $default_sort_icon_html = function_exists( 'bw_fpw_get_discovery_sort_icon_svg' ) ? bw_fpw_get_discovery_sort_icon_svg( $default_sort_key ) : '';
    ?>
    <div class="bw-fpw-discovery-toolbar bw-fpw-discovery-toolbar--search-results" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-ui-ready="false" style="opacity:0; visibility:hidden; transform:translateY(10px); pointer-events:none;">
        <div class="bw-fpw-visible-filters bw-fpw-visible-filters--search-results" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" aria-hidden="<?php echo ! empty( $settings['show_visible_filters'] ) ? 'false' : 'true'; ?>"></div>

        <div class="bw-fpw-discovery-toolbar__summary bw-fpw-discovery-toolbar__summary--search-results">
            <div class="bw-fpw-discovery-meta bw-fpw-discovery-meta--search-results" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                <span class="bw-fpw-discovery-result-count bw-fpw-discovery-result-count--search-results" data-widget-id="<?php echo esc_attr( $widget_id ); ?>"><?php echo esc_html( $result_label ); ?></span>
            </div>
        </div>

        <div class="bw-fpw-discovery-toolbar__controls bw-fpw-discovery-toolbar__controls--search-results">
            <div class="bw-fpw-mobile-filter bw-fpw-mobile-filter--search-results" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-category="<?php echo esc_attr( $default_category ); ?>">
                <button class="bw-fpw-mobile-filter-button bw-fpw-mobile-filter-trigger" type="button">
                    <span class="bw-fpw-mobile-filter-button-label"><?php echo esc_html( $mobile_filters_title ); ?></span>
                    <span class="bw-fpw-mobile-filter-button-icon-shell">
                        <?php echo $filter_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                </button>

                <div class="bw-fpw-mobile-filter-panel bw-fpw-mobile-filter-panel--drawer" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $drawer_title ); ?>">
                    <div class="bw-fpw-mobile-filter-drawer">
                        <div class="bw-fpw-mobile-filter-panel__header bw-fpw-mobile-filter-panel__header--drawer">
                            <div class="bw-fpw-mobile-filter-sheet-handle" aria-hidden="true">
                                <span class="bw-fpw-mobile-filter-sheet-handle-bar"></span>
                            </div>
                            <div class="bw-fpw-mobile-filter-drawer-title-row">
                                <span class="bw-fpw-mobile-filter-drawer-title"><?php echo esc_html( $drawer_title ); ?></span>
                                <button class="bw-fpw-drawer-clear-all bw-fpw-drawer-clear-all--header is-hidden" type="button" aria-hidden="true" data-widget-id="<?php echo esc_attr( $widget_id ); ?>"><?php esc_html_e( 'Clear all', 'bw-elementor-widgets' ); ?></button>
                            </div>
                        </div>

                        <div class="bw-fpw-mobile-filter-panel__body bw-fpw-mobile-filter-panel__body--drawer">
                            <div class="bw-fpw-drawer-content-shell" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                <div class="bw-fpw-active-chips bw-fpw-active-chips--drawer" data-widget-id="<?php echo esc_attr( $widget_id ); ?>"></div>
                                <div class="bw-fpw-drawer-groups" data-widget-id="<?php echo esc_attr( $widget_id ); ?>"></div>
                            </div>
                        </div>

                        <div class="bw-fpw-mobile-filter-panel__footer bw-fpw-mobile-filter-panel__footer--drawer">
                            <button class="bw-fpw-mobile-apply bw-fpw-mobile-apply--drawer" type="button"><?php echo esc_html( $mobile_show_results ); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $settings['show_order_by'] ) ) : ?>
                <div class="bw-fpw-sort bw-fpw-sort--<?php echo esc_attr( $settings['order_trigger_style'] ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                    <button class="bw-fpw-sort-trigger bw-fpw-sort-trigger--<?php echo esc_attr( $settings['order_trigger_style'] ); ?>" type="button" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" aria-haspopup="menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Change product order', 'bw-elementor-widgets' ); ?>">
                        <?php if ( 'dropdown' === $settings['order_trigger_style'] ) : ?>
                            <span class="bw-fpw-sort-trigger__content">
                                <span class="bw-fpw-sort-trigger__icon-shell" aria-hidden="true">
                                    <?php echo $default_sort_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                                <span class="bw-fpw-sort-trigger__label" data-sort-current-label><?php echo esc_html( $default_sort_label ); ?></span>
                            </span>
                            <span class="bw-fpw-sort-trigger__chevron" aria-hidden="true">
                                <?php echo $sort_chevron_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php else : ?>
                            <span class="bw-fpw-sort-trigger__icon-shell" aria-hidden="true">
                                <?php echo $default_sort_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="bw-fpw-sort-menu" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" role="menu" aria-hidden="true">
                        <?php foreach ( $discovery_sort_options as $sort_key => $sort_option ) : ?>
                            <button class="bw-fpw-sort-option<?php echo $default_sort_key === $sort_key ? ' is-selected' : ''; ?>" type="button" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-sort-key="<?php echo esc_attr( $sort_key ); ?>" role="menuitemradio" aria-checked="<?php echo $default_sort_key === $sort_key ? 'true' : 'false'; ?>">
                                <span class="bw-fpw-sort-option__label"><?php echo esc_html( isset( $sort_option['menu_label'] ) ? $sort_option['menu_label'] : '' ); ?></span>
                                <span class="bw-fpw-sort-option__check" aria-hidden="true"><?php echo $sort_check_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="bw-fpw-active-chips bw-fpw-quick-filters bw-fpw-quick-filters--search-results<?php echo empty( $active_chips ) ? ' is-empty' : ''; ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
            <?php bw_ss_render_initial_active_chips_markup( $active_chips ); ?>
        </div>
    </div>

    <div class="bw-fpw-filters bw-fpw-filters--drawer-state" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-category="<?php echo esc_attr( $default_category ); ?>"></div>
    <script type="application/json" class="bw-fpw-discovery-bootstrap" data-widget-id="<?php echo esc_attr( $widget_id ); ?>"><?php echo wp_json_encode( $bootstrap_payload ); ?></script>
    <?php
}

function bw_ss_render_initial_active_chips_markup( $chips ) {
    $chips = is_array( $chips ) ? $chips : [];

    foreach ( $chips as $chip ) {
        if ( empty( $chip['label'] ) || empty( $chip['url'] ) ) {
            continue;
        }
        ?>
        <a class="bw-fpw-active-chip bw-fpw-quick-filter is-selected" href="<?php echo esc_url( $chip['url'] ); ?>">
            <span class="bw-fpw-active-chip__label bw-fpw-quick-filter__label"><?php echo esc_html( $chip['label'] ); ?></span>
        </a>
        <?php
    }
}

function bw_ss_render_headless_product_grid( $args = [] ) {
    $state    = isset( $args['state'] ) && is_array( $args['state'] ) ? $args['state'] : [];
    $settings = bw_ss_normalize_headless_product_grid_settings( isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : [] );
    $widget_id = $settings['widget_id'];
    $results   = bw_ss_get_headless_product_grid_results( $state, $settings );
    $ui_result = $results['ui_result'];
    $requested_result = $results['requested_result'];
    $requested_request = $results['requested_request'];
    $render_request = $requested_request;
    $render_request['show_title']       = ! empty( $settings['show_title'] );
    $render_request['show_description'] = ! empty( $settings['show_description'] );
    $render_request['show_price']       = ! empty( $settings['show_price'] );
    $render_result = bw_fpw_render_product_grid_posts_html( $render_request, isset( $requested_result['page_post_ids'] ) ? (array) $requested_result['page_post_ids'] : [] );
    $grid_html = isset( $render_result['html'] ) ? (string) $render_result['html'] : '';

    if ( empty( $render_result['rendered_post_ids'] ) && 1 === (int) $requested_request['page'] ) {
        ob_start();
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php echo esc_html( bw_ss_get_empty_state_message( $state['query'] ) ); ?></p>
        </div>
        <?php
        $grid_html = ob_get_clean();
    }

    $bootstrap_payload = bw_ss_build_headless_discovery_bootstrap_payload( $state, $settings, $ui_result );
    $active_chips      = bw_ss_build_active_chip_links( $state );
    $has_more          = ! empty( $requested_result['has_more'] );
    $load_state_classes = [ 'bw-fpw-load-state' ];

    if ( empty( $settings['infinite_enabled'] ) ) {
        $load_state_classes[] = 'bw-fpw-load-state--disabled';
    } elseif ( ! $has_more ) {
        $load_state_classes[] = 'bw-fpw-load-state--complete';
    }

    $wrapper_style = sprintf(
        '--bw-fpw-max-width:%1$dpx; --bw-fpw-desktop-columns:%2$d; --bw-fpw-grid-column-gap:%3$dpx; --bw-fpw-grid-row-gap:%4$dpx; --bw-fpw-grid-column-gap-tablet:%5$dpx; --bw-fpw-grid-row-gap-tablet:%6$dpx; --bw-fpw-grid-column-gap-mobile:%7$dpx; --bw-fpw-grid-row-gap-mobile:%8$dpx;',
        $settings['container_max_width'],
        $settings['desktop_columns'],
        $settings['gap_x_desktop'],
        $settings['gap_y_desktop'],
        $settings['gap_x_tablet'],
        $settings['gap_y_tablet'],
        $settings['gap_x_mobile'],
        $settings['gap_y_mobile']
    );

    $grid_attributes = [
        'class'                            => 'bw-fpw-grid',
        'data-layout-mode'                 => $settings['layout_mode'],
        'data-masonry-effect'              => $settings['masonry_effect'],
        'data-widget-id'                   => $widget_id,
        'data-post-type'                   => $settings['post_type'],
        'data-context-slug'                => $state['context_slug'],
        'data-columns-desktop'             => $settings['desktop_columns'],
        'data-gap-x-desktop'               => $settings['gap_x_desktop'],
        'data-gap-y-desktop'               => $settings['gap_y_desktop'],
        'data-breakpoint-tablet-min'       => 768,
        'data-breakpoint-tablet-max'       => 1024,
        'data-columns-tablet'              => $settings['tablet_columns'],
        'data-gap-x-tablet'                => $settings['gap_x_tablet'],
        'data-gap-y-tablet'                => $settings['gap_y_tablet'],
        'data-breakpoint-mobile-max'       => 767,
        'data-columns-mobile'              => $settings['mobile_columns'],
        'data-gap-x-mobile'                => $settings['gap_x_mobile'],
        'data-gap-y-mobile'                => $settings['gap_y_mobile'],
        'data-image-size'                  => $settings['image_size'],
        'data-image-mode'                  => $settings['image_mode'],
        'data-hover-effect'                => $settings['hover_effect'] ? 'yes' : 'no',
        'data-open-cart-popup'             => $settings['open_cart_popup'] ? 'yes' : 'no',
        'data-show-title'                  => $settings['show_title'] ? 'yes' : 'no',
        'data-show-description'            => $settings['show_description'] ? 'yes' : 'no',
        'data-show-price'                  => $settings['show_price'] ? 'yes' : 'no',
        'data-search-enabled'              => $settings['show_search'] ? 'yes' : 'no',
        'data-show-order-by'               => $settings['show_order_by'] ? 'yes' : 'no',
        'data-show-visible-filters'        => $settings['show_visible_filters'] ? 'yes' : 'no',
        'data-desktop-filters-enabled'     => $settings['show_visible_filters'] ? 'yes' : 'no',
        'data-desktop-filter-groups'       => wp_json_encode( array_values( $settings['desktop_filter_groups'] ) ),
        'data-desktop-filter-order'        => wp_json_encode( array_values( $settings['desktop_filter_order'] ) ),
        'data-desktop-filter-icon-enabled' => $settings['show_desktop_filter_icon'] ? 'yes' : 'no',
        'data-order-trigger-style'         => $settings['order_trigger_style'],
        'data-default-sort-key'            => $settings['default_sort_key'],
        'data-order-by'                    => $settings['default_order_by'],
        'data-order'                       => $settings['default_order'],
        'data-default-order-by'            => $settings['default_order_by'],
        'data-default-order'               => $settings['default_order'],
        'data-specific-ids-mode'           => 'no',
        'data-initial-items'               => $settings['per_page'],
        'data-load-batch-size'             => $settings['per_page'],
        'data-per-page'                    => $settings['per_page'],
        'data-current-page'                => isset( $requested_result['page'] ) ? (int) $requested_result['page'] : 1,
        'data-next-page'                   => isset( $requested_result['next_page'] ) ? (int) $requested_result['next_page'] : 0,
        'data-loaded-count'                => isset( $requested_result['loaded_count'] ) ? (int) $requested_result['loaded_count'] : 0,
        'data-next-offset'                 => isset( $requested_result['next_offset'] ) ? (int) $requested_result['next_offset'] : 0,
        'data-has-more'                    => $has_more ? '1' : '0',
        'data-infinite-enabled'            => $settings['infinite_enabled'] ? 'yes' : 'no',
        'data-load-trigger-offset'         => $settings['load_trigger_offset'],
        'data-result-count'                => isset( $ui_result['result_count'] ) ? (int) $ui_result['result_count'] : 0,
    ];

    $grid_attr_html = '';
    foreach ( $grid_attributes as $attr => $value ) {
        if ( '' === $value && 0 !== $value ) {
            continue;
        }

        $grid_attr_html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( (string) $value ) );
    }

    ob_start();
    ?>
    <div class="bw-search-results-page__grid elementor-widget elementor-widget-bw-product-grid">
        <div class="elementor-widget-container">
            <div class="bw-product-grid-wrapper bw-fpw-layout-top bw-search-results-grid-wrapper" data-filter-breakpoint="<?php echo esc_attr( $settings['responsive_filter_breakpoint'] ); ?>" data-responsive-filter-mode="<?php echo esc_attr( $settings['responsive_filter_mode'] ? 'yes' : 'no' ); ?>" data-drawer-side="<?php echo esc_attr( $settings['drawer_side'] ); ?>">
                <?php bw_ss_render_headless_discovery_toolbar( $settings, $state, $widget_id, $bootstrap_payload, $active_chips ); ?>

                <div class="bw-product-grid" style="<?php echo esc_attr( $wrapper_style ); ?>" data-disable-hover-on-touch="<?php echo esc_attr( $settings['disable_hover_on_touch'] ? 'yes' : 'no' ); ?>">
                    <div<?php echo $grid_attr_html; ?>>
                        <?php echo $grid_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $load_state_classes ) ) ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-has-more="<?php echo $has_more ? '1' : '0'; ?>" aria-live="polite">
                        <div class="bw-fpw-load-indicator" role="status">
                            <span class="bw-fpw-load-indicator__spinner" aria-hidden="true"></span>
                            <span class="bw-fpw-load-indicator__label"><?php esc_html_e( 'Loading more', 'bw-elementor-widgets' ); ?></span>
                        </div>
                        <div class="bw-fpw-load-sentinel" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    return [
        'html'             => ob_get_clean(),
        'widget_id'        => $widget_id,
        'settings'         => $settings,
        'state'            => $state,
        'requested_result' => $requested_result,
        'ui_result'        => $ui_result,
    ];
}
