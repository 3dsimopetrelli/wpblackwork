(function () {
    'use strict';

    function BWNavigation(root) {
        this.root = root;
        this.toggle = root.querySelector('.bw-navigation__toggle');
        this.overlay = root.querySelector('.bw-navigation__mobile-overlay');
        this.close = root.querySelector('.bw-navigation__close');
        this.mobileLinks = root.querySelectorAll('.bw-navigation__mobile .bw-navigation__link, .bw-navigation__mobile-cta, .bw-navigation__mobile-footer-link');

        this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);
        this.handleOverlayClick = this.handleOverlayClick.bind(this);
        this.handleToggleClick = this.handleToggleClick.bind(this);
        this.handleCloseClick = this.handleCloseClick.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);
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

        this.mobileLinks.forEach(function (link) {
            link.addEventListener('click', this.handleLinkClick);
        }, this);
    };

    BWNavigation.prototype.destroy = function () {
        document.removeEventListener('keydown', this.handleDocumentKeydown);
        if (this.toggle) {
            this.toggle.removeEventListener('click', this.handleToggleClick);
        }
        if (this.close) {
            this.close.removeEventListener('click', this.handleCloseClick);
        }
        if (this.overlay) {
            this.overlay.removeEventListener('click', this.handleOverlayClick);
        }
        this.mobileLinks.forEach(function (link) {
            link.removeEventListener('click', this.handleLinkClick);
        }, this);
    };

    BWNavigation.prototype.getFocusableElements = function () {
        var panel = this.overlay.querySelector('.bw-navigation__mobile-panel');
        if (!panel) return [];
        var nodes = panel.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
        return Array.prototype.slice.call(nodes);
    };

    BWNavigation.prototype.open = function () {
        this.overlay.classList.add('is-open');
        this.overlay.setAttribute('aria-hidden', 'false');
        this.toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('bw-navigation-mobile-open');
        // Move focus into the panel for keyboard and screen reader users.
        this.close.focus();
    };

    BWNavigation.prototype.closeMenu = function () {
        this.overlay.classList.remove('is-open');
        this.overlay.setAttribute('aria-hidden', 'true');
        this.toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('bw-navigation-mobile-open');
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
        this.toggle.focus();
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
            this.toggle.focus();
            return;
        }

        // Focus trap: keep Tab navigation inside the panel.
        if (event.key === 'Tab' || event.keyCode === 9) {
            var focusable = this.getFocusableElements();
            if (focusable.length === 0) {
                event.preventDefault();
                return;
            }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }
    };

    BWNavigation.prototype.handleLinkClick = function () {
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
