(function ($) {
	'use strict';

	function initFadeReviewSlider($slider, $slides, prevBtn, nextBtn, setActiveSlide) {
		const slideCount = $slides.length;
		let activeIndex = 0;
		let autoplayTimer = null;
		const autoplayDelay = 2000;

		const goTo = function (index) {
			activeIndex = (index + slideCount) % slideCount;
			setActiveSlide(activeIndex);
		};

		const stopAutoplay = function () {
			if (autoplayTimer) {
				window.clearInterval(autoplayTimer);
				autoplayTimer = null;
			}
		};

		const startAutoplay = function () {
			if (slideCount < 2 || autoplayTimer) {
				return;
			}

			autoplayTimer = window.setInterval(function () {
				goTo(activeIndex + 1);
			}, autoplayDelay);
		};

		const restartAutoplay = function () {
			stopAutoplay();
			startAutoplay();
		};

		if (prevBtn) {
			$(prevBtn).off('.bwTrustFade').on('click.bwTrustFade', function (event) {
				event.preventDefault();
				goTo(activeIndex - 1);
				restartAutoplay();
			});
		}

		if (nextBtn) {
			$(nextBtn).off('.bwTrustFade').on('click.bwTrustFade', function (event) {
				event.preventDefault();
				goTo(activeIndex + 1);
				restartAutoplay();
			});
		}

		$slider
			.off('.bwTrustFadeHover')
			.on('mouseenter.bwTrustFadeHover focusin.bwTrustFadeHover', function () {
				stopAutoplay();
			})
			.on('mouseleave.bwTrustFadeHover', function () {
				startAutoplay();
			})
			.on('focusout.bwTrustFadeHover', function () {
				window.setTimeout(function () {
					if (!$slider[0].contains(document.activeElement)) {
						startAutoplay();
					}
				}, 0);
			});

		startAutoplay();

		$slider.data('bwTrustSliderInstance', {
			destroy: function () {
				stopAutoplay();
				$slider.off('.bwTrustFadeHover');
				if (prevBtn) {
					$(prevBtn).off('.bwTrustFade');
				}
				if (nextBtn) {
					$(nextBtn).off('.bwTrustFade');
				}
			}
		});
	}

	function initTrustReviewSlider($widget) {
		const $slider = $widget.find('[data-bw-trust-review-slider]').first();
		if (!$slider.length || $slider.data('bwTrustSliderInit')) {
			return;
		}

		$slider.data('bwTrustSliderInit', true);

		const viewport = $slider.find('.bw-trust-box__review-slider-viewport')[0];
		const prevBtn = $slider.find('.bw-trust-box__review-slider-arrow--prev')[0];
		const nextBtn = $slider.find('.bw-trust-box__review-slider-arrow--next')[0];
		const $slides = $slider.find('.bw-trust-box__review-slide');
		const effect = String($slider.data('bwTrustReviewSliderEffect') || 'slide');

		if (!viewport || !$slides.length) {
			return;
		}

		const setActiveSlide = function (index) {
			$slides.removeClass('is-active');
			const $activeSlide = $slides.eq(index);
			if ($activeSlide.length) {
				$activeSlide.addClass('is-active');
			}
		};

		setActiveSlide(0);

		if ($slides.length < 2) {
			return;
		}

		if ('fade' === effect) {
			initFadeReviewSlider($slider, $slides, prevBtn, nextBtn, setActiveSlide);
			return;
		}

		if (typeof BWEmblaCore === 'undefined') {
			console.warn('BW Trust Box: BWEmblaCore not available for trust review slider');
			return;
		}

		const emblaCore = new BWEmblaCore(viewport, {
			loop: true,
			align: 'start',
			containScroll: 'trimSnaps',
			slidesToScroll: 1,
			dragFree: false,
			watchResize: true
		}, {
			prevBtn: prevBtn,
			nextBtn: nextBtn,
			autoplay: {
				delay: 2000,
				playOnInit: true,
				stopOnInteraction: false,
				stopOnMouseEnter: true,
				stopOnFocusIn: true,
				jump: false
			},
			onSelect: setActiveSlide
		});

		const api = emblaCore.init();
		if (!api) {
			return;
		}

		$slider.data('bwTrustSliderInstance', emblaCore);
		requestAnimationFrame(function () {
			setActiveSlide(api.selectedScrollSnap());
		});
	}

	function initTrustBoxWidget($widget) {
		if (!$widget || !$widget.length || $widget.data('bwTrustBoxInit')) {
			return;
		}

		$widget.data('bwTrustBoxInit', true);
		initTrustReviewSlider($widget);
	}

	function initScope($scope) {
		const $widgets = $scope.is('.bw-trust-box') ? $scope : $scope.find('.bw-trust-box');
		if (!$widgets.length) {
			return;
		}

		$widgets.each(function () {
			initTrustBoxWidget($(this));
		});
	}

	$(document).ready(function () {
		initScope($(document));
	});

	$(window).on('elementor/frontend/init', function () {
		if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/bw-trust-box.default', function ($scope) {
				initScope($scope);
			});
		}
	});
})(jQuery);
