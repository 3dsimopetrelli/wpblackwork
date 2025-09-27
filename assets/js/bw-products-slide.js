(function ($) {
  function boolData(v) { return v === true || v === 'true' || v === 1 || v === '1'; }

  function initBwProductsSlider($scope) {
    var $slider = $scope.find('.bw-products-slider');
    if (!$slider.length) return;

    // evita doppia init
    if ($slider.data('bw-init')) return;
    $slider.data('bw-init', true);

    // leggi data-*
    var autoplay = $slider.data('autoplay') ? parseInt($slider.data('autoplay'), 10) : false;
    var arrows   = boolData($slider.data('arrows'));
    var dots     = boolData($slider.data('dots'));
    var wrap     = boolData($slider.data('wrap'));
    var fade     = boolData($slider.data('fade'));
    var columns  = $slider.data('columns') ? parseInt($slider.data('columns'), 10) : 3;
    var gap      = $slider.data('gap') ? parseInt($slider.data('gap'), 10) : 20;

    // imposta larghezze cella per sicurezza (non fanno danno anche con Flickity)
    $slider.find('.carousel-cell').css({ width: (100 / columns) + '%' });

    // inizializza Flickity
    $slider.flickity({
      cellAlign: 'left',
      contain: true,
      autoPlay: autoplay || false,
      prevNextButtons: arrows,
      pageDots: dots,
      wrapAround: wrap,
      fade: fade
    });

    // quando Flickity è pronto, ricalcola
    $slider.on('ready.flickity', function () {
      $slider.flickity('resize').flickity('reloadCells');
    });

    // dopo immagini caricate (richiede imagesLoaded che è nel pkgd)
    $slider.imagesLoaded(function () {
      $slider.flickity('resize').flickity('reloadCells');
    });

    // su window load e resize
    $(window).on('load resize', function () {
      $slider.flickity('resize').flickity('reloadCells');
    });

    // Fix per editor: rinfresca quando Elementor aggiorna pannelli / DOM
    if (window.elementorFrontend && elementorFrontend.isEditMode()) {
      // quando il widget viene (ri)renderizzato
      setTimeout(function(){ $slider.flickity('resize').flickity('reloadCells'); }, 300);
      // quando si cambia pannello o controllo
      elementor.channels.editor && elementor.channels.editor.on('change', function(){
        $slider.flickity('resize').flickity('reloadCells');
      });
      // osserva cambi dimensioni del contenitore
      var ro = new ResizeObserver(function(){ $slider.flickity('resize').flickity('reloadCells'); });
      ro.observe($slider.get(0));
    }
  }

  // hook Elementor (frontend + editor)
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });
})(jQuery);
