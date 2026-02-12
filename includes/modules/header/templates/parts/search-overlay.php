<?php
if (!defined('ABSPATH')) {
    exit;
}

$widget_id = isset($overlay_args['widget_id']) ? (string) $overlay_args['widget_id'] : wp_generate_uuid4();
$show_header_text = !empty($overlay_args['show_header_text']);
$header_text = isset($overlay_args['header_text']) ? (string) $overlay_args['header_text'] : __("Type what you're looking for", 'bw');
$placeholder = isset($overlay_args['placeholder']) ? (string) $overlay_args['placeholder'] : __('Type...', 'bw');
$hint_text = isset($overlay_args['hint_text']) ? (string) $overlay_args['hint_text'] : __('Hit enter to search or ESC to close', 'bw');
?>
<div class="bw-search-overlay" data-widget-id="<?php echo esc_attr($widget_id); ?>" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Search', 'bw'); ?>">
    <div class="bw-search-overlay__container">
        <button class="bw-search-overlay__close" type="button" aria-label="<?php esc_attr_e('Close search', 'bw'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="bw-search-overlay__content">
            <?php if ($show_header_text) : ?>
                <h2 class="bw-search-overlay__title"><?php echo esc_html($header_text); ?></h2>
            <?php endif; ?>

            <form class="bw-search-overlay__form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="bw-search-overlay__input-wrapper">
                    <input
                        type="search"
                        name="s"
                        class="bw-search-overlay__input"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        aria-label="<?php esc_attr_e('Search', 'bw'); ?>"
                        autocomplete="off"
                    />
                </div>

                <p class="bw-search-overlay__hint"><?php echo esc_html($hint_text); ?></p>

                <div class="bw-search-results">
                    <div class="bw-search-results__grid"></div>
                    <div class="bw-search-results__message"></div>
                    <div class="bw-search-results__loading">
                        <div class="bw-search-loading-spinner"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
