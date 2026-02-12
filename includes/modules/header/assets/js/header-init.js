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

        // Ensure the custom header is not constrained by theme wrappers/max-width containers.
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
        if (!adminBar) {
            return 0;
        }

        return adminBar.offsetHeight || 0;
    }

    /* ========================================================================
       ANIMATED BANNER SUPPORT
       ======================================================================== */

    function getAnimatedBannerHeight(header) {
        var banner = document.querySelector('.bw-animated-banner');
        if (!banner || banner.offsetParent === null) { // visible check
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
        if (!color || color === 'transparent' || color === 'rgba(0, 0, 0, 0)') {
            return null;
        }

        // Match rgb(r, g, b) or rgba(r, g, b, a)
        var rgbaMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
        if (rgbaMatch) {
            return {
                r: parseInt(rgbaMatch[1], 10),
                g: parseInt(rgbaMatch[2], 10),
                b: parseInt(rgbaMatch[3], 10),
                a: rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1
            };
        }

        // Match hex #RRGGBB or #RGB
        var hexMatch = color.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (hexMatch) {
            var hex = hexMatch[1];
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
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
        // W3C brightness formula
        return (color.r * 299 + color.g * 587 + color.b * 114) / 1000;
    }

    function isColorDark(color, threshold) {
        if (!color) return false;
        if (typeof threshold === 'undefined') threshold = 128;
        if (color.a < 0.5) return false; // Low opacity is not "dark" (assumes white bg behind)
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

            if (parsed && parsed.a > 0.5) {
                return parsed;
            }

            var bgImage = computedStyle.backgroundImage;
            if (bgImage && bgImage !== 'none' && bgImage.indexOf('gradient') !== -1) {
                var gradientColorMatch = bgImage.match(/rgba?\([^)]+\)/);
                if (gradientColorMatch) {
                    var gradientColor = parseColor(gradientColorMatch[0]);
                    if (gradientColor && gradientColor.a > 0.5) {
                        return gradientColor;
                    }
                }
            }

            current = current.parentElement;
            depth++;
        }

        return { r: 255, g: 255, b: 255, a: 1 };
    }

    function autoDetectDarkSections() {
        var sections = [];
        var selectors = [
            '.elementor-section',
            '.elementor-container',
            'section',
            '[data-elementor-type]',
            '.wp-block-cover',
            '.entry-content > div',
            'main > section',
            'main > div'
        ];

        var allSections = document.querySelectorAll(selectors.join(', '));

        allSections.forEach(function (section) {
            if (section.classList.contains('bw-custom-header') || section.closest('.bw-custom-header')) {
                return;
            }

            var rect = section.getBoundingClientRect();
            if (rect.height < 100) {
                return;
            }

            var bgColor = getEffectiveBackgroundColor(section);
            if (isColorDark(bgColor, 128)) {
                sections.push(section);
            }
        });

        return sections;
    }

    function checkDarkZoneOverlap(header) {
        if (!header) return;

        var headerRect = header.getBoundingClientRect();
        var headerTop = headerRect.top;
        var headerBottom = headerRect.bottom;
        var headerHeight = headerRect.height;

        var overlappingZone = null;
        var maxOverlap = 0;

        darkZones.forEach(function (zone) {
            var zoneRect = zone.getBoundingClientRect();
            var zoneTop = zoneRect.top;
            var zoneBottom = zoneRect.bottom;

            var isOverlapping = (zoneTop < headerBottom && zoneBottom > headerTop);

            if (isOverlapping) {
                var overlapTop = Math.max(headerTop, zoneTop);
                var overlapBottom = Math.min(headerBottom, zoneBottom);
                var overlapHeight = overlapBottom - overlapTop;
                var overlapPercentage = (overlapHeight / headerHeight) * 100;

                if (overlapPercentage > maxOverlap) {
                    maxOverlap = overlapPercentage;
                    overlappingZone = zone;
                }
            }
        });

        var overlapThreshold = 30; // 30% overlap required
        var shouldBeOnDark = overlappingZone !== null && maxOverlap >= overlapThreshold;

        if (shouldBeOnDark !== isOnDarkZone) {
            isOnDarkZone = shouldBeOnDark;
            currentDarkZone = overlappingZone;

            if (isOnDarkZone) {
                header.classList.add('smart-header--on-dark'); // Legacy class support
                header.classList.add('bw-header-on-dark');
            } else {
                header.classList.remove('smart-header--on-dark');
                header.classList.remove('bw-header-on-dark');
            }
        }
    }

    function initDarkZoneDetection(header) {
        var manualDarkZones = Array.from(document.querySelectorAll('.smart-header-dark-zone'));
        var autoDarkZones = autoDetectDarkSections();

        // Combine unique zones
        var allDarkZones = manualDarkZones.slice();
        autoDarkZones.forEach(function (zone) {
            if (!manualDarkZones.includes(zone)) {
                allDarkZones.push(zone);
            }
        });

        darkZones = allDarkZones;

        if (darkZones.length === 0) {
            return;
        }

        if ('IntersectionObserver' in window) {
            var observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: [0, 0.1, 0.25, 0.5, 0.75, 1.0]
            };

            darkZoneObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting || entry.intersectionRatio > 0) {
                        checkDarkZoneOverlap(header);
                    }
                });
            }, observerOptions);

            darkZones.forEach(function (zone) {
                darkZoneObserver.observe(zone);
            });
        }

        checkDarkZoneOverlap(header);
    }


    /* ========================================================================
       MAIN SCROLL INIT
       ======================================================================== */

    function initSmartHeaderScroll() {
        var header = document.querySelector('.bw-custom-header[data-smart-scroll="yes"]');
        if (!header) {
            if (document.body) {
                document.body.classList.remove('bw-custom-header-smart-enabled');
            }
            return;
        }

        // Initialize Dark Zone Detection
        initDarkZoneDetection(header);

        var smartCfg = (window.bwHeaderConfig && window.bwHeaderConfig.smartHeader) ? window.bwHeaderConfig.smartHeader : {};
        var scrollDownThreshold = parseInt(smartCfg.scrollDownThreshold, 10);
        var scrollUpThreshold = parseInt(smartCfg.scrollUpThreshold, 10);
        var blurThreshold = parseInt(smartCfg.blurThreshold, 10);
        var throttleDelay = parseInt(smartCfg.throttleDelay, 10);

        if (Number.isNaN(scrollDownThreshold)) {
            scrollDownThreshold = 100;
        }
        if (Number.isNaN(scrollUpThreshold)) {
            scrollUpThreshold = 0;
        }
        if (Number.isNaN(blurThreshold)) {
            blurThreshold = 50;
        }
        if (Number.isNaN(throttleDelay) || throttleDelay < 1) {
            throttleDelay = 16;
        }

        var docEl = document.documentElement;
        var body = document.body;
        var lastScrollTop = Math.max(0, window.pageYOffset || 0);
        var ticking = false;
        var scrollDelta = parseInt(smartCfg.scrollDelta, 10);
        if (Number.isNaN(scrollDelta) || scrollDelta < 1) {
            scrollDelta = 1;
        }
        var lastHandle = 0;
        var isHeaderHidden = false;

        function recalcOffsets() {
            var adminBarHeight = getAdminBarHeight();
            var headerHeight = header.offsetHeight || 0;
            var bp = getBreakpoint();
            var isMobile = window.matchMedia('(max-width: ' + bp + 'px)').matches;

            // Calculate banner height
            var bannerData = getAnimatedBannerHeight(header);
            var bannerHeight = bannerData.height;
            var bannerInside = bannerData.inside;

            var topOffset = adminBarHeight;
            if (!bannerInside) {
                topOffset += bannerHeight;
            }

            docEl.style.setProperty('--bw-header-top-offset', topOffset + 'px');

            if (isMobile) {
                docEl.style.setProperty('--bw-header-body-padding', '0px');
                body.classList.remove('bw-custom-header-smart-enabled');
            } else {
                var totalBodyPadding = headerHeight;
                // If banner is outside (above), it usually pushes content down naturally, 
                // but if header is fixed/sticky, we need to compensate.
                // Legacy behavior added bannerHeight to body padding.
                // We will simply ensure body padding accounts for header height (and implicitly banner if it takes space).

                // If header is sticky, it is taken out of flow. 
                // If banner is above it and also taken out of flow (fixed), we need to add it.
                // For now, let's keep body padding as header height, but maybe add banner if separate?
                // Safe bet: Match legacy total height calculation for padding if banner is NOT inside.
                if (!bannerInside) {
                    totalBodyPadding += bannerHeight;
                }

                docEl.style.setProperty('--bw-header-body-padding', totalBodyPadding + 'px');
                body.classList.add('bw-custom-header-smart-enabled');
            }
        }

        function showHeader() {
            if (!isHeaderHidden) {
                return;
            }
            header.classList.remove('hidden');
            header.classList.remove('bw-header-hidden');
            header.classList.add('visible');
            header.classList.add('bw-header-visible');
            isHeaderHidden = false;
        }

        function hideHeader() {
            if (isHeaderHidden) {
                return;
            }
            header.classList.remove('visible');
            header.classList.remove('bw-header-visible');
            header.classList.add('hidden');
            header.classList.add('bw-header-hidden');
            isHeaderHidden = true;
        }

        function onScroll() {
            var currentScrollTop = Math.max(0, window.pageYOffset || 0);

            // Check Dark Zones on scroll
            checkDarkZoneOverlap(header);

            if (Math.abs(currentScrollTop - lastScrollTop) < scrollDelta) {
                return;
            }
            var bp = getBreakpoint();
            var isMobile = window.matchMedia('(max-width: ' + bp + 'px)').matches;

            // Mobile: disable smart hide/show + scrolled blur state to avoid jitter.
            if (isMobile) {
                header.classList.remove('hidden');
                header.classList.add('visible');
                header.classList.remove('bw-header-hidden');
                header.classList.add('bw-header-visible');
                header.classList.remove('scrolled');
                header.classList.remove('bw-header-scrolled');
                lastScrollTop = currentScrollTop;
                return;
            }

            var headerHeight = header.offsetHeight || 0;
            // Include banner height in threshold if outside
            // var bannerHeight = getAnimatedBannerHeight(header).height; // Optimization: don't recalc largely every scroll
            // Better to rely on headerHeight. If header includes banner (visually or DOM), headerHeight reflects it.

            var hideThreshold = Math.max(scrollDownThreshold, headerHeight);
            var blurTrigger = Math.max(blurThreshold, Math.round(headerHeight * 0.5));
            var deltaScroll = currentScrollTop - lastScrollTop;
            var newDirection = deltaScroll > 0 ? 'down' : 'up';

            if (currentScrollTop > blurTrigger) {
                header.classList.add('scrolled');
                header.classList.add('bw-header-scrolled');
            } else {
                header.classList.remove('scrolled');
                header.classList.remove('bw-header-scrolled');
            }

            if (currentScrollTop <= hideThreshold) {
                showHeader();
                lastScrollTop = currentScrollTop;
                return;
            }

            if (newDirection === 'up') {
                // Scroll up: show immediately (legacy smart-header behavior)
                showHeader();
            } else if (newDirection === 'down') {
                // Scroll down: hide only after threshold
                if (currentScrollTop > hideThreshold) {
                    hideHeader();
                } else {
                    showHeader();
                }
            }

            lastScrollTop = currentScrollTop;
        }

        function onScrollOptimized() {
            if (ticking) {
                return;
            }

            var now = Date.now();
            if (now - lastHandle < throttleDelay) {
                return;
            }
            lastHandle = now;

            window.requestAnimationFrame(function () {
                onScroll();
                ticking = false;
            });

            ticking = true;
        }

        recalcOffsets();
        showHeader();

        window.addEventListener('scroll', onScrollOptimized, { passive: true });
        window.addEventListener('resize', function () {
            recalcOffsets();
            applyStateClass();
            checkDarkZoneOverlap(header); // Re-check on resize
        });
    }

    function initMobileStickyWhiteOnScroll() {
        var header = document.querySelector('.bw-custom-header');
        if (!header) {
            return;
        }

        var topOffsetTicking = false;

        function updateMobileTopOffset() {
            var adminBarHeight = getAdminBarHeight();
            document.documentElement.style.setProperty('--bw-header-mobile-top-offset', adminBarHeight + 'px');
        }

        function scheduleTopOffsetUpdate() {
            if (topOffsetTicking) {
                return;
            }
            topOffsetTicking = true;
            window.requestAnimationFrame(function () {
                updateMobileTopOffset();
                topOffsetTicking = false;
            });
        }

        function updateMobileScrolledState() {
            var bp = getBreakpoint();
            var isMobile = window.matchMedia('(max-width: ' + bp + 'px)').matches;
            var currentScrollTop = Math.max(0, window.pageYOffset || 0);

            if (isMobile && currentScrollTop > 2) {
                header.classList.add('bw-mobile-scrolled');
            } else {
                header.classList.remove('bw-mobile-scrolled');
            }
        }

        updateMobileTopOffset();
        updateMobileScrolledState();

        window.addEventListener('scroll', function () {
            scheduleTopOffsetUpdate();
            updateMobileScrolledState();
        }, { passive: true });
        window.addEventListener('resize', function () {
            scheduleTopOffsetUpdate();
            updateMobileScrolledState();
        });
        window.addEventListener('orientationchange', function () {
            scheduleTopOffsetUpdate();
            updateMobileScrolledState();
        });

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleTopOffsetUpdate);
            window.visualViewport.addEventListener('scroll', scheduleTopOffsetUpdate);
        }
    }

    function boot() {
        ensureHeaderInBody();
        applyStateClass();
        initSmartHeaderScroll();
        initMobileStickyWhiteOnScroll();

        var header = document.querySelector('.bw-custom-header.bw-header-preload');
        if (!header) {
            return;
        }

        var reveal = function () {
            header.classList.add('bw-header-ready');
        };

        if (document.readyState === 'complete') {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(reveal);
            });
        } else {
            window.addEventListener('load', function () {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(reveal);
                });
            }, { once: true });

            // Fallback safety: never keep header hidden if load event is delayed.
            window.setTimeout(reveal, 1800);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
