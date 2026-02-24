jQuery(function ($) {
    var checkoutForm = $('form.checkout.woocommerce-checkout, form[name="checkout"]').first();
    if (!checkoutForm.length) {
        return;
    }

    function ensureDiagnosticInput() {
        var input = checkoutForm.find('input[name="bw_subscribe_newsletter_frontend"]');
        if (!input.length) {
            input = $('<input>', {
                type: 'hidden',
                name: 'bw_subscribe_newsletter_frontend',
                value: ''
            });
            checkoutForm.append(input);
        }
        return input;
    }

    function syncStateToHiddenInput() {
        var checkbox = checkoutForm.find('input[name="bw_subscribe_newsletter"]');
        var state = 'unchecked';
        if (checkbox.length && checkbox.is(':checked')) {
            state = 'checked';
        }
        ensureDiagnosticInput().val(state);
    }

    checkoutForm.on('submit', function () {
        syncStateToHiddenInput();
    });

    checkoutForm.on('change', 'input[name="bw_subscribe_newsletter"]', function () {
        syncStateToHiddenInput();
    });

    syncStateToHiddenInput();
});
