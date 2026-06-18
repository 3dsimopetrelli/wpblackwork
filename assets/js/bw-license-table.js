(function ($) {
	'use strict';

	var ACTIVE_WRAPS = [];
	var RESIZE_BOUND = false;
	var OPEN_WRAP = null;

	function clearClasses($wrap) {
		if (!$wrap || !$wrap.length) {
			return;
		}

		$wrap.removeClass('is-left is-right is-centered is-above is-overflow-safe');
	}

	function prepareTooltip($wrap) {
		if (!$wrap || !$wrap.length) {
			return;
		}

		var $tooltip = $wrap.find('.bw-license-table-widget__tooltip').first();

		if (!$tooltip.length) {
			return;
		}

		$tooltip.removeClass('is-hidden').removeAttr('hidden');
	}

	function registerActiveWrap(wrapEl) {
		if (!wrapEl) {
			return;
		}

		if (ACTIVE_WRAPS.indexOf(wrapEl) === -1) {
			ACTIVE_WRAPS.push(wrapEl);
		}
	}

	function unregisterActiveWrap(wrapEl) {
		if (!wrapEl) {
			return;
		}

		ACTIVE_WRAPS = ACTIVE_WRAPS.filter(function (item) {
			return item !== wrapEl;
		});
	}

	function hideTooltip($wrap) {
		if (!$wrap || !$wrap.length) {
			return;
		}

		$wrap.removeClass('is-active');
		unregisterActiveWrap($wrap[0]);

		if (OPEN_WRAP === $wrap[0]) {
			OPEN_WRAP = null;
		}

		clearClasses($wrap);
	}

	function hideAllTooltips(exceptWrapEl) {
		$('.bw-license-table-widget__tooltip-wrap.is-active').each(function () {
			if (exceptWrapEl && this === exceptWrapEl) {
				return;
			}

			hideTooltip($(this));
		});
	}

	function showTooltip($wrap, persistActive) {
		if (!$wrap || !$wrap.length) {
			return;
		}

		hideAllTooltips($wrap[0]);
		prepareTooltip($wrap);
		registerActiveWrap($wrap[0]);
		applyTooltipPosition($wrap);

		if (persistActive) {
			$wrap.addClass('is-active');
			OPEN_WRAP = $wrap[0];
		}
	}

	function applyTooltipPosition($wrap) {
		if (!$wrap || !$wrap.length) {
			return;
		}

		var wrapEl = $wrap[0];
		var $tooltip = $wrap.find('.bw-license-table-widget__tooltip').first();
		var $trigger = $wrap.find('.bw-license-table-widget__tooltip-trigger').first();

		if (!$tooltip.length || !$trigger.length) {
			return;
		}

		if ($trigger.css('display') === 'none') {
			clearClasses($wrap);
			return;
		}

		clearClasses($wrap);
		$wrap.addClass('is-right is-overflow-safe');

		var tooltipEl = $tooltip[0];
		var triggerEl = $trigger[0];

		tooltipEl.style.visibility = 'hidden';
		tooltipEl.style.opacity = '1';
		tooltipEl.style.pointerEvents = 'none';

		var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		var triggerRect = triggerEl.getBoundingClientRect();
		var tooltipRect = tooltipEl.getBoundingClientRect();
		var spaceOnRight = viewportWidth - triggerRect.left;
		var spaceOnLeft = triggerRect.right;
		var horizontalMargin = 16;

		if (tooltipRect.width > (viewportWidth - horizontalMargin * 2)) {
			$wrap.removeClass('is-right is-left').addClass('is-centered');
		} else if (tooltipRect.right > (viewportWidth - horizontalMargin)) {
			if (spaceOnLeft >= tooltipRect.width + horizontalMargin) {
				$wrap.removeClass('is-right is-centered').addClass('is-left');
			} else {
				$wrap.removeClass('is-right is-left').addClass('is-centered');
			}
		} else if (tooltipRect.left < horizontalMargin) {
			if (spaceOnRight >= tooltipRect.width + horizontalMargin) {
				$wrap.removeClass('is-left is-centered').addClass('is-right');
			} else {
				$wrap.removeClass('is-right is-left').addClass('is-centered');
			}
		}

		tooltipRect = tooltipEl.getBoundingClientRect();

		if (tooltipRect.bottom > (viewportHeight - horizontalMargin) && triggerRect.top >= (tooltipRect.height + horizontalMargin)) {
			$wrap.addClass('is-above');
		}

		tooltipEl.style.visibility = '';
		tooltipEl.style.opacity = '';
		tooltipEl.style.pointerEvents = '';
	}

	function bindTooltip($wrap) {
		if (!$wrap || !$wrap.length || $wrap.data('bwLicenseTooltipBound')) {
			return;
		}

		$wrap.data('bwLicenseTooltipBound', true);

		var wrapEl = $wrap[0];
		var $trigger = $wrap.find('.bw-license-table-widget__tooltip-trigger').first();

		if (!$trigger.length) {
			return;
		}

		function isMobileViewport() {
			return !!(window.matchMedia && window.matchMedia('(max-width: 767px)').matches);
		}

		function activate() {
			showTooltip($wrap, false);
		}

		function deactivate() {
			if ($wrap.hasClass('is-active')) {
				return;
			}

			unregisterActiveWrap(wrapEl);
			clearClasses($wrap);
		}

		$wrap.on('mouseenter.bwLicenseTooltip', function () {
			if (isMobileViewport()) {
				return;
			}

			activate();
		});

		$wrap.on('mouseleave.bwLicenseTooltip', function () {
			if (isMobileViewport()) {
				return;
			}

			deactivate();
		});

		$trigger.on('focus.bwLicenseTooltip', function () {
			activate();
		});

		$trigger.on('blur.bwLicenseTooltip', function () {
			if (isMobileViewport()) {
				return;
			}

			deactivate();
		});

		$trigger.on('click.bwLicenseTooltip', function (event) {
			event.preventDefault();
			event.stopPropagation();

			if ($wrap.hasClass('is-active')) {
				hideTooltip($wrap);
				return;
			}

			showTooltip($wrap, true);
		});
	}

	function initLicenseTable($scope) {
		if (!$scope || !$scope.length) {
			return;
		}

		$scope.find('.bw-license-table-widget__tooltip-wrap').each(function () {
			bindTooltip($(this));
		});
	}

	function bindGlobalResize() {
		if (RESIZE_BOUND) {
			return;
		}

		RESIZE_BOUND = true;

		$(window).on('resize.bwLicenseTooltip', function () {
			hideAllTooltips();

			ACTIVE_WRAPS.forEach(function (wrapEl) {
				applyTooltipPosition($(wrapEl));
			});
		});

		$(document).on('click.bwLicenseTooltipOutside touchstart.bwLicenseTooltipOutside', function (event) {
			var $target = $(event.target);

			if ($target.closest('.bw-license-table-widget__tooltip-wrap').length) {
				return;
			}

			hideAllTooltips();
		});

		$(document).on('keydown.bwLicenseTooltipEscape', function (event) {
			if ('Escape' !== event.key) {
				return;
			}

			hideAllTooltips();
		});
	}

	$(document).ready(function () {
		bindGlobalResize();
		initLicenseTable($(document));
	});

	$(window).on('elementor/frontend/init', function () {
		if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
			return;
		}

		elementorFrontend.hooks.addAction('frontend/element_ready/bw-license-table.default', function ($scope) {
			bindGlobalResize();
			initLicenseTable($scope);
		});
	});
})(jQuery);
