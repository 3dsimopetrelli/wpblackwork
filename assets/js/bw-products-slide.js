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

    function refreshSlider() {
      $slider.flickity('resize').flickity('reloadCells');
    }

    function scheduleRefreshes() {
      refreshSlider();
      setTimeout(refreshSlider, 300);
      setTimeout(refreshSlider, 1000);
      setTimeout(refreshSlider, 2000);
    }

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

    scheduleRefreshes();

    // quando Flickity è pronto, ricalcola
    $slider.on('ready.flickity', function () {
      refreshSlider();
    });

    // dopo immagini caricate (richiede imagesLoaded che è nel pkgd)
    $slider.imagesLoaded(function () {
      scheduleRefreshes();
    });

    // su window load e resize
    $(window).on('load resize', function () {
      refreshSlider();
    });

    // Fix per editor: rinfresca quando Elementor aggiorna pannelli / DOM
    if (window.elementorFrontend && elementorFrontend.isEditMode()) {
      // quando il widget viene (ri)renderizzato
      setTimeout(refreshSlider, 300);
      // quando si cambia pannello o controllo
      elementor.channels.editor && elementor.channels.editor.on('change', function(){
        refreshSlider();
      });
      // osserva cambi dimensioni del contenitore
      var ro = new ResizeObserver(function(){ refreshSlider(); });
      ro.observe($slider.get(0));

      var editRefreshDuration = 10000;
      var editRefreshInterval = 2000;
      var elapsed = 0;
      var intervalId = setInterval(function () {
        elapsed += editRefreshInterval;
        refreshSlider();
        if (elapsed >= editRefreshDuration) {
          clearInterval(intervalId);
        }
      }, editRefreshInterval);
    }
  }

  // hook Elementor (frontend + editor)
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });
})(jQuery);
