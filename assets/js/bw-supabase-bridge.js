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
                if (sessionStorage.getItem(handledKey) === '1') {
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
                    sessionStorage.setItem(handledKey, '1');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }

            if (inviteType === 'invite') {
                if (window.bwSupabaseBridge && window.bwSupabaseBridge.setPasswordUrl) {
                    var targetUrl = window.bwSupabaseBridge.setPasswordUrl;
                    if (window.location.pathname.indexOf('/set-password') === -1) {
                        window.location.href = targetUrl + window.location.hash;
                    } else {
                        cleanHash();
                    }
                }
                return Promise.resolve(true);
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

        if (authCode) {
            handleCodeCallback().then(function (handled) {
                if (handled) {
                    return;
                }
                handleHashTokens();
            });
            return;
        }

        handleHashTokens();
    });
})();
