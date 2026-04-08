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
    return [
        'all'                 => bw_ss_get_scope_label( 'all' ),
        'digital-collections' => bw_ss_get_scope_label( 'digital-collections' ),
        'books'               => bw_ss_get_scope_label( 'books' ),
        'prints'              => bw_ss_get_scope_label( 'prints' ),
    ];
}

function bw_ss_get_overlay_sidebar_groups_map() {
    return [
        'all'                 => [
            [ 'key' => 'trending', 'label' => __( 'Trending', 'bw-elementor-widgets' ) ],
            [ 'key' => 'categories', 'label' => __( 'Categories', 'bw-elementor-widgets' ) ],
            [ 'key' => 'tags', 'label' => __( 'Style / Subject', 'bw-elementor-widgets' ) ],
            [ 'key' => 'years', 'label' => __( 'Year', 'bw-elementor-widgets' ) ],
        ],
        'digital-collections' => [
            [ 'key' => 'trending', 'label' => __( 'Trending', 'bw-elementor-widgets' ) ],
            [ 'key' => 'categories', 'label' => __( 'Categories', 'bw-elementor-widgets' ) ],
            [ 'key' => 'source', 'label' => __( 'Sources', 'bw-elementor-widgets' ) ],
            [ 'key' => 'technique', 'label' => __( 'Technique', 'bw-elementor-widgets' ) ],
            [ 'key' => 'years', 'label' => __( 'Year', 'bw-elementor-widgets' ) ],
        ],
        'books'               => [
            [ 'key' => 'trending', 'label' => __( 'Trending', 'bw-elementor-widgets' ) ],
            [ 'key' => 'categories', 'label' => __( 'Categories', 'bw-elementor-widgets' ) ],
            [ 'key' => 'author', 'label' => __( 'Authors', 'bw-elementor-widgets' ) ],
            [ 'key' => 'publisher', 'label' => __( 'Publisher', 'bw-elementor-widgets' ) ],
            [ 'key' => 'years', 'label' => __( 'Year', 'bw-elementor-widgets' ) ],
        ],
        'prints'              => [
            [ 'key' => 'trending', 'label' => __( 'Trending', 'bw-elementor-widgets' ) ],
            [ 'key' => 'categories', 'label' => __( 'Categories', 'bw-elementor-widgets' ) ],
            [ 'key' => 'artist', 'label' => __( 'Artists', 'bw-elementor-widgets' ) ],
            [ 'key' => 'technique', 'label' => __( 'Technique', 'bw-elementor-widgets' ) ],
            [ 'key' => 'years', 'label' => __( 'Year', 'bw-elementor-widgets' ) ],
        ],
    ];
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
        [],
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
            'searchResultsUrl' => home_url( '/search/' ),
            'scopeOptions'     => bw_ss_get_overlay_scope_options(),
            'sidebarGroups'    => bw_ss_get_overlay_sidebar_groups_map(),
            'strings'          => [
                'searchActionLabel'   => __( 'Search for', 'bw-elementor-widgets' ),
                'searchActionHint'    => __( 'Enter', 'bw-elementor-widgets' ),
                'suggestionsTitle'    => __( 'Suggested products', 'bw-elementor-widgets' ),
                'trendingTitle'       => __( 'Trending', 'bw-elementor-widgets' ),
                'browsePlaceholder'   => __( 'Facet browsing will be expanded in the next milestone.', 'bw-elementor-widgets' ),
                'previewTitle'        => __( 'Preview', 'bw-elementor-widgets' ),
                'previewBody'         => __( 'Start typing to search, or browse a group to refine the next view.', 'bw-elementor-widgets' ),
                'emptySuggestions'    => __( 'No matching products found.', 'bw-elementor-widgets' ),
                'emptyTrending'       => __( 'No curated products are available right now.', 'bw-elementor-widgets' ),
                'loading'             => __( 'Loading…', 'bw-elementor-widgets' ),
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
    $scope_label = bw_ss_get_scope_label( 'all' );

    ob_start();
    ?>
    <div class="bw-search-surface" data-bw-search-surface data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-default-scope="all">
        <div class="bw-search-surface__backdrop" data-bw-search-close></div>
        <div class="bw-search-surface__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'bw-elementor-widgets' ); ?>">
            <div class="bw-search-surface__topbar">
                <form class="bw-search-surface__form" data-bw-search-form>
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
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            autocomplete="off"
                        />
                    </label>
                </form>

                <div class="bw-search-surface__scope" data-bw-search-scope>
                    <button class="bw-search-surface__scope-trigger" type="button" data-bw-scope-toggle aria-haspopup="menu" aria-expanded="false">
                        <span data-bw-scope-current><?php echo esc_html( $scope_label ); ?></span>
                        <span class="bw-search-surface__scope-chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 9L12 15L18 9"></path>
                            </svg>
                        </span>
                    </button>

                    <div class="bw-search-surface__scope-menu" data-bw-scope-menu role="menu" aria-hidden="true">
                        <?php foreach ( bw_ss_get_overlay_scope_options() as $scope_key => $scope_item_label ) : ?>
                            <button class="bw-search-surface__scope-option<?php echo 'all' === $scope_key ? ' is-selected' : ''; ?>" type="button" role="menuitemradio" aria-checked="<?php echo 'all' === $scope_key ? 'true' : 'false'; ?>" data-bw-scope-option="<?php echo esc_attr( $scope_key ); ?>">
                                <?php echo esc_html( $scope_item_label ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="bw-search-surface__close" type="button" data-bw-search-close aria-label="<?php esc_attr_e( 'Close search', 'bw-elementor-widgets' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 6L18 18"></path>
                        <path d="M18 6L6 18"></path>
                    </svg>
                </button>
            </div>

            <div class="bw-search-surface__body">
                <aside class="bw-search-surface__sidebar" data-bw-search-sidebar>
                    <?php bw_ss_render_overlay_sidebar_items( 'all' ); ?>
                </aside>

                <section class="bw-search-surface__main">
                    <div class="bw-search-surface__section-header">
                        <h2 class="bw-search-surface__section-title" data-bw-search-title><?php esc_html_e( 'Trending', 'bw-elementor-widgets' ); ?></h2>
                    </div>

                    <div class="bw-search-surface__content" data-bw-search-content>
                        <div class="bw-search-surface__empty"><?php esc_html_e( 'Loading…', 'bw-elementor-widgets' ); ?></div>
                    </div>
                </section>

                <aside class="bw-search-surface__preview" data-bw-search-preview>
                    <div class="bw-search-surface__preview-card">
                        <h3 class="bw-search-surface__preview-title"><?php esc_html_e( 'Preview', 'bw-elementor-widgets' ); ?></h3>
                        <p class="bw-search-surface__preview-copy"><?php esc_html_e( 'Start typing to search, or browse a group to refine the next view.', 'bw-elementor-widgets' ); ?></p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}
