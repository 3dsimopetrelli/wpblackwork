/**
 * ============================================================================
 * BW SMART HEADER - JAVASCRIPT
 * ============================================================================
 *
 * Gestisce il comportamento dinamico dell'header smart:
 * - Nasconde l'header quando si scrolla giù
 * - Mostra l'header quando si scrolla su (anche con movimento minimo)
 * - Applica effetto blur dopo una certa soglia di scroll
 *
 * Performance:
 * - Usa requestAnimationFrame per animazioni smooth
 * - Throttling degli eventi scroll
 * - Passive event listeners
 * - GPU acceleration tramite transform
 *
 * @version 1.0.0
 */

(function() {
    'use strict';

    /* ========================================================================
       CONFIGURAZIONE
       ======================================================================== */

    /**
     * Oggetto di configurazione principale
     * Modifica questi valori per personalizzare il comportamento
     */
    const CONFIG = {
        // Pixel di scroll prima di nascondere l'header (scroll down)
        scrollThreshold: 100,

        // Pixel minimi di movimento per rilevare cambio direzione
        scrollDelta: 5,

        // Pixel di scroll prima di attivare l'effetto blur
        blurThreshold: 50,

        // Delay in millisecondi prima di nascondere l'header (0 = immediato)
        hideDelay: 0,

        // Delay in millisecondi prima di mostrare l'header (0 = immediato)
        showDelay: 0,

        // Intervallo di throttling per eventi scroll (ms)
        throttleDelay: 100,

        // Selettore CSS per l'header
        headerSelector: '.SmartAdder',

        // Debug mode - mostra log in console
        debug: false // 👈 Cambia a true per vedere i log di debug
    };

    /* ========================================================================
       VARIABILI GLOBALI
       ======================================================================== */

    let headerElement = null;
    let lastScrollTop = 0;
    let isScrolling = false;
    let scrollDirection = null;
    let hideTimeout = null;
    let showTimeout = null;
    let ticking = false;

    /* ========================================================================
       FUNZIONI UTILITY
       ======================================================================== */

    /**
     * Log di debug condizionale
     * @param {string} message - Messaggio da loggare
     * @param {*} data - Dati opzionali da mostrare
     */
    function debugLog(message, data = null) {
        if (CONFIG.debug) {
            if (data !== null) {
                console.log(`[BW Smart Header] ${message}`, data);
            } else {
                console.log(`[BW Smart Header] ${message}`);
            }
        }
    }

    /**
     * Throttle function - Limita la frequenza di esecuzione di una funzione
     * @param {Function} func - Funzione da eseguire
     * @param {number} delay - Delay in millisecondi
     * @return {Function} - Funzione throttled
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
     * @return {boolean}
     */
    function isElementorEditor() {
        return document.body.classList.contains('elementor-editor-active') ||
               (typeof window.elementor !== 'undefined' && window.elementor.isEditMode);
    }

    /**
     * Verifica se siamo nell'anteprima di Elementor
     * @return {boolean}
     */
    function isElementorPreview() {
        return window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode();
    }

    /* ========================================================================
       GESTIONE CLASSI HEADER
       ======================================================================== */

    /**
     * Mostra l'header
     */
    function showHeader() {
        if (!headerElement) return;

        clearTimeout(hideTimeout);

        const execute = () => {
            headerElement.classList.remove('hide');
            headerElement.classList.add('show');
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
        if (!headerElement) return;

        clearTimeout(showTimeout);

        const execute = () => {
            headerElement.classList.remove('show');
            headerElement.classList.add('hide');
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
     * @param {number} scrollTop - Posizione corrente di scroll
     */
    function handleBlurEffect(scrollTop) {
        if (!headerElement) return;

        if (scrollTop > CONFIG.blurThreshold) {
            if (!headerElement.classList.contains('scrolled')) {
                headerElement.classList.add('scrolled');
                debugLog('Blur effect attivato');
            }
        } else {
            if (headerElement.classList.contains('scrolled')) {
                headerElement.classList.remove('scrolled');
                debugLog('Blur effect disattivato');
            }
        }
    }

    /* ========================================================================
       GESTIONE SCROLL
       ======================================================================== */

    /**
     * Handler principale dello scroll
     * Determina la direzione e aggiorna lo stato dell'header
     */
    function handleScroll() {
        if (!headerElement) return;

        const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Previeni calcoli se non c'è movimento significativo
        if (Math.abs(currentScrollTop - lastScrollTop) < CONFIG.scrollDelta) {
            return;
        }

        // Determina direzione scroll
        const newDirection = currentScrollTop > lastScrollTop ? 'down' : 'up';

        debugLog(`Scroll ${newDirection}`, {
            current: currentScrollTop,
            last: lastScrollTop,
            delta: currentScrollTop - lastScrollTop
        });

        // Gestisci visibilità header basata su direzione e posizione
        if (newDirection === 'down' && currentScrollTop > CONFIG.scrollThreshold) {
            // Scroll DOWN oltre la soglia → Nascondi header
            hideHeader();
        } else if (newDirection === 'up') {
            // Scroll UP (qualsiasi direzione verso l'alto) → Mostra header
            showHeader();
        } else if (currentScrollTop <= CONFIG.scrollThreshold) {
            // Vicino al top della pagina → Mostra sempre header
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
     * Garantisce animazioni smooth sincronizzate con il refresh del browser
     */
    function optimizedScrollHandler() {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }

    /**
     * Throttled scroll handler
     * Limita la frequenza di esecuzione per migliori performance
     */
    const throttledScrollHandler = throttle(optimizedScrollHandler, CONFIG.throttleDelay);

    /* ========================================================================
       INIZIALIZZAZIONE E CLEANUP
       ======================================================================== */

    /**
     * Inizializza il sistema smart header
     */
    function init() {
        debugLog('Inizializzazione BW Smart Header System...');

        // Trova l'elemento header
        headerElement = document.querySelector(CONFIG.headerSelector);

        if (!headerElement) {
            debugLog('⚠️ Elemento con classe "SmartAdder" non trovato - Smart Header non attivato');
            return;
        }

        debugLog('✅ Header element trovato', headerElement);
        debugLog('Modalità Elementor:', {
            isEditor: isElementorEditor(),
            isPreview: isElementorPreview()
        });

        // Imposta stato iniziale
        lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Applica stato iniziale
        if (lastScrollTop > CONFIG.blurThreshold) {
            headerElement.classList.add('scrolled');
        }
        headerElement.classList.add('show');

        // Aggiungi event listener per lo scroll (passive per migliori performance)
        window.addEventListener('scroll', throttledScrollHandler, { passive: true });

        // Event listener per resize (ricalcola stato)
        window.addEventListener('resize', throttle(() => {
            debugLog('Window resized - Ricalcolo stato header');
            handleScroll();
        }, 250), { passive: true });

        debugLog('✅ BW Smart Header System inizializzato con successo');
        debugLog('Configurazione attiva:', CONFIG);
    }

    /**
     * Cleanup - Rimuove event listeners
     * Chiamato quando la pagina viene chiusa o cambiata
     */
    function cleanup() {
        debugLog('Cleanup BW Smart Header System...');

        window.removeEventListener('scroll', throttledScrollHandler);

        if (hideTimeout) clearTimeout(hideTimeout);
        if (showTimeout) clearTimeout(showTimeout);
    }

    /* ========================================================================
       AVVIO
       ======================================================================== */

    /**
     * Avvia il sistema quando il DOM è pronto
     */
    function startWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            // DOM già caricato
            init();
        }
    }

    /**
     * Cleanup su unload
     */
    window.addEventListener('beforeunload', cleanup);

    /**
     * Per Elementor: Inizializza quando il frontend è pronto
     */
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
            // Verifica se l'elemento contiene il nostro header
            if ($scope.find(CONFIG.headerSelector).length > 0 || $scope.hasClass('SmartAdder')) {
                debugLog('Elementor frontend ready - Inizializzazione Smart Header');
                setTimeout(init, 100); // Piccolo delay per assicurarsi che tutto sia renderizzato
            }
        });

        // Avvia anche normalmente
        startWhenReady();
    } else {
        // Non siamo in Elementor, avvia normalmente
        startWhenReady();
    }

    /**
     * Reinizializza quando Elementor carica preview
     */
    if (typeof jQuery !== 'undefined') {
        jQuery(window).on('elementor/frontend/init', function() {
            debugLog('Elementor frontend initialized');
            setTimeout(init, 200);
        });
    }

})();
