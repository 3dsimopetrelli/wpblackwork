(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.bwSupabaseBridge) {
            return;
        }

        var debugEnabled = Boolean(window.bwSupabaseBridge.debug);
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

        var searchParams = new URLSearchParams(window.location.search);
        var authCode = searchParams.get('code') || '';
        var typeParam = searchParams.get('type') || '';
        var needsSetPassword = searchParams.get('bw_set_password') === '1';
        var callbackUrl = window.bwSupabaseBridge.callbackUrl || '';
        if (searchParams.has('logged_out')) {
            if (window.sessionStorage) {
                try {
                    sessionStorage.removeItem('bw_pending_email');
                    sessionStorage.removeItem('bw_handled_supabase_hash');
                    sessionStorage.removeItem('bw_handled_supabase_code');
                    sessionStorage.removeItem('bw_handled_token_login');
                    sessionStorage.removeItem('bw_handled_email_confirm');
                    sessionStorage.removeItem('bw_handled_session_check');
                    sessionStorage.removeItem('bw_otp_needs_password');
                    sessionStorage.removeItem('bw_otp_mode');
                    sessionStorage.removeItem('bw_supabase_access_token');
                    sessionStorage.removeItem('bw_supabase_refresh_token');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }
            if (window.localStorage) {
                try {
                    localStorage.removeItem('bw_onboarded');
                    localStorage.removeItem('bw_onboarded_email');
                } catch (error) {
                    // ignore localStorage errors
                }
            }
            if (window.history && window.history.replaceState) {
                var loggedOutUrl = new URL(window.location.href);
                loggedOutUrl.searchParams.delete('logged_out');
                window.history.replaceState({}, document.title, loggedOutUrl.pathname + (loggedOutUrl.search ? loggedOutUrl.search : '') + (loggedOutUrl.hash ? loggedOutUrl.hash : ''));
            }
            return;
        }

        if (typeParam === 'recovery') {
            logDebug('Supabase recovery detected', { action: 'skip_bridge' });
            return;
        }

        var handledKey = 'bw_handled_supabase_hash';
        var codeHandledKey = 'bw_handled_supabase_code';
        var redirectGuardKey = 'bw_oauth_bridge_done';
        var redirectDelayMs = 150;
        if (window.sessionStorage) {
            try {
                if (sessionStorage.getItem(handledKey) === 'done') {
                    if (window.history && window.history.replaceState) {
                        var cleanedUrl = new URL(window.location.href);
                        cleanedUrl.hash = '';
                        window.history.replaceState({}, document.title, cleanedUrl.pathname + (cleanedUrl.search ? cleanedUrl.search : ''));
                    }
                    if (!authCode) {
                        return;
                    }
                }
            } catch (error) {
                // ignore sessionStorage errors
            }
        }

        var cleanHash = function () {
            if (window.history && window.history.replaceState) {
                var cleanUrl = new URL(window.location.href);
                cleanUrl.hash = '';
                window.history.replaceState({}, document.title, cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : ''));
            }
        };

        var handleInviteHashError = function () {
            var hash = window.location.hash.replace(/^#/, '');
            if (!hash) {
                return false;
            }

            var params = new URLSearchParams(hash);
            var errorCode = params.get('error_code') || '';
            var errorDescription = params.get('error_description') || '';
            if (!errorCode) {
                return false;
            }

            if (errorCode === 'otp_expired') {
                var expiredLinkUrl = window.bwSupabaseBridge.expiredLinkUrl || '';
                if (expiredLinkUrl) {
                    cleanHash();
                    window.location.replace(expiredLinkUrl);
                    return true;
                }
            }

            var accountUrl = window.bwSupabaseBridge.accountUrl || '/my-account/';
            var targetUrl = new URL(accountUrl, window.location.origin);
            targetUrl.searchParams.set('bw_invite_error', errorCode);

            if (errorDescription) {
                targetUrl.searchParams.set('bw_invite_error_description', errorDescription.replace(/\+/g, ' '));
            }

            cleanHash();
            window.location.replace(targetUrl.toString());
            return true;
        };

        var redirectInviteToCallback = function () {
            var currentUrl = new URL(window.location.href);
            var code = currentUrl.searchParams.get('code') || '';
            var typeFromQuery = currentUrl.searchParams.get('type') || '';
            if (code && (typeFromQuery === 'invite' || typeFromQuery === 'recovery') && callbackUrl) {
                if (currentUrl.searchParams.get('bw_auth_callback') === '1') {
                    return false;
                }

                var codeTargetUrl = new URL(callbackUrl, window.location.origin);
                codeTargetUrl.searchParams.set('code', code);
                codeTargetUrl.searchParams.set('type', typeFromQuery);

                var state = currentUrl.searchParams.get('state') || '';
                var provider = currentUrl.searchParams.get('provider') || '';
                if (state) {
                    codeTargetUrl.searchParams.set('state', state);
                }
                if (provider) {
                    codeTargetUrl.searchParams.set('provider', provider);
                }

                window.location.replace(codeTargetUrl.toString());
                return true;
            }

            var hash = window.location.hash || '';
            if (!hash) {
                return false;
            }

            var params = new URLSearchParams(hash.replace(/^#/, ''));
            var type = params.get('type') || '';
            var hasAuthTokens = hash.indexOf('access_token=') !== -1 || hash.indexOf('refresh_token=') !== -1;

            if (!hasAuthTokens || (type !== 'invite' && type !== 'recovery') || !callbackUrl) {
                return false;
            }

            var currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.get('bw_auth_callback') === '1') {
                return false;
            }

            var targetUrl = new URL(callbackUrl, window.location.origin);
            targetUrl.hash = hash.replace(/^#/, '');
            window.location.replace(targetUrl.toString());
            return true;
        };

        var cleanAuthParams = function () {
            if (window.history && window.history.replaceState) {
                var cleanUrl = new URL(window.location.href);
                cleanUrl.hash = '';
                cleanUrl.searchParams.delete('code');
                cleanUrl.searchParams.delete('state');
                cleanUrl.searchParams.delete('provider');
                window.history.replaceState({}, document.title, cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : ''));
            }
        };

        var safeRedirect = function (targetUrl) {
            if (!targetUrl) {
                return false;
            }
            var currentUrl = new URL(window.location.href);
            var nextUrl = new URL(targetUrl, window.location.origin);
            if (currentUrl.pathname === nextUrl.pathname && currentUrl.search === nextUrl.search) {
                cleanHash();
                return false;
            }
            window.location.href = targetUrl;
            return true;
        };

        var redirectAfterBridge = function (payload) {
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

            cleanAuthParams();
            cleanHash();

            var target = payload.data && payload.data.redirect ? payload.data.redirect : '/my-account/';
            var attempts = 0;
            var maxAttempts = 2;

            var checkSession = function () {
                attempts += 1;
                return fetch(window.bwSupabaseBridge.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                    },
                    body: new URLSearchParams({
                        action: 'bw_supabase_check_wp_session',
                        nonce: window.bwSupabaseBridge.nonce
                    })
                })
                    .then(function (response) {
                        logDebug('WP session check', { status: response.status, attempt: attempts });
                        return response.json();
                    })
                    .then(function (payload) {
                        return payload && payload.success && payload.data && payload.data.loggedIn;
                    })
                    .catch(function () {
                        return false;
                    });
            };

            var scheduleRedirect = function () {
                logDebug('Bridge success -> redirecting to /my-account/');
                setTimeout(function () {
                    window.location.replace(target);
                }, redirectDelayMs);
            };

            var attemptRedirect = function () {
                checkSession().then(function (loggedIn) {
                    if (loggedIn || attempts > maxAttempts) {
                        scheduleRedirect();
                        return;
                    }
                    var backoff = attempts === 1 ? 200 : 600;
                    setTimeout(attemptRedirect, backoff);
                });
            };

            attemptRedirect();
            return true;
        };

        var bridgeSession = function (accessToken, refreshToken, context) {
            return fetch(window.bwSupabaseBridge.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'bw_supabase_token_login',
                    nonce: window.bwSupabaseBridge.nonce,
                    access_token: accessToken,
                    refresh_token: refreshToken,
                    type: context || ''
                })
            })
                .then(function (response) {
                    logDebug('WP bridge response', { status: response.status, context: context || 'hash' });
                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.success && window.sessionStorage) {
                        try {
                            sessionStorage.setItem(handledKey, 'done');
                        } catch (error) {
                            // ignore sessionStorage errors
                        }
                    }
                    redirectAfterBridge(payload);
                    return payload;
                });
        };

        var handleHashTokens = function () {
            var hash = window.location.hash.replace(/^#/, '');
            if (!hash) {
                return Promise.resolve(false);
            }

            logDebug('Supabase callback detected', { method: 'hash' });

            var params = new URLSearchParams(hash);
            var accessToken = params.get('access_token') || '';
            var refreshToken = params.get('refresh_token') || '';
            var inviteType = params.get('type') || '';

            if (!accessToken) {
                return Promise.resolve(false);
            }

            if (window.sessionStorage) {
                try {
                    sessionStorage.setItem('bw_supabase_access_token', accessToken);
                    if (refreshToken) {
                        sessionStorage.setItem('bw_supabase_refresh_token', refreshToken);
                    }
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            if (inviteType === 'invite') {
                return bridgeSession(accessToken, refreshToken, inviteType).then(function () {
                    return true;
                });
            }

            if (inviteType === 'recovery') {
                cleanHash();
                return Promise.resolve(true);
            }

            return bridgeSession(accessToken, refreshToken, inviteType).then(function () {
                return true;
            });
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

        var handleCodeCallback = function () {
            if (!authCode) {
                return Promise.resolve(false);
            }

            if (window.sessionStorage) {
                try {
                    if (sessionStorage.getItem(codeHandledKey) === '1') {
                        cleanAuthParams();
                        return Promise.resolve(true);
                    }
                    sessionStorage.setItem(codeHandledKey, '1');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            logDebug('Supabase callback detected', { method: 'code' });

            var projectUrl = window.bwSupabaseBridge.projectUrl || '';
            var anonKey = window.bwSupabaseBridge.anonKey || '';

            if (!projectUrl || !anonKey) {
                logDebug('Supabase PKCE exchange skipped', { reason: 'missing_config' });
                return Promise.resolve(false);
            }

            if (window.supabase && typeof window.supabase.createClient === 'function') {
                var client = window.supabase.createClient(projectUrl, anonKey);
                return client.auth.exchangeCodeForSession(authCode).then(function (response) {
                    if (response && response.error) {
                        throw response.error;
                    }
                    var session = response && response.data ? response.data.session : null;
                    if (!session) {
                        throw new Error('Supabase session missing after PKCE exchange.');
                    }
                    logDebug('Supabase code exchange success', { hasSession: true });
                    return bridgeSession(session.access_token || '', session.refresh_token || '', 'oauth').then(function () {
                        return true;
                    });
                }).catch(function (error) {
                    logDebug('Supabase code exchange failed', { message: error && error.message ? error.message : 'unknown' });
                    return false;
                });
            }

            var codeVerifier = getCodeVerifier();
            if (!codeVerifier) {
                logDebug('Supabase PKCE exchange skipped', { reason: 'missing_code_verifier' });
                return Promise.resolve(false);
            }

            return fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/token?grant_type=pkce', {
                method: 'POST',
                headers: {
                    apikey: anonKey,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    auth_code: authCode,
                    code_verifier: codeVerifier
                })
            })
                .then(function (response) {
                    logDebug('Supabase PKCE response', { status: response.status });
                    if (!response.ok) {
                        return response.json().then(function (payload) {
                            var message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : 'Supabase PKCE exchange failed.';
                            throw new Error(message);
                        });
                    }
                    return response.json();
                })
                .then(function (payload) {
                    var accessToken = payload.access_token || '';
                    var refreshToken = payload.refresh_token || '';
                    if (!accessToken) {
                        throw new Error('Supabase PKCE exchange missing token.');
                    }
                    return bridgeSession(accessToken, refreshToken, 'oauth').then(function () {
                        return true;
                    });
                })
                .catch(function (error) {
                    logDebug('Supabase PKCE exchange failed', { message: error && error.message ? error.message : 'unknown' });
                    return false;
                });
        };

        var waitForWpSession = function (onReady, onTimeout) {
            var attempts = 0;
            var maxAttempts = 30;
            var intervalMs = 250;

            var check = function () {
                attempts += 1;

                fetch(window.bwSupabaseBridge.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                    },
                    body: new URLSearchParams({
                        action: 'bw_supabase_check_wp_session',
                        nonce: window.bwSupabaseBridge.nonce
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        var loggedIn = Boolean(payload && payload.success && payload.data && payload.data.loggedIn);
                        if (loggedIn) {
                            onReady();
                            return;
                        }

                        if (attempts >= maxAttempts) {
                            onTimeout();
                            return;
                        }

                        setTimeout(check, intervalMs);
                    })
                    .catch(function () {
                        if (attempts >= maxAttempts) {
                            onTimeout();
                            return;
                        }
                        setTimeout(check, intervalMs);
                    });
            };

            check();
        };

        if (needsSetPassword && !authCode && !window.location.hash) {
            logDebug('Waiting WP session for set-password flow');
            waitForWpSession(
                function () {
                    window.location.reload();
                },
                function () {
                    var fallback = window.bwSupabaseBridge.accountUrl || '/my-account/';
                    window.location.replace(fallback);
                }
            );
            return;
        }

        if (redirectInviteToCallback()) {
            return;
        }

        if (authCode) {
            handleCodeCallback().then(function (handled) {
                if (handled) {
                    return;
                }
                handleHashTokens();
            });
            return;
        }

        if (handleInviteHashError()) {
            return;
        }

        handleHashTokens();
    });
})();
