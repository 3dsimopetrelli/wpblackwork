(function ($) {
    'use strict';

    /**
     * BwSticky — JS-based sticky sidebar.
     *
     * Three internal states:
     *  'none'     — element is in normal document flow
     *  'fixed'    — position:fixed, top adjusted dynamically
     *  'absolute' — position:absolute; bottom:0 inside the parent row
     *               (activated when the element is too tall to fit in the
     *                remaining viewport space above the row boundary)
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

        this.$placeholder     = null;
        this.$parent          = null;
        this.stuck            = false;
        this._boundState      = 'none'; // 'none' | 'fixed' | 'absolute'
        this._parentWasStatic = false;

        // Natural (pre-sticky) geometry — measured before going fixed.
        this.naturalTop    = 0;
        this.naturalLeft   = 0;
        this.naturalWidth  = 0;
        this.naturalHeight = 0;

        this._uid = 'bwsticky_' + Math.random().toString(36).slice(2);

        this._scrollHandler = this._onScroll.bind(this);
        this._resizeHandler = this._debounce(this._onResize.bind(this), 150);
        this._loadHandler   = this._onLoad.bind(this);

        this._init();
    }

    BwSticky.prototype = {

        _init: function () {
            if (!this._isActiveDevice()) { return; }

            if (this.bound) {
                // Traverse up to the Elementor row container, which is the shared
                // parent of both columns and carries the full row height.
                // The direct parent is usually the same height as the element itself.
                var $row = this.$el.closest('.e-con.e-parent, .elementor-section');
                this.$parent = $row.length ? $row : this.$el.parent();
            }

            this._measure();
            $(window)
                .on('scroll.' + this._uid, this._scrollHandler)
                .on('resize.' + this._uid, this._resizeHandler)
                .on('load.' + this._uid, this._loadHandler);
            this._onScroll();
        },

        _isActiveDevice: function () {
            var w = window.innerWidth;
            if (this.devices === 'all')    { return true; }
            if (this.devices === 'tablet') { return w >= 768; }
            return w >= 1025;
        },

        // Capture geometry while element is still in normal flow.
        _measure: function () {
            if (this.stuck) { return; }
            var off            = this.$el.offset();
            this.naturalTop    = off.top;
            this.naturalLeft   = off.left;
            this.naturalWidth  = this.$el.outerWidth();
            this.naturalHeight = this.$el.outerHeight();
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

            if (!this.bound || !this.$parent) { return; }

            var parentBottom = this.$parent.offset().top + this.$parent.outerHeight();
            var maxTop       = parentBottom - this.naturalHeight - scrollTop;

            if (maxTop < 0) {
                // Element is too tall to fit — park it at row bottom via absolute.
                this._goAbsolute();
            } else {
                // Fixed mode: reduce top as we approach the row bottom.
                this._goFixed(maxTop < this.offset ? maxTop : this.offset);
            }
        },

        _stick: function () {
            if (this.stuck) { return; }
            this.stuck       = true;
            this._boundState = 'fixed';

            this.$placeholder = $('<div class="bw-ess-placeholder" aria-hidden="true">').css({
                display:       'block',
                width:         this.naturalWidth  + 'px',
                height:        this.naturalHeight + 'px',
                visibility:    'hidden',
                pointerEvents: 'none',
                flexShrink:    '0',
            });
            this.$el.before(this.$placeholder);

            // naturalLeft is document-relative; convert to viewport-relative for fixed.
            var viewportLeft = this.naturalLeft - (window.pageXOffset || 0);

            this.$el.css({
                position: 'fixed',
                top:      this.offset + 'px',
                left:     viewportLeft + 'px',
                width:    this.naturalWidth + 'px',
                bottom:   '',
                zIndex:   999,
            }).addClass('bw-ess-stuck');
        },

        _goFixed: function (top) {
            if (this._boundState === 'fixed') {
                this.$el.css('top', top + 'px');
                return;
            }

            // Returning from absolute mode.
            this._boundState = 'fixed';

            if (this._parentWasStatic && this.$parent) {
                this.$parent.css('position', '');
                this._parentWasStatic = false;
            }

            var viewportLeft = this.naturalLeft - (window.pageXOffset || 0);

            this.$el.css({
                position: 'fixed',
                top:      top + 'px',
                left:     viewportLeft + 'px',
                width:    this.naturalWidth + 'px',
                bottom:   '',
            });
        },

        _goAbsolute: function () {
            if (this._boundState === 'absolute') { return; }
            this._boundState = 'absolute';

            // Parent row needs position:relative.
            if (this.$parent.css('position') === 'static') {
                this.$parent.css('position', 'relative');
                this._parentWasStatic = true;
            }

            // Left offset relative to parent padding box.
            var parentBorderLeft = parseInt(this.$parent.css('border-left-width'), 10) || 0;
            var leftInParent     = this.naturalLeft - this.$parent.offset().left - parentBorderLeft;

            this.$el.css({
                position: 'absolute',
                top:      'auto',
                bottom:   '0',
                left:     leftInParent + 'px',
                width:    this.naturalWidth + 'px',
            });
        },

        _unstick: function () {
            if (!this.stuck) { return; }
            this.stuck       = false;
            this._boundState = 'none';

            if (this._parentWasStatic && this.$parent) {
                this.$parent.css('position', '');
                this._parentWasStatic = false;
            }

            if (this.$placeholder) {
                this.$placeholder.remove();
                this.$placeholder = null;
            }

            this.$el.css({
                position: '',
                top:      '',
                left:     '',
                bottom:   '',
                width:    '',
                zIndex:   '',
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
