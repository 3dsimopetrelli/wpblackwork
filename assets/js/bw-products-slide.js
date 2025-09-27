(function($){
  function initBwProductsSlider($scope){
    var $slider = $scope.find('.bw-products-slider');
    if(!$slider.length) return;
    if($slider.data('bw-init')) return;
    $slider.data('bw-init', true);

    // init base
    $slider.flickity({
      cellAlign: 'left',
      contain: true,
      autoPlay: 2500,
      prevNextButtons: true,
      pageDots: true,
      wrapAround: true
    });

    // rinfreschi mirati (editor + frontend)
    var refresh = function(){ $slider.flickity('resize').flickity('reloadCells'); };
    $slider.imagesLoaded(refresh);
    setTimeout(refresh, 300);
    setTimeout(refresh, 1000);

    if (window.elementorFrontend && elementorFrontend.isEditMode()) {
      var t1 = setInterval(refresh, 1500);
      setTimeout(function(){ clearInterval(t1); }, 7000);
    }
  }

  $(window).on('elementor/frontend/init', function(){
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });
})(jQuery);
