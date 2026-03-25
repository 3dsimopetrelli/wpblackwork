/**
 * BW Mosaic Slider Widget — JavaScript
 * Desktop: Embla pages made of 5-item mosaic layouts.
 * Mobile (<1000px): linear 1-card Embla slider fallback.
 */
(function ($) {
    'use strict';

    const debounce = (fn, ms) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), ms);
        };
    };

    class BWMosaicSlider {
        constructor(element) {
            this.$wrapper = $(element);
            this.widgetId = this.$wrapper.data('widget-id');
            this.config = this.$wrapper.data('config') || {};
            this.emblaCore = null;
            this._wheelHandler = null;
            this.activeMode = null;
            this.initialized = false;
            // Guards against pending debounce callbacks firing after destroy().
            this.destroyed = false;

            this.init();
        }

        init() {
            if (this.initialized) {
                return;
            }

            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Mosaic Slider: BWEmblaCore non disponibile.');
                return;
            }

            this._syncMode();

            $(window).on(`resize.bwms-${this.widgetId}`, debounce(() => {
                this._syncMode();
            }, 150));

            this.initialized = true;
        }

        _getMode() {
            // mobileBreakpoint comes from PHP (MOBILE_BREAKPOINT constant) via data-config,
            // making PHP the single source of truth instead of a separate JS constant.
            const bp = this.config.mobileBreakpoint || 1000;
            return window.innerWidth < bp ? 'mobile' : 'desktop';
        }

        _syncMode() {
            if (this.destroyed) {
                return;
            }

            const nextMode = this._getMode();

            if (nextMode === this.activeMode && this.emblaCore) {
                return;
            }

            this._destroyEmbla();
            this.activeMode = nextMode;

            const modeConfig = this.config[nextMode] || {};
            const viewportSelector = nextMode === 'mobile'
                ? '.bw-ms-mobile-viewport'
                : '.bw-ms-desktop-viewport';

            const viewport = this.$wrapper.find(viewportSelector)[0];
            if (!viewport) {
                this.$wrapper.removeClass('loading');
                return;
            }

            this.$wrapper.addClass('loading');

            const autoplayOpts = modeConfig.autoplay ? {
                delay: modeConfig.autoplaySpeed || 3500,
                playOnInit: true,
                stopOnInteraction: true,
                stopOnMouseEnter: modeConfig.pauseOnHover !== false,
                stopOnFocusIn: true,
                jump: false,
            } : false;

            const watchDrag = this._buildWatchDrag(modeConfig);
            const prevBtn = this.$wrapper.find(
                nextMode === 'mobile' ? '.bw-ms-arrow-prev-mobile' : '.bw-ms-arrow-prev-desktop'
            )[0];
            const nextBtn = this.$wrapper.find(
                nextMode === 'mobile' ? '.bw-ms-arrow-next-mobile' : '.bw-ms-arrow-next-desktop'
            )[0];
            const dotsContainer = this.$wrapper.find(
                nextMode === 'mobile' ? '.bw-ms-dots-container--mobile' : '.bw-ms-dots-container--desktop'
            )[0];

            this.emblaCore = new BWEmblaCore(viewport, {
                loop: modeConfig.infinite === true,
                align: modeConfig.align || 'start',
                containScroll: (modeConfig.align || 'start') === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree: modeConfig.dragFree === true,
                watchResize: true,
                watchDrag,
            }, {
                prevBtn,
                nextBtn,
                dotsContainer,
                dotsPosition: this.config.dotsPosition || 'center',
                autoplay: autoplayOpts,
            });

            const api = this.emblaCore.init();

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.$wrapper.removeClass('loading');
                });
            });

            if (!api) {
                return;
            }

            if (nextMode === 'mobile') {
                this._attachWheelHandler();
            }
        }

        _buildWatchDrag(modeConfig) {
            const enableTouch = modeConfig.enableTouchDrag !== false;
            const enableMouse = modeConfig.enableMouseDrag !== false;

            if (enableTouch && enableMouse) {
                return true;
            }

            if (!enableTouch && !enableMouse) {
                return false;
            }

            if (enableMouse) {
                return (_emblaApi, event) => event.pointerType === 'mouse';
            }

            return (_emblaApi, event) => event.pointerType !== 'mouse';
        }

        _attachWheelHandler() {
            const wrapper = this.$wrapper[0];
            let wheelEndTimer = null;

            this._wheelHandler = (evt) => {
                if (this.activeMode !== 'mobile') {
                    return;
                }

                if (!wrapper.contains(evt.target)) {
                    return;
                }

                if (Math.abs(evt.deltaX) <= Math.abs(evt.deltaY)) {
                    return;
                }

                const emblaApi = this.emblaCore?.api();
                if (!emblaApi || emblaApi.isDragging?.()) {
                    return;
                }

                evt.preventDefault();

                let dx = evt.deltaX;
                if (evt.deltaMode === 1) {
                    dx *= 20;
                }
                if (evt.deltaMode === 2) {
                    dx *= 200;
                }
                dx *= 3;

                const engine = emblaApi.internalEngine();
                const snaps = engine.scrollSnaps;
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

                clearTimeout(wheelEndTimer);
                wheelEndTimer = setTimeout(() => {
                    const api = this.emblaCore?.api();
                    if (!api) {
                        return;
                    }

                    const eng = api.internalEngine();
                    const target = eng.target.get();
                    const snapList = eng.scrollSnaps;
                    let bestIndex = 0;
                    let bestDistance = Infinity;

                    for (let i = 0; i < snapList.length; i++) {
                        const distance = Math.abs(snapList[i] - target);
                        if (distance < bestDistance) {
                            bestDistance = distance;
                            bestIndex = i;
                        }
                    }

                    api.scrollTo(bestIndex);
                }, 300);
            };

            window.addEventListener('wheel', this._wheelHandler, { passive: false });
        }

        _destroyEmbla() {
            if (this._wheelHandler) {
                window.removeEventListener('wheel', this._wheelHandler, { passive: false });
                this._wheelHandler = null;
            }

            if (!this.emblaCore) {
                return;
            }

            this.emblaCore.destroy();
            this.emblaCore = null;
        }

        destroy() {
            // Set destroyed first so any pending debounce callbacks bail immediately.
            this.destroyed = true;
            this._destroyEmbla();
            $(window).off(`.bwms-${this.widgetId}`);
            this.activeMode = null;
            this.config = null;
            this.initialized = false;
        }
    }

    function initWidgets() {
        document.querySelectorAll('.bw-mosaic-slider-wrapper').forEach((element) => {
            const $element = $(element);
            if ($element.data('bw-mosaic-slider-instance')) {
                return;
            }

            $element.data('bw-mosaic-slider-instance', new BWMosaicSlider(element));
        });
    }

    $(document).ready(initWidgets);

    let hooksRegistered = false;

    function registerElementorHooks() {
        if (hooksRegistered) {
            return;
        }

        if (typeof elementorFrontend?.hooks?.addAction !== 'function') {
            return;
        }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-mosaic-slider.default',
            function ($scope) {
                const $wrapper = $scope.find('.bw-mosaic-slider-wrapper');
                if (!$wrapper.length) {
                    return;
                }

                const existing = $wrapper.data('bw-mosaic-slider-instance');
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                    $wrapper.removeData('bw-mosaic-slider-instance');
                }

                $wrapper.data('bw-mosaic-slider-instance', new BWMosaicSlider($wrapper[0]));
            }
        );

        hooksRegistered = true;
    }

    function onElementorReady() {
        initWidgets();
        registerElementorHooks();
    }

    if (typeof elementorFrontend !== 'undefined' && elementorFrontend?.hooks) {
        onElementorReady();
    } else {
        $(window).on('elementor/frontend/init', onElementorReady);
    }
})(jQuery);
