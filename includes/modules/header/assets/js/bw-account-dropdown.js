(function () {
    'use strict';

    function BWAccountDropdown(root) {
        this.root = root;
        this.trigger = root.querySelector('[data-bw-account-dropdown-trigger]');
        this.panel = root.querySelector('[data-bw-account-dropdown-panel]');
        this.breakpoint = parseInt(root.getAttribute('data-bw-account-dropdown-breakpoint'), 10)
            || ((window.bwHeaderConfig && window.bwHeaderConfig.breakpoint) ? parseInt(window.bwHeaderConfig.breakpoint, 10) : 1024);
        this.isDesktop = window.innerWidth > this.breakpoint;

        this.handlePointerEnter = this.handlePointerEnter.bind(this);
        this.handlePointerLeave = this.handlePointerLeave.bind(this);
        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleFocusOut = this.handleFocusOut.bind(this);
        this.handleTriggerClick = this.handleTriggerClick.bind(this);
        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);
        this.handleWindowResize = this.handleWindowResize.bind(this);
    }

    BWAccountDropdown.prototype.init = function () {
        if (!this.root || !this.trigger || !this.panel || !this.isDesktop) {
            return;
        }

        this.root.addEventListener('pointerenter', this.handlePointerEnter);
        this.root.addEventListener('pointerleave', this.handlePointerLeave);
        this.root.addEventListener('focusin', this.handleFocusIn);
        this.root.addEventListener('focusout', this.handleFocusOut);
        this.trigger.addEventListener('click', this.handleTriggerClick);
        document.addEventListener('click', this.handleDocumentClick);
        document.addEventListener('keydown', this.handleDocumentKeydown);
        window.addEventListener('resize', this.handleWindowResize);
    };

    BWAccountDropdown.prototype.open = function () {
        if (!this.isDesktop) {
            return;
        }

        this.root.classList.add('is-open');
        this.trigger.setAttribute('aria-expanded', 'true');
        this.panel.setAttribute('aria-hidden', 'false');
    };

    BWAccountDropdown.prototype.close = function () {
        this.root.classList.remove('is-open');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.panel.setAttribute('aria-hidden', 'true');
    };

    BWAccountDropdown.prototype.handlePointerEnter = function () {
        this.open();
    };

    BWAccountDropdown.prototype.handlePointerLeave = function () {
        this.close();
    };

    BWAccountDropdown.prototype.handleFocusIn = function () {
        this.open();
    };

    BWAccountDropdown.prototype.handleTriggerClick = function (event) {
        if (!this.isDesktop) {
            return;
        }

        event.preventDefault();
        if (this.root.classList.contains('is-open')) {
            this.close();
            return;
        }

        this.open();
    };

    BWAccountDropdown.prototype.handleFocusOut = function (event) {
        var nextTarget = event.relatedTarget;
        if (nextTarget && this.root.contains(nextTarget)) {
            return;
        }

        this.close();
    };

    BWAccountDropdown.prototype.handleDocumentClick = function (event) {
        if (!this.root.contains(event.target)) {
            this.close();
        }
    };

    BWAccountDropdown.prototype.handleDocumentKeydown = function (event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            this.close();
        }
    };

    BWAccountDropdown.prototype.handleWindowResize = function () {
        var nowDesktop = window.innerWidth > this.breakpoint;
        if (!nowDesktop) {
            this.close();
        }
        this.isDesktop = nowDesktop;
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
