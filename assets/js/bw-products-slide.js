(function ($) {
  var initProductsSlider = function ($scope) {
    var $carousel = $scope.find('.bw-products-slider');
    if (!$carousel.length) {
      return;
    }

    var carouselElement = $carousel.get(0);
    if (!carouselElement) {
      return;
    }

    var existingInstance = Flickity.data(carouselElement);
    if (existingInstance) {
      existingInstance.reloadCells();
      existingInstance.resize();
      window.dispatchEvent(new Event('resize'));
      return;
    }

    var parseBool = function (value, defaultValue) {
      if (typeof value === 'undefined') {
        return defaultValue;
      }
      if (typeof value === 'string') {
        value = value.toLowerCase();
        if (value === 'false' || value === '0' || value === 'no') {
          return false;
        }
        if (value === 'true' || value === '1' || value === 'yes') {
          return true;
        }
      }
      return Boolean(value);
    };

    var autoplaySetting = parseInt($carousel.data('autoplay'), 10);
    var autoplayOption = 3000;
    if (!isNaN(autoplaySetting)) {
      autoplayOption = autoplaySetting > 0 ? autoplaySetting : false;
    }

    var options = {
      imagesLoaded: true,
      cellSelector: '.bw-products-slide-item',
      cellAlign: 'left',
      contain: true,
      wrapAround: parseBool($carousel.data('wrapAround'), true),
      pageDots: parseBool($carousel.data('pageDots'), true),
      prevNextButtons: parseBool($carousel.data('prevNextButtons'), true),
      autoPlay: autoplayOption,
    };

    if ($carousel.data('fade') === 'yes') {
      options.fade = true;
    }

    var flkty = new Flickity(carouselElement, options);
    flkty.reloadCells();
    flkty.resize();
    window.dispatchEvent(new Event('resize'));

    var handleResize = function () {
      flkty.resize();
    };

    if (elementorFrontend.isEditMode()) {
      var triggerFullRefresh = function () {
        flkty.reloadCells();
        flkty.resize();
        window.dispatchEvent(new Event('resize'));
      };

      flkty.on('ready', triggerFullRefresh);

      if (!$carousel.data('bwFlickityEditorEventsBound')) {
        var editorChannel = typeof elementor !== 'undefined' && elementor.channels
          ? elementor.channels.editor
          : null;
        if (editorChannel && editorChannel.on) {
          editorChannel.on('panel:open', handleResize);
          editorChannel.on('panel:close', handleResize);
        }

        elementorFrontend.hooks.addAction(
          'document/responsive/after_set_breakpoint',
          handleResize
        );

        $carousel.data('bwFlickityEditorEventsBound', true);
      }
    }
  };

  jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction(
      'frontend/element_ready/bw-products-slide.default',
      function ($scope) {
        initProductsSlider($scope);
      }
    );
  });
})(jQuery);
