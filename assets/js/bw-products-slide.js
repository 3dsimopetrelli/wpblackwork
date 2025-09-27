(function ($) {
    function initBwProductsSlider($scope) {
        var $slider = $scope.find('.bw-products-slider');
        if (!$slider.length) return;

        // Evita doppia init
        if ($slider.hasClass('is-initialized')) return;
        $slider.addClass('is-initialized');

        // Leggi data-*
        var autoplay = $slider.data('autoplay') ? parseInt($slider.data('autoplay'), 10) : false;
        var arrows   = ($slider.data('arrows') === true || $slider.data('arrows') === 'true');
        var dots     = ($slider.data('dots') === true || $slider.data('dots') === 'true');
        var wrap     = ($slider.data('wrap') === true || $slider.data('wrap') === 'true');
        var fade     = ($slider.data('fade') === true || $slider.data('fade') === 'true');
        var gap      = $slider.data('gap') ? parseInt($slider.data('gap'), 10) : 0;
        var columns  = $slider.data('columns') ? parseInt($slider.data('columns'), 10) : 3;

        // Colonne + gap via CSS inline (leggero)
        $slider.find('.carousel-cell').css({
            'width': (100 / columns) + '%',
            'margin-right': gap + 'px'
        });

        // Init Flickity una sola volta
        $slider.flickity({
            cellAlign: 'left',
            contain: true,
            autoPlay: autoplay,
            prevNextButtons: arrows,
            pageDots: dots,
            wrapAround: wrap,
            fade: fade
        });
    }

    // Hook Elementor: frontend + editor (solo quando il widget è pronto)
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
    });
})(jQuery);
