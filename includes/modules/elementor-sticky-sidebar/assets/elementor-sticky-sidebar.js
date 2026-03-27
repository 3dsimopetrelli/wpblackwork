(function ($) {
    'use strict';

    /**
     * BwSticky — JS-based sticky sidebar.
     *
     * Always uses position:fixed + placeholder. The "stay within column"
     * bound is implemented by setting a negative `top` value when the
     * parent row has scrolled above the viewport: the element tracks the
     * row's bottom edge and disappears off the top of the screen, exactly
     * as if it were position:absolute inside the row — but without any
     * DOM teleportation, so CSS inheritance from parent containers is
     * fully preserved and overflow:hidden ancestors cannot clip it.
     *
     * @param {HTMLElement} el      The container element.
     * @param {number}      offset  Top offset in px when stuck.
     * @param {string}      devices 'desktop' | 'tablet' | 'all'
     * @param {boolean}     bound   Stop at parent bottom edge.
     */
    function BwSticky(el, offset, devices, bound) {
        this.el      = el;
        this.$el     = $(el);
        this.offset  = offset;
        this.devices = devices;
        this.bound   = !!bound;

        this.$placeholder = null;
        this.$parent      = null;
        this.stuck        = false;

        // Natural (pre-sticky) geometry.
        this.naturalTop    = 0;
        this.naturalLeft   = 0;
        this.naturalWidth  = 0;
        this.naturalHeight = 0;

        this._uid = 'bwsticky_' + Math.random().toString(36).slice(2);
        this._resizeObserver = null;
        this._syncRaf = 0;

        this._scrollHandler = this._onScroll.bind(this);
        this._resizeHandler = this._debounce(this._onResize.bind(this), 150);
        this._loadHandler   = this._onLoad.bind(this);
        this._refreshHandler = this._requestDynamicSync.bind(this);

        this._init();
    }

    BwSticky.prototype = {

        _init: function () {
            if (!this._isActiveDevice()) { return; }

            if (this.bound) {
                // Row container = shared parent of both columns.
                var $row = this.$el.closest('.e-con.e-parent, .elementor-section');
                this.$parent = $row.length ? $row : this.$el.parent();
            }

            this._measure();
            $(window)
                .on('scroll.' + this._uid, this._scrollHandler)
                .on('resize.' + this._uid, this._resizeHandler)
                .on('load.' + this._uid, this._loadHandler)
                .on('bw:sticky-sidebar:refresh.' + this._uid, this._refreshHandler);

            this._initObservers();
            this._onScroll();
        },

        _isActiveDevice: function () {
            var w = window.innerWidth;
            if (this.devices === 'all')    { return true; }
            if (this.devices === 'tablet') { return w >= 768; }
            return w >= 1025;
        },

        _measure: function () {
            if (this.stuck) { return; }
            var rect           = this.el.getBoundingClientRect();
            var scrollTop      = window.pageYOffset || 0;
            var scrollLeft     = window.pageXOffset || 0;
            this.naturalTop    = rect.top  + scrollTop;
            this.naturalWidth  = rect.width;
            this.naturalHeight = rect.height;
        },

        _initObservers: function () {
            var self = this;

            if (typeof ResizeObserver === 'undefined') {
                return;
            }

            this._resizeObserver = new ResizeObserver(function () {
                self._requestDynamicSync();
            });

            this._resizeObserver.observe(this.el);
        },

        _requestDynamicSync: function () {
            var self = this;

            if (this._syncRaf) {
                return;
            }

            this._syncRaf = window.requestAnimationFrame(function () {
                self._syncRaf = 0;
                self._syncDynamicGeometry();
            });
        },

        _syncDynamicGeometry: function () {
            if (!this._isActiveDevice()) {
                return;
            }

            var rect      = this.el.getBoundingClientRect();
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            this.naturalWidth  = rect.width;
            this.naturalHeight = rect.height;

            if (!this.stuck) {
                this.naturalTop = rect.top + scrollTop;
                return;
            }

            if (this.$placeholder) {
                this.$placeholder.css({
                    flexBasis: this.naturalWidth + 'px',
                    width:     this.naturalWidth + 'px',
                    height:    this.naturalHeight + 'px',
                });
            }

            this.$el.css({
                width: this.naturalWidth + 'px',
            });

            this._onScroll();
        },

        _onScroll: function () {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop + this.offset < this.naturalTop) {
                this._unstick();
                return;
            }

            if (!this.stuck) {
                this._stick();
            }

            // Bound: keep the element within the parent row's height.
            // When maxTop >= offset → normal sticky at `offset`.
            // When 0 <= maxTop < offset → element slides toward row bottom.
            // When maxTop < 0 → top is negative: element is above the viewport,
            //   scrolling "with the page" without any DOM teleportation needed.
            if (this.bound && this.$parent) {
                var parentBottom = this.$parent.offset().top + this.$parent.outerHeight();
                var maxTop       = parentBottom - this.naturalHeight - scrollTop;
                this.$el.css('top', (maxTop < this.offset ? maxTop : this.offset) + 'px');
            }
        },

        _stick: function () {
            if (this.stuck) { return; }
            this.stuck = true;

            // Re-measure at the exact moment of sticking for subpixel accuracy.
            // The element is still in normal flow here, so getBoundingClientRect
            // returns its actual rendered geometry.
            var rect = this.el.getBoundingClientRect();
            this.naturalWidth  = rect.width;
            this.naturalHeight = rect.height;

            // Copy flex-item properties from the original element so the parent
            // flex container does not reflow and redistribute column widths.
            var cs = window.getComputedStyle(this.el);
            this.$placeholder = $('<div class="bw-ess-placeholder" aria-hidden="true">').css({
                display:       cs.display,
                flexGrow:      cs.flexGrow,
                flexShrink:    cs.flexShrink,
                flexBasis:     this.naturalWidth + 'px',
                alignSelf:     cs.alignSelf,
                width:         this.naturalWidth  + 'px',
                height:        this.naturalHeight + 'px',
                visibility:    'hidden',
                pointerEvents: 'none',
            });
            this.$el.before(this.$placeholder);

            // Freeze computed padding values as explicit px to prevent percentage
            // paddings from recalculating against the viewport (which is the
            // containing block for position:fixed elements). Without this, a 2%
            // padding jumps from ~10px (relative to 501px parent) to ~29px
            // (relative to 1440px viewport), visibly shrinking the content area.
            this.$el.css({
                position:      'fixed',
                top:           this.offset + 'px',
                left:          rect.left + 'px',
                width:         this.naturalWidth + 'px',
                paddingTop:    cs.paddingTop,
                paddingRight:  cs.paddingRight,
                paddingBottom: cs.paddingBottom,
                paddingLeft:   cs.paddingLeft,
                zIndex:        999,
            }).addClass('bw-ess-stuck');
        },

        _unstick: function () {
            if (!this.stuck) { return; }
            this.stuck = false;

            if (this.$placeholder) {
                this.$placeholder.remove();
                this.$placeholder = null;
            }

            this.$el.css({
                position:      '',
                top:           '',
                left:          '',
                width:         '',
                paddingTop:    '',
                paddingRight:  '',
                paddingBottom: '',
                paddingLeft:   '',
                zIndex:        '',
            }).removeClass('bw-ess-stuck');
        },

        _onLoad: function () {
            if (!this.stuck) {
                this._measure();
                this._onScroll();
            }
        },

        _onResize: function () {
            this._unstick();
            if (!this._isActiveDevice()) {
                $(window).off('scroll.' + this._uid);
                return;
            }
            this._measure();
            this._onScroll();
        },

        _debounce: function (fn, wait) {
            var timer;
            return function () {
                clearTimeout(timer);
                timer = setTimeout(fn, wait);
            };
        },

        destroy: function () {
            $(window).off('.' + this._uid);
            if (this._resizeObserver) {
                this._resizeObserver.disconnect();
                this._resizeObserver = null;
            }
            if (this._syncRaf) {
                window.cancelAnimationFrame(this._syncRaf);
                this._syncRaf = 0;
            }
            this._unstick();
        },
    };

    function bwInitStickyElement($element) {
        if ($element.data('bw-sticky-initialized')) { return; }
        $element.data('bw-sticky-initialized', true);

        var offset  = parseInt($element.data('bw-sticky-offset'), 10) || 0;
        var devices = $element.data('bw-sticky-on')    || 'desktop';
        var bound   = $element.data('bw-sticky-bound') === 'yes';

        new BwSticky($element[0], offset, devices, bound);
    }

    $(window).on('elementor/frontend/init', function () {
        if (
            typeof elementorFrontend === 'undefined' ||
            typeof elementorFrontend.hooks === 'undefined'
        ) { return; }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/container',
            function ($element) {
                if (
                    typeof elementorFrontend.isEditMode === 'function' &&
                    elementorFrontend.isEditMode()
                ) { return; }

                if ($element.data('bw-sticky') !== 'yes') { return; }

                bwInitStickyElement($element);
            }
        );
    });

    $(document).ready(function () {
        setTimeout(function () {
            $('[data-bw-sticky="yes"]').each(function () {
                bwInitStickyElement($(this));
            });
        }, 300);
    });

})(jQuery);
