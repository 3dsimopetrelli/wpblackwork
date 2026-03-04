<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="bw-media-folders-root" class="bw-media-folders" aria-live="polite">
    <div class="bw-media-folders__header">
        <h2 class="bw-media-folders__title"><?php esc_html_e('Folders', 'bw'); ?></h2>
        <div class="bw-media-folders__header-actions">
            <button type="button" class="button button-small" id="bw-media-folders-toggle" aria-expanded="true">
                <?php esc_html_e('Collapse', 'bw'); ?>
            </button>
            <button type="button" class="button button-primary button-small" id="bw-mr-new-folder-btn">
                <?php esc_html_e('New Folder', 'bw'); ?>
            </button>
        </div>
    </div>

    <div class="bw-media-folders__search-wrap">
        <input type="search" id="bw-mr-folder-search" class="bw-media-folders__search" placeholder="<?php esc_attr_e('Search folders...', 'bw'); ?>" />
    </div>

    <div class="bw-media-folders__defaults" id="bw-media-folders-defaults"></div>

    <div class="bw-media-folders__tree" id="bw-media-folders-tree"></div>

    <div class="bw-media-folders__bulk">
        <label for="bw-media-folders-bulk-select"><?php esc_html_e('Bulk organize', 'bw'); ?></label>
        <select id="bw-media-folders-bulk-select">
            <option value="0"><?php esc_html_e('Unassigned', 'bw'); ?></option>
        </select>
        <button type="button" class="button" id="bw-media-folders-bulk-btn"><?php esc_html_e('Move selected', 'bw'); ?></button>
    </div>
</div>
<button type="button" id="bw-mf-collapse-tab" aria-label="<?php esc_attr_e('Open folders sidebar', 'bw'); ?>">
    <?php esc_html_e('Folders', 'bw'); ?>
</button>
