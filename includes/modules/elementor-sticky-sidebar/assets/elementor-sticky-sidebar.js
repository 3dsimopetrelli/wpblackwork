(function ($) {
    'use strict';

    /**
     * BwSticky — JS-based sticky sidebar.
     *
     * Three internal states:
     *  'none'     — element is in normal document flow
     *  'fixed'    — position:fixed, top adjusted dynamically
     *  'absolute' — element teleported to <body> with position:absolute
     *               at document-relative coordinates tracking the row's
     *               bottom edge. This sidesteps overflow:hidden and any
     *               positioned ancestors between the element and the row.
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
        this._boundState  = 'none'; // 'none' | 'fixed' | 'absolute'

        // Natural (pre-sticky) geometry.
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
                // Element can't fit above the row boundary → teleport to body,
                // anchored at document-relative bottom of the parent row.
                this._goAbsolute(parentBottom);
            } else {
                // Normal fixed: reduce top as we approach the boundary.
                this._goFixed(maxTop < this.offset ? maxTop : this.offset);
            }
        },

        // ── Enter sticky mode ───────────────────────────────────────────────────
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

        // ── Switch to / stay in fixed mode ─────────────────────────────────────
        _goFixed: function (top) {
            if (this._boundState === 'fixed') {
                this.$el.css('top', top + 'px');
                return;
            }

            // Returning from absolute (teleported) mode → move back into the DOM.
            this._boundState = 'fixed';
            this.$placeholder.before(this.$el);

            var viewportLeft = this.naturalLeft - (window.pageXOffset || 0);

            this.$el.css({
                position: 'fixed',
                top:      top + 'px',
                left:     viewportLeft + 'px',
                width:    this.naturalWidth + 'px',
                bottom:   '',
            });
        },

        // ── Switch to absolute-in-body mode ────────────────────────────────────
        _goAbsolute: function (parentBottom) {
            if (this._boundState === 'absolute') { return; }
            this._boundState = 'absolute';

            // Teleport to <body> so that overflow:hidden / positioned ancestors
            // between the element and the row container don't clip or misplace it.
            // In body, position:absolute coordinates are document-relative.
            var docTop = (parentBottom !== undefined ? parentBottom : this.$parent.offset().top + this.$parent.outerHeight()) - this.naturalHeight;

            $('body').append(this.$el);

            this.$el.css({
                position: 'absolute',
                top:      docTop + 'px',
                left:     this.naturalLeft + 'px',
                width:    this.naturalWidth + 'px',
                bottom:   '',
            });
        },

        // ── Leave sticky mode ───────────────────────────────────────────────────
        _unstick: function () {
            if (!this.stuck) { return; }
            this.stuck       = false;

            // If element was teleported to body, restore it before the placeholder.
            if (this._boundState === 'absolute' && this.$placeholder && this.$placeholder.parent().length) {
                this.$placeholder.before(this.$el);
            }

            this._boundState = 'none';

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
