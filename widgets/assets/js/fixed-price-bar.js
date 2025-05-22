( function( $, elementor ) {
	'use strict';
	
	var InitializeFixPriceBar = function($scope, $) {

		jQuery(window).scroll(function() {    
			var scroll = jQuery(window).scrollTop();
		
			if (scroll >= 500) {
				jQuery(".bar_fade").addClass("visible");
			} else {
				jQuery(".bar_fade").removeClass("visible");
			}
		});

	}

	$( window ).on( 'elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-fixed-price-bar.default', InitializeFixPriceBar);
	} );
  
}( jQuery, window.elementorFrontend ) );



