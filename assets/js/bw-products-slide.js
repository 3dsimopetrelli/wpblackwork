(function ($) {
  function parseBoolean(value) {
    if (value === undefined || value === null) {
      return false;
    }

    if (typeof value === 'string') {
      value = value.toLowerCase();
      return value === 'true' || value === '1' || value === 'yes';
    }

    return Boolean(value);
  }

  function initBwProductsSlider($scope) {
    var $sliders = $scope.find('.bw-products-slider');

    if (!$sliders.length) {
      return;
    }

    $sliders.each(function () {
      var $slider = $(this);

      if ($slider.hasClass('is-initialized')) {
        return;
      }

      $slider.addClass('is-initialized');

      var columns = parseInt($slider.data('columns'), 10);
      var gap = parseInt($slider.data('gap'), 10);

      if (isNaN(columns) || columns < 1) {
        columns = 1;
      }

      if ($slider.length) {
        if (!isNaN(gap)) {
          $slider[0].style.setProperty('--gap', gap + 'px');
        }

        $slider[0].style.setProperty('--columns', columns);
      }

      var autoplayAttr = $slider.data('autoplay');
      var autoplay = false;

      if (autoplayAttr !== undefined && autoplayAttr !== '' && autoplayAttr !== false) {
        var autoplayValue = parseInt(autoplayAttr, 10);
        autoplay = !isNaN(autoplayValue) && autoplayValue > 0 ? autoplayValue : false;
      }

      var fade = parseBoolean($slider.data('fade'));

      var flickityOptions = {
        cellAlign: 'left',
        contain: true,
        groupCells: columns > 1 ? columns : 1,
        autoPlay: autoplay,
        wrapAround: parseBoolean($slider.data('wrap')),
        fade: fade,
        prevNextButtons: parseBoolean($slider.data('arrows')),
        pageDots: parseBoolean($slider.data('dots'))
      };

      if (fade) {
        flickityOptions.groupCells = false;
      }

      $slider.flickity(flickityOptions);
    });
  }

  $(function () {
    initBwProductsSlider($(document));
  });

  jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });

  window.initBwProductsSlider = initBwProductsSlider;
})(jQuery);
