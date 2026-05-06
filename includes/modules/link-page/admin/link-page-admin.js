(function () {
    'use strict';

    var optionKey = 'bw_link_page_settings_v1';
    var linksTableBody = document.querySelector('#bw-link-page-links-table tbody');
    var addLinkButton = document.getElementById('bw-link-page-add-link');
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
            '<td><input type="text" class="regular-text" name="' + optionKey + '[links][' + index + '][label]" value=""></td>' +
            '<td><input type="url" class="regular-text" name="' + optionKey + '[links][' + index + '][url]" value=""></td>' +
            '<td><label><input type="checkbox" name="' + optionKey + '[links][' + index + '][target]" value="1"> _blank</label></td>' +
            '<td><button type="button" class="button bw-link-page-remove-link">Remove</button></td>';

        return row;
    }

    if (addLinkButton && linksTableBody) {
        addLinkButton.addEventListener('click', function () {
            linksTableBody.appendChild(createLinkRow(nextIndex()));
        });

        linksTableBody.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement) || !target.classList.contains('bw-link-page-remove-link')) {
                return;
            }

            var row = target.closest('tr');
            if (row) {
                row.remove();
            }
        });
    }

    if (uploadButton && logoInput && logoPreview && typeof wp !== 'undefined' && wp.media) {
        uploadButton.addEventListener('click', function () {
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
}());
