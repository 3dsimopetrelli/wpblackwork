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

    function debounce(fn, ms) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, arguments), ms);
        };
    }

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
            this._cursor      = null;
            this._cursorRafId = null;

            // Sorted breakpoints per image-height mode
            this._sortedBreakpoints    = [];
            this._lastBreakpointIndex  = undefined; // track active bp for Embla reInit

            // Body scroll lock state (iOS-safe popup scroll locking)
            this._savedScrollY = 0;

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
            // Ascendente: il primo match nella direzione crescente è il breakpoint più piccolo
            this._sortedBreakpoints = [...responsive].sort((a, b) => a.breakpoint - b.breakpoint);

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

            const globalAlign     = hCfg.align || 'start';
            // enableTouchDrag: quando false, il drag via touch/pen (mobile & tablet) è disabilitato.
            // Il mouse (desktop) funziona sempre indipendentemente da questa impostazione.
            // Embla watchDrag API: https://www.embla-carousel.com/api/options/#watchdrag
            // Il callback riceve (emblaApi, PointerEvent). PointerEvent.pointerType può essere
            // 'mouse' (desktop), 'touch' (touchscreen) o 'pen' (stilo).
            const enableTouchDrag = hCfg.enableTouchDrag !== false; // default true

            const emblaOptions = {
                loop:           hCfg.infinite === true,
                align:          globalAlign,
                containScroll:  globalAlign === 'start' ? 'trimSnaps' : false,
                slidesToScroll: 1,
                dragFree:       hCfg.dragFree === true,
                watchResize:    true,
                // watchDrag: true   → tutti i pointer types possono trascinare (default)
                // watchDrag: fn     → solo 'mouse' può trascinare (touch/pen bloccati)
                watchDrag: enableTouchDrag
                    ? true
                    : (_emblaApi, evt) => evt.pointerType === 'mouse',
            };

            // Cache selectors prima di init() perché onSelect può essere chiamata durante init()
            this._$horizontal = this.$wrapper.find('.bw-ps-horizontal');
            this._$images     = this.$wrapper.find('.bw-ps-image img');

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

            // Frecce e dots: visibilità gestita da CSS @media inline (PHP render_breakpoint_css).
            // Nessuna funzione JS necessaria — CSS risponde istantaneamente senza race conditions.

            // Image-height mode + opzioni Embla (ancora JS: non gestibili solo da CSS)
            this._updateImageHeightControls();
            this._updateEmblaBreakpointOptions();

            // Aggiorna on resize (debounced: evita esecuzione per ogni pixel)
            $(window).on(`resize.bwps-${this.widgetId}`, debounce(() => {
                this._updateImageHeightControls();
                this._updateEmblaBreakpointOptions();
            }, 150));

            // Track a real press sequence so popup opening can only happen after
            // a pointerdown on the same clickable slide. This prevents accidental
            // openModal() calls from stray/synthetic pointerup events.
            let _pressState = null;
            $(viewport).on(`pointerdown.bwps-${this.widgetId}`, '.bw-ps-image-clickable', (e) => {
                _pressState = {
                    x: e.clientX,
                    y: e.clientY,
                    pointerId: e.pointerId,
                    target: e.currentTarget,
                };
            });

            $(viewport).on(`pointercancel.bwps-${this.widgetId} pointerleave.bwps-${this.widgetId}`, () => {
                _pressState = null;
            });

            // pointerup sulle slide: zoom (popup) o navigazione
            $(viewport).on(`pointerup.bwps-${this.widgetId}`, '.bw-ps-image-clickable', (e) => {
                if (
                    !_pressState ||
                    _pressState.pointerId !== e.pointerId ||
                    _pressState.target !== e.currentTarget
                ) {
                    _pressState = null;
                    return;
                }

                // Ignore if the pointer moved — it was a drag/swipe, not a tap
                if (Math.abs(e.clientX - _pressState.x) > 6 || Math.abs(e.clientY - _pressState.y) > 6) {
                    _pressState = null;
                    return;
                }

                _pressState = null;

                const $slide      = $(e.currentTarget).closest('.bw-ps-slide');
                const slideIndex  = parseInt($slide.data('bw-index'), 10);
                const activeIndex = this._getCenteredHorizontalSlideIndex(viewport, api);

                if (isNaN(slideIndex)) return;

                if (slideIndex === activeIndex) {
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
                $(window).on(`resize.bwps-vertical-${this.widgetId}`, debounce(() => {
                    this._handleVerticalResponsive(breakpoint);
                }, 150));
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
                    const index   = parseInt($(e.currentTarget).data('bw-index'), 10);
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

            // pointerup main image → popup, but only after a real pointerdown on
            // the same image. This keeps Safari support without allowing stray
            // pointerup events to trigger popup opening.
            let _mainPressState = null;
            $mainImageElements.find('.bw-ps-image-clickable')
                .off(`pointerdown.bwps-${this.widgetId}`)
                .on(`pointerdown.bwps-${this.widgetId}`, (e) => {
                    _mainPressState = {
                        pointerId: e.pointerId,
                        target: e.currentTarget,
                    };
                });

            $mainImageElements.find('.bw-ps-image-clickable')
                .off(`pointerup.bwps-${this.widgetId}`)
                .on(`pointerup.bwps-${this.widgetId}`, (e) => {
                    if (
                        !_mainPressState ||
                        _mainPressState.pointerId !== e.pointerId ||
                        _mainPressState.target !== e.currentTarget
                    ) {
                        _mainPressState = null;
                        return;
                    }

                    _mainPressState = null;
                    const index = parseInt(
                        $(e.currentTarget).closest('.bw-ps-main-image').data('bw-index'), 10
                    );
                    if (!isNaN(index) && this.config.enablePopup) {
                        this.openModal(index);
                    }
                });

            // Scroll main → aggiorna thumbnail attivo (RAF throttle: max 1 update per frame)
            let _scrollRaf = null;
            $mainImages
                .off(`scroll.bwps-${this.widgetId}`)
                .on(`scroll.bwps-${this.widgetId}`, () => {
                    if (_scrollRaf) return;
                    _scrollRaf = requestAnimationFrame(() => {
                        _scrollRaf = null;
                        const scrollTop = $mainImages.scrollTop();
                        let activeIndex = 0;
                        $mainImageElements.each(function (i) {
                            if (scrollTop >= this.offsetTop - 100) {
                                activeIndex = i;
                            }
                        });
                        const $thumbItems = $thumbnails.find('.bw-ps-thumb');
                        $thumbItems.removeClass('active').eq(activeIndex).addClass('active');
                    });
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

            // pointerup su main slide → popup (con drag detection), but only after
            // a matching pointerdown on the same slide.
            let _vpPressState = null;
            $(mainViewport).on(`pointerdown.bwps-vertical-${this.widgetId}`, '.bw-ps-slide-main', (e) => {
                _vpPressState = {
                    x: e.clientX,
                    y: e.clientY,
                    pointerId: e.pointerId,
                    target: e.currentTarget,
                };
            });
            $(mainViewport).on(`pointercancel.bwps-vertical-${this.widgetId} pointerleave.bwps-vertical-${this.widgetId}`, () => {
                _vpPressState = null;
            });
            $(mainViewport).on(`pointerup.bwps-vertical-${this.widgetId}`, '.bw-ps-slide-main', (e) => {
                if (
                    !_vpPressState ||
                    _vpPressState.pointerId !== e.pointerId ||
                    _vpPressState.target !== e.currentTarget
                ) {
                    _vpPressState = null;
                    return;
                }

                if (Math.abs(e.clientX - _vpPressState.x) > 6 || Math.abs(e.clientY - _vpPressState.y) > 6) {
                    _vpPressState = null;
                    return;
                }

                _vpPressState = null;
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
           IMAGE HEIGHT MODE (responsive)
        ──────────────────────────────────────────── */

        _updateImageHeightControls() {
            const width      = $(window).width();
            const $horizontal = this._$horizontal;
            const $images    = this._$images;

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
           EMBLA BREAKPOINT OPTIONS (slidesToScroll, centerMode, variableWidth)
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

        /**
         * Determine the slide that is visually centered in the current viewport.
         *
         * Embla's selectedScrollSnap() returns a snap index, which is not always the
         * same as the visually central slide when multiple slides are visible or when
         * alignment/variable-width settings are active. For the popup UX we care about
         * the slide the user perceives as "current", so we derive it from geometry.
         */
        _getCenteredHorizontalSlideIndex(viewport, api) {
            const slides = Array.from(viewport.querySelectorAll('.bw-ps-slide'));
            if (!slides.length) {
                return api ? api.selectedScrollSnap() : -1;
            }

            const viewportRect   = viewport.getBoundingClientRect();
            const viewportCenter = viewportRect.left + (viewportRect.width / 2);
            let bestIndex        = -1;
            let bestDistance     = Infinity;

            slides.forEach((slideEl) => {
                const slideRect = slideEl.getBoundingClientRect();
                const slideCenter = slideRect.left + (slideRect.width / 2);
                const distance = Math.abs(slideCenter - viewportCenter);
                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestIndex = parseInt(slideEl.getAttribute('data-bw-index'), 10);
                }
            });

            if (isNaN(bestIndex) || bestIndex < 0) {
                return api ? api.selectedScrollSnap() : -1;
            }

            return bestIndex;
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

            this.$popupOverlay = $overlay;

            this._syncPopupViewportMetrics();
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
                           if (e.key === 'Escape' && $overlay.hasClass('active')) {
                               this.closeModal();
                           }
                       });

            $(window).off(`resize.bwps-${this.widgetId}`)
                     .on(`resize.bwps-${this.widgetId}`, debounce(() => {
                         this._syncPopupViewportMetrics();
                     }, 100));
        }

        _syncPopupViewportMetrics() {
            const $overlay = this.$popupOverlay;
            if (!$overlay || !$overlay.length) return;

            const overlayEl = $overlay[0];
            const headerEl  = $overlay.find('.bw-ps-popup-header')[0];
            const bodyEl    = $overlay.find('.bw-ps-popup-body')[0];

            const headerHeight = headerEl ? headerEl.getBoundingClientRect().height : 0;
            let bodyPadTop = 40;
            let bodyPadBottom = 40;

            if (bodyEl) {
                const bodyStyles = window.getComputedStyle(bodyEl);
                bodyPadTop = parseFloat(bodyStyles.paddingTop) || 0;
                bodyPadBottom = parseFloat(bodyStyles.paddingBottom) || 0;
            }

            overlayEl.style.setProperty('--bw-ps-popup-header-height', `${headerHeight}px`);
            overlayEl.style.setProperty('--bw-ps-popup-body-pad-top', `${bodyPadTop}px`);
            overlayEl.style.setProperty('--bw-ps-popup-body-pad-bottom', `${bodyPadBottom}px`);
        }

        openModal(startIndex) {
            // Respect the mobile toggle: skip on touch devices if disabled
            if (this.isTouchDevice() && !this.config.enablePopupMobile) return;

            const $overlay     = this.$popupOverlay;

            if (!$overlay || !$overlay.length) return;
            const $targetImage = $overlay.find('.bw-ps-popup-image').eq(startIndex);
            if (!$targetImage.length) return;

            this._syncPopupViewportMetrics();

            // Step 1 — compute scroll position BEFORE locking the body.
            // On iOS Safari, _lockBodyScroll() sets body{position:fixed} which can
            // trigger the browser chrome to show/hide, resizing the viewport.
            // getBoundingClientRect() values captured before this resize are accurate;
            // values captured after may be off by the chrome height (~50-100 px).
            const headerH     = $overlay.find('.bw-ps-popup-header').outerHeight() || 0;
            const overlayRect = $overlay[0].getBoundingClientRect();
            const imageRect   = $targetImage[0].getBoundingClientRect();
            $overlay[0].scrollTop = Math.max(
                0,
                $overlay[0].scrollTop + (imageRect.top - overlayRect.top) - headerH
            );

            // Step 2 — lock body scroll synchronously (before the next paint).
            // Doing it here (not inside rAF) means the page can never scroll under
            // the overlay for even a single frame, eliminating the visible "jump"
            // on iOS Safari when the modal opens mid-page.
            this._lockBodyScroll();

            // Step 3 — show overlay in the next paint.
            // The browser has committed the scroll lock and the overlay's scrollTop,
            // so the CSS opacity 0→1 transition starts from a clean state.
            // visibility:hidden→visible (not display toggling) ensures Safari always
            // has a valid "from" value for the transition.
            requestAnimationFrame(() => {
                $overlay.addClass('active').attr('aria-hidden', 'false');
            });
        }

        closeModal() {
            const $overlay = this.$popupOverlay;
            if (!$overlay) return;

            // CSS handles everything: opacity 1→0 (0.3s), then visibility:hidden (0s delay).
            // No transitionend listener or display toggling needed.
            $overlay.removeClass('active').attr('aria-hidden', 'true');

            // Reset scrollTop after the transition completes so the next open
            // always starts at the correct scroll position.
            setTimeout(() => { $overlay[0].scrollTop = 0; }, 350);

            this._unlockBodyScroll();
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
           BODY SCROLL LOCK (iOS-safe)
           On iOS Safari, `body { overflow: hidden }` does not prevent the page from
           scrolling behind a modal.  The only reliable fix is `position: fixed` with
           `top: -scrollY` so the viewport does not jump, then restoring scrollY on close.
        ──────────────────────────────────────────── */

        _lockBodyScroll() {
            const body = document.body;
            if (body.style.position === 'fixed') return; // already locked
            this._savedScrollY = window.scrollY;
            body.style.overflow = 'hidden';
            body.style.position = 'fixed';
            body.style.top      = `-${this._savedScrollY}px`;
            body.style.width    = '100%';
        }

        _unlockBodyScroll() {
            const body = document.body;
            if (body.style.position !== 'fixed') return; // was not locked by us
            const savedY = this._savedScrollY || 0;
            body.style.overflow = '';
            body.style.position = '';
            body.style.top      = '';
            body.style.width    = '';
            window.scrollTo(0, savedY);
            this._savedScrollY = 0;
        }

        /* ────────────────────────────────────────────
           UTILITY
        ──────────────────────────────────────────── */

        isTouchDevice() {
            // navigator.maxTouchPoints > 0 is no longer a reliable mobile indicator:
            // Safari 15.5+ and Chrome 112+ on macOS report maxTouchPoints = 5 on ALL
            // Macs (anti-fingerprinting), which would falsely block the popup on desktops.
            //
            // (pointer: coarse) is the W3C standard: true only when the primary input
            // is imprecise (finger/stylus), false for mouse/trackpad.
            return window.matchMedia('(pointer: coarse)').matches;
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

            // Popup overlay: spostato in body da initPopup(), va rimosso esplicitamente
            // (altrimenti ogni re-init di Elementor accumula overlay orfani nel DOM)
            if (this.$popupOverlay) {
                this._unlockBodyScroll(); // unlock scroll if popup was open when destroyed
                this.$popupOverlay.remove();
                this.$popupOverlay = null;
            }

            // Cache DOM
            this._$horizontal = null;
            this._$images     = null;

            // Stato istanza
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
        $('.bw-ps-wrapper').each(function () {
            const $wrapper = $(this);
            if ($wrapper.data('bw-ps-instance')) return;
            const instance = new BWPresentationSlide(this);
            $wrapper.data('bw-ps-instance', instance);
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
            'frontend/element_ready/bw-presentation-slide.default',
            function ($scope) {
                const $wrapper = $scope.find('.bw-ps-wrapper');
                if (!$wrapper.length) return;

                const existing = $wrapper.data('bw-ps-instance');
                if (existing) {
                    // On normal frontend pages the widget may have already been
                    // initialized via document.ready(). Re-destroying it here
                    // removes the popup overlay that was moved to <body>, and the
                    // second init can no longer recover it from the wrapper.
                    // In edit mode we still want full destroy/re-init behavior.
                    if (!isElementorEditMode()) {
                        return;
                    }

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
