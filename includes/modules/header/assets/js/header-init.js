(function () {
    'use strict';

    if (document.documentElement) {
        document.documentElement.classList.add('bw-header-js');
    }

    // Dark Zone state
    var isOnDarkZone = false;

    /**
     * Read admin-configured values from bwHeaderConfig (set via wp_localize_script).
     */
    function getConfig() {
        var cfg = window.bwHeaderConfig || {};
        var smart = cfg.smartHeader || {};
        return {
            breakpoint: parseInt(cfg.breakpoint, 10) || 1024,
            smartScroll: !!cfg.smartScroll,
            scrollDownThreshold: parseInt(smart.scrollDownThreshold, 10) || 100,
            scrollUpThreshold: parseInt(smart.scrollUpThreshold, 10) || 0,
            scrollDelta: Math.max(1, parseInt(smart.scrollDelta, 10) || 1),
        };
    }

    function getBreakpoint() {
        return getConfig().breakpoint;
    }

    function ensureHeaderInBody() {
        var header = document.querySelector('.bw-custom-header');
        if (!header || !document.body) {
            return;
        }
        if (header.parentNode !== document.body) {
            var spacer = document.querySelector('.bw-header-spacer');
            document.body.insertBefore(header, document.body.firstChild);
            // Keep spacer right after header in the DOM.
            if (spacer) {
                header.after(spacer);
            }
        }
    }

    function applyStateClass() {
        var root = document.querySelector('.bw-custom-header');
        if (!root) {
            return;
        }

        var bp = getBreakpoint();
        if (window.matchMedia('(max-width: ' + bp + 'px)').matches) {
            root.classList.add('is-mobile');
            root.classList.remove('is-desktop');
        } else {
            root.classList.add('is-desktop');
            root.classList.remove('is-mobile');
        }
    }

    function getAdminBarHeight() {
        var adminBar = document.getElementById('wpadminbar');
        return adminBar ? (adminBar.offsetHeight || 0) : 0;
    }

    /* ========================================================================
       ANIMATED BANNER SUPPORT
       ======================================================================== */

    function getAnimatedBannerHeight(header) {
        var banner = document.querySelector('.bw-animated-banner');
        if (!banner || banner.offsetParent === null) {
            document.documentElement.style.setProperty('--animated-banner-height', '0px');
            return { height: 0, inside: false };
        }

        var height = banner.offsetHeight || 0;
        var inside = header && header.contains(banner);

        document.documentElement.style.setProperty('--animated-banner-height', height + 'px');

        return { height: height, inside: inside };
    }

    /* ========================================================================
       DARK ZONE DETECTION
       ======================================================================== */

    function parseColor(color) {
        if (!color || color === 'transparent' || color === 'rgba(0, 0, 0, 0)') return null;
        var rgbaMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
        if (rgbaMatch) {
            return {
                r: parseInt(rgbaMatch[1], 10),
                g: parseInt(rgbaMatch[2], 10),
                b: parseInt(rgbaMatch[3], 10),
                a: rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1
            };
        }
        var hexMatch = color.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (hexMatch) {
            var hex = hexMatch[1];
            if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            return {
                r: parseInt(hex.substr(0, 2), 16),
                g: parseInt(hex.substr(2, 2), 16),
                b: parseInt(hex.substr(4, 2), 16),
                a: 1
            };
        }
        return null;
    }

    function getColorBrightness(color) {
        if (!color) return 255;
        return (color.r * 299 + color.g * 587 + color.b * 114) / 1000;
    }

    function isColorDark(color, threshold) {
        if (!color) return false;
        if (typeof threshold === 'undefined') threshold = 128;
        if (color.a < 0.5) return false;
        return getColorBrightness(color) < threshold;
    }

    function getEffectiveBackgroundColor(element) {
        var current = element;
        var maxDepth = 10;
        var depth = 0;
        while (current && depth < maxDepth) {
            var computedStyle = window.getComputedStyle(current);
            var bgColor = computedStyle.backgroundColor;
            var parsed = parseColor(bgColor);
            if (parsed && parsed.a > 0.5) return parsed;
            current = current.parentElement;
            depth++;
        }
        return { r: 255, g: 255, b: 255, a: 1 };
    }

    /**
     * Walk every ancestor of el (up to body) and return true as soon as one
     * of them is effectively dark.  We walk the full chain so that nested
     * Elementor containers (.e-con inside .e-con-boxed) don't cause us to
     * stop at the wrong (inner, transparent) container.
     *
     * Per-ancestor checks:
     *   1. background-color is dark
     *   2. Direct overlay child div is dark (Elementor / Gutenberg pattern)
     *   3. Has background-image AND child headings are light-coloured
     */
    function isSectionDark(el) {
        var current = el;
        var depth = 0;

        while (current && current !== document.documentElement && depth < 15) {
            if (current.classList && current.classList.contains('bw-custom-header')) break;

            var st = window.getComputedStyle(current);

            // 1. Solid dark background-color
            var bg = parseColor(st.backgroundColor);
            if (bg && bg.a >= 0.5 && isColorDark(bg, 128)) return true;

            // 2. Elementor / Gutenberg overlay child
            var child = current.firstElementChild;
            while (child) {
                if (
                    child.classList.contains('elementor-background-overlay') ||
                    child.classList.contains('wp-block-cover__background') ||
                    child.classList.contains('wp-block-cover__gradient-background')
                ) {
                    var oBg = parseColor(window.getComputedStyle(child).backgroundColor);
                    if (oBg && oBg.a > 0.25 && isColorDark(oBg, 128)) return true;
                    break;
                }
                child = child.nextElementSibling;
            }

            // 3. Has background-image: check heading text colour as heuristic
            if (st.backgroundImage && st.backgroundImage !== 'none') {
                var headings = current.querySelectorAll('h1, h2, h3, h4');
                for (var i = 0; i < Math.min(headings.length, 6); i++) {
                    if (!headings[i].offsetParent) continue;
                    var tc = parseColor(window.getComputedStyle(headings[i]).color);
                    if (tc && getColorBrightness(tc) > 180) return true;
                }
            }

            current = current.parentElement;
            depth++;
        }

        return false;
    }

    /**
     * Probe elements behind the header (pointer-events:none trick, at the
     * header vertical centre) and just below it.  Manual .smart-header-dark-zone
     * elements always take priority.
     */
    function checkDarkZoneOverlap(header) {
        if (!header) return;
        var headerRect = header.getBoundingClientRect();
        var shouldBeOnDark = false;

        // — Manual zones (always take priority) —
        var manualZones = document.querySelectorAll('.smart-header-dark-zone');
        for (var m = 0; m < manualZones.length; m++) {
            var mRect = manualZones[m].getBoundingClientRect();
            if (mRect.top < headerRect.bottom && mRect.bottom > headerRect.top) {
                shouldBeOnDark = true;
                break;
            }
        }

        if (!shouldBeOnDark) {
            var probeXs = [
                headerRect.left + headerRect.width * 0.15,
                headerRect.left + headerRect.width * 0.50,
                headerRect.left + headerRect.width * 0.85,
            ];

            // Primary: probe BEHIND the header at its vertical centre.
            // pointer-events:none makes elementFromPoint see through the header.
            var oldPE = header.style.pointerEvents;
            header.style.pointerEvents = 'none';
            var probeY = headerRect.top + headerRect.height * 0.5;
            for (var i = 0; i < probeXs.length; i++) {
                var el = document.elementFromPoint(probeXs[i], probeY);
                if (el && !el.closest('.bw-custom-header') && isSectionDark(el)) {
                    shouldBeOnDark = true;
                    break;
                }
            }
            header.style.pointerEvents = oldPE;

            // Fallback: probe just below the header (catches sections that
            // start right below, e.g. after scroll).
            if (!shouldBeOnDark) {
                var belowY = headerRect.bottom + 2;
                for (var j = 0; j < probeXs.length; j++) {
                    var elB = document.elementFromPoint(probeXs[j], belowY);
                    if (elB && !elB.closest('.bw-custom-header') && isSectionDark(elB)) {
                        shouldBeOnDark = true;
                        break;
                    }
                }
            }
        }

        if (shouldBeOnDark !== isOnDarkZone) {
            isOnDarkZone = shouldBeOnDark;
            header.classList.toggle('bw-header-on-dark', shouldBeOnDark);
        }
    }

    function initDarkZoneDetection(header) {
        checkDarkZoneOverlap(header);
    }

    /* ========================================================================
       STICKY HEADER LOGIC
       ======================================================================== */

    function initStickyHeader() {
        var header = document.querySelector('.bw-custom-header[data-smart-scroll="yes"]');
        if (!header) return;

        initDarkZoneDetection(header);

        var cfg = getConfig();
        var docEl = document.documentElement;
        var body = document.body;
        var lastScrollTop = window.pageYOffset || 0;
        var ticking = false;
        var isHidden = false;
        var scrollDelta = cfg.scrollDelta;
        var scrollDownThreshold = cfg.scrollDownThreshold;
        var scrollUpThreshold = cfg.scrollUpThreshold;

        // Make header always-fixed via CSS; spacer fills the flow gap.
        // Because position never toggles, no layout jolt is possible.
        body.classList.add('bw-has-sticky-header');

        function recalcOffsets() {
            var adminBarHeight = getAdminBarHeight();
            var headerHeight = header.offsetHeight || 0;
            getAnimatedBannerHeight(header);

            docEl.style.setProperty('--bw-header-top-offset', adminBarHeight + 'px');
            docEl.style.setProperty('--bw-header-body-padding', headerHeight + 'px');
        }

        function showHeader() {
            if (!isHidden) return;
            header.classList.remove('bw-header-hidden');
            header.classList.add('bw-header-visible');
            isHidden = false;
        }

        function hideHeader() {
            if (isHidden) return;
            header.classList.add('bw-header-hidden');
            header.classList.remove('bw-header-visible');
            isHidden = true;
        }

        function resetHeader() {
            // At the very top: remove all scroll classes instantly (no animation).
            header.style.transition = 'none';
            header.classList.remove('bw-header-hidden');
            header.classList.remove('bw-header-visible');
            void header.offsetHeight;
            header.style.transition = '';
            isHidden = false;
        }

        function onScroll() {
            var st = window.pageYOffset || 0;
            var headerHeight = header.offsetHeight || 0;

            checkDarkZoneOverlap(header);

            var activationPoint = Math.max(headerHeight, scrollDownThreshold);

            // Toggle scrolled state for background color changes (admin-configured).
            if (st > 2) {
                header.classList.add('bw-header-scrolled');
            } else {
                header.classList.remove('bw-header-scrolled');
            }

            if (st <= 2) {
                // At the very top: header is naturally visible.
                resetHeader();
            } else if (st > activationPoint) {
                // Past the activation threshold: smart show/hide.
                var delta = Math.abs(lastScrollTop - st);
                if (delta > scrollDelta) {
                    if (st > lastScrollTop) {
                        // Scrolling DOWN → hide.
                        hideHeader();
                    } else {
                        // Scrolling UP → show.
                        var upDelta = lastScrollTop - st;
                        if (upDelta >= scrollUpThreshold && st + window.innerHeight < body.scrollHeight) {
                            showHeader();
                        }
                    }
                }
            }

            lastScrollTop = st;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    onScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        window.addEventListener('resize', function () {
            recalcOffsets();
            applyStateClass();
            checkDarkZoneOverlap(header);
        });

        recalcOffsets();
    }

    function boot() {
        ensureHeaderInBody();
        applyStateClass();
        initStickyHeader();

        var header = document.querySelector('.bw-custom-header');
        if (header) {
            // Se smart scroll non è attivo, dark zone detection non è stata
            // inizializzata — la attiviamo qui con il proprio scroll listener.
            if (header.getAttribute('data-smart-scroll') !== 'yes') {
                initDarkZoneDetection(header);
                var nonStickyTicking = false;
                window.addEventListener('scroll', function () {
                    if (!nonStickyTicking) {
                        window.requestAnimationFrame(function () {
                            checkDarkZoneOverlap(header);
                            nonStickyTicking = false;
                        });
                        nonStickyTicking = true;
                    }
                }, { passive: true });
            }

            header.classList.remove('bw-header-preload');
            header.style.opacity = '1';
            header.style.visibility = 'visible';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();
