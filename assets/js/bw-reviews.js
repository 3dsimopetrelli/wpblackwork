(function ($) {
    'use strict';

    const instances = new Map();

    class BWReviewsWidget {
        constructor(element) {
            this.$root = $(element);
            this.instanceId = String(this.$root.data('instance-id') || '');
            this.config = this.parseConfig();
            this.state = {
                sort: this.config.sortDefault || 'featured',
                shownCount: Number(this.config.shownCount || 0),
                hasMore: !!this.config.hasMore,
                modalMode: 'create',
                currentStep: 'rating',
                rating: 0,
                content: '',
                first_name: '',
                last_name: '',
                email: '',
                reviewId: 0,
                dirty: false,
                busy: false
            };

            this.$grid = this.$root.find('[data-review-grid]');
            this.$loadMore = this.$root.find('[data-review-load-more]');
            this.$sortTrigger = this.$root.find('.bw-reviews-sort__trigger');
            this.$sortMenu = this.$root.find('.bw-reviews-sort__menu');
            this.$summaryToggle = this.$root.find('.bw-reviews-summary__toggle');
            this.$breakdown = this.$root.find('.bw-reviews-breakdown');
            this.$modal = this.$root.find('[data-review-modal]');
            this.originalLoadMoreLabel = this.$loadMore.text();

            this.moveModalToBody();
            this.bind();
            this.seedReadonlyIdentity();
        }

        getString(key, fallback) {
            if (this.config && this.config.strings && this.config.strings[key]) {
                return this.config.strings[key];
            }

            return fallback;
        }

        getResponseMessage(response, fallback) {
            if (response && response.data && response.data.message) {
                return response.data.message;
            }

            return fallback;
        }

        parseConfig() {
            const raw = this.$root.find('.bw-reviews-config').first().text();

            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (error) {
                return {};
            }
        }

        moveModalToBody() {
            if (!this.$modal.length) {
                return;
            }

            this.$modal.attr('data-review-modal-instance', this.instanceId);
            $('body').append(this.$modal);
        }

        bind() {
            this.$root.on('click', '.bw-reviews-summary__toggle', () => this.toggleBreakdown());
            this.$root.on('click', '.bw-reviews-sort__trigger', (event) => {
                event.preventDefault();
                this.toggleSortMenu();
            });
            this.$root.on('click', '.bw-reviews-sort__option', (event) => {
                event.preventDefault();
                const sort = $(event.currentTarget).data('sortValue');
                this.applySort(String(sort || 'featured'));
            });
            this.$root.on('click', '[data-review-load-more]', (event) => {
                event.preventDefault();
                this.loadReviews(true);
            });
            this.$root.on('click', '[data-review-open="create"]', (event) => {
                event.preventDefault();
                this.openCreateModal();
            });
            this.$root.on('click', '[data-review-edit]', (event) => {
                event.preventDefault();
                const reviewId = parseInt($(event.currentTarget).data('reviewEdit'), 10) || 0;
                if (reviewId > 0) {
                    this.openEditModal(reviewId);
                }
            });

            this.$modal.on('click', '[data-review-close]', (event) => {
                event.preventDefault();
                this.closeModal();
            });
            this.$modal.on('click', '[data-review-next]', (event) => {
                event.preventDefault();
                this.advanceStep();
            });
            this.$modal.on('click', '[data-review-back]', (event) => {
                event.preventDefault();
                this.goBack();
            });
            this.$modal.on('click', '[data-review-submit]', (event) => {
                event.preventDefault();
                this.submit();
            });
            this.$modal.on('click', '[data-review-finish]', (event) => {
                event.preventDefault();
                this.closeModal(true);
            });
            this.$modal.on('click', '.bw-reviews-rating-picker__star', (event) => {
                event.preventDefault();
                const rating = parseInt($(event.currentTarget).data('ratingValue'), 10) || 0;
                this.state.rating = rating;
                this.state.dirty = true;
                this.syncRatingUi();
                this.clearModalMessage();
            });
            this.$modal.on('input change', '[data-review-field]', (event) => {
                const $field = $(event.currentTarget);
                const field = String($field.data('reviewField') || '');
                this.state[field] = String($field.val() || '');
                this.state.dirty = true;
                this.clearModalMessage();
            });

            $(document).on(`click.bwReviews-${this.instanceId}`, (event) => {
                if (!$(event.target).closest(this.$root.find('.bw-reviews-sort')[0]).length) {
                    this.closeSortMenu();
                }
            });

            $(document).on(`keydown.bwReviews-${this.instanceId}`, (event) => {
                if ('Escape' === event.key && this.$modal.is(':visible')) {
                    this.closeModal();
                }
            });
        }

        seedReadonlyIdentity() {
            if (!this.config.isLoggedIn) {
                return;
            }

            this.$modal.find('[data-review-user-name]').text(this.config.identity && this.config.identity.displayName ? this.config.identity.displayName : '');
            this.$modal.find('[data-review-user-email]').text(this.config.identity && this.config.identity.email ? this.config.identity.email : '');
        }

        toggleBreakdown() {
            if (!this.$breakdown.length || this.$breakdown.hasClass('is-static')) {
                return;
            }

            const expanded = 'true' === String(this.$summaryToggle.attr('aria-expanded'));
            this.$summaryToggle.attr('aria-expanded', expanded ? 'false' : 'true');
            this.$breakdown.stop(true, true)[expanded ? 'slideUp' : 'slideDown'](180);
        }

        toggleSortMenu() {
            if (this.$sortTrigger.is(':disabled')) {
                return;
            }

            const expanded = 'true' === String(this.$sortTrigger.attr('aria-expanded'));
            this.$sortTrigger.attr('aria-expanded', expanded ? 'false' : 'true');
            this.$sortMenu.prop('hidden', expanded);
        }

        closeSortMenu() {
            this.$sortTrigger.attr('aria-expanded', 'false');
            this.$sortMenu.prop('hidden', true);
        }

        applySort(sort) {
            if (!sort || sort === this.state.sort) {
                this.closeSortMenu();
                return;
            }

            this.state.sort = sort;
            this.state.shownCount = 0;
            this.setActiveSortOption();
            this.closeSortMenu();
            this.loadReviews(false);
        }

        setActiveSortOption() {
            const $options = this.$root.find('.bw-reviews-sort__option');
            $options.removeClass('is-active');
            $options.filter(`[data-sort-value="${this.state.sort}"]`).addClass('is-active');

            const activeLabel = $options.filter(`[data-sort-value="${this.state.sort}"]`).text() || 'Featured';
            this.$root.find('.bw-reviews-sort__label').text(activeLabel);
        }

        loadReviews(append) {
            if (this.state.busy) {
                return;
            }

            this.state.busy = true;
            if (this.$loadMore.length) {
                this.$loadMore.attr('aria-busy', 'true').prop('disabled', true).text(this.getString('loading', 'Loading…'));
            }

            $.post(this.config.ajaxUrl, {
                action: 'bw_reviews_load_reviews',
                nonce: this.config.nonce,
                product_id: this.config.productId,
                sort: this.state.sort,
                offset: append ? this.state.shownCount : 0,
                limit: append ? this.config.loadMoreCount : this.config.initialVisibleCount
            }).done((response) => {
                if (!response || !response.success || !response.data) {
                    return;
                }

                if (append) {
                    this.$grid.append(response.data.html || '');
                } else {
                    this.$grid.html(response.data.html || '');
                }

                this.state.shownCount = Number(response.data.shownCount || 0);
                this.state.hasMore = !!response.data.hasMore;
                this.toggleLoadMore();
            }).always(() => {
                this.state.busy = false;
                if (this.$loadMore.length) {
                    this.$loadMore.attr('aria-busy', 'false').prop('disabled', false).text(this.originalLoadMoreLabel);
                }
            });
        }

        toggleLoadMore() {
            if (!this.$loadMore.length) {
                return;
            }

            this.$loadMore.closest('.bw-reviews__footer').toggle(this.state.hasMore);
        }

        openCreateModal() {
            this.resetModalState();
            this.state.modalMode = 'create';
            this.state.reviewId = 0;
            this.$modal.find('[data-review-modal-title]').text(this.getString('writeReview', 'Write a review'));
            this.openModal();
        }

        openEditModal(reviewId) {
            this.resetModalState();
            this.state.modalMode = 'edit';
            this.state.reviewId = reviewId;
            this.$modal.find('[data-review-modal-title]').text(this.getString('editReview', 'Edit your review'));
            this.openModal();
            this.showModalMessage(this.getString('loading', 'Loading…'), 'info');

            $.post(this.config.ajaxUrl, {
                action: 'bw_reviews_get_edit_review',
                nonce: this.config.nonce,
                review_id: reviewId
            }).done((response) => {
                if (!response || !response.success || !response.data) {
                    this.showModalMessage(this.getResponseMessage(response, this.getString('validation', 'Unable to load review.')), 'error');
                    return;
                }

                this.state.rating = Number(response.data.prefill && response.data.prefill.rating ? response.data.prefill.rating : 0);
                this.state.content = String(response.data.prefill && response.data.prefill.content ? response.data.prefill.content : '');
                this.syncRatingUi();
                this.$modal.find('[data-review-field="content"]').val(this.state.content);
                this.clearModalMessage();
                this.state.dirty = false;
            }).fail((xhr) => {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : this.getString('validation', 'Unable to load review.');
                this.showModalMessage(message, 'error');
            });
        }

        openModal() {
            this.$modal.prop('hidden', false).addClass('is-open');
            $('body').addClass('bw-reviews-modal-open');
            this.syncModalUi();
        }

        closeModal(force) {
            if (!force && this.state.currentStep !== 'done' && this.state.dirty) {
                if (!window.confirm(this.getString('closeConfirm', 'Close this review form? Your progress will be lost.'))) {
                    return;
                }
            }

            this.$modal.removeClass('is-open').prop('hidden', true);
            $('body').removeClass('bw-reviews-modal-open');
            this.clearModalMessage();
        }

        resetModalState() {
            this.state.currentStep = 'rating';
            this.state.rating = 0;
            this.state.content = '';
            this.state.first_name = '';
            this.state.last_name = '';
            this.state.email = '';
            this.state.dirty = false;

            this.$modal.find('[data-review-field]').val('');
            this.$modal.find('[data-review-done-message]').text('');
            this.$modal.find('[data-review-identity-guest]').prop('hidden', !!this.config.isLoggedIn);
            this.$modal.find('[data-review-identity-user]').prop('hidden', !this.config.isLoggedIn);
            this.syncRatingUi();
            this.clearModalMessage();
            this.syncModalUi();
        }

        syncModalUi() {
            const steps = ['rating', 'content', 'identity', 'done'];
            const index = Math.max(0, steps.indexOf(this.state.currentStep));

            this.$modal.find('.bw-reviews-step').each((_, element) => {
                const $step = $(element);
                const active = String($step.data('step')) === this.state.currentStep;
                $step.toggleClass('is-active', active).prop('hidden', !active);
            });

            this.$modal.find('[data-review-progress]').text(`${this.getString('step', 'Step')} ${index + 1} ${this.getString('of', 'of')} 4`);
            this.$modal.find('[data-review-back]').prop('hidden', index < 1 || 'done' === this.state.currentStep);
            this.$modal.find('[data-review-next]').prop('hidden', index >= 2 || 'done' === this.state.currentStep);
            this.$modal.find('[data-review-submit]').prop('hidden', index !== 2);
            this.$modal.find('[data-review-finish]').prop('hidden', 'done' !== this.state.currentStep);

            const submitLabel = 'edit' === this.state.modalMode
                ? this.getString('saveChanges', 'Save changes')
                : this.getString('submit', 'Submit review');

            this.$modal.find('[data-review-submit]').text(submitLabel);
        }

        syncRatingUi() {
            this.$modal.find('.bw-reviews-rating-picker__star').each((_, element) => {
                const $star = $(element);
                const value = parseInt($star.data('ratingValue'), 10) || 0;
                $star.toggleClass('is-active', value <= this.state.rating);
            });
        }

        advanceStep() {
            if ('rating' === this.state.currentStep) {
                if (!this.state.rating) {
                    this.showModalMessage(this.getString('ratingRequired', 'Please select a rating.'), 'error');
                    return;
                }

                this.state.currentStep = 'content';
            } else if ('content' === this.state.currentStep) {
                this.state.content = String(this.$modal.find('[data-review-field="content"]').val() || '').trim();
                if (!this.state.content) {
                    this.showModalMessage(this.getString('contentRequired', 'Please share your review before continuing.'), 'error');
                    return;
                }

                this.state.currentStep = 'identity';
            }

            this.clearModalMessage();
            this.syncModalUi();
        }

        goBack() {
            if ('content' === this.state.currentStep) {
                this.state.currentStep = 'rating';
            } else if ('identity' === this.state.currentStep) {
                this.state.currentStep = 'content';
            }

            this.clearModalMessage();
            this.syncModalUi();
        }

        submit() {
            if ('identity' !== this.state.currentStep || this.state.busy) {
                return;
            }

            const payload = {
                action: 'edit' === this.state.modalMode ? 'bw_reviews_update_review' : 'bw_reviews_submit',
                nonce: this.config.nonce,
                product_id: this.config.productId,
                rating: this.state.rating,
                content: String(this.$modal.find('[data-review-field="content"]').val() || '').trim()
            };

            if ('edit' === this.state.modalMode) {
                payload.review_id = this.state.reviewId;
            } else if (this.config.isLoggedIn) {
                payload.first_name = '';
                payload.last_name = '';
                payload.email = '';
            } else {
                payload.first_name = String(this.$modal.find('[data-review-field="first_name"]').val() || '').trim();
                payload.last_name = String(this.$modal.find('[data-review-field="last_name"]').val() || '').trim();
                payload.email = String(this.$modal.find('[data-review-field="email"]').val() || '').trim();

                if (!payload.first_name) {
                    this.showModalMessage(this.getString('firstNameRequired', 'First name is required.'), 'error');
                    return;
                }

                if (!payload.last_name) {
                    this.showModalMessage(this.getString('lastNameRequired', 'Last name is required.'), 'error');
                    return;
                }

                if (!payload.email || !/.+@.+\..+/.test(payload.email)) {
                    this.showModalMessage(this.getString('emailRequired', 'A valid email address is required.'), 'error');
                    return;
                }
            }

            this.state.busy = true;
            this.$modal.find('[data-review-submit]').prop('disabled', true).attr('aria-busy', 'true');

            $.post(this.config.ajaxUrl, payload).done((response) => {
                if (!response || !response.success || !response.data) {
                    this.showModalMessage(this.getResponseMessage(response, this.getString('validation', 'Please complete the required fields before continuing.')), 'error');
                    return;
                }

                const data = response.data;
                this.state.currentStep = 'done';
                this.state.dirty = false;
                this.$modal.find('[data-review-done-message]').text(data.message || '');
                this.clearModalMessage();
                this.syncModalUi();
            }).fail((xhr) => {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : this.getString('validation', 'Please complete the required fields before continuing.');
                this.showModalMessage(message, 'error');
            }).always(() => {
                this.state.busy = false;
                this.$modal.find('[data-review-submit]').prop('disabled', false).attr('aria-busy', 'false');
            });
        }

        showModalMessage(message, type) {
            const $message = this.$modal.find('[data-review-message]');
            $message.text(message || '').attr('class', `bw-reviews-modal__message is-${type || 'info'}`).prop('hidden', !message);
        }

        clearModalMessage() {
            const $message = this.$modal.find('[data-review-message]');
            $message.text('').attr('class', 'bw-reviews-modal__message').prop('hidden', true);
        }
    }

    function initReviews(context) {
        $('.bw-reviews', context || document).each(function () {
            const instanceId = String($(this).data('instance-id') || '');
            if (!instanceId || instances.has(instanceId)) {
                return;
            }

            instances.set(instanceId, new BWReviewsWidget(this));
        });
    }

    $(document).ready(function () {
        initReviews(document);
    });

    $(window).on('elementor/frontend/init', function () {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }

        window.elementorFrontend.hooks.addAction('frontend/element_ready/bw-reviews.default', function ($scope) {
            initReviews($scope);
        });
    });
})(jQuery);
