(function () {
    document.addEventListener('DOMContentLoaded', function () {
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

        var hash = window.location.hash || '';
        if (hash.indexOf('access_token=') !== -1 && window.bwAccountAuth && window.bwAccountAuth.ajaxUrl) {
            var params = new URLSearchParams(hash.replace(/^#/, ''));
            var accessToken = params.get('access_token');
            var refreshToken = params.get('refresh_token');
            var authType = params.get('type');
            var autoLoginAfterConfirm = window.bwAccountAuth ? Boolean(window.bwAccountAuth.autoLoginAfterConfirm) : false;
            var shouldHandleToken = authType !== 'invite' && (authType !== 'signup' || autoLoginAfterConfirm);

            if (accessToken && shouldHandleToken) {
                fetch(window.bwAccountAuth.ajaxUrl, {
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
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({}, document.title, window.location.pathname + window.location.search);
                        }
                    });
            }
        }

        var projectUrl = window.bwAccountAuth ? window.bwAccountAuth.projectUrl : '';
        var anonKey = window.bwAccountAuth ? window.bwAccountAuth.anonKey : '';
        var magicLinkRedirect = window.bwAccountAuth ? window.bwAccountAuth.magicLinkRedirectUrl : '';
        var oauthRedirect = window.bwAccountAuth ? window.bwAccountAuth.oauthRedirectUrl : '';
        var signupRedirect = window.bwAccountAuth ? window.bwAccountAuth.signupRedirectUrl : '';
        var magicLinkEnabled = window.bwAccountAuth ? Boolean(window.bwAccountAuth.magicLinkEnabled) : true;
        var oauthGoogleEnabled = window.bwAccountAuth ? Boolean(window.bwAccountAuth.oauthGoogleEnabled) : true;
        var oauthFacebookEnabled = window.bwAccountAuth ? Boolean(window.bwAccountAuth.oauthFacebookEnabled) : true;
        var debugEnabled = window.bwAccountAuth ? Boolean(window.bwAccountAuth.debug) : false;
        var registrationMode = window.bwAccountAuth ? window.bwAccountAuth.registrationMode : 'R2';
        var messages = window.bwAccountAuth && window.bwAccountAuth.messages ? window.bwAccountAuth.messages : {};

        var magicLinkForm = document.querySelector('[data-bw-supabase-form][data-bw-supabase-action="magic-link"]');
        var passwordLoginForm = document.querySelector('[data-bw-supabase-form][data-bw-supabase-action="password-login"]');
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
        var goMagicButtons = Array.prototype.slice.call(document.querySelectorAll('[data-bw-go-magic]'));
        var getMessage = function (key, fallback) {
            if (messages && Object.prototype.hasOwnProperty.call(messages, key)) {
                return messages[key];
            }
            return fallback;
        };

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

        var setStepState = function (step, isActive) {
            setFieldsState(step, isActive);
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
                return input.value.replace(/\D/g, '');
            }).join('');
        };

        var setOtpInputsError = function (hasError) {
            if (!otpInputsWrap) {
                return;
            }
            otpInputsWrap.classList.toggle('is-error', hasError);
        };

        var updateOtpConfirmState = function () {
            if (!otpConfirmButton) {
                return;
            }
            var code = getOtpCode();
            otpConfirmButton.disabled = code.length !== 6;
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
                updateOtpConfirmState();
            }
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

        submitAjaxForm(passwordLoginForm, 'bw_supabase_login', {
            defaultError: getMessage('loginError', 'Unable to login.')
        });

        if (goPasswordButton) {
            goPasswordButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                switchAuthScreen('password');
            });
        }

        if (goMagicButtons.length) {
            goMagicButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    switchAuthScreen('magic');
                });
            });
        }

        if (oauthButtons.length) {
            oauthButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!projectUrl) {
                        return;
                    }
                    var provider = button.getAttribute('data-bw-oauth-provider');
                    if (provider === 'google' && !oauthGoogleEnabled) {
                        return;
                    }
                    if (provider === 'facebook' && !oauthFacebookEnabled) {
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

        if (registerForm) {
            var initialStep = registerForm.querySelector('[data-bw-register-step].is-active');
            var initialStepName = initialStep ? initialStep.getAttribute('data-bw-register-step') : 'email';
            showRegisterStep(initialStepName || 'email');
        }

        if (authScreens.length) {
            var storedEmail = getPendingOtpEmail();
            if (storedEmail) {
                switchAuthScreen('otp');
            } else {
                switchAuthScreen('magic');
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
                        var value = input.value.replace(/\D/g, '');
                        input.value = value;
                        if (value && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                        setOtpInputsError(false);
                        updateOtpConfirmState();
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
                        updateOtpConfirmState();
                    });
                }
            }

            otpForm.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var emailValue = getPendingOtpEmail();
                if (!emailValue) {
                    switchAuthScreen('magic');
                    showFormMessage(magicLinkForm, 'error', getMessage('enterEmail', 'Please enter your email address.'));
                    return;
                }

                var code = getOtpCode();
                if (code.length !== 6) {
                    showFormMessage(otpForm, 'error', getMessage('enterOtp', 'Please enter the 6-digit code.'));
                    setOtpInputsError(true);
                    return;
                }

                showFormMessage(otpForm, 'error', '');
                showFormMessage(otpForm, 'success', '');
                setOtpInputsError(false);

                verifyOtp(emailValue, code)
                    .then(function (response) {
                        if (response && response.error) {
                            throw response.error;
                        }
                        setPendingOtpEmail('');
                        window.location.href = magicLinkRedirect || window.location.origin + '/my-account/';
                    })
                    .catch(function (error) {
                        var message = getMessage('otpVerifyError', 'Unable to verify the code.');
                        if (error && error.message && error.message.toLowerCase().indexOf('expired') !== -1) {
                            message = getMessage('otpInvalid', 'Invalid or expired code. Please try again.');
                        }
                        showFormMessage(otpForm, 'error', message);
                        setOtpInputsError(true);
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
         */
    });
})();
