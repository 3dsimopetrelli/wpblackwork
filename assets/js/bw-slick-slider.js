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

  var quickViewActiveInstance = null;

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
        action: 'bw_get_quick_view',
        nonce: window.bwSlickSlider.quickViewNonce || '',
        product_id: productId,
      },
    });
  };

  var QuickView = function ($widget) {
    this.$widget = $widget;
    this.$overlay = $widget.find('[data-product-quick-view]');
    this.$dialog = this.$overlay.find('.bw-quickview');
    this.$imageWrap = this.$overlay.find('.bw-quickview-image');
    this.$image = this.$imageWrap.find('img');
    this.$loader = this.$overlay.find('.bw-loader');
    this.$title = this.$overlay.find('.bw-qv-title');
    this.$description = this.$overlay.find('.bw-qv-description');
    this.$variations = this.$overlay.find('.bw-qv-variations');
    this.$price = this.$overlay.find('.bw-qv-price');
    this.$addToCart = this.$overlay.find('.bw-qv-addtocart');
    this.$message = this.$overlay.find('.bw-qv-message');
    this.currentRequest = null;
    this.isOpen = false;
    this.isPreparing = false;
    this.lastTrigger = null;

    this.bindEvents();
  };

  QuickView.prototype.bindEvents = function () {
    var self = this;

    this.$widget.on('click', '.overlay-button--quick', function (event) {
      event.preventDefault();

      var $trigger = $(event.currentTarget);
      var productId = parseInt($trigger.attr('data-product-id'), 10);

      if (!productId || self.isPreparing) {
        return;
      }

      self.open(productId, $trigger);
    });

    this.$overlay.on('click', '[data-quickview-close]', function (event) {
      event.preventDefault();
      self.close();
    });

    this.$overlay.on('click', function (event) {
      if (event.target === self.$overlay.get(0)) {
        self.close();
      }
    });
  };

  QuickView.prototype.animateImage = function ($originImage) {
    var self = this;

    return new Promise(function (resolve) {
      if (!$originImage || !$originImage.length || !$originImage.get(0)) {
        resolve();
        return;
      }

      var originRect = $originImage.get(0).getBoundingClientRect();

      if (!originRect.width || !originRect.height) {
        resolve();
        return;
      }

      var $clone = $originImage.clone().addClass('bw-quickview-fly-image');
      var computed = window.getComputedStyle($originImage.get(0));

      $clone.css({
        position: 'fixed',
        top: originRect.top + 'px',
        left: originRect.left + 'px',
        width: originRect.width + 'px',
        height: originRect.height + 'px',
        margin: 0,
        zIndex: 99999,
        transition:
          'transform 0.55s cubic-bezier(0.23, 1, 0.32, 1), top 0.55s ease, left 0.55s ease, width 0.55s ease, height 0.55s ease',
        borderRadius: computed.borderRadius || '0px',
        objectFit: computed.objectFit || 'cover',
      });

      $('body').append($clone);

      requestAnimationFrame(function () {
        var targetRect = self.$imageWrap.get(0).getBoundingClientRect();
        var scaleX = targetRect.width / Math.max(originRect.width, 1);
        var scaleY = targetRect.height / Math.max(originRect.height, 1);
        var translateX = targetRect.left - originRect.left;
        var translateY = targetRect.top - originRect.top;

        $clone.css({
          transform:
            'translate3d(' +
            translateX +
            'px,' +
            translateY +
            'px,0) scale(' +
            scaleX +
            ',' +
            scaleY +
            ')',
        });
      });

      $clone.one('transitionend', function () {
        $clone.remove();
        resolve();
      });
    });
  };

  QuickView.prototype.resetContent = function () {
    this.$title.text('');
    this.$description.empty();
    this.$variations.empty();
    this.$price.empty();
    this.$addToCart.empty();
    this.$message.empty().removeClass('is-visible');
  };

  QuickView.prototype.toggleSection = function ($element, hasContent) {
    if (!$element || !$element.length) {
      return;
    }

    if (hasContent) {
      $element.removeClass('is-empty').attr('aria-hidden', 'false');
    } else {
      $element.addClass('is-empty').attr('aria-hidden', 'true');
    }
  };

  QuickView.prototype.showMessage = function (message) {
    var text = message || getLocalizedString('error', 'Unable to load product.');

    if (this.$message && this.$message.length) {
      this.$message.text(text).addClass('is-visible');
    }
  };

  QuickView.prototype.updateContent = function (data) {
    var title = data && data.title ? data.title : '';
    var description = data && data.description ? data.description : '';
    var priceHtml = data && data.price_html ? data.price_html : '';
    var variationsHtml = data && data.variations_html ? data.variations_html : '';
    var addToCartHtml = data && data.add_to_cart_html ? data.add_to_cart_html : '';
    var imageSrc = data && data.image ? data.image : '';
    var imageAlt = data && data.image_alt ? data.image_alt : '';

    this.$title.text(title);
    this.$description.html(description);
    this.$variations.html(variationsHtml);
    this.$price.html(priceHtml);
    this.$addToCart.html(addToCartHtml);

    if (variationsHtml && this.$variations.length && $.fn.wc_variation_form) {
      this.$variations.find('.variations_form').each(function () {
        var $form = $(this);
        if (typeof $form.wc_variation_form === 'function') {
          $form.wc_variation_form();
        }
        $form.trigger('wc_variation_form');
      });
    }

    this.toggleSection(this.$description, !!description);
    this.toggleSection(this.$variations, !!variationsHtml);
    this.toggleSection(this.$price, !!priceHtml);
    this.toggleSection(this.$addToCart, !!addToCartHtml);

    if (imageSrc) {
      this.$image.attr('src', imageSrc);
    }

    this.$image.attr('alt', imageAlt || title || '');
  };

  QuickView.prototype.setLoadingState = function (isLoading) {
    if (isLoading) {
      this.$overlay.addClass('loading').removeClass('loaded show-content has-error');
    } else {
      this.$overlay.removeClass('loading');
    }
  };

  QuickView.prototype.open = function (productId, $trigger) {
    var self = this;
    var $originImage = null;

    if ($trigger && $trigger.length) {
      $originImage = $trigger
        .closest('.bw-ss__card')
        .find('.bw-ss__media img')
        .first();
    }

    this.resetContent();
    this.isPreparing = true;
    this.isOpen = true;
    this.lastTrigger = $trigger || null;
    quickViewActiveInstance = this;

    this.$overlay.attr('aria-hidden', 'false');
    this.$overlay.addClass('is-active loading is-preparing');
    $('body').addClass('bw-quickview-open');

    if ($originImage && $originImage.length) {
      var previewSrc = $originImage.attr('src') || $originImage.attr('data-src') || '';
      var previewAlt = $originImage.attr('alt') || '';
      if (previewSrc) {
        this.$image.attr('src', previewSrc);
      }
      this.$image.attr('alt', previewAlt);
    }

    this.animateImage($originImage).then(function () {
      self.$overlay.removeClass('is-preparing').addClass('opening');
      self.$dialog.focus();
    });

    this.setLoadingState(true);

    this.fetchData(productId)
      .then(function (data) {
        if (!self.isOpen) {
          return;
        }
        self.updateContent(data || {});
        self.$overlay.addClass('loaded');
        setTimeout(function () {
          self.$overlay.addClass('show-content');
        }, 40);
      })
      .catch(function (error) {
        if (!self.isOpen) {
          return;
        }
        var message = error && error.message ? error.message : null;
        self.$overlay.addClass('has-error loaded show-content');
        self.showMessage(message);
      })
      .then(function () {
        if (!self.isOpen) {
          return;
        }
        self.setLoadingState(false);
        self.isPreparing = false;
      });
  };

  QuickView.prototype.fetchData = function (productId) {
    var self = this;

    if (this.currentRequest && typeof this.currentRequest.abort === 'function') {
      this.currentRequest.abort();
    }

    return new Promise(function (resolve, reject) {
      self.currentRequest = fetchQuickViewData(productId)
        .done(function (response) {
          if (response && response.success) {
            resolve(response.data || {});
          } else {
            var message = getLocalizedString('error', 'Unable to load product.');
            if (response && response.data && response.data.message) {
              message = response.data.message;
            }
            reject({ message: message });
          }
        })
        .fail(function (jqXHR) {
          var errorMessage = getLocalizedString('error', 'Unable to load product.');

          if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data) {
            if (jqXHR.responseJSON.data.message) {
              errorMessage = jqXHR.responseJSON.data.message;
            }
          }

          reject({ message: errorMessage });
        })
        .always(function () {
          self.currentRequest = null;
        });
    });
  };

  QuickView.prototype.close = function () {
    if (!this.isOpen) {
      return;
    }

    var $lastTrigger = this.lastTrigger;

    if (this.currentRequest && typeof this.currentRequest.abort === 'function') {
      this.currentRequest.abort();
      this.currentRequest = null;
    }

    this.isOpen = false;
    this.isPreparing = false;
    this.lastTrigger = null;

    this.$overlay
      .removeClass('is-active opening loaded show-content loading has-error is-preparing')
      .attr('aria-hidden', 'true');

    $('body').removeClass('bw-quickview-open');

    if ($lastTrigger && $lastTrigger.length) {
      $lastTrigger.focus();
    }

    if (quickViewActiveInstance === this) {
      quickViewActiveInstance = null;
    }
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

  var initQuickView = function ($scope) {
    var $widget = $scope.closest('.elementor-widget-bw-slick-slider');

    if (!$widget.length) {
      $widget = $scope;
    }

    if ($widget.data('bwQuickViewInit')) {
      return;
    }

    if (!$widget.find('[data-product-quick-view]').length) {
      return;
    }

    $widget.data('bwQuickViewInit', true);
    var instance = new QuickView($widget);
    $widget.data('bwQuickViewInstance', instance);
  };

  $(document).on('keyup', function (event) {
    if (event.key === 'Escape' && quickViewActiveInstance) {
      quickViewActiveInstance.close();
    }
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
      'frontend/element_ready/bw-slick-slider.default',
      function ($scope) {
        initSlickSlider($scope);
        initQuickView($scope);
      }
    );
  });
})(jQuery);
