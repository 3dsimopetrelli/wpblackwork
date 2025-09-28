jQuery(window).on('elementor/frontend/init', function () {
  elementorFrontend.hooks.addAction(
    'frontend/element_ready/bw-products-slide.default',
    function ($scope, $) {
      var $carousel = $scope.find('.bw-products-slider');
      if ($carousel.length && !$carousel.data('flickity')) {
        $carousel.flickity({
          cellAlign: 'left',
          contain: true,
          wrapAround: true,
          pageDots: true,
          prevNextButtons: true,
          autoPlay: 3000
        });
      }
    }
  );
});
