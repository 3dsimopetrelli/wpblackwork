(function () {
    'use strict';

    function BWAccountDropdown(root) {
        this.root = root;
        this.trigger = root.querySelector('[data-bw-account-dropdown-trigger]');
        this.panel = root.querySelector('[data-bw-account-dropdown-panel]');
        this.breakpoint = parseInt(root.getAttribute('data-bw-account-dropdown-breakpoint'), 10)
            || ((window.bwHeaderConfig && window.bwHeaderConfig.breakpoint) ? parseInt(window.bwHeaderConfig.breakpoint, 10) : 1024);
        this._closeTimeout = null;

        this.handlePointerEnter = this.handlePointerEnter.bind(this);
        this.handlePointerLeave = this.handlePointerLeave.bind(this);
        this.handlePanelPointerEnter = this.handlePanelPointerEnter.bind(this);
        this.handlePanelPointerLeave = this.handlePanelPointerLeave.bind(this);
        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleFocusOut = this.handleFocusOut.bind(this);
        this.handleTriggerClick = this.handleTriggerClick.bind(this);
        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);
        this.handleWindowResize = this.handleWindowResize.bind(this);
    }

    BWAccountDropdown.prototype.isDesktop = function () {
        return window.innerWidth > this.breakpoint;
    };

    BWAccountDropdown.prototype.init = function () {
        if (!this.root || !this.trigger || !this.panel) {
            return;
        }

        // Move panel to <body> so its backdrop-filter samples real page content
        // instead of being trapped inside the header's compositor layer.
        if (this.panel.parentNode !== document.body) {
            document.body.appendChild(this.panel);
        }

        this.root.addEventListener('pointerenter', this.handlePointerEnter);
        this.root.addEventListener('pointerleave', this.handlePointerLeave);
        this.panel.addEventListener('pointerenter', this.handlePanelPointerEnter);
        this.panel.addEventListener('pointerleave', this.handlePanelPointerLeave);
        this.root.addEventListener('focusin', this.handleFocusIn);
        this.root.addEventListener('focusout', this.handleFocusOut);
        this.trigger.addEventListener('click', this.handleTriggerClick);
        document.addEventListener('click', this.handleDocumentClick);
        document.addEventListener('keydown', this.handleDocumentKeydown);
        window.addEventListener('resize', this.handleWindowResize);
    };

    BWAccountDropdown.prototype.positionPanel = function () {
        if (!this.trigger || !this.panel) {
            return;
        }

        var rect = this.trigger.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        var gap = 10;
        var margin = 12;
        var top = Math.round(rect.bottom + gap);
        // Align panel's right edge with trigger's right edge
        var right = Math.round(viewportWidth - rect.right);
        if (right < margin) {
            right = margin;
        }

        this.panel.style.setProperty('--bw-account-dropdown-top', top + 'px');
        this.panel.style.setProperty('--bw-account-dropdown-right', right + 'px');
    };

    BWAccountDropdown.prototype.open = function () {
        // Check live — never rely on a cached isDesktop flag that can get stuck.
        if (!this.isDesktop()) {
            return;
        }

        if (this._closeTimeout) {
            clearTimeout(this._closeTimeout);
            this._closeTimeout = null;
        }

        this.positionPanel();
        this.root.classList.add('is-open');
        this.panel.classList.add('is-open');
        this.trigger.setAttribute('aria-expanded', 'true');
        this.panel.setAttribute('aria-hidden', 'false');
    };

    BWAccountDropdown.prototype.close = function () {
        if (this._closeTimeout) {
            clearTimeout(this._closeTimeout);
            this._closeTimeout = null;
        }

        this.root.classList.remove('is-open');
        this.panel.classList.remove('is-open');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.panel.setAttribute('aria-hidden', 'true');
    };

    BWAccountDropdown.prototype.handlePointerEnter = function () {
        this.open();
    };

    BWAccountDropdown.prototype.handlePointerLeave = function () {
        var self = this;
        // Delay close so the pointer has time to reach the panel before it disappears.
        this._closeTimeout = setTimeout(function () {
            self.close();
        }, 150);
    };

    BWAccountDropdown.prototype.handlePanelPointerEnter = function () {
        // Cancel any pending close when the pointer enters the panel.
        if (this._closeTimeout) {
            clearTimeout(this._closeTimeout);
            this._closeTimeout = null;
        }
    };

    BWAccountDropdown.prototype.handlePanelPointerLeave = function () {
        this.close();
    };

    BWAccountDropdown.prototype.handleFocusIn = function () {
        this.open();
    };

    BWAccountDropdown.prototype.handleTriggerClick = function (event) {
        if (!this.isDesktop()) {
            return;
        }

        event.preventDefault();
        if (this.panel.classList.contains('is-open')) {
            this.close();
            return;
        }

        this.open();
    };

    BWAccountDropdown.prototype.handleFocusOut = function (event) {
        var nextTarget = event.relatedTarget;
        if (nextTarget && (this.root.contains(nextTarget) || this.panel.contains(nextTarget))) {
            return;
        }

        this.close();
    };

    BWAccountDropdown.prototype.handleDocumentClick = function (event) {
        if (!this.root.contains(event.target) && !this.panel.contains(event.target)) {
            this.close();
        }
    };

    BWAccountDropdown.prototype.handleDocumentKeydown = function (event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            this.close();
        }
    };

    BWAccountDropdown.prototype.handleWindowResize = function () {
        if (!this.panel.classList.contains('is-open')) {
            return;
        }

        if (!this.isDesktop()) {
            this.close();
        } else {
            this.positionPanel();
        }
    };

    function initAll() {
        document.querySelectorAll('[data-bw-account-dropdown]').forEach(function (instance) {
            if (instance.dataset.bwAccountDropdownInitialized === 'yes') {
                return;
            }

            var dropdown = new BWAccountDropdown(instance);
            dropdown.init();
            instance.dataset.bwAccountDropdownInitialized = 'yes';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
