(function ($) {
	'use strict';

	var OPEN_MS = 520;
	var CLOSE_MS = 420;
	var OPEN_EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';
	var CLOSE_EASING = 'cubic-bezier(0.4, 0, 0.2, 1)';

	function initAccordion($widget) {
		if (!$widget || !$widget.length) {
			return;
		}

		if (!$widget.hasClass('bw-accordion')) {
			return;
		}

		if ($widget.data('bwAccordionInit')) {
			return;
		}

		$widget.data('bwAccordionInit', true);

		var $header = $widget.find('> .bw-accordion__header');
		var $panel = $widget.find('> .bw-accordion__body > .bw-accordion__panel');
		var panelEl = $panel[0];

		if (!$header.length || !panelEl) {
			return;
		}

		var timer = null;

		function clearTimer() {
			if (timer) {
				clearTimeout(timer);
				timer = null;
			}
		}

		function setStaticState(isOpen) {
			$widget.toggleClass('is-open', isOpen);
			$widget.toggleClass('is-closed', !isOpen);
			$header.attr('aria-expanded', isOpen ? 'true' : 'false');
			$panel.attr('aria-hidden', isOpen ? 'false' : 'true');
			if ('inert' in panelEl) {
				panelEl.inert = !isOpen;
			}

			if (isOpen) {
				panelEl.style.height = 'auto';
				panelEl.style.overflow = 'visible';
				panelEl.style.opacity = '1';
			} else {
				panelEl.style.height = '0px';
				panelEl.style.overflow = 'hidden';
				panelEl.style.opacity = '0';
			}
		}

		function openAccordion() {
			if ($widget.hasClass('is-open')) {
				return;
			}

			clearTimer();

			$widget.addClass('is-open').removeClass('is-closed');
			$header.attr('aria-expanded', 'true');
			$panel.attr('aria-hidden', 'false');
			if ('inert' in panelEl) {
				panelEl.inert = false;
			}

			panelEl.style.transition = '';
			panelEl.style.height = '0px';
			panelEl.style.overflow = 'hidden';
			panelEl.style.opacity = '1';

			// eslint-disable-next-line no-unused-expressions
			panelEl.offsetHeight;

			var targetHeight = panelEl.scrollHeight;
			panelEl.style.transition = 'height ' + OPEN_MS + 'ms ' + OPEN_EASING;
			panelEl.style.height = targetHeight + 'px';

			timer = setTimeout(function () {
				if ($widget.hasClass('is-open')) {
					panelEl.style.transition = '';
					panelEl.style.height = 'auto';
					panelEl.style.overflow = 'visible';
				}
			}, OPEN_MS + 60);
		}

		function closeAccordion() {
			if (!$widget.hasClass('is-open')) {
				return;
			}

			clearTimer();

			var currentHeight = panelEl.getBoundingClientRect().height;
			panelEl.style.transition = '';
			panelEl.style.height = currentHeight + 'px';
			panelEl.style.overflow = 'hidden';
			panelEl.style.opacity = '1';

			// eslint-disable-next-line no-unused-expressions
			panelEl.offsetHeight;

			$widget.removeClass('is-open').addClass('is-closed');
			$header.attr('aria-expanded', 'false');
			$panel.attr('aria-hidden', 'true');

			// eslint-disable-next-line no-unused-expressions
			panelEl.offsetHeight;

			panelEl.style.transition = 'height ' + CLOSE_MS + 'ms ' + CLOSE_EASING;
			panelEl.style.height = '0px';

			timer = setTimeout(function () {
				panelEl.style.transition = '';
				panelEl.style.overflow = 'hidden';
				panelEl.style.opacity = '0';
				if ('inert' in panelEl) {
					panelEl.inert = true;
				}
			}, CLOSE_MS + 60);
		}

		function syncFromState() {
			var isOpen = $widget.hasClass('is-open');
			setStaticState(isOpen);

			if (isOpen) {
				window.requestAnimationFrame(function () {
					panelEl.style.height = 'auto';
					panelEl.style.overflow = 'visible';
					panelEl.style.opacity = '1';
				});
			}
		}

		$header.off('.bwAccordion').on('click.bwAccordion', function (event) {
			event.preventDefault();
			event.stopPropagation();

			if ($widget.hasClass('is-open')) {
				closeAccordion();
			} else {
				openAccordion();
			}
		});

		syncFromState();
	}

	function initAll(scope) {
		var $scope = scope && scope.length ? scope : $(document);

		$scope.find('.bw-accordion').each(function () {
			initAccordion($(this));
		});
	}

	$(document).ready(function () {
		initAll($(document));
	});

	$(window).on('elementor/frontend/init', function () {
		if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
			return;
		}

		elementorFrontend.hooks.addAction('frontend/element_ready/bw-accordion.default', function ($scope) {
			initAll($scope);
		});
	});

})(jQuery);
