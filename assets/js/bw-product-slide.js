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

    var openPopup = function () {
      $popup.addClass('active');
      $body.addClass('popup-active');
    };

    var closePopup = function () {
      $popup.removeClass('active');
      $body.removeClass('popup-active');
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

      $slider.on('init.bwProductSlide reInit.bwProductSlide afterChange.bwProductSlide', updateCounter);
      $slider.slick(settings);

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
