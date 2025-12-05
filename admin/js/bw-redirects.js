(function ($) {
    $(document).ready(function () {
        const $rowsContainer = $('#bw-redirects-rows');
        const template = $('#bw-redirect-row-template').html();
        let nextIndex = parseInt($rowsContainer.data('next-index'), 10);

        if (isNaN(nextIndex)) {
            nextIndex = $rowsContainer.find('.bw-redirect-row').length;
        }

        $('#bw-add-redirect').on('click', function (event) {
            event.preventDefault();

            if (!template) {
                return;
            }

            const newRowHtml = template.replace(/__index__/g, nextIndex);
            $rowsContainer.append(newRowHtml);
            nextIndex += 1;
        });

        $rowsContainer.on('click', '.bw-remove-redirect', function (event) {
            event.preventDefault();
            $(this).closest('.bw-redirect-row').remove();

            if (!$rowsContainer.children().length) {
                $('#bw-add-redirect').trigger('click');
            }
        });
    });
})(jQuery);
