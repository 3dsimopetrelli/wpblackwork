(function ($) {
  'use strict';

  var CARD_CLASSES = [
    'bw-editor-widget-card',
    'bw-family-ui',
    'bw-family-ui-ps',
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
  // Title-to-family map: keyed on exact widget title (always available in DOM).
  // Use this for widgets without a BW-UI/BW-SP prefix, and for title-based overrides.
  // NOTE: Elementor panel cards do NOT expose data-widget_type as an HTML attribute,
  // so slug-based detection is unreliable here — title is the only safe key.
  var TITLE_FAMILY_MAP = {
    'BW Title Product':          'bw-family-sp',
    'BW Reviews':                'bw-family-sp',
    'BW Trust Box':              'bw-family-sp',
    'Go to App':                 'bw-family-ui',
    'Newsletter Subscription':   'bw-family-ui',
    'BW Product Grid':           'bw-family-ui',
    'BW-UI Presentation Slider': 'bw-family-ui-ps'
  };
  var panelObserver = null;
  var observerTick = null;
  var PRODUCT_GRID_FILTER_GROUPS_BY_CONTEXT =
    (window.bwElementorWidgetPanelData && window.bwElementorWidgetPanelData.productGridDesktopFilterGroupsByContext) || {};
  var PRODUCT_CATEGORY_CONTEXT_BY_TERM_ID =
    (window.bwElementorWidgetPanelData && window.bwElementorWidgetPanelData.productCategoryContextByTermId) || {};

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
    // Title map checked first: works in panel (no data-widget_type) and overrides prefix.
    if (TITLE_FAMILY_MAP[title]) {
      return TITLE_FAMILY_MAP[title];
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

    if (TITLE_FAMILY_MAP[title]) {
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

  function getFirstControlRoot(controlName) {
    return $('.elementor-panel .elementor-control-' + controlName).first();
  }

  function getControlValue(controlName) {
    var $control = getFirstControlRoot(controlName);
    if (!$control.length) {
      return null;
    }

    var $field = $control.find('[data-setting="' + controlName + '"]').first();
    if (!$field.length) {
      return null;
    }

    if ($field.is('select')) {
      return $field.val();
    }

    return $field.val ? $field.val() : null;
  }

  function normalizeControlValues(value) {
    if (Array.isArray(value)) {
      return value
        .map(function (item) {
          return String(item || '').trim();
        })
        .filter(function (item) {
          return item.length > 0;
        });
    }

    var normalized = String(value || '').trim();
    if (!normalized) {
      return [];
    }

    if (normalized.charAt(0) === '[') {
      try {
        return normalizeControlValues(JSON.parse(normalized));
      } catch (error) {
        return [];
      }
    }

    return [normalized];
  }

  function getResolvedCategoryContext(termId) {
    var normalizedTermId = String(termId || '').trim();
    if (!normalizedTermId || normalizedTermId === 'all') {
      return '';
    }

    return PRODUCT_CATEGORY_CONTEXT_BY_TERM_ID[normalizedTermId] || '';
  }

  function resolveProductGridDesktopFilterContext() {
    var postType = String(getControlValue('post_type') || '').trim();
    if (postType && postType !== 'product') {
      return '';
    }

    var defaultCategoryValues = normalizeControlValues(getControlValue('default_category'));
    if (defaultCategoryValues.length === 1 && defaultCategoryValues[0] !== 'all') {
      return getResolvedCategoryContext(defaultCategoryValues[0]);
    }

    var parentCategories = normalizeControlValues(getControlValue('parent_category'));
    if (!parentCategories.length) {
      return '';
    }

    var resolvedContexts = {};

    parentCategories.forEach(function (termId) {
      var contextSlug = getResolvedCategoryContext(termId);
      if (contextSlug) {
        resolvedContexts[contextSlug] = true;
      }
    });

    var uniqueContexts = Object.keys(resolvedContexts);

    if (uniqueContexts.length === 1) {
      return uniqueContexts[0];
    }

    if (uniqueContexts.length > 1) {
      return 'mixed';
    }

    return '';
  }

  function getAllowedDesktopFilterGroupsForContext(contextSlug) {
    if (PRODUCT_GRID_FILTER_GROUPS_BY_CONTEXT[contextSlug]) {
      return PRODUCT_GRID_FILTER_GROUPS_BY_CONTEXT[contextSlug];
    }

    if (PRODUCT_GRID_FILTER_GROUPS_BY_CONTEXT.mixed) {
      return PRODUCT_GRID_FILTER_GROUPS_BY_CONTEXT.mixed;
    }

    return ['types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years'];
  }

  function applyProductGridDesktopFilterVisibility() {
    var $desktopFilterControl = getFirstControlRoot('desktop_filters_config');
    if (!$desktopFilterControl.length) {
      return;
    }

    var allowedGroups = getAllowedDesktopFilterGroupsForContext(resolveProductGridDesktopFilterContext());
    var allowedGroupMap = {};

    allowedGroups.forEach(function (groupKey) {
      allowedGroupMap[groupKey] = true;
    });

    $desktopFilterControl.find('[data-setting="group_key"]').each(function () {
      var $select = $(this);
      var currentValue = String($select.val() || '').trim();
      var $row = $select.closest('.elementor-repeater-row');

      $select.find('option').each(function () {
        var $option = $(this);
        var optionValue = String($option.attr('value') || '').trim();
        var isAllowed = !optionValue || !!allowedGroupMap[optionValue];

        $option.prop('disabled', !isAllowed);
        $option.prop('hidden', !isAllowed);
      });

      if ($row.length) {
        $row.toggle(!currentValue || !!allowedGroupMap[currentValue]);
      }
    });

    $desktopFilterControl.find('.elementor-repeater-row').each(function () {
      var $row = $(this);
      var $groupSelect = $row.find('[data-setting="group_key"]').first();
      var rowValue = String($groupSelect.val() || '').trim();

      if (!rowValue) {
        rowValue = normalizeText($row.find('.elementor-repeater-row-item-title').first().text());
      }

      $row.toggle(!rowValue || !!allowedGroupMap[rowValue]);
    });
  }

  function schedulePanelScan() {
    styleWidgetCards($(document));
    applyProductGridDesktopFilterVisibility();
    setTimeout(function () { styleWidgetCards($(document)); }, 150);
    setTimeout(applyProductGridDesktopFilterVisibility, 150);
    setTimeout(function () { styleWidgetCards($(document)); }, 500);
    setTimeout(applyProductGridDesktopFilterVisibility, 500);
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
