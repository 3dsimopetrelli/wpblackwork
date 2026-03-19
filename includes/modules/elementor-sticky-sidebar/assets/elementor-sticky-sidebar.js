(function ($) {
    'use strict';

    /**
     * BwSticky — JS-based sticky sidebar.
     *
     * Uses position:fixed + placeholder instead of CSS position:sticky.
     * This works regardless of overflow:hidden/auto on ancestor containers.
     *
     * When `bound` is true the element stops at the bottom edge of its parent:
     * instead of scrolling off the screen it "parks" at the parent's bottom by
     * reducing the fixed `top` value dynamically — no absolute positioning needed.
     *
     * @param {HTMLElement} el      The container element.
     * @param {number}      offset  Top offset in px when stuck.
     * @param {string}      devices 'desktop' | 'tablet' | 'all'
     * @param {boolean}     bound   Stop at parent bottom edge.
     */
    function BwSticky(el, offset, devices, bound) {
        this.el       = el;
        this.$el      = $(el);
        this.offset   = offset;
        this.devices  = devices;
        this.bound    = !!bound;

        this.$placeholder  = null;
        this.$parent       = null;
        this.stuck         = false;
        this.naturalTop    = 0;
        this.naturalHeight = 0;
        this._uid          = 'bwsticky_' + Math.random().toString(36).slice(2);

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
            if (this.bound) {
                // We need the *row-level* ancestor as the bound container, not the
                // immediate parent. The sticky element's direct parent is typically
                // the same height as the element itself (only child), which would
                // make the boundary trigger immediately.
                // In Elementor Flexbox Containers the row/section is the nearest
                // ancestor that carries the `.e-con.e-parent` classes.
                // Fall back to the immediate parent for non-Elementor markup.
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

            if (scrollTop + this.offset < this.naturalTop) {
                this._unstick();
                return;
            }

            // Ensure element is in the stuck (fixed) state.
            if (!this.stuck) {
                this._stick();
            }

            // Bottom-boundary: adjust `top` so the element parks at parent bottom.
            if (this.bound && this.stuck && this.$parent) {
                var parentBottom = this.$parent.offset().top + this.$parent.outerHeight();
                var elHeight     = this.naturalHeight || this.$el.outerHeight();
                // How far from the viewport top the bottom of the element would be.
                var maxTop = parentBottom - elHeight - scrollTop;
                // Clamp between 0 and the configured offset.
                var top = maxTop < this.offset ? Math.max(maxTop, 0) : this.offset;
                this.$el.css('top', top + 'px');
            }
        },

        _stick: function () {
            if (this.stuck) { return; }
            this.stuck = true;

            // Capture geometry before going fixed.
            var rect   = this.el.getBoundingClientRect();
            var width  = this.$el.outerWidth();
            var height = this.$el.outerHeight();

            this.naturalHeight = height; // cache for bound calculation

            // Insert placeholder so the parent layout doesn't collapse.
            this.$placeholder = $('<div class="bw-ess-placeholder" aria-hidden="true">').css({
                display:       'block',
                width:         width  + 'px',
                height:        height + 'px',
                visibility:    'hidden',
                pointerEvents: 'none',
                flexShrink:    '0',
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

    // Shared init helper — guards against double-initialization.
    function bwInitStickyElement($element) {
        if ($element.data('bw-sticky-initialized')) { return; }
        $element.data('bw-sticky-initialized', true);

        var offset  = parseInt($element.data('bw-sticky-offset'), 10) || 0;
        var devices = $element.data('bw-sticky-on')    || 'desktop';
        var bound   = $element.data('bw-sticky-bound') === 'yes';

        new BwSticky($element[0], offset, devices, bound);
    }

    // Primary: Elementor frontend hook (fires when each container is ready).
    $(window).on('elementor/frontend/init', function () {
        if (
            typeof elementorFrontend === 'undefined' ||
            typeof elementorFrontend.hooks === 'undefined'
        ) { return; }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/container',
            function ($element) {
                // Never activate inside the Elementor editor canvas.
                if (
                    typeof elementorFrontend.isEditMode === 'function' &&
                    elementorFrontend.isEditMode()
                ) { return; }

                if ($element.data('bw-sticky') !== 'yes') { return; }

                bwInitStickyElement($element);
            }
        );
    });

    // Fallback: scan the DOM directly on document-ready.
    // Covers cases where Elementor's frontend JS crashes or the hook never fires.
    $(document).ready(function () {
        // Small delay so Elementor has a chance to fire its hooks first.
        setTimeout(function () {
            $('[data-bw-sticky="yes"]').each(function () {
                bwInitStickyElement($(this));
            });
        }, 300);
    });

})(jQuery);
