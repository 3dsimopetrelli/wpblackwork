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

            this.isOpen = false;

            this.init();
        }

        init() {
            // Bind events
            this.$button.on('click', this.openOverlay.bind(this));
            this.$closeButton.on('click', this.closeOverlay.bind(this));
            this.$overlay.on('click', this.onOverlayClick.bind(this));
            this.$form.on('submit', this.onFormSubmit.bind(this));

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

            // Clear input
            this.$input.val('');
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
            });
        }
    });

})(jQuery);
