jQuery(document).ready(function($){
  $('.bw-products-slider').each(function(){
    let el = $(this);

    let autoplay = el.data('autoplay');
    let wrap = el.data('wrap') === 'yes';
    let fade = el.data('fade') === 'yes';

    el.flickity({
      cellAlign: 'left',
      contain: true,
      groupCells: true,
      autoPlay: autoplay ? autoplay : false,
      wrapAround: wrap,
      fade: fade
    });
  });
});
