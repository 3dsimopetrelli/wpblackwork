/**
 * BW Presentation Slide Widget JavaScript
 * Handles horizontal slider, vertical elevator, popup, and custom cursor
 */
(function ($) {
    'use strict';

    /**
     * Main BW Presentation Slide Class
     */
    class BWPresentationSlide {
        constructor(element) {
            this.$wrapper = $(element);
            this.widgetId = this.$wrapper.data('widget-id');
            this.config = this.$wrapper.data('config') || {};
            this.layoutMode = this.config.layoutMode || 'horizontal';
            this.initialized = false;
            this.customCursor = null;
            this.slickInstances = [];

            this.init();
        }

        init() {
            if (this.initialized) {
                return;
            }

            // Wait for Slick to be available
            if (typeof $.fn.slick === 'undefined') {
                console.warn('Slick slider not loaded yet, retrying...');
                setTimeout(() => this.init(), 100);
                return;
            }

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

        /**
         * Initialize Horizontal Slick Slider
         */
        initHorizontalLayout() {
            const $slider = this.$wrapper.find('.bw-ps-slider-horizontal');
            if ($slider.length === 0) return;

            // Destroy existing instance if any
            if ($slider.hasClass('slick-initialized')) {
                $slider.slick('unslick');
            }

            this.$wrapper.addClass('loading');

            // Build responsive breakpoints config
            const responsive = this.config.horizontal.responsive || [];
            this.sortedBreakpoints = [...responsive].sort((a, b) => b.breakpoint - a.breakpoint);

            const slickConfig = {
                infinite: this.config.horizontal.infinite,
                autoplay: this.config.horizontal.autoplay,
                autoplaySpeed: this.config.horizontal.autoplaySpeed,
                speed: this.config.horizontal.speed,
                pauseOnHover: this.config.horizontal.pauseOnHover,
                adaptiveHeight: true, // Default enabled
                arrows: false, // We use custom arrows
                dots: false,
                slidesToShow: 1, // Default value - overridden by breakpoints
                slidesToScroll: 1, // Default value - overridden by breakpoints
                centerMode: false, // Default value - overridden by breakpoints
                centerPadding: '0',
                focusOnSelect: true,
                responsive: responsive
            };

            $slider.one('init', () => {
                this.$wrapper.removeClass('loading');
                this.initImageFade($slider);
            });

            $slider.slick(slickConfig);
            this.slickInstances.push($slider);

            // Apply dots position class
            this.applyDotsPosition();

            // Custom arrows navigation - show/hide based on breakpoints
            this.initArrowsVisibility();

            // Apply slide width based on breakpoints
            this.initSlideWidths();

            // Apply image height mode based on breakpoints
            this.initImageHeightControls();

            // Click behavior: center opens popup with custom cursor, side slides only navigate
            $slider.on('click', '.slick-slide.slick-active .bw-ps-image-clickable', (e) => {
                const $slide = $(e.currentTarget).closest('.slick-slide');
                const index = parseInt($slide.data('bw-index'), 10);
                const $cursor = this.customCursor || $('.bw-ps-custom-cursor');
                const isZoomCursor = $cursor.length && $cursor.hasClass('zoom');

                if (isNaN(index)) {
                    return;
                }

                if (this.config.enableCustomCursor) {
                    if ($slide.hasClass('slick-center') && isZoomCursor) {
                        if (this.config.enablePopup) {
                            this.openModal(index);
                        }
                        return;
                    }

                    $slider.slick('slickGoTo', index);
                    return;
                }

                if (this.config.enablePopup) {
                    this.openModal(index);
                }
            });
        }

        /**
         * Initialize image fade-in when loaded
         */
        initImageFade($container) {
            const $images = $container.find('img');
            if ($images.length === 0) {
                return;
            }

            $images.each(function () {
                const $img = $(this);
                $img.addClass('bw-ps-fade');
                if (this.complete && this.naturalWidth > 0) {
                    $img.addClass('is-loaded');
                } else {
                    $img.one('load', function () {
                        $(this).addClass('is-loaded');
                    });
                }
            });
        }

        /**
         * Initialize arrows visibility based on breakpoints
         */
        initArrowsVisibility() {
            // Initial check
            this.updateArrowsVisibility();

            // Update on window resize
            $(window).on(`resize.bwps-${this.widgetId}`, () => {
                this.updateArrowsVisibility();
            });

            // Custom arrows click events
            this.$wrapper.find('.bw-ps-arrow-prev').on('click', (e) => {
                e.preventDefault();
                this.$wrapper.find('.bw-ps-slider-horizontal').slick('slickPrev');
            });

            this.$wrapper.find('.bw-ps-arrow-next').on('click', (e) => {
                e.preventDefault();
                this.$wrapper.find('.bw-ps-slider-horizontal').slick('slickNext');
            });
        }

        /**
         * Update arrows visibility based on current breakpoint
         */
        updateArrowsVisibility() {
            const windowWidth = $(window).width();
            const $arrows = this.$wrapper.find('.bw-ps-arrows-container');
            const sortedBreakpoints = this.sortedBreakpoints || [];

            let showArrows = true; // Desktop default (always show)

            // Check breakpoints from largest to smallest
            for (const bp of sortedBreakpoints) {
                if (windowWidth <= bp.breakpoint) {
                    // Use the showArrows property we added in PHP
                    showArrows = bp.showArrows === true;
                    break; // Use the first matching breakpoint
                }
            }

            if (showArrows) {
                $arrows.css('display', 'flex');
            } else {
                $arrows.css('display', 'none');
            }
        }

        /**
         * Initialize slide widths based on breakpoints
         */
        initSlideWidths() {
            // Initial check
            this.updateSlideWidths();

            // Update on window resize
            $(window).on(`resize.bwps-width-${this.widgetId}`, () => {
                this.updateSlideWidths();
            });
        }

        /**
         * Initialize image height settings based on breakpoints
         */
        initImageHeightControls() {
            this.updateImageHeightControls();

            $(window).on(`resize.bwps-height-${this.widgetId}`, () => {
                this.updateImageHeightControls();
            });
        }

        /**
         * Update slide widths based on current breakpoint
         */
        updateSlideWidths() {
            const windowWidth = $(window).width();
            const $slides = this.$wrapper.find('.bw-ps-slide');
            const sortedBreakpoints = this.sortedBreakpoints || [];

            let slideWidth = null;

            // Check breakpoints from largest to smallest
            for (const bp of sortedBreakpoints) {
                if (windowWidth <= bp.breakpoint) {
                    if (bp.slideWidth) {
                        slideWidth = bp.slideWidth;
                        break;
                    }
                }
            }

            // Apply or remove width
            if (slideWidth) {
                $slides.css('width', slideWidth + 'px');
            } else {
                $slides.css('width', '');
            }
        }

        /**
         * Update image height mode and dimensions based on current breakpoint
         */
        updateImageHeightControls() {
            const windowWidth = $(window).width();
            const $horizontal = this.$wrapper.find('.bw-ps-horizontal');
            const $images = this.$wrapper.find('.bw-ps-image img');
            const sortedBreakpoints = this.sortedBreakpoints || [];

            let heightMode = 'auto';
            let imageHeight = null;
            let imageWidth = null;

            for (const bp of sortedBreakpoints) {
                if (windowWidth <= bp.breakpoint) {
                    if (bp.imageHeightMode) {
                        heightMode = bp.imageHeightMode;
                    }
                    if (bp.imageHeight) {
                        imageHeight = bp.imageHeight;
                    }
                    if (bp.imageWidth) {
                        imageWidth = bp.imageWidth;
                    }
                    break;
                }
            }

            const heightClasses = ['bw-ps-height-auto', 'bw-ps-height-fixed', 'bw-ps-height-contain', 'bw-ps-height-cover'];
            $horizontal.removeClass(heightClasses.join(' ')).addClass(`bw-ps-height-${heightMode}`);

            if (heightMode !== 'auto' && imageHeight && imageHeight.size !== null && imageHeight.unit) {
                $images.css('height', `${imageHeight.size}${imageHeight.unit}`);
            } else {
                $images.css('height', '');
            }

            if ((heightMode === 'contain' || heightMode === 'cover') && imageWidth && imageWidth.size !== null && imageWidth.unit) {
                $images.css('width', `${imageWidth.size}${imageWidth.unit}`);
            } else {
                $images.css('width', '');
            }
        }

        /**
         * Apply dots position class
         */
        applyDotsPosition() {
            const position = this.config.dotsPosition || 'center';
            const $dots = this.$wrapper.find('.slick-dots');

            if ($dots.length) {
                $dots.addClass(`bw-ps-dots-${position}`);
            }

            // Check after Slick init
            setTimeout(() => {
                const $dotsAfter = this.$wrapper.find('.slick-dots');
                if ($dotsAfter.length) {
                    $dotsAfter.addClass(`bw-ps-dots-${position}`);
                }
            }, 100);
        }

        /**
         * Initialize Vertical Layout (Desktop + Responsive)
         */
        initVerticalLayout() {
            const breakpoint = this.config.vertical.responsiveBreakpoint || 1024;

            if (this.config.vertical.enableResponsive) {
                this.handleVerticalResponsive(breakpoint);
                $(window).on(`resize.bwps-vertical-${this.widgetId}`, () => this.handleVerticalResponsive(breakpoint));
            } else {
                this.initVerticalDesktop();
            }
        }

        /**
         * Handle Vertical Responsive Mode (Desktop vs Mobile)
         */
        handleVerticalResponsive(breakpoint) {
            const windowWidth = $(window).width();
            const $desktop = this.$wrapper.find('.bw-ps-thumbnails, .bw-ps-main-images');
            const $responsive = this.$wrapper.find('.bw-ps-vertical-responsive');

            if (windowWidth >= breakpoint) {
                // Desktop mode
                $desktop.show();
                $responsive.hide();
                this.destroyVerticalSlick();
                this.initVerticalDesktop();
            } else {
                // Mobile/Tablet mode
                $desktop.hide();
                $responsive.show();
                this.initVerticalSlick();
            }
        }

        /**
         * Initialize Vertical Desktop (Elevator)
         */
        initVerticalDesktop() {
            const $thumbnails = this.$wrapper.find('.bw-ps-thumbnails');
            const $mainImages = this.$wrapper.find('.bw-ps-main-images');
            const $mainImageElements = $mainImages.find('.bw-ps-main-image');

            if ($thumbnails.length === 0 || $mainImageElements.length === 0) return;

            this.initImageFade($thumbnails);
            this.initImageFade($mainImages);

            // Thumbnail click - scroll to corresponding main image
            $thumbnails.find('.bw-ps-thumb').off('click').on('click', (e) => {
                const index = parseInt($(e.currentTarget).data('index'), 10);
                const $target = $mainImageElements.eq(index);

                if ($target.length) {
                    $mainImages.animate({
                        scrollTop: $target.position().top + $mainImages.scrollTop()
                    }, this.config.vertical.smoothScroll ? 500 : 0);

                    // Update active thumbnail
                    $thumbnails.find('.bw-ps-thumb').removeClass('active');
                    $(e.currentTarget).addClass('active');
                }
            });

            // Main image click - open popup
            $mainImageElements.find('.bw-ps-image-clickable').off('click').on('click', (e) => {
                const index = parseInt($(e.currentTarget).closest('.bw-ps-main-image').data('bw-index'), 10);
                if (!isNaN(index) && this.config.enablePopup) {
                    this.openModal(index);
                }
            });

            // Update active thumbnail on scroll
            $mainImages.off('scroll').on('scroll', () => {
                const scrollTop = $mainImages.scrollTop();
                let activeIndex = 0;

                $mainImageElements.each(function (index) {
                    const offsetTop = $(this).position().top + scrollTop;
                    if (scrollTop >= offsetTop - 100) {
                        activeIndex = index;
                    }
                });

                $thumbnails.find('.bw-ps-thumb').removeClass('active');
                $thumbnails.find('.bw-ps-thumb').eq(activeIndex).addClass('active');
            });

            // Set first thumbnail as active
            $thumbnails.find('.bw-ps-thumb').first().addClass('active');
        }

        /**
         * Initialize Vertical Responsive Slick (Main + Thumbs Sync)
         */
        initVerticalSlick() {
            const $sliderMain = this.$wrapper.find('.bw-ps-slider-main');
            const $sliderThumbs = this.$wrapper.find('.bw-ps-slider-thumbs');

            if ($sliderMain.length === 0 || $sliderThumbs.length === 0) return;

            // Destroy existing instances
            if ($sliderMain.hasClass('slick-initialized')) {
                $sliderMain.slick('unslick');
            }
            if ($sliderThumbs.hasClass('slick-initialized')) {
                $sliderThumbs.slick('unslick');
            }

            // Initialize main slider
            $sliderMain.slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: false,
                fade: true,
                adaptiveHeight: true,
                asNavFor: $sliderThumbs
            });

            // Initialize thumbnails slider
            $sliderThumbs.slick({
                slidesToShow: this.config.vertical.thumbsSlidesToShow || 4,
                slidesToScroll: 1,
                asNavFor: $sliderMain,
                dots: false,
                arrows: false,
                centerMode: false,
                focusOnSelect: true
            });

            this.slickInstances.push($sliderMain, $sliderThumbs);
            this.initImageFade($sliderMain);
            this.initImageFade($sliderThumbs);

            // Click on main slide opens popup
            $sliderMain.on('click', '.bw-ps-slide-main', (e) => {
                const index = parseInt($(e.currentTarget).data('bw-index'), 10);
                if (!isNaN(index) && this.config.enablePopup) {
                    this.openModal(index);
                }
            });
        }

        /**
         * Destroy Vertical Slick Instances
         */
        destroyVerticalSlick() {
            const $sliderMain = this.$wrapper.find('.bw-ps-slider-main');
            const $sliderThumbs = this.$wrapper.find('.bw-ps-slider-thumbs');

            if ($sliderMain.hasClass('slick-initialized')) {
                $sliderMain.slick('unslick');
            }
            if ($sliderThumbs.hasClass('slick-initialized')) {
                $sliderThumbs.slick('unslick');
            }
        }

        /**
         * Initialize Popup Modal
         */
        initPopup() {
            const $overlay = this.$wrapper.find('.bw-ps-popup-overlay');
            const $closeBtn = $overlay.find('.bw-ps-popup-close');

            if ($overlay.length === 0) return;

            if (!$overlay.parent().is('body')) {
                $overlay.appendTo('body');
            }
            $overlay.attr('data-bw-ps-widget-id', this.widgetId);
            this.$popupOverlay = $overlay;
            this.initImageFade($overlay);

            // Close button click
            $closeBtn.off('click').on('click', () => this.closeModal());

            // Overlay click (optional)
            $overlay.off('click').on('click', (e) => {
                if ($(e.target).hasClass('bw-ps-popup-overlay')) {
                    this.closeModal();
                }
            });

            // ESC key close
            $(document).off(`keydown.bwps-${this.widgetId}`).on(`keydown.bwps-${this.widgetId}`, (e) => {
                if (e.key === 'Escape' && $overlay.is(':visible')) {
                    this.closeModal();
                }
            });
        }

        /**
         * Open Modal at Specific Index
         */
        openModal(startIndex) {
            const $overlay = this.$popupOverlay || $(`.bw-ps-popup-overlay[data-bw-ps-widget-id="${this.widgetId}"]`);
            const $targetImage = $overlay.find('.bw-ps-popup-image').eq(startIndex);

            if ($overlay.length === 0 || $targetImage.length === 0) return;

            // Show overlay with fade
            $overlay.fadeIn(300, function () {
                $(this).addClass('active');
            });

            // Disable body scroll
            $('body').css('overflow', 'hidden');

            // Scroll to target image
            const scrollToTarget = () => {
                const headerHeight = $overlay.find('.bw-ps-popup-header').outerHeight() || 0;
                const overlayTop = $overlay[0].getBoundingClientRect().top;
                const targetTop = $targetImage[0].getBoundingClientRect().top;
                const delta = targetTop - overlayTop - headerHeight;
                $overlay.scrollTop($overlay.scrollTop() + delta);
            };

            requestAnimationFrame(() => {
                scrollToTarget();
                setTimeout(scrollToTarget, 150);
            });
        }

        /**
         * Close Modal
         */
        closeModal() {
            const $overlay = this.$popupOverlay || $(`.bw-ps-popup-overlay[data-bw-ps-widget-id="${this.widgetId}"]`);

            $overlay.removeClass('active').fadeOut(300);
            $('body').css('overflow', '');
        }

        /**
         * Initialize Custom Cursor
         */
        initCustomCursor() {
            // Create cursor element if not exists
            if ($('.bw-ps-custom-cursor').length === 0) {
                this.customCursor = $('<div class="bw-ps-custom-cursor"></div>').appendTo('body');
            } else {
                this.customCursor = $('.bw-ps-custom-cursor');
            }

            const $wrapper = this.$wrapper;
            const $cursor = this.customCursor;
            const zoomText = this.config.cursorZoomText || 'ZOOM';
            const zoomTextSize = Number.isFinite(this.config.cursorZoomTextSize)
                ? `${this.config.cursorZoomTextSize}px`
                : '12px';
            const borderWidth = Number.isFinite(this.config.cursorBorderWidth)
                ? `${this.config.cursorBorderWidth}px`
                : '2px';
            const borderColor = this.config.cursorBorderColor || '#000';
            const parsedBlur = parseFloat(this.config.cursorBlur);
            const blurStrength = Number.isFinite(parsedBlur)
                ? `${parsedBlur}px`
                : '12px';
            const arrowColor = this.config.cursorArrowColor || '#000';
            const arrowSize = Number.isFinite(this.config.cursorArrowSize)
                ? `${this.config.cursorArrowSize}px`
                : '24px';
            const backgroundColor = this.config.cursorBackgroundColor || '#ffffff';
            const parsedOpacity = parseFloat(this.config.cursorBackgroundOpacity);
            const backgroundOpacity = Number.isFinite(parsedOpacity)
                ? Math.min(Math.max(parsedOpacity, 0), 1)
                : 0.6;
            const backgroundColorRgba = this.toRgba(backgroundColor, backgroundOpacity);
            const cursorState = {
                currentX: 0,
                currentY: 0,
                targetX: 0,
                targetY: 0,
                initialized: false,
                rafId: null
            };

            // Hide system cursor if enabled
            if (this.config.hideSystemCursor) {
                $wrapper.addClass('bw-ps-hide-cursor');
            }

            // Track mouse movement
            $wrapper.off('mousemove').on('mousemove', (e) => {
                cursorState.targetX = e.clientX;
                cursorState.targetY = e.clientY;

                if (!cursorState.initialized) {
                    cursorState.currentX = cursorState.targetX;
                    cursorState.currentY = cursorState.targetY;
                    cursorState.initialized = true;
                }
            });

            const animateCursor = () => {
                const ease = 0.18;
                cursorState.currentX += (cursorState.targetX - cursorState.currentX) * ease;
                cursorState.currentY += (cursorState.targetY - cursorState.currentY) * ease;
                $cursor.css({
                    left: `${cursorState.currentX}px`,
                    top: `${cursorState.currentY}px`
                });
                cursorState.rafId = requestAnimationFrame(animateCursor);
            };

            if (cursorState.rafId) {
                cancelAnimationFrame(cursorState.rafId);
            }
            $cursor.css({
                borderWidth,
                borderColor,
                color: arrowColor,
                '--bw-ps-cursor-bg': backgroundColorRgba,
                '--bw-site-blur': blurStrength,
                '--bw-ps-arrow-size': arrowSize,
                '--bw-ps-zoom-size': zoomTextSize
            });
            animateCursor();
            this.cursorState = cursorState;

            // Horizontal layout cursor states
            if (this.layoutMode === 'horizontal') {
                const $slider = $wrapper.find('.bw-ps-slider-horizontal');

                $slider.off('mouseenter', '.slick-slide.slick-active .bw-ps-image-clickable')
                    .on('mouseenter', '.slick-slide.slick-active .bw-ps-image-clickable', function () {
                        const $slide = $(this).closest('.slick-slide');
                        const $center = $slider.find('.slick-slide.slick-center');
                        const slideIndex = parseInt($slide.attr('data-slick-index'), 10);
                        const centerIndex = parseInt($center.attr('data-slick-index'), 10);

                        $cursor.removeClass('zoom prev next');

                        if ($slide.hasClass('slick-center')) {
                            $cursor.addClass('zoom active').text(zoomText);
                            return;
                        }

                        if (!isNaN(slideIndex) && !isNaN(centerIndex)) {
                            $cursor.addClass(slideIndex < centerIndex ? 'prev' : 'next');
                        } else {
                            $cursor.addClass('next');
                        }

                        $cursor.addClass('active').text('');
                    });

                $slider.off('mouseleave', '.slick-slide.slick-active .bw-ps-image-clickable')
                    .on('mouseleave', '.slick-slide.slick-active .bw-ps-image-clickable', () => {
                        $cursor.removeClass('active zoom prev next');
                        $cursor.text('');
                    });
            }

            // Vertical layout cursor states (desktop only)
            if (this.layoutMode === 'vertical') {
                const $mainImages = $wrapper.find('.bw-ps-main-images .bw-ps-image-clickable');

                $mainImages.off('mouseenter').on('mouseenter', () => {
                    $cursor.removeClass('prev next').addClass('zoom active');
                    $cursor.text(zoomText);
                });

                $mainImages.off('mouseleave').on('mouseleave', () => {
                    $cursor.removeClass('active zoom');
                    $cursor.text('');
                });
            }

            // Leave wrapper - hide cursor
            $wrapper.off('mouseleave.cursor').on('mouseleave.cursor', () => {
                $cursor.removeClass('active');
            });
        }

        /**
         * Convert hex color to rgba string
         */
        toRgba(color, alpha) {
            if (!color || typeof color !== 'string') {
                return `rgba(255, 255, 255, ${alpha})`;
            }

            const trimmed = color.trim();

            if (trimmed.startsWith('rgba')) {
                const values = trimmed.replace(/rgba\\(|\\)/g, '').split(',').map((val) => val.trim());
                if (values.length >= 3) {
                    const r = parseFloat(values[0]);
                    const g = parseFloat(values[1]);
                    const b = parseFloat(values[2]);
                    if ([r, g, b].every((value) => Number.isFinite(value))) {
                        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                    }
                }
            }

            if (trimmed.startsWith('rgb')) {
                const values = trimmed.replace(/rgb\\(|\\)/g, '').split(',').map((val) => val.trim());
                if (values.length >= 3) {
                    const r = parseFloat(values[0]);
                    const g = parseFloat(values[1]);
                    const b = parseFloat(values[2]);
                    if ([r, g, b].every((value) => Number.isFinite(value))) {
                        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                    }
                }
            }

            let normalized = trimmed.replace('#', '');
            if (normalized.length === 3) {
                normalized = normalized.split('').map((char) => char + char).join('');
            }

            if (normalized.length !== 6) {
                return `rgba(255, 255, 255, ${alpha})`;
            }

            const r = parseInt(normalized.slice(0, 2), 16);
            const g = parseInt(normalized.slice(2, 4), 16);
            const b = parseInt(normalized.slice(4, 6), 16);

            if ([r, g, b].some((value) => Number.isNaN(value))) {
                return `rgba(255, 255, 255, ${alpha})`;
            }

            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        /**
         * Check if device is touch-enabled
         */
        isTouchDevice() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        }

        /**
         * Destroy instance
         */
        destroy() {
            // Destroy all Slick instances
            this.slickInstances.forEach($instance => {
                if ($instance.hasClass('slick-initialized')) {
                    $instance.slick('unslick');
                }
            });

            // Remove event listeners
            $(document).off(`keydown.bwps-${this.widgetId}`);
            $(window).off(`resize.bwps-${this.widgetId}`);
            $(window).off(`resize.bwps-width-${this.widgetId}`);
            $(window).off(`resize.bwps-height-${this.widgetId}`);
            $(window).off(`resize.bwps-vertical-${this.widgetId}`);
            this.$wrapper.off();

            // Remove custom cursor
            if (this.customCursor) {
                this.customCursor.removeClass('active');
            }
            if (this.cursorState && this.cursorState.rafId) {
                cancelAnimationFrame(this.cursorState.rafId);
                this.cursorState.rafId = null;
            }

            this.initialized = false;
        }
    }

    /**
     * Initialize widgets on page load and in Elementor editor
     */
    function initWidgets() {
        $('.bw-ps-wrapper').each(function () {
            const $wrapper = $(this);

            // Avoid duplicate initialization
            if ($wrapper.data('bw-ps-instance')) {
                return;
            }

            const instance = new BWPresentationSlide(this);
            $wrapper.data('bw-ps-instance', instance);
        });
    }

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function () {
        initWidgets();
    });

    /**
     * Elementor frontend hooks
     */
    if (typeof elementorFrontend !== 'undefined') {
        // Initialize when Elementor preview is loaded
        $(window).on('elementor/frontend/init', function () {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-presentation-slide.default', function ($scope) {
                const $wrapper = $scope.find('.bw-ps-wrapper');
                if ($wrapper.length) {
                    // Destroy existing instance
                    const existingInstance = $wrapper.data('bw-ps-instance');
                    if (existingInstance) {
                        existingInstance.destroy();
                        $wrapper.removeData('bw-ps-instance');
                    }

                    // Create new instance
                    const instance = new BWPresentationSlide($wrapper[0]);
                    $wrapper.data('bw-ps-instance', instance);
                }
            });
        });
    }

    /**
     * Expose for debugging
     */
    window.BWPresentationSlide = BWPresentationSlide;

})(jQuery);
