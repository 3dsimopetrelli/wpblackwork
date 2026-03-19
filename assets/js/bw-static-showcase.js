/* BW Static Showcase — lazy-load fade-in */
( function () {
    'use strict';

    function applyFade( img ) {
        if ( img.complete && img.naturalWidth > 0 ) {
            img.classList.add( 'is-loaded' );
        } else {
            img.addEventListener( 'load', function () {
                img.classList.add( 'is-loaded' );
            } );
            img.addEventListener( 'error', function () {
                // Show image even on error to avoid invisible broken state
                img.classList.add( 'is-loaded' );
            } );
        }
    }

    function initShowcaseFade( root ) {
        root.querySelectorAll( '.bw-static-showcase-container .bw-lazy-img' ).forEach( applyFade );
    }

    document.addEventListener( 'DOMContentLoaded', function () {
        initShowcaseFade( document );
    } );

    // Elementor editor: re-run after widget re-render
    if ( window.elementorFrontend ) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-static-showcase.default',
            function ( $scope ) {
                initShowcaseFade( $scope[ 0 ] );
            }
        );
    }
} )();
