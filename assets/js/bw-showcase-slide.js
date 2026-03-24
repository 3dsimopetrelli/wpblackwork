/**
 * BW Showcase Slide Widget — JavaScript
 * Embla-based curated showcase slider powered by product showcase metabox data.
 * No popup support. Optional custom cursor, responsive image-height modes.
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

    class BWShowcaseSlide {
        constructor(element) {
            this.$wrapper = $(element);
            this.widgetId = this.$wrapper.data('widget-id');
            this.config = this.$wrapper.data('config') || {};
            this.emblaCore = null;
            this._wheelHandler = null;
            this._sortedBreakpoints = [];
            this._lastBreakpointIndex = undefined;
            this._cursor = null;
            this._cursorRafId = null;
            this._$horizontal = null;
            this._$images = null;
            this._pressState = null;
            this.initialized = false;

            this.init();
        }

        init() {
            if (this.initialized) {
                return;
            }

            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Showcase Slide: BWEmblaCore non disponibile.');
                return;
            }

            const responsive = this.config.horizontal?.responsive || [];
            this._sortedBreakpoints = [...responsive].sort((a, b) => a.breakpoint - b.breakpoint);

            this.initHorizontalLayout();

            if (this.config.enableCustomCursor && !this.isTouchDevice()) {
                this.initCustomCursor();
            }

            this.initialized = true;
        }

        initHorizontalLayout() {
            const viewport = this.$wrapper.find('.bw-ss-embla-viewport')[0];

            if (!viewport) {
                return;
            }

            this.$wrapper.addClass('loading');

            const hCfg = this.config.horizontal || {};
            const autoplayOpts = hCfg.autoplay ? {
                delay: hCfg.autoplaySpeed || 3000,
                playOnInit: true,
                stopOnInteraction: true,
                stopOnMouseEnter: hCfg.pauseOnHover !== false,
                stopOnFocusIn: true,
                jump: false,
            } : false;

            const enableTouchDrag = hCfg.enableTouchDrag !== false;
            const emblaOptions = {
                loop: hCfg.infinite === true,
                align: hCfg.align || 'start',
                containScroll: (hCfg.align || 'start') === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree: hCfg.dragFree === true,
                watchResize: true,
                watchDrag: enableTouchDrag ? true : (_emblaApi, evt) => evt.pointerType === 'mouse',
            };

            this._$horizontal = this.$wrapper.find('.bw-showcase-slide-horizontal');
            this._$images = this.$wrapper.find('.bw-showcase-slide-image-el');

            this.emblaCore = new BWEmblaCore(viewport, emblaOptions, {
                prevBtn: this.$wrapper.find('.bw-ss-arrow-prev')[0],
                nextBtn: this.$wrapper.find('.bw-ss-arrow-next')[0],
                dotsContainer: this.$wrapper.find('.bw-ss-dots-container')[0],
                dotsPosition: this.config.dotsPosition || 'center',
                autoplay: autoplayOpts,
                onSelect: () => this._updateImageHeightControls(),
            });

            const api = this.emblaCore.init();

            this._revealAfterFirstImage();

            if (!api) {
                return;
            }

            this._attachWheelHandler();
            this._attachCardNavigationHandler(viewport, api);
            this._updateImageHeightControls();
            this._updateEmblaBreakpointOptions();

            $(window).on(`resize.bwss-${this.widgetId}`, debounce(() => {
                this._updateImageHeightControls();
                this._updateEmblaBreakpointOptions();
            }, 150));
        }

        _revealAfterFirstImage() {
            const firstImg = this.$wrapper.find('.bw-showcase-slide-image-el').get(0);
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

        _attachWheelHandler() {
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

        _attachCardNavigationHandler(viewport, api) {
            $(viewport).on(`pointerdown.bwss-nav-${this.widgetId}`, '.bw-showcase-slide-card', (e) => {
                if ($(e.target).closest('a, button').length) {
                    this._pressState = null;
                    return;
                }

                this._pressState = {
                    x: e.clientX,
                    y: e.clientY,
                    pointerId: e.pointerId,
                    target: e.currentTarget,
                };
            });

            $(viewport).on(`pointercancel.bwss-nav-${this.widgetId} pointerleave.bwss-nav-${this.widgetId}`, '.bw-showcase-slide-card', () => {
                this._pressState = null;
            });

            $(viewport).on(`pointerup.bwss-nav-${this.widgetId}`, '.bw-showcase-slide-card', (e) => {
                if ($(e.target).closest('a, button').length) {
                    this._pressState = null;
                    return;
                }

                if (
                    !this._pressState ||
                    this._pressState.pointerId !== e.pointerId ||
                    this._pressState.target !== e.currentTarget
                ) {
                    this._pressState = null;
                    return;
                }

                if (Math.abs(e.clientX - this._pressState.x) > 6 || Math.abs(e.clientY - this._pressState.y) > 6) {
                    this._pressState = null;
                    return;
                }

                this._pressState = null;

                const $slide = $(e.currentTarget).closest('.bw-ss-slide');
                const slideIndex = parseInt($slide.data('bw-index'), 10);
                const activeIndex = this._getCenteredSlideIndex(viewport, api);

                if (Number.isNaN(slideIndex) || slideIndex === activeIndex) {
                    return;
                }

                api.scrollTo(slideIndex);
            });
        }

        _getActiveBreakpointIndex() {
            const width = $(window).width();

            for (let i = 0; i < this._sortedBreakpoints.length; i++) {
                if (width <= this._sortedBreakpoints[i].breakpoint) {
                    return i;
                }
            }

            return -1;
        }

        _updateEmblaBreakpointOptions() {
            if (!this.emblaCore) {
                return;
            }

            const api = this.emblaCore.api();
            if (!api) {
                return;
            }

            const idx = this._getActiveBreakpointIndex();
            if (idx === this._lastBreakpointIndex) {
                return;
            }

            this._lastBreakpointIndex = idx;

            const bp = idx >= 0 ? this._sortedBreakpoints[idx] : null;
            const baseAlign = this.config.horizontal?.align || 'start';
            const align = bp?.centerMode ? 'center' : baseAlign;

            api.reInit({
                slidesToScroll: bp?.slidesToScroll || 1,
                align,
                containScroll: (bp?.centerMode || bp?.variableWidth || align !== 'start') ? false : 'trimSnaps',
            });
        }

        _updateImageHeightControls() {
            const width = $(window).width();
            const $horizontal = this._$horizontal;
            const $images = this._$images;
            const $slides = this.$wrapper.find('.bw-ss-slide');

            if (!$horizontal || !$horizontal.length || !$images || !$images.length || !$slides.length) {
                return;
            }

            let heightMode = 'auto';
            let imageHeight = null;
            let imageWidth = null;
            let variableWidth = false;

            for (const bp of this._sortedBreakpoints) {
                if (width <= bp.breakpoint) {
                    if (bp.imageHeightMode) {
                        heightMode = bp.imageHeightMode;
                    }
                    if (bp.imageHeight) {
                        imageHeight = bp.imageHeight;
                    }
                    if (bp.imageWidth) {
                        imageWidth = bp.imageWidth;
                    }
                    variableWidth = bp.variableWidth === true;
                    break;
                }
            }

            const heightClasses = [
                'bw-ps-height-auto',
                'bw-ps-height-fixed',
                'bw-ps-height-contain',
                'bw-ps-height-cover',
            ];

            $horizontal.removeClass(heightClasses.join(' ')).addClass(`bw-ps-height-${heightMode}`);

            if (heightMode !== 'auto' && imageHeight?.size != null && imageHeight?.unit) {
                $images.css('height', `${imageHeight.size}${imageHeight.unit}`);
            } else {
                $images.css('height', '');
            }

            if (['contain', 'cover'].includes(heightMode) && imageWidth?.size != null && imageWidth?.unit) {
                if (variableWidth && imageWidth.unit === '%') {
                    $slides.css({
                        flex: `0 0 ${imageWidth.size}${imageWidth.unit}`,
                        maxWidth: `${imageWidth.size}${imageWidth.unit}`,
                    });
                    $images.css('width', '100%');
                } else {
                    $slides.css({
                        flex: '',
                        maxWidth: '',
                    });
                    $images.css('width', `${imageWidth.size}${imageWidth.unit}`);
                }
            } else {
                $slides.css({
                    flex: '',
                    maxWidth: '',
                });
                $images.css('width', '');
            }
        }

        _getCenteredSlideIndex(viewport, api) {
            const slides = Array.from(viewport.querySelectorAll('.bw-ss-slide'));
            if (!slides.length) {
                return api ? api.selectedScrollSnap() : -1;
            }

            const viewportRect = viewport.getBoundingClientRect();
            const viewportCenter = viewportRect.left + (viewportRect.width / 2);
            let bestIndex = -1;
            let bestDistance = Infinity;

            slides.forEach((slideEl) => {
                const rect = slideEl.getBoundingClientRect();
                const slideCenter = rect.left + (rect.width / 2);
                const distance = Math.abs(slideCenter - viewportCenter);

                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestIndex = parseInt(slideEl.getAttribute('data-bw-index'), 10);
                }
            });

            return Number.isNaN(bestIndex) ? (api ? api.selectedScrollSnap() : -1) : bestIndex;
        }

        initCustomCursor() {
            const cursorId = `bw-ss-cursor-${this.widgetId}`;
            this._cursor = $(`<div class="bw-ss-custom-cursor" data-cursor-id="${cursorId}"></div>`).appendTo('body');

            const $wrapper = this.$wrapper;
            const $cursor = this._cursor;
            const state = {
                targX: 0,
                targY: 0,
                curX: 0,
                curY: 0,
                running: false,
            };

            $wrapper.addClass('bw-ss-hide-cursor');

            const animateCursor = () => {
                const ease = 0.18;
                state.curX += (state.targX - state.curX) * ease;
                state.curY += (state.targY - state.curY) * ease;
                $cursor.css({ left: `${state.curX}px`, top: `${state.curY}px` });

                if (state.running) {
                    this._cursorRafId = requestAnimationFrame(animateCursor);
                }
            };

            $wrapper.on(`mousemove.bwss-cursor-${this.widgetId}`, (e) => {
                state.targX = e.clientX;
                state.targY = e.clientY;

                if (!state.running) {
                    state.curX = state.targX;
                    state.curY = state.targY;
                    state.running = true;
                    this._cursorRafId = requestAnimationFrame(animateCursor);
                }
            });

            $wrapper.on(`mouseleave.bwss-cursor-${this.widgetId}`, () => {
                state.running = false;
                if (this._cursorRafId) {
                    cancelAnimationFrame(this._cursorRafId);
                    this._cursorRafId = null;
                }
                $cursor.removeClass('active view prev next');
            });

            const $viewport = $wrapper.find('.bw-ss-embla-viewport');
            const updateCursorForCard = (cardEl) => {
                const emblaApi = this.emblaCore ? this.emblaCore.api() : null;
                const viewport = $viewport.get(0);
                const selected = emblaApi && viewport
                    ? this._getCenteredSlideIndex(viewport, emblaApi)
                    : 0;
                const slideIndex = parseInt($(cardEl).closest('.bw-ss-slide').data('bw-index'), 10);

                $cursor.removeClass('view prev next active');

                if (Number.isNaN(slideIndex)) {
                    return;
                }

                if (slideIndex === selected) {
                    $cursor.addClass('view active');
                } else {
                    $cursor.addClass(slideIndex < selected ? 'prev' : 'next').addClass('active');
                }
            };

            $viewport.on(`mouseenter.bwss-cursor-${this.widgetId}`, '.bw-showcase-slide-card', (e) => {
                updateCursorForCard(e.currentTarget);
            });

            $viewport.on(`mouseleave.bwss-cursor-${this.widgetId}`, '.bw-showcase-slide-card', () => {
                $cursor.removeClass('active view prev next');
            });

            $viewport.on(`mouseenter.bwss-cursor-${this.widgetId}`, 'a', () => {
                $cursor.removeClass('active view prev next');
            });

            $viewport.on(`mouseleave.bwss-cursor-${this.widgetId}`, 'a', (e) => {
                const $card = $(e.currentTarget).closest('.bw-showcase-slide-card');
                if ($card.length) {
                    updateCursorForCard($card[0]);
                }
            });
        }

        isTouchDevice() {
            return window.matchMedia('(pointer: coarse)').matches;
        }

        destroy() {
            if (this._wheelHandler) {
                window.removeEventListener('wheel', this._wheelHandler);
                this._wheelHandler = null;
            }

            if (this.emblaCore) {
                this.emblaCore.destroy();
                this.emblaCore = null;
            }

            if (this._cursorRafId) {
                cancelAnimationFrame(this._cursorRafId);
                this._cursorRafId = null;
            }

            if (this._cursor) {
                this._cursor.remove();
                this._cursor = null;
            }

            $(window).off(`.bwss-${this.widgetId}`);
            this.$wrapper.off(`.bwss-cursor-${this.widgetId}`);
            this.$wrapper.find('.bw-ss-embla-viewport').off(`.bwss-cursor-${this.widgetId}`).off(`.bwss-nav-${this.widgetId}`);
            this._pressState = null;
            this.initialized = false;
        }
    }

    function initWidgets() {
        document.querySelectorAll('.bw-showcase-slide-wrapper').forEach((el) => {
            const $el = $(el);

            if ($el.data('bw-showcase-slide-instance')) {
                return;
            }

            $el.data('bw-showcase-slide-instance', new BWShowcaseSlide(el));
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

    let hooksRegistered = false;

    function registerElementorHooks() {
        if (hooksRegistered) {
            return;
        }

        if (typeof elementorFrontend?.hooks?.addAction !== 'function') {
            return;
        }

        hooksRegistered = true;

        elementorFrontend.hooks.addAction('frontend/element_ready/bw-showcase-slide.default', ($scope) => {
            const $wrapper = $scope.find('.bw-showcase-slide-wrapper');

            if (!$wrapper.length) {
                return;
            }

            const existing = $wrapper.data('bw-showcase-slide-instance');
            if (existing) {
                if (!isEditMode()) {
                    return;
                }

                existing.destroy();
                $wrapper.removeData('bw-showcase-slide-instance');
            }

            $wrapper.data('bw-showcase-slide-instance', new BWShowcaseSlide($wrapper[0]));
        });
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

    window.BWShowcaseSlide = BWShowcaseSlide;
})(jQuery);
