jQuery(function ($) {
    const button = $('#bw-brevo-test-connection');
    const result = $('.bw-brevo-test-result');

    if (!button.length) {
        return;
    }

    button.on('click', function () {
        const apiKey = $('#bw_checkout_subscribe_api_key').val();
        const apiBase = $('#bw_checkout_subscribe_api_base').val();

        result.removeClass('notice-success notice-error').text('');
        button.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'bw_brevo_test_connection',
            nonce: bwCheckoutSubscribe.nonce,
            api_key: apiKey,
            api_base: apiBase
        })
            .done(function (response) {
                if (response.success) {
                    result.addClass('notice-success').text(response.data.message);
                } else {
                    result.addClass('notice-error').text(response.data.message || bwCheckoutSubscribe.errorText);
                }
            })
            .fail(function () {
                result.addClass('notice-error').text(bwCheckoutSubscribe.errorText);
            })
            .always(function () {
                button.prop('disabled', false);
            });
    });
});
