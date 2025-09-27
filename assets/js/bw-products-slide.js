(function ($) {
  function parseBoolean(value) {
    return value === true || value === 'true' || value === '1' || value === 1 || value === 'yes';
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

      var columns = parseInt($slider.data('columns'), 10);
      var gap = parseInt($slider.data('gap'), 10);
      var autoplayData = $slider.data('autoplay');
      var arrows = parseBoolean($slider.data('arrows'));
      var dots = parseBoolean($slider.data('dots'));
      var wrap = parseBoolean($slider.data('wrap'));
      var fade = parseBoolean($slider.data('fade'));

      if (isNaN(columns) || columns < 1) {
        columns = 1;
      }

      if (!isNaN(gap)) {
        $slider[0].style.setProperty('--gap', gap + 'px');
      }

      $slider[0].style.setProperty('--columns', columns);

      var autoplay = false;

      if (autoplayData !== undefined && autoplayData !== '' && autoplayData !== false) {
        var autoplayValue = parseInt(autoplayData, 10);

        autoplay = !isNaN(autoplayValue) && autoplayValue > 0 ? autoplayValue : false;
      }

      var flickityOptions = {
        cellAlign: 'left',
        contain: true,
        groupCells: columns > 1 ? columns : 1,
        autoPlay: autoplay,
        prevNextButtons: arrows,
        pageDots: dots,
        wrapAround: wrap,
        fade: fade
      };

      if (fade) {
        flickityOptions.groupCells = false;
      }

      $slider.flickity(flickityOptions);
      $slider.addClass('is-initialized');
    });
  }

  $(document).ready(function () {
    initBwProductsSlider($(document));
  });

  jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/bw_products_slide.default', initBwProductsSlider);
  });

  window.initBwProductsSlider = initBwProductsSlider;
})(jQuery);
