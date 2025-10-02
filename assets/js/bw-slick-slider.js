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
        action: 'bw_quickview_product',
        nonce: window.bwSlickSlider.quickViewNonce || '',
        product_id: productId,
      },
    });
  };

  var collectQuickViewElements = function ($overlay) {
    if (!$overlay || !$overlay.length) {
      return null;
    }

    var $dialog = $overlay.find('.bw-quickview');

    if (!$dialog.length) {
      return null;
    }

    return {
      $overlay: $overlay,
      $dialog: $dialog,
      $backdrop: $overlay.find('.bw-quickview-backdrop'),
      $image: $dialog.find('.bw-quickview-image img'),
      $title: $dialog.find('.bw-qv-title'),
      $description: $dialog.find('.bw-qv-description'),
      $variations: $dialog.find('.bw-qv-variations'),
      $price: $dialog.find('.bw-qv-price'),
      $addToCart: $dialog.find('.bw-qv-addtocart'),
      $message: $dialog.find('.bw-qv-message'),
      $viewProduct: $dialog.find('.bw-qv-view-product'),
    };
  };

  var toggleQuickViewSection = function ($element, hasContent) {
    if (!$element || !$element.length) {
      return;
    }

    if (hasContent) {
      $element.removeClass('is-empty').attr('aria-hidden', 'false');
    } else {
      $element.addClass('is-empty').attr('aria-hidden', 'true');
    }
  };

  var resetQuickViewContent = function (elements) {
    if (!elements) {
      return;
    }

    if (elements.$title && elements.$title.length) {
      elements.$title.empty();
    }

    if (elements.$description && elements.$description.length) {
      elements.$description.empty();
      toggleQuickViewSection(elements.$description, false);
    }

    if (elements.$variations && elements.$variations.length) {
      elements.$variations.empty();
      toggleQuickViewSection(elements.$variations, false);
    }

    if (elements.$price && elements.$price.length) {
      elements.$price.empty();
      toggleQuickViewSection(elements.$price, false);
    }

    if (elements.$addToCart && elements.$addToCart.length) {
      elements.$addToCart.empty();
      toggleQuickViewSection(elements.$addToCart, false);
    }

    if (elements.$message && elements.$message.length) {
      elements.$message.empty().removeClass('is-visible').attr('aria-hidden', 'true');
    }

    if (elements.$image && elements.$image.length) {
      elements.$image.attr('src', '').attr('alt', '');
    }
  };

  var showQuickViewMessage = function (elements, message) {
    if (!elements || !elements.$message || !elements.$message.length) {
      return;
    }

    var text = message || getLocalizedString('error', 'Unable to load product.');

    elements.$message
      .text(text)
      .addClass('is-visible')
      .attr('aria-hidden', 'false');
  };

  var initializeVariationForms = function ($container) {
    if (!$container || !$container.length || !$.fn.wc_variation_form) {
      return;
    }

    $container.find('.variations_form').each(function () {
      var $form = $(this);
      if (typeof $form.wc_variation_form === 'function') {
        $form.wc_variation_form();
      }
      $form.trigger('wc_variation_form');
    });
  };

  var populateQuickViewContent = function (elements, data) {
    if (!elements) {
      return;
    }

    var safeData = data || {};
    var title = safeData.title || '';
    var description = safeData.description || '';
    var priceHtml = safeData.price || safeData.price_html || '';
    var variationsHtml = safeData.variations || safeData.variations_html || '';
    var addToCartHtml = safeData.add_to_cart || safeData.add_to_cart_html || '';
    var imageSrc = safeData.image || '';
    var imageAlt = safeData.image_alt || title || '';

    if (elements.$title && elements.$title.length) {
      elements.$title.html(title);
    }

    if (elements.$description && elements.$description.length) {
      elements.$description.html(description);
      toggleQuickViewSection(elements.$description, !!description);
    }

    if (elements.$variations && elements.$variations.length) {
      elements.$variations.html(variationsHtml);
      toggleQuickViewSection(elements.$variations, !!variationsHtml);
      if (variationsHtml) {
        initializeVariationForms(elements.$variations);
      }
    }

    if (elements.$price && elements.$price.length) {
      elements.$price.html(priceHtml);
      toggleQuickViewSection(elements.$price, !!priceHtml);
    }

    if (elements.$addToCart && elements.$addToCart.length) {
      elements.$addToCart.html(addToCartHtml);
      toggleQuickViewSection(elements.$addToCart, !!addToCartHtml);
    }

    if (elements.$image && elements.$image.length) {
      if (imageSrc) {
        elements.$image.attr('src', imageSrc);
      }
      elements.$image.attr('alt', imageAlt);
    }

    if (elements.$viewProduct && elements.$viewProduct.length && safeData.permalink) {
      elements.$viewProduct.attr('href', safeData.permalink);
    }

    if (elements.$message && elements.$message.length) {
      elements.$message.empty().removeClass('is-visible').attr('aria-hidden', 'true');
    }
  };

  var quickViewState = {
    request: null,
    trigger: null,
    elements: null,
  };

  function openQuickView(productId, $context, $trigger) {
    var $overlay = $();

    if ($context && $context.length) {
      $overlay = $context.find('.bw-quickview-overlay').first();
    }

    if (!$overlay.length) {
      $overlay = $('.bw-quickview-overlay').first();
    }

    if (!$overlay.length) {
      return;
    }

    var elements = collectQuickViewElements($overlay);

    if (!elements) {
      return;
    }

    if (quickViewState.request && typeof quickViewState.request.abort === 'function') {
      quickViewState.request.abort();
    }

    quickViewState.request = null;
    quickViewState.trigger = $trigger || null;
    quickViewState.elements = elements;

    resetQuickViewContent(elements);

    elements.$overlay.attr('aria-hidden', 'false').addClass('active');
    elements.$backdrop.addClass('visible');
    elements.$dialog
      .removeClass('loaded show-content')
      .addClass('opening loading');

    $('body').addClass('bw-quickview-open');

    elements.$dialog.attr('tabindex', '-1').focus();

    var request = fetchQuickViewData(productId);
    quickViewState.request = request;

    request
      .done(function (response) {
        if (!response || !response.success) {
          var errorMessage =
            (response && response.data && response.data.message) ||
            getLocalizedString('error', 'Unable to load product.');

          showQuickViewMessage(elements, errorMessage);
        } else {
          populateQuickViewContent(elements, response.data || {});
        }

        elements.$dialog.removeClass('loading').addClass('loaded');

        setTimeout(function () {
          elements.$dialog.addClass('show-content');
        }, 150);
      })
      .fail(function (jqXHR) {
        var errorMessage = getLocalizedString('error', 'Unable to load product.');

        if (
          jqXHR &&
          jqXHR.responseJSON &&
          jqXHR.responseJSON.data &&
          jqXHR.responseJSON.data.message
        ) {
          errorMessage = jqXHR.responseJSON.data.message;
        }

        showQuickViewMessage(elements, errorMessage);

        elements.$dialog.removeClass('loading').addClass('loaded');

        setTimeout(function () {
          elements.$dialog.addClass('show-content');
        }, 150);
      })
      .always(function () {
        if (quickViewState.request === request) {
          quickViewState.request = null;
        }
      });
  }

  function closeQuickView(event) {
    if (event && typeof event.preventDefault === 'function') {
      event.preventDefault();
    }

    var elements = quickViewState.elements;

    if (event && event.currentTarget) {
      var $targetOverlay = $(event.currentTarget).closest('.bw-quickview-overlay');
      if ($targetOverlay.length) {
        elements = collectQuickViewElements($targetOverlay);
      }
    }

    if (!elements) {
      return;
    }

    if (quickViewState.request && typeof quickViewState.request.abort === 'function') {
      quickViewState.request.abort();
    }

    quickViewState.request = null;

    if (elements.$backdrop && elements.$backdrop.length) {
      elements.$backdrop.removeClass('visible');
    }

    elements.$overlay.removeClass('active').attr('aria-hidden', 'true');

    if (elements.$dialog && elements.$dialog.length) {
      elements.$dialog.removeClass('opening loading loaded show-content');
    }

    $('body').removeClass('bw-quickview-open');

    resetQuickViewContent(elements);

    if (
      quickViewState.elements &&
      elements.$overlay &&
      quickViewState.elements.$overlay &&
      elements.$overlay.get(0) === quickViewState.elements.$overlay.get(0)
    ) {
      var $lastTrigger = quickViewState.trigger;
      if ($lastTrigger && $lastTrigger.length) {
        $lastTrigger.focus();
      }

      quickViewState.trigger = null;
      quickViewState.elements = null;
    }
  }

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
    $(document)
      .off('click.bwQuickView')
      .on('click.bwQuickView', '.bw-qv-btn', function (event) {
        event.preventDefault();

        var $trigger = $(this);
        var productId = parseInt($trigger.attr('data-product-id'), 10);

        if (!productId) {
          return;
        }

        var $widget = $trigger.closest('.elementor-widget-bw-slick-slider');
        openQuickView(productId, $widget, $trigger);
      });

    $(document)
      .off('click.bwQuickViewClose')
      .on('click.bwQuickViewClose', '.bw-qv-close, .bw-quickview-backdrop', function (event) {
        closeQuickView(event);
      });

    $(document)
      .off('keyup.bwQuickView')
      .on('keyup.bwQuickView', function (event) {
        if (event.key === 'Escape') {
          closeQuickView();
        }
      });
  };

  $(function () {
    initQuickView();
  });

  $(window).on('elementor/frontend/init', function () {
    if (
      typeof elementorFrontend === 'undefined' ||
      !elementorFrontend.hooks ||
      typeof elementorFrontend.hooks.addAction !== 'function'
    ) {
      return;
    }

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/global',
      function () {
        initQuickView();
      }
    );

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/bw-slick-slider.default',
      function ($scope) {
        initSlickSlider($scope);
        initQuickView();
      }
    );
  });
})(jQuery);
