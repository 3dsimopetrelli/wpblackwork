/**
 * BW NavShop Widget JavaScript
 *
 * Gestisce l'integrazione con il Cart Pop-Up quando attivo
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Intercetta click sui link cart con data-use-popup="yes"
        $(document).on('click', '.bw-navshop__cart[data-use-popup="yes"]', function(e) {
            e.preventDefault();

            // Verifica che BW_CartPopup sia disponibile
            if (typeof window.BW_CartPopup !== 'undefined' && typeof window.BW_CartPopup.openPanel === 'function') {
                // Apri il Cart Pop-Up
                window.BW_CartPopup.openPanel();
            } else {
                console.warn('BW Cart Pop-Up non è disponibile, uso il link normale');
                // Fallback: segui il link normale
                window.location.href = $(this).attr('href');
            }
        });
    });

})(jQuery);
