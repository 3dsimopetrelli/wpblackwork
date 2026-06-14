(function ($) {
	'use strict';

	var OPEN_MS = 920;
	var CLOSE_MS = 420;
	var OPEN_EASING = 'cubic-bezier(0.19, 1, 0.22, 1)';
	var CLOSE_EASING = 'cubic-bezier(0.4, 0, 0.2, 1)';

	function initAccordion($widget) {
		if (!$widget || !$widget.length || !$widget.hasClass('bw-accordion')) {
			return;
		}

		if ($widget.data('bwAccordionInit')) {
			return;
		}

		$widget.data('bwAccordionInit', true);

		var $header = $widget.find('> .bw-accordion__header');
		var $panel = $widget.find('> .bw-accordion__body > .bw-accordion__panel');
		var $panelInner = $panel.find('> .bw-accordion__panel-inner');
		var panelEl = $panel[0];
		var panelInnerEl = $panelInner[0];
		var timer = null;
		var activeCleanup = null;

		if (!$header.length || !panelEl || !panelInnerEl) {
			return;
		}

		function clearTimer() {
			if (timer) {
				clearTimeout(timer);
				timer = null;
			}
		}

		function clearTransitionHandler() {
			if (activeCleanup) {
				activeCleanup();
				activeCleanup = null;
			}
		}

		function onTransitionEnd(expectedDuration, callback) {
			var done = false;

			function finish() {
				if (done) {
					return;
				}

				done = true;
				panelEl.removeEventListener('transitionend', handleEnd);
				clearTimer();
				activeCleanup = null;
				callback();
			}

			function handleEnd(event) {
				if (event.target !== panelEl || event.propertyName !== 'height') {
					return;
				}

				finish();
			}

			panelEl.addEventListener('transitionend', handleEnd);
			timer = setTimeout(finish, expectedDuration + 80);
			activeCleanup = function () {
				panelEl.removeEventListener('transitionend', handleEnd);
				clearTimer();
			};
		}

		function setStaticState(isOpen) {
			clearTimer();
			clearTransitionHandler();

			$widget.removeClass('is-collapsing is-opening is-closing');
			$widget.toggleClass('is-open', isOpen);
			$widget.toggleClass('is-closed', !isOpen);
			$header.attr('aria-expanded', isOpen ? 'true' : 'false');
			$panel.attr('aria-hidden', isOpen ? 'false' : 'true');

			if ('inert' in panelEl) {
				panelEl.inert = !isOpen;
			}

			panelEl.style.transition = '';
			panelEl.style.height = isOpen ? 'auto' : '0px';
			panelEl.style.overflow = isOpen ? 'visible' : 'hidden';
			panelEl.style.opacity = '1';
		}

		function openAccordion() {
			if ($widget.hasClass('is-open') || $widget.hasClass('is-collapsing')) {
				return;
			}

			clearTimer();
			clearTransitionHandler();

			$widget.removeClass('is-closed is-closing').addClass('is-collapsing is-opening');
			$header.attr('aria-expanded', 'true');
			$panel.attr('aria-hidden', 'false');

			if ('inert' in panelEl) {
				panelEl.inert = false;
			}

			panelEl.style.height = '0px';
			panelEl.style.overflow = 'hidden';
			panelEl.style.transition = 'none';
			panelEl.style.opacity = '1';

			// eslint-disable-next-line no-unused-expressions
			panelEl.offsetHeight;

			var targetHeight = Math.ceil(panelInnerEl.getBoundingClientRect().height);
			if (targetHeight <= 0) {
				targetHeight = panelEl.scrollHeight;
			}
			panelEl.style.transition = 'height ' + OPEN_MS + 'ms ' + OPEN_EASING;
			panelEl.style.height = targetHeight + 'px';

			onTransitionEnd(OPEN_MS, function () {
				panelEl.style.height = targetHeight + 'px';
				panelEl.style.overflow = 'hidden';

				window.requestAnimationFrame(function () {
					$widget.removeClass('is-collapsing is-opening').addClass('is-open');
					panelEl.style.transition = '';
					panelEl.style.height = 'auto';
					panelEl.style.overflow = 'visible';
				});
			});
		}

		function closeAccordion() {
			if (!$widget.hasClass('is-open') || $widget.hasClass('is-collapsing')) {
				return;
			}

			clearTimer();
			clearTransitionHandler();

			var currentHeight = Math.ceil(panelEl.getBoundingClientRect().height);

			panelEl.style.height = currentHeight + 'px';
			panelEl.style.overflow = 'hidden';
			panelEl.style.transition = 'none';
			panelEl.style.opacity = '1';

			// eslint-disable-next-line no-unused-expressions
			panelEl.offsetHeight;

			$widget.removeClass('is-open is-opening').addClass('is-collapsing is-closing');
			$header.attr('aria-expanded', 'false');
			$panel.attr('aria-hidden', 'true');

			panelEl.style.transition = 'height ' + CLOSE_MS + 'ms ' + CLOSE_EASING;
			panelEl.style.height = '0px';

			onTransitionEnd(CLOSE_MS, function () {
				$widget.removeClass('is-collapsing is-closing').addClass('is-closed');
				panelEl.style.transition = '';
				panelEl.style.height = '0px';
				panelEl.style.overflow = 'hidden';

				if ('inert' in panelEl) {
					panelEl.inert = true;
				}
			});
		}

		function syncFromState() {
			setStaticState($widget.hasClass('is-open'));
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
