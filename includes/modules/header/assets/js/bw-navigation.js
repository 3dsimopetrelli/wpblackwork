(function () {
    'use strict';

    function BWNavigation(root) {
        this.root = root;
        this.toggle = root.querySelector('.bw-navigation__toggle');
        this.overlay = root.querySelector('.bw-navigation__mobile-overlay');
        this.panel = root.querySelector('.bw-navigation__mobile-panel');
        this.close = root.querySelector('.bw-navigation__close');
        this.mobileLinks = root.querySelectorAll('.bw-navigation__mobile .bw-navigation__link, .bw-navigation__mobile-cta, .bw-navigation__profile-cta, .bw-navigation__mobile-auth-link, .bw-navigation__mobile-footer-link');
        this.breakpoint = (window.bwHeaderConfig && window.bwHeaderConfig.breakpoint) ? parseInt(window.bwHeaderConfig.breakpoint, 10) : 1024;
        this.lastActiveElement = null;

        this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);
        this.handleOverlayClick = this.handleOverlayClick.bind(this);
        this.handleToggleClick = this.handleToggleClick.bind(this);
        this.handleCloseClick = this.handleCloseClick.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);
        this.handleWindowResize = this.handleWindowResize.bind(this);
    }

    BWNavigation.prototype.init = function () {
        if (!this.toggle || !this.overlay || !this.close) {
            return;
        }

        // Move overlay to <body> so fixed positioning is viewport-based,
        // not constrained by transformed header ancestors.
        if (this.overlay.parentNode !== document.body) {
            document.body.appendChild(this.overlay);
        }

        this.toggle.addEventListener('click', this.handleToggleClick);
        this.close.addEventListener('click', this.handleCloseClick);
        this.overlay.addEventListener('click', this.handleOverlayClick);
        document.addEventListener('keydown', this.handleDocumentKeydown);
        window.addEventListener('resize', this.handleWindowResize);

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
        var width = Math.min(420, Math.max(260, viewportWidth - (margin * 2)));
        var left = Math.round(rect.left);
        var top = Math.round(rect.bottom + 12);
        var maxHeight = Math.max(220, viewportHeight - top - margin);

        if (left + width > viewportWidth - margin) {
            left = viewportWidth - margin - width;
        }

        if (left < margin) {
            left = margin;
        }

        if (top < margin) {
            top = margin;
        }

        this.panel.style.setProperty('--bw-navigation-popup-left', left + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-top', top + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-width', width + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-max-height', maxHeight + 'px');
        this.panel.style.setProperty('--bw-navigation-popup-transform-origin', left <= viewportWidth / 2 ? 'top left' : 'top right');
    };

    BWNavigation.prototype.open = function () {
        this.lastActiveElement = document.activeElement;
        this.positionPanel();
        this.overlay.classList.add('is-open');
        this.overlay.setAttribute('aria-hidden', 'false');
        this.toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('bw-navigation-mobile-open');

        var focusables = this.getFocusableElements();
        if (focusables.length > 0) {
            focusables[0].focus();
        } else if (this.close && typeof this.close.focus === 'function') {
            this.close.focus();
        }
    };

    BWNavigation.prototype.closeMenu = function () {
        this.overlay.classList.remove('is-open');
        this.overlay.setAttribute('aria-hidden', 'true');
        this.toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('bw-navigation-mobile-open');

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

    BWNavigation.prototype.handleCloseClick = function (event) {
        event.preventDefault();
        this.closeMenu();
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
            if (this.close && typeof this.close.focus === 'function') {
                this.close.focus();
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
