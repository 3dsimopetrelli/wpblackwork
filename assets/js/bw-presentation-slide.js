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
            this._sortedBreakpoints = [];

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

            // Autoplay options (false se disabilitato)
            const autoplayOpts = hCfg.autoplay ? {
                delay:             hCfg.autoplaySpeed || 3000,
                playOnInit:        true,
                stopOnInteraction: true,
                stopOnMouseEnter:  hCfg.pauseOnHover !== false,
                stopOnFocusIn:     true,
                jump:              false,
            } : false;

            const emblaOptions = {
                loop:          hCfg.infinite === true,
                align:         'start',
                containScroll: 'trimSnaps',
                slidesToScroll: 1,
                watchResize:   true,
                watchDrag:     true,
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

            this.$wrapper.removeClass('loading');

            if (!api) return;

            // Visibilità frecce + image-height mode iniziale
            this._updateArrowsVisibility();
            this._updateImageHeightControls();

            // Aggiorna on resize via window (Embla watchResize aggiorna il layout,
            // noi aggiorniamo solo lo stato delle frecce e l'imageHeight)
            $(window).on(`resize.bwps-${this.widgetId}`, () => {
                this._updateArrowsVisibility();
                this._updateImageHeightControls();
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
                    break;
                }
            }

            $arrows.css('display', showArrows ? 'flex' : 'none');
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
                    break;
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
            // Cursore per-istanza: ID univoco per non condividere tra widget
            const cursorId = `bw-ps-cursor-${this.widgetId}`;
            this._cursor   = $(`<div class="bw-ps-custom-cursor" data-cursor-id="${cursorId}"></div>`)
                .appendTo('body');

            const $wrapper = this.$wrapper;
            const $cursor  = this._cursor;
            const cfg      = this.config;

            const zoomText   = cfg.cursorZoomText       || 'ZOOM';
            const zoomSize   = isFinite(cfg.cursorZoomTextSize) ? `${cfg.cursorZoomTextSize}px`   : '12px';
            const borderW    = isFinite(cfg.cursorBorderWidth)  ? `${cfg.cursorBorderWidth}px`    : '2px';
            const borderC    = cfg.cursorBorderColor            || '#000';
            const blurStr    = isFinite(parseFloat(cfg.cursorBlur)) ? `${parseFloat(cfg.cursorBlur)}px` : '12px';
            const arrowC     = cfg.cursorArrowColor             || '#000';
            const arrowSz    = isFinite(cfg.cursorArrowSize)    ? `${cfg.cursorArrowSize}px`      : '24px';
            const bgColor    = cfg.cursorBackgroundColor        || '#ffffff';
            const bgOpacity  = isFinite(parseFloat(cfg.cursorBackgroundOpacity))
                ? Math.min(Math.max(parseFloat(cfg.cursorBackgroundOpacity), 0), 1)
                : 0.6;
            const bgRgba = this._hexToRgba(bgColor, bgOpacity);

            if (cfg.hideSystemCursor) {
                $wrapper.addClass('bw-ps-hide-cursor');
            }

            $cursor.css({
                borderWidth:           borderW,
                borderColor:           borderC,
                color:                 arrowC,
                '--bw-ps-cursor-bg':   bgRgba,
                '--bw-site-blur':      blurStr,
                '--bw-ps-arrow-size':  arrowSz,
                '--bw-ps-zoom-size':   zoomSize,
            });

            // Stato cursore (targX/targY aggiornati da mousemove, RAF on-demand)
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

            // Mousemove: avvia RAF solo se non già attivo
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

            // Mouseleave wrapper: ferma RAF
            $wrapper.on(`mouseleave.bwps-cursor-${this.widgetId}`, () => {
                state.running = false;
                if (this._cursorRafId) {
                    cancelAnimationFrame(this._cursorRafId);
                    this._cursorRafId = null;
                }
                $cursor.removeClass('active zoom prev next').text('');
            });

            // Stati cursore per layout horizontal
            if (this.layoutMode === 'horizontal') {
                const emblaApi = this.emblaCore ? this.emblaCore.api() : null;
                const $viewport = $wrapper.find('.bw-ps-embla-viewport');

                $viewport.on(`mouseenter.bwps-cursor-${this.widgetId}`, '.bw-ps-image-clickable', (e) => {
                    const $slide     = $(e.currentTarget).closest('.bw-ps-slide');
                    const slideIndex = parseInt($slide.data('bw-index'), 10);
                    const selected   = emblaApi ? emblaApi.selectedScrollSnap() : 0;

                    $cursor.removeClass('zoom prev next');

                    if (slideIndex === selected) {
                        $cursor.addClass('zoom active').text(zoomText);
                    } else {
                        $cursor.addClass(slideIndex < selected ? 'prev' : 'next')
                               .addClass('active').text('');
                    }
                });

                $viewport.on(`mouseleave.bwps-cursor-${this.widgetId}`, '.bw-ps-image-clickable', () => {
                    $cursor.removeClass('active zoom prev next').text('');
                });
            }

            // Stato cursore per layout vertical (desktop)
            if (this.layoutMode === 'vertical') {
                const $mainImages = $wrapper.find('.bw-ps-main-images .bw-ps-image-clickable');

                $mainImages
                    .on(`mouseenter.bwps-cursor-${this.widgetId}`, () => {
                        $cursor.removeClass('prev next').addClass('zoom active').text(zoomText);
                    })
                    .on(`mouseleave.bwps-cursor-${this.widgetId}`, () => {
                        $cursor.removeClass('active zoom').text('');
                    });
            }
        }

        /* ────────────────────────────────────────────
           UTILITY
        ──────────────────────────────────────────── */

        _hexToRgba(hex, alpha) {
            if (!hex || typeof hex !== 'string') return `rgba(255,255,255,${alpha})`;
            let h = hex.replace('#', '').trim();
            if (h.length === 3) h = h.split('').map(c => c + c).join('');
            if (h.length !== 6) return `rgba(255,255,255,${alpha})`;
            const r = parseInt(h.slice(0, 2), 16);
            const g = parseInt(h.slice(2, 4), 16);
            const b = parseInt(h.slice(4, 6), 16);
            if ([r, g, b].some(Number.isNaN)) return `rgba(255,255,255,${alpha})`;
            return `rgba(${r},${g},${b},${alpha})`;
        }

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
