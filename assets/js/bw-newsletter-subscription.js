(function () {
    var config = window.bwMailMarketingSubscription || {};
    var forms = document.querySelectorAll('.bw-newsletter-subscription-form');

    if (!forms.length) {
        return;
    }

    function setMessage(form, type, text) {
        var message = form.querySelector('.bw-newsletter-subscription-message');
        if (!message) {
            return;
        }

        message.classList.remove('is-success', 'is-error');
        if (type) {
            message.classList.add(type === 'error' ? 'is-error' : 'is-success');
        }
        message.textContent = text || '';
    }

    function setBusy(form, busy) {
        var button = form.querySelector('.bw-newsletter-subscription-button');
        if (!button) {
            return;
        }

        button.disabled = !!busy;
        if (busy) {
            button.setAttribute('aria-busy', 'true');
        } else {
            button.removeAttribute('aria-busy');
        }
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var ajaxUrl = config.ajaxUrl || '';
            var nonce = form.getAttribute('data-nonce') || '';
            var formData = new FormData(form);

            formData.append('action', 'bw_mail_marketing_subscribe');
            formData.append('nonce', nonce);

            if (!ajaxUrl || !nonce) {
                setMessage(form, 'error', config.networkErrorText || 'Unable to connect right now. Please try again later.');
                return;
            }

            setBusy(form, true);
            setMessage(form, 'success', config.workingText || 'Submitting...');

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {
                            success: false,
                            data: {
                                message: config.networkErrorText || 'Unable to connect right now. Please try again later.'
                            }
                        };
                    });
                })
                .then(function (response) {
                    if (response && response.success) {
                        setMessage(form, 'success', response.data && response.data.message ? response.data.message : '');
                        form.reset();
                        return;
                    }

                    setMessage(
                        form,
                        'error',
                        response && response.data && response.data.message
                            ? response.data.message
                            : (config.networkErrorText || 'Unable to connect right now. Please try again later.')
                    );
                })
                .catch(function () {
                    setMessage(form, 'error', config.networkErrorText || 'Unable to connect right now. Please try again later.');
                })
                .finally(function () {
                    setBusy(form, false);
                });
        });
    });
}());
