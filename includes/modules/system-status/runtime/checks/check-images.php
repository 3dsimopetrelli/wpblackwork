<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_check_images')) {
    function bw_system_status_check_images()
    {
        $sizes = get_intermediate_image_sizes();
        $additional_sizes = wp_get_additional_image_sizes();
        $size_details = [];

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
        }

        return [
            'status' => empty($size_details) ? 'warn' : 'ok',
            'summary' => sprintf(
                /* translators: %d: number of image sizes */
                __('%d registered image sizes.', 'bw'),
                count($size_details)
            ),
            'metrics' => [
                'count' => count($size_details),
                'sizes' => $size_details,
            ],
            'warnings' => empty($size_details)
                ? [__('No registered image sizes found.', 'bw')]
                : [],
        ];
    }
}
