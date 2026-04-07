<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_subcategories()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $category_id = bw_fpw_normalize_term_selector(isset($_POST['category_id']) ? wp_unslash($_POST['category_id']) : 'all');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());

    if (bw_fpw_is_throttled_request('bw_fpw_get_subcategories')) {
        bw_fpw_send_throttled_response('bw_fpw_get_subcategories');
    }

    $transient_key = 'bw_fpw_subcats_' . $post_type . '_' . $category_id;
    $cached_result = get_transient($transient_key);

    if (false !== $cached_result) {
        wp_send_json_success($cached_result);
        return;
    }

    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

    $get_terms_args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ];

    if ('all' !== $category_id) {
        $get_terms_args['parent'] = $category_id;
    }

    $subcategories = get_terms($get_terms_args);

    if ('all' === $category_id && !is_wp_error($subcategories)) {
        $subcategories = array_filter(
            $subcategories,
            static function ($term) {
                return (int) $term->parent > 0;
            }
        );
    }

    if (is_wp_error($subcategories)) {
        wp_send_json_error(['message' => $subcategories->get_error_message()]);
    }

    $result = [];

    foreach ($subcategories as $subcat) {
        $result[] = [
            'term_id' => $subcat->term_id,
            'name' => $subcat->name,
            'count' => $subcat->count,
        ];
    }

    set_transient($transient_key, $result, 15 * MINUTE_IN_SECONDS);

    wp_send_json_success($result);
}

function bw_fpw_get_tags()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $category_id = bw_fpw_normalize_term_selector(isset($_POST['category_id']) ? wp_unslash($_POST['category_id']) : 'all');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());
    $subcategories = bw_fpw_normalize_int_array(isset($_POST['subcategories']) ? wp_unslash($_POST['subcategories']) : [], 50);
    $normalized_year_range = bw_fpw_normalize_year_range(
        isset($_POST['year_from']) ? wp_unslash($_POST['year_from']) : null,
        isset($_POST['year_to']) ? wp_unslash($_POST['year_to']) : null
    );
    $year_from = $normalized_year_range['from'];
    $year_to = $normalized_year_range['to'];

    if (bw_fpw_is_throttled_request('bw_fpw_get_tags')) {
        bw_fpw_send_throttled_response('bw_fpw_get_tags');
    }

    $subcategories_for_key = $subcategories;
    sort($subcategories_for_key, SORT_NUMERIC);
    $cache_params_hash = md5(wp_json_encode([
        'subcats' => $subcategories_for_key,
        'year_from' => $year_from,
        'year_to' => $year_to,
    ]));
    $transient_key = 'bw_fpw_tags_' . $post_type . '_' . $category_id . '_' . $cache_params_hash;
    $cached_result = get_transient($transient_key);

    if (false !== $cached_result) {
        wp_send_json_success($cached_result);
        return;
    }

    $tags = bw_fpw_get_related_tags_data($post_type, $category_id, $subcategories, '', $year_from, $year_to);

    if (empty($tags)) {
        set_transient($transient_key, [], 15 * MINUTE_IN_SECONDS);
        wp_send_json_success([]);
        return;
    }

    set_transient($transient_key, $tags, 15 * MINUTE_IN_SECONDS);

    wp_send_json_success($tags);
}

function bw_fpw_ajax_refresh_nonce()
{
    wp_send_json_success(['nonce' => wp_create_nonce('bw_fpw_nonce')]);
}

function bw_fpw_filter_posts()
{
    try {
        bw_fpw_filter_posts_inner();
    } catch (\Throwable $e) {
        error_log('[bw_fpw_filter_posts] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(['message' => 'server_error', 'debug' => $e->getMessage()]);
    }
}

function bw_fpw_filter_posts_inner()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $widget_id = bw_fpw_normalize_widget_id(isset($_POST['widget_id']) ? wp_unslash($_POST['widget_id']) : '');

    if (bw_fpw_is_throttled_request('bw_fpw_filter_posts')) {
        bw_fpw_send_throttled_response('bw_fpw_filter_posts', $widget_id);
    }

    $request = bw_fpw_build_engine_request($_POST);
    $engine_result = bw_fpw_execute_search($request);
    $render_result = bw_fpw_render_product_grid_posts_html($request, isset($engine_result['page_post_ids']) ? (array) $engine_result['page_post_ids'] : []);
    $html = $render_result['html'];

    if (empty($render_result['rendered_post_ids']) && 1 === (int) $request['page']) {
        ob_start();
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php echo esc_html(bw_fpw_get_empty_state_message($request['subcategories'], $request['tags'], $request['search'])); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr($request['widget_id']); ?>">
                <?php esc_html_e('RESET FILTERS', 'bw-elementor-widgets'); ?>
            </button>
        </div>
        <?php
        $html = ob_get_clean();
    }

    $response_data = [
        'html' => $html,
        'result_count' => $engine_result['result_count'],
        'has_posts' => !empty($render_result['rendered_post_ids']),
        'page' => $engine_result['page'],
        'per_page' => $engine_result['per_page'],
        'has_more' => $engine_result['has_more'],
        'next_page' => $engine_result['next_page'],
        'offset' => $engine_result['offset'],
        'loaded_count' => $engine_result['loaded_count'],
        'next_offset' => $engine_result['next_offset'],
    ];

    if (empty($engine_result['is_append'])) {
        $response_data['tags_html'] = isset($engine_result['tags_html']) ? (string) $engine_result['tags_html'] : '';
        $response_data['available_tags'] = isset($engine_result['available_tags']) ? bw_fpw_normalize_array_for_cache_key($engine_result['available_tags']) : [];
        $response_data['available_types'] = isset($engine_result['available_types']) ? bw_fpw_normalize_array_for_cache_key($engine_result['available_types']) : [];
        $response_data['filter_ui'] = isset($engine_result['filter_ui']) && is_array($engine_result['filter_ui']) ? $engine_result['filter_ui'] : [];

        $filter_ui_hashes = bw_fpw_build_filter_ui_hashes($response_data['filter_ui']);
        $response_data['filter_ui_hashes'] = $filter_ui_hashes;

        if (!empty($request['client_filter_ui_hashes']) && !empty($response_data['filter_ui']) && is_array($response_data['filter_ui'])) {
            $changed_filter_ui = [];

            foreach ($response_data['filter_ui'] as $section_key => $section_value) {
                if (
                    !isset($filter_ui_hashes[$section_key])
                    || !isset($request['client_filter_ui_hashes'][$section_key])
                    || $request['client_filter_ui_hashes'][$section_key] !== $filter_ui_hashes[$section_key]
                ) {
                    $changed_filter_ui[$section_key] = $section_value;
                }
            }

            $response_data['filter_ui'] = $changed_filter_ui;

            if (isset($filter_ui_hashes['tags']) && isset($request['client_filter_ui_hashes']['tags']) && $request['client_filter_ui_hashes']['tags'] === $filter_ui_hashes['tags']) {
                unset($response_data['tags_html'], $response_data['available_tags']);
            }

            if (isset($filter_ui_hashes['types']) && isset($request['client_filter_ui_hashes']['types']) && $request['client_filter_ui_hashes']['types'] === $filter_ui_hashes['types']) {
                unset($response_data['available_types']);
            }
        }
    }

    wp_send_json_success($response_data);
}

function bw_fpw_get_empty_state_message($subcategories = [], $tags = [], $search = '')
{
    return 'No results found.';
}

function bw_fpw_render_product_grid_posts_html($request, $page_post_ids)
{
    $page_post_ids = array_values(array_filter(array_map('absint', (array) $page_post_ids)));
    $post_type = $request['post_type'];
    $per_page = (int) $request['per_page'];
    $page = (int) $request['page'];
    $offset = (int) $request['offset'];
    $image_loading = ($page > 1 || $offset > 0) ? 'lazy' : 'eager';
    $query = new WP_Query([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => max(1, count($page_post_ids)),
        'post__in' => !empty($page_post_ids) ? $page_post_ids : [0],
        'orderby' => 'post__in',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);
    $rendered_posts = 0;
    $rendered_post_ids = [];

    ob_start();

    if (!empty($page_post_ids) && $query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            if ($per_page > 0 && $rendered_posts >= $per_page) {
                break;
            }

            $post_id = get_the_ID();

            if (
                'product' === $post_type
                && class_exists('BW_Product_Card_Component')
                && function_exists('wc_get_product')
            ) {
                $product = wc_get_product($post_id);

                if ($product) {
                    echo BW_Product_Card_Component::render(
                        $product,
                        [
                            'image_size' => $request['image_size'],
                            'image_mode' => $request['image_mode'],
                            'image_loading' => $image_loading,
                            'hover_image_loading' => 'lazy',
                            'show_image' => $request['image_toggle'],
                            'show_hover_image' => $request['image_toggle'] && $request['hover_effect'],
                            'hover_image_source' => 'meta',
                            'show_title' => true,
                            'show_description' => true,
                            'description_mode' => 'auto',
                            'show_price' => true,
                            'show_buttons' => true,
                            'show_add_to_cart' => true,
                            'open_cart_popup' => $request['open_cart_popup'],
                            'wrapper_classes' => 'bw-fpw-item',
                            'card_classes' => 'bw-fpw-card',
                            'media_classes' => 'bw-fpw-media',
                            'media_link_classes' => 'bw-fpw-media-link',
                            'image_wrapper_classes' => 'bw-fpw-image',
                            'content_classes' => 'bw-fpw-content bw-slider-content',
                            'title_classes' => 'bw-fpw-title',
                            'description_classes' => 'bw-fpw-description',
                            'price_classes' => 'bw-fpw-price price',
                            'overlay_classes' => 'bw-fpw-overlay overlay-buttons has-buttons',
                            'overlay_buttons_classes' => 'bw-fpw-overlay-buttons',
                            'view_button_classes' => 'bw-fpw-overlay-button overlay-button overlay-button--view',
                            'cart_button_classes' => 'bw-fpw-overlay-button overlay-button overlay-button--cart bw-btn-addtocart',
                            'placeholder_classes' => 'bw-fpw-image-placeholder',
                        ]
                    );
                    $rendered_post_ids[] = $post_id;
                    $rendered_posts++;
                    continue;
                }
            }

            $permalink = get_permalink($post_id);
            $title = get_the_title($post_id);
            $excerpt = get_the_excerpt($post_id);

            if (empty($excerpt)) {
                $excerpt = wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $post_id)), 30);
            }

            if (!empty($excerpt) && false === strpos($excerpt, '<p')) {
                $excerpt = '<p>' . $excerpt . '</p>';
            }

            $thumbnail_html = '';

            if ($request['image_toggle'] && has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);

                if ($thumbnail_id) {
                    $thumbnail_html = wp_get_attachment_image(
                        $thumbnail_id,
                        $request['image_size'],
                        false,
                        [
                            'loading' => $image_loading,
                            'class' => 'bw-slider-main',
                        ]
                    );
                }
            }

            $hover_image_html = '';
            if ($request['hover_effect'] && 'product' === $post_type) {
                $hover_image_id = (int) get_post_meta($post_id, '_bw_slider_hover_image', true);

                if ($hover_image_id) {
                    $hover_image_html = wp_get_attachment_image(
                        $hover_image_id,
                        $request['image_size'],
                        false,
                        [
                            'class' => 'bw-slider-hover',
                            'loading' => $image_loading,
                        ]
                    );
                }
            }

            $price_html = '';
            $has_add_to_cart = false;
            $add_to_cart_url = '';

            if ('product' === $post_type) {
                $price_html = bw_fpw_get_price_markup($post_id);

                if (function_exists('wc_get_product')) {
                    $product = wc_get_product($post_id);

                    if ($product) {
                        if ($product->is_type('variable')) {
                            $add_to_cart_url = $permalink;
                        } else {
                            $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';

                            if ($cart_url) {
                                $add_to_cart_url = add_query_arg('add-to-cart', $product->get_id(), $cart_url);
                            }
                        }

                        if (!$add_to_cart_url) {
                            $add_to_cart_url = $permalink;
                        }

                        $has_add_to_cart = true;
                    }
                }
            }

            $view_label = 'product' === $post_type
                ? esc_html__('View Product', 'bw-elementor-widgets')
                : esc_html__('Read More', 'bw-elementor-widgets');
            ?>
            <article <?php post_class('bw-fpw-item'); ?>>
                <div class="bw-fpw-card">
                    <div class="bw-slider-image-container">
                        <?php
                        $media_classes = ['bw-fpw-media'];
                        if (!$thumbnail_html) {
                            $media_classes[] = 'bw-fpw-media--placeholder';
                        }
                        ?>
                        <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $media_classes))); ?>">
                            <?php if ($thumbnail_html): ?>
                                <a class="bw-fpw-media-link" href="<?php echo esc_url($permalink); ?>">
                                    <div
                                        class="bw-fpw-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-fpw-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                        <?php echo wp_kses_post($thumbnail_html); ?>
                                        <?php if ($hover_image_html): ?>
                                            <?php echo wp_kses_post($hover_image_html); ?>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="bw-fpw-overlay overlay-buttons has-buttons">
                                    <div
                                        class="bw-fpw-overlay-buttons<?php echo $has_add_to_cart ? ' bw-fpw-overlay-buttons--double' : ''; ?>">
                                        <a class="bw-fpw-overlay-button overlay-button overlay-button--view"
                                            href="<?php echo esc_url($permalink); ?>">
                                            <span
                                                class="bw-fpw-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                        </a>
                                        <?php if ('product' === $post_type && $has_add_to_cart && $add_to_cart_url): ?>
                                            <a class="bw-fpw-overlay-button overlay-button overlay-button--cart"
                                                href="<?php echo esc_url($add_to_cart_url); ?>" <?php echo $request['open_cart_popup'] ? ' data-open-cart-popup="1"' : ''; ?>>
                                                <span
                                                    class="bw-fpw-overlay-button__label overlay-button__label"><?php esc_html_e('Add to Cart', 'bw-elementor-widgets'); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="bw-fpw-image-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bw-fpw-content bw-slider-content">
                        <h3 class="bw-fpw-title">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php echo esc_html($title); ?>
                            </a>
                        </h3>

                        <?php if (!empty($excerpt)): ?>
                            <div class="bw-fpw-description"><?php echo wp_kses_post($excerpt); ?></div>
                        <?php endif; ?>

                        <?php if ($price_html): ?>
                            <div class="bw-fpw-price price"><?php echo wp_kses_post($price_html); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php
            $rendered_post_ids[] = $post_id;
            $rendered_posts++;
        }
    }

    wp_reset_postdata();

    return [
        'html' => ob_get_clean(),
        'rendered_post_ids' => $rendered_post_ids,
    ];
}

function bw_fpw_get_price_markup($post_id)
{
    if (!$post_id) {
        return '';
    }

    $format_price = static function ($value) {
        if ('' === $value || null === $value) {
            return '';
        }

        if (function_exists('wc_price') && is_numeric($value)) {
            return wc_price($value);
        }

        if (is_numeric($value)) {
            $value = number_format_i18n((float) $value, 2);
        }

        return esc_html($value);
    };

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($post_id);
        if ($product) {
            $price_html = $product->get_price_html();
            if (!empty($price_html)) {
                return $price_html;
            }

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $current_price = $product->get_price();

            $regular_markup = $format_price($regular_price);
            $sale_markup = $format_price($sale_price);
            $current_markup = $format_price($current_price);

            if ($sale_markup && $regular_markup && $sale_markup !== $regular_markup) {
                return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                    '<span class="price-sale">' . $sale_markup . '</span>';
            }

            if ($current_markup) {
                return '<span class="price-regular">' . $current_markup . '</span>';
            }
        }
    }

    $regular_price = get_post_meta($post_id, '_regular_price', true);
    $sale_price = get_post_meta($post_id, '_sale_price', true);
    $current_price = get_post_meta($post_id, '_price', true);

    if ('' === $current_price && '' === $regular_price && '' === $sale_price) {
        $additional_keys = ['price', 'product_price'];
        foreach ($additional_keys as $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            if ('' !== $meta_value && null !== $meta_value) {
                $current_price = $meta_value;
                break;
            }
        }
    }

    $regular_markup = $format_price($regular_price);
    $sale_markup = $format_price($sale_price);
    $current_markup = $format_price($current_price);

    if ($sale_markup && $regular_markup && $sale_markup !== $regular_markup) {
        return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
            '<span class="price-sale">' . $sale_markup . '</span>';
    }

    if ($current_markup) {
        return '<span class="price-regular">' . $current_markup . '</span>';
    }

    if ($regular_markup) {
        return '<span class="price-regular">' . $regular_markup . '</span>';
    }

    return '';
}
