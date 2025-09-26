jQuery(document).ready(function ($) {
  $('.bw-products-slider').each(function () {
    var $slider = $(this);

    var columns = parseInt($slider.data('columns'), 10);
    var gap = parseInt($slider.data('gap'), 10);
    var autoplay = parseInt($slider.data('autoplay'), 10);
    var wrap = $slider.data('wrap') === 'yes';
    var fade = $slider.data('fade') === 'yes';

    if (isNaN(columns) || columns < 1) {
      columns = 1;
    }

    if (isNaN(gap)) {
      gap = 0;
    }

    if (isNaN(autoplay) || autoplay <= 0) {
      autoplay = false;
    }

    $slider.find('.carousel-cell').css('margin-right', gap + 'px');

    var flickityOptions = {
      cellAlign: 'left',
      contain: true,
      groupCells: columns > 1 ? columns : 1,
      autoPlay: autoplay,
      wrapAround: wrap,
      fade: fade,
      pageDots: false
    };

    if (fade) {
      flickityOptions.groupCells = false;
    }

    $slider.flickity(flickityOptions);
  });
});
