jQuery(function ($) {
    const $button = $('#bw-google-pay-test-connection');
    const $result = $('#bw-google-pay-test-result');
    const $testMode = $('#bw_google_pay_test_mode');
    const $modePill = $('#bw-google-pay-mode-pill');

    if (!$button.length || !$result.length || typeof bwGooglePayAdmin === 'undefined') {
        return;
    }

    const setResult = function (statusClass, text) {
        $result
            .removeClass('is-success is-error is-testing')
            .addClass(statusClass)
            .text(text || '');
    };

    const syncModeUi = function () {
        const isTestMode = $testMode.is(':checked');
        if ($modePill.length) {
            $modePill
                .toggleClass('is-live', !isTestMode)
                .text(isTestMode ? 'Modalita attiva: TEST' : 'Modalita attiva: LIVE');
        }

        $button.text(isTestMode ? 'Verifica connessione (TEST)' : 'Verifica connessione (LIVE)');
    };

    syncModeUi();
    $testMode.on('change', function () {
        syncModeUi();
        setResult('', '');
    });

    $button.on('click', function () {
        const isTestMode = $('#bw_google_pay_test_mode').is(':checked');
        const mode = isTestMode ? 'test' : 'live';
        const secretKey = isTestMode
            ? $('#bw_google_pay_test_secret_key').val()
            : $('#bw_google_pay_secret_key').val();
        const publishableKey = isTestMode
            ? $('#bw_google_pay_test_publishable_key').val()
            : $('#bw_google_pay_publishable_key').val();

        setResult('is-testing', bwGooglePayAdmin.testingText || 'Testing connection…');
        $button.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'bw_google_pay_test_connection',
            nonce: bwGooglePayAdmin.nonce,
            mode: mode,
            secret_key: secretKey,
            publishable_key: publishableKey
        })
            .done(function (response) {
                if (response && response.success && response.data && response.data.message) {
                    setResult('is-success', response.data.message);
                    return;
                }

                const message = response && response.data && response.data.message
                    ? response.data.message
                    : bwGooglePayAdmin.errorText;

                setResult('is-error', message);
            })
            .fail(function () {
                setResult('is-error', bwGooglePayAdmin.errorText);
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });
});
