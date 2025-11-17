(function ($) {
    'use strict';

    /**
     * BW Search Widget
     * Gestisce l'apertura/chiusura dell'overlay di ricerca con animazioni fluide
     */
    class BWSearchWidget {
        constructor(element) {
            this.$element = $(element);
            this.$button = this.$element.find('.bw-search-button');
            this.$overlay = this.$element.find('.bw-search-overlay');
            this.$closeButton = this.$overlay.find('.bw-search-overlay__close');
            this.$input = this.$overlay.find('.bw-search-overlay__input');
            this.$form = this.$overlay.find('.bw-search-overlay__form');
            this.$filtersContainer = this.$overlay.find('.bw-search-overlay__filters');
            this.$categoryFilters = this.$overlay.find('.bw-category-filter');
            this.$selectedCategoryInput = this.$overlay.find('.bw-selected-category');

            // Live search elements
            this.$resultsContainer = this.$overlay.find('.bw-search-results');
            this.$resultsGrid = this.$overlay.find('.bw-search-results__grid');
            this.$resultsMessage = this.$overlay.find('.bw-search-results__message');
            this.$resultsLoading = this.$overlay.find('.bw-search-results__loading');

            this.isOpen = false;
            this.selectedCategories = [];
            this.multiSelectEnabled = this.$filtersContainer.data('multi-select') === 'yes';
            this.searchTimeout = null;
            this.ajaxRequest = null;
            this.masonryInstance = null;

            // Move overlay to body to ensure it's not constrained by parent containers
            this.moveOverlayToBody();

            this.init();
        }

        /**
         * Sposta l'overlay nel body per evitare vincoli di posizionamento
         * dal contenitore padre (es. navigation con overflow hidden)
         */
        moveOverlayToBody() {
            if (this.$overlay.length && this.$overlay.parent()[0].tagName !== 'BODY') {
                // Preserva l'ID del widget Elementor se esiste già
                const existingWidgetId = this.$overlay.attr('data-widget-id');

                if (!existingWidgetId) {
                    // Solo se non esiste, genera un nuovo ID
                    const widgetId = 'bw-search-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    this.$overlay.attr('data-widget-id', widgetId);
                    this.$element.attr('data-widget-id', widgetId);
                }

                // Sposta l'overlay nel body
                this.$overlay.appendTo('body');
            }
        }

        init() {
            // Bind events
            this.$button.on('click', this.openOverlay.bind(this));
            this.$closeButton.on('click', this.closeOverlay.bind(this));
            this.$overlay.on('click', this.onOverlayClick.bind(this));
            this.$form.on('submit', this.onFormSubmit.bind(this));
            this.$categoryFilters.on('click', this.onCategoryFilterClick.bind(this));

            // Input event for placeholder fade
            this.$input.on('input', this.onInputChange.bind(this));

            // Keyboard events
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

            // Add class to overlay
            this.$overlay.addClass('is-active');

            // Prevent body scroll
            $('body').addClass('bw-search-overlay-active');

            // Focus input after animation
            setTimeout(() => {
                this.$input.focus();
            }, 500);
        }

        closeOverlay(e) {
            if (e) {
                e.preventDefault();
            }

            if (!this.isOpen) {
                return;
            }

            this.isOpen = false;

            // Remove class from overlay
            this.$overlay.removeClass('is-active');

            // Allow body scroll
            $('body').removeClass('bw-search-overlay-active');

            // Clear input and filters
            this.$input.val('').removeClass('has-content');
            this.clearCategoryFilter();

            // Hide search results
            this.hideResults();
        }

        onInputChange(e) {
            // Toggle 'has-content' class based on input value
            // This enables smooth fade-out of placeholder when typing
            if (this.$input.val().length > 0) {
                this.$input.addClass('has-content');
            } else {
                this.$input.removeClass('has-content');
            }

            // Trigger live search with debounce
            this.debounceLiveSearch();
        }

        onOverlayClick(e) {
            // Close when clicking on the overlay background (not the content)
            if ($(e.target).hasClass('bw-search-overlay')) {
                this.closeOverlay();
            }
        }

        onKeyDown(e) {
            // ESC key closes the overlay
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (this.isOpen) {
                    this.closeOverlay();
                }
            }
        }

        onFormSubmit(e) {
            const searchValue = this.$input.val().trim();

            // If empty, prevent submission
            if (!searchValue) {
                e.preventDefault();
                return;
            }

            // Form will submit normally to the action URL with the search query
            // After submission, close the overlay
            setTimeout(() => {
                this.closeOverlay();
            }, 100);
        }

        onCategoryFilterClick(e) {
            e.preventDefault();
            const $clickedButton = $(e.currentTarget);
            const categorySlug = $clickedButton.data('category-slug');

            if (this.multiSelectEnabled) {
                // Multi-selection mode
                if ($clickedButton.hasClass('is-active')) {
                    // Remove from selection
                    $clickedButton.removeClass('is-active');
                    const index = this.selectedCategories.indexOf(categorySlug);
                    if (index > -1) {
                        this.selectedCategories.splice(index, 1);
                    }
                } else {
                    // Add to selection
                    $clickedButton.addClass('is-active');
                    this.selectedCategories.push(categorySlug);
                }

                // Update hidden input with comma-separated values
                this.$selectedCategoryInput.val(this.selectedCategories.join(','));
            } else {
                // Single selection mode
                if ($clickedButton.hasClass('is-active')) {
                    // Deactivate if already active
                    this.clearCategoryFilter();
                } else {
                    // Remove active class from all buttons
                    this.$categoryFilters.removeClass('is-active');

                    // Add active class to clicked button
                    $clickedButton.addClass('is-active');

                    // Update selection
                    this.selectedCategories = [categorySlug];
                    this.$selectedCategoryInput.val(categorySlug);
                }
            }

            // Trigger live search when category filter changes
            this.debounceLiveSearch();
        }

        clearCategoryFilter() {
            this.$categoryFilters.removeClass('is-active');
            this.selectedCategories = [];
            this.$selectedCategoryInput.val('');
        }

        /**
         * Debounce per la ricerca live (attende 300ms dopo l'ultima digitazione)
         */
        debounceLiveSearch() {
            // Cancella il timeout precedente
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Cancella la richiesta AJAX precedente se ancora in corso
            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
            }

            const searchTerm = this.$input.val().trim();

            // Se il campo è vuoto, nascondi i risultati
            if (searchTerm.length === 0) {
                this.hideResults();
                return;
            }

            // Se il termine è troppo corto, mostra un messaggio
            if (searchTerm.length < 2) {
                this.hideResults();
                return;
            }

            // Mostra il loading
            this.showLoading();

            // Imposta un nuovo timeout
            this.searchTimeout = setTimeout(() => {
                this.performLiveSearch(searchTerm);
            }, 300);
        }

        /**
         * Esegue la ricerca AJAX
         */
        performLiveSearch(searchTerm) {
            // Prepara i dati per la richiesta
            const data = {
                action: 'bw_live_search_products',
                nonce: bwSearchAjax.nonce,
                search_term: searchTerm,
                categories: this.selectedCategories
            };

            // Esegui la richiesta AJAX
            this.ajaxRequest = $.ajax({
                url: bwSearchAjax.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.renderResults(response.data.products, response.data.message);
                    } else {
                        this.showMessage('Errore durante la ricerca');
                    }
                },
                error: (xhr, status, error) => {
                    if (status !== 'abort') {
                        this.showMessage('Errore di connessione');
                    }
                },
                complete: () => {
                    this.hideLoading();
                    this.ajaxRequest = null;
                }
            });
        }

        /**
         * Renderizza i risultati con layout masonry
         */
        renderResults(products, message) {
            // Nascondi messaggio
            this.$resultsMessage.hide().text('');

            // Se non ci sono prodotti, mostra messaggio
            if (products.length === 0) {
                this.showMessage(message || 'Nessun prodotto trovato');
                this.$resultsGrid.empty();
                this.$resultsContainer.addClass('is-visible');
                return;
            }

            // Distruggi istanza masonry precedente se esiste
            if (this.masonryInstance) {
                this.masonryInstance.destroy();
                this.masonryInstance = null;
            }

            // Svuota il grid
            this.$resultsGrid.empty();

            // Crea gli elementi HTML per i prodotti
            products.forEach((product, index) => {
                const $productCard = $(`
                    <div class="bw-product-card" style="animation-delay: ${index * 0.05}s;">
                        <a href="${product.permalink}" class="bw-product-card__link">
                            <div class="bw-product-card__image">
                                <img src="${product.image_url}" alt="${this.escapeHtml(product.title)}" loading="lazy">
                            </div>
                            <div class="bw-product-card__info">
                                <h3 class="bw-product-card__title">${this.escapeHtml(product.title)}</h3>
                                <div class="bw-product-card__price">${product.price_html}</div>
                            </div>
                        </a>
                    </div>
                `);

                this.$resultsGrid.append($productCard);
            });

            // Mostra il container dei risultati con fade-in
            this.$resultsContainer.addClass('is-visible');

            // Inizializza masonry dopo che le immagini sono caricate
            this.$resultsGrid.imagesLoaded(() => {
                this.masonryInstance = this.$resultsGrid.masonry({
                    itemSelector: '.bw-product-card',
                    columnWidth: '.bw-product-card',
                    percentPosition: true,
                    transitionDuration: '0.3s',
                    gutter: 20
                });
            });
        }

        /**
         * Mostra il loading spinner
         */
        showLoading() {
            this.$resultsLoading.addClass('is-visible');
        }

        /**
         * Nascondi il loading spinner
         */
        hideLoading() {
            this.$resultsLoading.removeClass('is-visible');
        }

        /**
         * Mostra un messaggio
         */
        showMessage(message) {
            this.$resultsMessage.text(message).show();
            this.$resultsContainer.addClass('is-visible');
        }

        /**
         * Nascondi i risultati
         */
        hideResults() {
            this.$resultsContainer.removeClass('is-visible');
            this.$resultsGrid.empty();
            this.$resultsMessage.hide().text('');

            // Distruggi istanza masonry se esiste
            if (this.masonryInstance) {
                this.masonryInstance.destroy();
                this.masonryInstance = null;
            }
        }

        /**
         * Escape HTML per prevenire XSS
         */
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, (m) => map[m]);
        }

        /**
         * Cleanup quando il widget viene distrutto
         */
        destroy() {
            // Remove overlay from body if it was moved there
            if (this.$overlay.length && this.$overlay.parent()[0].tagName === 'BODY') {
                this.$overlay.remove();
            }

            // Remove event listeners
            this.$button.off('click');
            this.$closeButton.off('click');
            this.$overlay.off('click');
            this.$form.off('submit');
            this.$categoryFilters.off('click');
            this.$input.off('input');
            $(document).off('keydown', this.onKeyDown);

            // Remove body class if active
            if (this.isOpen) {
                $('body').removeClass('bw-search-overlay-active');
            }
        }
    }

    /**
     * Initialize all BW Search widgets on the page
     */
    function initBWSearchWidgets() {
        $('.elementor-widget-bw-search').each(function () {
            if (!$(this).data('bw-search-initialized')) {
                new BWSearchWidget(this);
                $(this).data('bw-search-initialized', true);
            }
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        initBWSearchWidgets();
    });

    /**
     * Re-initialize after Elementor preview refresh
     */
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-search.default', function ($scope) {
                if (!$scope.data('bw-search-initialized')) {
                    new BWSearchWidget($scope[0]);
                    $scope.data('bw-search-initialized', true);
                }

                // Live update in Elementor editor
                if (typeof elementor !== 'undefined') {
                    setupLiveUpdate($scope);
                }
            });
        }
    });

    /**
     * Setup live update for Elementor editor
     */
    function setupLiveUpdate($scope) {
        const widgetId = $scope.data('id');
        if (!widgetId) return;

        const $overlay = $('body').find('.bw-search-overlay[data-widget-id="' + widgetId + '"]');
        if (!$overlay.length) {
            // Fallback to overlay inside scope
            $overlay = $scope.find('.bw-search-overlay');
        }

        // Helper to update element text
        const updateText = (selector, value) => {
            const $el = $overlay.find(selector);
            if ($el.length) {
                if ($el.is('input')) {
                    $el.attr('placeholder', value);
                } else {
                    $el.text(value);
                }
            }
        };

        // Helper to toggle element visibility
        const toggleElement = (selector, show) => {
            const $el = $overlay.find(selector);
            if ($el.length) {
                if (show) {
                    $el.show();
                } else {
                    $el.hide();
                }
            }
        };

        // Helper to update categories
        const updateCategories = (categoryIds) => {
            const $filters = $overlay.find('.bw-search-overlay__filters');

            if (!categoryIds || categoryIds.length === 0) {
                $filters.parent().hide();
                return;
            }

            // Show filters container
            $filters.parent().show();

            // Get all current category buttons
            const $currentButtons = $filters.find('.bw-category-filter');
            const currentIds = $currentButtons.map(function() {
                return $(this).data('category-id').toString();
            }).get();

            // Check if order has changed
            const idsChanged = JSON.stringify(categoryIds) !== JSON.stringify(currentIds);

            if (idsChanged) {
                // Reorder buttons according to new categoryIds
                const $newButtons = [];
                categoryIds.forEach(id => {
                    const $btn = $currentButtons.filter('[data-category-id="' + id + '"]');
                    if ($btn.length) {
                        $newButtons.push($btn[0]);
                    }
                });

                // Clear and re-append in new order
                $filters.empty();
                $newButtons.forEach(btn => {
                    $filters.append(btn);
                });
            }
        };

        // Listen to Elementor settings changes
        elementor.channels.editor.on('change', function(controlView) {
            const elementView = controlView.container.view;

            // Check if this is our widget
            if (elementView.model.id !== widgetId) {
                return;
            }

            const controlName = controlView.model.get('name');
            const value = elementView.model.getSetting(controlName);

            switch (controlName) {
                case 'popup_header_text':
                    updateText('.bw-search-overlay__title', value);
                    break;

                case 'popup_placeholder':
                    updateText('.bw-search-overlay__input', value);
                    break;

                case 'popup_hint_text':
                    updateText('.bw-search-overlay__hint', value);
                    break;

                case 'show_header_text':
                    toggleElement('.bw-search-overlay__title', value === 'yes');
                    break;

                case 'show_hint_text':
                    toggleElement('.bw-search-overlay__hint', value === 'yes');
                    break;

                case 'category_ids':
                    updateCategories(value);
                    break;

                case 'enable_category_filters':
                    const categoryIds = elementView.model.getSetting('category_ids');
                    if (value === 'yes' && categoryIds && categoryIds.length > 0) {
                        $overlay.find('.bw-search-overlay__filters').parent().show();
                    } else {
                        $overlay.find('.bw-search-overlay__filters').parent().hide();
                    }
                    break;
            }
        });
    }

})(jQuery);
