(function () {
    'use strict';

    function initLinkPageAdmin() {
        var optionKey = 'bw_link_page_settings_v1';
        var linksTableBody = document.querySelector('#bw-link-page-links-table tbody');
        var addLinkButton = document.getElementById('bw-link-page-add-link');
        var settingsForm = document.querySelector('form.bw-site-settings-form');
        var uploadButton = document.getElementById('bw-link-page-logo-upload');
        var removeButton = document.getElementById('bw-link-page-logo-remove');
        var logoInput = document.getElementById('bw-link-page-logo-id');
        var logoPreview = document.getElementById('bw-link-page-logo-preview');

        function nextIndex() {
            if (!linksTableBody) {
                return 0;
            }

            return linksTableBody.querySelectorAll('tr').length;
        }

        function createLinkRow(index) {
            var row = document.createElement('tr');
            row.innerHTML = '' +
                '<td style="text-align:center;vertical-align:middle;"><span class="bw-link-page-drag-handle" aria-label="Drag to reorder" title="Drag to reorder" style="cursor:move;display:inline-block;font-size:18px;line-height:1;color:#2271b1;">&#8801;</span></td>' +
                '<td><input type="text" class="regular-text" name="' + optionKey + '[links][' + index + '][label]" value=""></td>' +
                '<td><input type="url" class="regular-text" name="' + optionKey + '[links][' + index + '][url]" value=""></td>' +
                '<td><label><input type="checkbox" name="' + optionKey + '[links][' + index + '][target]" value="1"> _blank</label></td>' +
                '<td><button type="button" class="button bw-link-page-remove-link">Remove</button></td>';

            return row;
        }

        function reindexRows() {
            if (!linksTableBody) {
                return;
            }

            var rows = linksTableBody.querySelectorAll('tr');
            rows.forEach(function (row, index) {
                var inputs = row.querySelectorAll('input[name]');
                inputs.forEach(function (input) {
                    var currentName = String(input.getAttribute('name') || '');
                    var updatedName = currentName.replace(/\[links\]\[\d+\]/, '[links][' + index + ']');
                    input.setAttribute('name', updatedName);
                });
            });
        }

        if (addLinkButton && linksTableBody) {
            addLinkButton.addEventListener('click', function () {
                linksTableBody.appendChild(createLinkRow(nextIndex()));
                reindexRows();
            });

            linksTableBody.addEventListener('click', function (event) {
                var target = event.target;
                if (!(target instanceof Element)) {
                    return;
                }

                var removeLinkButton = target.closest('.bw-link-page-remove-link');
                if (!removeLinkButton) {
                    return;
                }

                var row = removeLinkButton.closest('tr');
                if (row) {
                    row.remove();
                    reindexRows();
                }
            });
        }

        if (linksTableBody && window.jQuery && window.jQuery.fn && window.jQuery.fn.sortable) {
            window.jQuery(linksTableBody).sortable({
                axis: 'y',
                handle: '.bw-link-page-drag-handle',
                helper: function (event, ui) {
                    ui.children().each(function () {
                        window.jQuery(this).width(window.jQuery(this).width());
                    });
                    return ui;
                },
                update: function () {
                    reindexRows();
                }
            });
        }

        if (settingsForm) {
            settingsForm.addEventListener('submit', function () {
                reindexRows();
            });
        }

        if (uploadButton && logoInput && logoPreview) {
            uploadButton.addEventListener('click', function () {
                if (typeof wp === 'undefined' || !wp.media) {
                    return;
                }

                var frame = wp.media({
                    title: 'Select logo',
                    button: { text: 'Use logo' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    logoInput.value = String(attachment.id || '');
                    logoPreview.innerHTML = attachment.url
                        ? '<img src="' + attachment.url + '" alt="" style="max-width:140px;height:auto;display:block;">'
                        : '';
                });

                frame.open();
            });
        }

        if (removeButton && logoInput && logoPreview) {
            removeButton.addEventListener('click', function () {
                logoInput.value = '';
                logoPreview.innerHTML = '';
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLinkPageAdmin);
    } else {
        initLinkPageAdmin();
    }
}());
