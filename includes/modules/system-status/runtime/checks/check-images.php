<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_check_images')) {
    function bw_system_status_check_images()
    {
        global $wpdb;

        $sizes = get_intermediate_image_sizes();
        $additional_sizes = wp_get_additional_image_sizes();
        $size_details = [];
        $duplicate_groups = [];
        $size_signature_map = [];

        foreach ($sizes as $size_name) {
            if (isset($additional_sizes[$size_name])) {
                $width = isset($additional_sizes[$size_name]['width']) ? (int) $additional_sizes[$size_name]['width'] : 0;
                $height = isset($additional_sizes[$size_name]['height']) ? (int) $additional_sizes[$size_name]['height'] : 0;
                $crop = !empty($additional_sizes[$size_name]['crop']);
            } else {
                $width = (int) get_option("{$size_name}_size_w", 0);
                $height = (int) get_option("{$size_name}_size_h", 0);
                $crop = (bool) get_option("{$size_name}_crop", false);
            }

            $size_details[] = [
                'name' => (string) $size_name,
                'width' => $width,
                'height' => $height,
                'crop' => $crop,
            ];

            $signature = $width . 'x' . $height . ':' . ($crop ? '1' : '0');
            if (!isset($size_signature_map[$signature])) {
                $size_signature_map[$signature] = [];
            }
            $size_signature_map[$signature][] = (string) $size_name;
        }

        foreach ($size_signature_map as $signature => $grouped_sizes) {
            if (count($grouped_sizes) < 2) {
                continue;
            }

            $duplicate_groups[] = [
                'signature' => $signature,
                'sizes' => $grouped_sizes,
            ];
        }

        $image_attachments_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_status = 'inherit'
               AND post_mime_type LIKE 'image/%'"
        );
        $estimated_generated_image_count = $image_attachments_count * count($size_details);

        $warnings = [];
        $status = empty($size_details) ? 'warn' : 'ok';
        if (!empty($duplicate_groups)) {
            $status = 'warn';
            $warnings[] = __('Duplicate image size dimensions detected.', 'bw');
        }
        if (empty($size_details)) {
            $warnings[] = __('No registered image sizes found.', 'bw');
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: %d: number of image sizes */
                __('%d registered image sizes.', 'bw'),
                count($size_details)
            ),
            'metrics' => [
                'count' => count($size_details),
                'total_registered_sizes' => count($size_details),
                'image_attachments_count' => $image_attachments_count,
                'estimated_generated_image_count' => $estimated_generated_image_count,
                'duplicate_sizes' => $duplicate_groups,
                'sizes' => $size_details,
            ],
            'warnings' => $warnings,
        ];
    }
}
