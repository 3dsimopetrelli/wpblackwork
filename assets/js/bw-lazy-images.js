/**
 * Lazy image fade-in using IntersectionObserver.
 *
 * Scoped to containers where a fade makes sense:
 *   .bw-related-products-grid, .bw-related-products-widget, .bw-product-grid
 *
 * Explicitly skipped:
 *   .bw-wallpost — has its own hover/opacity/transition system and transition:none on imgs.
 *   .bw-slick-slider — manages its own lazy loading via data-lazy / Slick.
 *
 * Cached images (complete on observe) reveal immediately via the fade transition.
 * Falls back gracefully if IntersectionObserver is unsupported.
 */
( function () {
    'use strict';

    if ( ! ( 'IntersectionObserver' in window ) ) {
        return;
    }

    var SELECTOR = [
        '.bw-related-products-grid img[loading="lazy"]',
        '.bw-related-products-widget img[loading="lazy"]',
        '.bw-product-grid img[loading="lazy"]',
    ].join( ', ' );

    var observer = new IntersectionObserver( function ( entries ) {
        entries.forEach( function ( entry ) {
            if ( ! entry.isIntersecting ) { return; }

            var img = entry.target;
            observer.unobserve( img );

            if ( img.complete && img.naturalWidth !== 0 ) {
                img.classList.add( 'bw-lazy--loaded' );
            } else {
                img.addEventListener( 'load', function () {
                    img.classList.add( 'bw-lazy--loaded' );
                }, { once: true } );
                img.addEventListener( 'error', function () {
                    img.classList.add( 'bw-lazy--loaded' );
                }, { once: true } );
            }
        } );
    }, {
        rootMargin: '0px 0px 100px 0px'
    } );

    function initLazyFade() {
        document.querySelectorAll( SELECTOR ).forEach( function ( img ) {
            img.classList.add( 'bw-lazy--fade' );
            observer.observe( img );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', initLazyFade );
    } else {
        initLazyFade();
    }
}() );
