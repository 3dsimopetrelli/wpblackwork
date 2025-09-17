/* TEST tutorial: https://benmarshall.me/build-custom-elementor-widgets/ */
	
( function( $, elementor ) {
	
	'use strict';
 
  var BW_Mobile_Menu = function( $scope, $ ) {
   /* 
	if ( $scope.find( '#sas-smart_user_widget' ) ){
		$('#sas-smart_user_widget').smartmenus({
			mainMenuSubOffsetY: 0,
			noMouseOver: false
		});
		
	}
	*/
	
	if ( $scope.find( '.menu-toggle' ) ){
	
		
	  $('.menu-toggle').click(function() {
	    $('.menu-mobile').toggleClass('show-popup');
	  });
	
	  $('.close-popup').click(function() {
	    $('.menu-mobile').removeClass('show-popup');
	  });
	}	
	
	
  };
   

  $( window ).on( 'elementor/frontend/init', function() {
    elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-header-mobile-menu.default', BW_Mobile_Menu );
  } );
  
  
}( jQuery, window.elementorFrontend ) );