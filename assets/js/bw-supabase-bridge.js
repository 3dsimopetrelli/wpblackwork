(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.bwSupabaseBridge) {
            return;
        }

        var searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('logged_out')) {
            if (window.sessionStorage) {
                try {
                    sessionStorage.removeItem('bw_pending_otp_email');
                    sessionStorage.removeItem('bw_handled_supabase_hash');
                    sessionStorage.removeItem('bw_handled_token_login');
                    sessionStorage.removeItem('bw_handled_email_confirm');
                    sessionStorage.removeItem('bw_handled_session_check');
                    sessionStorage.removeItem('bw_supabase_access_token');
                    sessionStorage.removeItem('bw_supabase_refresh_token');
                } catch (error) {
                    // ignore sessionStorage errors
                }
            }
            if (window.localStorage) {
                try {
                    localStorage.removeItem('bw_pending_otp_email');
                    localStorage.removeItem('bw_onboarded');
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

        var handledKey = 'bw_handled_supabase_hash';
        if (window.sessionStorage) {
            try {
                if (sessionStorage.getItem(handledKey) === '1') {
                    if (window.history && window.history.replaceState) {
                        var cleanedUrl = new URL(window.location.href);
                        cleanedUrl.hash = '';
                        window.history.replaceState({}, document.title, cleanedUrl.pathname + (cleanedUrl.search ? cleanedUrl.search : ''));
                    }
                    return;
                }
            } catch (error) {
                // ignore sessionStorage errors
            }
        }

        var hash = window.location.hash.replace(/^#/, '');
        if (!hash) {
            return;
        }

        var params = new URLSearchParams(hash);
        var accessToken = params.get('access_token') || '';
        var refreshToken = params.get('refresh_token') || '';
        var inviteType = params.get('type') || '';

        if (!accessToken) {
            return;
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

        var cleanHash = function () {
            if (window.history && window.history.replaceState) {
                var cleanUrl = new URL(window.location.href);
                cleanUrl.hash = '';
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

        if (inviteType === 'invite') {
            if (window.bwSupabaseBridge && window.bwSupabaseBridge.setPasswordUrl) {
                var targetUrl = window.bwSupabaseBridge.setPasswordUrl;
                if (window.location.pathname.indexOf('/set-password') === -1) {
                    window.location.href = targetUrl + window.location.hash;
                } else {
                    cleanHash();
                }
            }
            return;
        }

        fetch(window.bwSupabaseBridge.ajaxUrl, {
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
                type: inviteType
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                cleanHash();
                if (payload && payload.success && payload.data && payload.data.redirect) {
                    safeRedirect(payload.data.redirect);
                }
            });
    });
})();
