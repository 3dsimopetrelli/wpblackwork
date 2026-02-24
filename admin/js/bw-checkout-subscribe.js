jQuery(function ($) {
    const button = $('#bw-brevo-test-connection');
    const result = $('.bw-brevo-test-result');

    if (!button.length) {
        return;
    }

    button.on('click', function () {
        const apiKey = $('#bw_mail_marketing_general_api_key').val() || $('#bw_checkout_subscribe_api_key').val();

        result.removeClass('notice-success notice-error is-success is-error').text('');
        button.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'bw_brevo_test_connection',
            nonce: bwCheckoutSubscribe.nonce,
            api_key: apiKey
        })
            .done(function (response) {
                if (response.success) {
                    result.addClass('notice-success is-success').text(response.data.message);
                } else {
                    result.addClass('notice-error is-error').text(response.data.message || bwCheckoutSubscribe.errorText);
                }
            })
            .fail(function () {
                result.addClass('notice-error is-error').text(bwCheckoutSubscribe.errorText);
            })
            .always(function () {
                button.prop('disabled', false);
            });
    });
});
