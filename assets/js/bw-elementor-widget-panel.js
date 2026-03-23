(function ($) {
  'use strict';

  var CARD_CLASSES = [
    'bw-editor-widget-card',
    'bw-family-ui',
    'bw-family-sp',
    'bw-family-deprecated'
  ];

  var TITLE_SELECTORS = [
    '.title',
    '.elementor-element-title',
    '.widget-title'
  ];

  var PANEL_SELECTORS = [
    '.elementor-panel .elementor-element',
    '.elementor-panel-category-items .elementor-element'
  ];
  var REMOVED_WIDGET_SLUGS = [
    'bw-add-to-cart',
    'bw-add-to-cart-variation',
    'bw-wallpost'
  ];
  var REMOVED_WIDGET_TITLES = [
    'DEPRECATED - BW Add to Cart',
    'DEPRECATED - BW Add To Cart Variation',
    'DEPRECATED - BW WallPost'
  ];
  var WOO_WIDGET_SLUGS = [
    'bw-title-product',
    'bw-reviews'
  ];
  var UI_WIDGET_SLUGS = [
    'bw-product-grid'
  ];
  var panelObserver = null;
  var observerTick = null;

  function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function getTitle($card) {
    var title = '';

    TITLE_SELECTORS.some(function (selector) {
      var $title = $card.find(selector).first();
      if ($title.length) {
        title = normalizeText($title.text());
        return title.length > 0;
      }
      return false;
    });

    return title;
  }

  function getWidgetType($card) {
    return normalizeText(
      $card.attr('data-widget_type') ||
      $card.data('widget_type') ||
      $card.attr('data-element_type') ||
      $card.data('element_type')
    );
  }

  function getWidgetSlug(widgetType) {
    var normalized = normalizeText(widgetType).toLowerCase();
    if (!normalized) {
      return '';
    }

    return normalized.split('.')[0];
  }

  function isRemovedWidgetCard(title, widgetType) {
    var slug = getWidgetSlug(widgetType);

    if (slug && REMOVED_WIDGET_SLUGS.indexOf(slug) !== -1) {
      return true;
    }

    return REMOVED_WIDGET_TITLES.indexOf(title) !== -1;
  }

  function getFamilyClass(title, widgetType) {
    var slug = getWidgetSlug(widgetType);

    if (slug && UI_WIDGET_SLUGS.indexOf(slug) !== -1) {
      return 'bw-family-ui';
    }

    if (slug && WOO_WIDGET_SLUGS.indexOf(slug) !== -1) {
      return 'bw-family-sp';
    }

    if (title.indexOf('DEPRECATED -') === 0) {
      return 'bw-family-deprecated';
    }

    if (title.indexOf('BW-UI ') === 0) {
      return 'bw-family-ui';
    }

    if (title.indexOf('BW-SP ') === 0) {
      return 'bw-family-sp';
    }

    return '';
  }

  function isBlackworkWidget($card, title, widgetType) {
    if (widgetType.indexOf('bw-') === 0 || widgetType.indexOf('bw_') === 0) {
      return true;
    }

    return title.indexOf('BW-UI ') === 0 ||
      title.indexOf('BW-SP ') === 0 ||
      title.indexOf('DEPRECATED -') === 0;
  }

  function styleWidgetCards($root) {
    var $scope = $root && $root.length ? $root : $(document);
    var selector = PANEL_SELECTORS.join(', ');

    $scope.find(selector).each(function () {
      var $card = $(this);
      var title = getTitle($card);
      var widgetType = getWidgetType($card);

      $card.removeClass(CARD_CLASSES.join(' '));
      $card.removeClass('bw-removed-widget-card').removeAttr('aria-hidden').css('display', '');

      if (isRemovedWidgetCard(title, widgetType)) {
        $card.addClass('bw-removed-widget-card').attr('aria-hidden', 'true').css('display', 'none');
        return;
      }

      if (!isBlackworkWidget($card, title, widgetType)) {
        return;
      }

      var familyClass = getFamilyClass(title, widgetType);
      if (!familyClass) {
        return;
      }

      $card.addClass('bw-editor-widget-card').addClass(familyClass);
    });
  }

  function schedulePanelScan() {
    styleWidgetCards($(document));
    setTimeout(function () { styleWidgetCards($(document)); }, 150);
    setTimeout(function () { styleWidgetCards($(document)); }, 500);
  }

  function connectPanelObserver() {
    if (panelObserver || typeof MutationObserver === 'undefined') {
      return;
    }

    var panelRoot = document.querySelector('.elementor-panel');
    if (!panelRoot) {
      return;
    }

    panelObserver = new MutationObserver(function () {
      schedulePanelScan();
    });

    panelObserver.observe(panelRoot, {
      childList: true,
      subtree: true
    });
  }

  function bootPanelObserver() {
    connectPanelObserver();

    if (panelObserver || observerTick) {
      return;
    }

    observerTick = setInterval(function () {
      connectPanelObserver();
      if (panelObserver) {
        clearInterval(observerTick);
        observerTick = null;
      }
    }, 250);

    setTimeout(function () {
      if (!panelObserver && observerTick) {
        clearInterval(observerTick);
        observerTick = null;
      }
    }, 10000);
  }

  $(document).ready(schedulePanelScan);
  $(window).on('elementor:init', schedulePanelScan);
  $(window).on('elementor/frontend/init', schedulePanelScan);
  $(document).on('keyup click', '.elementor-panel', schedulePanelScan);
  $(document).ready(bootPanelObserver);
  $(window).on('elementor:init elementor/frontend/init', bootPanelObserver);
})(jQuery);
