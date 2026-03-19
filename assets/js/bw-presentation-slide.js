/**
 * BW Presentation Slide Widget — JavaScript
 * Usa BWEmblaCore (bw-embla-core.js) invece di Slick Carousel.
 *
 * Fix inclusi:
 * - Popup CSS: copia la classe Elementor sul popup prima di appendToBody
 * - Cursore per-istanza: ogni widget ha il suo elemento cursore
 * - RAF on-demand: animazione cursore solo durante mousemove, stop su mouseleave
 * - Breakpoint responsive: gestito da CSS inline (PHP), non da JS
 * - Prima immagine eager: gestito in PHP, BWEmblaCore.initImageLoading gestisce fade-in
 * - Destroy completo: eventi namespaced, cursore rimosso dal DOM
 */
(function ($) {
    'use strict';

    class BWPresentationSlide {
        constructor(element) {
            this.$wrapper  = $(element);
            this.widgetId  = this.$wrapper.data('widget-id');
            this.config    = this.$wrapper.data('config') || {};
            this.layoutMode = this.config.layoutMode || 'horizontal';
            this.initialized = false;

            // Istanze Embla
            this.emblaCore   = null; // layout horizontal
            this.emblaMain   = null; // vertical responsive main
            this.emblaThumbs = null; // vertical responsive thumbs

            // Cursore custom (per-istanza, non condiviso)
            this._cursor       = null;
            this._cursorRafId  = null;
            this._cursorActive = false;

            // Sorted breakpoints per image-height mode
            this._sortedBreakpoints    = [];
            this._lastBreakpointIndex  = undefined; // track active bp for Embla reInit

            this.init();
        }

        /* ────────────────────────────────────────────
           INIT
        ──────────────────────────────────────────── */

        init() {
            if (this.initialized) return;
            if (typeof BWEmblaCore === 'undefined') {
                console.warn('BW Presentation Slide: BWEmblaCore non disponibile.');
                return;
            }

            const responsive = this.config.horizontal?.responsive || [];
            this._sortedBreakpoints = [...responsive].sort((a, b) => b.breakpoint - a.breakpoint);

            if (this.layoutMode === 'horizontal') {
                this.initHorizontalLayout();
            } else if (this.layoutMode === 'vertical') {
                this.initVerticalLayout();
            }

            if (this.config.enablePopup) {
                this.initPopup();
            }

            if (this.config.enableCustomCursor && !this.isTouchDevice()) {
                this.initCustomCursor();
            }

            this.initialized = true;
        }

        /* ────────────────────────────────────────────
           LAYOUT HORIZONTAL
        ──────────────────────────────────────────── */

        initHorizontalLayout() {
            const viewport = this.$wrapper.find('.bw-ps-embla-viewport')[0];
            if (!viewport) return;

            this.$wrapper.addClass('loading');

            const hCfg       = this.config.horizontal || {};
            const dotsPos    = this.config.dotsPosition || 'center';
            const prevBtn    = this.$wrapper.find('.bw-ps-arrow-prev')[0];
            const nextBtn    = this.$wrapper.find('.bw-ps-arrow-next')[0];
            const dotsCont   = this.$wrapper.find('.bw-ps-dots-container')[0];

            // Loop + center: the last slide is rendered to the LEFT of the first
            // slide and is immediately visible. Preload it via <link rel="preload">
            // so it downloads with high priority concurrently with slide 0/1,
            // regardless of whether the HTML has loading="eager" or "lazy"
            // (page-cache may serve stale HTML with the old lazy attribute).
            if (hCfg.infinite && (hCfg.align || 'start') === 'center') {
                const $lastImg = this.$wrapper.find('.bw-ps-image img').last();
                if ($lastImg.length && !$lastImg[0].complete) {
                    const src    = $lastImg.attr('src');
                    const srcset = $lastImg.attr('srcset');
                    const sizes  = $lastImg.attr('sizes');
                    if (src) {
                        const link        = document.createElement('link');
                        link.rel          = 'preload';
                        link.as           = 'image';
                        link.href         = src;
                        link.fetchPriority = 'high';
                        if (srcset) link.setAttribute('imagesrcset', srcset);
                        if (sizes)  link.setAttribute('imagesizes',  sizes);
                        document.head.appendChild(link);
                    }
                }
            }

            // Autoplay options (false se disabilitato)
            const autoplayOpts = hCfg.autoplay ? {
                delay:             hCfg.autoplaySpeed || 3000,
                playOnInit:        true,
                stopOnInteraction: true,
                stopOnMouseEnter:  hCfg.pauseOnHover !== false,
                stopOnFocusIn:     true,
                jump:              false,
            } : false;

            const globalAlign   = hCfg.align || 'start';
            const emblaOptions = {
                loop:           hCfg.infinite === true,
                align:          globalAlign,
                containScroll:  globalAlign === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree:       hCfg.dragFree === true,
                watchResize:    true,
                watchDrag:      true,
            };

            this.emblaCore = new BWEmblaCore(viewport, emblaOptions, {
                prevBtn,
                nextBtn,
                dotsContainer:  dotsCont,
                dotsPosition:   dotsPos,
                autoplay:       autoplayOpts,
                onSelect:       () => this._updateImageHeightControls(),
            });

            const api = this.emblaCore.init();

            // Reveal wrapper after the first slide image is ready so we get a clean
            // coordinated fade-in instead of a jarring opacity jump.
            // A 2s timeout acts as a safety net in case the image never fires load/error.
            const firstImg = this.$wrapper.find('.bw-ps-image img')[0];
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

            // Visibilità frecce + dots + image-height mode + opzioni Embla iniziali
            this._updateArrowsVisibility();
            this._updateDotsVisibility();
            this._updateImageHeightControls();
            this._updateEmblaBreakpointOptions();

            // Aggiorna on resize via window
            $(window).on(`resize.bwps-${this.widgetId}`, () => {
                this._updateArrowsVisibility();
                this._updateDotsVisibility();
                this._updateImageHeightControls();
                this._updateEmblaBreakpointOptions();
            });

            // Click sulle slide: zoom (popup) o navigazione
            $(viewport).on(`click.bwps-${this.widgetId}`, '.bw-ps-image-clickable', (e) => {
                const $slide      = $(e.currentTarget).closest('.bw-ps-slide');
                const slideIndex  = parseInt($slide.data('bw-index'), 10);
                const selected    = api.selectedScrollSnap();

                if (isNaN(slideIndex)) return;

                if (slideIndex === selected) {
                    if (this.config.enablePopup) {
                        this.openModal(slideIndex);
                    }
                } else {
                    api.scrollTo(slideIndex);
                }
            });
        }

        /* ────────────────────────────────────────────
           LAYOUT VERTICAL
        ──────────────────────────────────────────── */

        initVerticalLayout() {
            const breakpoint = this.config.vertical?.responsiveBreakpoint || 1024;

            if (this.config.vertical?.enableResponsive) {
                this._handleVerticalResponsive(breakpoint);
                $(window).on(`resize.bwps-vertical-${this.widgetId}`, () => {
                    this._handleVerticalResponsive(breakpoint);
                });
            } else {
                this.initVerticalDesktop();
            }
        }

        _handleVerticalResponsive(breakpoint) {
            const width      = $(window).width();
            const $desktop   = this.$wrapper.find('.bw-ps-thumbnails, .bw-ps-main-images');
            const $responsive = this.$wrapper.find('.bw-ps-vertical-responsive');

            if (width >= breakpoint) {
                $desktop.show();
                $responsive.hide();
                this._destroyVerticalEmbla();
                this.initVerticalDesktop();
            } else {
                $desktop.hide();
                $responsive.show();
                this.initVerticalResponsive();
            }
        }

        initVerticalDesktop() {
            const $thumbnails         = this.$wrapper.find('.bw-ps-thumbnails');
            const $mainImages         = this.$wrapper.find('.bw-ps-main-images');
            const $mainImageElements  = $mainImages.find('.bw-ps-main-image');

            if ($thumbnails.length === 0 || $mainImageElements.length === 0) return;

            BWEmblaCore.initImageLoading($thumbnails[0]);
            BWEmblaCore.initImageLoading($mainImages[0]);

            // Click thumbnail → scroll alla main image corrispondente
            $thumbnails.find('.bw-ps-thumb')
                .off(`click.bwps-${this.widgetId}`)
                .on(`click.bwps-${this.widgetId}`, (e) => {
                    const index   = parseInt($(e.currentTarget).data('index'), 10);
                    const $target = $mainImageElements.eq(index);

                    if (!$target.length) return;

                    // Fix calcolo scroll: usa offsetTop relativo al container scrollabile
                    const containerScrollTop = $mainImages.scrollTop();
                    const targetOffsetTop    = $target[0].offsetTop;

                    $mainImages.animate(
                        { scrollTop: targetOffsetTop },
                        this.config.vertical?.smoothScroll ? 400 : 0
                    );

                    $thumbnails.find('.bw-ps-thumb').removeClass('active');
                    $(e.currentTarget).addClass('active');
                });

            // Click main image → popup
            $mainImageElements.find('.bw-ps-image-clickable')
                .off(`click.bwps-${this.widgetId}`)
                .on(`click.bwps-${this.widgetId}`, (e) => {
                    const index = parseInt(
                        $(e.currentTarget).closest('.bw-ps-main-image').data('bw-index'), 10
                    );
                    if (!isNaN(index) && this.config.enablePopup) {
                        this.openModal(index);
                    }
                });

            // Scroll main → aggiorna thumbnail attivo
            $mainImages
                .off(`scroll.bwps-${this.widgetId}`)
                .on(`scroll.bwps-${this.widgetId}`, () => {
                    const scrollTop   = $mainImages.scrollTop();
                    let activeIndex   = 0;

                    $mainImageElements.each(function (i) {
                        if (scrollTop >= this.offsetTop - 100) {
                            activeIndex = i;
                        }
                    });

                    $thumbnails.find('.bw-ps-thumb').removeClass('active');
                    $thumbnails.find('.bw-ps-thumb').eq(activeIndex).addClass('active');
                });

            $thumbnails.find('.bw-ps-thumb').first().addClass('active');
        }

        initVerticalResponsive() {
            // Evita doppia inizializzazione
            if (this.emblaMain) return;

            const mainViewport   = this.$wrapper.find('.bw-ps-main-viewport')[0];
            const thumbsViewport = this.$wrapper.find('.bw-ps-thumbs-viewport')[0];

            if (!mainViewport || !thumbsViewport) return;

            const thumbsCount = this.config.vertical?.thumbsSlidesToShow || 4;

            // Main slider
            this.emblaMain = new BWEmblaCore(mainViewport, {
                loop:          false,
                align:         'center',
                containScroll: 'trimSnaps',
                watchResize:   true,
            }, {
                onSelect: (selectedIndex) => {
                    // Sync thumbs → scorri al thumbnail corrispondente
                    if (this.emblaThumbs) {
                        this.emblaThumbs.scrollTo(selectedIndex);
                    }
                    this._updateActiveThumb(selectedIndex);
                },
            });

            const mainApi = this.emblaMain.init();

            // Thumbs slider
            this.emblaThumbs = new BWEmblaCore(thumbsViewport, {
                loop:          false,
                align:         'start',
                containScroll: 'trimSnaps',
                slidesToScroll: 1,
                dragFree:      true,
                watchResize:   true,
            });

            this.emblaThumbs.init();

            // Click su thumb → scroll main
            $(thumbsViewport).on(`click.bwps-vertical-${this.widgetId}`, '.bw-ps-slide-thumb', (e) => {
                const index = parseInt($(e.currentTarget).data('bw-index'), 10);
                if (!isNaN(index) && mainApi) {
                    mainApi.scrollTo(index);
                }
            });

            // Click su main slide → popup
            $(mainViewport).on(`click.bwps-vertical-${this.widgetId}`, '.bw-ps-slide-main', (e) => {
                const index = parseInt($(e.currentTarget).data('bw-index'), 10);
                if (!isNaN(index) && this.config.enablePopup) {
                    this.openModal(index);
                }
            });

            this._updateActiveThumb(0);
        }

        _updateActiveThumb(index) {
            const $thumbs = this.$wrapper.find('.bw-ps-slide-thumb');
            $thumbs.removeClass('is-active');
            $thumbs.eq(index).addClass('is-active');
        }

        _destroyVerticalEmbla() {
            if (this.emblaMain)   { this.emblaMain.destroy();   this.emblaMain = null; }
            if (this.emblaThumbs) { this.emblaThumbs.destroy(); this.emblaThumbs = null; }
            this.$wrapper.find('.bw-ps-main-viewport').off(`.bwps-vertical-${this.widgetId}`);
            this.$wrapper.find('.bw-ps-thumbs-viewport').off(`.bwps-vertical-${this.widgetId}`);
        }

        /* ────────────────────────────────────────────
           VISIBILITÀ FRECCE (responsive)
        ──────────────────────────────────────────── */

        _updateArrowsVisibility() {
            const width     = $(window).width();
            const $arrows   = this.$wrapper.find('.bw-ps-arrows-container');
            let showArrows  = true; // default desktop: mostra

            for (const bp of this._sortedBreakpoints) {
                if (width <= bp.breakpoint) {
                    showArrows = bp.showArrows === true;
                    // no break: keep going to find the most specific (smallest) matching bp
                }
            }

            $arrows.css('display', showArrows ? 'flex' : 'none');
        }

        /* ────────────────────────────────────────────
           VISIBILITÀ DOTS (responsive)
        ──────────────────────────────────────────── */

        _updateDotsVisibility() {
            const width   = $(window).width();
            const $dots   = this.$wrapper.find('.bw-ps-dots-container');
            let showDots  = true; // default desktop: mostra

            for (const bp of this._sortedBreakpoints) {
                if (width <= bp.breakpoint) {
                    showDots = bp.showDots === true;
                    // no break: keep going to find the most specific (smallest) matching bp
                }
            }

            $dots.css('display', showDots ? 'flex' : 'none');
        }

        /* ────────────────────────────────────────────
           IMAGE HEIGHT MODE (responsive)
        ──────────────────────────────────────────── */

        _updateImageHeightControls() {
            const width      = $(window).width();
            const $horizontal = this.$wrapper.find('.bw-ps-horizontal');
            const $images    = this.$wrapper.find('.bw-ps-image img');

            let heightMode   = 'auto';
            let imageHeight  = null;
            let imageWidth   = null;

            for (const bp of this._sortedBreakpoints) {
                if (width <= bp.breakpoint) {
                    if (bp.imageHeightMode) heightMode  = bp.imageHeightMode;
                    if (bp.imageHeight)     imageHeight = bp.imageHeight;
                    if (bp.imageWidth)      imageWidth  = bp.imageWidth;
                    // no break: keep going to find the most specific (smallest) matching bp
                }
            }

            const heightClasses = ['bw-ps-height-auto', 'bw-ps-height-fixed', 'bw-ps-height-contain', 'bw-ps-height-cover'];
            $horizontal.removeClass(heightClasses.join(' ')).addClass(`bw-ps-height-${heightMode}`);

            if (heightMode !== 'auto' && imageHeight?.size != null && imageHeight?.unit) {
                $images.css('height', `${imageHeight.size}${imageHeight.unit}`);
            } else {
                $images.css('height', '');
            }

            if (['contain', 'cover'].includes(heightMode) && imageWidth?.size != null && imageWidth?.unit) {
                $images.css('width', `${imageWidth.size}${imageWidth.unit}`);
            } else {
                $images.css('width', '');
            }
        }

        /* ────────────────────────────────────────────
           EMBLA BREAKPOINT OPTIONS (slidesToScroll, centerMode, variableWidth)
        ──────────────────────────────────────────── */

        /**
         * Restituisce l'indice del breakpoint attivo in _sortedBreakpoints,
         * oppure -1 se nessun breakpoint è attivo (desktop).
         */
        _getActiveBreakpointIndex() {
            const width = $(window).width();
            let result = -1;
            for (let i = 0; i < this._sortedBreakpoints.length; i++) {
                if (width <= this._sortedBreakpoints[i].breakpoint) {
                    result = i; // keep updating: last match = most specific (smallest) bp
                }
            }
            return result;
        }

        /**
         * Chiama emblaApi.reInit() solo quando cambia il breakpoint attivo,
         * aggiornando slidesToScroll, align (centerMode) e containScroll (variableWidth).
         */
        _updateEmblaBreakpointOptions() {
            if (!this.emblaCore) return;
            const api = this.emblaCore.api();
            if (!api) return;

            const idx = this._getActiveBreakpointIndex();
            if (idx === this._lastBreakpointIndex) return; // nessun cambio
            this._lastBreakpointIndex = idx;

            const bp        = idx >= 0 ? this._sortedBreakpoints[idx] : null;
            const hCfg      = this.config.horizontal || {};
            const baseAlign = hCfg.align || 'start';
            const activeAlign = (bp?.centerMode) ? 'center' : baseAlign;

            const newOpts = {
                slidesToScroll: bp?.slidesToScroll || 1,
                align:          activeAlign,
                containScroll:  (bp?.centerMode || bp?.variableWidth || activeAlign !== 'start') ? false : 'trimSnaps',
            };

            api.reInit(newOpts);
        }

        /* ────────────────────────────────────────────
           POPUP
        ──────────────────────────────────────────── */

        initPopup() {
            const $overlay  = this.$wrapper.find('.bw-ps-popup-overlay');
            const $closeBtn = $overlay.find('.bw-ps-popup-close');

            if ($overlay.length === 0) return;

            // Fix CSS popup: copia la classe Elementor sul popup prima di spostarlo in body.
            // Così i selettori {{WRAPPER}} .bw-ps-popup-* continuano a funzionare.
            if (!$overlay.parent().is('body')) {
                const elementorClass = this.$wrapper
                    .closest('[class*="elementor-element-"]')
                    .attr('class')
                    ?.match(/elementor-element-[\w]+/)?.[0] || '';

                if (elementorClass) {
                    $overlay.addClass(elementorClass);
                }
                $overlay.appendTo('body');
            }

            $overlay.attr('data-bw-ps-widget-id', this.widgetId);
            this.$popupOverlay = $overlay;

            BWEmblaCore.initImageLoading($overlay[0]);

            $closeBtn.off(`click.bwps-${this.widgetId}`)
                     .on(`click.bwps-${this.widgetId}`, () => this.closeModal());

            $overlay.off(`click.bwps-${this.widgetId}`)
                    .on(`click.bwps-${this.widgetId}`, (e) => {
                        if ($(e.target).hasClass('bw-ps-popup-overlay')) {
                            this.closeModal();
                        }
                    });

            $(document).off(`keydown.bwps-${this.widgetId}`)
                       .on(`keydown.bwps-${this.widgetId}`, (e) => {
                           if (e.key === 'Escape' && $overlay.is(':visible')) {
                               this.closeModal();
                           }
                       });
        }

        openModal(startIndex) {
            const $overlay     = this.$popupOverlay || $(`.bw-ps-popup-overlay[data-bw-ps-widget-id="${this.widgetId}"]`);
            const $targetImage = $overlay.find('.bw-ps-popup-image').eq(startIndex);

            if ($overlay.length === 0 || $targetImage.length === 0) return;

            $overlay.fadeIn(300, function () {
                $(this).addClass('active');
            });

            $('body').css('overflow', 'hidden');

            const scrollToTarget = () => {
                const headerH  = $overlay.find('.bw-ps-popup-header').outerHeight() || 0;
                const overlayT = $overlay[0].getBoundingClientRect().top;
                const targetT  = $targetImage[0].getBoundingClientRect().top;
                const delta    = targetT - overlayT - headerH;
                $overlay.scrollTop($overlay.scrollTop() + delta);
            };

            requestAnimationFrame(() => {
                scrollToTarget();
                setTimeout(scrollToTarget, 150);
            });
        }

        closeModal() {
            const $overlay = this.$popupOverlay || $(`.bw-ps-popup-overlay[data-bw-ps-widget-id="${this.widgetId}"]`);
            $overlay.removeClass('active').fadeOut(300);
            $('body').css('overflow', '');
        }

        /* ────────────────────────────────────────────
           CUSTOM CURSOR — per-istanza, RAF on-demand
        ──────────────────────────────────────────── */

        initCustomCursor() {
            const cursorId = `bw-ps-cursor-${this.widgetId}`;
            this._cursor   = $(`<div class="bw-ps-custom-cursor" data-cursor-id="${cursorId}"></div>`)
                .appendTo('body');

            const $wrapper = this.$wrapper;
            const $cursor  = this._cursor;

            // Il cursore di sistema si nasconde sempre quando il custom è attivo
            $wrapper.addClass('bw-ps-hide-cursor');

            // Stato RAF (targX/Y inseguiti con easing, RAF on-demand)
            const state = { targX: 0, targY: 0, curX: 0, curY: 0, running: false };
            this._cursorState = state;

            const animateCursor = () => {
                const ease = 0.18;
                state.curX += (state.targX - state.curX) * ease;
                state.curY += (state.targY - state.curY) * ease;
                $cursor.css({ left: `${state.curX}px`, top: `${state.curY}px` });
                if (state.running) {
                    this._cursorRafId = requestAnimationFrame(animateCursor);
                }
            };

            $wrapper.on(`mousemove.bwps-cursor-${this.widgetId}`, (e) => {
                state.targX = e.clientX;
                state.targY = e.clientY;
                if (!state.running) {
                    state.curX    = state.targX;
                    state.curY    = state.targY;
                    state.running = true;
                    this._cursorRafId = requestAnimationFrame(animateCursor);
                }
            });

            $wrapper.on(`mouseleave.bwps-cursor-${this.widgetId}`, () => {
                state.running = false;
                if (this._cursorRafId) {
                    cancelAnimationFrame(this._cursorRafId);
                    this._cursorRafId = null;
                }
                $cursor.removeClass('active zoom prev next');
            });

            if (this.layoutMode === 'horizontal') {
                const emblaApi  = this.emblaCore ? this.emblaCore.api() : null;
                const $viewport = $wrapper.find('.bw-ps-embla-viewport');

                $viewport.on(`mouseenter.bwps-cursor-${this.widgetId}`, '.bw-ps-image-clickable', (e) => {
                    const $slide     = $(e.currentTarget).closest('.bw-ps-slide');
                    const slideIndex = parseInt($slide.data('bw-index'), 10);
                    const selected   = emblaApi ? emblaApi.selectedScrollSnap() : 0;
                    $cursor.removeClass('zoom prev next');
                    if (slideIndex === selected) {
                        $cursor.addClass('zoom active');
                    } else {
                        $cursor.addClass(slideIndex < selected ? 'prev' : 'next').addClass('active');
                    }
                });

                $viewport.on(`mouseleave.bwps-cursor-${this.widgetId}`, '.bw-ps-image-clickable', () => {
                    $cursor.removeClass('active zoom prev next');
                });
            }

            if (this.layoutMode === 'vertical') {
                $wrapper.find('.bw-ps-main-images .bw-ps-image-clickable')
                    .on(`mouseenter.bwps-cursor-${this.widgetId}`, () => {
                        $cursor.removeClass('prev next').addClass('zoom active');
                    })
                    .on(`mouseleave.bwps-cursor-${this.widgetId}`, () => {
                        $cursor.removeClass('active zoom');
                    });
            }
        }

        /* ────────────────────────────────────────────
           UTILITY
        ──────────────────────────────────────────── */

        isTouchDevice() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        }

        /* ────────────────────────────────────────────
           DESTROY
        ──────────────────────────────────────────── */

        destroy() {
            // Istanze Embla
            if (this.emblaCore)   { this.emblaCore.destroy();   this.emblaCore = null; }
            if (this.emblaMain)   { this.emblaMain.destroy();   this.emblaMain = null; }
            if (this.emblaThumbs) { this.emblaThumbs.destroy(); this.emblaThumbs = null; }

            // Event listeners namespaced
            $(document).off(`.bwps-${this.widgetId}`);
            $(window).off(`.bwps-${this.widgetId}`).off(`.bwps-vertical-${this.widgetId}`);
            this.$wrapper.off(`.bwps-${this.widgetId}`).off(`.bwps-cursor-${this.widgetId}`);
            this.$wrapper.find('.bw-ps-embla-viewport').off(`.bwps-${this.widgetId}`).off(`.bwps-cursor-${this.widgetId}`);
            this.$wrapper.find('.bw-ps-main-viewport').off(`.bwps-vertical-${this.widgetId}`);
            this.$wrapper.find('.bw-ps-thumbs-viewport').off(`.bwps-vertical-${this.widgetId}`);

            // RAF cursore
            if (this._cursorRafId) {
                cancelAnimationFrame(this._cursorRafId);
                this._cursorRafId = null;
            }

            // Cursore: rimosso dal DOM (non solo nascosto)
            if (this._cursor) {
                this._cursor.remove();
                this._cursor = null;
            }

            this.initialized = false;
        }
    }

    /* ────────────────────────────────────────────
       INIZIALIZZAZIONE
    ──────────────────────────────────────────── */

    function initWidgets() {
        $('.bw-ps-wrapper').each(function () {
            const $wrapper = $(this);
            if ($wrapper.data('bw-ps-instance')) return;
            const instance = new BWPresentationSlide(this);
            $wrapper.data('bw-ps-instance', instance);
        });
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
            'frontend/element_ready/bw-presentation-slide.default',
            function ($scope) {
                const $wrapper = $scope.find('.bw-ps-wrapper');
                if (!$wrapper.length) return;

                const existing = $wrapper.data('bw-ps-instance');
                if (existing) {
                    existing.destroy();
                    $wrapper.removeData('bw-ps-instance');
                }

                const instance = new BWPresentationSlide($wrapper[0]);
                $wrapper.data('bw-ps-instance', instance);
            }
        );
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

    window.BWPresentationSlide = BWPresentationSlide;

})(jQuery);
