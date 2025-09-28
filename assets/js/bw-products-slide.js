jQuery(window).on('elementor/frontend/init', function () {
  elementorFrontend.hooks.addAction(
    'frontend/element_ready/bw-products-slide.default',
    function ($scope, $) {
      var $carousel = $scope.find('.bw-products-slider');
      var carouselElement = $carousel.length ? $carousel.get(0) : null;
      if (
        carouselElement &&
        !$carousel.data('flickity') &&
        !(carouselElement.flickity && carouselElement.flickity.isInitActivated)
      ) {
        var flkty = new Flickity(carouselElement, {
          cellAlign: 'left',
          contain: true,
          imagesLoaded: true,
          wrapAround: true,
          pageDots: true,
          prevNextButtons: true,
          autoPlay: 3000
        });
        if (elementorFrontend.isEditMode()) {
          flkty.on('ready', function () {
            flkty.reloadCells();
            flkty.resize();
            window.dispatchEvent(new Event('resize'));
          });
        }
        $carousel.data('flickity', flkty);
        flkty.resize();
        window.dispatchEvent(new Event('resize'));
      }
    }
  );
});
