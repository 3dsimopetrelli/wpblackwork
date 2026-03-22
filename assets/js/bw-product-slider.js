/**
 * BW Product Slider Widget — JavaScript
 * Usa BWEmblaCore (bw-embla-core.js) per il carousel orizzontale.
 * Mostra product card via WP_Query. Nessun popup, nessun layout verticale.
 */
(function ($) {
    'use strict';

    const debounce = (fn, ms) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    class BWProductSlider {
        constructor(element) {
            this.$wrapper  = $(element);
            this.widgetId  = this.$wrapper.data('widget-id');
            this.config    = this.$wrapper.data('config') || {};
            this.emblaCore = null;
            this._wheelHandler         = null;
            this._sortedBreakpoints   = [];
            this._lastBreakpointIndex = undefined;
            this.initialized          = false;

            this._init();
        }

        /* ────────────────────────────────────────────
           INIT
        ──────────────────────────────────────────── */

        _init() {
            if (this.initialized) return;
            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Product Slider: BWEmblaCore non disponibile.');
                return;
            }

            const responsive = this.config.horizontal?.responsive || [];
            this._sortedBreakpoints = [...responsive].sort((a, b) => a.breakpoint - b.breakpoint);

            this._initCarousel();
            this.initialized = true;
        }

        /* ────────────────────────────────────────────
           CAROUSEL
        ──────────────────────────────────────────── */

        _initCarousel() {
            const viewport = this.$wrapper.find('.bw-ps-embla-viewport')[0];
            if (!viewport) return;

            this.$wrapper.addClass('loading');

            const hCfg       = this.config.horizontal || {};
            const globalAlign = hCfg.align || 'start';

            // watchDrag: combine touch + mouse toggles
            const enableTouch = hCfg.enableTouchDrag !== false;
            const enableMouse = hCfg.enableMouseDrag !== false;
            let watchDrag;
            if (enableTouch && enableMouse)          watchDrag = true;
            else if (!enableTouch && !enableMouse)   watchDrag = false;
            else if (enableMouse)                    watchDrag = (_, e) => e.pointerType === 'mouse';
            else                                     watchDrag = (_, e) => e.pointerType !== 'mouse';

            const autoplayOpts = hCfg.autoplay ? {
                delay:             hCfg.autoplaySpeed || 3000,
                playOnInit:        true,
                stopOnInteraction: true,
                stopOnMouseEnter:  hCfg.pauseOnHover !== false,
                stopOnFocusIn:     true,
                jump:              false,
            } : false;

            this.emblaCore = new BWEmblaCore(viewport, {
                loop:           hCfg.infinite === true,
                align:          globalAlign,
                containScroll:  globalAlign === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree:       hCfg.dragFree === true,
                watchResize:    true,
                watchDrag,
            }, {
                prevBtn:       this.$wrapper.find('.bw-ps-arrow-prev')[0],
                nextBtn:       this.$wrapper.find('.bw-ps-arrow-next')[0],
                dotsContainer: this.$wrapper.find('.bw-ps-dots-container')[0],
                dotsPosition:  this.config.dotsPosition || 'center',
                autoplay:      autoplayOpts,
            });

            const api = this.emblaCore.init();

            // Reveal wrapper once Embla has committed its layout — two rAFs ensure
            // the initial flex positions are painted before we show the slider.
            // Individual images fade in progressively via CSS (.bw-ps-embla-viewport img).
            requestAnimationFrame(() => {
                requestAnimationFrame(() => this.$wrapper.removeClass('loading'));
            });

            if (!api) return;

            this._attachWheelHandler();
            this._updateEmblaBreakpointOptions();

            $(window).on(`resize.bwpsl-${this.widgetId}`, debounce(() => {
                this._updateEmblaBreakpointOptions();
            }, 150));
        }

        /* ────────────────────────────────────────────
           TRACKPAD WHEEL — horizontal swipe
        ──────────────────────────────────────────── */

        _attachWheelHandler() {
            const wrapper = this.$wrapper[0];
            let _wheelEndTimer = null;

            this._wheelHandler = (evt) => {
                // Only intercept gestures that originate inside this carousel
                if (!wrapper.contains(evt.target)) return;
                // Ignore vertical-dominant gestures (page scroll)
                if (Math.abs(evt.deltaX) <= Math.abs(evt.deltaY)) return;

                const emblaApi = this.emblaCore?.api();
                if (!emblaApi) return;

                // If Embla is already handling a pointer drag (touch/mouse),
                // skip the wheel handler to avoid double-movement on devices
                // that fire both pointer and wheel events for the same gesture.
                if (emblaApi.isDragging?.()) return;

                // Block browser back/forward navigation
                evt.preventDefault();

                // Normalize delta: trackpads = px (deltaMode 0), wheels = lines/pages
                let dx = evt.deltaX;
                if (evt.deltaMode === 1) dx *= 20;
                if (evt.deltaMode === 2) dx *= 200;
                dx *= 3; // amplify for responsive feel

                // Drive Embla's internal scroll target pixel-by-pixel — fluid, drag-free feel
                const engine    = emblaApi.internalEngine();
                const snaps     = engine.scrollSnaps;
                const newTarget = engine.target.get() - dx;

                engine.target.set(
                    engine.options.loop
                        ? newTarget
                        : Math.max(
                            Math.min.apply(null, snaps),
                            Math.min(Math.max.apply(null, snaps), newTarget)
                        )
                );
                engine.animation.start();

                // After gesture ends, snap to nearest slide.
                // 300ms (not 150ms): gives Embla's deceleration physics time
                // to settle before snapping, preventing a jarring double-jump.
                clearTimeout(_wheelEndTimer);
                _wheelEndTimer = setTimeout(() => {
                    const api2 = this.emblaCore?.api();
                    if (!api2) return;
                    const eng2  = api2.internalEngine();
                    const t     = eng2.target.get();
                    const snps  = eng2.scrollSnaps;
                    let bestIdx = 0, bestDist = Infinity;
                    for (let i = 0; i < snps.length; i++) {
                        const d = Math.abs(snps[i] - t);
                        if (d < bestDist) { bestDist = d; bestIdx = i; }
                    }
                    api2.scrollTo(bestIdx);
                }, 300);
            };

            // Must be on window: Chrome captures horizontal swipe before element listeners fire
            window.addEventListener('wheel', this._wheelHandler, { passive: false });
        }

        /* ────────────────────────────────────────────
           BREAKPOINT MANAGEMENT
        ──────────────────────────────────────────── */

        _getActiveBreakpointIndex() {
            const width = window.innerWidth;
            for (let i = 0; i < this._sortedBreakpoints.length; i++) {
                if (width <= this._sortedBreakpoints[i].breakpoint) return i;
            }
            return -1; // wider than all breakpoints → desktop
        }

        _updateEmblaBreakpointOptions() {
            if (!this.emblaCore) return;
            const api = this.emblaCore.api();
            if (!api) return;

            const idx = this._getActiveBreakpointIndex();
            if (idx === this._lastBreakpointIndex) return; // no change
            this._lastBreakpointIndex = idx;

            const bp        = idx >= 0 ? this._sortedBreakpoints[idx] : null;
            const baseAlign = this.config.horizontal?.align || 'start';
            const align     = bp?.centerMode ? 'center' : baseAlign;

            api.reInit({
                slidesToScroll: bp?.slidesToScroll ?? 1,
                align,
                containScroll:  (bp?.centerMode || bp?.variableWidth || align !== 'start') ? false : 'trimSnaps',
            });
        }

        /* ────────────────────────────────────────────
           DESTROY
        ──────────────────────────────────────────── */

        destroy() {
            if (this._wheelHandler) {
                window.removeEventListener('wheel', this._wheelHandler);
                this._wheelHandler = null;
            }
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
        document.querySelectorAll('.bw-product-slider-wrapper').forEach(el => {
            const $el = $(el);
            if ($el.data('bw-product-slider-instance')) return;
            $el.data('bw-product-slider-instance', new BWProductSlider(el));
        });
    }

    function isEditMode() {
        return (
            typeof elementorFrontend !== 'undefined' &&
            typeof elementorFrontend.isEditMode === 'function' &&
            elementorFrontend.isEditMode()
        );
    }

    $(document).ready(initWidgets);

    let _hooksRegistered = false;

    function registerElementorHooks() {
        if (_hooksRegistered) return;
        if (typeof elementorFrontend?.hooks?.addAction !== 'function') return;
        _hooksRegistered = true;

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-product-slider.default',
            ($scope) => {
                const $wrapper = $scope.find('.bw-product-slider-wrapper');
                if (!$wrapper.length) return;

                const existing = $wrapper.data('bw-product-slider-instance');
                if (existing) {
                    if (!isEditMode()) return;
                    existing.destroy();
                    $wrapper.removeData('bw-product-slider-instance');
                }

                $wrapper.data('bw-product-slider-instance', new BWProductSlider($wrapper[0]));
            }
        );
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

    window.BWProductSlider = BWProductSlider;

})(jQuery);
