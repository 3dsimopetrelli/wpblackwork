(function ($) {
  'use strict';

  /**
   * BW Product Slide - Slider Initialization & Animation Handler
   *
   * ARCHITECTURE NOTE:
   * This widget loads TWO slider scripts:
   * 1. bw-slick-slider.js (shared, 700 lines) - Used by other widgets
   * 2. bw-product-slide.js (this file, 1050+ lines) - Product Slide specific
   *
   * For Product Slide, ONLY this file's logic is active:
   * - initProductSlide() → Full custom initialization
   * - Popup logic, image fade handling
   * - Responsive dimension updates
   * - Arrow/dot/counter visibility control
   *
   * The shared slider script (bw-slick-slider.js) remains loaded for:
   * - Backward compatibility with other widgets
   * - Shared Slick settings parsing utilities
   * - Future consolidation (planned refactor)
   *
   * RECENT FIXES (2025-12-13):
   * - ✅ Removed excessive setPosition() calls (was causing stutter)
   * - ✅ Added waitForAnimate: false (smooth rapid navigation)
   * - ✅ Consolidated editor change handlers (no more double-refresh)
   * - ✅ Made setPosition() conditional (only when dimensions change)
   *
   * See: docs/2025-12-13-product-slide-vacuum-cleanup-report.md
   */

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
    var $body = $('body');
    var popupId = $container.attr('data-popup-id');

    if (!popupId) {
      popupId = 'bw-product-slide-popup-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
      $container.attr('data-popup-id', popupId);
    }

    var $popup = $container.find('.bw-product-slide-popup');

    if (!$popup.length) {
      $popup = $body.children('#' + popupId);
    }

    if (!$popup.length) {
      return;
    }

    if (!$popup.attr('id')) {
      $popup.attr('id', popupId);
    }

    var $popupInBody = $body.children('#' + popupId);

    if ($popupInBody.length && !$popupInBody.is($popup)) {
      $popup.remove();
      $popup = $popupInBody;
    }

    if (!$popup.parent().is($body)) {
      $popup.detach();
      $body.append($popup);
    }

    var $popupTitle = $popup.find('.bw-popup-title');
    var $popupContent = $popup.find('.bw-popup-content');

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

    var openPopup = function (imageIndex) {
      clearHideTimeout();
      $popup.off('transitionend.bwProductSlidePopup', onPopupTransitionEnd);

      if ($popup.attr('hidden')) {
        $popup.removeAttr('hidden');
      }

      $popup.attr('aria-hidden', 'false');

      // Reset scroll position prima di aprire il popup
      var popupElement = $popup.get(0);
      if (popupElement) {
        popupElement.scrollTop = 0;
      }

      window.requestAnimationFrame(function () {
        $popup.addClass('active');
        $body.addClass('popup-active');

        // Scroll to the specific image if imageIndex is provided
        if (typeof imageIndex === 'number' && imageIndex >= 0) {
          var $images = $popupContent.find('img');
          if ($images.length > imageIndex) {
            var $targetImage = $images.eq(imageIndex);
            if ($targetImage.length) {
              // Use setTimeout to ensure the popup is fully visible before scrolling
              setTimeout(function () {
                if (popupElement) {
                  // Reimposta nuovamente a 0 per sicurezza prima di calcolare la nuova posizione
                  popupElement.scrollTop = 0;

                  // Calcola l'offset dell'immagine target rispetto al contenuto del popup
                  var imageOffset = $targetImage.offset().top;
                  var popupOffset = $popup.offset().top;
                  var scrollTop = imageOffset - popupOffset;

                  // Imposta la posizione di scroll
                  popupElement.scrollTop = scrollTop;
                }
              }, 50);
            }
          }
        }
      });
    };

    var closePopup = function () {
      $popup.removeClass('active');
      $body.removeClass('popup-active');

      // Reset scroll position quando si chiude il popup
      var popupElement = $popup.get(0);
      if (popupElement) {
        popupElement.scrollTop = 0;
      }

      $popup
        .off('transitionend.bwProductSlidePopup', onPopupTransitionEnd)
        .on('transitionend.bwProductSlidePopup', onPopupTransitionEnd);

      hideTimeoutId = window.setTimeout(function () {
        ensurePopupHidden();
        $popup.off('transitionend.bwProductSlidePopup', onPopupTransitionEnd);
      }, 600);
    };

    // Check if popup should open on image click
    var popupOpenOnClick = String($container.attr('data-popup-open-on-click')) === 'true';

    if (popupOpenOnClick) {
      // Use event delegation to handle clicks on all images (including cloned slides)
      $container
        .off('click.bwProductSlide', '.bw-product-slide-item img')
        .on('click.bwProductSlide', '.bw-product-slide-item img', function () {
          var title = $(this).attr('alt') || '';

          // Find the closest .slick-slide element to get the correct index
          var $slickSlide = $(this).closest('.slick-slide');
          var imageIndex = 0;

          if ($slickSlide.length && $slickSlide.attr('data-slick-index')) {
            // Use data-slick-index added by Slick (handles cloned slides correctly)
            var slickIndex = parseInt($slickSlide.attr('data-slick-index'), 10);

            // Get the slider element to check total slides
            var $slider = $container.find('.bw-product-slide-wrapper');
            if ($slider.length && $slider.hasClass('slick-initialized')) {
              var slideCount = $slider.slick('getSlick').slideCount;

              // Normalize the index for cloned slides (handles negative indices)
              imageIndex = ((slickIndex % slideCount) + slideCount) % slideCount;
            } else {
              imageIndex = slickIndex >= 0 ? slickIndex : 0;
            }
          } else {
            // Fallback to data-index if slick-slide is not found (shouldn't happen)
            var $slideItem = $(this).closest('.bw-product-slide-item');
            var slideIndex = parseInt($slideItem.attr('data-index'), 10) || 1;
            imageIndex = slideIndex - 1; // Convert to 0-based index
          }

          $popupTitle.text(title);
          openPopup(imageIndex);
        });
    } else {
      // Remove click handlers if popup should not open on click
      $container.off('click.bwProductSlide', '.bw-product-slide-item img');
    }

    $popup
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

  var getSliderImageHeight = function (sliderElement) {
    if (!sliderElement || typeof window.getComputedStyle !== 'function') {
      return '';
    }

    var computedStyle = window.getComputedStyle(sliderElement);
    if (!computedStyle) {
      return '';
    }

    var rawValue = computedStyle.getPropertyValue('--bw-product-slide-image-height');

    if (!rawValue) {
      return '';
    }

    var normalized = rawValue.trim();

    return normalized === 'auto' ? '' : normalized;
  };

  var refreshSliderImages = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    var $images = $slider.find('.bw-product-slide-item img');

    if (!$images.length) {
      return;
    }

    var sliderElement = $slider.get(0);
    var imageHeightValue = getSliderImageHeight(sliderElement);
    var cropEnabled = String($slider.attr('data-image-crop')) === 'true';

    $images.each(function () {
      var $image = $(this);
      var cssProperties = {
        width: '100%',
        'max-width': '100%',
      };

      if (cropEnabled) {
        cssProperties.height = imageHeightValue || '';
        cssProperties['max-height'] = 'none';
      } else {
        cssProperties.height = '';
        cssProperties['max-height'] = imageHeightValue || '';
      }

      $image.css(cssProperties);
    });

    // ✅ FIX: Removed setPosition() call - images can update via CSS without full re-layout
    // This prevents layout thrashing when images refresh during navigation
  };

  var applyResponsiveDimensions = function ($slider, settings) {
    if (!$slider || !$slider.length || !settings) {
      return;
    }

    if (!settings.responsive || !Array.isArray(settings.responsive)) {
      return;
    }

    var windowWidth = $(window).width();
    var sortedBreakpoints = settings.responsive
      .slice()
      .sort(function (a, b) {
        return a.breakpoint - b.breakpoint;
      });

    var widthToApply = null;
    var heightToApply = null;
    var gapToApply = null;
    var breakpointFound = false;

    for (var i = 0; i < sortedBreakpoints.length; i++) {
      var bp = sortedBreakpoints[i];
      if (windowWidth <= bp.breakpoint) {
        breakpointFound = true;
        if (bp.settings && bp.settings.responsiveWidth) {
          widthToApply = bp.settings.responsiveWidth;
        }
        if (bp.settings && bp.settings.responsiveHeight) {
          heightToApply = bp.settings.responsiveHeight;
        }
        if (bp.settings && bp.settings.responsiveGap) {
          gapToApply = bp.settings.responsiveGap;
        }
        break;
      }
    }

    // Get original inline style values once for fallback (only when NO breakpoint matches)
    var originalStyle = $slider.data('bwOriginalStyle');
    if (!originalStyle) {
      originalStyle = {
        width: $slider.get(0).style.getPropertyValue('--bw-product-slide-column-width') || '',
        height: $slider.get(0).style.getPropertyValue('--bw-product-slide-image-height') || '',
        gap: $slider.get(0).style.getPropertyValue('--bw-product-slide-gap') || ''
      };
      $slider.data('bwOriginalStyle', originalStyle);
    }

    // Se non è stato trovato nessun breakpoint applicabile, resetta ai valori inline originali
    if (!breakpointFound && sortedBreakpoints.length > 0) {
      // Restore original values
      if (originalStyle.width) {
        $slider.css({
          '--bw-product-slide-column-width': originalStyle.width,
          '--bw-column-width': originalStyle.width,
          '--bw-slide-width': originalStyle.width
        });
      }
      if (originalStyle.height) {
        $slider.css('--bw-product-slide-image-height', originalStyle.height);
      }
      if (originalStyle.gap) {
        $slider.css('--bw-product-slide-gap', originalStyle.gap);
      }
    } else if (breakpointFound) {
      // Only apply values if the breakpoint explicitly provides them
      // If breakpoint doesn't provide a value, clear the CSS variable to let Slick handle it naturally

      if (widthToApply && widthToApply.size !== null && widthToApply.size !== '') {
        var widthValue = widthToApply.size + widthToApply.unit;
        $slider.css({
          '--bw-product-slide-column-width': widthValue,
          '--bw-column-width': widthValue,
          '--bw-slide-width': widthValue
        });
      } else {
        // Breakpoint found but no custom width - clear the variable to use auto/natural sizing
        $slider.css({
          '--bw-product-slide-column-width': '',
          '--bw-column-width': '',
          '--bw-slide-width': ''
        });
      }

      if (heightToApply && heightToApply.size !== null && heightToApply.size !== '') {
        var heightValue = heightToApply.size + heightToApply.unit;
        $slider.css('--bw-product-slide-image-height', heightValue);
      } else {
        // Breakpoint found but no custom height - clear to use auto
        $slider.css('--bw-product-slide-image-height', '');
      }

      if (gapToApply && gapToApply.size !== null && gapToApply.size !== '') {
        var gapValue = gapToApply.size + gapToApply.unit;
        $slider.css('--bw-product-slide-gap', gapValue);
      } else {
        // Breakpoint found but no custom gap - clear to use default
        $slider.css('--bw-product-slide-gap', '');
      }
    }

    // Aggiorna le dimensioni delle immagini dopo aver applicato le nuove variabili CSS
    refreshSliderImages($slider);

    // ✅ FIX: Only call setPosition() if dimensions actually changed
    // This prevents unnecessary layout recalculations during navigation
    var dimensionsChanged = breakpointFound && (widthToApply || heightToApply || gapToApply);

    if (dimensionsChanged && $slider.hasClass('slick-initialized')) {
      setTimeout(function () {
        if ($slider.hasClass('slick-initialized')) {
          try {
            var slickInstance = $slider.slick('getSlick');
            if (slickInstance && typeof slickInstance.setPosition === 'function') {
              slickInstance.setPosition();
            }
          } catch (e) {
            // Slick not fully initialized yet, skip setPosition
          }
        }
      }, 50);
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

    var previousObserver = $slider.data('bwMutationObserver');
    if (previousObserver && typeof previousObserver.disconnect === 'function') {
      previousObserver.disconnect();
      $slider.removeData('bwMutationObserver');
    }

    if (previousResizeEvent || previousEditorHandler) {
      $slider.removeData('bwResponsiveBound');
    }
  };

  var bindResponsiveUpdates = function ($slider, settings) {
    if (!$slider || !$slider.length) {
      return;
    }

    unbindResponsiveUpdates($slider);

    var resizeEvent = 'resize.bwProductSlide-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    var resizeTimeout = null;
    var isRefreshing = false;

    var refreshImages = function () {
      refreshSliderImages($slider);
    };

    var applyDimensions = function () {
      applyResponsiveDimensions($slider, settings);
    };

    var refreshAll = function () {
      if (isRefreshing) {
        return; // Prevent concurrent refreshes
      }
      isRefreshing = true;
      refreshImages();
      applyDimensions();
      setTimeout(function () {
        isRefreshing = false;
      }, 100);
    };

    $(window).on(resizeEvent, function () {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(refreshAll, 150);
    });
    $slider.data('bwResizeEvent', resizeEvent);

    if (
      window.elementorFrontend &&
      elementorFrontend.isEditMode() &&
      window.elementor &&
      elementor.channels &&
      elementor.channels.editor &&
      typeof elementor.channels.editor.on === 'function'
    ) {
      // ✅ FIX: Handler #1 - Only dimension changes (not control visibility)
      // Removed 'responsive' to prevent overlap with Handler #2
      var editorHandler = function (panel) {
        if (!panel || !panel.changed) {
          return;
        }

        var changedKeys = Object.keys(panel.changed);
        var shouldRefresh = changedKeys.some(function (key) {
          if (typeof key !== 'string') {
            return false;
          }

          // Only refresh for dimension-related changes
          return (
            key.indexOf('column_width') !== -1 ||
            key.indexOf('image_height') !== -1 ||
            key.indexOf('image_crop') !== -1 ||
            key.indexOf('gap') !== -1
            // ❌ REMOVED: key.indexOf('responsive') to prevent double-refresh
          );
        });

        if (shouldRefresh) {
          setTimeout(refreshAll, 200);
        }
      };

      elementor.channels.editor.on('change', editorHandler);
      $slider.data('bwEditorChangeHandler', editorHandler);
    }

    if (typeof window.MutationObserver === 'function') {
      var sliderElement = $slider.get(0);

      if (sliderElement) {
        var mutationTimeout = null;
        var observer = new MutationObserver(function (mutations) {
          var shouldRefresh = mutations.some(function (mutation) {
            if (!mutation || mutation.type !== 'attributes') {
              return false;
            }

            if (mutation.attributeName === 'style') {
              return true;
            }

            if (mutation.attributeName === 'data-image-crop') {
              return true;
            }

            return false;
          });

          if (shouldRefresh && !isRefreshing) {
            clearTimeout(mutationTimeout);
            mutationTimeout = setTimeout(function () {
              if (!isRefreshing) {
                refreshAll();
              }
            }, 200);
          }
        });

        observer.observe(sliderElement, {
          attributes: true,
          attributeFilter: ['style', 'data-image-crop'],
        });

        $slider.data('bwMutationObserver', observer);
      }
    }

    refreshAll();
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
      $slider.off('.bwProductSlideArrows');
      $slider.off('.bwProductSlideDots');
      $slider.off('.bwProductSlideCount');

      // Rimuovi event listener di resize
      $(window).off('resize.bwProductSlideArrows-' + $container.data('arrowsResizeEvent'));
      $(window).off('resize.bwProductSlideDots-' + $container.data('dotsResizeEvent'));
      $(window).off('resize.bwProductSlideCount-' + $container.data('slideCountResizeEvent'));

      // Rimuovi listener dell'editor per i controlli
      var previousControlsHandler = $slider.data('bwControlsEditorHandler');
      if (
        previousControlsHandler &&
        window.elementor &&
        elementor.channels &&
        elementor.channels.editor &&
        typeof elementor.channels.editor.off === 'function'
      ) {
        elementor.channels.editor.off('change', previousControlsHandler);
        $slider.removeData('bwControlsEditorHandler');
      }

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

      // ✅ FIX: Add waitForAnimate to allow smooth rapid navigation
      // Without this, clicking arrows rapidly feels sluggish/blocked
      settings.waitForAnimate = false;

      var hasCustomColumnWidth = $slider.is('[data-has-column-width]');

      if (hasCustomColumnWidth) {
        // Solo imposta variableWidth se centerMode non è attivo
        // perché centerMode + variableWidth possono causare problemi in Slick
        if (!settings.centerMode) {
          settings.variableWidth = true;
        }

        if (Array.isArray(settings.responsive)) {
          settings.responsive = settings.responsive.map(function (entry) {
            if (!entry || typeof entry !== 'object') {
              return entry;
            }

            var responsiveEntry = $.extend(true, {}, entry);

            if (!responsiveEntry.settings || typeof responsiveEntry.settings !== 'object') {
              responsiveEntry.settings = {};
            }

            // Solo imposta variableWidth se centerMode non è attivo per questo breakpoint
            var breakpointCenterMode = responsiveEntry.settings.centerMode !== undefined
              ? responsiveEntry.settings.centerMode
              : settings.centerMode;

            // Check if this breakpoint has custom responsive width
            var hasResponsiveWidth = responsiveEntry.settings.responsiveWidth &&
                                     responsiveEntry.settings.responsiveWidth.size !== null &&
                                     responsiveEntry.settings.responsiveWidth.size !== '';

            // Respect user's explicit variableWidth setting, only auto-enable if not set
            if (typeof responsiveEntry.settings.variableWidth === 'undefined') {
              // User hasn't explicitly set variableWidth for this breakpoint
              // Only enable if: (1) has custom width AND (2) centerMode is off
              if (hasResponsiveWidth && !breakpointCenterMode) {
                responsiveEntry.settings.variableWidth = true;
              } else {
                // No custom width OR centerMode is on → use fixed-width (false)
                responsiveEntry.settings.variableWidth = false;
              }
            }
            // If user explicitly set variableWidth, keep their setting

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

      $container.addClass('bw-slider-loading');

      $slider.removeClass('bw-slider-ready');

      var animationFadeEnabled = String($slider.attr('data-animation-fade')) === 'true';
      if (animationFadeEnabled) {
        $slider.addClass('bw-enable-fade');
      } else {
        $slider.removeClass('bw-enable-fade');
      }

      prepareSliderImages($slider);

      var onSliderReady = function () {
        window.requestAnimationFrame(function () {
          $container.removeClass('bw-slider-loading');
          $slider.addClass('bw-slider-ready');
          prepareSliderImages($slider);
        });
      };

      $slider
        .off('init.bwProductSlideReady reInit.bwProductSlideReady')
        .on('init.bwProductSlideReady reInit.bwProductSlideReady', onSliderReady);

      $slider.on('init.bwProductSlide reInit.bwProductSlide afterChange.bwProductSlide', updateCounter);

      // Applica dimensioni responsive all'inizializzazione
      $slider.on('init.bwProductSlide', function () {
        applyResponsiveDimensions($slider, settings);
      });

      // Applica dimensioni responsive quando cambia il breakpoint
      $slider.on('breakpoint.bwProductSlide', function () {
        applyResponsiveDimensions($slider, settings);
      });

      $slider.slick(settings);

      refreshSliderImages($slider);
      bindResponsiveUpdates($slider, settings);

      // Funzione per aggiornare la visibilità delle frecce in base al breakpoint
      var updateArrowsVisibility = function () {
        var showArrows = settings.arrows !== false;
        var windowWidth = $(window).width();

        // Controlla se c'è una configurazione responsive per le frecce
        if (Array.isArray(settings.responsive)) {
          // Ordina i breakpoint dal più piccolo al più grande
          var sortedBreakpoints = settings.responsive
            .slice()
            .sort(function (a, b) {
              return a.breakpoint - b.breakpoint;
            });

          // Trova il breakpoint più piccolo che è >= alla viewport (max-width logic)
          // Parti dal più grande e scendi verso il più piccolo
          var matchedBreakpoint = null;
          for (var i = sortedBreakpoints.length - 1; i >= 0; i--) {
            var bp = sortedBreakpoints[i];
            if (windowWidth <= bp.breakpoint) {
              matchedBreakpoint = bp;
            } else {
              break; // Smetti di cercare, i successivi sono più piccoli
            }
          }

          // Applica le impostazioni del breakpoint trovato
          if (matchedBreakpoint && matchedBreakpoint.settings && typeof matchedBreakpoint.settings.arrows !== 'undefined') {
            showArrows = matchedBreakpoint.settings.arrows !== false;
          }
        }

        // Mostra/nascondi le frecce
        if (showArrows) {
          $container.find('.bw-product-slide-arrows').show();
        } else {
          $container.find('.bw-product-slide-arrows').hide();
        }
      };

      // Applica la visibilità delle frecce all'inizializzazione e dopo init
      $slider.on('init.bwProductSlideArrowsInit', function () {
        setTimeout(updateArrowsVisibility, 50);
      });
      updateArrowsVisibility();

      // Aggiorna la visibilità delle frecce quando cambia il breakpoint
      $slider.on('breakpoint.bwProductSlideArrows', function () {
        setTimeout(updateArrowsVisibility, 50);
      });

      // Aggiorna la visibilità delle frecce al resize con debounce
      var arrowsResizeEventId = Date.now();
      var arrowsResizeTimeout = null;
      $container.data('arrowsResizeEvent', arrowsResizeEventId);
      $(window).on('resize.bwProductSlideArrows-' + arrowsResizeEventId, function () {
        clearTimeout(arrowsResizeTimeout);
        arrowsResizeTimeout = setTimeout(updateArrowsVisibility, 100);
      });

      // Funzione per aggiornare i dots in base al breakpoint
      var updateDotsVisibility = function () {
        var showDots = settings.dots !== false;
        var windowWidth = $(window).width();

        // Controlla se c'è una configurazione responsive per i dots
        if (Array.isArray(settings.responsive)) {
          // Ordina i breakpoint dal più piccolo al più grande
          var sortedBreakpoints = settings.responsive
            .slice()
            .sort(function (a, b) {
              return a.breakpoint - b.breakpoint;
            });

          // Trova il breakpoint più piccolo che è >= alla viewport (max-width logic)
          var matchedBreakpoint = null;
          for (var i = sortedBreakpoints.length - 1; i >= 0; i--) {
            var bp = sortedBreakpoints[i];
            if (windowWidth <= bp.breakpoint) {
              matchedBreakpoint = bp;
            } else {
              break;
            }
          }

          // Applica le impostazioni del breakpoint trovato
          if (matchedBreakpoint && matchedBreakpoint.settings && typeof matchedBreakpoint.settings.dots !== 'undefined') {
            showDots = matchedBreakpoint.settings.dots !== false;
          }
        }

        // Aggiorna l'opzione dots di Slick
        if ($slider.hasClass('slick-initialized')) {
          $slider.slick('slickSetOption', 'dots', showDots, true);
        }
      };

      // Applica i dots all'inizializzazione e dopo init
      $slider.on('init.bwProductSlideDotsInit', function () {
        setTimeout(updateDotsVisibility, 50);
      });
      updateDotsVisibility();

      // Aggiorna i dots quando cambia il breakpoint
      $slider.on('breakpoint.bwProductSlideDots', function () {
        setTimeout(updateDotsVisibility, 50);
      });

      // Aggiorna i dots al resize con debounce
      var dotsResizeEventId = Date.now();
      var dotsResizeTimeout = null;
      $container.data('dotsResizeEvent', dotsResizeEventId);
      $(window).on('resize.bwProductSlideDots-' + dotsResizeEventId, function () {
        clearTimeout(dotsResizeTimeout);
        dotsResizeTimeout = setTimeout(updateDotsVisibility, 100);
      });

      // Funzione per aggiornare la visibilità del contatore slide in base al breakpoint
      var updateSlideCountVisibility = function () {
        var showSlideCount = String($container.attr('data-show-slide-count')) === 'true';
        var windowWidth = $(window).width();

        // Controlla se c'è una configurazione responsive per showSlideCount
        if (Array.isArray(settings.responsive)) {
          // Ordina i breakpoint dal più piccolo al più grande
          var sortedBreakpoints = settings.responsive
            .slice()
            .sort(function (a, b) {
              return a.breakpoint - b.breakpoint;
            });

          // Trova il breakpoint più piccolo che è >= alla viewport (max-width logic)
          var matchedBreakpoint = null;
          for (var i = sortedBreakpoints.length - 1; i >= 0; i--) {
            var bp = sortedBreakpoints[i];
            if (windowWidth <= bp.breakpoint) {
              matchedBreakpoint = bp;
            } else {
              break;
            }
          }

          // Applica le impostazioni del breakpoint trovato
          if (matchedBreakpoint && matchedBreakpoint.settings && typeof matchedBreakpoint.settings.showSlideCount !== 'undefined') {
            showSlideCount = matchedBreakpoint.settings.showSlideCount;
          }
        }

        // Mostra/nascondi il contatore slide
        if (showSlideCount) {
          $container.find('.bw-product-slide-count').show();
        } else {
          $container.find('.bw-product-slide-count').hide();
        }
      };

      // Applica la visibilità del contatore all'inizializzazione e dopo init
      $slider.on('init.bwProductSlideCountInit', function () {
        setTimeout(updateSlideCountVisibility, 50);
      });
      updateSlideCountVisibility();

      // Aggiorna la visibilità del contatore quando cambia il breakpoint
      $slider.on('breakpoint.bwProductSlideCount', function () {
        setTimeout(updateSlideCountVisibility, 50);
      });

      // Aggiorna la visibilità del contatore al resize con debounce
      var slideCountResizeEventId = Date.now();
      var slideCountResizeTimeout = null;
      $container.data('slideCountResizeEvent', slideCountResizeEventId);
      $(window).on('resize.bwProductSlideCount-' + slideCountResizeEventId, function () {
        clearTimeout(slideCountResizeTimeout);
        slideCountResizeTimeout = setTimeout(updateSlideCountVisibility, 100);
      });

      // ✅ FIX: Handler #2 - Only control visibility changes (not dimensions)
      // More specific responsive key matching to avoid overlap with Handler #1
      if (
        window.elementorFrontend &&
        elementorFrontend.isEditMode() &&
        window.elementor &&
        elementor.channels &&
        elementor.channels.editor &&
        typeof elementor.channels.editor.on === 'function'
      ) {
        var controlsEditorHandler = function (panel) {
          if (!panel || !panel.changed) {
            return;
          }

          var changedKeys = Object.keys(panel.changed);
          var shouldUpdateControls = changedKeys.some(function (key) {
            if (typeof key !== 'string') {
              return false;
            }

            // Only watch control visibility settings (arrows, dots, counter)
            // NOT dimension settings (width, height, gap) - those are in Handler #1
            return (
              key.indexOf('responsive_arrows') !== -1 ||
              key.indexOf('responsive_dots') !== -1 ||
              key.indexOf('responsive_show_slide_count') !== -1 ||
              key.indexOf('arrows') !== -1 ||
              key.indexOf('dots') !== -1 ||
              key.indexOf('show_slide_count') !== -1
            );
          });

          if (shouldUpdateControls) {
            // Forza re-render completo del widget in editor mode
            setTimeout(function () {
              // Trova l'elemento widget di Elementor
              var $widget = $container.closest('.elementor-element');
              if ($widget.length && window.elementor) {
                var widgetId = $widget.data('id');
                if (widgetId) {
                  // Forza refresh del widget
                  elementor.channels.editor.trigger('refresh:preview', {
                    force: true
                  });
                }
              }
            }, 100);
          }
        };

        elementor.channels.editor.on('change', controlsEditorHandler);
        $slider.data('bwControlsEditorHandler', controlsEditorHandler);
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
