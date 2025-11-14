/**
 * ============================================================================
 * SMART HEADER SYSTEM - JAVASCRIPT
 * ============================================================================
 *
 * Gestisce il comportamento dinamico dell'header smart:
 * - Nasconde l'header quando si scrolla giù
 * - Mostra l'header quando si scrolla su
 * - Applica effetto blur dopo una certa soglia di scroll
 *
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /* ========================================================================
       CONFIGURAZIONE
       ======================================================================== */

    const CONFIG = {
        // Pixel di scroll prima di nascondere l'header
        scrollThreshold: 100,

        // Pixel minimi di movimento per rilevare cambio direzione
        scrollDelta: 5,

        // Pixel di scroll prima di attivare l'effetto blur
        blurThreshold: 50,

        // Delay in millisecondi prima di nascondere l'header
        hideDelay: 0,

        // Delay in millisecondi prima di mostrare l'header
        showDelay: 0,

        // Intervallo di throttling per eventi scroll (ms)
        throttleDelay: 100,

        // Selettore CSS per l'header
        headerSelector: '.smart-header',

        // Debug mode
        debug: false
    };

    /* ========================================================================
       VARIABILI GLOBALI
       ======================================================================== */

    let headerElement = null;
    let lastScrollTop = 0;
    let scrollDirection = null;
    let hideTimeout = null;
    let showTimeout = null;
    let ticking = false;
    let isEditorActive = false;

    /* ========================================================================
       FUNZIONI UTILITY
       ======================================================================== */

    /**
     * Log di debug condizionale
     */
    function debugLog(message, data = null) {
        if (CONFIG.debug) {
            if (data !== null) {
                console.log('[Smart Header] ' + message, data);
            } else {
                console.log('[Smart Header] ' + message);
            }
        }
    }

    /**
     * Throttle function
     */
    function throttle(func, delay) {
        let lastCall = 0;
        return function(...args) {
            const now = Date.now();
            if (now - lastCall >= delay) {
                lastCall = now;
                return func.apply(this, args);
            }
        };
    }

    /**
     * Verifica se siamo nell'editor di Elementor
     */
    function isElementorEditor() {
        return $('body').hasClass('elementor-editor-active') ||
               (typeof window.elementorFrontend !== 'undefined' &&
                typeof window.elementorFrontend.isEditMode === 'function' &&
                window.elementorFrontend.isEditMode()) ||
               typeof window.elementor !== 'undefined';
    }

    /* ========================================================================
       GESTIONE CLASSI HEADER
       ======================================================================== */

    /**
     * Mostra l'header
     */
    function showHeader() {
        if (!headerElement || isEditorActive) return;

        clearTimeout(hideTimeout);

        const execute = function() {
            headerElement.removeClass('hide');
            headerElement.addClass('show');
            debugLog('Header mostrato');
        };

        if (CONFIG.showDelay > 0) {
            showTimeout = setTimeout(execute, CONFIG.showDelay);
        } else {
            execute();
        }
    }

    /**
     * Nasconde l'header
     */
    function hideHeader() {
        if (!headerElement || isEditorActive) return;

        clearTimeout(showTimeout);

        const execute = function() {
            headerElement.removeClass('show');
            headerElement.addClass('hide');
            debugLog('Header nascosto');
        };

        if (CONFIG.hideDelay > 0) {
            hideTimeout = setTimeout(execute, CONFIG.hideDelay);
        } else {
            execute();
        }
    }

    /**
     * Gestisce l'effetto blur basato sulla posizione di scroll
     */
    function handleBlurEffect(scrollTop) {
        if (!headerElement || isEditorActive) return;

        if (scrollTop > CONFIG.blurThreshold) {
            if (!headerElement.hasClass('scrolled')) {
                headerElement.addClass('scrolled');
                debugLog('Blur effect attivato');
            }
        } else {
            if (headerElement.hasClass('scrolled')) {
                headerElement.removeClass('scrolled');
                debugLog('Blur effect disattivato');
            }
        }
    }

    /**
     * Calcola l'altezza dell'header e aggiorna CSS variable
     */
    function updateHeaderHeight() {
        if (!headerElement) return;

        const headerHeight = headerElement.outerHeight();
        $('html').css('--smart-header-height', headerHeight + 'px');
        debugLog('Header height aggiornato', headerHeight);
    }

    /* ========================================================================
       GESTIONE SCROLL
       ======================================================================== */

    /**
     * Handler principale dello scroll
     */
    function handleScroll() {
        if (!headerElement || isEditorActive) return;

        const currentScrollTop = $(window).scrollTop();

        // Previeni calcoli se non c'è movimento significativo
        if (Math.abs(currentScrollTop - lastScrollTop) < CONFIG.scrollDelta) {
            return;
        }

        // Determina direzione scroll
        const newDirection = currentScrollTop > lastScrollTop ? 'down' : 'up';

        debugLog('Scroll ' + newDirection, {
            current: currentScrollTop,
            last: lastScrollTop,
            delta: currentScrollTop - lastScrollTop
        });

        // Gestisci visibilità header basata su direzione e posizione
        if (newDirection === 'down' && currentScrollTop > CONFIG.scrollThreshold) {
            // Scroll DOWN oltre la soglia → Nascondi header
            hideHeader();
        } else if (newDirection === 'up') {
            // Scroll UP → Mostra header
            showHeader();
        } else if (currentScrollTop <= CONFIG.scrollThreshold) {
            // Vicino al top → Mostra sempre header
            showHeader();
        }

        // Gestisci effetto blur
        handleBlurEffect(currentScrollTop);

        // Aggiorna variabili di tracking
        scrollDirection = newDirection;
        lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
    }

    /**
     * Handler ottimizzato con requestAnimationFrame
     */
    function optimizedScrollHandler() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }

    /**
     * Throttled scroll handler
     */
    const throttledScrollHandler = throttle(optimizedScrollHandler, CONFIG.throttleDelay);

    /* ========================================================================
       INIZIALIZZAZIONE
       ======================================================================== */

    /**
     * Inizializza il sistema smart header
     */
    function init() {
        debugLog('Inizializzazione Smart Header System...');

        // Verifica se siamo nell'editor
        isEditorActive = isElementorEditor();

        if (isEditorActive) {
            debugLog('⚠️ Editor Elementor rilevato - Smart Header disabilitato');
            return;
        }

        // Trova l'elemento header
        headerElement = $(CONFIG.headerSelector);

        if (!headerElement.length) {
            console.warn('[Smart Header] ⚠️ Elemento con classe "' + CONFIG.headerSelector + '" non trovato!');
            console.warn('[Smart Header] Assicurati di aver aggiunto la classe "smart-header" al container principale dell\'header in Elementor.');
            return;
        }

        debugLog('✅ Header element trovato', headerElement);

        // Imposta stato iniziale
        lastScrollTop = $(window).scrollTop();

        // Calcola altezza header
        updateHeaderHeight();

        // Applica stato iniziale
        if (lastScrollTop > CONFIG.blurThreshold) {
            headerElement.addClass('scrolled');
        }
        headerElement.addClass('show');

        // Event listener per lo scroll
        $(window).on('scroll', throttledScrollHandler);

        // Event listener per resize
        $(window).on('resize', throttle(function() {
            debugLog('Window resized - Ricalcolo stato header');
            updateHeaderHeight();
            handleScroll();
        }, 250));

        debugLog('✅ Smart Header System inizializzato con successo');
        debugLog('Configurazione attiva:', CONFIG);
    }

    /**
     * Cleanup
     */
    function cleanup() {
        debugLog('Cleanup Smart Header System...');

        $(window).off('scroll', throttledScrollHandler);

        if (hideTimeout) clearTimeout(hideTimeout);
        if (showTimeout) clearTimeout(showTimeout);
    }

    /* ========================================================================
       AVVIO
       ======================================================================== */

    /**
     * Avvia quando jQuery e DOM sono pronti
     */
    $(document).ready(function() {
        init();
    });

    /**
     * Cleanup su unload
     */
    $(window).on('beforeunload', cleanup);

    /**
     * Gestione Elementor frontend
     */
    if (typeof window.elementorFrontend !== 'undefined') {
        $(window).on('elementor/frontend/init', function() {
            debugLog('Elementor frontend init');
            setTimeout(function() {
                if (!isElementorEditor() && !headerElement) {
                    init();
                }
            }, 100);
        });
    }

})(jQuery);
