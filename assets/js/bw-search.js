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
            this.$categoryFilters = this.$overlay.find('.bw-category-filter');
            this.$selectedCategoryInput = this.$overlay.find('.bw-selected-category');

            this.isOpen = false;
            this.selectedCategory = null;

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
            this.$input.val('');
            this.clearCategoryFilter();
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

            // If clicking the already active button, deactivate it
            if ($clickedButton.hasClass('is-active')) {
                this.clearCategoryFilter();
            } else {
                // Remove active class from all buttons
                this.$categoryFilters.removeClass('is-active');

                // Add active class to clicked button
                $clickedButton.addClass('is-active');

                // Update hidden input
                this.selectedCategory = categorySlug;
                this.$selectedCategoryInput.val(categorySlug);
            }
        }

        clearCategoryFilter() {
            this.$categoryFilters.removeClass('is-active');
            this.selectedCategory = null;
            this.$selectedCategoryInput.val('');
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
