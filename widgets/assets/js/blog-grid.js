( function( $, elementor ) {
	'use strict';
	
	var InitializeBlogGrid = function($scope, $) {
		InitIsotope($scope, $);
		InitLoadMore($scope);

		setTimeout( function() {
			jQuery('.elementor-element-' + $scope.attr("data-id") + " .gallery_post_format").not('.slick-initialized').slick({
				adaptiveHeight: false,
				dots: false,
				infinite: true,
				autoplay: true,
				speed: 500,
				fade: false	
			}); 
		}, 1000); 
	}

	$( window ).on( 'elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-blog.default', InitializeBlogGrid);
		elementorFrontend.hooks.addAction( 'frontend/element_ready/sas-blog-search.default', InitializeBlogGrid);
	} );
	
	var InitIsotope = function( $scope, $ ) {
		var $container = $scope.find( '.sas-grid_blog_container' );
		if ($container){
			var isotopeEna = $container.data('enableisotope');

			if (isotopeEna) {
				// init Isotope
				var $grid = $( '.sas-grid_blog_container .sas-grid' ).isotope({
					// options
					itemSelector: '.elementor-element-' + $scope.attr("data-id") + ' .blog-post',
					//layoutMode: 'fitRows',
				});
				
				window.setTimeout(function(){
					$grid.isotope( 'layout' );
				}, 200);
			}
		}
	};


	var InitLoadMore = function($scope) {
		var $grid = $scope.find( '.sas-grid' ),
				$settings  = $grid.data('settings'),
				$loadMoreElgrid = $scope.find('.sas-post_loadmore');

			if ( ! $grid.length ||!$loadMoreElgrid.length ) {
				return;
			}
								
			var $currentPage = $settings['current_page'];

			if ( $currentPage >= $settings['max_page'] ) {
				$loadMoreElgrid.remove();
			}

			$($loadMoreElgrid).on('click', function(event){
				//TODO
				if ($loadMoreElgrid.length) {
					$loadMoreElgrid.addClass('sas-load-more-loading');
				}

				$currentPage++;

				$settings['current_page'] = $currentPage;

				callLoadMore();
			});


			function callLoadMore(){
				var $itemHolder = $scope.find('.sas-grid');
				var isotopeEna = $scope.find( '.sas-grid_blog_container' ).data('enableisotope');


				jQuery.ajax({
					url: ajax_object.ajaxurl,
					type:'post',
					data: $settings,
					success:function(response){					
						var $newItems = $(response);
						
						$itemHolder.append($newItems);
						
					    if (isotopeEna)	$itemHolder.isotope( 'insert', $newItems );
						
						if ($loadMoreElgrid.length) {
							$loadMoreElgrid.removeClass('sas-load-more-loading');
						}
						if ( $settings['current_page'] == $settings['max_page']) {
							$loadMoreElgrid.remove();
						}

						$itemHolder.find('.blog-post img').each(function(i) {
							jQuery(this).addClass("is-loaded");
						});

					}
				});
			}
	}; 
  
}( jQuery, window.elementorFrontend ) );



