/**
 * Lazy image fade-in using IntersectionObserver.
 *
 * All img[loading="lazy"] start at opacity:0 (via .bw-lazy--fade).
 * When an image enters the viewport:
 *   - cached (complete): immediately add .bw-lazy--loaded → instant visible, then fade
 *   - not yet loaded: wait for load event → fade in on arrival
 *
 * Falls back gracefully if IntersectionObserver is unsupported (images render normally).
 */
( function () {
    'use strict';

    if ( ! ( 'IntersectionObserver' in window ) ) {
        return; // old browsers: images render normally, no fade
    }

    var observer = new IntersectionObserver( function ( entries ) {
        entries.forEach( function ( entry ) {
            if ( ! entry.isIntersecting ) { return; }

            var img = entry.target;
            observer.unobserve( img );

            if ( img.complete && img.naturalWidth !== 0 ) {
                // Cached — reveal immediately (transition runs from opacity:0)
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
        rootMargin: '0px 0px 100px 0px' // start 100px before entering viewport
    } );

    function initLazyFade() {
        document.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( img ) {
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
