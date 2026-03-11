(function ($) {
  'use strict';

  var MARKER_CLASS = 'bw-tbl-hide-pro-upsell';
  var SCAN_SELECTORS = [
    '.elementor-panel .elementor-panel-category',
    '.elementor-panel .elementor-panel-heading',
    '.elementor-panel .elementor-control',
    '.elementor-panel .elementor-element'
  ];

  function hasUpgradeText($node) {
    return /upgrade/i.test(($node.text() || '').replace(/\s+/g, ' ').trim());
  }

  function markUpgradeContainers($root) {
    var $scope = $root && $root.length ? $root : $(document);
    var selectors = SCAN_SELECTORS.join(', ');

    $scope.find(selectors).each(function () {
      var $item = $(this);
      if (!hasUpgradeText($item)) {
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
