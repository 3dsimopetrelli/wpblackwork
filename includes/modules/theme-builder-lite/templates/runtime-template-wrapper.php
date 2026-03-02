<?php
if (!defined('ABSPATH')) {
    exit;
}

$bw_tbl_runtime_template_id = function_exists('bw_tbl_runtime_get_active_template_id') ? bw_tbl_runtime_get_active_template_id() : 0;
$bw_tbl_runtime_template_type = function_exists('bw_tbl_runtime_get_active_template_type') ? bw_tbl_runtime_get_active_template_type() : '';
$bw_tbl_runtime_content = function_exists('bw_tbl_runtime_render_template_content') ? bw_tbl_runtime_render_template_content($bw_tbl_runtime_template_id) : '';

if (!is_string($bw_tbl_runtime_content) || '' === trim($bw_tbl_runtime_content)) {
    return;
}

get_header();
?>
<main id="primary" class="site-main bw-tbl-runtime-template">
    <div
        class="bw-tbl-runtime-template-content"
        data-bw-tbl-template-id="<?php echo esc_attr((string) $bw_tbl_runtime_template_id); ?>"
        data-bw-tbl-template-type="<?php echo esc_attr($bw_tbl_runtime_template_type); ?>"
    >
        <?php echo $bw_tbl_runtime_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</main>
<?php
get_footer();
