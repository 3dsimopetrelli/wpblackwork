(function () {
    'use strict';

    if (document.documentElement) {
        document.documentElement.classList.add('bw-header-js');
    }

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

    function initSmartHeaderScroll() {
        var header = document.querySelector('.bw-custom-header[data-smart-scroll="yes"]');
        if (!header) {
            if (document.body) {
                document.body.classList.remove('bw-custom-header-smart-enabled');
            }
            return;
        }

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

        function recalcOffsets() {
            var adminBarHeight = getAdminBarHeight();
            var headerHeight = header.offsetHeight || 0;
            var bp = getBreakpoint();
            var isMobile = window.matchMedia('(max-width: ' + bp + 'px)').matches;

            docEl.style.setProperty('--bw-header-top-offset', adminBarHeight + 'px');
            if (isMobile) {
                docEl.style.setProperty('--bw-header-body-padding', '0px');
                body.classList.remove('bw-custom-header-smart-enabled');
            } else {
                docEl.style.setProperty('--bw-header-body-padding', headerHeight + 'px');
                body.classList.add('bw-custom-header-smart-enabled');
            }
        }

        function showHeader() {
            header.classList.remove('bw-header-hidden');
            header.classList.add('bw-header-visible');
        }

        function hideHeader() {
            header.classList.remove('bw-header-visible');
            header.classList.add('bw-header-hidden');
        }

        function onScroll() {
            var currentScrollTop = Math.max(0, window.pageYOffset || 0);
            if (Math.abs(currentScrollTop - lastScrollTop) < scrollDelta) {
                return;
            }
            var bp = getBreakpoint();
            var isMobile = window.matchMedia('(max-width: ' + bp + 'px)').matches;

            // Mobile: disable smart hide/show + scrolled blur state to avoid jitter.
            if (isMobile) {
                header.classList.remove('bw-header-hidden');
                header.classList.add('bw-header-visible');
                header.classList.remove('bw-header-scrolled');
                lastScrollTop = currentScrollTop;
                return;
            }

            var headerHeight = header.offsetHeight || 0;
            var hideThreshold = Math.max(scrollDownThreshold, headerHeight);
            var blurTrigger = Math.max(blurThreshold, Math.round(headerHeight * 0.5));

            if (currentScrollTop > blurTrigger) {
                header.classList.add('bw-header-scrolled');
            } else {
                header.classList.remove('bw-header-scrolled');
            }

            if (currentScrollTop > lastScrollTop) {
                if (currentScrollTop > hideThreshold) {
                    hideHeader();
                }
            } else {
                var upDelta = lastScrollTop - currentScrollTop;
                if (upDelta >= scrollUpThreshold) {
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
