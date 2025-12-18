/**
 * Shared border toggle functionality for admin settings
 * Used by Cart Popup Settings and Site Settings
 */
jQuery(document).ready(function($) {
    // Toggle visibilità campi bordo per Checkout button
    function toggleCheckoutBorderFields() {
        const isEnabled = $('#bw_cart_popup_checkout_border_enabled').is(':checked');
        $('.bw-checkout-border-field').toggle(isEnabled);
    }

    // Toggle visibilità campi bordo per Continue button
    function toggleContinueBorderFields() {
        const isEnabled = $('#bw_cart_popup_continue_border_enabled').is(':checked');
        $('.bw-continue-border-field').toggle(isEnabled);
    }

    // Inizializza stato al caricamento pagina
    toggleCheckoutBorderFields();
    toggleContinueBorderFields();

    // Listener per checkbox
    $('#bw_cart_popup_checkout_border_enabled').on('change', toggleCheckoutBorderFields);
    $('#bw_cart_popup_continue_border_enabled').on('change', toggleContinueBorderFields);
});
