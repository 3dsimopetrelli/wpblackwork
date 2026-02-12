(function ($) {
    'use strict';

    $(document).on('click', '.bw-navshop__cart[data-use-popup="yes"]', function (e) {
        e.preventDefault();

        if (typeof window.BW_CartPopup !== 'undefined' && typeof window.BW_CartPopup.openPanel === 'function') {
            window.BW_CartPopup.openPanel();
            return;
        }

        window.location.href = $(this).attr('href');
    });
})(jQuery);
