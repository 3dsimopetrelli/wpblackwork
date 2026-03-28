(function ($) {
	'use strict';

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
