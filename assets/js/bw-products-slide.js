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

    var applyLayoutSettings = function () {
      var columns = parseInt($carousel.data('columns'), 10);
      if (isNaN(columns) || columns < 1) {
        columns = 1;
      }

      var gap = parseInt($carousel.data('gap'), 10);
      if (isNaN(gap) || gap < 0) {
        gap = 0;
      }

      var imageHeight = parseInt($carousel.data('imageHeight'), 10);
      if (isNaN(imageHeight) || imageHeight < 0) {
        imageHeight = 0;
      }

      var widthExpression = 'calc(100% / ' + columns + ' - ' + gap + 'px)';
      var halfGap = Math.max(gap / 2, 0);
      var marginValue = Math.round(halfGap * 100) / 100;
      var marginExpression = marginValue + 'px';

      $carousel.find('.bw-products-slide-item').each(function () {
        var $item = $(this);

        $item.css({
          width: widthExpression,
          marginLeft: marginExpression,
          marginRight: marginExpression,
          flex: '0 0 auto',
        });

        var $images = $item.find('img');
        if (imageHeight > 0) {
          $images.css({
            maxHeight: imageHeight + 'px',
            objectFit: 'cover',
          });
        } else {
          $images.css({
            maxHeight: '',
            objectFit: '',
          });
        }
      });
    };

    applyLayoutSettings();

    var existingInstance = Flickity.data(carouselElement);
    if (existingInstance) {
      applyLayoutSettings();
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
    applyLayoutSettings();
    flkty.reloadCells();
    flkty.resize();
    window.dispatchEvent(new Event('resize'));

    var handleResize = function () {
      applyLayoutSettings();
      flkty.resize();
    };

    var triggerFullRefresh = function () {
      applyLayoutSettings();
      flkty.reloadCells();
      flkty.resize();
      window.dispatchEvent(new Event('resize'));
    };

    flkty.on('ready', triggerFullRefresh);

    if (elementorFrontend.isEditMode()) {

      if (!$carousel.data('bwFlickityEditorEventsBound')) {
        var editorChannel = elementor && elementor.channels && elementor.channels.editor;
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
