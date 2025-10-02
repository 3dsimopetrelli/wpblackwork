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

    return settings;
  };

  var isObject = function (value) {
    return Object.prototype.toString.call(value) === '[object Object]';
  };

  var getLocalizedString = function (key, fallback) {
    if (
      isObject(window.bwSlickSlider) &&
      isObject(window.bwSlickSlider.i18n) &&
      typeof window.bwSlickSlider.i18n[key] === 'string'
    ) {
      return window.bwSlickSlider.i18n[key];
    }

    return typeof fallback === 'string' ? fallback : '';
  };

  var fetchQuickViewData = function (productId) {
    if (!isObject(window.bwSlickSlider) || !window.bwSlickSlider.ajaxUrl) {
      return $.Deferred().reject({
        message: 'Missing AJAX endpoint.',
      }).promise();
    }

    return $.ajax({
      url: window.bwSlickSlider.ajaxUrl,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'bw_qv_get_product',
        nonce: window.bwSlickSlider.quickViewNonce || '',
        product_id: productId,
      },
    });
  };

  var activeQuickViewClose = null;

  var setupQuickViewOverlay = function ($overlay) {
    var state = {
      currentRequest: null,
      isOpen: false,
      lastTrigger: null,
    };

    var $dialog = $overlay.find('.bw-quickview');
    var $image = $overlay.find('.bw-quickview-image img');
    var $loader = $overlay.find('.bw-loader');
    var $content = $overlay.find('.bw-quickview-content, .bw-qv-content');
    var $title = $overlay.find('.bw-qv-title');
    var $description = $overlay.find('.bw-qv-description');
    var $variations = $overlay.find('.bw-qv-variations');
    var $price = $overlay.find('.bw-qv-price');
    var $addToCart = $overlay.find('.bw-qv-addtocart');
    var $message = $overlay.find('.bw-qv-message');

    var resetContent = function () {
      $title.text('');
      $description.empty();
      $variations.empty();
      $price.empty();
      $addToCart.empty();

      toggleSection($description, false);
      toggleSection($variations, false);
      toggleSection($price, false);
      toggleSection($addToCart, false);

      if ($message.length) {
        $message.empty().removeClass('is-visible');
      }
    };

    var toggleSection = function ($element, hasContent) {
      if (!$element || !$element.length) {
        return;
      }

      if (hasContent) {
        $element.removeClass('is-empty').attr('aria-hidden', 'false');
      } else {
        $element.addClass('is-empty').attr('aria-hidden', 'true');
      }
    };

    var populateContent = function (data) {
      var title = data && data.title ? data.title : '';
      var description = data && data.description ? data.description : '';
      var priceHtml = data && data.price_html ? data.price_html : '';
      var variationsHtml = data && data.variations_html ? data.variations_html : '';
      var addToCartHtml = data && data.add_to_cart_html ? data.add_to_cart_html : '';
      var imageSrc = data && data.image ? data.image : '';
      var imageAlt = data && data.image_alt ? data.image_alt : '';

      $title.text(title);
      $description.html(description);
      $variations.html(variationsHtml);
      $price.html(priceHtml);
      $addToCart.html(addToCartHtml);

      if (variationsHtml && $variations.length && $.fn.wc_variation_form) {
        $variations.find('.variations_form').each(function () {
          var $form = $(this);
          if (typeof $form.wc_variation_form === 'function') {
            $form.wc_variation_form();
          }
          $form.trigger('wc_variation_form');
        });
      }

      toggleSection($description, !!description);
      toggleSection($variations, !!variationsHtml);
      toggleSection($price, !!priceHtml);
      toggleSection($addToCart, !!addToCartHtml);

      if (imageSrc) {
        $image.attr('src', imageSrc);
      }

      $image.attr('alt', imageAlt || title || '');
    };

    var close = function () {
      if (!state.isOpen) {
        return;
      }

      if (state.currentRequest && typeof state.currentRequest.abort === 'function') {
        state.currentRequest.abort();
        state.currentRequest = null;
      }

      state.isOpen = false;

      $overlay
        .removeClass('is-open is-active opening loaded show-content loading has-error')
        .attr('aria-hidden', 'true');

      $('body').removeClass('bw-quickview-open');

      if (state.lastTrigger && state.lastTrigger.length) {
        state.lastTrigger.focus();
      }

      state.lastTrigger = null;
      resetContent();

      if (activeQuickViewClose === close) {
        activeQuickViewClose = null;
      }
    };

    var handleError = function (message) {
      close();

      if (message && window.console && typeof window.console.error === 'function') {
        window.console.error('Quick View:', message);
      }
    };

    var open = function (event, payload) {
      var options = payload || {};
      var productId = parseInt(options.productId, 10);

      if (!productId) {
        return;
      }

      if (state.currentRequest && typeof state.currentRequest.abort === 'function') {
        state.currentRequest.abort();
        state.currentRequest = null;
      }

      resetContent();
      state.isOpen = true;
      state.lastTrigger = options.trigger || null;

      $overlay
        .attr('aria-hidden', 'false')
        .addClass('is-open is-active loading opening')
        .removeClass('loaded show-content has-error');

      $('body').addClass('bw-quickview-open');

      if (options.preview && options.preview.src) {
        $image.attr('src', options.preview.src);
        $image.attr('alt', options.preview.alt || '');
      }

      if ($dialog.length) {
        setTimeout(function () {
          $dialog.trigger('focus');
        }, 20);
      }

      activeQuickViewClose = close;

      state.currentRequest = fetchQuickViewData(productId)
        .done(function (response) {
          if (!state.isOpen) {
            return;
          }

          if (response && response.success) {
            populateContent(response.data || {});
            $overlay.addClass('loaded');
            setTimeout(function () {
              if (state.isOpen) {
                $overlay.addClass('show-content').removeClass('opening');
              }
            }, 40);
          } else {
            var message = getLocalizedString('error', 'Unable to load product.');
            if (response && response.data && response.data.message) {
              message = response.data.message;
            }
            handleError(message);
          }
        })
        .fail(function (jqXHR, textStatus) {
          if (textStatus === 'abort') {
            return;
          }

          var errorMessage = getLocalizedString('error', 'Unable to load product.');

          if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data) {
            if (jqXHR.responseJSON.data.message) {
              errorMessage = jqXHR.responseJSON.data.message;
            }
          }

          handleError(errorMessage);
        })
        .always(function () {
          state.currentRequest = null;
          $overlay.removeClass('loading');
        });
    };

    $overlay.on('bw:qv:open', open);

    $overlay.on('click', '.bw-quickview-close, .bw-qv-close', function (event) {
      event.preventDefault();
      close();
    });

    $overlay.on('click', '.bw-quickview-backdrop', function (event) {
      event.preventDefault();
      close();
    });

    $overlay.on('click', function (event) {
      if (event.target === event.currentTarget) {
        close();
      }
    });

    $overlay.data('bw-init', true);
    $overlay.data('bw-close', close);
  };

  var initSlickSlider = function ($scope) {
    var $slider = $scope.find('.bw-slick-slider');

    if (!$slider.length) {
      return;
    }

    $slider.each(function () {
      var $currentSlider = $(this);

      if ($currentSlider.hasClass('slick-initialized')) {
        $currentSlider.slick('unslick');
      }

      var settings = parseSettings($currentSlider);

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

      if (
        typeof elementorFrontend !== 'undefined' &&
        elementorFrontend.isEditMode()
      ) {
        setTimeout(function () {
          if ($currentSlider.hasClass('slick-initialized')) {
            $currentSlider.slick('setPosition');
          }
        }, 100);
      }
    });
  };

  var initQuickView = function () {
    $('.elementor-widget-bw-slick-slider').each(function () {
      var $widget = $(this);
      var $overlay = $widget.find('[data-product-quick-view]').first();

      if (!$overlay.length) {
        return;
      }

      if (!$overlay.hasClass('bw-qv-overlay')) {
        $overlay.addClass('bw-qv-overlay');
      }

      if (!$overlay.data('bw-init')) {
        setupQuickViewOverlay($overlay);
      }

      if ($widget.data('bw-qv-triggers')) {
        return;
      }

      $widget.on('click', '.overlay-button--quick', function (event) {
        event.preventDefault();

        var $trigger = $(this);
        var productId = parseInt($trigger.attr('data-product-id'), 10);

        if (!productId) {
          return;
        }

        var $originImage = $trigger
          .closest('.bw-ss__card')
          .find('.bw-ss__media img')
          .first();

        var preview = null;

        if ($originImage && $originImage.length) {
          preview = {
            src: $originImage.attr('src') || $originImage.attr('data-src') || '',
            alt: $originImage.attr('alt') || '',
          };
        }

        $overlay.trigger('bw:qv:open', [
          {
            productId: productId,
            trigger: $trigger,
            preview: preview,
          },
        ]);
      });

      $widget.data('bw-qv-triggers', true);
    });

    $('.bw-quickview-overlay').each(function () {
      var $overlay = $(this);

      if (!$overlay.hasClass('bw-qv-overlay')) {
        $overlay.addClass('bw-qv-overlay');
      }

      if (!$overlay.data('bw-init')) {
        setupQuickViewOverlay($overlay);
      }
    });
  };

  $(document).on('keyup', function (event) {
    if (event.key === 'Escape' && typeof activeQuickViewClose === 'function') {
      activeQuickViewClose();
    }
  });

  $(initQuickView);

  $(window).on('elementor/frontend/init', function () {
    if (
      typeof elementorFrontend === 'undefined' ||
      !elementorFrontend.hooks ||
      typeof elementorFrontend.hooks.addAction !== 'function'
    ) {
      return;
    }

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/bw-slick-slider.default',
      function ($scope) {
        initSlickSlider($scope);
        initQuickView();
      }
    );
  });
})(jQuery);
