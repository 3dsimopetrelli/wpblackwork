(function ($) {
    function initBwProductsSlider($scope) {
        var $slider = $scope.find('.bw-products-slider');

        if (!$slider.length) return;

        // Evita doppia init
        if ($slider.hasClass('is-initialized')) return;
        $slider.addClass('is-initialized');

        // Inizializza Flickity con i data-*
        $slider.flickity({
            cellAlign: 'left',
            contain: true,
            autoPlay: $slider.data('autoplay') ? parseInt($slider.data('autoplay'), 10) : false,
            prevNextButtons: $slider.data('arrows') === true || $slider.data('arrows') === 'true',
            pageDots: $slider.data('dots') === true || $slider.data('dots') === 'true',
            wrapAround: $slider.data('wrap') === true || $slider.data('wrap') === 'true',
            fade: $slider.data('fade') === true || $slider.data('fade') === 'true'
        });
    }

    // Hook Elementor: frontend + editor
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw_products_slide.default',
            initBwProductsSlider
        );
    });
})(jQuery);
