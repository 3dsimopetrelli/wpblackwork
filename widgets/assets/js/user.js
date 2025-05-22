/* TEST tutorial: https://benmarshall.me/build-custom-elementor-widgets/ */
	
( function( $, elementor ) {
	
	'use strict';
 
  var WidgetHandlerUser = function( $scope, $ ) {
    
	if ( $scope.find( '#sas-smart_user_widget' ) ){
		$('#sas-smart_user_widget').smartmenus({
			mainMenuSubOffsetY: 0,
			noMouseOver: false
		});
	}
  };
   

  $( window ).on( 'elementor/frontend/init', function() {
    elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-header-user.default', WidgetHandlerUser );
  } );
  
  
}( jQuery, window.elementorFrontend ) );