(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    const SELECTORS = {
        widget: '[data-review-widget]',
        config: '.bw-reviews-config',
        grid: '[data-review-grid]',
        footer: '[data-review-footer]',
        loadMore: '[data-review-load-more]',
        writeButton: '[data-review-open="create"]',
        editButton: '[data-review-edit]',
        summaryTrigger: '[data-review-summary-trigger]',
        breakdown: '[data-review-breakdown]',
        sortTrigger: '[data-review-sort-trigger]',
        sortMenu: '[data-review-sort-menu]',
        sortOption: '.bw-reviews-sort__option',
        modal: '[data-review-modal]',
        modalDialog: '[data-review-modal-dialog]',
        modalTitle: '[data-review-modal-title]',
        modalSubtitle: '[data-review-modal-subtitle]',
        modalProgress: '[data-review-progress]',
        modalProgressBars: '[data-review-progress-bars] span',
        modalMessage: '[data-review-message]',
        modalClose: '[data-review-close]',
        modalBack: '[data-review-back]',
        modalNext: '[data-review-next]',
        modalSubmit: '[data-review-submit]',
        modalFinish: '[data-review-finish]',
        modalStep: '[data-step]',
        modalField: '[data-review-field]',
        modalRatingStar: '.bw-reviews-rating-picker__star',
        modalUserIdentity: '[data-review-identity-user]',
        modalGuestIdentity: '[data-review-identity-guest]',
        modalUserName: '[data-review-user-name]',
        modalUserEmail: '[data-review-user-email]',
        doneTitle: '[data-review-done-title]',
        doneMessage: '[data-review-done-message]',
    };

    const STEP_ORDER = ['rating', 'content', 'identity', 'done'];
    const CONFIRMATION_MESSAGES = {
        confirmed_approved: 'confirmedLive',
        confirmed_pending: 'confirmedPending',
    };

    class AjaxClient {
        request(url, data) {
            return $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
            });
        }

        loadReviews(config, payload) {
            return this.request(config.ajaxUrl, {
                action: 'bw_reviews_load_reviews',
                nonce: config.nonce,
                product_id: config.productId,
                sort: payload.sort,
                offset: payload.offset,
                limit: payload.limit,
            });
        }

        submitReview(config, payload) {
            return this.request(
                config.ajaxUrl,
                $.extend(
                    {
                        action: 'bw_reviews_submit',
                        nonce: config.nonce,
                        product_id: config.productId,
                    },
                    payload
                )
            );
        }

        getEditReview(config, reviewId) {
            return this.request(config.ajaxUrl, {
                action: 'bw_reviews_get_edit_review',
                nonce: config.nonce,
                review_id: reviewId,
            });
        }

        updateReview(config, payload) {
            return this.request(
                config.ajaxUrl,
                $.extend(
                    {
                        action: 'bw_reviews_update_review',
                        nonce: config.nonce,
                    },
                    payload
                )
            );
        }
    }

    class ModalController {
        constructor() {
            this.$root = $(SELECTORS.modal).first();
            this.$dialog = this.$root.find(SELECTORS.modalDialog);
            this.$title = this.$root.find(SELECTORS.modalTitle);
            this.$subtitle = this.$root.find(SELECTORS.modalSubtitle);
            this.$progress = this.$root.find(SELECTORS.modalProgress);
            this.$progressBars = this.$root.find(SELECTORS.modalProgressBars);
            this.$message = this.$root.find(SELECTORS.modalMessage);
            this.$back = this.$root.find(SELECTORS.modalBack);
            this.$next = this.$root.find(SELECTORS.modalNext);
            this.$submit = this.$root.find(SELECTORS.modalSubmit);
            this.$finish = this.$root.find(SELECTORS.modalFinish);
            this.$steps = this.$root.find(SELECTORS.modalStep);
            this.$doneTitle = this.$root.find(SELECTORS.doneTitle);
            this.$doneMessage = this.$root.find(SELECTORS.doneMessage);
            this.$guestIdentity = this.$root.find(SELECTORS.modalGuestIdentity);
            this.$userIdentity = this.$root.find(SELECTORS.modalUserIdentity);
            this.$userName = this.$root.find(SELECTORS.modalUserName);
            this.$userEmail = this.$root.find(SELECTORS.modalUserEmail);
            this.currentWidget = null;
            this.state = this.getDefaultState();
            this.bound = false;

            if (this.$root.length) {
                $('body').append(this.$root);
                this.bind();
            }
        }

        getDefaultState() {
            return {
                mode: 'create',
                step: 'rating',
                dirty: false,
                isSubmitting: false,
                reviewId: 0,
                rating: 0,
                content: '',
                identity: {
                    first_name: '',
                    last_name: '',
                    email: '',
                },
                privacyAccepted: false,
            };
        }

        isAvailable() {
            return this.$root.length > 0;
        }

        isOpen() {
            return this.isAvailable() && !this.$root.prop('hidden');
        }

        bind() {
            if (this.bound || !this.isAvailable()) {
                return;
            }

            this.$root.on('click', SELECTORS.modalClose, (event) => {
                event.preventDefault();
                this.close();
            });

            this.$root.on('click', SELECTORS.modalRatingStar, (event) => {
                event.preventDefault();
                const rating = parseInt($(event.currentTarget).data('ratingValue'), 10) || 0;
                this.state.rating = rating;
                this.state.dirty = true;
                this.clearMessage();
                this.syncStateToUi();
            });

            this.$root.on('click', SELECTORS.modalNext, (event) => {
                event.preventDefault();
                this.goForward();
            });

            this.$root.on('click', SELECTORS.modalBack, (event) => {
                event.preventDefault();
                this.goBack();
            });

            this.$root.on('click', SELECTORS.modalSubmit, (event) => {
                event.preventDefault();
                this.submit();
            });

            this.$root.on('click', SELECTORS.modalFinish, (event) => {
                event.preventDefault();
                this.close(true);
            });

            this.$root.on('input change', SELECTORS.modalField, (event) => {
                this.handleFieldChange($(event.currentTarget));
            });

            $(document).on('keydown.bwReviewsModal', (event) => {
                if ('Escape' === event.key && this.isOpen()) {
                    this.close();
                }
            });

            this.bound = true;
        }

        openCreate(widget) {
            if (!this.isAvailable()) {
                return;
            }

            this.currentWidget = widget;
            this.state = this.getDefaultState();
            this.state.mode = 'create';
            this.seedIdentityFromWidget();
            this.open();
            this.syncStateToUi();
        }

        openEdit(widget, payload) {
            if (!this.isAvailable()) {
                return;
            }

            this.currentWidget = widget;
            this.state = this.getDefaultState();
            this.state.mode = 'edit';
            this.state.reviewId = payload && payload.reviewId ? Number(payload.reviewId) : 0;
            this.state.rating = payload && payload.prefill ? Number(payload.prefill.rating || 0) : 0;
            this.state.content = payload && payload.prefill ? String(payload.prefill.content || '') : '';
            this.seedIdentityFromWidget(payload && payload.identity ? payload.identity : null);
            this.open();
            this.syncStateToUi();
        }

        openConfirmation(widget, message) {
            if (!this.isAvailable()) {
                return;
            }

            this.currentWidget = widget || this.currentWidget;
            this.state = this.getDefaultState();
            this.state.mode = 'confirmation_notice';
            this.state.step = 'done';
            this.open();
            this.$doneTitle.text('Thank you');
            this.$doneMessage.text(message);
            this.$title.text('Thank you');
            this.$subtitle.prop('hidden', true).text('');
            this.syncStateToUi();
        }

        open() {
            if (!this.isAvailable()) {
                return;
            }

            this.$root.prop('hidden', false);
            $('body').addClass('bw-reviews-modal-open');
            window.setTimeout(() => {
                this.$dialog.trigger('focus');
            }, 20);
        }

        close(forceClose) {
            if (!this.isAvailable() || !this.isOpen()) {
                return;
            }

            if (!forceClose && this.shouldConfirmClose() && !window.confirm(this.getString('closeConfirm', 'Close this review form? Your progress will be lost.'))) {
                return;
            }

            this.$root.prop('hidden', true);
            $('body').removeClass('bw-reviews-modal-open');
            this.currentWidget = null;
            this.state = this.getDefaultState();
            this.clearMessage();
            this.syncStateToUi();
        }

        shouldConfirmClose() {
            return this.state.mode !== 'confirmation_notice' && this.state.step !== 'done' && this.state.dirty;
        }

        handleFieldChange($field) {
            const field = String($field.data('reviewField') || '');

            if ('privacy_ack' === field) {
                this.state.privacyAccepted = $field.is(':checked');
            } else if (Object.prototype.hasOwnProperty.call(this.state.identity, field)) {
                this.state.identity[field] = String($field.val() || '');
            } else if ('content' === field) {
                this.state.content = String($field.val() || '');
            }

            this.state.dirty = true;
            this.clearMessage();
            this.syncStateToUi();
        }

        seedIdentityFromWidget(identity) {
            const widgetIdentity = identity || (this.currentWidget ? this.currentWidget.config.identity || {} : {});

            this.state.identity = {
                first_name: '',
                last_name: '',
                email: widgetIdentity && widgetIdentity.email ? String(widgetIdentity.email) : '',
            };

            if (this.currentWidget && this.currentWidget.config.isLoggedIn) {
                this.$userName.text(widgetIdentity && widgetIdentity.displayName ? String(widgetIdentity.displayName) : '');
                this.$userEmail.text(widgetIdentity && widgetIdentity.email ? String(widgetIdentity.email) : '');
            }
        }

        goForward() {
            if ('rating' === this.state.step) {
                if (!this.validateRating()) {
                    return;
                }

                this.state.step = 'content';
                this.syncStateToUi();
                return;
            }

            if ('content' === this.state.step) {
                if (!this.validateContent()) {
                    return;
                }

                this.state.step = 'identity';
                this.syncStateToUi();
            }
        }

        goBack() {
            if ('content' === this.state.step) {
                this.state.step = 'rating';
            } else if ('identity' === this.state.step) {
                this.state.step = 'content';
            }

            this.clearMessage();
            this.syncStateToUi();
        }

        submit() {
            if (!this.currentWidget || this.state.isSubmitting) {
                return;
            }

            if (!this.validateIdentity()) {
                return;
            }

            this.state.isSubmitting = true;
            this.syncStateToUi();

            const request = 'edit' === this.state.mode
                ? this.currentWidget.ajaxClient.updateReview(this.currentWidget.config, this.currentWidget.buildUpdatePayload(this.state))
                : this.currentWidget.ajaxClient.submitReview(this.currentWidget.config, this.currentWidget.buildSubmissionPayload(this.state));

            request.done((response) => {
                if (!response || !response.success || !response.data) {
                    this.showMessage(this.getString('validation', 'Please complete the required fields.'));
                    return;
                }

                this.handleSuccessfulSubmission(response.data);
            }).fail((xhr) => {
                const data = xhr && xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : null;
                const message = data && data.message ? data.message : this.getString('validation', 'Please complete the required fields.');
                this.showMessage(message);
            }).always(() => {
                this.state.isSubmitting = false;
                this.syncStateToUi();
            });
        }

        handleSuccessfulSubmission(data) {
            if ('create' === this.state.mode && this.currentWidget && data.reviewId) {
                this.currentWidget.state.ownedReviewId = Number(data.reviewId);
            }

            this.state.step = 'done';
            this.state.dirty = false;
            this.$title.text('Thank you');
            this.$subtitle.prop('hidden', true).text('');
            this.$doneTitle.text('Thank you');
            this.$doneMessage.text(String(data.message || ''));
            this.clearMessage();
            this.syncStateToUi();
        }

        validateRating() {
            if (this.state.rating >= 1 && this.state.rating <= 5) {
                this.clearMessage();
                return true;
            }

            this.showMessage(this.getString('ratingRequired', 'Please select a rating.'));
            return false;
        }

        validateContent() {
            if ($.trim(this.state.content).length > 0) {
                this.clearMessage();
                return true;
            }

            this.showMessage(this.getString('contentRequired', 'Please share your review before continuing.'));
            return false;
        }

        validateIdentity() {
            if (!this.currentWidget) {
                return false;
            }

            if (!this.currentWidget.config.isLoggedIn) {
                if (!$.trim(this.state.identity.first_name)) {
                    this.showMessage(this.getString('firstNameRequired', 'First name is required.'));
                    return false;
                }

                if (!this.isValidEmail(this.state.identity.email)) {
                    this.showMessage(this.getString('emailRequired', 'A valid email address is required.'));
                    return false;
                }
            }

            if (!this.state.privacyAccepted) {
                this.showMessage(this.getString('privacyRequired', 'Please accept the terms and privacy acknowledgement before continuing.'));
                return false;
            }

            this.clearMessage();
            return true;
        }

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($.trim(String(email || '')));
        }

        showMessage(message) {
            this.$message.text(message).prop('hidden', false).addClass('is-visible');
        }

        clearMessage() {
            this.$message.text('').prop('hidden', true).removeClass('is-visible');
        }

        syncStateToUi() {
            if (!this.isAvailable()) {
                return;
            }

            this.syncTitles();
            this.syncSteps();
            this.syncFields();
            this.syncIdentityMode();
            this.syncRatingPicker();
            this.syncProgress();
            this.syncFooter();
        }

        syncTitles() {
            const isConfirmation = 'confirmation_notice' === this.state.mode;
            const modalTitle = 'edit' === this.state.mode
                ? this.getString('editReview', 'Edit your review')
                : this.getString('writeReview', 'Write a review');

            this.$title.text('done' === this.state.step || isConfirmation ? 'Thank you' : modalTitle);
            this.$subtitle.prop('hidden', true).text('');
        }

        syncSteps() {
            const currentStep = this.state.step;

            this.$steps.each(function () {
                const $step = $(this);
                const isActive = $step.data('step') === currentStep;
                $step.toggleClass('is-active', isActive).prop('hidden', !isActive);
            });
        }

        syncFields() {
            const fields = this.state.identity;
            const $content = this.$root.find('[data-review-field="content"]');
            const $firstName = this.$root.find('[data-review-field="first_name"]');
            const $lastName = this.$root.find('[data-review-field="last_name"]');
            const $email = this.$root.find('[data-review-field="email"]');
            const $privacy = this.$root.find('[data-review-field="privacy_ack"]');

            if ($content.val() !== this.state.content) {
                $content.val(this.state.content);
            }

            if ($firstName.val() !== fields.first_name) {
                $firstName.val(fields.first_name);
            }

            if ($lastName.val() !== fields.last_name) {
                $lastName.val(fields.last_name);
            }

            if ($email.val() !== fields.email && !(this.currentWidget && this.currentWidget.config.isLoggedIn)) {
                $email.val(fields.email);
            }

            $privacy.prop('checked', this.state.privacyAccepted);
        }

        syncIdentityMode() {
            const isLoggedIn = !!(this.currentWidget && this.currentWidget.config.isLoggedIn);
            this.$guestIdentity.prop('hidden', isLoggedIn);
            this.$userIdentity.prop('hidden', !isLoggedIn);
        }

        syncRatingPicker() {
            this.$root.find(SELECTORS.modalRatingStar).each((index, element) => {
                const $star = $(element);
                const rating = parseInt($star.data('ratingValue'), 10) || 0;
                $star.toggleClass('is-selected', rating <= this.state.rating);
            });
        }

        syncProgress() {
            const isDone = 'done' === this.state.step || 'confirmation_notice' === this.state.mode;
            const stepIndex = Math.max(0, STEP_ORDER.indexOf(this.state.step));
            const currentStepNumber = isDone ? 4 : stepIndex + 1;

            this.$progress.text(
                this.getString('step', 'Step') + ' ' + currentStepNumber + ' ' + this.getString('of', 'of') + ' 4'
            );
            this.$progress.toggle(!isDone);

            this.$progressBars.each((index, element) => {
                const $bar = $(element);
                $bar.toggleClass('is-complete', index < stepIndex || ('done' === this.state.step && index < 4));
                $bar.toggleClass('is-active', !isDone && index === stepIndex);
            });

            this.$progressBars.parent().toggle(!isDone);
        }

        syncFooter() {
            const step = this.state.step;
            const isDone = 'done' === step || 'confirmation_notice' === this.state.mode;
            const canGoNext = ('rating' === step && this.state.rating > 0)
                || ('content' === step && $.trim(this.state.content).length > 0);
            const canSubmit = this.currentWidget && this.currentWidget.config.isLoggedIn
                ? this.state.privacyAccepted
                : !!($.trim(this.state.identity.first_name) && this.isValidEmail(this.state.identity.email) && this.state.privacyAccepted);

            this.$back.prop('hidden', 'rating' === step || isDone);
            this.$next.prop('hidden', !('rating' === step || 'content' === step));
            this.$submit.prop('hidden', 'identity' !== step);
            this.$finish.prop('hidden', !isDone);

            this.$next.prop('disabled', !canGoNext || this.state.isSubmitting);
            this.$submit.prop('disabled', !canSubmit || this.state.isSubmitting);
            this.$finish.prop('disabled', false);

            if ('edit' === this.state.mode) {
                this.$submit.text(this.getString('saveChanges', 'Save changes'));
            } else {
                this.$submit.text(this.getString('submit', 'Submit review'));
            }

            if (this.state.isSubmitting) {
                this.$submit.attr('aria-busy', 'true');
            } else {
                this.$submit.removeAttr('aria-busy');
            }
        }

        getString(key, fallback) {
            if (this.currentWidget && this.currentWidget.config && this.currentWidget.config.strings && this.currentWidget.config.strings[key]) {
                return this.currentWidget.config.strings[key];
            }

            return fallback;
        }
    }

    class WidgetController {
        constructor(element, ajaxClient, modalController) {
            this.element = element;
            this.$root = $(element);
            this.config = this.parseConfig();
            this.instanceId = String(this.config.instanceId || this.$root.data('instanceId') || '');
            this.namespace = '.bwReviewsWidget-' + this.instanceId;
            this.ajaxClient = ajaxClient;
            this.modalController = modalController;
            this.pendingListRequest = null;
            this.lastRequestToken = 0;
            this.state = {
                productId: Number(this.config.productId || 0),
                sort: this.config.sortDefault || 'featured',
                shownCount: Number(this.config.shownCount || 0),
                hasMore: !!this.config.hasMore,
                ownedReviewId: Number(this.config.ownedReviewId || 0),
                isLoadingList: false,
            };

            this.cacheDom();
            this.bind();
            this.syncSortUi();
            this.syncFooter();
        }

        parseConfig() {
            const raw = this.$root.find(SELECTORS.config).first().text();

            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (error) {
                return {};
            }
        }

        cacheDom() {
            this.$grid = this.$root.find(SELECTORS.grid);
            this.$footer = this.$root.find(SELECTORS.footer);
            this.$loadMore = this.$root.find(SELECTORS.loadMore);
            this.$summaryTrigger = this.$root.find(SELECTORS.summaryTrigger);
            this.$breakdown = this.$root.find(SELECTORS.breakdown);
            this.$sortTrigger = this.$root.find(SELECTORS.sortTrigger);
            this.$sortMenu = this.$root.find(SELECTORS.sortMenu);
            this.originalLoadMoreLabel = this.$loadMore.text();
        }

        bind() {
            this.$root.off(this.namespace);

            this.$root.on('click' + this.namespace, SELECTORS.summaryTrigger, (event) => {
                event.preventDefault();
                this.toggleBreakdown();
            });

            this.$root.on('click' + this.namespace, SELECTORS.sortTrigger, (event) => {
                event.preventDefault();
                this.toggleSortMenu();
            });

            this.$root.on('click' + this.namespace, SELECTORS.sortOption, (event) => {
                event.preventDefault();
                this.applySort(String($(event.currentTarget).data('sortValue') || 'featured'));
            });

            this.$root.on('click' + this.namespace, SELECTORS.loadMore, (event) => {
                event.preventDefault();
                this.loadReviews(true);
            });

            this.$root.on('click' + this.namespace, SELECTORS.writeButton, (event) => {
                event.preventDefault();
                if (this.modalController) {
                    this.modalController.openCreate(this);
                }
            });

            this.$root.on('click' + this.namespace, SELECTORS.editButton, (event) => {
                event.preventDefault();
                this.openEditModal(parseInt($(event.currentTarget).data('reviewEdit'), 10) || 0);
            });
        }

        destroy() {
            if (this.pendingListRequest && this.pendingListRequest.abort) {
                this.pendingListRequest.abort();
            }

            this.$root.off(this.namespace);
        }

        handleDocumentClick(target) {
            if (!this.$sortMenu.length || this.$sortMenu.prop('hidden')) {
                return;
            }

            if (!$(target).closest(this.$root.find('.bw-reviews-sort')).length) {
                this.closeSortMenu();
            }
        }

        toggleBreakdown() {
            if (!this.$breakdown.length || this.$summaryTrigger.is(':disabled')) {
                return;
            }

            const expanded = 'true' === String(this.$summaryTrigger.attr('aria-expanded'));
            this.$summaryTrigger.attr('aria-expanded', expanded ? 'false' : 'true');
            this.$breakdown.toggleClass('is-open', !expanded);
        }

        toggleSortMenu() {
            if (!this.$sortTrigger.length || this.$sortTrigger.is(':disabled')) {
                return;
            }

            const expanded = 'true' === String(this.$sortTrigger.attr('aria-expanded'));
            window.BWReviews.closeAllSortMenus(this.instanceId);
            this.$sortTrigger.attr('aria-expanded', expanded ? 'false' : 'true');
            this.$sortMenu.prop('hidden', expanded);
        }

        closeSortMenu() {
            this.$sortTrigger.attr('aria-expanded', 'false');
            this.$sortMenu.prop('hidden', true);
        }

        syncSortUi() {
            this.$root.find(SELECTORS.sortOption).each((index, element) => {
                const $option = $(element);
                const isSelected = String($option.data('sortValue') || '') === this.state.sort;
                $option.toggleClass('is-selected', isSelected).attr('aria-pressed', isSelected ? 'true' : 'false');
            });
        }

        applySort(sort) {
            if (!sort) {
                return;
            }

            if (sort === this.state.sort) {
                this.closeSortMenu();
                return;
            }

            this.state.sort = sort;
            this.syncSortUi();
            this.closeSortMenu();
            this.loadReviews(false);
        }

        loadReviews(append) {
            if (!this.config.ajaxUrl || !this.config.nonce || !this.state.productId) {
                return;
            }

            const requestToken = ++this.lastRequestToken;
            const offset = append ? this.state.shownCount : 0;
            const limit = append ? Number(this.config.loadMoreCount || 4) : Number(this.config.initialVisibleCount || 4);

            if (this.pendingListRequest && this.pendingListRequest.abort) {
                this.pendingListRequest.abort();
            }

            this.state.isLoadingList = true;
            this.syncLoadingState(true, append);

            this.pendingListRequest = this.ajaxClient.loadReviews(this.config, {
                sort: this.state.sort,
                offset: offset,
                limit: limit,
            });

            this.pendingListRequest.done((response) => {
                if (requestToken !== this.lastRequestToken || !response || !response.success || !response.data) {
                    return;
                }

                if (append) {
                    this.$grid.append(response.data.html || '');
                } else {
                    this.$grid.html(response.data.html || '');
                }

                this.state.shownCount = Number(response.data.shownCount || 0);
                this.state.hasMore = !!response.data.hasMore;
                this.syncFooter();
            }).always(() => {
                if (requestToken !== this.lastRequestToken) {
                    return;
                }

                this.pendingListRequest = null;
                this.state.isLoadingList = false;
                this.syncLoadingState(false, append);
            });
        }

        syncLoadingState(isLoading) {
            this.$root.toggleClass('is-loading', !!isLoading);

            if (!this.$loadMore.length) {
                return;
            }

            this.$loadMore.prop('disabled', !!isLoading).attr('aria-busy', isLoading ? 'true' : 'false');
            this.$loadMore.text(isLoading ? this.getString('loading', 'Loading…') : this.originalLoadMoreLabel);
        }

        syncFooter() {
            if (!this.$footer.length) {
                return;
            }

            this.$footer.toggle(this.state.hasMore);
        }

        openEditModal(reviewId) {
            if (!reviewId || !this.config.canEditOwnReview) {
                return;
            }

            this.ajaxClient.getEditReview(this.config, reviewId).done((response) => {
                if (!response || !response.success || !response.data || !this.modalController) {
                    return;
                }

                this.modalController.openEdit(this, response.data);
            });
        }

        buildSubmissionPayload(state) {
            const payload = {
                rating: state.rating,
                content: $.trim(state.content),
                privacy_ack: state.privacyAccepted ? '1' : '',
            };

            if (!this.config.isLoggedIn) {
                payload.first_name = $.trim(state.identity.first_name);
                payload.last_name = $.trim(state.identity.last_name);
                payload.email = $.trim(state.identity.email);
            }

            return payload;
        }

        buildUpdatePayload(state) {
            return {
                review_id: state.reviewId,
                rating: state.rating,
                content: $.trim(state.content),
                privacy_ack: state.privacyAccepted ? '1' : '',
            };
        }

        scrollIntoView() {
            if (this.element && this.element.scrollIntoView) {
                this.element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        }

        getString(key, fallback) {
            if (this.config && this.config.strings && this.config.strings[key]) {
                return this.config.strings[key];
            }

            return fallback;
        }
    }

    const BWReviews = window.BWReviews || {};
    BWReviews.registry = BWReviews.registry instanceof Map ? BWReviews.registry : new Map();
    BWReviews.ajaxClient = BWReviews.ajaxClient instanceof AjaxClient ? BWReviews.ajaxClient : new AjaxClient();
    BWReviews.modalController = BWReviews.modalController instanceof ModalController ? BWReviews.modalController : new ModalController();
    BWReviews.globalEventsBound = !!BWReviews.globalEventsBound;
    BWReviews.confirmationHandled = !!BWReviews.confirmationHandled;

    BWReviews.closeAllSortMenus = function (exceptInstanceId) {
        this.registry.forEach((controller, instanceId) => {
            if (!exceptInstanceId || instanceId !== exceptInstanceId) {
                controller.closeSortMenu();
            }
        });
    };

    BWReviews.bindGlobalEvents = function () {
        if (this.globalEventsBound) {
            return;
        }

        $(document).on('click.bwReviewsGlobal', (event) => {
            this.registry.forEach((controller) => {
                controller.handleDocumentClick(event.target);
            });
        });

        this.globalEventsBound = true;
    };

    BWReviews.handleConfirmationNotice = function () {
        if (this.confirmationHandled) {
            return;
        }

        const params = new window.URLSearchParams(window.location.search);
        const notice = params.get('bw_review_notice');
        const firstWidget = this.registry.size ? this.registry.values().next().value : null;
        const stringKey = notice && Object.prototype.hasOwnProperty.call(CONFIRMATION_MESSAGES, notice)
            ? CONFIRMATION_MESSAGES[notice]
            : '';

        if (notice) {
            params.delete('bw_review_notice');
            params.delete('bw_review_confirm');
            params.delete('bw_review_token');

            const search = params.toString();
            const nextUrl = window.location.pathname + (search ? '?' + search : '') + window.location.hash;
            window.history.replaceState({}, document.title, nextUrl);
        }

        if (!stringKey || !firstWidget || !this.modalController || !this.modalController.isAvailable()) {
            this.confirmationHandled = true;
            return;
        }

        const message = firstWidget.getString(stringKey, '');
        if (!message) {
            this.confirmationHandled = true;
            return;
        }

        firstWidget.scrollIntoView();
        window.setTimeout(() => {
            this.modalController.openConfirmation(firstWidget, message);
        }, 180);

        this.confirmationHandled = true;
    };

    BWReviews.boot = function (scope) {
        const $scope = scope && scope.jquery ? scope : $(scope || document);

        if (!this.modalController || !this.modalController.isAvailable()) {
            this.modalController = new ModalController();
        }

        this.bindGlobalEvents();

        $scope.find(SELECTORS.widget).addBack(SELECTORS.widget).each((index, element) => {
            const $element = $(element);
            const rawId = $element.attr('data-instance-id') || $element.data('instanceId') || '';
            const instanceId = String(rawId);

            if (!instanceId) {
                return;
            }

            const existing = this.registry.get(instanceId);
            if (existing) {
                if (existing.element === element) {
                    return;
                }

                existing.destroy();
                this.registry.delete(instanceId);
            }

            const controller = new WidgetController(element, this.ajaxClient, this.modalController);
            this.registry.set(instanceId, controller);
        });

        this.handleConfirmationNotice();
    };

    window.BWReviews = BWReviews;

    $(document).ready(() => {
        window.BWReviews.boot(document);
    });

    $(window).on('elementor/frontend/init', () => {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/bw-reviews.default', ($scope) => {
                window.BWReviews.boot($scope);
            });
        }
    });
}(window, document, window.jQuery));
