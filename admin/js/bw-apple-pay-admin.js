(function ($) {
    'use strict';

    function updateModePill() {
        $('#bw-apple-pay-mode-pill')
            .text('ACTIVE MODE: LIVE')
            .addClass('is-live');
    }

    function setResultMessage($result, text, stateClass) {
        $result.removeClass('is-success is-error is-testing');
        if (stateClass) {
            $result.addClass(stateClass);
        }
        $result.text(text);
    }

    function testConnection() {
        var $button = $('#bw-apple-pay-test-connection');
        var $result = $('#bw-apple-pay-test-result');

        var secretKey = ($('#bw_apple_pay_secret_key').val() || '').trim();
        var publishableKey = ($('#bw_apple_pay_publishable_key').val() || '').trim();

        if (!secretKey) {
            setResultMessage($result, 'Enter your Live Secret Key before testing.', 'is-error');
            return;
        }

        setResultMessage($result, bwApplePayAdmin.testingText || 'Testing connection…', 'is-testing');
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'bw_apple_pay_test_connection',
                nonce: bwApplePayAdmin.nonce,
                mode: 'live',
                secret_key: secretKey,
                publishable_key: publishableKey
            }
        }).done(function (response) {
            if (response && response.success) {
                setResultMessage($result, response.data && response.data.message ? response.data.message : 'Connected successfully (Live mode).', 'is-success');
            } else {
                setResultMessage($result, response && response.data && response.data.message ? response.data.message : (bwApplePayAdmin.errorText || 'Connection test failed.'), 'is-error');
            }
        }).fail(function () {
            setResultMessage($result, bwApplePayAdmin.errorText || 'Connection test failed.', 'is-error');
        }).always(function () {
            $button.prop('disabled', false);
        });
    }

    $(function () {
        if (!$('#bw-apple-pay-test-connection').length) {
            return;
        }

        updateModePill();
        $('#bw-apple-pay-test-connection').on('click', testConnection);
    });
})(jQuery);
