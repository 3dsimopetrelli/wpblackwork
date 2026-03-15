(function ($) {
  'use strict';

  var MARKER_CLASS = 'bw-tbl-hide-pro-upsell';
  var SCAN_SELECTORS = [
    '.elementor-panel .elementor-panel-category',
    '.elementor-panel .elementor-panel-heading',
    '.elementor-panel .elementor-control',
    '.elementor-panel .elementor-element'
  ];
  var HIDDEN_SECTION_PATTERNS = [
    /link\s*in\s*bio/i
  ];

  function hasUpgradeText($node) {
    return /upgrade/i.test(($node.text() || '').replace(/\s+/g, ' ').trim());
  }

  function matchesHiddenSectionText($node) {
    var text = ($node.text() || '').replace(/\s+/g, ' ').trim();

    return HIDDEN_SECTION_PATTERNS.some(function (pattern) {
      return pattern.test(text);
    });
  }

  function markUpgradeContainers($root) {
    var $scope = $root && $root.length ? $root : $(document);
    var selectors = SCAN_SELECTORS.join(', ');

    $scope.find(selectors).each(function () {
      var $item = $(this);
      if (!hasUpgradeText($item) && !matchesHiddenSectionText($item)) {
        return;
      }

      $item.addClass(MARKER_CLASS);
    });
  }

  function scheduleScan() {
    markUpgradeContainers($(document));
    setTimeout(function () { markUpgradeContainers($(document)); }, 120);
    setTimeout(function () { markUpgradeContainers($(document)); }, 400);
  }

  function bindObserver() {
    if (typeof MutationObserver === 'undefined') {
      return;
    }

    var panel = document.querySelector('.elementor-panel');
    if (!panel) {
      return;
    }

    var observer = new MutationObserver(function () {
      scheduleScan();
    });

    observer.observe(panel, { childList: true, subtree: true });
  }

  $(document).ready(function () {
    scheduleScan();
    bindObserver();
  });

  $(window).on('elementor:init elementor/frontend/init', scheduleScan);
})(jQuery);
