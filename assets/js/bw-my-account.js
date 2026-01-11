(function() {
    const tabs = document.querySelectorAll('.bw-tab');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetSelector = tab.getAttribute('data-target');
            if (!targetSelector) {
                return;
            }

            const container = tab.closest('.bw-settings');
            if (!container) {
                return;
            }

            container.querySelectorAll('.bw-tab').forEach((btn) => btn.classList.remove('is-active'));
            container.querySelectorAll('.bw-tab-panel').forEach((panel) => panel.classList.remove('is-active'));

            tab.classList.add('is-active');
            const targetPanel = container.querySelector(targetSelector);
            if (targetPanel) {
                targetPanel.classList.add('is-active');
            }
        });
    });

    const setPasswordForm = document.querySelector('[data-bw-set-password-form]');
    if (!setPasswordForm || !window.bwAccountOnboarding) {
        return;
    }

    const errorBox = setPasswordForm.querySelector('.bw-account-set-password__error');
    const successBox = setPasswordForm.querySelector('.bw-account-set-password__success');
    const missingTokenBox = setPasswordForm.querySelector('[data-bw-missing-token]');
    const submitButton = setPasswordForm.querySelector('.bw-account-set-password__submit');
    const projectUrl = window.bwAccountOnboarding.projectUrl || '';
    const anonKey = window.bwAccountOnboarding.anonKey || '';
    const ajaxUrl = window.bwAccountOnboarding.ajaxUrl || '';
    const nonce = window.bwAccountOnboarding.nonce || '';
    const redirectUrl = window.bwAccountOnboarding.redirectUrl || '';
    const resendButton = document.querySelector('[data-bw-resend-invite]');
    const resendEmailInput = document.querySelector('[data-bw-resend-email]');
    const resendNotice = document.querySelector('[data-bw-resend-notice]');
    const userEmail = window.bwAccountOnboarding.userEmail || '';
    const searchParams = new URLSearchParams(window.location.search);
    const inviteEmailParam = searchParams.get('bw_invite_email') || '';

    const hash = window.location.hash.replace(/^#/, '');
    const params = new URLSearchParams(hash);
    let accessToken = params.get('access_token') || '';
    let refreshToken = params.get('refresh_token') || '';
    const errorCode = params.get('error_code') || '';
    const errorDescription = params.get('error_description') || '';
    const debugEnabled = Boolean(window.bwAccountOnboarding.debug);

    const showError = (message) => {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message;
        errorBox.hidden = false;
    };

    const showSuccess = (message) => {
        if (!successBox) {
            return;
        }
        successBox.textContent = message;
        successBox.hidden = false;
    };

    if (!accessToken && window.sessionStorage) {
        accessToken = sessionStorage.getItem('bw_supabase_access_token') || '';
        refreshToken = sessionStorage.getItem('bw_supabase_refresh_token') || '';
        if (accessToken) {
            sessionStorage.removeItem('bw_supabase_access_token');
            sessionStorage.removeItem('bw_supabase_refresh_token');
        }
    }

    if (debugEnabled) {
        console.log('[bw] Supabase invite tokens', {
            accessToken: accessToken ? 'present' : 'missing',
            refreshToken: refreshToken ? 'present' : 'missing',
            errorCode: errorCode || 'none'
        });
    }

    if (userEmail && resendEmailInput) {
        resendEmailInput.value = userEmail;
        resendEmailInput.readOnly = true;
    }

    if (!userEmail && inviteEmailParam && resendEmailInput) {
        resendEmailInput.value = decodeURIComponent(inviteEmailParam);
    }

    if (errorCode) {
        const message = errorCode === 'otp_expired'
            ? 'Link expired. Please request a new invite.'
            : decodeURIComponent(errorDescription.replace(/\+/g, ' ')) || 'The invite link is invalid.';
        showError(message);
        if (missingTokenBox) {
            missingTokenBox.hidden = false;
        }
        if (submitButton) {
            submitButton.disabled = true;
        }
    }

    if (!accessToken && !errorCode) {
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = '';
        }
        if (missingTokenBox) {
            missingTokenBox.hidden = false;
        }
        if (submitButton) {
            submitButton.disabled = true;
        }
    }

    if (resendButton && ajaxUrl && nonce) {
        resendButton.addEventListener('click', (event) => {
            event.preventDefault();
            if (resendNotice) {
                resendNotice.textContent = '';
                resendNotice.hidden = true;
            }

            const emailValue = resendEmailInput ? resendEmailInput.value.trim() : '';
            if (!emailValue) {
                showError('Please enter the email used for the purchase.');
                return;
            }

            resendButton.disabled = true;

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'bw_supabase_resend_invite',
                    nonce: nonce,
                    email: emailValue
                })
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload && payload.success) {
                        if (resendNotice) {
                            resendNotice.textContent = payload.data && payload.data.message ? payload.data.message : 'Invite sent.';
                            resendNotice.hidden = false;
                        }
                        return;
                    }

                    const message = payload && payload.data && payload.data.message ? payload.data.message : 'Unable to send invite.';
                    showError(message);
                })
                .catch(() => {
                    showError('Unable to send invite.');
                })
                .finally(() => {
                    resendButton.disabled = false;
                });
        });
    }

    setPasswordForm.addEventListener('submit', (event) => {
        event.preventDefault();

        if (errorBox) {
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        if (successBox) {
            successBox.textContent = '';
            successBox.hidden = true;
        }

        const newPassword = setPasswordForm.querySelector('input[name="new_password"]');
        const confirmPassword = setPasswordForm.querySelector('input[name="confirm_password"]');

        if (!newPassword || !confirmPassword) {
            showError('Password fields are missing.');
            return;
        }

        if (!accessToken) {
            if (missingTokenBox) {
                missingTokenBox.hidden = false;
            }
            return;
        }

        if (!projectUrl || !anonKey) {
            showError('Supabase configuration is missing.');
            return;
        }

        if (newPassword.value.trim() === '' || confirmPassword.value.trim() === '') {
            showError('Please enter and confirm your new password.');
            return;
        }

        if (newPassword.value !== confirmPassword.value) {
            showError('Passwords do not match.');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        fetch(projectUrl.replace(/\/$/, '') + '/auth/v1/user', {
            method: 'PUT',
            headers: {
                apikey: anonKey,
                Authorization: 'Bearer ' + accessToken,
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify({
                password: newPassword.value
            })
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((payload) => {
                        const message = payload && (payload.msg || payload.message) ? (payload.msg || payload.message) : 'Unable to update password.';
                        throw new Error(message);
                    });
                }
                return response.json();
            })
            .then(() => {
                if (!ajaxUrl || !nonce) {
                    showError('Unable to finalize login session.');
                    return;
                }

                return fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                    },
                    body: new URLSearchParams({
                        action: 'bw_supabase_token_login',
                        nonce: nonce,
                        access_token: accessToken
                    })
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (payload && payload.success && payload.data && payload.data.redirect) {
                            showSuccess('Password updated. Redirecting...');
                            if (window.history && window.history.replaceState) {
                                window.history.replaceState({}, document.title, window.location.pathname);
                            }
                            window.location.href = payload.data.redirect;
                            return;
                        }

                        const message = payload && payload.data && payload.data.message ? payload.data.message : 'Unable to create session.';
                        showError(message);
                    });
            })
            .catch((error) => {
                showError(error.message || 'Unable to update password.');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
    });
})();
