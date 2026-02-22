(function ($) {
    'use strict';

    function updateModePill() {
        $('#bw-klarna-mode-pill')
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
        var $button = $('#bw-klarna-test-connection');
        var $result = $('#bw-klarna-test-result');

        var secretKey = ($('#bw_klarna_secret_key').val() || '').trim();
        var publishableKey = ($('#bw_klarna_publishable_key').val() || '').trim();

        if (!secretKey) {
            setResultMessage($result, 'Enter your Live Secret Key before testing.', 'is-error');
            return;
        }

        setResultMessage($result, bwKlarnaAdmin.testingText || 'Testing connection…', 'is-testing');
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'bw_klarna_test_connection',
                nonce: bwKlarnaAdmin.nonce,
                mode: 'live',
                secret_key: secretKey,
                publishable_key: publishableKey
            }
        }).done(function (response) {
            if (response && response.success) {
                setResultMessage($result, response.data && response.data.message ? response.data.message : 'Connected successfully (Live mode).', 'is-success');
            } else {
                setResultMessage($result, response && response.data && response.data.message ? response.data.message : (bwKlarnaAdmin.errorText || 'Connection test failed.'), 'is-error');
            }
        }).fail(function () {
            setResultMessage($result, bwKlarnaAdmin.errorText || 'Connection test failed.', 'is-error');
        }).always(function () {
            $button.prop('disabled', false);
        });
    }

    $(function () {
        if (!$('#bw-klarna-test-connection').length) {
            return;
        }

        updateModePill();
        $('#bw-klarna-test-connection').on('click', testConnection);
    });
})(jQuery);
