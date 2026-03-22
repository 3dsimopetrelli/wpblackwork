/**
 * BW Product Slider Widget — JavaScript
 * Usa BWEmblaCore (bw-embla-core.js) per il carousel orizzontale.
 * Mostra product card (BW_Product_Card_Component) tramite WP_Query.
 * Nessun popup, nessun custom cursor, nessun layout verticale.
 */
(function ($) {
    'use strict';

    function debounce(fn, ms) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, arguments), ms);
        };
    }

    class BWProductSlider {
        constructor(element) {
            this.$wrapper  = $(element);
            this.widgetId  = this.$wrapper.data('widget-id');
            this.config    = this.$wrapper.data('config') || {};
            this.initialized = false;

            // Istanza Embla
            this.emblaCore = null;

            // Sorted breakpoints per Embla reInit (ascendente: primo match = breakpoint attivo)
            this._sortedBreakpoints   = [];
            this._lastBreakpointIndex = undefined;

            this.init();
        }

        /* ────────────────────────────────────────────
           INIT
        ──────────────────────────────────────────── */

        init() {
            if (this.initialized) return;
            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Product Slider: BWEmblaCore non disponibile.');
                return;
            }

            const responsive = this.config.horizontal?.responsive || [];
            // Ascendente: il primo match è il breakpoint più piccolo attivo
            this._sortedBreakpoints = [...responsive].sort((a, b) => a.breakpoint - b.breakpoint);

            this.initHorizontalLayout();
            this.initialized = true;
        }

        /* ────────────────────────────────────────────
           LAYOUT HORIZONTAL
        ──────────────────────────────────────────── */

        initHorizontalLayout() {
            const viewport = this.$wrapper.find('.bw-ps-embla-viewport')[0];
            if (!viewport) return;

            this.$wrapper.addClass('loading');

            const hCfg     = this.config.horizontal || {};
            const dotsPos  = this.config.dotsPosition || 'center';
            const prevBtn  = this.$wrapper.find('.bw-ps-arrow-prev')[0];
            const nextBtn  = this.$wrapper.find('.bw-ps-arrow-next')[0];
            const dotsCont = this.$wrapper.find('.bw-ps-dots-container')[0];

            // Autoplay options (false se disabilitato)
            const autoplayOpts = hCfg.autoplay ? {
                delay:             hCfg.autoplaySpeed || 3000,
                playOnInit:        true,
                stopOnInteraction: true,
                stopOnMouseEnter:  hCfg.pauseOnHover !== false,
                stopOnFocusIn:     true,
                jump:              false,
            } : false;

            const globalAlign     = hCfg.align || 'start';
            // enableTouchDrag: quando false, il drag via touch/pen è disabilitato;
            // il mouse desktop funziona sempre.
            const enableTouchDrag = hCfg.enableTouchDrag !== false; // default true

            const desktopPeek   = hCfg.desktopPeek > 0;
            const emblaOptions = {
                loop:           hCfg.infinite === true,
                align:          globalAlign,
                containScroll:  (desktopPeek || globalAlign !== 'start') ? false : 'trimSnaps',
                slidesToScroll: 1,
                dragFree:       hCfg.dragFree === true,
                watchResize:    true,
                watchDrag: enableTouchDrag
                    ? true
                    : (_emblaApi, evt) => evt.pointerType === 'mouse',
            };

            this.emblaCore = new BWEmblaCore(viewport, emblaOptions, {
                prevBtn,
                nextBtn,
                dotsContainer: dotsCont,
                dotsPosition:  dotsPos,
                autoplay:      autoplayOpts,
            });

            const api = this.emblaCore.init();

            // Reveal wrapper dopo che la prima card è visibile
            // (prima immagine caricata o timeout di sicurezza 2s)
            const firstImg = this.$wrapper.find('.bw-ps-slide').first().find('img')[0];
            const revealWrapper = () => this.$wrapper.removeClass('loading');

            if (!firstImg) {
                revealWrapper();
            } else if (firstImg.complete && firstImg.naturalWidth > 0) {
                requestAnimationFrame(revealWrapper);
            } else {
                let revealed = false;
                const _done = () => {
                    if (revealed) return;
                    revealed = true;
                    firstImg.removeEventListener('load',  _done);
                    firstImg.removeEventListener('error', _done);
                    revealWrapper();
                };
                firstImg.addEventListener('load',  _done);
                firstImg.addEventListener('error', _done);
                setTimeout(_done, 2000); // fallback
            }

            if (!api) return;

            // Embla breakpoint options (slidesToScroll, align, containScroll)
            this._updateEmblaBreakpointOptions();

            $(window).on(`resize.bwpsl-${this.widgetId}`, debounce(() => {
                this._updateEmblaBreakpointOptions();
            }, 150));
        }

        /* ────────────────────────────────────────────
           EMBLA BREAKPOINT OPTIONS
        ──────────────────────────────────────────── */

        /**
         * Restituisce l'indice del breakpoint attivo in _sortedBreakpoints,
         * oppure -1 se nessun breakpoint è attivo (desktop).
         */
        _getActiveBreakpointIndex() {
            const width = $(window).width();
            for (let i = 0; i < this._sortedBreakpoints.length; i++) {
                if (width <= this._sortedBreakpoints[i].breakpoint) {
                    return i; // array ascendente: primo match è il breakpoint corretto
                }
            }
            return -1; // nessun breakpoint attivo → desktop
        }

        /**
         * Chiama emblaApi.reInit() solo quando cambia il breakpoint attivo,
         * aggiornando slidesToScroll, align (centerMode) e containScroll.
         */
        _updateEmblaBreakpointOptions() {
            if (!this.emblaCore) return;
            const api = this.emblaCore.api();
            if (!api) return;

            const idx = this._getActiveBreakpointIndex();
            if (idx === this._lastBreakpointIndex) return; // nessun cambio
            this._lastBreakpointIndex = idx;

            const bp          = idx >= 0 ? this._sortedBreakpoints[idx] : null;
            const hCfg        = this.config.horizontal || {};
            const baseAlign   = hCfg.align || 'start';
            const activeAlign = bp?.centerMode ? 'center' : baseAlign;

            const hasPeek   = bp ? (bp.peek > 0) : (hCfg.desktopPeek > 0);
            const newOpts = {
                slidesToScroll: bp?.slidesToScroll || 1,
                align:          activeAlign,
                containScroll:  (bp?.centerMode || bp?.variableWidth || activeAlign !== 'start' || hasPeek) ? false : 'trimSnaps',
            };

            api.reInit(newOpts);
        }

        /* ────────────────────────────────────────────
           DESTROY
        ──────────────────────────────────────────── */

        destroy() {
            if (this.emblaCore) {
                this.emblaCore.destroy();
                this.emblaCore = null;
            }

            $(window).off(`.bwpsl-${this.widgetId}`);

            this._sortedBreakpoints   = null;
            this._lastBreakpointIndex = null;
            this.config               = null;
            this.initialized          = false;
        }
    }

    /* ────────────────────────────────────────────
       INIZIALIZZAZIONE
    ──────────────────────────────────────────── */

    function initWidgets() {
        $('.bw-product-slider-wrapper').each(function () {
            const $wrapper = $(this);
            if ($wrapper.data('bw-product-slider-instance')) return;
            const instance = new BWProductSlider(this);
            $wrapper.data('bw-product-slider-instance', instance);
        });
    }

    function isElementorEditMode() {
        return (
            typeof elementorFrontend !== 'undefined' &&
            typeof elementorFrontend.isEditMode === 'function' &&
            elementorFrontend.isEditMode()
        );
    }

    $(document).ready(function () {
        initWidgets();
    });

    let hooksRegistered = false;

    function registerElementorHooks() {
        if (hooksRegistered) return;
        if (
            typeof elementorFrontend === 'undefined' ||
            !elementorFrontend.hooks ||
            typeof elementorFrontend.hooks.addAction !== 'function'
        ) return;

        hooksRegistered = true;

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-product-slider.default',
            function ($scope) {
                const $wrapper = $scope.find('.bw-product-slider-wrapper');
                if (!$wrapper.length) return;

                const existing = $wrapper.data('bw-product-slider-instance');
                if (existing) {
                    // In edit mode: full destroy/re-init for live preview updates.
                    // On frontend: skip re-init to avoid resetting a working instance.
                    if (!isElementorEditMode()) {
                        return;
                    }

                    existing.destroy();
                    $wrapper.removeData('bw-product-slider-instance');
                }

                const instance = new BWProductSlider($wrapper[0]);
                $wrapper.data('bw-product-slider-instance', instance);
            }
        );
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

    window.BWProductSlider = BWProductSlider;

})(jQuery);
