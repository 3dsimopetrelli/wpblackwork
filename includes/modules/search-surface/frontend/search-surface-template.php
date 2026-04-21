<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_should_enqueue_frontend_assets() {
    if ( is_admin() ) {
        return false;
    }

    if ( bw_ss_is_search_results_request() ) {
        return true;
    }

    if ( function_exists( 'bw_header_is_enabled' ) && bw_header_is_enabled() ) {
        return ! function_exists( 'bw_header_is_elementor_preview' ) || ! bw_header_is_elementor_preview();
    }

    return false;
}

function bw_ss_get_overlay_scope_options() {
    $options = [];

    foreach ( bw_ss_get_scope_definitions() as $scope_key => $scope_def ) {
        $options[ $scope_key ] = (string) $scope_def['label'];
    }

    return $options;
}

function bw_ss_get_overlay_sidebar_groups_map() {
    $group_definitions = bw_ss_get_group_definitions();
    $map               = [];

    foreach ( bw_ss_get_scope_definitions() as $scope_key => $scope_def ) {
        $groups = [];

        foreach ( $scope_def['groups'] as $group_key ) {
            if ( ! isset( $group_definitions[ $group_key ] ) ) {
                continue;
            }

            $groups[] = [
                'key'   => $group_key,
                'label' => (string) $group_definitions[ $group_key ]['label'],
            ];
        }

        $map[ $scope_key ] = $groups;
    }

    return $map;
}

function bw_ss_get_overlay_sidebar_icon_svg( $group_key ) {
    $group_key   = sanitize_key( (string) $group_key );
    $definitions = bw_ss_get_group_definitions();

    return isset( $definitions[ $group_key ] ) ? (string) $definitions[ $group_key ]['icon_svg'] : '';
}

function bw_ss_enqueue_frontend_assets() {
    if ( ! bw_ss_should_enqueue_frontend_assets() ) {
        return;
    }

    $base_path = BW_MEW_PATH . 'includes/modules/search-surface/frontend/';
    $base_url  = BW_MEW_URL . 'includes/modules/search-surface/frontend/';

    wp_enqueue_style(
        'bw-search-surface-style',
        $base_url . 'search-surface.css',
        wp_style_is( 'bw-product-labels-style', 'registered' ) ? [ 'bw-product-labels-style' ] : [],
        filemtime( $base_path . 'search-surface.css' ) ?: '1.0.0'
    );

    wp_enqueue_script(
        'bw-search-surface-script',
        $base_url . 'search-surface.js',
        [],
        filemtime( $base_path . 'search-surface.js' ) ?: '1.0.0',
        true
    );

    wp_localize_script(
        'bw-search-surface-script',
        'bwSearchSurfaceConfig',
        [
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'bw_ss_overlay_nonce' ),
            'searchResultsUrl' => bw_ss_get_search_results_url(),
            'scopeOptions'     => bw_ss_get_overlay_scope_options(),
            'sidebarGroups'    => bw_ss_get_overlay_sidebar_groups_map(),
            'groupIcons'       => bw_ss_get_group_icon_map(),
            'strings'          => [
                'searchActionLabel'   => __( 'Search for', 'bw-elementor-widgets' ),
                'searchActionHint'    => __( 'Enter', 'bw-elementor-widgets' ),
                'emptyBrowse'         => __( 'No values are available for this filter.', 'bw-elementor-widgets' ),
                'emptySuggestions'    => __( 'No matching products found.', 'bw-elementor-widgets' ),
                'emptyFeed'           => __( 'No products available right now.', 'bw-elementor-widgets' ),
                'loading'             => __( 'Loading…', 'bw-elementor-widgets' ),
                'seeAllResults'       => __( 'See all results', 'bw-elementor-widgets' ),
                'modeLabelTrending'   => __( 'Selects', 'bw-elementor-widgets' ),
                'modeLabelNew'        => __( 'New Arrivals', 'bw-elementor-widgets' ),
                'modeLabelSale'       => __( 'On Sale', 'bw-elementor-widgets' ),
                'modeLabelFree'       => __( 'Free Downloads', 'bw-elementor-widgets' ),
                'filterGroupCategories' => __( 'Categories', 'bw-elementor-widgets' ),
                'filterGroupTags'       => __( 'Style / Subject', 'bw-elementor-widgets' ),
                'filterGroupYear'       => __( 'Year', 'bw-elementor-widgets' ),
                'filterYearFrom'        => __( 'From', 'bw-elementor-widgets' ),
                'filterYearTo'          => __( 'To', 'bw-elementor-widgets' ),
                'filterYearAny'         => __( 'Any year', 'bw-elementor-widgets' ),
                'filterResultCount'     => __( '%d results', 'bw-elementor-widgets' ),
                'filterClearAll'        => __( 'Clear all', 'bw-elementor-widgets' ),
                'filterShowResults'     => __( 'Show results', 'bw-elementor-widgets' ),
                'filterEmpty'           => __( 'No filters available for this scope.', 'bw-elementor-widgets' ),
            ],
        ]
    );
}

function bw_ss_render_overlay_sidebar_items( $scope = 'all' ) {
    $scope      = bw_ss_normalize_scope_param( $scope );
    $groups_map = bw_ss_get_overlay_sidebar_groups_map();
    $groups     = isset( $groups_map[ $scope ] ) ? $groups_map[ $scope ] : $groups_map['all'];

    foreach ( $groups as $index => $group ) {
        $group_key = isset( $group['key'] ) ? sanitize_key( $group['key'] ) : '';
        $label     = isset( $group['label'] ) ? (string) $group['label'] : $group_key;

        if ( '' === $group_key ) {
            continue;
        }
        ?>
        <button
            class="bw-search-surface__nav-item<?php echo 0 === $index ? ' is-active' : ''; ?>"
            type="button"
            data-bw-search-group="<?php echo esc_attr( $group_key ); ?>"
        >
            <span class="bw-search-surface__nav-icon" aria-hidden="true">
                <?php echo bw_ss_get_overlay_sidebar_icon_svg( $group_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </span>
            <span class="bw-search-surface__nav-label"><?php echo esc_html( $label ); ?></span>
        </button>
        <?php
    }
}

function bw_ss_render_search_surface_template( $overlay_args = [] ) {
    $widget_id   = isset( $overlay_args['widget_id'] ) ? sanitize_key( (string) $overlay_args['widget_id'] ) : wp_generate_uuid4();
    $placeholder = isset( $overlay_args['placeholder'] ) && '' !== (string) $overlay_args['placeholder']
        ? (string) $overlay_args['placeholder']
        : __( 'Search Blackwork...', 'bw-elementor-widgets' );

    ob_start();
    ?>
    <div class="bw-search-surface" data-bw-search-surface data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-scope="all">
        <div class="bw-search-surface__backdrop" data-bw-search-close></div>
        <div class="bw-search-surface__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'bw-elementor-widgets' ); ?>">
            <div class="bw-search-surface__topbar">
                <div class="bw-search-surface__search-row">
                    <form class="bw-search-surface__form" data-bw-search-form method="get" action="<?php echo esc_url( bw_ss_get_search_results_url() ); ?>">
                        <label class="bw-search-surface__input-shell">
                            <span class="bw-search-surface__search-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="M20 20L16.65 16.65"></path>
                                </svg>
                            </span>
                            <input
                                type="search"
                                class="bw-search-surface__input"
                                data-bw-search-input
                                name="q"
                                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                autocomplete="off"
                            />
                        </label>
                        <input type="hidden" name="scope" value="all" data-bw-search-scope-input />
                    </form>

                    <button class="bw-search-surface__close" type="button" data-bw-search-close aria-label="<?php esc_attr_e( 'Close search', 'bw-elementor-widgets' ); ?>">
                        <span class="bw-search-surface__close-label"><?php esc_html_e( 'Close', 'bw-elementor-widgets' ); ?></span>
                    </button>
                </div>

                <div class="bw-search-surface__scope-row" data-bw-search-scope>
                    <?php foreach ( bw_ss_get_overlay_scope_options() as $scope_key => $scope_item_label ) : ?>
                        <button class="bw-search-surface__scope-option<?php echo 'all' === $scope_key ? ' is-selected' : ''; ?>" type="button" data-bw-scope-option="<?php echo esc_attr( $scope_key ); ?>" aria-pressed="<?php echo 'all' === $scope_key ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $scope_item_label ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bw-search-surface__body">
                <aside class="bw-search-surface__sidebar" data-bw-search-sidebar>
                    <?php bw_ss_render_overlay_sidebar_items( 'all' ); ?>
                </aside>

                <section class="bw-search-surface__main">
                    <div class="bw-search-surface__content-header" data-bw-search-content-header hidden></div>
                    <div class="bw-search-surface__content" data-bw-search-content aria-live="polite">
                        <div class="bw-search-surface__empty"><?php esc_html_e( 'Loading…', 'bw-elementor-widgets' ); ?></div>
                    </div>
                    <div class="bw-search-surface__filter-footer" data-bw-filter-footer hidden>
                        <div class="bw-search-surface__filter-footer-inner">
                            <div class="bw-search-surface__filter-meta">
                                <span class="bw-search-surface__filter-count" data-bw-filter-count></span>
                            </div>
                            <button class="bw-search-surface__filter-apply" type="button" data-bw-filter-apply><?php esc_html_e( 'Show results', 'bw-elementor-widgets' ); ?></button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}
