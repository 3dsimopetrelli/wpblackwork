(function ($) {
    'use strict';

    const debounce = (fn, ms) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), ms);
        };
    };

    class BWBasicSlide {
        constructor(element) {
            this.$wrapper = $(element);
            this.widgetId = this.$wrapper.data('widget-id');
            this.mode = this.$wrapper.data('mode') || 'slide';
            this.config = this.$wrapper.data('config') || {};
            this.emblaCore = null;
            this._wheelHandler = null;
            this._sortedBreakpoints = [];
            this._lastBreakpointIndex = undefined;
            this.initialized = false;

            this.init();
        }

        init() {
            if (this.initialized) {
                return;
            }

            if (typeof BWEmblaCore !== 'undefined') {
                BWEmblaCore.initImageLoading(this.$wrapper[0]);
            }

            if (this.mode !== 'slide') {
                this.initialized = true;
                return;
            }

            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Basic Slide: BWEmblaCore non disponibile.');
                return;
            }

            const responsive = this.config.horizontal?.responsive || [];
            this._sortedBreakpoints = [...responsive].sort((a, b) => a.breakpoint - b.breakpoint);

            this.initCarousel();
            this.initialized = true;
        }

        initCarousel() {
            const viewport = this.$wrapper.find('.bw-bs-embla-viewport')[0];
            if (!viewport) {
                return;
            }

            this.$wrapper.addClass('loading');

            const hCfg = this.config.horizontal || {};
            const enableTouch = hCfg.enableTouchDrag !== false;
            const enableMouse = hCfg.enableMouseDrag !== false;

            let watchDrag;
            if (enableTouch && enableMouse) {
                watchDrag = true;
            } else if (!enableTouch && !enableMouse) {
                watchDrag = false;
            } else if (enableMouse) {
                watchDrag = (_api, evt) => evt.pointerType === 'mouse';
            } else {
                watchDrag = (_api, evt) => evt.pointerType !== 'mouse';
            }

            const autoplayOpts = hCfg.autoplay ? {
                delay: hCfg.autoplaySpeed || 3000,
                playOnInit: true,
                stopOnInteraction: true,
                stopOnMouseEnter: true,
                stopOnFocusIn: true,
                jump: false,
            } : false;

            this.emblaCore = new BWEmblaCore(viewport, {
                loop: hCfg.infinite === true,
                align: hCfg.align || 'start',
                containScroll: (hCfg.align || 'start') === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree: false,
                watchResize: true,
                watchDrag,
            }, {
                prevBtn: this.$wrapper.find('.bw-bs-arrow-prev')[0],
                nextBtn: this.$wrapper.find('.bw-bs-arrow-next')[0],
                dotsContainer: this.$wrapper.find('.bw-bs-dots-container')[0],
                dotsPosition: 'center',
                autoplay: autoplayOpts,
            });

            const api = this.emblaCore.init();
            this.revealAfterFirstImage();

            if (!api) {
                return;
            }

            if (enableMouse) {
                this.attachWheelHandler();
            }
            this.updateEmblaBreakpointOptions();
            $(window).on(`resize.bwbs-${this.widgetId}`, debounce(() => {
                this.updateEmblaBreakpointOptions();
            }, 150));
        }

        revealAfterFirstImage() {
            const firstImg = this.$wrapper.find('.bw-bs-image').get(0);
            const reveal = () => this.$wrapper.removeClass('loading');

            if (!firstImg) {
                reveal();
                return;
            }

            if (firstImg.complete && firstImg.naturalWidth > 0) {
                requestAnimationFrame(() => requestAnimationFrame(reveal));
                return;
            }

            let revealed = false;
            const done = () => {
                if (revealed) {
                    return;
                }
                revealed = true;
                firstImg.removeEventListener('load', done);
                firstImg.removeEventListener('error', done);
                reveal();
            };

            firstImg.addEventListener('load', done);
            firstImg.addEventListener('error', done);
            setTimeout(done, 2000);
        }

        getActiveBreakpointIndex() {
            const width = window.innerWidth;
            for (let i = 0; i < this._sortedBreakpoints.length; i++) {
                if (width <= this._sortedBreakpoints[i].breakpoint) {
                    return i;
                }
            }
            return -1;
        }

        attachWheelHandler() {
            if (this._wheelHandler) {
                return;
            }

            const wrapper = this.$wrapper[0];
            let wheelEndTimer = null;

            this._wheelHandler = (evt) => {
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

        updateEmblaBreakpointOptions() {
            if (!this.emblaCore) {
                return;
            }

            const api = this.emblaCore.api();
            if (!api) {
                return;
            }

            const idx = this.getActiveBreakpointIndex();
            if (idx === this._lastBreakpointIndex) {
                return;
            }
            this._lastBreakpointIndex = idx;

            const bp = idx >= 0 ? this._sortedBreakpoints[idx] : null;
            const baseAlign = this.config.horizontal?.align || 'start';
            const align = bp?.centerMode ? 'center' : baseAlign;

            api.reInit({
                slidesToScroll: bp?.slidesToScroll ?? 1,
                align,
                containScroll: (bp?.centerMode || bp?.variableWidth || align !== 'start') ? false : 'trimSnaps',
            });
        }

        destroy() {
            if (this._wheelHandler) {
                window.removeEventListener('wheel', this._wheelHandler);
                this._wheelHandler = null;
            }
        }
    }

    const init = ($scope) => {
        $scope.find('.bw-basic-slide-wrapper').each(function () {
            if (!this.__bwBasicSlide) {
                this.__bwBasicSlide = new BWBasicSlide(this);
            }
        });
    };

    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-basic-slide.default', init);
        } else {
            init($(document));
        }
    });

    $(function () {
        if (!(window.elementorFrontend && window.elementorFrontend.hooks)) {
            init($(document));
        }
    });
})(jQuery);
