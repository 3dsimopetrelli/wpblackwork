(function ($) {
    'use strict';

    class BWSearchWidget {
        constructor(element) {
            this.$element = $(element);
            this.$button = this.$element.find('.bw-search-button');
            this.$overlay = this.$element.find('.bw-search-overlay');
            this.$closeButton = this.$overlay.find('.bw-search-overlay__close');
            this.$input = this.$overlay.find('.bw-search-overlay__input');
            this.$form = this.$overlay.find('.bw-search-overlay__form');

            this.$resultsContainer = this.$overlay.find('.bw-search-results');
            this.$resultsGrid = this.$overlay.find('.bw-search-results__grid');
            this.$resultsMessage = this.$overlay.find('.bw-search-results__message');
            this.$resultsLoading = this.$overlay.find('.bw-search-results__loading');

            this.isOpen = false;
            this.searchTimeout = null;
            this.ajaxRequest = null;

            this.moveOverlayToBody();
            this.init();
        }

        moveOverlayToBody() {
            if (this.$overlay.length && this.$overlay.parent()[0].tagName !== 'BODY') {
                this.$overlay.appendTo('body');
            }
        }

        init() {
            this.$button.on('click', this.openOverlay.bind(this));
            this.$closeButton.on('click', this.closeOverlay.bind(this));
            this.$overlay.on('click', this.onOverlayClick.bind(this));
            this.$form.on('submit', this.onFormSubmit.bind(this));
            this.$input.on('input', this.onInputChange.bind(this));
            $(document).on('keydown', this.onKeyDown.bind(this));
        }

        openOverlay(e) {
            if (e) {
                e.preventDefault();
            }
            if (this.isOpen) {
                return;
            }

            this.isOpen = true;
            this.$overlay.addClass('is-active');
            $('body').addClass('bw-search-overlay-active');

            setTimeout(() => this.$input.trigger('focus'), 450);
        }

        closeOverlay(e) {
            if (e) {
                e.preventDefault();
            }
            if (!this.isOpen) {
                return;
            }

            this.isOpen = false;
            this.$overlay.removeClass('is-active');
            $('body').removeClass('bw-search-overlay-active');
            this.$input.val('').removeClass('has-content');
            this.hideResults();
        }

        onOverlayClick(e) {
            if ($(e.target).hasClass('bw-search-overlay')) {
                this.closeOverlay();
            }
        }

        onInputChange() {
            if (this.$input.val().length > 0) {
                this.$input.addClass('has-content');
            } else {
                this.$input.removeClass('has-content');
            }
            this.debounceLiveSearch();
        }

        onKeyDown(e) {
            if ((e.key === 'Escape' || e.keyCode === 27) && this.isOpen) {
                this.closeOverlay();
            }
        }

        onFormSubmit(e) {
            const searchValue = this.$input.val().trim();
            if (!searchValue) {
                e.preventDefault();
                return;
            }
            setTimeout(() => this.closeOverlay(), 100);
        }

        debounceLiveSearch() {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
            }

            const searchTerm = this.$input.val().trim();
            if (!searchTerm || searchTerm.length < 2) {
                this.hideResults();
                return;
            }

            this.showLoading();
            this.searchTimeout = setTimeout(() => {
                this.performLiveSearch(searchTerm);
            }, 300);
        }

        performLiveSearch(searchTerm) {
            if (typeof bwSearchAjax === 'undefined') {
                return;
            }

            this.ajaxRequest = $.ajax({
                url: bwSearchAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_live_search_products',
                    nonce: bwSearchAjax.nonce,
                    search_term: searchTerm,
                    categories: []
                },
                success: (response) => {
                    if (response && response.success && response.data) {
                        this.renderResults(response.data.products || [], response.data.message || '');
                    } else {
                        this.showMessage((bwSearchAjax.i18n && bwSearchAjax.i18n.searchError) || 'Error during search');
                    }
                },
                error: (xhr, status) => {
                    if (status !== 'abort') {
                        this.showMessage((bwSearchAjax.i18n && bwSearchAjax.i18n.connectionError) || 'Connection error');
                    }
                },
                complete: () => {
                    this.hideLoading();
                    this.ajaxRequest = null;
                }
            });
        }

        renderResults(products, message) {
            this.$resultsMessage.hide().text('');

            if (!products.length) {
                this.showMessage(message || (bwSearchAjax.i18n && bwSearchAjax.i18n.noProducts) || 'No products found');
                this.$resultsGrid.empty();
                this.$resultsContainer.addClass('is-visible');
                return;
            }

            this.$resultsGrid.empty();

            products.forEach((product, index) => {
                const $productCard = $(
                    '<div class="bw-product-card" style="animation-delay: ' + (index * 0.05) + 's;">' +
                        '<a href="' + product.permalink + '" class="bw-product-card__link">' +
                            '<div class="bw-product-card__image">' +
                                '<img src="' + product.image_url + '" alt="' + this.escapeHtml(product.title) + '" loading="lazy">' +
                            '</div>' +
                            '<div class="bw-product-card__info">' +
                                '<h3 class="bw-product-card__title">' + this.escapeHtml(product.title) + '</h3>' +
                                '<div class="bw-product-card__price">' + product.price_html + '</div>' +
                            '</div>' +
                        '</a>' +
                    '</div>'
                );

                this.$resultsGrid.append($productCard);
            });

            this.$resultsContainer.addClass('is-visible');
        }

        showLoading() {
            this.$resultsLoading.addClass('is-visible');
        }

        hideLoading() {
            this.$resultsLoading.removeClass('is-visible');
        }

        showMessage(message) {
            this.$resultsMessage.text(message).show();
            this.$resultsContainer.addClass('is-visible');
        }

        hideResults() {
            this.$resultsContainer.removeClass('is-visible');
            this.$resultsGrid.empty();
            this.$resultsMessage.hide().text('');
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return String(text).replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        }
    }

    function initBWSearchWidgets() {
        $('.bw-header-search').each(function () {
            if ($(this).data('bw-search-initialized')) {
                return;
            }
            new BWSearchWidget(this);
            $(this).data('bw-search-initialized', true);
        });
    }

    $(document).ready(function () {
        initBWSearchWidgets();
    });
})(jQuery);
