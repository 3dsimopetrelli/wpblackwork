(function ($) {
    function initBwProductsSlider($scope) {
        console.log('>>> initBwProductsSlider called', $scope);
        var $slider = $scope.find('.bw-products-slider');
        console.log('>>> slider trovato:', $slider.length);

        if (!$slider.length) return;

        // Evita doppia init
        if ($slider.hasClass('is-initialized')) return;
        $slider.addClass('is-initialized');

        // Inizializza Flickity con i data-*
        var autoplay = $slider.data('autoplay') ? parseInt($slider.data('autoplay'), 10) : false;
        var arrows = $slider.data('arrows') === true || $slider.data('arrows') === 'true';
        var dots = $slider.data('dots') === true || $slider.data('dots') === 'true';
        var wrap = $slider.data('wrap') === true || $slider.data('wrap') === 'true';
        var fade = $slider.data('fade') === true || $slider.data('fade') === 'true';
        var gap = $slider.data('gap');
        var columns = $slider.data('columns');

        console.log('>>> settings letti:', {
            autoplay: autoplay,
            arrows: arrows,
            dots: dots,
            wrap: wrap,
            fade: fade,
            gap: gap,
            columns: columns
        });

        $slider.flickity({
            cellAlign: 'left',
            contain: true,
            autoPlay: autoplay,
            prevNextButtons: arrows,
            pageDots: dots,
            wrapAround: wrap,
            fade: fade
        });
        console.log('>>> Flickity inizializzato su', $slider);
    }

    // Hook Elementor: frontend + editor
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw_products_slide.default',
            initBwProductsSlider
        );
    });
})(jQuery);
