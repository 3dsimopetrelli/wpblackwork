( function( $, elementorFront ) {
	'use strict';

	
        var BW_Products_Slider = function( $scope, $ ) {
		
	
		var columns = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('slidercolumns');
		var dots = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderdots');
		var central = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('slidercentralmode');
		//var autoplay = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderautoplay');
		var autoplay = false;
		var infinite = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderinfinite');
		var speed = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderspeed');
		var width = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderwidth');
		var next = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('slidernext');
		var prev = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('sliderprev');
		var center = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('slidercenter');
		var prodImages = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('images');
		var cursorPositions = $('.elementor-element-' + $scope.attr("data-id") + ' .sas-total_wrap_post_slider').data('cursorpositions');
		
		var $status = $('.elementor-element-' + $scope.attr("data-id") + ' .pagingInfo');
		var nextButton = $('.elementor-element-' + $scope.attr("data-id") + ' .btn-nextSlide');
		var prevButton = $('.elementor-element-' + $scope.attr("data-id") + ' .btn-prevSlide');


		function closeOverlay() {
			$('.product-image-overlay').css({
				'visibility': 'hidden',
				'opacity': '0',
				'tranisition': '.3s'
			});
			$('body').removeClass('product-image-overlay-open');
		};

		$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').not('.slick-initialized')
		.on('afterChange init', function(event, slick, direction){
			// remove all prev/next
			slick.$slides.removeClass('prevSlide').removeClass('nextSlide');

			document.addEventListener('keydown', (event) => {
				if (event.code === "ArrowRight") {
					$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickNext');
				}
				if (event.code === "ArrowLeft") {
					$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickPrev');
				}
			}, false);

			// find current slide
			for (var i = 0; i < slick.$slides.length; i++)
			{
				var $slide = $(slick.$slides[i]);
				if ($slide.hasClass('slick-current')) {
					// update DOM siblings
					$slide.prev().addClass('prevSlide').attr('data-cursor-txt', 'Prev');
					$slide.next().addClass('nextSlide').attr('data-cursor-txt', 'Next');
					$slide.attr('data-cursor-txt', 'Zoom')

					//ETICHETTA
					$('[data-cursor-txt]').hover(function(event){
						var cursorTxtColor = $(this).attr("data-cursor-txt-color") ? $(this).attr("data-cursor-txt-color") : "#000";
						var cursorTxtBackground = $(this).attr("data-cursor-txt-background") ? $(this).attr("data-cursor-txt-background") : "rgba(255,0,0,0.5)";
						$('#circularcursor').attr("data-cursor-txt", $(this).attr("data-cursor-txt"));
						$('#circularcursor.hover-label::after').css("color", cursorTxtColor); //Aggiunge txt color
						$('#circularcursor.hover-label::after').css("background-color", cursorTxtBackground); //Aggiunge background color
						$('#circularcursor').addClass("hover-label");
					},
					function(){
						$('#circularcursor').removeClass("hover-label");
						$('#circularcursor').removeAttr("data-cursor-txt");
						$('#circularcursor.hover-label::after').css("color", ""); //Rimuove txt color
						$('#circularcursor.hover-label::after').css("background-color", ""); //Rimuove background color
					});

					$(".slick-slide.nextSlide").css("cursor", "url('"+ next +"') " + cursorPositions.n_x + " " + cursorPositions.n_y +  ", auto");
					$(".slick-slide.prevSlide").css("cursor", "url('"+ prev +"') " + cursorPositions.p_x + " " + cursorPositions.p_y +  ", auto");
					$(".slick-slide.slick-current").css("cursor", "url('"+ center +"') " + cursorPositions.c_x + " " + cursorPositions.c_y +  ", auto");

					$('.slick-slide.nextSlide').unbind("click").click(function( event ) {
						event.preventDefault();
						$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickNext');
					});
					$('.slick-slide.prevSlide').unbind("click").click(function( event ) {
						event.preventDefault();
						$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickPrev');
					});
					nextButton.unbind("click").click(function( event ) {
						event.preventDefault();
						$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickNext');
					});
					prevButton.unbind("click").click(function( event ) {
						event.preventDefault();
						$('.elementor-element-' + $scope.attr("data-id") +' .sas-total_wrap_post_slider').slick('slickPrev');
					});
 
					$(".slick-slide.slick-current").unbind("click").click(function(){
						var slideID = $(this).find('img').attr('data-id');
						$('.product-image-overlay').css({
							'visibility': 'visible',
							'opacity': '1',
							'tranisition': '.3s'
						});
						$('body').addClass('product-image-overlay-open');
						$('.product-image-overlay-gallery').scrollTop($(`.product-image-overlay-gallery img[data-id='${slideID}']`)[0].offsetTop - 130);
					});

					$('.product-image-overlay-gallery').click(function() {
						closeOverlay();
					});

					$('.product-image-overlay-header button').click(function() {
						closeOverlay();
					});

					$('.product-image-overlay-header h2').click(function() {
						closeOverlay();
					});

					break;
				}
			}

			
		  }
		)
		.on('beforeChange', function(event, slick) {
			// optional, but cleaner maybe
			// remove all prev/next
			slick.$slides.removeClass('prevSlide').removeClass('nextSlide');
		})
		.on('init reInit afterChange', function (event, slick, currentSlide, nextSlide) {
			//currentSlide is undefined on init -- set it to 0 in this case (currentSlide is 0 based)
			var i = (currentSlide ? currentSlide : 0) + 1;
			$status.text(i + '/' + slick.slideCount);
		})
		.slick({
				autoplay: autoplay,
				infinite: infinite,
				dots: dots,

				speed: speed,

				slidesToShow: columns,
				slidesToScroll: columns,

				focusOnSelect: false,
				
				//da aggiungere per la slide centrale
				centerMode: central,
				variableWidth: width,
				//centerPadding: '15%',
				
				arrows: false,
	
				responsive: [{
					breakpoint: 1024,
					settings: {
						speed: speed,
						slidesToShow: 2,
						slidesToScroll: 2,
						infinite: true,
						dots: false
					}
					},{
					breakpoint: 768,
					settings: {
						speed: speed,
						slidesToShow: 1,
						slidesToScroll: 1,
						adaptiveHeight: true
						}
					},{
					breakpoint: 480,
					settings: {
						speed: speed,
						slidesToShow: 1,
						slidesToScroll: 1,
						adaptiveHeight: true
						}
					}
				]
			});
		};
		
		
  $( window ).on( 'elementor/frontend/init', function() {
    elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-products-slider.default', BW_Products_Slider );
  } );
  
  
  
}( jQuery, window.elementorFrontend ) );
