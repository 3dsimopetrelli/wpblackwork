( function( $, elementor ) {
	'use strict';
	
	var InitializeWooProducts = function($scope, $) {

		var $container = $scope.find( '.sas-products_container' );
		$container.imagesLoaded( function() {
			$container.find('article').each(function(i) { 
				jQuery(this).delay(i*200)
				.animate({'opacity': 1},800, 'swing', function() { 
					 jQuery(this).addClass('loaded');
				});
			 });
		});
		
	}

	$( window ).on( 'elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-products.default', InitializeWooProducts);
	} );
  
}( jQuery, window.elementorFrontend ) );



