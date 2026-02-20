(function ($) {
    'use strict';

    // Debug flag — enable with: window.BW_GOOGLE_PAY_DEBUG = true in browser console.
    var BW_GOOGLE_PAY_DEBUG = window.BW_GOOGLE_PAY_DEBUG === true;

    if (typeof bwGooglePayParams === 'undefined' || typeof Stripe === 'undefined') {
        return;
    }

    if (!bwGooglePayParams.publishableKey) {
        BW_GOOGLE_PAY_DEBUG && console.error('[BW Google Pay] Publishable key mancante.');
        return;
    }

    var stripe         = Stripe(bwGooglePayParams.publishableKey);
    var paymentRequest = null;
    var googlePayAvailable = false;
    var googlePayState = 'checking'; // checking | available | unavailable

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse a WooCommerce price string into a float, handling both
     * Italian (1.234,56) and US (1,234.56) number formats.
     *
     * @param {string} text Raw price text from the DOM.
     * @returns {number}
     */
    function bwParseWcAmount(text) {
        var cleaned  = text.replace(/[^\d.,]/g, '');
        if (!cleaned) return 0;

        var lastDot   = cleaned.lastIndexOf('.');
        var lastComma = cleaned.lastIndexOf(',');

        if (lastComma > lastDot) {
            // Italian/European format: 1.234,56 → dot = thousands, comma = decimal
            cleaned = cleaned.replace(/\./g, '').replace(',', '.');
        } else if (lastDot > lastComma) {
            // US format: 1,234.56 → comma = thousands, dot = decimal
            cleaned = cleaned.replace(/,/g, '');
        }
        // If only one separator type, treat it as decimal separator.

        return parseFloat(cleaned) || 0;
    }

    /**
     * Read the WooCommerce grand total from the order-review table and
     * return the amount in the smallest currency unit (cents).
     *
     * @returns {number}
     */
    function bwGetOrderTotalCents() {
        // Prefer the dedicated order-total row over any other price element.
        var $el = $('.order-total .woocommerce-Price-amount bdi').first();
        if (!$el.length) {
            $el = $('.woocommerce-Price-amount bdi').last();
        }
        return Math.round(bwParseWcAmount($el.text()) * 100);
    }

    /**
     * Validate visible required checkout fields before opening Google Pay sheet.
     * Mirrors WooCommerce invalid/valid classes enough to give clear UX feedback.
     *
     * @returns {boolean}
     */
    function bwValidateRequiredCheckoutFields() {
        var $form = $('form.checkout');
        var $requiredRows = $form.find('.validate-required:visible');
        var invalidLabels = [];

        $requiredRows.each(function () {
            var $row = $(this);
            var $input = $row.find('input, select, textarea').filter(function () {
                var $el = $(this);
                var type = ($el.attr('type') || '').toLowerCase();
                return !$el.prop('disabled') && type !== 'hidden';
            }).first();

            if (!$input.length) {
                return;
            }

            var isValid = true;
            var type = ($input.attr('type') || '').toLowerCase();

            if (type === 'checkbox') {
                isValid = $input.is(':checked');
            } else if ($input.is('select')) {
                isValid = $.trim($input.val() || '') !== '';
            } else {
                isValid = $.trim($input.val() || '') !== '';
            }

            if (!isValid) {
                var labelText = $.trim($row.find('label').first().clone().children().remove().end().text()) || 'Required field';
                invalidLabels.push(labelText);
                $row.removeClass('woocommerce-validated').addClass('woocommerce-invalid');
            } else {
                $row.removeClass('woocommerce-invalid').addClass('woocommerce-validated');
            }
        });

        if (!invalidLabels.length) {
            return true;
        }

        var $notices = $('.woocommerce-notices-wrapper').first();
        if ($notices.length) {
            $notices.html(
                '<ul class="woocommerce-error" role="alert"><li>Please fill in required fields: ' +
                invalidLabels.join(', ') +
                '.</li></ul>'
            );
        }
        $('html, body').animate({ scrollTop: 0 }, 250);
        return false;
    }

    // -------------------------------------------------------------------------
    // Google Pay button visibility
    // -------------------------------------------------------------------------

    var googleLogo = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="display:block;pointer-events:none;">' +
        '<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>' +
        '<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>' +
        '<path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.07H2.18c-.77 1.54-1.21 3.27-1.21 5.1s.44 3.55 1.21 5.1l3.66-2.17z"/>' +
        '<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.66l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>' +
    '</svg>';

    function handleButtonVisibility() {
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        var $placeOrder    = $('#place_order');
        var $wrapper       = $('#bw-google-pay-button-wrapper');
        var $placeholder   = $('#bw-google-pay-accordion-placeholder');
        var hasCustomBtn   = $('#bw-google-pay-trigger').length > 0;
        var isGoogleMethod = selectedMethod === 'bw_google_pay';

        if (isGoogleMethod) {
            // While Google Pay is selected, never show the generic Place order button.
            // This avoids intermediate UI glitches during async capability checks.
            $placeOrder[0] && $placeOrder[0].style.setProperty('display', 'none', 'important');

            if (googlePayState === 'available' && googlePayAvailable) {
                $wrapper.show();
                if (hasCustomBtn) {
                    $placeholder.hide();
                }
            } else if (googlePayState === 'checking') {
                $wrapper.hide();
                $placeholder.show();
            } else {
                $wrapper.hide();
                $placeholder.show();
            }

            if (googlePayAvailable && $('#bw-google-pay-trigger').length === 0) {
                var btn  = document.createElement('button');
                btn.type = 'button';
                btn.id   = 'bw-google-pay-trigger';
                btn.className = 'bw-custom-gpay-btn';

                var spanPay = document.createElement('span');
                spanPay.textContent = 'Pay with';
                btn.appendChild(spanPay);

                var logoWrap = document.createElement('span');
                logoWrap.innerHTML = googleLogo; // SVG is hardcoded — safe
                btn.appendChild(logoWrap);

                var spanG = document.createElement('span');
                spanG.textContent = 'Pay';
                btn.appendChild(spanG);

                var container = document.getElementById('bw-google-pay-button');
                if (container) {
                    container.innerHTML = '';
                    container.appendChild(btn);
                }

                // Ensure "Inizializzazione Google Pay..." is hidden as soon as the custom button exists.
                if (googlePayState === 'available' && googlePayAvailable) {
                    $placeholder.hide();
                }
            }
        } else {
            $wrapper.hide();
            if ($placeOrder[0]) {
                $placeOrder[0].style.display = '';
            }
            $placeOrder.show();
        }
    }

    /**
     * MutationObserver to prevent WooCommerce or theme scripts from
     * overriding our display:none on #place_order.
     */
    function observePlaceOrderButton() {
        var target = document.getElementById('place_order');
        if (!target) return;

        var observer = new MutationObserver(function () {
            if ($('input[name="payment_method"]:checked').val() === 'bw_google_pay' &&
                target.style.display !== 'none') {
                target.style.setProperty('display', 'none', 'important');
            }
        });

        observer.observe(target, { attributes: true, attributeFilter: ['style', 'class'] });
    }

    // -------------------------------------------------------------------------
    // Checkout AJAX submission
    // -------------------------------------------------------------------------

    /**
     * Submit the WooCommerce checkout form via AJAX (mirrors what wc-checkout.js
     * does internally) so we can call ev.complete() with the correct result.
     *
     * @param {object}   ev           Stripe paymentmethod event.
     * @param {string}   methodId     Stripe PaymentMethod ID (pm_xxx).
     */
    function submitCheckoutWithGooglePay(ev, methodId) {
        var $form = $('form.checkout');

        // Inject the PaymentMethod ID as a hidden field.
        var $hidden = $('#bw_google_pay_method_id');
        if ($hidden.length === 0) {
            $hidden = $('<input>', {
                type:  'hidden',
                id:    'bw_google_pay_method_id',
                name:  'bw_google_pay_method_id',
                value: ''
            });
            $form.append($hidden);
        }
        $hidden.val(methodId);

        $.ajax({
            type:     'POST',
            url:      bwGooglePayParams.ajaxCheckoutUrl,
            data:     $form.serialize(),
            dataType: 'json',
            success: function (response) {
                BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] Risposta checkout:', response);

                if (response && response.result === 'success') {
                    // Payment confirmed — close the Google Pay sheet with success.
                    ev.complete('success');
                    window.location.href = response.redirect;
                } else {
                    // Payment failed — close with failure so Google Pay shows error.
                    ev.complete('fail');
                    if (response && response.messages) {
                        var $notices = $('.woocommerce-notices-wrapper').first();
                        if ($notices.length) {
                            $notices.html(response.messages);
                        }
                        $('html, body').animate({ scrollTop: 0 }, 400);
                    }
                    BW_GOOGLE_PAY_DEBUG && console.warn('[BW Google Pay] Checkout fallito:', response.messages);
                }
            },
            error: function (xhr, status, err) {
                ev.complete('fail');
                BW_GOOGLE_PAY_DEBUG && console.error('[BW Google Pay] Errore AJAX:', status, err);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Stripe Payment Request (Google Pay)
    // -------------------------------------------------------------------------

    function initGooglePay() {
        var initialCents = bwGetOrderTotalCents();
        googlePayState = 'checking';
        googlePayAvailable = false;

        // Fallback to safe defaults if WC settings are empty (avoids Stripe validation error).
        var country  = (bwGooglePayParams.country  || 'IT').toUpperCase();
        var currency = (bwGooglePayParams.currency || 'eur').toLowerCase();

        try {
            paymentRequest = stripe.paymentRequest({
                country:  country,
                currency: currency,
                total: {
                    label:  'Ordine BlackWork',
                    amount: initialCents || 100, // Stripe requires a positive integer
                },
                requestPayerName:  true,
                requestPayerEmail: true,
                requestPayerPhone: true,
            });
        } catch (err) {
            BW_GOOGLE_PAY_DEBUG && console.error('[BW Google Pay] stripe.paymentRequest() error:', err);
            return;
        }

        BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] Params:', { country: country, currency: currency, cents: initialCents });

        paymentRequest.canMakePayment().then(function (result) {
            BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] canMakePayment result:', result);

            if (result) {
                // Payment method (Google Pay, Apple Pay or Stripe Link) is available.
                $('#bw-google-pay-accordion-placeholder').hide();
                handleButtonVisibility();

                $(document).off('click.bwgpay', '#bw-google-pay-trigger')
                           .on('click.bwgpay', '#bw-google-pay-trigger', function (e) {
                    e.preventDefault();
                    if (!bwValidateRequiredCheckoutFields()) {
                        BW_GOOGLE_PAY_DEBUG && console.warn('[BW Google Pay] Campi obbligatori mancanti: popup bloccato.');
                        return;
                    }
                    paymentRequest.show();
                });

            } else if (bwGooglePayParams.testMode) {
                /*
                 * TEST MODE FALLBACK
                 * canMakePayment() returns null when the browser has no Google Pay
                 * or Stripe Link configured.  In test mode we still render the button
                 * so the UI/payment flow can be verified.  In production the button is
                 * only shown when the device genuinely supports it.
                 */
                BW_GOOGLE_PAY_DEBUG && console.warn('[BW Google Pay] Test mode: canMakePayment null — forcing button visible for UI testing.');

                $('#bw-google-pay-accordion-placeholder').hide();
                handleButtonVisibility();

                $(document).off('click.bwgpay', '#bw-google-pay-trigger')
                           .on('click.bwgpay', '#bw-google-pay-trigger', function (e) {
                    e.preventDefault();
                    try {
                        paymentRequest.show();
                    } catch (showErr) {
                        BW_GOOGLE_PAY_DEBUG && console.warn('[BW Google Pay] show() failed (expected if no payment method in browser):', showErr);
                    }
                });

            } else {
                // Production: device does not support any compatible payment method.
                BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] Non disponibile su questo dispositivo/browser.');
                $('#bw-google-pay-button-wrapper').hide();

                var p = document.createElement('p');
                p.style.cssText = 'font-size:13px;color:#666;margin:16px auto 10px auto;text-align:center;max-width:640px;display:block;';
                p.textContent   = 'Google Pay non è disponibile su questo dispositivo/browser o account.';

                var placeholder = document.getElementById('bw-google-pay-accordion-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = '';
                    placeholder.appendChild(p);
                }

                handleButtonVisibility();
            }
        }).catch(function () {
            googlePayAvailable = false;
            googlePayState = 'unavailable';
            handleButtonVisibility();
        });

        // Fires when the customer approves the payment in the Google Pay sheet.
        paymentRequest.on('paymentmethod', function (ev) {
            BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] paymentMethod ricevuto:', ev.paymentMethod.id);
            submitCheckoutWithGooglePay(ev, ev.paymentMethod.id);
        });
    }

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    $(document).ready(function () {
        initGooglePay();
        observePlaceOrderButton();

        $(document.body).on('change', 'input[name="payment_method"]', function () {
            handleButtonVisibility();
        });

        $(document.body).on('updated_checkout', function () {
            handleButtonVisibility();

            // WooCommerce can re-render payment fields and bring back the placeholder:
            // keep it hidden when Google Pay button is already available.
            if ($('#bw-google-pay-trigger').length) {
                $('#bw-google-pay-accordion-placeholder').hide();
            }

            if (paymentRequest) {
                var updatedCents = bwGetOrderTotalCents() || 100;
                BW_GOOGLE_PAY_DEBUG && console.log('[BW Google Pay] Aggiornamento totale:', updatedCents, 'centesimi');
                paymentRequest.update({
                    total: {
                        label:  'Ordine BlackWork',
                        amount: updatedCents,
                    },
                });
            }
        });

        handleButtonVisibility();
    });

})(jQuery);
