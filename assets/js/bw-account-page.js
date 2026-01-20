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
        var pendingEmailKey = 'bw_pending_email';
        var authFlowKey = 'bw_auth_flow';
        var pendingAccessTokenKey = 'bw_pending_access_token';
        var pendingRefreshTokenKey = 'bw_pending_refresh_token';
        var redirectGuardKey = 'bw_bridge_redirected_at';
        var bridgeAttemptKey = 'bw_bridge_attempted';
        var sessionCheckKey = 'bw_handled_session_check';
        var tokenHandledKey = 'bw_token_handled_otp';

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
                    sessionStorage.removeItem(authFlowKey);
                    sessionStorage.removeItem(pendingAccessTokenKey);
                    sessionStorage.removeItem(pendingRefreshTokenKey);
                    sessionStorage.removeItem(tokenHandledKey);
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
        var createPasswordForm = authWrapper.querySelector('[data-bw-create-password-form]');
        var createPasswordInput = createPasswordForm ? createPasswordForm.querySelector('input[name="new_password"]') : null;
        var createPasswordConfirm = createPasswordForm ? createPasswordForm.querySelector('input[name="confirm_password"]') : null;
        var createPasswordSubmit = createPasswordForm ? createPasswordForm.querySelector('[data-bw-create-password-submit]') : null;
        var createPasswordRules = createPasswordForm ? Array.prototype.slice.call(createPasswordForm.querySelectorAll('[data-bw-password-rule]')) : [];
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

        var hashEmail = function (value) {
            if (!value) {
                return '';
            }
            var hash = 0;
            var normalized = String(value).toLowerCase();
            for (var i = 0; i < normalized.length; i += 1) {
                hash = ((hash << 5) - hash) + normalized.charCodeAt(i);
                hash |= 0;
            }
            return Math.abs(hash).toString(16);
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

        var setAuthFlow = function (flow) {
            setSessionStorageItem(authFlowKey, flow || '');
        };

        var getAuthFlow = function () {
            return getSessionStorageItem(authFlowKey);
        };

        var clearAuthFlow = function () {
            setAuthFlow('');
        };

        var setPendingTokens = function (accessToken, refreshToken) {
            setSessionStorageItem(pendingAccessTokenKey, accessToken || '');
            setSessionStorageItem(pendingRefreshTokenKey, refreshToken || '');
        };

        var clearPendingTokens = function () {
            setPendingTokens('', '');
            setSessionStorageItem(tokenHandledKey, '');
        };

        var sanitizeSupabasePayload = function (payload) {
            if (!payload || typeof payload !== 'object') {
                return payload;
            }
            var clone = {};
            Object.keys(payload).forEach(function (key) {
                if (key === 'access_token' || key === 'refresh_token') {
                    return;
                }
                clone[key] = payload[key];
            });
            return clone;
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
                clearAuthFlow();
                clearPendingTokens();
            }

            if (target === 'otp' && otpInputs.length) {
                otpInputs[0].focus();
                updateOtpState();
            }

            if (target === 'create-password') {
                if (createPasswordInput) {
                    createPasswordInput.focus();
                }
                updateCreatePasswordSubmitState();
            }

            logDiagnostics(target);
        };

        var getOtpErrorMessage = function (error) {
            var fallback = getMessage('magicLinkError', 'Unable to send the code.');
            if (!error) {
                return fallback;
            }
            var message = error.message ? error.message : String(error);
            var normalized = message.toLowerCase();
            if (normalized.indexOf('signups not allowed') !== -1) {
                return getMessage('otpSignupDisabledNeutral', 'We could not send a code. Please try a different email.');
            }
            if (normalized.indexOf('rate') !== -1 || normalized.indexOf('too many') !== -1) {
                return getMessage('otpRateLimit', 'Too many attempts. Please wait and try again.');
            }
            if (normalized.indexOf('redirect') !== -1 && normalized.indexOf('invalid') !== -1) {
                return getMessage('otpRedirectInvalid', 'Login is unavailable right now. Please contact support.');
            }
            return message || fallback;
        };

        var getPasswordRules = function () {
            return [
                { id: 'length', test: function (value) { return value.length >= 8; } },
                { id: 'upper', test: function (value) { return /[A-Z]/.test(value); } },
                { id: 'number', test: function (value) { return /[0-9]|[^A-Za-z0-9]/.test(value); } }
            ];
        };

        var updatePasswordRuleState = function () {
            if (!createPasswordInput || !createPasswordRules.length) {
                return true;
            }
            var value = createPasswordInput.value || '';
            var allPass = true;
            getPasswordRules().forEach(function (rule) {
                var passes = rule.test(value);
                var ruleItem = createPasswordRules.find(function (item) {
                    return item.dataset.bwPasswordRule === rule.id;
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

        var updateCreatePasswordSubmitState = function () {
            if (!createPasswordSubmit) {
                return;
            }
            var rulesPass = updatePasswordRuleState();
            var newValue = createPasswordInput ? createPasswordInput.value.trim() : '';
            var confirmValue = createPasswordConfirm ? createPasswordConfirm.value.trim() : '';
            createPasswordSubmit.disabled = !(rulesPass && newValue && confirmValue && newValue === confirmValue);
        };

        var checkEmailExists = function (email) {
            if (!authConfig.ajaxUrl || !authConfig.nonce) {
                return Promise.resolve({ ok: false, exists: false, reason: 'missing_config' });
            }
            return fetch(authConfig.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'bw_supabase_email_exists',
                    nonce: authConfig.nonce,
                    email: email
                })
            }).then(function (response) {
                return response.json().then(function (payload) {
                    var result = payload && payload.data ? payload.data : payload;
                    logDebug('EMAIL_EXISTS_RESULT', {
                        ok: Boolean(result && result.ok),
                        exists: Boolean(result && result.exists),
                        reason: result && result.reason ? result.reason : '',
                        status: result && result.status ? result.status : response.status,
                        emailHash: hashEmail(email)
                    });
                    return result;
                });
            }).catch(function () {
                logDebug('EMAIL_EXISTS_RESULT', {
                    ok: false,
                    exists: false,
                    reason: 'request_failed',
                    emailHash: hashEmail(email)
                });
                return { ok: false, exists: false, reason: 'request_failed' };
            });
        };

        var updateSupabasePassword = function (newPassword) {
            var supabase = getSupabaseClient();
            logDebug('PASSWORD_UPDATE_START', {
                mode: supabase ? 'sdk' : 'rest',
                hasAccessToken: Boolean(getSessionStorageItem(pendingAccessTokenKey))
            });

            if (supabase && supabase.auth && typeof supabase.auth.updateUser === 'function') {
                return supabase.auth.updateUser({ password: newPassword }).then(function (response) {
                    logDebug('PASSWORD_UPDATE_RESULT', {
                        mode: 'sdk',
                        ok: Boolean(response && !response.error),
                        errorMessage: response && response.error ? response.error.message : ''
                    });
                    return response;
                });
            }

            if (!projectUrl || !anonKey) {
                return Promise.resolve({ error: new Error(getMessage('missingConfig', 'Supabase configuration is missing.')) });
            }

            var accessToken = getSessionStorageItem(pendingAccessTokenKey) || getSessionStorageItem('bw_supabase_access_token');
            if (!accessToken) {
                return Promise.resolve({ error: new Error(getMessage('otpVerifyError', 'Unable to verify the code.')) });
            }

            return fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/user', {
                method: 'PUT',
                headers: {
                    apikey: anonKey,
                    Authorization: 'Bearer ' + accessToken,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({ password: newPassword })
            }).then(function (response) {
                logDebug('PASSWORD_UPDATE_RESULT', {
                    mode: 'rest',
                    ok: response.ok,
                    status: response.status
                });
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('createPasswordError', 'Unable to update password.');
                        throw new Error(message);
                    });
                }
                return response.json();
            }).catch(function (error) {
                return { error: error };
            });
        };

        var resolveBridgeTokens = function () {
            var accessToken = getSessionStorageItem(pendingAccessTokenKey);
            var refreshToken = getSessionStorageItem(pendingRefreshTokenKey);
            if (accessToken) {
                return Promise.resolve({
                    access_token: accessToken,
                    refresh_token: refreshToken
                });
            }
            var supabase = getSupabaseClient();
            if (!supabase) {
                return Promise.resolve({ access_token: '', refresh_token: '' });
            }
            return supabase.auth.getSession().then(function (response) {
                var session = response && response.data ? response.data.session : null;
                return {
                    access_token: session && session.access_token ? session.access_token : '',
                    refresh_token: session && session.refresh_token ? session.refresh_token : ''
                };
            });
        };

        var requestOtp = function (email, shouldCreateUser, context) {
            var supabase = getSupabaseClient();
            var redirectTo = magicLinkRedirect || window.location.origin + '/my-account/';
            var allowCreate = Boolean(shouldCreateUser);
            var modeLabel = context || (allowCreate ? 'signup' : 'login-only');

            logDebug('OTP_REQUEST_START', {
                mode: supabase ? 'sdk' : 'rest',
                flow: modeLabel,
                shouldCreateUser: allowCreate,
                emailHash: hashEmail(email),
                redirectTo: redirectTo
            });

            if (supabase && supabase.auth && typeof supabase.auth.signInWithOtp === 'function') {
                return supabase.auth.signInWithOtp({
                    email: email,
                    options: { emailRedirectTo: redirectTo, shouldCreateUser: allowCreate }
                }).then(function (response) {
                    logDebug('OTP_REQUEST_RESULT', {
                        mode: 'sdk',
                        ok: Boolean(response && !response.error),
                        errorMessage: response && response.error ? response.error.message : ''
                    });
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
                    Accept: 'application/json',
                    Authorization: 'Bearer ' + anonKey
                },
                body: JSON.stringify({
                    email: email,
                    create_user: allowCreate,
                    should_create_user: allowCreate,
                    options: { email_redirect_to: redirectTo }
                })
            }).then(function (response) {
                logDebug('OTP_REQUEST_RESULT', { mode: 'rest', ok: response.ok, status: response.status });
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        logDebug('OTP_REQUEST_ERROR', { mode: 'rest', status: response.status, payload: sanitizeSupabasePayload(payload) });
                        var msg = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : getMessage('magicLinkError', 'Unable to send code.');
                        throw new Error(msg);
                    }).catch(function () {
                        throw new Error(getMessage('magicLinkError', 'Unable to send code.'));
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
                    Authorization: 'Bearer ' + anonKey,
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

            logDebug('WP_BRIDGE_START', {
                provider: context || 'otp',
                hasAccessToken: Boolean(accessToken)
            });

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
                    logDebug('WP_BRIDGE_RESULT', {
                        ok: response.ok,
                        status: response.status,
                        provider: context || 'otp'
                    });
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
                    var previous = parseInt(sessionStorage.getItem(redirectGuardKey) || '0', 10);
                    var now = Date.now();
                    if (previous && now - previous < 10000) {
                        logDebug('Skip redirect (already redirected)');
                        return false;
                    }
                    sessionStorage.setItem(redirectGuardKey, String(now));
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
                    if (getAuthFlow() === 'signup') {
                        setPendingTokens(response.data.session.access_token || '', response.data.session.refresh_token || '');
                        switchAuthScreen('create-password');
                        return;
                    }
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
            if (url.searchParams.has('email')) {
                url.searchParams.delete('email');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
                }
                logDebug('Legacy email param removed');
            }
        };

        var initializeScreenState = function () {
            var hasCallback = hasAuthCallback();
            var storedEmail = getPendingOtpEmail();
            var pendingFlow = getAuthFlow();
            var hasPendingToken = Boolean(getSessionStorageItem(pendingAccessTokenKey));

            if (!storedEmail && !hasCallback) {
                setPendingOtpEmail('');
                clearAuthFlow();
                clearPendingTokens();
            }

            if (hasCallback) {
                logDebug('OAuth callback detected, deferring screen selection');
                return;
            }

            cleanAuthUrl(['bw_email_confirmed', 'bw_set_password']);

            if (pendingFlow === 'signup' && hasPendingToken) {
                switchAuthScreen('create-password');
                return;
            }

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
                showFormMessage(otpForm, 'error', '');
                showFormMessage(otpForm, 'success', '');

                var submitButton = form.querySelector('[data-bw-supabase-submit]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('is-loading');
                    submitButton.setAttribute('aria-busy', 'true');
                }

                logDebug('EMAIL_SUBMIT', { emailHash: hashEmail(emailValue) });
                setPendingOtpEmail(emailValue);
                switchAuthScreen('otp', { clearOtp: false });
                checkEmailExists(emailValue)
                    .then(function (result) {
                        var exists = result && result.ok ? Boolean(result.exists) : false;
                        var flow = exists ? 'login' : 'signup';
                        var shouldCreateUser = !exists;
                        if (!result || !result.ok) {
                            flow = 'signup';
                            shouldCreateUser = true;
                        }
                        logDebug('EMAIL_EXISTS_DECISION', {
                            exists: exists,
                            flow: flow,
                            reason: result && result.reason ? result.reason : ''
                        });
                        setAuthFlow(flow);
                        return requestOtp(emailValue, shouldCreateUser, flow).catch(function (error) {
                            var message = String(error && error.message ? error.message : '').toLowerCase();
                            if (message.indexOf('signups not allowed') !== -1 && !shouldCreateUser) {
                                logDebug('OTP_RETRY_SIGNUP', { flow: 'signup', emailHash: hashEmail(emailValue) });
                                setAuthFlow('signup');
                                return requestOtp(emailValue, true, 'signup');
                            }
                            throw error;
                        });
                    })
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        if (!getAuthFlow()) {
                            setAuthFlow('login');
                        }
                        showFormMessage(otpForm, 'success', getMessage('otpSent', 'If the email is valid, we sent you a code.'));
                    })
                    .catch(function (error) {
                        showFormMessage(otpForm, 'error', getOtpErrorMessage(error));
                        clearPendingTokens();
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.classList.remove('is-loading');
                            submitButton.removeAttribute('aria-busy');
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

                logDebug('PASSWORD_LOGIN_START', { emailHash: hashEmail(email) });
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
                        return response.json().then(function (payload) {
                            return { httpOk: response.ok, status: response.status, payload: payload };
                        });
                    })
                    .then(function (result) {
                        logDebug('PASSWORD_LOGIN_HTTP', { status: result.status });
                        var payload = result.payload;
                        if (!result.httpOk || !payload || payload.success !== true) {
                            var message = payload && payload.data && payload.data.message ? payload.data.message : getMessage('loginError', 'Unable to login.');
                            throw new Error(message);
                        }

                        var data = payload.data || {};
                        var tokens = {
                            access_token: data.access_token || (data.session && data.session.access_token ? data.session.access_token : ''),
                            refresh_token: data.refresh_token || (data.session && data.session.refresh_token ? data.session.refresh_token : '')
                        };

                        if (tokens.access_token) {
                            logDebug('PASSWORD_LOGIN_SUCCESS', { path: 'bridge' });
                            return bridgeSupabaseSession(tokens.access_token, tokens.refresh_token, 'password')
                                .then(function (bridgePayload) {
                                    completeBridgeRedirect(bridgePayload);
                                    return null;
                                });
                        }

                        if (data.redirect) {
                            logDebug('PASSWORD_LOGIN_SUCCESS', { path: 'redirect' });
                            cleanAuthUrl(['email', 'code', 'type', 'state', 'provider', 'bw_email_confirmed']);
                            setTimeout(function () {
                                window.location.replace(data.redirect);
                            }, 150);
                            return null;
                        }

                        throw new Error(getMessage('loginError', 'Unable to login.'));
                    })
                    .catch(function (error) {
                        logDebug('PASSWORD_LOGIN_ERROR', { message: error && error.message ? error.message : 'unknown' });
                        showFormMessage(form, 'error', error && error.message ? error.message : getMessage('loginError', 'Unable to login.'));
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
                        if (getSessionStorageItem(tokenHandledKey) === '1') {
                            return { data: {} };
                        }
                        setSessionStorageItem(tokenHandledKey, '1');
                        var tokens = extractTokensFromVerify(response);
                        if (!tokens.accessToken) {
                            throw new Error(getMessage('otpVerifyError', 'Unable to verify the code.'));
                        }
                        setPendingTokens(tokens.accessToken, tokens.refreshToken);
                        if (getAuthFlow() === 'signup') {
                            logDebug('OTP_VERIFY_NEXT', { next: 'create-password' });
                            switchAuthScreen('create-password');
                            return { success: true, data: { redirect: '/my-account/' } };
                        }
                        logDebug('OTP_VERIFY_NEXT', { next: 'wp-bridge' });
                        return bridgeSupabaseSession(tokens.accessToken, tokens.refreshToken, 'otp');
                    })
                    .then(function (payload) {
                        if (getAuthFlow() === 'signup') {
                            return;
                        }
                        if (!completeBridgeRedirect(payload)) {
                            showFormMessage(form, 'error', getMessage('otpVerifyError', 'Unable to verify the code.'));
                        } else {
                            setPendingOtpEmail('');
                            clearAuthFlow();
                            clearPendingTokens();
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

            if (form.matches('[data-bw-create-password-form]')) {
                event.preventDefault();
                event.stopPropagation();
                var newPassword = createPasswordInput ? createPasswordInput.value.trim() : '';
                var confirmPassword = createPasswordConfirm ? createPasswordConfirm.value.trim() : '';

                if (!newPassword || !confirmPassword) {
                    showFormMessage(form, 'error', getMessage('createPasswordError', 'Unable to update password.'));
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showFormMessage(form, 'error', getMessage('passwordMismatch', 'Passwords do not match.'));
                    return;
                }

                if (!updatePasswordRuleState()) {
                    showFormMessage(form, 'error', getMessage('passwordRules', 'Please meet all password requirements.'));
                    return;
                }

                if (createPasswordSubmit) {
                    createPasswordSubmit.disabled = true;
                }

                updateSupabasePassword(newPassword)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        clearAuthFlow();
                        setPendingOtpEmail('');
                        return resolveBridgeTokens();
                    })
                    .then(function (tokens) {
                        if (!tokens.access_token) {
                            throw new Error(getMessage('otpVerifyError', 'Unable to verify the code.'));
                        }
                        return bridgeSupabaseSession(tokens.access_token, tokens.refresh_token, 'otp');
                    })
                    .then(function (payload) {
                        if (!completeBridgeRedirect(payload)) {
                            showFormMessage(form, 'error', getMessage('createPasswordError', 'Unable to update password.'));
                            return;
                        }
                        clearPendingTokens();
                    })
                    .catch(function (error) {
                        showFormMessage(form, 'error', error && error.message ? error.message : getMessage('createPasswordError', 'Unable to update password.'));
                    })
                    .finally(function () {
                        if (createPasswordSubmit) {
                            createPasswordSubmit.disabled = false;
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
                var flow = getAuthFlow() === 'signup' ? 'signup' : 'login';
                var shouldCreateUser = flow === 'signup';
                if (otpResendButton) {
                    otpResendButton.disabled = true;
                }
                requestOtp(pendingEmail, shouldCreateUser, flow)
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
                if (target.matches('[data-bw-password-input], [data-bw-password-confirm]')) {
                    updateCreatePasswordSubmitState();
                }
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

        if (createPasswordForm) {
            updateCreatePasswordSubmitState();
        }

        // QA checklist:
        // - Magic/email: enter email → neutral success message → otp screen appears
        // - Existing user email → Continue → OTP request ok (log) → enter OTP → WP bridge → redirect without manual refresh
        // - New user email → Continue → OTP signup ok → enter OTP → create-password screen → set password → bridge → redirect
        // - Wrong OTP → error message
        // - Signups not allowed for otp → stable error and no state corruption
        // - Logout → returns to email screen (not otp)
        // - Google/Facebook buttons: click → callback handled → logged-in My Account without manual refresh (if enabled)
        // - Buttons always clickable after multiple fade-up transitions
        // - No console errors
        // - No ?email= lingering; URL ends clean (/my-account/)
        // Verification notes:
        // - Open Network tab: check /admin-ajax.php?action=bw_supabase_email_exists and /auth/v1/otp requests.
        // - With debug enabled, look for OTP_REQUEST_START/RESULT and WP_BRIDGE_START/RESULT logs in console.
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
