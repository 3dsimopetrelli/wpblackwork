(function () {
    if (!window.bwSupabaseBridge) {
        return;
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
        sessionStorage.setItem('bw_supabase_access_token', accessToken);
        if (refreshToken) {
            sessionStorage.setItem('bw_supabase_refresh_token', refreshToken);
        }
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
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (payload && payload.success && payload.data && payload.data.redirect) {
                window.location.href = window.bwSupabaseBridge.setPasswordUrl;
            }
        });
})();
