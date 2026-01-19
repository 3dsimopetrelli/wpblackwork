(function () {
    if (window.__bwAccountAuthInit) {
        return;
    }
    window.__bwAccountAuthInit = true;

    var init = function () {
        if (window.__bwAccountAuthReady) {
            return;
        }
        window.__bwAccountAuthReady = true;

        var authConfig = window.bwAccountAuth || {};
        var debugEnabled = Boolean(authConfig.debug);
        var cookieBase = authConfig.cookieBase || 'bw_supabase_session';
        var otpAllowSignup = authConfig.otpAllowSignup !== undefined ? Boolean(authConfig.otpAllowSignup) : true;

        var pendingEmailKey = 'bw_pending_email';
        var redirectGuardKey = 'bw_bridge_redirected';
        var bridgeAttemptKey = 'bw_bridge_attempted';
        var sessionCheckKey = 'bw_handled_session_check';

        var lastBridgeStatus = '';

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

        var clearAuthStorage = function () {
            if (window.sessionStorage) {
                try {
                    sessionStorage.removeItem(pendingEmailKey);
                    sessionStorage.removeItem('bw_handled_supabase_hash');
                    sessionStorage.removeItem('bw_handled_supabase_code');
                    sessionStorage.removeItem('bw_handled_token_login');
                    sessionStorage.removeItem(sessionCheckKey);
                    sessionStorage.removeItem('bw_supabase_access_token');
                    sessionStorage.removeItem('bw_supabase_refresh_token');
                    sessionStorage.removeItem(redirectGuardKey);
                    sessionStorage.removeItem(bridgeAttemptKey);
                    sessionStorage.removeItem('bw_oauth_bridge_done');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            clearCookie(cookieBase + '_access');
            clearCookie(cookieBase + '_refresh');
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
        };

        var isValidEmail = function (value) {
            if (!value) {
                return false;
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        };

        var hasAuthCallback = function () {
            var hashValue = window.location.hash || '';
            var searchValue = window.location.search || '';
            return hashValue.indexOf('access_token=') !== -1 ||
                hashValue.indexOf('refresh_token=') !== -1 ||
                searchValue.indexOf('code=') !== -1 ||
                searchValue.indexOf('state=') !== -1 ||
                searchValue.indexOf('provider=') !== -1;
        };

        var authWrapper = document.querySelector('.bw-account-auth');
        if (!authWrapper) {
            return;
        }

        var magicLinkForm = authWrapper.querySelector('[data-bw-supabase-form][data-bw-supabase-action="magic-link"]');
        var magicEmailInput = magicLinkForm ? magicLinkForm.querySelector('input[name="email"]') : null;
        var passwordLoginForm = authWrapper.querySelector('[data-bw-supabase-form][data-bw-supabase-action="password-login"]');
        var otpForm = authWrapper.querySelector('[data-bw-otp-form]');
        var otpInputs = otpForm ? Array.prototype.slice.call(otpForm.querySelectorAll('[data-bw-otp-digit]')) : [];
        var otpInputsWrap = otpForm ? otpForm.querySelector('[data-bw-otp-inputs]') : null;
        var otpConfirmButton = otpForm ? otpForm.querySelector('[data-bw-otp-confirm]') : null;
        var otpResendButton = otpForm ? otpForm.querySelector('[data-bw-otp-resend]') : null;
        var otpEmailText = otpForm ? otpForm.querySelector('[data-bw-otp-email]') : null;
        var screens = Array.prototype.slice.call(authWrapper.querySelectorAll('[data-bw-screen]'));

        var projectUrl = authConfig.projectUrl || '';
        var anonKey = authConfig.anonKey || '';
        var magicLinkRedirect = authConfig.magicLinkRedirectUrl || '';
        var oauthRedirect = authConfig.oauthRedirectUrl || '';
        var magicLinkEnabled = authConfig.magicLinkEnabled !== undefined ? Boolean(authConfig.magicLinkEnabled) : true;
        var oauthGoogleEnabled = authConfig.oauthGoogleEnabled !== undefined ? Boolean(authConfig.oauthGoogleEnabled) : true;
        var oauthFacebookEnabled = authConfig.oauthFacebookEnabled !== undefined ? Boolean(authConfig.oauthFacebookEnabled) : true;
        var oauthAppleEnabled = authConfig.oauthAppleEnabled !== undefined ? Boolean(authConfig.oauthAppleEnabled) : false;
        var passwordLoginEnabled = authConfig.passwordLoginEnabled !== undefined ? Boolean(authConfig.passwordLoginEnabled) : true;
        var messages = authConfig.messages || {};

        var supabaseClient = null;
        var pendingOtpEmail = '';

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

        var clearScreenMessages = function (screen) {
            if (!screen) {
                return;
            }
            var messagesEls = screen.querySelectorAll('.bw-account-login__error, .bw-account-login__success');
            Array.prototype.slice.call(messagesEls).forEach(function (element) {
                element.hidden = true;
                element.textContent = '';
            });
            if (otpInputsWrap) {
                otpInputsWrap.classList.remove('is-error');
            }
        };

        var setPendingOtpEmail = function (email) {
            pendingOtpEmail = email;
            if (otpEmailText) {
                otpEmailText.textContent = email ? email : '';
            }
            setSessionStorageItem(pendingEmailKey, email || '');
        };

        var getPendingOtpEmail = function () {
            if (pendingOtpEmail) {
                return pendingOtpEmail;
            }
            pendingOtpEmail = getSessionStorageItem(pendingEmailKey) || '';
            if (otpEmailText) {
                otpEmailText.textContent = pendingOtpEmail ? pendingOtpEmail : '';
            }
            return pendingOtpEmail;
        };

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

        var logDiagnostics = function (screenName) {
            if (!debugEnabled) {
                return;
            }
            console.log('[bw] Diagnostics', {
                screen: screenName || '',
                pendingEmail: Boolean(getPendingOtpEmail()),
                oauthCallback: hasAuthCallback(),
                hasSupabaseJs: Boolean(window.supabase && typeof window.supabase.createClient === 'function'),
                hasProjectUrl: Boolean(projectUrl),
                hasAnonKey: Boolean(anonKey),
                lastBridgeStatus: lastBridgeStatus || 'none'
            });
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

        var updateOtpState = function () {
            if (!otpConfirmButton) {
                return;
            }
            var code = getOtpCode();
            var isValid = code.length === 6 && /^\d{6}$/.test(code);
            otpConfirmButton.disabled = !isValid;
        };

        var switchAuthScreen = function (target, options) {
            var allowClearOtp = !options || options.clearOtp !== false;

            screens.forEach(function (screen) {
                var isActive = screen.getAttribute('data-bw-screen') === target;
                screen.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                if (isActive) {
                    screen.classList.add('is-visible');
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            screen.classList.add('is-active');
                        });
                    });
                } else {
                    screen.classList.remove('is-active');
                    setTimeout(function () {
                        screen.classList.remove('is-visible');
                    }, 300);
                }
                clearScreenMessages(screen);
            });

            if (target === 'magic' && allowClearOtp) {
                setPendingOtpEmail('');
            }

            if (target === 'otp' && otpInputs.length) {
                otpInputs[0].focus();
                updateOtpState();
            }

            logDiagnostics(target);
        };

        var isSignupNotAllowedError = function (error) {
            if (!error) {
                return false;
            }
            var message = '';
            if (typeof error === 'string') {
                message = error;
            } else if (error.message) {
                message = error.message;
            }
            message = message.toLowerCase();
            return message.indexOf('signups not allowed') !== -1 ||
                (message.indexOf('signup') !== -1 && message.indexOf('not allowed') !== -1);
        };

        var requestOtp = function (email) {
            var supabase = getSupabaseClient();
            var redirectTo = magicLinkRedirect || window.location.origin + '/my-account/';
            var allowCreate = Boolean(otpAllowSignup);

            logDebug('OTP send request', { method: supabase ? 'sdk' : 'rest', shouldCreateUser: allowCreate });

            if (supabase) {
                return supabase.auth.signInWithOtp({
                    email: email,
                    options: {
                        emailRedirectTo: redirectTo,
                        shouldCreateUser: allowCreate
                    }
                }).then(function (response) {
                    logDebug('OTP SDK response', { status: response && response.error ? 'error' : 'ok', shouldCreateUser: allowCreate });
                    return response;
                });
            }

            if (!projectUrl || !anonKey) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            var endpoint = projectUrl.replace(/\/$/, '') + '/auth/v1/otp';

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    apikey: anonKey,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    create_user: allowCreate,
                    options: {
                        email_redirect_to: redirectTo
                    }
                })
            }).then(function (response) {
                logDebug('OTP REST response', { status: response.status, endpoint: endpoint, shouldCreateUser: allowCreate });
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('magicLinkError', 'Unable to send code.');
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
                }).then(function (response) {
                    logDebug('OTP verify SDK response', { status: response && response.error ? 'error' : 'ok' });
                    return response;
                });
            }

            if (!projectUrl || !anonKey) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            var endpoint = projectUrl.replace(/\/$/, '') + '/auth/v1/verify';

            return fetch(endpoint, {
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
                logDebug('OTP verify REST response', { status: response.status, endpoint: endpoint });
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

        var extractTokensFromVerify = function (response) {
            if (!response) {
                return { accessToken: '', refreshToken: '' };
            }
            var data = response.data || response;
            var session = data && data.session ? data.session : null;
            var accessToken = '';
            var refreshToken = '';
            if (session) {
                accessToken = session.access_token || '';
                refreshToken = session.refresh_token || '';
            } else {
                accessToken = data.access_token || '';
                refreshToken = data.refresh_token || '';
            }
            return {
                accessToken: accessToken,
                refreshToken: refreshToken
            };
        };

        var bridgeSupabaseSession = function (accessToken, refreshToken, context) {
            if (!authConfig.ajaxUrl || !authConfig.nonce) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

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
                    type: context || 'otp'
                })
            })
                .then(function (response) {
                    lastBridgeStatus = String(response.status);
                    logDebug('WP bridge response', { status: response.status, context: context || 'otp' });
                    return response.json();
                });
        };

        var checkWpSession = function () {
            if (!authConfig.ajaxUrl || !authConfig.nonce) {
                return Promise.resolve(false);
            }

            return fetch(authConfig.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'bw_supabase_check_wp_session',
                    nonce: authConfig.nonce
                })
            })
                .then(function (response) {
                    logDebug('WP session check', { status: response.status });
                    return response.json();
                })
                .then(function (payload) {
                    return payload && payload.success && payload.data && payload.data.loggedIn;
                })
                .catch(function () {
                    return false;
                });
        };

        var completeBridgeRedirect = function (payload) {
            if (!payload || !payload.success) {
                return false;
            }

            if (window.sessionStorage) {
                try {
                    if (sessionStorage.getItem(redirectGuardKey) === '1') {
                        logDebug('Skip redirect (already redirected)');
                        return false;
                    }
                    sessionStorage.setItem(redirectGuardKey, '1');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            var targetUrl = payload.data && payload.data.redirect ? payload.data.redirect : '/my-account/';
            cleanAuthUrl(['email', 'code', 'type', 'state', 'provider', 'bw_email_confirmed']);

            var attempts = 0;
            var maxAttempts = 2;

            var attemptRedirect = function () {
                attempts += 1;
                checkWpSession().then(function (loggedIn) {
                    if (loggedIn || attempts > maxAttempts) {
                        setTimeout(function () {
                            window.location.replace(targetUrl);
                        }, 150);
                        return;
                    }
                    var backoff = attempts === 1 ? 200 : 600;
                    setTimeout(attemptRedirect, backoff);
                });
            };

            attemptRedirect();
            return true;
        };

        var checkExistingSession = function () {
            if (getSessionStorageItem(sessionCheckKey) === '1') {
                return;
            }
            var supabase = getSupabaseClient();
            if (!supabase) {
                return;
            }
            if (hasAuthCallback()) {
                return;
            }
            setSessionStorageItem(sessionCheckKey, '1');

            if (getSessionStorageItem(bridgeAttemptKey) === '1') {
                logDebug('Auto-bridge skipped', { reason: 'already_attempted' });
                return;
            }

            supabase.auth.getSession().then(function (response) {
                if (response && response.data && response.data.session) {
                    setSessionStorageItem(bridgeAttemptKey, '1');
                    logDebug('Supabase session found', { autoBridge: true });
                    bridgeSupabaseSession(
                        response.data.session.access_token || '',
                        response.data.session.refresh_token || '',
                        'session'
                    ).then(function (payload) {
                        if (!completeBridgeRedirect(payload)) {
                            setSessionStorageItem(bridgeAttemptKey, '');
                        }
                    }).catch(function () {
                        setSessionStorageItem(bridgeAttemptKey, '');
                    });
                }
            });
        };

        var handleLegacyEmailParam = function () {
            var url = new URL(window.location.href);
            var legacyEmail = url.searchParams.get('email');
            if (legacyEmail && isValidEmail(legacyEmail)) {
                if (magicEmailInput && !magicEmailInput.value) {
                    magicEmailInput.value = legacyEmail;
                }
                url.searchParams.delete('email');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
                }
                logDebug('Legacy email param consumed');
            }
        };

        var initializeScreenState = function () {
            var hasCallback = hasAuthCallback();
            var storedEmail = getPendingOtpEmail();

            if (!storedEmail && !hasCallback) {
                setPendingOtpEmail('');
            }

            if (hasCallback) {
                logDebug('OAuth callback detected, deferring screen selection');
                return;
            }

            cleanAuthUrl(['bw_email_confirmed', 'bw_set_password']);

            if (storedEmail) {
                switchAuthScreen('otp');
            } else {
                switchAuthScreen('magic');
            }
        };

        if (magicLinkForm && !magicLinkEnabled) {
            var magicSubmit = magicLinkForm.querySelector('[data-bw-supabase-submit]');
            if (magicSubmit) {
                magicSubmit.disabled = true;
            }
        }

        var searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('logged_out')) {
            clearAuthStorage();
            searchParams.delete('logged_out');
            if (window.history && window.history.replaceState) {
                var loggedOutUrl = new URL(window.location.href);
                loggedOutUrl.searchParams.delete('logged_out');
                window.history.replaceState({}, document.title, loggedOutUrl.pathname + (loggedOutUrl.search ? loggedOutUrl.search : '') + loggedOutUrl.hash);
            }
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

        handleLegacyEmailParam();
        initializeScreenState();
        checkExistingSession();

        authWrapper.addEventListener('submit', function (event) {
            var form = event.target;
            if (form.matches('[data-bw-supabase-form][data-bw-supabase-action="magic-link"]')) {
                event.preventDefault();
                event.stopPropagation();
                var emailValue = magicEmailInput ? magicEmailInput.value.trim() : '';
                if (!isValidEmail(emailValue)) {
                    showFormMessage(form, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }
                showFormMessage(form, 'error', '');
                showFormMessage(form, 'success', '');

                var submitButton = form.querySelector('[data-bw-supabase-submit]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                requestOtp(emailValue)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        setPendingOtpEmail(emailValue);
                        showFormMessage(form, 'success', getMessage('otpSent', 'Check your email for the 6-digit code.'));
                        switchAuthScreen('otp', { clearOtp: false });
                    })
                    .catch(function (error) {
                        if (isSignupNotAllowedError(error)) {
                            showFormMessage(form, 'error', getMessage('otpSignupDisabled', 'No account found for this email.'));
                        } else {
                            showFormMessage(form, 'error', error && error.message ? error.message : getMessage('magicLinkError', 'Unable to send the code.'));
                        }
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
                return;
            }

            if (form.matches('[data-bw-supabase-form][data-bw-supabase-action="password-login"]')) {
                event.preventDefault();
                event.stopPropagation();

                if (!passwordLoginEnabled) {
                    showFormMessage(form, 'error', getMessage('loginError', 'Unable to login.'));
                    return;
                }

                var emailField = form.querySelector('input[name="email"]');
                var passwordField = form.querySelector('input[name="password"]');
                var email = emailField ? emailField.value.trim() : '';
                var password = passwordField ? passwordField.value : '';

                if (!email || !password) {
                    showFormMessage(form, 'error', getMessage('loginError', 'Email and password are required.'));
                    return;
                }

                if (!authConfig.ajaxUrl || !authConfig.nonce) {
                    showFormMessage(form, 'error', getMessage('missingConfig', 'Supabase configuration is missing.'));
                    return;
                }

                var loginSubmit = form.querySelector('[data-bw-supabase-submit]');
                if (loginSubmit) {
                    loginSubmit.disabled = true;
                }

                fetch(authConfig.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                    },
                    body: new URLSearchParams({
                        action: 'bw_supabase_login',
                        nonce: authConfig.nonce,
                        email: email,
                        password: password
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
                        var message = payload && payload.data && payload.data.message ? payload.data.message : getMessage('loginError', 'Unable to login.');
                        showFormMessage(form, 'error', message);
                    })
                    .catch(function () {
                        showFormMessage(form, 'error', getMessage('loginError', 'Unable to login.'));
                    })
                    .finally(function () {
                        if (loginSubmit) {
                            loginSubmit.disabled = false;
                        }
                    });
                return;
            }

            if (form.matches('[data-bw-otp-form]')) {
                event.preventDefault();
                event.stopPropagation();
                var pendingEmail = getPendingOtpEmail();
                if (!pendingEmail) {
                    switchAuthScreen('magic');
                    return;
                }
                var code = getOtpCode();
                if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
                    showFormMessage(form, 'error', getMessage('enterOtp', 'Please enter the 6-digit code.'));
                    if (otpInputsWrap) {
                        otpInputsWrap.classList.add('is-error');
                    }
                    return;
                }

                if (otpConfirmButton) {
                    otpConfirmButton.disabled = true;
                }

                verifyOtp(pendingEmail, code)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        var tokens = extractTokensFromVerify(response);
                        if (!tokens.accessToken) {
                            throw new Error(getMessage('otpVerifyError', 'Unable to verify the code.'));
                        }
                        return bridgeSupabaseSession(tokens.accessToken, tokens.refreshToken, 'otp');
                    })
                    .then(function (payload) {
                        if (!completeBridgeRedirect(payload)) {
                            showFormMessage(form, 'error', getMessage('otpVerifyError', 'Unable to verify the code.'));
                        } else {
                            setPendingOtpEmail('');
                        }
                    })
                    .catch(function (error) {
                        showFormMessage(form, 'error', error && error.message ? error.message : getMessage('otpInvalid', 'Invalid or expired code. Please try again.'));
                        if (otpInputsWrap) {
                            otpInputsWrap.classList.add('is-error');
                        }
                    })
                    .finally(function () {
                        if (otpConfirmButton) {
                            otpConfirmButton.disabled = false;
                        }
                    });
            }
        });

        authWrapper.addEventListener('click', function (event) {
            var target = event.target;
            var actionTarget = target.closest('[data-bw-go-password], [data-bw-go-magic], [data-bw-otp-resend], [data-bw-oauth-provider]');
            if (!actionTarget) {
                return;
            }

            if (actionTarget.matches('[data-bw-go-password]')) {
                event.preventDefault();
                switchAuthScreen('password');
                return;
            }

            if (actionTarget.matches('[data-bw-go-magic]')) {
                event.preventDefault();
                switchAuthScreen('magic');
                return;
            }

            if (actionTarget.matches('[data-bw-otp-resend]')) {
                event.preventDefault();
                var pendingEmail = getPendingOtpEmail();
                if (!pendingEmail) {
                    switchAuthScreen('magic');
                    return;
                }
                if (otpResendButton) {
                    otpResendButton.disabled = true;
                }
                requestOtp(pendingEmail)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        showFormMessage(otpForm, 'success', getMessage('otpResent', 'We sent you a new code.'));
                        updateOtpState();
                    })
                    .catch(function () {
                        showFormMessage(otpForm, 'error', getMessage('otpResendError', 'Unable to resend the code right now.'));
                    })
                    .finally(function () {
                        if (otpResendButton) {
                            otpResendButton.disabled = false;
                        }
                    });
                return;
            }

            if (actionTarget.matches('[data-bw-oauth-provider]')) {
                event.preventDefault();
                var provider = actionTarget.getAttribute('data-bw-oauth-provider');
                if (!provider) {
                    return;
                }
                if (provider === 'google' && !oauthGoogleEnabled) {
                    return;
                }
                if (provider === 'facebook' && !oauthFacebookEnabled) {
                    return;
                }
                if (provider === 'apple' && !oauthAppleEnabled) {
                    return;
                }
                if (provider === 'apple') {
                    showFormMessage(magicLinkForm, 'success', getMessage('oauthAppleSoon', 'Apple login is coming soon.'));
                    return;
                }

                var redirectTo = oauthRedirect || window.location.origin + '/my-account/';
                var supabase = getSupabaseClient();

                if (supabase && supabase.auth && typeof supabase.auth.signInWithOAuth === 'function') {
                    logDebug('Supabase OAuth redirect', { provider: provider, redirect_to: redirectTo, method: 'sdk' });
                    supabase.auth.signInWithOAuth({
                        provider: provider,
                        options: {
                            redirectTo: redirectTo
                        }
                    });
                    return;
                }

                if (!projectUrl) {
                    return;
                }

                var authUrl = projectUrl.replace(/\/$/, '') + '/auth/v1/authorize?provider=' + encodeURIComponent(provider) + '&redirect_to=' + encodeURIComponent(redirectTo);
                logDebug('Supabase OAuth redirect', { provider: provider, redirect_to: redirectTo, method: 'rest' });
                window.location.href = authUrl;
            }
        });

        authWrapper.addEventListener('input', function (event) {
            var target = event.target;
            if (!target.matches('[data-bw-otp-digit]')) {
                return;
            }
            var value = target.value.replace(/\D/g, '').slice(0, 1);
            target.value = value;
            if (value) {
                var index = otpInputs.indexOf(target);
                if (index > -1 && otpInputs[index + 1]) {
                    otpInputs[index + 1].focus();
                }
            }
            updateOtpState();
        });

        authWrapper.addEventListener('keydown', function (event) {
            var target = event.target;
            if (!target.matches('[data-bw-otp-digit]')) {
                return;
            }
            if (event.key !== 'Backspace') {
                return;
            }
            if (target.value) {
                target.value = '';
                updateOtpState();
                return;
            }
            var index = otpInputs.indexOf(target);
            if (index > 0) {
                otpInputs[index - 1].focus();
            }
            updateOtpState();
        });

        authWrapper.addEventListener('paste', function (event) {
            var target = event.target;
            if (!target.matches('[data-bw-otp-digit]')) {
                return;
            }
            var paste = event.clipboardData ? event.clipboardData.getData('text') : '';
            if (!paste) {
                return;
            }
            event.preventDefault();
            var digits = paste.replace(/\D/g, '').slice(0, otpInputs.length).split('');
            if (!digits.length) {
                return;
            }
            otpInputs.forEach(function (input, index) {
                input.value = digits[index] || '';
            });
            var lastIndex = Math.min(digits.length, otpInputs.length) - 1;
            if (otpInputs[lastIndex]) {
                otpInputs[lastIndex].focus();
            }
            updateOtpState();
        });

        // QA checklist:
        // - Magic/email: enter email → OTP email arrives → otp screen appears
        // - Wrong OTP → error message
        // - Correct OTP → WP session created → redirect to logged-in My Account WITHOUT manual refresh
        // - Logout → returns to email screen (not otp)
        // - Google/Facebook buttons: click → callback handled → logged-in My Account without manual refresh (if enabled)
        // - Buttons always clickable after multiple fade-up transitions
        // - No console errors
        // - URL always ends clean (/my-account/)
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
