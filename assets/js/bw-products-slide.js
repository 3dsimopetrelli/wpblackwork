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
      var existingEditorEvents = $carousel.data('bwFlickityEditorEvents');

      if (existingEditorEvents) {
        if (
          existingEditorEvents.editorChannel &&
          typeof existingEditorEvents.editorChannel.off === 'function'
        ) {
          existingEditorEvents.editorChannel.off(
            'panel:open',
            existingEditorEvents.handleResize
          );
          existingEditorEvents.editorChannel.off(
            'panel:close',
            existingEditorEvents.handleResize
          );
        }

        if (
          typeof elementorFrontend !== 'undefined' &&
          elementorFrontend.hooks &&
          typeof elementorFrontend.hooks.removeAction === 'function'
        ) {
          elementorFrontend.hooks.removeAction(
            'document/responsive/after_set_breakpoint',
            existingEditorEvents.handleResize
          );
        }

        if (
          existingEditorEvents.flickityInstance &&
          typeof existingEditorEvents.flickityInstance.off === 'function'
        ) {
          existingEditorEvents.flickityInstance.off(
            'ready',
            existingEditorEvents.triggerFullRefresh
          );
        }
      }

      existingInstance.destroy();

      $carousel.removeData('bwFlickityEditorEvents');
      $carousel.removeData('bwFlickityEditorEventsBound');
    }

    var parseBool = function (value, defaultValue) {
      if (typeof value === 'undefined' || value === null) {
        return defaultValue;
      }

      if (typeof value === 'boolean') {
        return value;
      }

      if (typeof value === 'number') {
        return value !== 0;
      }

      if (typeof value === 'string') {
        var normalized = value.toLowerCase().trim();
        if (normalized === '') {
          return defaultValue;
        }
        if (['false', '0', 'no', 'off'].indexOf(normalized) !== -1) {
          return false;
        }
        if (['true', '1', 'yes', 'on'].indexOf(normalized) !== -1) {
          return true;
        }
      }

      return defaultValue;
    };

    var parseFloatOption = function (value, defaultValue) {
      if (typeof value === 'undefined' || value === null || value === '') {
        return defaultValue;
      }

      var parsed = parseFloat(value);
      return isNaN(parsed) ? defaultValue : parsed;
    };

    var parseIntOption = function (value, defaultValue) {
      if (typeof value === 'undefined' || value === null || value === '') {
        return defaultValue;
      }

      var parsed = parseInt(value, 10);
      return isNaN(parsed) ? defaultValue : parsed;
    };

    var cellAlign = $carousel.data('cellAlign');
    var validAlignments = ['left', 'center', 'right'];
    if (validAlignments.indexOf(cellAlign) === -1) {
      cellAlign = 'left';
    }

    var contain = parseBool($carousel.data('contain'), true);

    var groupCellsData = $carousel.data('groupCells');
    var groupCells = 1;
    if (typeof groupCellsData === 'string' && groupCellsData.toLowerCase() === 'auto') {
      groupCells = 'auto';
    } else {
      groupCells = Math.max(1, parseIntOption(groupCellsData, 1));
    }

    var wrapAround = parseBool($carousel.data('wrapAround'), false);
    var freeScroll = parseBool($carousel.data('freeScroll'), false);
    var freeScrollFriction = parseFloatOption(
      $carousel.data('freeScrollFriction'),
      0.075
    );
    var friction = parseFloatOption($carousel.data('friction'), 0.28);
    var selectedAttraction = parseFloatOption(
      $carousel.data('selectedAttraction'),
      0.025
    );
    var draggable = parseBool($carousel.data('draggable'), true);
    var dragThreshold = Math.max(0, parseIntOption($carousel.data('dragThreshold'), 3));
    var percentPosition = parseBool($carousel.data('percentPosition'), true);
    var adaptiveHeight = parseBool($carousel.data('adaptiveHeight'), false);
    var resize = parseBool($carousel.data('resize'), true);
    var watchCss = parseBool($carousel.data('watchCss'), false);
    var imagesLoaded = parseBool($carousel.data('imagesLoaded'), true);
    var setGallerySize = parseBool($carousel.data('setGallerySize'), true);
    var prevNextButtons = parseBool($carousel.data('prevNextButtons'), true);
    var pageDots = parseBool($carousel.data('pageDots'), true);
    var accessibility = parseBool($carousel.data('accessibility'), true);
    var rightToLeft = parseBool($carousel.data('rightToLeft'), false);

    var autoPlayRaw = $carousel.data('autoPlay');
    var autoPlayValue = parseIntOption(autoPlayRaw, 0);
    var autoPlay = autoPlayValue > 0 ? autoPlayValue : false;

    var pauseAutoPlayOnHover = parseBool(
      $carousel.data('pauseAutoPlayOnHover'),
      true
    );

    var initialIndex = Math.max(0, parseIntOption($carousel.data('initialIndex'), 0));

    var asNavFor = $carousel.data('asNavFor');
    if (typeof asNavFor === 'string') {
      asNavFor = asNavFor.trim();
    }

    var arrowShape = $carousel.data('arrowShape');

    var options = {
      cellSelector: '.bw-products-slide-item',
      cellAlign: cellAlign,
      contain: contain,
      groupCells: groupCells,
      wrapAround: wrapAround,
      freeScroll: freeScroll,
      freeScrollFriction: freeScrollFriction,
      friction: friction,
      selectedAttraction: selectedAttraction,
      draggable: draggable,
      dragThreshold: dragThreshold,
      percentPosition: percentPosition,
      adaptiveHeight: adaptiveHeight,
      resize: resize,
      watchCSS: watchCss,
      imagesLoaded: imagesLoaded,
      setGallerySize: setGallerySize,
      prevNextButtons: prevNextButtons,
      pageDots: pageDots,
      accessibility: accessibility,
      rightToLeft: rightToLeft,
      autoPlay: autoPlay,
      pauseAutoPlayOnHover: pauseAutoPlayOnHover,
      initialIndex: initialIndex,
    };

    if (asNavFor && typeof asNavFor === 'string' && asNavFor !== '') {
      options.asNavFor = asNavFor;
    }

    if (arrowShape && typeof arrowShape === 'string' && arrowShape !== '') {
      options.arrowShape = arrowShape;
    }

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
        $carousel.data('bwFlickityEditorEvents', {
          handleResize: handleResize,
          triggerFullRefresh: triggerFullRefresh,
          editorChannel: editorChannel,
          flickityInstance: flkty,
        });
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
