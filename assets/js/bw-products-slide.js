(function($) {
  jQuery(window).on('elementor/frontend/init', function() {
    console.log('✅ BW Products Slider JS inizializzato');

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/bw_products_slide.default',
      initBwProductsSlider
    );
  });

  function initBwProductsSlider($scope) {
    console.log('👉 initBwProductsSlider chiamato');

    var $carousel = $scope.find('.bw-products-slider');
    if ($carousel.length && !$carousel.data('flickity')) {
      $carousel.flickity({
        cellAlign: 'left',
        contain: true,
        pageDots: true,
        prevNextButtons: true,
        wrapAround: true,
        groupCells: true
      });
    }
  }
})(jQuery);
