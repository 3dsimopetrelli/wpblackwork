(function() {
    const initSettingsFloatingLabels = (scope = document) => {
        const root = scope && scope.querySelectorAll ? scope : document;
        const settingsContainers = root.querySelectorAll('.bw-settings');

        if (!settingsContainers.length) {
            return;
        }

        const getLabelText = (labelNode) => {
            if (!labelNode) {
                return '';
            }

            const clone = labelNode.cloneNode(true);
            clone.querySelectorAll('abbr, .optional, .required').forEach((node) => node.remove());
            return clone.textContent.replace(/\*/g, '').trim();
        };

        const findLabelForInput = (container, inputId) => {
            if (!inputId) {
                return null;
            }

            return container.querySelector('label[for="' + inputId.replace(/"/g, '\\"') + '"]');
        };

        settingsContainers.forEach((settingsContainer) => {
            const fields = settingsContainer.querySelectorAll(
                'input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="password"], textarea, select'
            );

            fields.forEach((input) => {
                if (!input.id || input.closest('.bw-field-wrapper') || input.dataset.bwSettingsFloatingInitialized === '1') {
                    return;
                }

                const fieldRow = input.closest('.form-row, .bw-field');
                if (!fieldRow) {
                    return;
                }

                const originalLabel = findLabelForInput(fieldRow, input.id);
                if (!originalLabel) {
                    return;
                }

                const labelText = getLabelText(originalLabel);
                if (!labelText) {
                    return;
                }

                let elementToWrap = input;
                if ('SELECT' === input.tagName) {
                    const select2Container = fieldRow.querySelector('.select2-container');
                    if (select2Container) {
                        elementToWrap = select2Container;
                    }
                }

                if (!elementToWrap.parentNode || elementToWrap.closest('.bw-field-wrapper')) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'bw-field-wrapper bw-field-wrapper--settings';
                if ('SELECT' === input.tagName) {
                    wrapper.classList.add('bw-field-wrapper--select');
                }
                elementToWrap.parentNode.insertBefore(wrapper, elementToWrap);
                wrapper.appendChild(elementToWrap);

                const floatingLabel = document.createElement('label');
                floatingLabel.className = 'bw-floating-label';
                floatingLabel.setAttribute('for', input.id);
                floatingLabel.textContent = labelText;
                wrapper.appendChild(floatingLabel);

                originalLabel.classList.add('bw-original-label-hidden');
                fieldRow.classList.add('bw-has-floating-label');
                if ('SELECT' === input.tagName) {
                    fieldRow.classList.add('bw-has-select2-floating');
                }

                const updateHasValue = () => {
                    const currentValue = (input.value || '').toString().trim();
                    wrapper.classList.toggle('has-value', currentValue !== '');
                };

                input.addEventListener('input', updateHasValue);
                input.addEventListener('change', updateHasValue);
                input.addEventListener('blur', updateHasValue);

                updateHasValue();
                input.dataset.bwSettingsFloatingInitialized = '1';
            });
        });
    };

    const scheduleSettingsFloatingLabels = () => {
        window.requestAnimationFrame(() => initSettingsFloatingLabels(document));
        window.setTimeout(() => initSettingsFloatingLabels(document), 180);
    };

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

            scheduleSettingsFloatingLabels();
        });
    });

    scheduleSettingsFloatingLabels();

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!target || !target.closest || !target.closest('.bw-settings')) {
            return;
        }

        if ('SELECT' === target.tagName || /_country$|_state$/.test(target.id || '')) {
            scheduleSettingsFloatingLabels();
        }
    });

    const setPasswordForm = document.querySelector('[data-bw-set-password-form]');
    const resendButton = document.querySelector('[data-bw-resend-invite]');
    const resendEmailInput = document.querySelector('[data-bw-resend-email]');
    const resendNotice = document.querySelector('[data-bw-resend-notice]');

    if (!window.bwAccountOnboarding) {
        return;
    }

    const errorBox = setPasswordForm ? setPasswordForm.querySelector('.bw-account-set-password__error') : document.querySelector('.bw-account-set-password__error');
    const successBox = setPasswordForm ? setPasswordForm.querySelector('.bw-account-set-password__success') : document.querySelector('.bw-account-set-password__success');
    const missingTokenBox = setPasswordForm ? setPasswordForm.querySelector('[data-bw-missing-token]') : document.querySelector('[data-bw-missing-token]');
    const submitButton = setPasswordForm ? setPasswordForm.querySelector('.bw-account-set-password__submit') : null;
    const projectUrl = window.bwAccountOnboarding.projectUrl || '';
    const anonKey = window.bwAccountOnboarding.anonKey || '';
    const ajaxUrl = window.bwAccountOnboarding.ajaxUrl || '';
    const nonce = window.bwAccountOnboarding.nonce || '';
    const redirectUrl = window.bwAccountOnboarding.redirectUrl || '';
    const userEmail = window.bwAccountOnboarding.userEmail || '';
    const searchParams = new URLSearchParams(window.location.search);
    const inviteEmailParam = searchParams.get('bw_invite_email') || '';

    const hash = window.location.hash.replace(/^#/, '');
    const params = new URLSearchParams(hash);
    let accessToken = params.get('access_token') || '';
    let refreshToken = params.get('refresh_token') || '';
    const tokenType = params.get('type') || '';
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
            errorCode: errorCode || 'none',
            type: tokenType || 'none'
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
    } else if (submitButton) {
        submitButton.disabled = false;
    }

    if (accessToken && missingTokenBox) {
        missingTokenBox.hidden = true;
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

    const passwordForm = document.querySelector('[data-bw-supabase-password-form]');
    const emailForm = document.querySelector('[data-bw-supabase-email-form]');
    const pendingEmailBanner = document.querySelector('[data-bw-pending-email-banner]');
    const shippingToggle = document.querySelector('#bw_shipping_same_as_billing');
    const shippingFields = document.querySelector('[data-bw-shipping-fields]');
    const passwordRuleConfig = [
        { id: 'length', test: (value) => value.length >= 8 },
        { id: 'lowercase', test: (value) => /[a-z]/.test(value) },
        { id: 'uppercase', test: (value) => /[A-Z]/.test(value) },
        { id: 'number', test: (value) => /[0-9]/.test(value) },
        { id: 'symbol', test: (value) => /[^A-Za-z0-9]/.test(value) }
    ];
    const passwordRuleItems = passwordForm ? Array.from(passwordForm.querySelectorAll('[data-bw-password-rule]')) : [];
    const passwordMissingSession = passwordForm ? passwordForm.querySelector('[data-bw-supabase-missing-session]') : null;
    const passwordInput = passwordForm ? passwordForm.querySelector('input[name="new_password"]') : null;
    const passwordConfirmInput = passwordForm ? passwordForm.querySelector('input[name="confirm_password"]') : null;
    const passwordSubmitButton = passwordForm ? passwordForm.querySelector('button[type="submit"]') : null;

    const updatePasswordRulesState = () => {
        if (!passwordForm || !passwordInput) {
            return true;
        }

        const value = passwordInput.value || '';
        let allRulesPass = true;

        passwordRuleConfig.forEach((rule) => {
            const passes = rule.test(value);
            const ruleItem = passwordRuleItems.find((item) => item.dataset.bwPasswordRule === rule.id);

            if (ruleItem) {
                ruleItem.classList.toggle('is-valid', passes);
            }

            if (!passes) {
                allRulesPass = false;
            }
        });

        return allRulesPass;
    };

    const updatePasswordSubmitState = () => {
        if (!passwordForm || !passwordSubmitButton) {
            return;
        }

        const rulesPass = updatePasswordRulesState();
        const passwordValue = passwordInput ? passwordInput.value.trim() : '';
        const confirmValue = passwordConfirmInput ? passwordConfirmInput.value.trim() : '';
        const confirmMatches = passwordValue !== '' && confirmValue !== '' && passwordValue === confirmValue;

        if (passwordForm.dataset.bwSubmitting === '1') {
            return;
        }

        passwordSubmitButton.disabled = !(rulesPass && confirmMatches);
    };

    const hidePasswordMissingSession = () => {
        if (passwordMissingSession) {
            passwordMissingSession.hidden = true;
        }
    };

    const submitSupabaseForm = (form, action, options = {}) => {
        if (!form) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector('.bw-account-form__error');
        const successBox = form.querySelector('.bw-account-form__success');
        const defaultMessage = options.defaultMessage || 'Unable to update settings.';

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!ajaxUrl || !nonce) {
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

            if (action === 'bw_supabase_update_password') {
                hidePasswordMissingSession();
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            const formData = new FormData(form);
            if (action === 'bw_supabase_update_email') {
                const emailValue = formData.get('email') ? formData.get('email').toString().trim() : '';
                const confirmValue = formData.get('confirm_email') ? formData.get('confirm_email').toString().trim() : '';

                if (!emailValue || !confirmValue || emailValue !== confirmValue) {
                    if (errorBox) {
                        errorBox.textContent = 'Email addresses do not match.';
                        errorBox.hidden = false;
                    }
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    return;
                }
            }

            if (action === 'bw_supabase_update_password') {
                const passwordValue = formData.get('new_password') ? formData.get('new_password').toString() : '';
                const confirmValue = formData.get('confirm_password') ? formData.get('confirm_password').toString() : '';
                const rulesPass = updatePasswordRulesState();

                if (!passwordValue || !confirmValue) {
                    if (errorBox) {
                        errorBox.textContent = 'Please enter and confirm your new password.';
                        errorBox.hidden = false;
                    }
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    return;
                }

                if (!rulesPass) {
                    if (errorBox) {
                        errorBox.textContent = 'Password does not meet the requirements.';
                        errorBox.hidden = false;
                    }
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    return;
                }

                if (passwordValue !== confirmValue) {
                    if (errorBox) {
                        errorBox.textContent = 'Passwords do not match.';
                        errorBox.hidden = false;
                    }
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    return;
                }
            }

            formData.append('action', action);
            formData.append('nonce', nonce);

            form.dataset.bwSubmitting = '1';
            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams(formData)
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload && payload.success) {
                        if (successBox) {
                            successBox.textContent = (payload.data && payload.data.message) ? payload.data.message : 'Saved.';
                            successBox.hidden = false;
                        }

                        if (action === 'bw_supabase_update_email' && pendingEmailBanner && payload.data && payload.data.pendingEmail) {
                            pendingEmailBanner.textContent = 'Confirm your new email address (' + payload.data.pendingEmail + ') from the confirmation email we sent you.';
                            pendingEmailBanner.hidden = false;
                        }

                        if (action === 'bw_supabase_update_password') {
                            const passwordFields = form.querySelectorAll('input[type="password"]');
                            passwordFields.forEach((field) => {
                                field.value = '';
                            });
                        }

                        return;
                    }

                    const message = payload && payload.data && payload.data.message ? payload.data.message : defaultMessage;
                    if (errorBox) {
                        errorBox.textContent = message;
                        errorBox.hidden = false;
                    }

                    if (action === 'bw_supabase_update_password' && message === 'Supabase session is missing.' && passwordMissingSession) {
                        passwordMissingSession.hidden = false;
                    }
                })
                .catch(() => {
                    if (errorBox) {
                        errorBox.textContent = defaultMessage;
                        errorBox.hidden = false;
                    }
                })
                .finally(() => {
                    form.dataset.bwSubmitting = '';
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    if (action === 'bw_supabase_update_password') {
                        updatePasswordSubmitState();
                    }
                });
        });
    };

    submitSupabaseForm(passwordForm, 'bw_supabase_update_password', {
        defaultMessage: 'Unable to update password.'
    });
    submitSupabaseForm(emailForm, 'bw_supabase_update_email', {
        defaultMessage: 'Unable to update email.'
    });

    if (passwordForm) {
        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                hidePasswordMissingSession();
                updatePasswordSubmitState();
            });
        }

        if (passwordConfirmInput) {
            passwordConfirmInput.addEventListener('input', () => {
                hidePasswordMissingSession();
                updatePasswordSubmitState();
            });
        }

        updatePasswordSubmitState();
    }

    if (shippingToggle && shippingFields) {
        const toggleShippingFields = () => {
            const shouldHide = shippingToggle.checked;
            shippingFields.hidden = shouldHide;
        };
        shippingToggle.addEventListener('change', toggleShippingFields);
        toggleShippingFields();
    }

    if (!setPasswordForm) {
        return;
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
                        access_token: accessToken,
                        refresh_token: refreshToken
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
