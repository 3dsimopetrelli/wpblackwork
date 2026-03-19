(function ($) {
    'use strict';

    /**
     * BwSticky — JS-based sticky sidebar.
     *
     * Uses position:fixed + placeholder instead of CSS position:sticky.
     * This works regardless of overflow:hidden/auto on ancestor containers.
     *
     * @param {HTMLElement} el      The container element.
     * @param {number}      offset  Top offset in px when stuck.
     * @param {string}      devices 'desktop' | 'tablet' | 'all'
     */
    function BwSticky(el, offset, devices) {
        this.el       = el;
        this.$el      = $(el);
        this.offset   = offset;
        this.devices  = devices;

        this.$placeholder = null;
        this.stuck        = false;
        this.naturalTop   = 0;
        this._uid         = 'bwsticky_' + Math.random().toString(36).slice(2);

        this._scrollHandler = this._onScroll.bind(this);
        this._resizeHandler = this._debounce(this._onResize.bind(this), 150);
        this._loadHandler   = this._onLoad.bind(this);

        this._init();
    }

    BwSticky.prototype = {

        _init: function () {
            if (!this._isActiveDevice()) {
                return;
            }
            this._measure();
            $(window)
                .on('scroll.' + this._uid, this._scrollHandler)
                .on('resize.' + this._uid, this._resizeHandler)
                .on('load.' + this._uid, this._loadHandler);
            this._onScroll();
        },

        // True if sticky should be active at current viewport width.
        _isActiveDevice: function () {
            var w = window.innerWidth;
            if (this.devices === 'all')    { return true; }
            if (this.devices === 'tablet') { return w >= 768; }
            return w >= 1025; // desktop (default)
        },

        // Record the element's natural top position (from page top).
        _measure: function () {
            if (!this.stuck) {
                this.naturalTop = this.$el.offset().top;
            }
        },

        _onScroll: function () {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop + this.offset >= this.naturalTop) {
                this._stick();
            } else {
                this._unstick();
            }
        },

        _stick: function () {
            if (this.stuck) { return; }
            this.stuck = true;

            // Capture geometry before going fixed.
            var rect   = this.el.getBoundingClientRect();
            var width  = this.$el.outerWidth();
            var height = this.$el.outerHeight();

            // Insert placeholder so the parent layout doesn't collapse.
            this.$placeholder = $('<div class="bw-ess-placeholder" aria-hidden="true">').css({
                display:    'block',
                width:      width  + 'px',
                height:     height + 'px',
                visibility: 'hidden',
                pointerEvents: 'none',
                flexShrink: '0',
            });
            this.$el.before(this.$placeholder);

            this.$el.css({
                position: 'fixed',
                top:      this.offset + 'px',
                left:     rect.left  + 'px',
                width:    width      + 'px',
                zIndex:   999,
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
                position: '',
                top:      '',
                left:     '',
                width:    '',
                zIndex:   '',
            }).removeClass('bw-ess-stuck');
        },

        // Re-measure after all images/fonts are loaded (layout may shift).
        _onLoad: function () {
            if (!this.stuck) {
                this._measure();
                this._onScroll();
            }
        },

        _onResize: function () {
            this._unstick();
            if (!this._isActiveDevice()) {
                // Disable completely for this viewport — don't re-bind scroll.
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

    // Bootstrap via Elementor's frontend hook.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/container',
            function ($element) {
                // Never activate inside the Elementor editor canvas.
                if (elementorFrontend.isEditMode()) { return; }

                if ($element.data('bw-sticky') !== 'yes') { return; }

                var offset  = parseInt($element.data('bw-sticky-offset'), 10) || 0;
                var devices = $element.data('bw-sticky-on') || 'desktop';

                new BwSticky($element[0], offset, devices);
            }
        );
    });

})(jQuery);
