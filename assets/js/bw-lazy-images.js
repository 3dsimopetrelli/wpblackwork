/**
 * Lazy image fade-in effect.
 *
 * For each img[loading="lazy"] that is not yet loaded:
 *   - adds .bw-lazy--fade (opacity: 0, transition)
 *   - adds .bw-lazy--loaded (opacity: 1) when the image finishes loading
 *
 * Already-loaded images (cache hits) are skipped entirely — no visual flash.
 * Hover images (.bw-slider-hover) get the class but the higher-specificity
 * wallpost/product-card CSS keeps them at opacity:0 until hover — no conflict.
 */
( function () {
    'use strict';

    function applyLazyFade() {
        document.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( img ) {
            // Already in cache or loaded before JS ran — skip, render instantly.
            if ( img.complete && img.naturalWidth !== 0 ) {
                return;
            }

            img.classList.add( 'bw-lazy--fade' );

            img.addEventListener( 'load', function () {
                img.classList.add( 'bw-lazy--loaded' );
            }, { once: true } );

            // On error show the broken-image placeholder normally.
            img.addEventListener( 'error', function () {
                img.classList.add( 'bw-lazy--loaded' );
            }, { once: true } );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', applyLazyFade );
    } else {
        applyLazyFade();
    }
}() );
