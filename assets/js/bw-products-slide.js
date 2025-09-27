(function($){
  console.log("✅ Init BW Products Slider JS caricato");

  jQuery(window).on('elementor/frontend/init', function() {
    var editMode = Boolean(elementorFrontend.isEditMode());
    console.log("BW Products JS LOADED — editMode:", editMode);

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/bw_products_slide.default',
      initBwProductsSlider
    );
  });

  function initBwProductsSlider($scope) {
    console.log("👉 initBwProductsSlider called", $scope);

    var $carousel = $scope.find('.bw-products-slider');
    if ($carousel.length) {
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
