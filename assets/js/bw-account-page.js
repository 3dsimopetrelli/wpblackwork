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

        var authWrapper = document.querySelector('.bw-account-auth');

        if (authWrapper) {
            initAuthTabs(authWrapper);

            var emailConfirmed = authWrapper.getAttribute('data-bw-email-confirmed');
            if (emailConfirmed === '1') {
                var loginTab = authWrapper.querySelector('[data-bw-auth-tab="login"]');
                if (loginTab) {
                    loginTab.click();
                }
            }
        }

        var autoLoginEnabled = window.bwAccountAuth && window.bwAccountAuth.autoLoginAfterConfirm;
        var hash = window.location.hash || '';
        if (autoLoginEnabled && hash.indexOf('access_token=') !== -1 && window.bwAccountAuth && window.bwAccountAuth.ajaxUrl) {
            var params = new URLSearchParams(hash.replace(/^#/, ''));
            var accessToken = params.get('access_token');

            if (accessToken) {
                fetch(window.bwAccountAuth.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                    },
                    body: new URLSearchParams({
                        action: 'bw_supabase_token_login',
                        nonce: window.bwAccountAuth.nonce,
                        access_token: accessToken
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload && payload.success && payload.data && payload.data.redirect) {
                            window.location.replace(payload.data.redirect);
                            return;
                        }
                    })
                    .finally(function () {
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({}, document.title, window.location.pathname + window.location.search);
                        }
                    });
            }
        }

        // Handle Supabase login/register/recover forms (single provider view).
        var supabaseForms = Array.prototype.slice.call(document.querySelectorAll('[data-bw-supabase-form]'));
        if (supabaseForms.length && window.bwAccountAuth) {
            supabaseForms.forEach(function (supabaseForm) {
                var submitButton = supabaseForm.querySelector('[data-bw-supabase-submit]');
                var errorBox = supabaseForm.querySelector('.bw-account-login__error');
                var successBox = supabaseForm.querySelector('.bw-account-login__success');

                supabaseForm.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (!window.bwAccountAuth.ajaxUrl || !window.bwAccountAuth.nonce) {
                        return;
                    }

                    if (errorBox) {
                        errorBox.textContent = '';
                        errorBox.hidden = true;
                    }

                    if (successBox) {
                        successBox.textContent = '';
                        successBox.hidden = true;
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    var formData = new FormData(supabaseForm);
                    var actionType = supabaseForm.getAttribute('data-bw-supabase-action');
                    var action = 'bw_supabase_login';

                    if (actionType === 'register') {
                        action = 'bw_supabase_register';
                    } else if (actionType === 'recover') {
                        action = 'bw_supabase_recover';
                    }

                    if (actionType === 'login' && window.bwAccountAuth.supabaseWithOidc) {
                        if (window.bwAccountAuth.oidcAuthUrl) {
                            window.location.href = window.bwAccountAuth.oidcAuthUrl;
                            return;
                        }

                        if (errorBox) {
                            errorBox.textContent = 'OIDC auth URL is not configured.';
                            errorBox.hidden = false;
                        }

                        if (submitButton) {
                            submitButton.disabled = false;
                        }

                        return;
                    }

                    formData.append('action', action);
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

                            if (payload && payload.success && payload.data && payload.data.message) {
                                if (successBox) {
                                    successBox.textContent = payload.data.message;
                                    successBox.hidden = false;
                                }
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
            });
        }
    });
})();
