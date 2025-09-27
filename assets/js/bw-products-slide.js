(function($){
  function waitForImages($wrap){
    return new Promise(function(resolve){
      var $imgs = $wrap.find('img'); if(!$imgs.length) return resolve();
      var pending = $imgs.length;
      $imgs.each(function(){
        if (this.complete) { if(--pending===0) resolve(); }
        else $(this).one('load error', function(){ if(--pending===0) resolve(); });
      });
    });
  }

  function refresh($s){ $s.flickity('resize').flickity('reloadCells'); }

  function initBwProductsSlider($scope){
    var $s = $scope.find('.bw-products-slider'); if(!$s.length) return;
    if($s.data('bw-init')) { refresh($s); return; }
    $s.data('bw-init', true);

    // inizializza dopo primo paint
    requestAnimationFrame(function(){
      $s.flickity({
        cellAlign: 'left',
        contain: true,
        autoPlay: 2500,
        prevNextButtons: true,
        pageDots: true,
        wrapAround: true,
        draggable: '>1',
        watchCSS: false
      });

      // rinfreschi scaglionati
      refresh($s);
      setTimeout(function(){ refresh($s); }, 300);
      setTimeout(function(){ refresh($s); }, 1000);

      // dopo caricamento immagini (senza imagesLoaded)
      waitForImages($s).then(function(){ refresh($s); });

      // editor: osserva ridimensionamenti del widget/iframe
      if (window.elementorFrontend && elementorFrontend.isEditMode() && 'ResizeObserver' in window) {
        var ro = new ResizeObserver(function(){ refresh($s); });
        ro.observe($s.get(0));
        // ping iniziali per stabilizzare
        var i = setInterval(function(){ refresh($s); }, 1500);
        setTimeout(function(){ clearInterval(i); }, 7000);
      }
    });
  }

  $(window).on('elementor/frontend/init', function(){
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });
})(jQuery);
