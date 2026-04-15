<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BW_Duplicate_Page
 *
 * Adds a "Duplicate" row action to Pages (and optionally Posts) in the
 * WordPress admin list screen. The duplicate copies:
 *   - All standard WordPress post fields (title, content, template, …)
 *   - All post meta — including every Elementor meta key — except
 *     ephemeral keys that must not be transferred (_edit_lock,
 *     _edit_last, _elementor_css).
 *   - All taxonomy terms attached to the original.
 *
 * The duplicate is always created as a Draft so it is never accidentally
 * published before the editor reviews it. After duplication the browser
 * is redirected to the new post's edit screen.
 */
class BW_Duplicate_Page
{
    /** Meta keys that must never be copied to the duplicate. */
    private const SKIP_META = [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_old_date',
        '_elementor_css',   // CSS cache — Elementor regenerates on first save
    ];

    public function __construct()
    {
        add_filter('page_row_actions', [$this, 'add_row_action'], 10, 2);
        add_filter('post_row_actions', [$this, 'add_row_action'], 10, 2);
        add_action('admin_action_bw_duplicate_page', [$this, 'handle_duplicate']);
        add_action('admin_notices', [$this, 'maybe_show_notice']);
    }

    // -------------------------------------------------------------------------
    // Row action
    // -------------------------------------------------------------------------

    /**
     * Appends a "Duplicate" link to the row actions in the post/page list.
     *
     * @param array    $actions Existing row actions.
     * @param \WP_Post $post    Current post object.
     * @return array
     */
    public function add_row_action(array $actions, \WP_Post $post): array
    {
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $url = $this->build_action_url($post->ID);
        $actions['bw_duplicate'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Duplicate', 'bw')
        );

        return $actions;
    }

    // -------------------------------------------------------------------------
    // Duplicate handler
    // -------------------------------------------------------------------------

    /**
     * Processes the duplicate request, then redirects to the new post editor.
     */
    public function handle_duplicate(): void
    {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

        if ($post_id <= 0) {
            wp_die(esc_html__('Invalid post.', 'bw'));
        }

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bw_duplicate_page_' . $post_id)
        ) {
            wp_die(esc_html__('Security check failed.', 'bw'));
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('Insufficient permissions.', 'bw'));
        }

        $original = get_post($post_id);
        if (!$original instanceof \WP_Post) {
            wp_die(esc_html__('Post not found.', 'bw'));
        }

        $new_id = $this->duplicate_post($original);

        if (is_wp_error($new_id)) {
            wp_die(esc_html($new_id->get_error_message()));
        }

        wp_safe_redirect(
            add_query_arg(
                ['action' => 'edit', 'post' => $new_id, 'bw_duplicated' => 1],
                admin_url('post.php')
            )
        );
        exit;
    }

    // -------------------------------------------------------------------------
    // Admin notice
    // -------------------------------------------------------------------------

    public function maybe_show_notice(): void
    {
        if (!isset($_GET['bw_duplicated'])) {
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__('Page duplicated successfully. You are now editing the copy.', 'bw')
            . '</p></div>';
    }

    // -------------------------------------------------------------------------
    // Core duplication logic
    // -------------------------------------------------------------------------

    /**
     * Creates a full copy of $original and returns the new post ID.
     *
     * @param  \WP_Post $original
     * @return int|\WP_Error  New post ID on success.
     */
    private function duplicate_post(\WP_Post $original)
    {
        $new_id = wp_insert_post(
            $this->build_post_args($original),
            true // return WP_Error on failure
        );

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        $this->copy_meta($original->ID, $new_id);
        $this->copy_taxonomies($original, $new_id);

        return $new_id;
    }

    /**
     * Builds the wp_insert_post() argument array for the duplicate.
     *
     * @param  \WP_Post $original
     * @return array
     */
    private function build_post_args(\WP_Post $original): array
    {
        return [
            'post_title'     => $original->post_title . ' ' . __('(Copy)', 'bw'),
            'post_content'   => $original->post_content,
            'post_excerpt'   => $original->post_excerpt,
            'post_status'    => 'draft',
            'post_type'      => $original->post_type,
            'post_author'    => get_current_user_id(),
            'post_parent'    => $original->post_parent,
            'menu_order'     => $original->menu_order,
            'comment_status' => $original->comment_status,
            'ping_status'    => $original->ping_status,
            'to_ping'        => $original->to_ping,
            'post_password'  => $original->post_password,
        ];
    }

    /**
     * Copies all eligible post meta from the original to the duplicate.
     * Handles both scalar and serialised values correctly.
     *
     * Elementor-specific keys copied:
     *   _elementor_data           – the full JSON layout
     *   _elementor_edit_mode      – 'builder'
     *   _elementor_template_type  – 'wp-page', etc.
     *   _elementor_version        – Elementor version string
     *   _elementor_page_settings  – per-page Elementor settings
     *
     * Elementor-specific keys intentionally skipped:
     *   _elementor_css            – CSS cache, regenerated automatically
     *
     * @param int $from_id  Source post ID.
     * @param int $to_id    Destination post ID.
     */
    private function copy_meta(int $from_id, int $to_id): void
    {
        $all_meta = get_post_meta($from_id);

        if (empty($all_meta)) {
            return;
        }

        foreach ($all_meta as $key => $values) {
            if (in_array($key, self::SKIP_META, true)) {
                continue;
            }

            foreach ($values as $raw_value) {
                // get_post_meta() always returns serialised values as strings;
                // maybe_unserialize() restores arrays/objects before re-saving.
                add_post_meta($to_id, $key, maybe_unserialize($raw_value));
            }
        }
    }

    /**
     * Copies every taxonomy term set from the original post to the duplicate.
     *
     * @param \WP_Post $original
     * @param int      $to_id
     */
    private function copy_taxonomies(\WP_Post $original, int $to_id): void
    {
        $taxonomies = get_object_taxonomies($original->post_type);

        foreach ($taxonomies as $taxonomy) {
            $term_ids = wp_get_object_terms($original->ID, $taxonomy, ['fields' => 'ids']);

            if (!empty($term_ids) && !is_wp_error($term_ids)) {
                wp_set_object_terms($to_id, $term_ids, $taxonomy);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function build_action_url(int $post_id): string
    {
        return add_query_arg(
            [
                'action'   => 'bw_duplicate_page',
                'post'     => $post_id,
                '_wpnonce' => wp_create_nonce('bw_duplicate_page_' . $post_id),
            ],
            admin_url('admin.php')
        );
    }
}

new BW_Duplicate_Page();
