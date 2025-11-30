/**
 * ============================================================================
 * SMART HEADER SCROLL ANIMATION SYSTEM
 * ============================================================================
 *
 * Gestisce il comportamento dinamico dell'header smart con animazioni fluide:
 * - Nasconde l'header quando si scrolla giù oltre 100px
 * - Mostra l'header IMMEDIATAMENTE quando si scrolla su (anche 1px)
 * - Applica effetto blur dopo 50px di scroll
 * - Calcola automaticamente offset WordPress Admin Bar
 * - Animazioni speculari per scroll up/down usando CSS transitions
 *
 * Performance:
 * - requestAnimationFrame per ottimizzazione
 * - Throttle per limitare chiamate
 * - Passive event listeners
 * - GPU acceleration via CSS
 * - CSS variables per offset dinamici
 *
 * @version 2.1.0
 */

(function($) {
    'use strict';

    /* ========================================================================
       CONFIGURAZIONE
       ======================================================================== */

    // Configurazione di default - può essere sovrascritto da wp_localize_script
    const DEFAULT_CONFIG = {
        // Pixel di scroll prima di nascondere l'header (scroll DOWN)
        scrollDownThreshold: 100,

        // Threshold scroll UP - IMMEDIATO (anche 1px)
        scrollUpThreshold: 0,

        // Pixel minimi di movimento per rilevare cambio direzione
        scrollDelta: 1,

        // Pixel di scroll prima di attivare l'effetto blur
        blurThreshold: 50,

        // Intervallo di throttling per eventi scroll (ms)
        throttleDelay: 16, // ~60fps

        // Selettore CSS per l'header
        headerSelector: '.smart-header',

        // Debug mode
        debug: false
    };

    // Merge con configurazione da WordPress (se presente)
    const CONFIG = typeof bwSmartHeaderConfig !== 'undefined'
        ? Object.assign({}, DEFAULT_CONFIG, bwSmartHeaderConfig)
        : DEFAULT_CONFIG;

    /* ========================================================================
       VARIABILI GLOBALI
       ======================================================================== */

    let headerElement = null;
    let lastScrollTop = 0;
    let scrollDirection = null;
    let ticking = false;
    let isEditorActive = false;
    let adminBarHeight = 0;
    let animatedBannerHeight = 0;
    let animatedBannerElement = null;

    /* ========================================================================
       FUNZIONI UTILITY
       ======================================================================== */

    /**
     * Log di debug condizionale
     */
    function debugLog(message, data = null) {
        if (CONFIG.debug) {
            const logMessage = '[Smart Header] ' + message;
            if (data !== null) {
                console.log(logMessage, data);
            } else {
                console.log(logMessage);
            }
        }
    }

    /**
     * Throttle function - limita la frequenza di esecuzione
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

    /**
     * Calcola l'altezza del BW Animated Banner se presente
     * Il banner deve essere posizionato PRIMA dello smart-header nel DOM
     */
    function calculateAnimatedBannerHeight() {
        // Cerca il banner animato che precede lo smart-header
        animatedBannerElement = $('.bw-animated-banner').first();

        if (animatedBannerElement.length && animatedBannerElement.is(':visible')) {
            animatedBannerHeight = animatedBannerElement.outerHeight() || 0;
            debugLog('BW Animated Banner rilevato', { height: animatedBannerHeight + 'px' });
        } else {
            animatedBannerHeight = 0;
            debugLog('BW Animated Banner non presente');
        }

        // Applica l'altezza usando CSS variable
        document.documentElement.style.setProperty('--animated-banner-height', animatedBannerHeight + 'px');
        debugLog('CSS variable impostata', { '--animated-banner-height': animatedBannerHeight + 'px' });
    }

    /**
     * Calcola e applica l'offset per la WordPress admin bar
     * Usa CSS variable per permettere transizioni smooth
     */
    function calculateAdminBarOffset() {
        const adminBar = $('#wpadminbar');

        if (adminBar.length && adminBar.is(':visible')) {
            adminBarHeight = adminBar.outerHeight() || 0;
            debugLog('WordPress admin bar rilevata', { height: adminBarHeight + 'px' });
        } else {
            adminBarHeight = 0;
            debugLog('WordPress admin bar non presente');
        }

        // Applica l'offset usando CSS variable
        document.documentElement.style.setProperty('--wp-admin-bar-height', adminBarHeight + 'px');
        debugLog('CSS variable impostata', { '--wp-admin-bar-height': adminBarHeight + 'px' });
    }

    /**
     * Calcola e applica tutti gli offset (admin bar + animated banner)
     * Aggiorna anche il padding del body dinamicamente
     */
    function calculateAllOffsets() {
        calculateAdminBarOffset();
        calculateAnimatedBannerHeight();

        // Calcola l'offset totale per lo smart-header
        const totalTopOffset = adminBarHeight + animatedBannerHeight;
        document.documentElement.style.setProperty('--smart-header-top-offset', totalTopOffset + 'px');

        // Calcola il padding del body (header height + offsets)
        if (headerElement && headerElement.length) {
            const headerHeight = headerElement.outerHeight() || 0;
            const totalBodyPadding = totalTopOffset + headerHeight;
            document.documentElement.style.setProperty('--smart-header-body-padding', totalBodyPadding + 'px');

            debugLog('Offset totali calcolati', {
                adminBar: adminBarHeight + 'px',
                banner: animatedBannerHeight + 'px',
                header: headerHeight + 'px',
                totalTop: totalTopOffset + 'px',
                bodyPadding: totalBodyPadding + 'px'
            });
        }
    }

    /* ========================================================================
       GESTIONE CLASSI HEADER
       ======================================================================== */

    /**
     * Mostra l'header con animazione slideDown
     */
    function showHeader() {
        if (!headerElement || isEditorActive) return;

        headerElement.removeClass('hidden');
        headerElement.addClass('visible');

        debugLog('Header mostrato (visible)');
    }

    /**
     * Nasconde l'header con slide verso l'alto
     */
    function hideHeader() {
        if (!headerElement || isEditorActive) return;

        headerElement.removeClass('visible');
        headerElement.addClass('hidden');

        debugLog('Header nascosto (hidden)');
    }

    /**
     * Gestisce l'effetto blur basato sulla posizione di scroll
     * Attivo dopo 50px di scroll
     */
    function handleBlurEffect(scrollTop) {
        if (!headerElement || isEditorActive) return;

        if (scrollTop > CONFIG.blurThreshold) {
            if (!headerElement.hasClass('scrolled')) {
                headerElement.addClass('scrolled');
                debugLog('Blur effect attivato', { scrollTop });
            }
        } else {
            if (headerElement.hasClass('scrolled')) {
                headerElement.removeClass('scrolled');
                debugLog('Blur effect disattivato', { scrollTop });
            }
        }
    }

    /* ========================================================================
       GESTIONE SCROLL
       ======================================================================== */

    /**
     * Handler principale dello scroll con logica ottimizzata
     */
    function handleScroll() {
        if (!headerElement || isEditorActive) return;

        const currentScrollTop = Math.max(0, $(window).scrollTop());

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

        // ====================================================================
        // LOGICA PRINCIPALE:
        // ====================================================================

        if (newDirection === 'up') {
            // SCROLL UP → Mostra IMMEDIATAMENTE (anche 1px)
            showHeader();

        } else if (newDirection === 'down') {
            // SCROLL DOWN
            if (currentScrollTop > CONFIG.scrollDownThreshold) {
                // Oltre 100px → Nascondi header
                hideHeader();
            } else {
                // Sotto 100px → Mostra header
                showHeader();
            }
        }

        // Gestisci effetto blur (attivo dopo 50px)
        handleBlurEffect(currentScrollTop);

        // Aggiorna variabili di tracking
        scrollDirection = newDirection;
        lastScrollTop = currentScrollTop;
    }

    /**
     * Handler ottimizzato con requestAnimationFrame
     * Garantisce performance fluide a 60fps
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
     * Throttled scroll handler per limitare le chiamate
     */
    const throttledScrollHandler = throttle(optimizedScrollHandler, CONFIG.throttleDelay);

    /* ========================================================================
       INIZIALIZZAZIONE
       ======================================================================== */

    /**
     * Inizializza il sistema smart header
     */
    function init() {
        debugLog('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        debugLog('Inizializzazione Smart Header Scroll System');
        debugLog('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Verifica se siamo nell'editor
        isEditorActive = isElementorEditor();

        if (isEditorActive) {
            debugLog('⚠️  Editor Elementor rilevato - Smart Header disabilitato');
            return;
        }

        // Trova l'elemento header
        headerElement = $(CONFIG.headerSelector);

        if (!headerElement.length) {
            console.warn('[Smart Header] ⚠️  Elemento "' + CONFIG.headerSelector + '" non trovato!');
            console.warn('[Smart Header] Aggiungi la classe "smart-header" al container dell\'header in Elementor.');
            return;
        }

        debugLog('✅ Header element trovato', {
            selector: CONFIG.headerSelector,
            height: headerElement.outerHeight() + 'px'
        });

        // Imposta stato iniziale
        lastScrollTop = Math.max(0, $(window).scrollTop());

        // Applica stato iniziale
        if (lastScrollTop > CONFIG.blurThreshold) {
            headerElement.addClass('scrolled');
        }

        // Header sempre visibile all'inizio
        headerElement.addClass('visible');

        // Calcola e applica tutti gli offset (admin bar + animated banner)
        calculateAllOffsets();

        // ====================================================================
        // EVENT LISTENERS con opzione PASSIVE per performance
        // ====================================================================

        // Scroll event con passive listener
        window.addEventListener('scroll', throttledScrollHandler, { passive: true });
        debugLog('✅ Scroll event listener registrato (passive)');

        // Resize event con throttle e passive
        const throttledResizeHandler = throttle(function() {
            debugLog('Window resized - Ricalcolo stato');
            calculateAllOffsets(); // Ricalcola tutti gli offset su resize
            handleScroll();
        }, 250);

        window.addEventListener('resize', throttledResizeHandler, { passive: true });
        debugLog('✅ Resize event listener registrato (passive)');

        debugLog('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        debugLog('✅ Sistema inizializzato con successo');
        debugLog('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        debugLog('Configurazione attiva:', CONFIG);
    }

    /**
     * Cleanup delle risorse
     */
    function cleanup() {
        debugLog('Cleanup Smart Header System...');

        window.removeEventListener('scroll', throttledScrollHandler);
        window.removeEventListener('resize', throttledScrollHandler);
    }

    /* ========================================================================
       AVVIO
       ======================================================================== */

    /**
     * Avvia quando jQuery e DOM sono pronti
     */
    $(document).ready(function() {
        // Piccolo delay per assicurarsi che Elementor sia completamente caricato
        setTimeout(init, 100);
    });

    /**
     * Cleanup su unload
     */
    $(window).on('beforeunload', cleanup);

    /**
     * Gestione Elementor frontend (se presente)
     */
    if (typeof window.elementorFrontend !== 'undefined') {
        $(window).on('elementor/frontend/init', function() {
            debugLog('Elementor frontend init event');
            setTimeout(function() {
                if (!isElementorEditor() && !headerElement) {
                    init();
                }
            }, 150);
        });
    }

    /* ========================================================================
       API PUBBLICA (opzionale - per debugging)
       ======================================================================== */

    // Esponi API globale per debugging in console
    window.bwSmartHeader = {
        version: '2.2.0',
        config: CONFIG,
        show: showHeader,
        hide: hideHeader,
        getState: function() {
            return {
                scrollTop: lastScrollTop,
                direction: scrollDirection,
                isVisible: headerElement ? headerElement.hasClass('visible') : null,
                isHidden: headerElement ? headerElement.hasClass('hidden') : null,
                hasBlur: headerElement ? headerElement.hasClass('scrolled') : null,
                adminBarHeight: adminBarHeight,
                animatedBannerHeight: animatedBannerHeight,
                totalTopOffset: adminBarHeight + animatedBannerHeight
            };
        },
        recalculateAdminBar: calculateAdminBarOffset,
        recalculateAllOffsets: calculateAllOffsets
    };

})(jQuery);
