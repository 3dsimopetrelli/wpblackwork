(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var initAuthTabs = function (authWrapper) {
            var tabs = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-tab]'));
            var panels = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-panel]'));

            if (!tabs.length || !panels.length) {
                return;
            }

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
        };

        var providerWrapper = document.querySelector('.bw-account-provider');
        var authWrapper = document.querySelector('.bw-account-auth');

        if (authWrapper) {
            initAuthTabs(authWrapper);
        }

        if (providerWrapper) {
            var providerTabs = Array.prototype.slice.call(providerWrapper.querySelectorAll('[data-bw-provider-tab]'));
            var providerPanels = Array.prototype.slice.call(providerWrapper.querySelectorAll('[data-bw-provider-panel]'));

            var activateProvider = function (target) {
                providerTabs.forEach(function (tab) {
                    var isActive = tab.getAttribute('data-bw-provider-tab') === target;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    tab.setAttribute('tabindex', isActive ? '0' : '-1');
                });

                providerPanels.forEach(function (panel) {
                    var isTarget = panel.getAttribute('data-bw-provider-panel') === target;

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

            providerTabs.forEach(function (tab) {
                tab.addEventListener('click', function (event) {
                    event.preventDefault();
                    var target = tab.getAttribute('data-bw-provider-tab');
                    if (!target) {
                        return;
                    }
                    activateProvider(target);
                });
            });

            var defaultProvider = providerWrapper.getAttribute('data-bw-provider-default') || (window.bwAccountAuth && window.bwAccountAuth.defaultProvider) || 'wordpress';
            activateProvider(defaultProvider);
        }

        var supabaseForm = document.querySelector('[data-bw-supabase-form]');
        if (supabaseForm && window.bwAccountAuth) {
            var submitButton = supabaseForm.querySelector('[data-bw-supabase-submit]');
            var errorBox = supabaseForm.querySelector('.bw-account-login__error');

            supabaseForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!window.bwAccountAuth.ajaxUrl || !window.bwAccountAuth.nonce) {
                    return;
                }

                if (errorBox) {
                    errorBox.textContent = '';
                    errorBox.hidden = true;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                }

                var formData = new FormData(supabaseForm);
                formData.append('action', 'bw_supabase_login');
                formData.append('nonce', window.bwAccountAuth.nonce);

                fetch(window.bwAccountAuth.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams(formData)
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload && payload.success && payload.data && payload.data.redirect) {
                            window.location.href = payload.data.redirect;
                            return;
                        }

                        var message = (payload && payload.data && payload.data.message) ? payload.data.message : 'Unable to login.';
                        if (errorBox) {
                            errorBox.textContent = message;
                            errorBox.hidden = false;
                        }
                    })
                    .catch(function () {
                        if (errorBox) {
                            errorBox.textContent = 'Unable to login.';
                            errorBox.hidden = false;
                        }
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
            });
        }
    });
})();
