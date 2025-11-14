(function ($) {
  'use strict';

  var ensureTrailingSlash = function (path) {
    if (typeof path !== 'string' || !path.length) {
      return '';
    }

    return path.charAt(path.length - 1) === '/' ? path : path + '/';
  };

  var assetsUrl = ensureTrailingSlash(
    (window.bwSlickSlider && window.bwSlickSlider.assetsUrl) || ''
  );

  var normalizeBoolean = function (value, fallback) {
    if (typeof value === 'boolean') {
      return value;
    }

    if (typeof value === 'number') {
      return value !== 0;
    }

    if (typeof value === 'string') {
      var normalized = value.toLowerCase().trim();
      if (['1', 'true', 'yes', 'on'].indexOf(normalized) !== -1) {
        return true;
      }
      if (['0', 'false', 'no', 'off'].indexOf(normalized) !== -1) {
        return false;
      }
    }

    return typeof fallback === 'boolean' ? fallback : false;
  };

  var normalizeInteger = function (value, fallback) {
    var parsed = parseInt(value, 10);
    return isNaN(parsed) ? (typeof fallback === 'number' ? fallback : 0) : parsed;
  };

  var normalizeFloat = function (value, fallback) {
    var parsed = parseFloat(value);
    if (isNaN(parsed)) {
      return typeof fallback === 'number' ? fallback : 0;
    }

    return parsed;
  };

  var clamp = function (value, min, max) {
    if (typeof value !== 'number' || isNaN(value)) {
      return min;
    }

    if (value < min) {
      return min;
    }

    if (value > max) {
      return max;
    }

    return value;
  };

  var DEFAULT_DRAG_SMOOTHNESS = 60;

  var normalizeResponsive = function (responsive) {
    if (!Array.isArray(responsive)) {
      return undefined;
    }

    return responsive
      .map(function (item) {
        if (!item || typeof item !== 'object') {
          return null;
        }

        var breakpoint = normalizeInteger(item.breakpoint, null);
        if (breakpoint === null) {
          return null;
        }

        var settings = item.settings || {};
        var normalizedSettings = {};

        if (typeof settings.slidesToShow !== 'undefined') {
          normalizedSettings.slidesToShow = Math.max(
            1,
            normalizeInteger(settings.slidesToShow, 1)
          );
        }

        if (typeof settings.slidesToScroll !== 'undefined') {
          normalizedSettings.slidesToScroll = Math.max(
            1,
            normalizeInteger(settings.slidesToScroll, 1)
          );
        }

        if (typeof settings.infinite !== 'undefined') {
          normalizedSettings.infinite = normalizeBoolean(settings.infinite, true);
        }

        if (typeof settings.dots !== 'undefined') {
          normalizedSettings.dots = normalizeBoolean(settings.dots, true);
        }

        if (typeof settings.arrows !== 'undefined') {
          normalizedSettings.arrows = normalizeBoolean(settings.arrows, true);
        }

        if (typeof settings.centerMode !== 'undefined') {
          normalizedSettings.centerMode = normalizeBoolean(
            settings.centerMode,
            false
          );
        }

        if (typeof settings.variableWidth !== 'undefined') {
          normalizedSettings.variableWidth = normalizeBoolean(
            settings.variableWidth,
            false
          );
        }

        if (typeof settings.responsiveHeight !== 'undefined') {
          var heightData = settings.responsiveHeight;
          if (heightData && typeof heightData === 'object') {
            var size = normalizeFloat(heightData.size, null);
            var unit = (heightData.unit || 'px').toString();
            if (size !== null && size >= 0) {
              normalizedSettings.responsiveHeight = {
                size: size,
                unit: unit
              };
            }
          }
        }

        if (
          typeof normalizedSettings.slidesToShow !== 'undefined' &&
          typeof normalizedSettings.slidesToScroll !== 'undefined'
        ) {
          normalizedSettings.slidesToScroll = Math.min(
            normalizedSettings.slidesToScroll,
            normalizedSettings.slidesToShow
          );
        }

        return {
          breakpoint: breakpoint,
          settings: normalizedSettings,
        };
      })
      .filter(function (item) {
        return item !== null;
      });
  };

  var parseSettings = function ($slider) {
    var rawSettings = $slider.attr('data-slider-settings');
    var settings = {};

    if (rawSettings) {
      try {
        settings = JSON.parse(rawSettings);
      } catch (error) {
        settings = {};
      }
    }

    if (typeof settings !== 'object' || settings === null) {
      settings = {};
    }

    if (typeof settings.slidesToShow === 'undefined') {
      var fallbackColumns = normalizeInteger($slider.attr('data-columns'), 3);
      settings.slidesToShow = Math.max(1, fallbackColumns);
    } else {
      settings.slidesToShow = Math.max(1, normalizeInteger(settings.slidesToShow, 1));
    }

    settings.slidesToScroll = Math.max(
      1,
      normalizeInteger(settings.slidesToScroll, 1)
    );

    settings.slidesToScroll = Math.min(settings.slidesToScroll, settings.slidesToShow);

    if (typeof settings.autoplay !== 'undefined') {
      settings.autoplay = normalizeBoolean(settings.autoplay, false);
    }

    if (typeof settings.infinite !== 'undefined') {
      settings.infinite = normalizeBoolean(settings.infinite, true);
    }

    if (typeof settings.arrows !== 'undefined') {
      settings.arrows = normalizeBoolean(settings.arrows, true);
    }

    if (typeof settings.dots !== 'undefined') {
      settings.dots = normalizeBoolean(settings.dots, false);
    }

    if (typeof settings.fade !== 'undefined') {
      settings.fade = normalizeBoolean(settings.fade, false);
    }

    if (typeof settings.centerMode !== 'undefined') {
      settings.centerMode = normalizeBoolean(settings.centerMode, false);
    }

    if (typeof settings.variableWidth !== 'undefined') {
      settings.variableWidth = normalizeBoolean(settings.variableWidth, false);
    }

    if (typeof settings.adaptiveHeight !== 'undefined') {
      settings.adaptiveHeight = normalizeBoolean(settings.adaptiveHeight, false);
    }

    if (typeof settings.pauseOnHover !== 'undefined') {
      settings.pauseOnHover = normalizeBoolean(settings.pauseOnHover, true);
    }

    settings.responsive = normalizeResponsive(settings.responsive);

    var dragSmoothness = DEFAULT_DRAG_SMOOTHNESS;

    if (typeof settings.dragSmoothness !== 'undefined') {
      dragSmoothness = clamp(
        normalizeFloat(settings.dragSmoothness, DEFAULT_DRAG_SMOOTHNESS),
        0,
        100
      );
    }

    settings.dragSmoothness = dragSmoothness;

    var smoothFactor = dragSmoothness / 100;
    var minThreshold = 2;
    var maxThreshold = 20;
    settings.touchThreshold = Math.max(
      1,
      Math.round(
        maxThreshold - (maxThreshold - minThreshold) * smoothFactor
      )
    );

    var minFriction = 0.05;
    var maxFriction = 0.45;
    settings.edgeFriction = parseFloat(
      (minFriction + (maxFriction - minFriction) * smoothFactor).toFixed(3)
    );

    settings.swipeToSlide = true;

    if (typeof settings.touchMove === 'undefined') {
      settings.touchMove = true;
    }

    if (typeof settings.cssEase === 'undefined') {
      settings.cssEase = 'cubic-bezier(0.22, 0.61, 0.36, 1)';
    }

    settings.waitForAnimate = false;

    delete settings.dragSmoothness;

    return settings;
  };

  var sliderObservers = typeof WeakMap === 'function' ? new WeakMap() : null;
  var rebuildTimeouts = typeof WeakMap === 'function' ? new WeakMap() : null;

  var getColumnWidthInfo = function ($slider) {
    var info = {
      value: 'auto',
      hasCustom: false,
    };

    if (!$slider || !$slider.length || !$slider[0]) {
      return info;
    }

    var computedStyle = window.getComputedStyle($slider[0]);
    if (!computedStyle) {
      return info;
    }

    var columnWidthValue = computedStyle
      .getPropertyValue('--bw-column-width')
      .trim();

    if (
      columnWidthValue &&
      columnWidthValue !== 'auto' &&
      columnWidthValue !== 'initial' &&
      columnWidthValue !== 'inherit' &&
      columnWidthValue !== 'unset'
    ) {
      info.value = columnWidthValue;
      info.hasCustom = true;
    }

    return info;
  };

  var rebuildSlider = function ($currentSlider) {
    if (!$currentSlider || !$currentSlider.length) {
      return;
    }

    var columnWidthInfo = getColumnWidthInfo($currentSlider);

    if ($currentSlider.hasClass('slick-initialized')) {
      $currentSlider.slick('unslick');
    }

    var settings = parseSettings($currentSlider);

    if (columnWidthInfo.hasCustom) {
      $currentSlider.attr('data-has-column-width', 'true');
      settings.variableWidth = true;
    } else {
      $currentSlider.removeAttr('data-has-column-width');
    }

    if (typeof settings.prevArrow === 'undefined') {
      settings.prevArrow =
        '<button type="button" class="bw-slick-prev"><img src="' +
        assetsUrl +
        'img/arrow-l.svg" alt="prev"></button>';
    }

    if (typeof settings.nextArrow === 'undefined') {
      settings.nextArrow =
        '<button type="button" class="bw-slick-next"><img src="' +
        assetsUrl +
        'img/arrow-d.svg" alt="next"></button>';
    }

    $currentSlider.slick(settings);

    // Funzione per applicare l'altezza responsive
    var applyResponsiveHeight = function (event, slick, currentBreakpoint) {
      if (!settings.responsive || !Array.isArray(settings.responsive)) {
        return;
      }

      var heightToApply = null;

      // Se currentBreakpoint è undefined, cerchiamo il breakpoint corrente basato sulla larghezza della finestra
      if (typeof currentBreakpoint === 'undefined') {
        var windowWidth = $(window).width();
        // Ordina i breakpoint dal più piccolo al più grande
        var sortedBreakpoints = settings.responsive
          .slice()
          .sort(function (a, b) {
            return a.breakpoint - b.breakpoint;
          });

        // Trova il breakpoint attivo
        for (var i = 0; i < sortedBreakpoints.length; i++) {
          var bp = sortedBreakpoints[i];
          if (windowWidth <= bp.breakpoint && bp.settings.responsiveHeight) {
            heightToApply = bp.settings.responsiveHeight;
            break;
          }
        }
      } else {
        // Trova il breakpoint corrispondente
        for (var j = 0; j < settings.responsive.length; j++) {
          var responsiveItem = settings.responsive[j];
          if (
            responsiveItem.breakpoint === currentBreakpoint &&
            responsiveItem.settings.responsiveHeight
          ) {
            heightToApply = responsiveItem.settings.responsiveHeight;
            break;
          }
        }
      }

      // Applica l'altezza se trovata
      if (heightToApply && heightToApply.size >= 0) {
        $currentSlider.css(
          '--bw-image-height',
          heightToApply.size + heightToApply.unit
        );
      }
    };

    // Applica l'altezza all'inizializzazione
    applyResponsiveHeight(null, null, undefined);

    // Ascolta i cambiamenti di breakpoint
    $currentSlider.on('breakpoint', applyResponsiveHeight);

    // Nell'editor Elementor, aggiungi anche listener per resize
    if (
      typeof elementorFrontend !== 'undefined' &&
      elementorFrontend.isEditMode()
    ) {
      var resizeTimeout;
      var handleResize = function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
          applyResponsiveHeight(null, null, undefined);
        }, 100);
      };

      $(window).on('resize.bwSlickSlider', handleResize);

      // Salva il riferimento per poter rimuovere il listener se necessario
      $currentSlider.data('bwResizeHandler', handleResize);

      setTimeout(function () {
        if ($currentSlider.hasClass('slick-initialized')) {
          $currentSlider.slick('setPosition');
        }
      }, 100);
    }

    $currentSlider.data('bwColumnWidthValue', columnWidthInfo.value);
  };

  var scheduleRebuild = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    if (!rebuildTimeouts) {
      rebuildSlider($slider);
      return;
    }

    var element = $slider[0];
    var existingTimeout = rebuildTimeouts.get(element);
    if (existingTimeout) {
      clearTimeout(existingTimeout);
    }

    var timeoutId = setTimeout(function () {
      rebuildTimeouts.delete(element);
      rebuildSlider($slider);
    }, 100);

    rebuildTimeouts.set(element, timeoutId);
  };

  var observeColumnWidthChanges = function ($slider) {
    if (
      typeof MutationObserver === 'undefined' ||
      !sliderObservers ||
      !$slider ||
      !$slider.length
    ) {
      return;
    }

    var element = $slider[0];
    if (sliderObservers.has(element)) {
      return;
    }

    var observer = new MutationObserver(function (mutations) {
      var shouldRebuild = false;
      var previousValue = $slider.data('bwColumnWidthValue');

      mutations.forEach(function (mutation) {
        if (mutation.type !== 'attributes') {
          return;
        }

        if (mutation.attributeName !== 'style') {
          return;
        }

        var info = getColumnWidthInfo($slider);
        if (info.value !== previousValue) {
          shouldRebuild = true;
          previousValue = info.value;
        }
      });

      if (shouldRebuild) {
        $slider.data('bwColumnWidthValue', previousValue);
        scheduleRebuild($slider);
      }
    });

    observer.observe(element, {
      attributes: true,
      attributeFilter: ['style'],
    });

    sliderObservers.set(element, observer);
  };

  var initSlickSlider = function ($scope) {
    var $context = $scope && $scope.length ? $scope : $(document);
    var $sliders = $context.find('.bw-slick-slider');

    if (!$sliders.length) {
      return;
    }

    $sliders.each(function () {
      var $currentSlider = $(this);

      rebuildSlider($currentSlider);

      if (
        typeof elementorFrontend !== 'undefined' &&
        elementorFrontend.isEditMode()
      ) {
        observeColumnWidthChanges($currentSlider);
      }
    });
  };

  $(function () {
    if (
      typeof elementorFrontend === 'undefined' ||
      typeof elementorFrontend.isEditMode !== 'function' ||
      !elementorFrontend.isEditMode()
    ) {
      initSlickSlider($(document));
    }
  });

  var hooksRegistered = false;
  var registerElementorHooks = function () {
    if (hooksRegistered) {
      return;
    }

    if (
      typeof elementorFrontend === 'undefined' ||
      !elementorFrontend.hooks ||
      typeof elementorFrontend.hooks.addAction !== 'function'
    ) {
      return;
    }

    hooksRegistered = true;

    var widgetsToInit = [
      'frontend/element_ready/bw-slick-slider.default',
      'frontend/element_ready/bw-slide-showcase.default',
    ];

    widgetsToInit.forEach(function (hook) {
      elementorFrontend.hooks.addAction(hook, function ($scope) {
        initSlickSlider($scope);
      });
    });
  };

  registerElementorHooks();
  $(window).on('elementor/frontend/init', registerElementorHooks);
})(jQuery);
