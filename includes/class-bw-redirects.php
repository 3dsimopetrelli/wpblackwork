<?php
/**
 * Gestione redirect personalizzati configurati dal pannello Blackwork Site.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalizza un URL o path per confronto: ritorna path + query con leading slash.
 *
 * @param string $url URL o path inserito.
 * @return string Path normalizzato con querystring.
 */
function bw_normalize_redirect_path( $url ) {
    $url = trim( (string) $url );

    if ( '' === $url ) {
        return '';
    }

    $parsed = wp_parse_url( $url );

    if ( false === $parsed ) {
        return '';
    }

    $path  = isset( $parsed['path'] ) ? '/' . ltrim( $parsed['path'], '/' ) : '/';
    $query = isset( $parsed['query'] ) && '' !== $parsed['query'] ? '?' . $parsed['query'] : '';

    return $path . $query;
}

/**
 * Esegue il redirect sul frontend se l'URL richiesto corrisponde a uno dei pattern salvati.
 */
function bw_maybe_redirect_request() {
    if ( is_admin() ) {
        return;
    }

    $redirects = get_option( 'bw_redirects', [] );

    if ( empty( $redirects ) || ! is_array( $redirects ) ) {
        return;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

    if ( '' === $request_uri ) {
        return;
    }

    $normalized_request = bw_normalize_redirect_path( $request_uri );

    foreach ( $redirects as $redirect ) {
        $source = isset( $redirect['source'] ) ? trim( $redirect['source'] ) : '';
        $target = isset( $redirect['target'] ) ? trim( $redirect['target'] ) : '';

        if ( '' === $source || '' === $target ) {
            continue;
        }

        $normalized_source = bw_normalize_redirect_path( $source );
        $safe_target       = esc_url_raw( $target );

        if ( '' === $normalized_source || '' === $safe_target ) {
            continue;
        }

        if ( $normalized_request !== $normalized_source ) {
            continue;
        }

        // Evita loop se il target coincide con la richiesta corrente o con lo stesso path sorgente.
        $current_full_url = home_url( $normalized_request );
        $target_path      = bw_normalize_redirect_path( $safe_target );

        if ( $current_full_url === $safe_target || $target_path === $normalized_source ) {
            continue;
        }

        wp_safe_redirect( $safe_target, 301 );
        exit;
    }
}
add_action( 'template_redirect', 'bw_maybe_redirect_request', 5 );
