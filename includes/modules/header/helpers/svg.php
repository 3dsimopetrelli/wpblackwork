<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_get_icon_markup')) {
    function bw_header_get_icon_markup($attachment_id, $fallback_svg, $class)
    {
        $attachment_id = absint($attachment_id);
        if ($attachment_id > 0) {
            $mime = get_post_mime_type($attachment_id);
            $url = wp_get_attachment_url($attachment_id);
            if ($url) {
                if ($mime === 'image/svg+xml') {
                    return sprintf('<img class="%1$s" src="%2$s" alt="" loading="lazy" decoding="async" />', esc_attr($class), esc_url($url));
                }

                $img = wp_get_attachment_image($attachment_id, 'full', false, ['class' => $class]);
                if ($img) {
                    return $img;
                }
            }
        }

        return $fallback_svg;
    }
}

if (!function_exists('bw_header_get_logo_markup')) {
    function bw_header_get_logo_markup($attachment_id, $settings = [])
    {
        $attachment_id = absint($attachment_id);
        $width = isset($settings['logo_width']) ? max(10, absint($settings['logo_width'])) : 54;
        $height = isset($settings['logo_height']) ? absint($settings['logo_height']) : 54;

        if ($attachment_id > 0) {
            $mime = get_post_mime_type($attachment_id);
            $url = wp_get_attachment_url($attachment_id);
            if ($url) {
                $style = $height > 0
                    ? sprintf('style="width:%dpx;height:%dpx;"', $width, $height)
                    : sprintf('style="width:%dpx;height:auto;"', $width);

                if ($mime === 'image/svg+xml') {
                    return sprintf(
                        '<img class="bw-custom-header__logo-image" src="%1$s" alt="%2$s" %3$s loading="lazy" decoding="async" />',
                        esc_url($url),
                        esc_attr(get_bloginfo('name')),
                        $style
                    );
                }

                $img = wp_get_attachment_image($attachment_id, 'full', false, [
                    'class' => 'bw-custom-header__logo-image',
                    'style' => $height > 0 ? "width:{$width}px;height:{$height}px;" : "width:{$width}px;height:auto;",
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ]);

                if ($img) {
                    return $img;
                }
            }
        }

        return '<span class="bw-custom-header__logo-fallback" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="11"></circle><circle cx="12" cy="12" r="1.8" fill="currentColor" stroke="none"></circle></svg></span>';
    }
}
