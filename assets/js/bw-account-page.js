(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var authWrapper = document.querySelector('.bw-account-auth');
        if (!authWrapper) {
            return;
        }

        var tabs = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-tab]'));
        var panels = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-panel]'));

        if (!tabs.length || !panels.length) {
            return;
        }

        var fadeDuration = 200;

        var activatePanel = function (target) {
            tabs.forEach(function (tab) {
                var isActive = tab.getAttribute('data-bw-auth-tab') === target;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            });

            panels.forEach(function (panel) {
                var isTarget = panel.getAttribute('data-bw-auth-panel') === target;

                if (isTarget) {
                    panel.classList.add('is-visible');
                    panel.setAttribute('aria-hidden', 'false');
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            panel.classList.add('is-active');
                        });
                    });
                } else {
                    panel.classList.remove('is-active');
                    panel.setAttribute('aria-hidden', 'true');
                    setTimeout(function () {
                        panel.classList.remove('is-visible');
                    }, 300);
                }
            });
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                var target = tab.getAttribute('data-bw-auth-tab');
                if (!target) {
                    return;
                }
                activatePanel(target);
            });
        });

        var defaultTab = authWrapper.getAttribute('data-bw-default-tab') || 'login';
        activatePanel(defaultTab);
    });
})();
