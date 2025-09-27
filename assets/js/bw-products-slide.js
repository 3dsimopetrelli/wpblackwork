jQuery(document).ready(function ($) {
  $('.bw-products-slider').each(function () {
    var $slider = $(this);

    var columns = parseInt($slider.data('columns'), 10);
    var gap = parseInt($slider.data('gap'), 10);
    var autoplayEnabled = $slider.data('autoPlay') === 'yes';
    var autoplaySpeed = parseInt($slider.data('autoPlaySpeed'), 10);
    var prevNextButtons = $slider.data('prevNextButtons') === 'yes';
    var pageDots = $slider.data('pageDots') === 'yes';
    var wrapAround = $slider.data('wrapAround') === 'yes';
    var fade = $slider.data('fade') === 'yes';

    if (isNaN(columns) || columns < 1) {
      columns = 1;
    }

    if ($slider.length) {
      if (!isNaN(gap)) {
        $slider[0].style.setProperty('--gap', gap + 'px');
      }

      $slider[0].style.setProperty('--columns', columns);
    }

    var autoplay = false;
    if (autoplayEnabled) {
      autoplay = !isNaN(autoplaySpeed) && autoplaySpeed > 0 ? autoplaySpeed : 3000;
    }

    var flickityOptions = {
      cellAlign: 'left',
      contain: true,
      groupCells: columns > 1 ? columns : 1,
      autoPlay: autoplay,
      wrapAround: wrapAround,
      fade: fade,
      prevNextButtons: prevNextButtons,
      pageDots: pageDots
    };

    if (fade) {
      flickityOptions.groupCells = false;
    }

    $slider.flickity(flickityOptions);
  });
});
