(function () {
    'use strict';

    if (document.documentElement) {
        document.documentElement.classList.add('bw-header-js');
    }

    // Global definition for Dark Zone vars within this scope
    var darkZoneObserver = null;
    var darkZones = [];
    var isOnDarkZone = false;
    var currentDarkZone = null;

    function getBreakpoint() {
        if (typeof window.bwHeaderConfig === 'undefined') {
            return 1024;
        }

        var bp = parseInt(window.bwHeaderConfig.breakpoint, 10);
        if (!bp || Number.isNaN(bp)) {
            return 1024;
        }

        return bp;
    }

    function ensureHeaderInBody() {
        var header = document.querySelector('.bw-custom-header');
        if (!header || !document.body) {
            return;
        }
        if (header.parentNode !== document.body) {
            document.body.insertBefore(header, document.body.firstChild);
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
       DARK ZONE DETECTION HELPERS
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

    function autoDetectDarkSections() {
        var sections = [];
        var selectors = ['.elementor-section', 'section', '[data-elementor-type]', '.wp-block-cover', 'main > section', 'main > div'];
        var allSections = document.querySelectorAll(selectors.join(', '));
        allSections.forEach(function (section) {
            if (section.classList.contains('bw-custom-header') || section.closest('.bw-custom-header')) return;
            var rect = section.getBoundingClientRect();
            if (rect.height < 100) return;
            var bgColor = getEffectiveBackgroundColor(section);
            if (isColorDark(bgColor, 128)) sections.push(section);
        });
        return sections;
    }

    function checkDarkZoneOverlap(header) {
        if (!header) return;
        var headerRect = header.getBoundingClientRect();
        var headerHeight = headerRect.height;
        // Optimization: if header is hidden (translateY -100%), technically it's not "on" anything, 
        // but for state consistency we might want to keep the last state or check overlap effectively.
        // Assuming we check based on where it *would* be.
        var headerTop = headerRect.top > 0 ? headerRect.top : 0; // Simplified for sticky top
        var headerBottom = headerTop + headerHeight;

        var overlappingZone = null;
        var maxOverlap = 0;

        darkZones.forEach(function (zone) {
            var zoneRect = zone.getBoundingClientRect();
            // Simple overlap check
            if (zoneRect.top < headerBottom && zoneRect.bottom > headerTop) {
                var overlapHeight = Math.min(headerBottom, zoneRect.bottom) - Math.max(headerTop, zoneRect.top);
                var pct = (overlapHeight / headerHeight) * 100;
                if (pct > maxOverlap) {
                    maxOverlap = pct;
                    overlappingZone = zone;
                }
            }
        });

        var shouldBeOnDark = overlappingZone !== null && maxOverlap >= 30;
        if (shouldBeOnDark !== isOnDarkZone) {
            isOnDarkZone = shouldBeOnDark;
            currentDarkZone = overlappingZone;
            if (isOnDarkZone) {
                header.classList.add('bw-header-on-dark');
                // Legacy support if needed, but trying to keep clean
            } else {
                header.classList.remove('bw-header-on-dark');
            }
        }
    }

    function initDarkZoneDetection(header) {
        var manualDarkZones = Array.from(document.querySelectorAll('.smart-header-dark-zone'));
        var autoDarkZones = autoDetectDarkSections();
        var allDarkZones = manualDarkZones.concat(autoDarkZones.filter(function (z) { return !manualDarkZones.includes(z) }));
        darkZones = allDarkZones;

        if ('IntersectionObserver' in window && darkZones.length > 0) {
            darkZoneObserver = new IntersectionObserver(function (entries) {
                // When any dark zone enters/exits view, re-check overlap
                checkDarkZoneOverlap(header);
            }, { root: null, margin: '0px' });
            darkZones.forEach(function (zone) { darkZoneObserver.observe(zone); });
        }
        checkDarkZoneOverlap(header);
    }

    /* ========================================================================
       NEW SIMPLIFIED STICKY LOGIC
       ======================================================================== */

    function initStickyHeader() {
        var header = document.querySelector('.bw-custom-header[data-smart-scroll="yes"]');
        if (!header) return;

        initDarkZoneDetection(header);

        var docEl = document.documentElement;
        var body = document.body;
        var lastScrollTop = window.pageYOffset || 0;
        var ticking = false;

        // Constants
        var SCROLL_DELTA = 5; // Minimum scroll to trigger action
        var HIDE_OFFSET = 100; // Pixel offset before hiding starts

        function recalcOffsets() {
            var adminBarHeight = getAdminBarHeight();
            var headerHeight = header.offsetHeight || 0;
            // Banner height might not be needed for sticky logic if it scrolls away,
            // but we keep the variable if needed for other logic.
            getAnimatedBannerHeight(header);

            var topOffset = adminBarHeight;

            // Set variables
            docEl.style.setProperty('--bw-header-top-offset', topOffset + 'px');
            docEl.style.setProperty('--bw-header-body-padding', headerHeight + 'px');

            // IMPORTANT:
            // We do NOT add the 'bw-sticky-header-active' class here initially.
            // That class adds padding-top to body. 
            // Since the header is position:relative by default (static), it effectively takes up space.
            // Adding padding to body would double the whitespace at the top.
            // The class is now toggled ONLY in the onScroll function when the header becomes fixed.

            // Just ensure it's removed on resize if we are at the top
            var st = window.pageYOffset || 0;
            if (st <= headerHeight + 50) {
                body.classList.remove('bw-sticky-header-active');
                header.classList.remove('bw-sticky-header');
            }
        }

        function onScroll() {
            var st = window.pageYOffset || 0;
            var headerHeight = header.offsetHeight || 0;

            checkDarkZoneOverlap(header);

            // ACTIVATION POINT:
            // When the header should become sticky.
            // Usually headerHeight + some buffer.
            var activationPoint = headerHeight + 50;
            var isSticky = st > activationPoint;

            if (isSticky) {
                header.classList.add('bw-sticky-header');
                document.body.classList.add('bw-sticky-header-active');
            } else {
                header.classList.remove('bw-sticky-header');
                document.body.classList.remove('bw-sticky-header-active');
                header.classList.remove('bw-header-hidden');
                header.classList.remove('bw-header-visible');
                lastScrollTop = st;
                return;
            }

            // Directional Logic
            if (Math.abs(lastScrollTop - st) <= SCROLL_DELTA) return;

            // HIDE LOGIC:
            // Only hide if scrolling DOWN AND past a certain point.
            // Crucial fix: Ensure we are well past the activation point to avoid immediate hiding.
            // If we just became sticky at 'activationPoint' (e.g. 150px), and we scroll to 155px,
            // we are scrolling down, but we shouldn't hide yet.
            // Let's require being past activationPoint + headerHeight.
            var safeHideThreshold = activationPoint + headerHeight + 50;

            if (st > lastScrollTop && st > safeHideThreshold) {
                // Scroll Down -> Hide
                header.classList.add('bw-header-hidden');
                header.classList.remove('bw-header-visible');
            } else if (st < lastScrollTop) {
                // Scroll Up -> Show
                // Only show if we aren't at the very bottom (rubber banding)
                if (st + window.innerHeight < document.body.scrollHeight) {
                    header.classList.remove('bw-header-hidden');
                    header.classList.add('bw-header-visible');
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

        // Init
        recalcOffsets();
    }

    function boot() {
        ensureHeaderInBody();
        applyStateClass();
        initStickyHeader();

        // Reveal header
        var header = document.querySelector('.bw-custom-header');
        if (header) {
            header.classList.remove('bw-header-preload'); // If used in CSS
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
