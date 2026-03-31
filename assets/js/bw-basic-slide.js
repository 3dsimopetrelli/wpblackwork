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
            });

            const api = this.emblaCore.init();
            this.revealAfterFirstImage();

            if (!api) {
                return;
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
