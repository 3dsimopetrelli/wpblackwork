(function($){
  function initBwProductsSlider($scope){
    var $slider = $scope.find('.bw-products-slider');
    if(!$slider.length) return;
    if($slider.hasClass('f-initialized')) return;
    $slider.addClass('f-initialized');

    $slider.flickity({
      cellAlign: 'left',
      contain: true,
      autoPlay: 2000,
      prevNextButtons: true,
      pageDots: true,
      wrapAround: true
    });
  }

  $(window).on('elementor/frontend/init', function(){
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });
})(jQuery);
