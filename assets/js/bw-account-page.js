(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var authConfig = window.bwAccountAuth || {};
        var cookieBase = authConfig.cookieBase || 'bw_supabase_session';
        var searchParams = new URLSearchParams(window.location.search);
        var loggedOutParam = searchParams.has('logged_out');
        var skipAuthHandlers = false;
        var debugEnabled = Boolean(authConfig.debug);
        var shouldSignOutSupabase = loggedOutParam;
        var reloadGuardKey = 'bw_account_reload_guard';
        var bridgeAttemptKey = 'bw_supabase_bridge_attempted';
        var redirectGuardKey = 'bw_bridge_redirected';
        var recoveryHandledKey = 'bw_recovery_handled';
        var logDebug = function (message, context) {
            if (!debugEnabled) {
                return;
            }
            if (context) {
                console.log('[bw]', message, context);
                return;
            }
            console.log('[bw]', message);
        };

        var clearCookie = function (name) {
            document.cookie = name + '=; Max-Age=0; path=/; SameSite=Lax';
        };

        var getSessionStorageItem = function (key) {
            if (!window.sessionStorage) {
                return '';
            }
            try {
                return window.sessionStorage.getItem(key) || '';
            } catch (error) {
                return '';
            }
        };

        var setSessionStorageItem = function (key, value) {
            if (!window.sessionStorage) {
                return;
            }
            try {
                if (value) {
                    window.sessionStorage.setItem(key, value);
                } else {
                    window.sessionStorage.removeItem(key);
                }
            } catch (error) {
                // ignore sessionStorage errors
            }
        };

        var clearAuthStorage = function () {
            if (window.localStorage) {
                try {
                    localStorage.removeItem('bw_pending_otp_email');
                    localStorage.removeItem('bw_onboarded');
                } catch (error) {
                    // ignore localStorage errors
                }
            }

            if (window.sessionStorage) {
                try {
                    sessionStorage.removeItem('bw_pending_otp_email');
                    sessionStorage.removeItem('bw_handled_supabase_hash');
                    sessionStorage.removeItem('bw_handled_email_confirm');
                    sessionStorage.removeItem('bw_handled_token_login');
                    sessionStorage.removeItem('bw_handled_session_check');
                    sessionStorage.removeItem('bw_supabase_access_token');
                    sessionStorage.removeItem('bw_supabase_refresh_token');
                    sessionStorage.removeItem('bw_oauth_bridge_done');
                    sessionStorage.removeItem(redirectGuardKey);
                    sessionStorage.removeItem(recoveryHandledKey);
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            clearCookie(cookieBase + '_access');
            clearCookie(cookieBase + '_refresh');
        };

        var clearLoggedOutParam = function () {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            var loggedOutUrl = new URL(window.location.href);
            loggedOutUrl.searchParams.delete('logged_out');
            window.history.replaceState(null, document.title, loggedOutUrl.pathname + (loggedOutUrl.search ? loggedOutUrl.search : '') + (loggedOutUrl.hash ? loggedOutUrl.hash : ''));
        };

        var cleanAuthUrl = function (removeParams) {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            var url = new URL(window.location.href);
            url.hash = '';
            if (removeParams && url.searchParams) {
                removeParams.forEach(function (param) {
                    url.searchParams.delete(param);
                });
            }
            window.history.replaceState(null, document.title, url.pathname + (url.search ? url.search : ''));
            if (debugEnabled) {
                console.log('[bw] Auth URL cleaned');
            }
        };

        var updateReloadGuard = function () {
            if (!window.sessionStorage) {
                return false;
            }
            try {
                var now = Date.now();
                var state = {};
                var rawState = sessionStorage.getItem(reloadGuardKey);
                if (rawState) {
                    state = JSON.parse(rawState) || {};
                }
                var last = state.last || 0;
                var count = state.count || 0;
                if (now - last < 3000) {
                    count += 1;
                } else {
                    count = 1;
                }
                sessionStorage.setItem(reloadGuardKey, JSON.stringify({ last: now, count: count }));
                return count > 1;
            } catch (error) {
                return false;
            }
        };

        var resetReloadGuard = function () {
            if (!window.sessionStorage) {
                return;
            }
            try {
                sessionStorage.removeItem(reloadGuardKey);
            } catch (error) {
                // ignore sessionStorage errors
            }
        };

        var setBridgeAttempted = function (value) {
            setSessionStorageItem(bridgeAttemptKey, value ? '1' : '');
        };

        var hasBridgeAttempted = function () {
            return getSessionStorageItem(bridgeAttemptKey) === '1';
        };

        if (updateReloadGuard()) {
            skipAuthHandlers = true;
            logDebug('Auto-login aborted', { reason: 'reload_guard' });
        }

        if (loggedOutParam) {
            clearAuthStorage();
            clearLoggedOutParam();
            skipAuthHandlers = true;
            setBridgeAttempted(false);
            resetReloadGuard();
        }

        if (document.body.classList.contains('logged-in')) {
            if (window.sessionStorage) {
                try {
                    sessionStorage.removeItem('bw_oauth_bridge_done');
                    sessionStorage.removeItem(redirectGuardKey);
                    sessionStorage.removeItem(recoveryHandledKey);
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }
        }

        var setFieldsState = function (container, isEnabled) {
            if (!container) {
                return;
            }
            var fields = Array.prototype.slice.call(container.querySelectorAll('input, select, textarea, button'));
            fields.forEach(function (field) {
                if (isEnabled) {
                    field.disabled = false;
                    if (field.dataset && field.dataset.bwRequired === '1') {
                        field.required = true;
                    }
                    return;
                }

                if (field.required) {
                    field.dataset.bwRequired = '1';
                    field.required = false;
                }
                field.disabled = true;
            });
        };

        var initAuthTabs = function (authWrapper) {
            var tabs = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-tab]'));
            var panels = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-auth-panel]'));

            if (!tabs.length || !panels.length) {
                return;
            }

            var setPanelState = function (panel, isActive) {
                if (!panel) {
                    return;
                }
                if (!isActive) {
                    setFieldsState(panel, false);
                    return;
                }

                var steps = panel.querySelectorAll('[data-bw-register-step]');
                if (!steps.length) {
                    setFieldsState(panel, true);
                    return;
                }

                Array.prototype.slice.call(steps).forEach(function (step) {
                    var stepActive = step.classList.contains('is-active');
                    setFieldsState(step, stepActive);
                });
            };

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
                    setPanelState(panel, isTarget);
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

        var hasRecoveryContext = function () {
            try {
                var hashValue = window.location.hash || '';
                var searchValue = window.location.search || '';
                if (hashValue.indexOf('type=recovery') !== -1 || hashValue.indexOf('access_token=') !== -1 || hashValue.indexOf('refresh_token=') !== -1) {
                    return true;
                }
                if (searchValue.indexOf('code=') !== -1) {
                    return true;
                }
            } catch (error) {
                return false;
            }
            return false;
        };

        if (authWrapper) {
            initAuthTabs(authWrapper);

            if (!skipAuthHandlers) {
                var emailConfirmed = authWrapper.getAttribute('data-bw-email-confirmed');
                if (emailConfirmed === '1') {
                    var loginTab = authWrapper.querySelector('[data-bw-auth-tab="login"]');
                    if (loginTab) {
                        loginTab.click();
                    }
                    if (getSessionStorageItem('bw_handled_email_confirm') !== '1') {
                        setSessionStorageItem('bw_handled_email_confirm', '1');
                        cleanAuthUrl(['bw_email_confirmed', 'code']);
                    }
                }
            }
        }

        if (!skipAuthHandlers && (hasRecoveryContext() || hasRecoveryContextFallback()) && !recoveryAlreadyHandled()) {
            markRecoveryHandled();
            switchAuthScreen('set-password');
        }

        if (!skipAuthHandlers) {
            var hash = window.location.hash || '';
            if (hash.indexOf('access_token=') !== -1 && authConfig.ajaxUrl) {
                if (getSessionStorageItem('bw_handled_supabase_hash') === '1') {
                    cleanAuthUrl(['access_token', 'refresh_token', 'type', 'code', 'bw_email_confirmed']);
                } else {
                    var params = new URLSearchParams(hash.replace(/^#/, ''));
                    var accessToken = params.get('access_token');
                    var refreshToken = params.get('refresh_token');
                    var authType = params.get('type');
                    var autoLoginAfterConfirm = Boolean(authConfig.autoLoginAfterConfirm);
                    var shouldHandleToken = authType !== 'invite' && (authType !== 'signup' || autoLoginAfterConfirm);
                    if (authType === 'recovery') {
                        cleanAuthUrl(['access_token', 'refresh_token', 'type', 'code', 'bw_email_confirmed']);
                        shouldHandleToken = false;
                    }

                    if (accessToken && shouldHandleToken) {
                        setSessionStorageItem('bw_handled_supabase_hash', '1');
                        setSessionStorageItem('bw_handled_token_login', '1');
                        fetch(authConfig.ajaxUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                            },
                            body: new URLSearchParams({
                                action: 'bw_supabase_token_login',
                                nonce: authConfig.nonce,
                                access_token: accessToken,
                                refresh_token: refreshToken || '',
                                type: authType || ''
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
                                cleanAuthUrl(['access_token', 'refresh_token', 'type', 'code', 'bw_email_confirmed']);
                            });
                    }
                }
            }
        }

        var projectUrl = authConfig.projectUrl || '';
        var anonKey = authConfig.anonKey || '';
        var magicLinkRedirect = authConfig.magicLinkRedirectUrl || '';
        var oauthRedirect = authConfig.oauthRedirectUrl || '';
        var signupRedirect = authConfig.signupRedirectUrl || '';
        var resetRedirect = authConfig.resetRedirectUrl || magicLinkRedirect || window.location.origin + '/my-account/';
        var magicLinkEnabled = authConfig.magicLinkEnabled !== undefined ? Boolean(authConfig.magicLinkEnabled) : true;
        var oauthGoogleEnabled = authConfig.oauthGoogleEnabled !== undefined ? Boolean(authConfig.oauthGoogleEnabled) : true;
        var oauthFacebookEnabled = authConfig.oauthFacebookEnabled !== undefined ? Boolean(authConfig.oauthFacebookEnabled) : true;
        var oauthAppleEnabled = authConfig.oauthAppleEnabled !== undefined ? Boolean(authConfig.oauthAppleEnabled) : false;
        var passwordLoginEnabled = authConfig.passwordLoginEnabled !== undefined ? Boolean(authConfig.passwordLoginEnabled) : true;
        var registerPromptEnabled = authConfig.registerPromptEnabled !== undefined ? Boolean(authConfig.registerPromptEnabled) : true;
        var registrationMode = authConfig.registrationMode || 'R2';
        var messages = authConfig.messages || {};

        var magicLinkForm = document.querySelector('[data-bw-supabase-form][data-bw-supabase-action="magic-link"]');
        var passwordLoginForm = document.querySelector('[data-bw-supabase-form][data-bw-supabase-action="password-login"]');
        var resetPasswordForm = document.querySelector('[data-bw-reset-form]');
        var setPasswordForm = document.querySelector('[data-bw-set-password-form]');
        var oauthButtons = Array.prototype.slice.call(document.querySelectorAll('[data-bw-oauth-provider]'));
        var registerForm = document.querySelector('[data-bw-register-form]');
        var registerContinue = registerForm ? registerForm.querySelector('[data-bw-register-continue]') : null;
        var registerSubmit = registerForm ? registerForm.querySelector('[data-bw-register-submit]') : null;
        var otpForm = document.querySelector('[data-bw-otp-form]');
        var otpInputs = otpForm ? Array.prototype.slice.call(otpForm.querySelectorAll('[data-bw-otp-digit]')) : [];
        var otpInputsWrap = otpForm ? otpForm.querySelector('[data-bw-otp-inputs]') : null;
        var otpConfirmButton = otpForm ? otpForm.querySelector('[data-bw-otp-confirm]') : null;
        var otpResendButton = otpForm ? otpForm.querySelector('[data-bw-otp-resend]') : null;
        var otpEmailText = otpForm ? otpForm.querySelector('[data-bw-otp-email]') : null;
        var authScreens = Array.prototype.slice.call(document.querySelectorAll('[data-bw-screen]'));
        var goPasswordButton = document.querySelector('[data-bw-go-password]');
        var goResetPasswordButton = document.querySelector('[data-bw-go-reset-password]');
        var resetRules = setPasswordForm ? Array.prototype.slice.call(setPasswordForm.querySelectorAll('[data-bw-reset-rule]')) : [];
        var resetPasswordSubmit = setPasswordForm ? setPasswordForm.querySelector('[data-bw-setpass-submit]') : null;
        var resetPasswordInput = setPasswordForm ? setPasswordForm.querySelector('input[name="new_password"]') : null;
        var resetPasswordConfirm = setPasswordForm ? setPasswordForm.querySelector('input[name="confirm_password"]') : null;
        var goMagicButtons = Array.prototype.slice.call(document.querySelectorAll('[data-bw-go-magic]'));
        var getMessage = function (key, fallback) {
            if (messages && Object.prototype.hasOwnProperty.call(messages, key)) {
                return messages[key];
            }
            return fallback;
        };

        var showFormMessage = function (form, type, message) {
            if (!form) {
                return;
            }
            var errorBox = form.querySelector('.bw-account-login__error');
            var successBox = form.querySelector('.bw-account-login__success');

            if (errorBox) {
                errorBox.hidden = type !== 'error';
                errorBox.textContent = type === 'error' ? message : '';
            }

            if (successBox) {
                successBox.hidden = type !== 'success';
                successBox.textContent = type === 'success' ? message : '';
            }
        };

        var resetPasswordRules = [
            { id: 'length', test: function (value) { return value.length >= 8; } },
            { id: 'upper', test: function (value) { return /[A-Z]/.test(value); } },
            { id: 'number', test: function (value) { return /[0-9]|[^A-Za-z0-9]/.test(value); } }
        ];

        var updateResetRules = function () {
            if (!resetPasswordInput || !resetRules.length) {
                return true;
            }
            var value = resetPasswordInput.value || '';
            var allPass = true;
            resetPasswordRules.forEach(function (rule) {
                var passes = rule.test(value);
                var ruleItem = resetRules.find(function (item) {
                    return item.dataset.bwResetRule === rule.id;
                });
                if (ruleItem) {
                    ruleItem.classList.toggle('is-valid', passes);
                }
                if (!passes) {
                    allPass = false;
                }
            });
            return allPass;
        };

        var updateResetSubmitState = function () {
            if (!resetPasswordSubmit) {
                return;
            }
            var rulesPass = updateResetRules();
            var newValue = resetPasswordInput ? resetPasswordInput.value.trim() : '';
            var confirmValue = resetPasswordConfirm ? resetPasswordConfirm.value.trim() : '';
            resetPasswordSubmit.disabled = !(rulesPass && newValue && confirmValue && newValue === confirmValue);
        };

        var getCodeVerifier = function () {
            if (!window.localStorage) {
                return '';
            }
            try {
                var direct = localStorage.getItem('supabase.auth.code_verifier');
                if (direct) {
                    return direct;
                }
                for (var i = 0; i < localStorage.length; i += 1) {
                    var key = localStorage.key(i);
                    if (key && key.indexOf('code_verifier') !== -1) {
                        var value = localStorage.getItem(key);
                        if (value) {
                            return value;
                        }
                    }
                }
            } catch (error) {
                // ignore localStorage errors
            }
            return '';
        };

        // Guarded recovery detection: avoid hard crashes that disable auth UI.
        var hasRecoveryContextFallback = function () {
            try {
                var hash = window.location.hash.replace(/^#/, '');
                var hashParams = hash ? new URLSearchParams(hash) : null;
                var type = hashParams ? hashParams.get('type') : '';
                var accessToken = hashParams ? hashParams.get('access_token') : '';
                var refreshToken = hashParams ? hashParams.get('refresh_token') : '';
                var codeParam = searchParams.get('code') || '';

                if (type === 'recovery' || (accessToken && refreshToken)) {
                    return true;
                }

                if (codeParam) {
                    return true;
                }
            } catch (error) {
                return false;
            }
            return false;
        };

        var markRecoveryHandled = function () {
            setSessionStorageItem(recoveryHandledKey, '1');
        };

        var recoveryAlreadyHandled = function () {
            return getSessionStorageItem(recoveryHandledKey) === '1';
        };

        var setStepState = function (step, isActive) {
            setFieldsState(step, isActive);
        };

        var cleanupAuthState = function () {
            setPendingOtpEmail('');
            clearAuthStorage();
        };

        var clearOtpPendingState = function () {
            setPendingOtpEmail('');
            setSessionStorageItem('bw_pending_otp_email', '');
            setSessionStorageItem('bw_handled_supabase_hash', '');
        };

        var supabaseClient = null;
        var pendingOtpEmail = '';
        var pendingOtpKey = 'bw_pending_otp_email';
        var getSupabaseClient = function () {
            if (supabaseClient) {
                return supabaseClient;
            }
            if (!projectUrl || !anonKey) {
                return null;
            }
            if (!window.supabase || typeof window.supabase.createClient !== 'function') {
                if (debugEnabled) {
                    console.warn(getMessage('supabaseSdkMissing', 'Supabase JS SDK is not loaded.'));
                }
                return null;
            }
            supabaseClient = window.supabase.createClient(projectUrl, anonKey);
            return supabaseClient;
        };

        if (shouldSignOutSupabase) {
            var supabaseForLogout = getSupabaseClient();
            if (supabaseForLogout) {
                supabaseForLogout.auth.signOut().then(function (response) {
                    if (response && response.error) {
                        logDebug('Supabase signOut failed', { message: response.error.message || 'unknown' });
                        return;
                    }
                    logDebug('Supabase signOut success');
                });
            }
        }

        var requestOtp = function (email) {
            var supabase = getSupabaseClient();
            var redirectTo = magicLinkRedirect || window.location.origin + '/my-account/';
            var shouldCreateUser = registrationMode === 'R2';

            if (supabase) {
                return supabase.auth.signInWithOtp({
                    email: email,
                    options: {
                        emailRedirectTo: redirectTo,
                        shouldCreateUser: shouldCreateUser
                    }
                });
            }

            if (!projectUrl || !anonKey) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            return fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/otp', {
                method: 'POST',
                headers: {
                    apikey: anonKey,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    create_user: shouldCreateUser,
                    options: {
                        email_redirect_to: redirectTo
                    }
                })
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('magicLinkError', 'Unable to send magic link.');
                        throw new Error(message);
                    });
                }
                return { data: {} };
            }).catch(function (error) {
                return { error: error };
            });
        };

        var verifyOtp = function (email, code) {
            var supabase = getSupabaseClient();
            if (supabase) {
                return supabase.auth.verifyOtp({
                    email: email,
                    token: code,
                    type: 'email'
                });
            }

            if (!projectUrl || !anonKey) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            return fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/verify', {
                method: 'POST',
                headers: {
                    apikey: anonKey,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    token: code,
                    type: 'email'
                })
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('otpVerifyError', 'Unable to verify the code.');
                        throw new Error(message);
                    });
                }
                return response.json();
            }).then(function (payload) {
                return { data: payload };
            }).catch(function (error) {
                return { error: error };
            });
        };

        var setPendingOtpEmail = function (email) {
            pendingOtpEmail = email;
            if (otpEmailText) {
                otpEmailText.textContent = email ? email : '';
            }
            if (window.localStorage) {
                try {
                    if (email) {
                        window.localStorage.setItem(pendingOtpKey, email);
                    } else {
                        window.localStorage.removeItem(pendingOtpKey);
                    }
                } catch (error) {
                    logDebug('Unable to access localStorage', error);
                }
            }
        };

        var getPendingOtpEmail = function () {
            if (pendingOtpEmail) {
                return pendingOtpEmail;
            }
            if (window.localStorage) {
                try {
                    pendingOtpEmail = window.localStorage.getItem(pendingOtpKey) || '';
                } catch (error) {
                    logDebug('Unable to access localStorage', error);
                }
            }
            if (otpEmailText) {
                otpEmailText.textContent = pendingOtpEmail ? pendingOtpEmail : '';
            }
            return pendingOtpEmail;
        };

        var getOtpCode = function () {
            if (!otpInputs.length) {
                return '';
            }
            return otpInputs.map(function (input) {
                var digit = input.value.replace(/\D/g, '');
                return digit ? digit.charAt(0) : '';
            }).join('');
        };

        var setOtpInputsError = function (hasError) {
            if (!otpInputsWrap) {
                return;
            }
            otpInputsWrap.classList.toggle('is-error', hasError);
        };

        var updateOtpState = function () {
            if (!otpConfirmButton) {
                return;
            }
            var code = getOtpCode();
            var isValid = code.length === 6 && /^\d{6}$/.test(code);
            otpConfirmButton.disabled = !isValid;
            if (debugEnabled) {
                console.log('[bw] OTP state', { length: code.length, enabled: isValid });
            }
        };

        var setScreenState = function (screen, isActive) {
            if (!screen) {
                return;
            }
            setFieldsState(screen, isActive);
            if (isActive) {
                screen.classList.add('is-visible');
                screen.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        screen.classList.add('is-active');
                    });
                });
                return;
            }

            screen.classList.remove('is-active');
            screen.setAttribute('aria-hidden', 'true');
            setTimeout(function () {
                screen.classList.remove('is-visible');
            }, 300);
        };

        var switchAuthScreen = function (target) {
            if (!authScreens.length) {
                return;
            }

            authScreens.forEach(function (screen) {
                var isTarget = screen.getAttribute('data-bw-screen') === target;
                setScreenState(screen, isTarget);
            });

            if (target === 'password' && passwordLoginForm) {
                var passwordEmail = passwordLoginForm.querySelector('input[type="email"]');
                if (passwordEmail) {
                    passwordEmail.focus();
                }
            }

            if (target === 'magic' && passwordLoginForm) {
                showFormMessage(passwordLoginForm, 'error', '');
                showFormMessage(passwordLoginForm, 'success', '');
            }

            if (target === 'magic') {
                if (otpForm) {
                    showFormMessage(otpForm, 'error', '');
                    showFormMessage(otpForm, 'success', '');
                    setOtpInputsError(false);
                }
            }

            if (target === 'otp') {
                if (otpInputs.length) {
                    otpInputs[0].focus();
                }
                updateOtpState();
            }

            if (target === 'set-password') {
                updateResetSubmitState();
            }
        };

        var supabaseSessionRedirect = function () {
            cleanupAuthState();
            window.location.href = magicLinkRedirect || window.location.origin + '/my-account/';
        };

        var bridgeSupabaseSession = function (accessToken, refreshToken, context) {
            if (!window.bwAccountAuth || !window.bwAccountAuth.ajaxUrl || !window.bwAccountAuth.nonce) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            if (!accessToken) {
                return Promise.resolve({ error: new Error(getMessage('otpVerifyError', 'Unable to verify the code.')) });
            }

            if (debugEnabled) {
                console.log('[bw] Supabase token bridge', { context: context || 'otp' });
            }

            return fetch(window.bwAccountAuth.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'bw_supabase_token_login',
                    nonce: window.bwAccountAuth.nonce,
                    access_token: accessToken,
                    refresh_token: refreshToken || '',
                    type: context || 'otp'
                })
            })
                .then(function (response) {
                    logDebug('WP bridge response', { status: response.status, context: context || 'otp' });
                    return response.json();
                })
                .then(function (payload) {
                    cleanAuthUrl(['access_token', 'refresh_token', 'type', 'code', 'bw_email_confirmed']);
                    if (payload && payload.success && payload.data && payload.data.redirect) {
                        setBridgeAttempted(false);
                        resetReloadGuard();
                        window.location.href = payload.data.redirect;
                        return payload;
                    }
                    window.location.href = magicLinkRedirect || window.location.origin + '/my-account/';
                    return payload;
                });
        };

        var checkExistingSession = function () {
            if (getSessionStorageItem('bw_handled_session_check') === '1') {
                return;
            }
            var supabase = getSupabaseClient();
            if (!supabase) {
                return;
            }
            setSessionStorageItem('bw_handled_session_check', '1');
            if (hasBridgeAttempted()) {
                logDebug('Auto-bridge skipped', { reason: 'already_attempted' });
                return;
            }
            supabase.auth.getSession().then(function (response) {
                if (response && response.data && response.data.session) {
                    logDebug('Supabase session found', { autoBridge: true });
                    setBridgeAttempted(true);
                    return bridgeSupabaseSession(
                        response.data.session.access_token || '',
                        response.data.session.refresh_token || '',
                        'session'
                    ).catch(function (error) {
                        logDebug('Auto-bridge failed', { message: error && error.message ? error.message : 'unknown' });
                        switchAuthScreen('magic');
                    });
                }
                logDebug('Auto-bridge skipped', { reason: 'no_session' });
                switchAuthScreen('magic');
                return null;
            });
        };

        if (debugEnabled) {
            logDebug('Auth UI elements', {
                magicLinkForm: Boolean(magicLinkForm),
                passwordLoginForm: Boolean(passwordLoginForm),
                registerForm: Boolean(registerForm),
                registerContinue: Boolean(registerContinue),
                registerSubmit: Boolean(registerSubmit),
                authScreens: authScreens.length
            });
        }

        var submitAjaxForm = function (form, action, options) {
            if (!form) {
                return;
            }

            var submitButton = form.querySelector('[data-bw-supabase-submit]');
            var defaultError = options && options.defaultError ? options.defaultError : getMessage('loginError', 'Unable to login.');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (!window.bwAccountAuth || !window.bwAccountAuth.ajaxUrl || !window.bwAccountAuth.nonce) {
                    showFormMessage(form, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var emailField = form.querySelector('input[type="email"]');
                var passwordField = form.querySelector('input[type="password"]');
                logDebug('Supabase form submit', {
                    action: action,
                    hasEmail: Boolean(emailField && emailField.value.trim()),
                    hasPassword: Boolean(passwordField && passwordField.value)
                });

                var formData = new FormData(form);
                formData.append('action', action);
                formData.append('nonce', window.bwAccountAuth.nonce);

                showFormMessage(form, 'error', '');
                showFormMessage(form, 'success', '');

                if (submitButton) {
                    submitButton.disabled = true;
                }

                fetch(window.bwAccountAuth.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams(formData)
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        logDebug('Supabase form response', {
                            success: Boolean(payload && payload.success),
                            keys: payload && payload.data ? Object.keys(payload.data) : []
                        });
                        if (payload && payload.success) {
                            if (payload.data && payload.data.redirect) {
                                window.location.href = payload.data.redirect;
                                return;
                            }
                            if (payload.data && payload.data.message) {
                                showFormMessage(form, 'success', payload.data.message);
                                return;
                            }
                        }

                        var message = payload && payload.data && payload.data.message ? payload.data.message : defaultError;
                        showFormMessage(form, 'error', message);
                    })
                    .catch(function () {
                        showFormMessage(form, 'error', defaultError);
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
            });
        };

        if (magicLinkForm) {
            magicLinkForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setBridgeAttempted(false);
                resetReloadGuard();
                if (!magicLinkEnabled) {
                    return;
                }
                if (!projectUrl || !anonKey) {
                    showFormMessage(magicLinkForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var supabase = getSupabaseClient();
                if (!supabase) {
                    showFormMessage(magicLinkForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var emailField = magicLinkForm.querySelector('input[name="email"]');
                var emailValue = emailField ? emailField.value.trim() : '';
                if (!emailValue) {
                    showFormMessage(magicLinkForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }

                showFormMessage(magicLinkForm, 'success', '');
                showFormMessage(magicLinkForm, 'error', '');

                logDebug('Supabase OTP request', { redirect_to: magicLinkRedirect });

                // Supabase email templates control OTP vs link ({{ .Token }} vs {{ .ConfirmationURL }}).
                requestOtp(emailValue)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        setPendingOtpEmail(emailValue);
                        switchAuthScreen('otp');
                        showFormMessage(otpForm || magicLinkForm, 'success', getMessage('otpSent', 'Check your email for the 6-digit code.'));
                    })
                    .catch(function (error) {
                        showFormMessage(magicLinkForm, 'error', error.message || getMessage('magicLinkError', 'Unable to send magic link.'));
                    });
            });
        }

        if (resetPasswordInput) {
            resetPasswordInput.addEventListener('input', updateResetSubmitState);
        }

        if (resetPasswordConfirm) {
            resetPasswordConfirm.addEventListener('input', updateResetSubmitState);
        }

        updateResetSubmitState();

        if (goResetPasswordButton) {
            goResetPasswordButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (resetPasswordForm) {
                    showFormMessage(resetPasswordForm, 'error', '');
                    showFormMessage(resetPasswordForm, 'success', '');
                }
                switchAuthScreen('reset-password');
            });
        }

        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (!projectUrl || !anonKey) {
                    showFormMessage(resetPasswordForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var emailField = resetPasswordForm.querySelector('input[type="email"]');
                var emailValue = emailField ? emailField.value.trim() : '';
                if (!emailValue) {
                    showFormMessage(resetPasswordForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }

                var submitButton = resetPasswordForm.querySelector('[data-bw-reset-send]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                showFormMessage(resetPasswordForm, 'error', '');
                showFormMessage(resetPasswordForm, 'success', '');

                var supabase = getSupabaseClient();
                var request;

                if (supabase) {
                    logDebug('Supabase reset request', { method: 'sdk' });
                    request = supabase.auth.resetPasswordForEmail(emailValue, { redirectTo: resetRedirect });
                } else {
                    logDebug('Supabase reset request', { method: 'rest' });
                    request = fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/recover', {
                        method: 'POST',
                        headers: {
                            apikey: anonKey,
                            'Content-Type': 'application/json',
                            Accept: 'application/json'
                        },
                        body: JSON.stringify({
                            email: emailValue,
                            redirect_to: resetRedirect
                        })
                    }).then(function (response) {
                        if (!response.ok) {
                            return response.json().then(function (payload) {
                                var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('resetEmailError', 'Unable to send reset email.');
                                throw new Error(message);
                            });
                        }
                        return { data: {} };
                    });
                }

                Promise.resolve(request)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        showFormMessage(resetPasswordForm, 'success', getMessage('resetEmailSent', 'If an account exists for this email, you’ll receive a reset link shortly.'));
                    })
                    .catch(function () {
                        showFormMessage(resetPasswordForm, 'error', getMessage('resetEmailError', 'Unable to send reset email.'));
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
            });
        }

        if (passwordLoginEnabled && passwordLoginForm) {
            passwordLoginForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setBridgeAttempted(false);
                resetReloadGuard();

                if (!projectUrl || !anonKey) {
                    showFormMessage(passwordLoginForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var emailField = passwordLoginForm.querySelector('input[type="email"]');
                var passwordField = passwordLoginForm.querySelector('input[type="password"]');
                var emailValue = emailField ? emailField.value.trim() : '';
                var passwordValue = passwordField ? passwordField.value : '';

                if (!emailValue || !passwordValue) {
                    showFormMessage(passwordLoginForm, 'error', getMessage('loginError', 'Invalid email or password.'));
                    return;
                }

                var submitButton = passwordLoginForm.querySelector('[data-bw-supabase-submit]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                showFormMessage(passwordLoginForm, 'error', '');
                showFormMessage(passwordLoginForm, 'success', '');

                var bridgePasswordSession = function (accessToken, refreshToken) {
                    if (!authConfig.ajaxUrl || !authConfig.nonce) {
                        return Promise.reject(new Error(getMessage('missingConfig', 'Supabase configuration is missing.')));
                    }

                    logDebug('WP bridge start', { type: 'password' });

                    return fetch(authConfig.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                        },
                        body: new URLSearchParams({
                            action: 'bw_supabase_token_login',
                            nonce: authConfig.nonce,
                            access_token: accessToken,
                            refresh_token: refreshToken || '',
                            type: 'password'
                        })
                    })
                        .then(function (response) {
                            logDebug('WP bridge response', { status: response.status });
                            return response.json().then(function (payload) {
                                return { payload: payload, status: response.status };
                            });
                        })
                        .then(function (result) {
                            var payload = result.payload;
                            if (payload && payload.success) {
                                if (payload.data && payload.data.redirect) {
                                    window.location.href = payload.data.redirect;
                                    return;
                                }
                                window.location.href = window.location.origin + '/my-account/';
                                return;
                            }
                            var message = payload && payload.data && payload.data.message ? payload.data.message : getMessage('loginError', 'Unable to login.');
                            throw new Error(message);
                        });
                };

                var supabase = getSupabaseClient();
                var loginPromise;

                if (supabase) {
                    logDebug('Supabase password login', { method: 'sdk' });
                    loginPromise = supabase.auth.signInWithPassword({
                        email: emailValue,
                        password: passwordValue
                    });
                } else {
                    logDebug('Supabase password login', { method: 'rest' });
                    loginPromise = fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/token?grant_type=password', {
                        method: 'POST',
                        headers: {
                            apikey: anonKey,
                            'Content-Type': 'application/json',
                            Accept: 'application/json'
                        },
                        body: JSON.stringify({
                            email: emailValue,
                            password: passwordValue
                        })
                    }).then(function (response) {
                        logDebug('Supabase REST login response', { status: response.status });
                        if (!response.ok) {
                            return response.json().then(function (payload) {
                                var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('loginError', 'Invalid email or password.');
                                throw new Error(message);
                            });
                        }
                        return response.json().then(function (payload) {
                            return { data: { session: payload } };
                        });
                    });
                }

                Promise.resolve(loginPromise)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        var session = response && response.data ? response.data.session : null;
                        if (!session) {
                            throw new Error(getMessage('loginError', 'Unable to login.'));
                        }
                        logDebug('Supabase password login success', { hasSession: true });
                        return bridgePasswordSession(session.access_token || '', session.refresh_token || '');
                    })
                    .catch(function (error) {
                        logDebug('Password login failed', { message: error && error.message ? error.message : 'unknown' });
                        showFormMessage(passwordLoginForm, 'error', (error && error.message) ? error.message : getMessage('loginError', 'Invalid email or password.'));
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
            });
        }

        if (setPasswordForm) {
            var setPasswordSubmit = function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (!projectUrl || !anonKey) {
                    showFormMessage(setPasswordForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var newValue = resetPasswordInput ? resetPasswordInput.value.trim() : '';
                var confirmValue = resetPasswordConfirm ? resetPasswordConfirm.value.trim() : '';

                if (!newValue || !confirmValue) {
                    showFormMessage(setPasswordForm, 'error', getMessage('updatePasswordError', 'Unable to update password.'));
                    return;
                }

                if (newValue !== confirmValue) {
                    showFormMessage(setPasswordForm, 'error', getMessage('registerPasswordMismatch', 'Passwords do not match.'));
                    return;
                }

                if (!updateResetRules()) {
                    showFormMessage(setPasswordForm, 'error', getMessage('updatePasswordError', 'Unable to update password.'));
                    return;
                }

                if (resetPasswordSubmit) {
                    resetPasswordSubmit.disabled = true;
                }

                showFormMessage(setPasswordForm, 'error', '');
                showFormMessage(setPasswordForm, 'success', '');

                var hash = window.location.hash.replace(/^#/, '');
                var hashParams = hash ? new URLSearchParams(hash) : null;
                var hashAccessToken = hashParams ? hashParams.get('access_token') || '' : '';
                var hashRefreshToken = hashParams ? hashParams.get('refresh_token') || '' : '';
                var codeParam = searchParams.get('code') || '';

                if (!hashAccessToken && window.sessionStorage) {
                    try {
                        hashAccessToken = sessionStorage.getItem('bw_supabase_access_token') || '';
                        hashRefreshToken = sessionStorage.getItem('bw_supabase_refresh_token') || '';
                    } catch (error) {
                        // ignore sessionStorage errors
                    }
                }

                var supabase = getSupabaseClient();
                var sessionPromise;

                if (supabase) {
                    if (codeParam) {
                        sessionPromise = supabase.auth.exchangeCodeForSession(codeParam).then(function (response) {
                            if (response && response.error) {
                                throw response.error;
                            }
                            return response && response.data ? response.data.session : null;
                        });
                    } else if (hashAccessToken) {
                        sessionPromise = supabase.auth.setSession({
                            access_token: hashAccessToken,
                            refresh_token: hashRefreshToken
                        }).then(function (response) {
                            if (response && response.error) {
                                throw response.error;
                            }
                            return response && response.data ? response.data.session : null;
                        });
                    } else {
                        sessionPromise = supabase.auth.getSession().then(function (response) {
                            return response && response.data ? response.data.session : null;
                        });
                    }
                } else {
                    if (codeParam) {
                        var verifier = getCodeVerifier();
                        if (!verifier) {
                            sessionPromise = Promise.reject(new Error(getMessage('updatePasswordError', 'Unable to update password.')));
                        } else {
                            sessionPromise = fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/token?grant_type=pkce', {
                                method: 'POST',
                                headers: {
                                    apikey: anonKey,
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json'
                                },
                                body: JSON.stringify({
                                    auth_code: codeParam,
                                    code_verifier: verifier
                                })
                            }).then(function (response) {
                                if (!response.ok) {
                                    return response.json().then(function (payload) {
                                        var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('updatePasswordError', 'Unable to update password.');
                                        throw new Error(message);
                                    });
                                }
                                return response.json();
                            }).then(function (payload) {
                                return {
                                    access_token: payload.access_token || '',
                                    refresh_token: payload.refresh_token || ''
                                };
                            });
                        }
                    } else {
                        sessionPromise = Promise.resolve({
                            access_token: hashAccessToken,
                            refresh_token: hashRefreshToken
                        });
                    }
                }

                Promise.resolve(sessionPromise)
                    .then(function (session) {
                        var accessToken = session && session.access_token ? session.access_token : '';
                        if (!accessToken) {
                            throw new Error(getMessage('updatePasswordError', 'Unable to update password.'));
                        }

                        if (supabase) {
                            return supabase.auth.updateUser({ password: newValue });
                        }

                        return fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/user', {
                            method: 'PUT',
                            headers: {
                                apikey: anonKey,
                                Authorization: 'Bearer ' + accessToken,
                                'Content-Type': 'application/json',
                                Accept: 'application/json'
                            },
                            body: JSON.stringify({ password: newValue })
                        }).then(function (response) {
                            if (!response.ok) {
                                return response.json().then(function (payload) {
                                    var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('updatePasswordError', 'Unable to update password.');
                                    throw new Error(message);
                                });
                            }
                            return response.json();
                        });
                    })
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        cleanAuthUrl(['code', 'state', 'type', 'provider']);
                        setSessionStorageItem(recoveryHandledKey, '');
                        showFormMessage(setPasswordForm, 'success', getMessage('updatePasswordSuccess', 'Your password has been updated.'));
                    })
                    .catch(function () {
                        showFormMessage(setPasswordForm, 'error', getMessage('updatePasswordError', 'Unable to update password.'));
                    })
                    .finally(function () {
                        if (resetPasswordSubmit) {
                            resetPasswordSubmit.disabled = false;
                        }
                    });
            };

            setPasswordForm.addEventListener('submit', setPasswordSubmit);
        }

        if (passwordLoginEnabled && goPasswordButton) {
            goPasswordButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearOtpPendingState();
                switchAuthScreen('password');
            });
        }

        if (goMagicButtons.length) {
            goMagicButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setSessionStorageItem(recoveryHandledKey, '');
                    if (setPasswordForm) {
                        showFormMessage(setPasswordForm, 'error', '');
                        showFormMessage(setPasswordForm, 'success', '');
                    }
                    cleanAuthUrl(['code', 'state', 'type', 'provider']);
                    switchAuthScreen('magic');
                });
            });
        }

        if (oauthButtons.length) {
            oauthButtons.forEach(function (button) {
                var provider = button.getAttribute('data-bw-oauth-provider');
                if (provider === 'google' && !oauthGoogleEnabled) {
                    return;
                }
                if (provider === 'facebook' && !oauthFacebookEnabled) {
                    return;
                }
                if (provider === 'apple' && !oauthAppleEnabled) {
                    return;
                }
                button.addEventListener('click', function () {
                    if (provider === 'apple') {
                        showFormMessage(magicLinkForm, 'success', 'Apple login coming soon.');
                        return;
                    }
                    if (!projectUrl) {
                        return;
                    }
                    var redirectTo = oauthRedirect || window.location.origin + '/my-account/';
                    var authUrl = projectUrl.replace(/\/$/, '') + '/auth/v1/authorize?provider=' + encodeURIComponent(provider) + '&redirect_to=' + encodeURIComponent(redirectTo);
                    logDebug('Supabase OAuth redirect', { provider: provider, redirect_to: redirectTo });
                    window.location.href = authUrl;
                });
            });
        }

        var showRegisterStep = function (step) {
            if (!registerForm) {
                return;
            }
            var steps = Array.prototype.slice.call(registerForm.querySelectorAll('[data-bw-register-step]'));
            steps.forEach(function (panel) {
                var isActive = panel.getAttribute('data-bw-register-step') === step;
                panel.classList.toggle('is-active', isActive);
                setStepState(panel, isActive);
            });
        };

        var url = new URL(window.location.href);
        if (url.pathname.indexOf('customer-logout') !== -1 || url.searchParams.get('loggedout') === 'true') {
            cleanupAuthState();
            clearOtpPendingState();
        }

        var logoutLinks = Array.prototype.slice.call(document.querySelectorAll('a[href*="customer-logout"]'));
        if (logoutLinks.length) {
            logoutLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    cleanupAuthState();
                    clearOtpPendingState();
                });
            });
        }

        if (document.body.classList.contains('bw-account-login-only')) {
            clearOtpPendingState();
            switchAuthScreen('magic');
        }

        if (registerForm) {
            var initialStep = registerForm.querySelector('[data-bw-register-step].is-active');
            var initialStepName = initialStep ? initialStep.getAttribute('data-bw-register-step') : 'email';
            showRegisterStep(initialStepName || 'email');
        }

        if (!skipAuthHandlers) {
            checkExistingSession();
        }

        if (authScreens.length) {
            if (skipAuthHandlers) {
                switchAuthScreen('magic');
            } else {
                var storedEmail = getPendingOtpEmail();
                if (!storedEmail) {
                    clearOtpPendingState();
                }
                if (!document.body.classList.contains('bw-account-login-only') && storedEmail) {
                    switchAuthScreen('otp');
                } else {
                    switchAuthScreen('magic');
                }
            }
        }

        if (registerContinue && registerForm) {
            registerContinue.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var emailField = registerForm.querySelector('#bw_supabase_register_email');
                var emailValue = emailField ? emailField.value.trim() : '';
                if (!emailValue) {
                    showFormMessage(registerForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }
                showFormMessage(registerForm, 'error', '');
                showRegisterStep('password');
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var emailField = registerForm.querySelector('#bw_supabase_register_email');
                var passwordField = registerForm.querySelector('#bw_supabase_register_password');
                var confirmField = registerForm.querySelector('#bw_supabase_register_password_confirm');
                var emailValue = emailField ? emailField.value.trim() : '';
                var passwordValue = passwordField ? passwordField.value : '';
                var confirmValue = confirmField ? confirmField.value : '';

                if (!emailValue || !passwordValue || !confirmValue) {
                    showFormMessage(registerForm, 'error', getMessage('registerCompleteFields', 'Please complete all fields.'));
                    return;
                }

                if (passwordValue !== confirmValue) {
                    showFormMessage(registerForm, 'error', getMessage('registerPasswordMismatch', 'Passwords do not match.'));
                    return;
                }

                if (!window.bwAccountAuth || !window.bwAccountAuth.ajaxUrl || !window.bwAccountAuth.nonce) {
                    showFormMessage(registerForm, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                logDebug('Supabase signup request', { email_present: Boolean(emailValue), redirect_to: signupRedirect || 'default' });

                if (registerSubmit) {
                    registerSubmit.disabled = true;
                }

                var formData = new FormData();
                formData.append('email', emailValue);
                formData.append('password', passwordValue);
                formData.append('action', 'bw_supabase_register');
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
                        if (payload && payload.success) {
                            if (payload.data && payload.data.redirect) {
                                window.location.href = payload.data.redirect;
                                return;
                            }
                            showFormMessage(registerForm, 'success', payload.data && payload.data.message ? payload.data.message : getMessage('registerSuccess', 'Check your email to confirm your account.'));
                            return;
                        }

                        var message = payload && payload.data && payload.data.message ? payload.data.message : getMessage('registerError', 'Unable to register.');
                        showFormMessage(registerForm, 'error', message);
                    })
                    .catch(function () {
                        showFormMessage(registerForm, 'error', getMessage('registerError', 'Unable to register.'));
                    })
                    .finally(function () {
                        if (registerSubmit) {
                            registerSubmit.disabled = false;
                        }
                    });
            });
        }

        if (otpForm) {
            if (otpInputs.length) {
                otpInputs.forEach(function (input, index) {
                    input.addEventListener('input', function () {
                        var value = input.value.replace(/\D/g, '').charAt(0);
                        input.value = value;
                        if (value && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                        setOtpInputsError(false);
                        updateOtpState();
                    });

                    input.addEventListener('change', function () {
                        var value = input.value.replace(/\D/g, '').charAt(0);
                        input.value = value;
                        updateOtpState();
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Backspace' && !input.value && index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    });
                });

                if (otpInputsWrap) {
                    otpInputsWrap.addEventListener('paste', function (event) {
                        var pasteValue = (event.clipboardData || window.clipboardData).getData('text');
                        if (!pasteValue) {
                            return;
                        }
                        var digits = pasteValue.replace(/\D/g, '').slice(0, otpInputs.length);
                        if (!digits) {
                            return;
                        }
                        event.preventDefault();
                        digits.split('').forEach(function (digit, index) {
                            if (otpInputs[index]) {
                                otpInputs[index].value = digit;
                            }
                        });
                        updateOtpState();
                    });
                }
            }

            otpForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setBridgeAttempted(false);
                resetReloadGuard();

                if (otpConfirmButton && otpConfirmButton.disabled) {
                    return;
                }

                var emailValue = getPendingOtpEmail();
                if (!emailValue) {
                    switchAuthScreen('magic');
                    showFormMessage(magicLinkForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }

                var code = getOtpCode();
                if (!/^\d{6}$/.test(code)) {
                    showFormMessage(otpForm, 'error', getMessage('enterOtp', 'Please enter the 6-digit code.'));
                    setOtpInputsError(true);
                    return;
                }

                showFormMessage(otpForm, 'error', '');
                showFormMessage(otpForm, 'success', '');
                setOtpInputsError(false);

                if (otpConfirmButton) {
                    otpConfirmButton.disabled = true;
                }

                verifyOtp(emailValue, code)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        var session = response && response.data ? response.data.session : null;
                        var accessToken = session ? session.access_token : response.access_token;
                        var refreshToken = session ? session.refresh_token : response.refresh_token;
                        return bridgeSupabaseSession(accessToken, refreshToken, 'otp');
                    })
                    .catch(function (error) {
                        var message = getMessage('otpVerifyError', 'Unable to verify the code.');
                        if (error && error.message && error.message.toLowerCase().indexOf('expired') !== -1) {
                            message = getMessage('otpInvalid', 'Invalid or expired code. Please try again.');
                        }
                        showFormMessage(otpForm, 'error', message);
                        setOtpInputsError(true);
                    })
                    .finally(function () {
                        if (otpConfirmButton) {
                            updateOtpState();
                        }
                    });
            });
        }

        if (otpResendButton) {
            otpResendButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var emailValue = getPendingOtpEmail();
                if (!emailValue) {
                    switchAuthScreen('magic');
                    showFormMessage(magicLinkForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }

                otpResendButton.disabled = true;

                requestOtp(emailValue)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        showFormMessage(otpForm, 'success', getMessage('otpResent', 'We sent you a new code.'));
                    })
                    .catch(function () {
                        showFormMessage(otpForm, 'error', getMessage('otpResendError', 'Unable to resend the code right now.'));
                    })
                    .finally(function () {
                        setTimeout(function () {
                            otpResendButton.disabled = false;
                        }, 10000);
                    });
            });
        }

        /*
         * Manual QA checklist:
         * - Email -> Continue -> OTP screen appears with fade-up
         * - Paste 6 digits -> fields fill -> Confirm enables
         * - Correct OTP -> session -> /my-account/ loads logged-in UI
         * - Wrong OTP -> error shown -> stay on OTP screen
         * - Resend works
         * - Back to Login returns to email screen with fade-up
         * - Refresh on OTP screen retains email (localStorage) and allows verification
         * - Email + password logs in and redirects to /my-account/
         * - Wrong password stays on password screen with error
         * - Forgot password -> reset screen shows (fade-up)
         * - Reset email -> success message shown
         * - Recovery link -> set-password screen shows automatically
         * - Set password -> rules validate, success message, back to login works
         * - Logout -> no OTP/reset screens shown by default
         */
    });
})();
