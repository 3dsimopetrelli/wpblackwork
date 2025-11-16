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
                // Aggiungi un attributo data per identificare a quale widget appartiene
                const widgetId = 'bw-search-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                this.$overlay.attr('data-widget-id', widgetId);
                this.$element.attr('data-widget-id', widgetId);

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
            });
        }
    });

})(jQuery);
