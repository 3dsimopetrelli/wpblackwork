jQuery(window).on('elementor/frontend/init', function () {
  elementorFrontend.hooks.addAction(
    'frontend/element_ready/bw_products_slide.default',
    function ($scope) {
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
  );
});
