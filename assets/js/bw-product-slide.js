(function ($) {
  'use strict';

  var parseSettings = function ($slider) {
    var rawSettings = $slider.attr('data-slider-settings');
    if (!rawSettings) {
      return {};
    }

    try {
      var parsed = JSON.parse(rawSettings);
      return typeof parsed === 'object' && parsed !== null ? parsed : {};
    } catch (error) {
      return {};
    }
  };

  var buildSettings = function (defaults, custom) {
    var settings = $.extend(true, {}, defaults, custom || {});

    if (settings.responsive && !Array.isArray(settings.responsive)) {
      delete settings.responsive;
    }

    return settings;
  };

  var bindPopup = function ($container) {
    var $popup = $container.find('.bw-product-slide-popup');
    if (!$popup.length) {
      return;
    }

    var $body = $('body');
    var $popupTitle = $popup.find('.bw-popup-title');

    var hideTimeoutId = null;

    var clearHideTimeout = function () {
      if (hideTimeoutId !== null) {
        window.clearTimeout(hideTimeoutId);
        hideTimeoutId = null;
      }
    };

    var ensurePopupHidden = function () {
      clearHideTimeout();
      $popup.attr('aria-hidden', 'true');
      $popup.attr('hidden', 'hidden');
    };

    var onPopupTransitionEnd = function (event) {
      if (event.target !== $popup.get(0)) {
        return;
      }

      ensurePopupHidden();
      $popup.off('transitionend.bwProductSlidePopup', onPopupTransitionEnd);
    };

    var openPopup = function () {
      clearHideTimeout();
      $popup.off('transitionend.bwProductSlidePopup', onPopupTransitionEnd);

      if ($popup.attr('hidden')) {
        $popup.removeAttr('hidden');
      }

      $popup.attr('aria-hidden', 'false');

      window.requestAnimationFrame(function () {
        $popup.addClass('active');
        $body.addClass('popup-active');
      });
    };

    var closePopup = function () {
      $popup.removeClass('active');
      $body.removeClass('popup-active');

      $popup
        .off('transitionend.bwProductSlidePopup', onPopupTransitionEnd)
        .on('transitionend.bwProductSlidePopup', onPopupTransitionEnd);

      hideTimeoutId = window.setTimeout(function () {
        ensurePopupHidden();
        $popup.off('transitionend.bwProductSlidePopup', onPopupTransitionEnd);
      }, 600);
    };

    $container
      .find('.bw-product-slide-item img')
      .off('click.bwProductSlide')
      .on('click.bwProductSlide', function () {
        var title = $(this).attr('alt') || '';
        $popupTitle.text(title);
        openPopup();
      });

    $container
      .find('.bw-popup-close-btn')
      .off('click.bwProductSlide')
      .on('click.bwProductSlide', function (event) {
        event.preventDefault();
        closePopup();
      });

    $popup.off('click.bwProductSlide').on('click.bwProductSlide', function (event) {
      if ($(event.target).is($popup)) {
        closePopup();
      }
    });

    $(document)
      .off('keydown.bwProductSlide')
      .on('keydown.bwProductSlide', function (event) {
        if (event.key === 'Escape') {
          closePopup();
        }
      });
  };

  var markImageAsLoaded = function ($image) {
    window.requestAnimationFrame(function () {
      $image.addClass('bw-loaded');
    });
  };

  var prepareSliderImages = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    var $images = $slider.find('.bw-product-slide-item img');

    if (!$images.length) {
      return;
    }

    $images.each(function () {
      var $image = $(this);
      var imageElement = this;

      if (!$image.attr('loading')) {
        $image.attr('loading', 'lazy');
      }

      if (!$image.attr('decoding')) {
        $image.attr('decoding', 'async');
      }

      if (!$image.hasClass('bw-fade-image')) {
        $image.addClass('bw-fade-image');
      }

      if (imageElement.complete && imageElement.naturalWidth > 0) {
        markImageAsLoaded($image);
        return;
      }

      if ($image.hasClass('bw-loaded')) {
        return;
      }

      $image.off('load.bwProductSlideFade error.bwProductSlideFade');

      var handleLoad = function () {
        markImageAsLoaded($image);
        $image.off('error.bwProductSlideFade');
      };

      $image
        .one('load.bwProductSlideFade', handleLoad)
        .one('error.bwProductSlideFade', function () {
          markImageAsLoaded($image);
          $image.off('load.bwProductSlideFade');
        });
    });
  };

  var refreshSliderImages = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    var $images = $slider.find('.bw-product-slide-item img');

    if (!$images.length) {
      return;
    }

    $images.each(function () {
      var $image = $(this);

      $image.css({
        width: '100%',
        'max-width': 'none',
        height: '',
        'max-height': '',
      });
    });

    if ($slider.hasClass('slick-initialized')) {
      $slider.slick('setPosition');
    }
  };

  var unbindResponsiveUpdates = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    var previousResizeEvent = $slider.data('bwResizeEvent');
    if (previousResizeEvent) {
      $(window).off(previousResizeEvent);
      $slider.removeData('bwResizeEvent');
    }

    var previousEditorHandler = $slider.data('bwEditorChangeHandler');
    if (
      previousEditorHandler &&
      window.elementor &&
      elementor.channels &&
      elementor.channels.editor &&
      typeof elementor.channels.editor.off === 'function'
    ) {
      elementor.channels.editor.off('change', previousEditorHandler);
      $slider.removeData('bwEditorChangeHandler');
    }

    if (previousResizeEvent || previousEditorHandler) {
      $slider.removeData('bwResponsiveBound');
    }
  };

  var bindResponsiveUpdates = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    unbindResponsiveUpdates($slider);

    var resizeEvent = 'resize.bwProductSlide-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

    var refreshImages = function () {
      refreshSliderImages($slider);
    };

    $(window).on(resizeEvent, refreshImages);
    $slider.data('bwResizeEvent', resizeEvent);

    if (
      window.elementorFrontend &&
      elementorFrontend.isEditMode() &&
      window.elementor &&
      elementor.channels &&
      elementor.channels.editor &&
      typeof elementor.channels.editor.on === 'function'
    ) {
      var editorHandler = function (panel) {
        if (panel && panel.changed && Object.prototype.hasOwnProperty.call(panel.changed, 'column_width')) {
          setTimeout(refreshImages, 200);
        }
      };

      elementor.channels.editor.on('change', editorHandler);
      $slider.data('bwEditorChangeHandler', editorHandler);
    }

    refreshImages();
    $slider.data('bwResponsiveBound', true);
  };

  var initProductSlide = function ($scope) {
    var $containers = $scope.find('.bw-product-slide');

    if (!$containers.length) {
      return;
    }

    $containers.each(function () {
      var $container = $(this);
      var $slider = $container.find('.bw-product-slide-wrapper');

      if (!$slider.length || !$slider.children().length) {
        return;
      }

      unbindResponsiveUpdates($slider);

      if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
      }

      $slider.off('.bwProductSlide');

      var defaults = {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        dots: false,
        infinite: true,
        speed: 600,
        fade: false,
        prevArrow: $container.find('.bw-prev'),
        nextArrow: $container.find('.bw-next'),
      };

      var settings = buildSettings(defaults, parseSettings($slider));
      settings.prevArrow = defaults.prevArrow;
      settings.nextArrow = defaults.nextArrow;

      var hasCustomColumnWidth = $slider.is('[data-has-column-width]');

      if (hasCustomColumnWidth) {
        settings.variableWidth = true;

        if (Array.isArray(settings.responsive)) {
          settings.responsive = settings.responsive.map(function (entry) {
            if (!entry || typeof entry !== 'object') {
              return entry;
            }

            var responsiveEntry = $.extend(true, {}, entry);

            if (!responsiveEntry.settings || typeof responsiveEntry.settings !== 'object') {
              responsiveEntry.settings = {};
            }

            responsiveEntry.settings.variableWidth = true;

            return responsiveEntry;
          });
        }
      }

      var $count = $container.find('.bw-product-slide-count .current');
      var totalSlides = $slider.children().length;
      $container.find('.bw-product-slide-count .total').text(totalSlides);

      var updateCounter = function (event, slick, currentSlide) {
        var index = 0;

        if (typeof currentSlide === 'number') {
          index = currentSlide;
        } else if (slick && typeof slick.currentSlide === 'number') {
          index = slick.currentSlide;
        }

        $count.text(index + 1);
      };

      $slider
        .removeClass('bw-slider-ready')
        .addClass('bw-enable-fade');

      prepareSliderImages($slider);

      var onSliderReady = function () {
        window.requestAnimationFrame(function () {
          $slider.addClass('bw-slider-ready');
          prepareSliderImages($slider);
        });
      };

      $slider
        .off('init.bwProductSlideReady reInit.bwProductSlideReady')
        .on('init.bwProductSlideReady reInit.bwProductSlideReady', onSliderReady);

      $slider.on('init.bwProductSlide reInit.bwProductSlide afterChange.bwProductSlide', updateCounter);
      $slider.slick(settings);

      refreshSliderImages($slider);
      bindResponsiveUpdates($slider);

      if (settings.arrows === false) {
        $container.find('.bw-product-slide-arrows').hide();
      } else {
        $container.find('.bw-product-slide-arrows').show();
      }

      bindPopup($container);
    });
  };

  $(document).ready(function () {
    initProductSlide($(document));
  });

  var productSlideHooksRegistered = false;
  var registerElementorHooks = function () {
    if (productSlideHooksRegistered) {
      return;
    }

    var frontend = window.elementorFrontend;
    if (
      !frontend ||
      !frontend.hooks ||
      typeof frontend.hooks.addAction !== 'function'
    ) {
      return;
    }

    productSlideHooksRegistered = true;

    frontend.hooks.addAction(
      'frontend/element_ready/bw-product-slide.default',
      function ($scope) {
        initProductSlide($scope);
      }
    );
  };

  registerElementorHooks();
  $(window).on('elementor/frontend/init', registerElementorHooks);
})(jQuery);
