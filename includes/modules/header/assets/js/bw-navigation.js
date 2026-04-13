(function () {
    'use strict';

    function BWNavigation(root) {
        this.root = root;
        this.toggle = root.querySelector('.bw-navigation__toggle');
        this.overlay = root.querySelector('.bw-navigation__mobile-overlay');
        this.panel = root.querySelector('.bw-navigation__mobile-panel');
        this.mobileLinks = root.querySelectorAll('.bw-navigation__mobile .bw-navigation__link, .bw-navigation__mobile-cta, .bw-navigation__profile-cta, .bw-navigation__mobile-auth-link, .bw-navigation__mobile-footer-link');
        this.breakpoint = (window.bwHeaderConfig && window.bwHeaderConfig.breakpoint) ? parseInt(window.bwHeaderConfig.breakpoint, 10) : 1024;
        this.lastActiveElement = null;

        this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);
        this.handleOverlayClick = this.handleOverlayClick.bind(this);
        this.handleToggleClick = this.handleToggleClick.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);
        this.handleWindowResize = this.handleWindowResize.bind(this);
        this.handleWindowScroll = this.handleWindowScroll.bind(this);
        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchMove = this.handleTouchMove.bind(this);
        this.scrollYOnOpen = 0;
    }

    BWNavigation.prototype.init = function () {
        if (!this.toggle || !this.overlay || !this.panel) {
            return;
        }

        // Move overlay to <body> so fixed positioning is viewport-based,
        // not constrained by transformed header ancestors.
        if (this.overlay.parentNode !== document.body) {
            document.body.appendChild(this.overlay);
        }

        this.toggle.addEventListener('click', this.handleToggleClick);
        this.overlay.addEventListener('click', this.handleOverlayClick);
        document.addEventListener('keydown', this.handleDocumentKeydown);
        window.addEventListener('resize', this.handleWindowResize);
        window.addEventListener('scroll', this.handleWindowScroll, { passive: true });
        window.addEventListener('touchstart', this.handleTouchStart, { passive: true });
        window.addEventListener('touchmove', this.handleTouchMove, { passive: true });

        this.mobileLinks.forEach(function (link) {
            link.addEventListener('click', this.handleLinkClick);
        }, this);
    };

    BWNavigation.prototype.getFocusableElements = function () {
        if (!this.panel) {
            return [];
        }

        var nodes = this.panel.querySelectorAll('a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
        return Array.prototype.filter.call(nodes, function (element) {
            return element && typeof element.focus === 'function' && (element.offsetWidth || element.offsetHeight || element.getClientRects().length);
        });
    };

    BWNavigation.prototype.positionPanel = function () {
        if (!this.toggle || !this.panel) {
            return;
        }

        var rect = this.toggle.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        var margin = viewportWidth <= 640 ? 8 : 12;
        var gap = viewportWidth <= 640 ? 8 : 10;
        var width = Math.min(340, Math.max(280, viewportWidth - (margin * 2)));
        var estimatedHeight = Math.min(560, Math.max(260, this.panel.scrollHeight || 0));
        var top = Math.round(rect.bottom + gap);
        var left = Math.round(rect.left - 6);
        var maxHeight = Math.max(220, viewportHeight - (margin * 2));
        var panelHeight = Math.min(estimatedHeight, maxHeight);
        var belowSpace = viewportHeight - rect.bottom - gap - margin;
        var aboveSpace = rect.top - gap - margin;

        if (belowSpace < panelHeight && aboveSpace > belowSpace) {
            top = Math.max(margin, Math.round(rect.top - panelHeight - gap));
        }

        if (left + width > viewportWidth - margin) {
            left = viewportWidth - margin - width;
        }

        if (left < margin) {
            left = margin;
        }

        if (top < margin) {
            top = margin;
        }

        if (top + panelHeight > viewportHeight - margin) {
            panelHeight = Math.max(220, viewportHeight - top - margin);
        }

        this.panel.style.setProperty('--bw-navigation-popup-left', left + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-top', top + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-width', width + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-max-height', panelHeight + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-transform-origin', left <= viewportWidth / 2 ? 'top left' : 'top right');
    };

    BWNavigation.prototype.open = function () {
        this.lastActiveElement = document.activeElement;
        this.scrollYOnOpen = window.pageYOffset || document.documentElement.scrollTop || 0;
        this.positionPanel();
        this.overlay.classList.add('is-open');
        this.overlay.setAttribute('aria-hidden', 'false');
        this.toggle.setAttribute('aria-expanded', 'true');

        var focusables = this.getFocusableElements();
        if (focusables.length > 0) {
            focusables[0].focus();
        } else if (this.toggle && typeof this.toggle.focus === 'function') {
            this.toggle.focus();
        }
    };

    BWNavigation.prototype.closeMenu = function () {
        this.overlay.classList.remove('is-open');
        this.overlay.setAttribute('aria-hidden', 'true');
        this.toggle.setAttribute('aria-expanded', 'false');

        if (this.lastActiveElement && typeof this.lastActiveElement.focus === 'function') {
            this.lastActiveElement.focus();
        } else if (this.toggle && typeof this.toggle.focus === 'function') {
            this.toggle.focus();
        }
    };

    BWNavigation.prototype.handleToggleClick = function (event) {
        event.preventDefault();

        if (this.overlay.classList.contains('is-open')) {
            this.closeMenu();
            return;
        }

        this.open();
    };

    BWNavigation.prototype.handleOverlayClick = function (event) {
        if (event.target === this.overlay) {
            this.closeMenu();
        }
    };

    BWNavigation.prototype.handleDocumentKeydown = function (event) {
        if (!this.overlay.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape' || event.keyCode === 27) {
            this.closeMenu();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        var focusables = this.getFocusableElements();
        if (!focusables.length) {
            event.preventDefault();
            if (this.toggle && typeof this.toggle.focus === 'function') {
                this.toggle.focus();
            }
            return;
        }

        var firstFocusable = focusables[0];
        var lastFocusable = focusables[focusables.length - 1];
        var activeElement = document.activeElement;

        if (event.shiftKey && activeElement === firstFocusable) {
            event.preventDefault();
            lastFocusable.focus();
            return;
        }

        if (!event.shiftKey && activeElement === lastFocusable) {
            event.preventDefault();
            firstFocusable.focus();
        }
    };

    BWNavigation.prototype.handleLinkClick = function () {
        this.closeMenu();
    };

    BWNavigation.prototype.handleWindowResize = function () {
        if (!this.overlay.classList.contains('is-open')) {
            return;
        }

        if (window.innerWidth > this.breakpoint) {
            this.closeMenu();
            return;
        }

        this.positionPanel();
    };

    BWNavigation.prototype.handleWindowScroll = function () {
        if (!this.overlay.classList.contains('is-open')) {
            return;
        }

        var currentScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
        if (currentScrollY !== this.scrollYOnOpen) {
            this.closeMenu();
        }
    };

    BWNavigation.prototype.handleTouchStart = function () {
        if (!this.overlay.classList.contains('is-open')) {
            return;
        }

        this.scrollYOnOpen = window.pageYOffset || document.documentElement.scrollTop || 0;
    };

    BWNavigation.prototype.handleTouchMove = function () {
        if (!this.overlay.classList.contains('is-open')) {
            return;
        }

        this.closeMenu();
    };

    function initAll() {
        document.querySelectorAll('.bw-navigation').forEach(function (instance) {
            if (instance.dataset.bwNavigationInitialized === 'yes') {
                return;
            }

            var nav = new BWNavigation(instance);
            nav.init();
            instance.dataset.bwNavigationInitialized = 'yes';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
