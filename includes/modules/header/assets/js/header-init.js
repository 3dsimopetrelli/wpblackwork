(function () {
    'use strict';

    if (document.documentElement) {
        document.documentElement.classList.add('bw-header-js');
    }

    // Dark Zone state
    var darkZoneObserver = null;
    var darkZones = [];
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
        var headerTop = headerRect.top > 0 ? headerRect.top : 0;
        var headerBottom = headerTop + headerHeight;

        var overlappingZone = null;
        var maxOverlap = 0;

        darkZones.forEach(function (zone) {
            var zoneRect = zone.getBoundingClientRect();
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
            if (isOnDarkZone) {
                header.classList.add('bw-header-on-dark');
            } else {
                header.classList.remove('bw-header-on-dark');
            }
        }
    }

    function initDarkZoneDetection(header) {
        var manualDarkZones = Array.from(document.querySelectorAll('.smart-header-dark-zone'));
        var autoDarkZones = autoDetectDarkSections();
        var allDarkZones = manualDarkZones.concat(autoDarkZones.filter(function (z) { return !manualDarkZones.includes(z); }));
        darkZones = allDarkZones;

        if ('IntersectionObserver' in window && darkZones.length > 0) {
            darkZoneObserver = new IntersectionObserver(function () {
                checkDarkZoneOverlap(header);
            }, { root: null, rootMargin: '0px' });
            darkZones.forEach(function (zone) { darkZoneObserver.observe(zone); });
        }
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
        var wasSticky = false;
        var scrollDelta = cfg.scrollDelta;
        var scrollDownThreshold = cfg.scrollDownThreshold;
        var scrollUpThreshold = cfg.scrollUpThreshold;

        function recalcOffsets() {
            var adminBarHeight = getAdminBarHeight();
            var headerHeight = header.offsetHeight || 0;
            getAnimatedBannerHeight(header);

            docEl.style.setProperty('--bw-header-top-offset', adminBarHeight + 'px');
            docEl.style.setProperty('--bw-header-body-padding', headerHeight + 'px');

            var st = window.pageYOffset || 0;
            if (st <= 0) {
                body.classList.remove('bw-sticky-header-active');
                header.classList.remove('bw-sticky-header');
                header.classList.remove('bw-header-hidden');
                header.classList.remove('bw-header-visible');
                wasSticky = false;
            }
        }

        function onScroll() {
            var st = window.pageYOffset || 0;
            var headerHeight = header.offsetHeight || 0;

            checkDarkZoneOverlap(header);

            // Header must scroll fully past its own height before becoming sticky.
            // Also respect the admin-configured scrollDownThreshold.
            // Hysteresis: activate at activationPoint (scroll down),
            // but only deactivate when scroll reaches the very top (~0)
            // so the natural (relative) header is already in view.
            var activationPoint = Math.max(headerHeight, scrollDownThreshold);

            var shouldBeSticky;
            if (wasSticky) {
                // Once sticky, stay sticky until user scrolls to the very top.
                shouldBeSticky = st > 2;
            } else {
                shouldBeSticky = st > activationPoint;
            }

            if (shouldBeSticky) {
                if (!wasSticky) {
                    // First frame entering sticky mode.
                    // Disable transitions BEFORE any class changes to prevent
                    // the browser from animating the position: relative → fixed shift.
                    header.style.transition = 'none';

                    // Ensure body padding matches current header height exactly.
                    docEl.style.setProperty('--bw-header-body-padding', headerHeight + 'px');

                    header.classList.add('bw-sticky-header');
                    body.classList.add('bw-sticky-header-active');

                    if (st > lastScrollTop) {
                        // Entering sticky while scrolling DOWN: hide instantly.
                        header.classList.add('bw-header-hidden');
                        header.classList.remove('bw-header-visible');
                    } else {
                        // Entering sticky while scrolling UP: show immediately.
                        header.classList.remove('bw-header-hidden');
                        header.classList.add('bw-header-visible');
                    }

                    // Force reflow so all changes are computed with no transition,
                    // then restore CSS transitions for future show/hide animations.
                    void header.offsetHeight;
                    header.style.transition = '';
                    wasSticky = true;
                } else {
                    // Already sticky — directional show/hide.
                    var delta = Math.abs(lastScrollTop - st);
                    if (delta > scrollDelta) {
                        if (st > lastScrollTop) {
                            // Scrolling DOWN → hide.
                            header.classList.add('bw-header-hidden');
                            header.classList.remove('bw-header-visible');
                        } else {
                            // Scrolling UP → show (respecting scrollUpThreshold).
                            var upDelta = lastScrollTop - st;
                            if (upDelta >= scrollUpThreshold && st + window.innerHeight < body.scrollHeight) {
                                header.classList.remove('bw-header-hidden');
                                header.classList.add('bw-header-visible');
                            }
                        }
                    }
                }
            } else {
                // At the very top: natural header is in view.
                // Remove sticky instantly — no animation needed, the natural
                // header provides seamless visual continuity.
                header.style.transition = 'none';
                header.classList.remove('bw-sticky-header');
                body.classList.remove('bw-sticky-header-active');
                header.classList.remove('bw-header-hidden');
                header.classList.remove('bw-header-visible');
                void header.offsetHeight;
                header.style.transition = '';
                wasSticky = false;
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
