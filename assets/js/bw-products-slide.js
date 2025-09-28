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
          console.log('Flickity init in editor', carouselElement);
          flkty.on('ready', function () {
            console.log('Flickity ready in editor, triggering reload and resize');
            flkty.reloadCells();
            flkty.resize();
            window.dispatchEvent(new Event('resize'));
            setTimeout(function () {
              console.log('Flickity delayed resize after ready');
              flkty.resize();
              window.dispatchEvent(new Event('resize'));
            }, 200);
          });

          if (!$carousel.data('bwFlickityEditorEventsBound')) {
            var editorChannel = elementor && elementor.channels && elementor.channels.editor;
            if (editorChannel && editorChannel.on) {
              editorChannel.on('panel:open', function () {
                console.log('Flickity resize after panel open');
                flkty.reloadCells();
                flkty.resize();
              });
              editorChannel.on('panel:close', function () {
                console.log('Flickity resize after panel close');
                flkty.reloadCells();
                flkty.resize();
              });
            }

            elementorFrontend.hooks.addAction(
              'document/responsive/after_set_breakpoint',
              function () {
                console.log('Flickity resize after breakpoint change');
                flkty.resize();
                window.dispatchEvent(new Event('resize'));
              }
            );

            $carousel.data('bwFlickityEditorEventsBound', true);
          }
        }
        $carousel.data('flickity', flkty);
        flkty.resize();
        window.dispatchEvent(new Event('resize'));
      }
    }
  );
});
