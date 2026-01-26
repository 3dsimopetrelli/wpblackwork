(function ($) {
    'use strict';

    if (typeof bwGooglePayParams === 'undefined' || typeof Stripe === 'undefined') {
        return;
    }

    if (!bwGooglePayParams.publishableKey) {
        console.error('Google Pay: Publishable key is missing.');
        return;
    }

    const stripe = Stripe(bwGooglePayParams.publishableKey);
    let paymentRequest = null;

    const googleLogo = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" style="display: block; margin: 0; pointer-events: none;">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.07H2.18c-.77 1.54-1.21 3.27-1.21 5.1s.44 3.55 1.21 5.1l3.66-2.17z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.66l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>`;

    const customBtnHtml = `
        <button type="button" class="bw-custom-gpay-btn" id="bw-google-pay-trigger">
            <span>Pay with</span>
            ${googleLogo}
            <span>Pay</span>
        </button>
    `;

    const handleButtonVisibility = () => {
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        const $placeOrder = $('#place_order');
        const $wrapper = $('#bw-google-pay-button-wrapper');

        if (selectedMethod === 'bw_google_pay') {
            $placeOrder.hide();
            // Use important style to override theme/WC scripts
            $placeOrder[0]?.style.setProperty('display', 'none', 'important');
            $wrapper.show();

            // Ensure the custom button is rendered in the placeholder
            if ($('#bw-google-pay-trigger').length === 0) {
                $('#bw-google-pay-button').html(customBtnHtml);
            }
        } else {
            $wrapper.hide();
            if ($placeOrder[0]) {
                $placeOrder[0].style.display = '';
            }
            $placeOrder.show();
        }
    };

    // Robust observer to catch WooCommerce or theme scripts overriding our hide()
    const observeButton = () => {
        const target = document.getElementById('place_order');
        if (!target) return;

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                const selectedMethod = $('input[name="payment_method"]:checked').val();
                if (selectedMethod === 'bw_google_pay' && target.style.display !== 'none') {
                    target.style.setProperty('display', 'none', 'important');
                }
            });
        });

        observer.observe(target, { attributes: true, attributeFilter: ['style', 'class'] });
    };

    const initGooglePay = () => {
        const totalAmount = parseFloat($('.woocommerce-Price-amount bdi').last().text().replace(/[^\d.,]/g, '').replace(',', '.'));
        const amountInCents = Math.round(totalAmount * 100);

        paymentRequest = stripe.paymentRequest({
            country: 'IT',
            currency: 'eur',
            total: {
                label: 'Ordine BlackWork',
                amount: amountInCents,
            },
            requestPayerName: true,
            requestPayerEmail: true,
            requestPayerPhone: true,
        });

        // Check availability
        paymentRequest.canMakePayment().then((result) => {
            if (result) {
                // Success - hide loading/initializing placeholders
                $('#bw-google-pay-accordion-placeholder').hide();
                handleButtonVisibility();

                // Bind click event (delegated)
                $(document).off('click', '#bw-google-pay-trigger').on('click', '#bw-google-pay-trigger', function (e) {
                    e.preventDefault();
                    paymentRequest.show();
                });
            } else {
                console.log('Google Pay not available');
                $('#bw-google-pay-button-wrapper').hide();
                $('#bw-google-pay-accordion-placeholder').html('<p style="font-size: 13px; color: #666; margin: 10px 0;">Google Pay non è disponibile su questo dispositivo o browser.</p>');
            }
        });

        // Handle payment method creation
        paymentRequest.on('paymentmethod', async (ev) => {
            const $form = $('form.checkout');
            if ($('#bw_google_pay_method_id').length === 0) {
                $form.append('<input type="hidden" id="bw_google_pay_method_id" name="bw_google_pay_method_id" value="">');
            }
            $('#bw_google_pay_method_id').val(ev.paymentMethod.id);
            ev.complete('success');
            $form.submit();
        });
    };

    $(document).ready(function () {
        initGooglePay();
        observeButton();

        // Listen for method changes
        $(document.body).on('change', 'input[name="payment_method"]', function () {
            handleButtonVisibility();
        });

        // Listen for updated_checkout (AJAX refresh)
        $(document.body).on('updated_checkout', function () {
            handleButtonVisibility();
            if (paymentRequest) {
                const totalText = $('.woocommerce-Price-amount bdi').last().text();
                const totalAmount = parseFloat(totalText.replace(/[^\d.,]/g, '').replace(',', '.'));
                const amountInCents = Math.round(totalAmount * 100);
                paymentRequest.update({
                    total: { label: 'Ordine BlackWork', amount: amountInCents },
                });
            }
        });

        // Direct call for initial state
        handleButtonVisibility();
    });

})(jQuery);
