<?php
/**
 * Gestione redirect personalizzati configurati dal pannello Blackwork Site.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restituisce il path senza querystring.
 *
 * @param string $normalized_path Path normalizzato.
 * @return string
 */
function bw_redirect_get_path_only( $normalized_path ) {
    $normalized_path = (string) $normalized_path;
    $query_position  = strpos( $normalized_path, '?' );

    if ( false === $query_position ) {
        return $normalized_path;
    }

    return substr( $normalized_path, 0, $query_position );
}

/**
 * Famiglie di path protette: non possono essere usate come source/target redirect.
 *
 * @return string[]
 */
function bw_get_protected_redirect_route_families() {
    return [
        '/wp-admin',
        '/wp-login.php',
        '/wp-json',
        '/cart',
        '/checkout',
        '/my-account',
        '/wc-api',
    ];
}

/**
 * Verifica se un path appartiene a una famiglia protetta.
 *
 * @param string $path_only Path senza query.
 * @return bool
 */
function bw_is_protected_redirect_path( $path_only ) {
    $path_only = '/' . ltrim( (string) $path_only, '/' );

    foreach ( bw_get_protected_redirect_route_families() as $protected_base ) {
        $protected_base = '/' . ltrim( (string) $protected_base, '/' );

        if ( $path_only === $protected_base || 0 === strpos( $path_only, $protected_base . '/' ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica se URL/path normalizzato punta a route protetta.
 *
 * @param string $url_or_path URL o path.
 * @return bool
 */
function bw_is_protected_redirect_url( $url_or_path ) {
    $normalized = bw_normalize_redirect_path( $url_or_path );

    if ( '' === $normalized ) {
        return false;
    }

    return bw_is_protected_redirect_path( bw_redirect_get_path_only( $normalized ) );
}

/**
 * Ritorna il numero massimo di hop consentiti per catena redirect.
 *
 * @return int
 */
function bw_get_redirect_max_hops() {
    return 5;
}

/**
 * Legge il contatore hop corrente da cookie.
 *
 * @return int
 */
function bw_get_redirect_current_hop_count() {
    if ( ! isset( $_COOKIE['bw_redirect_hops'] ) ) {
        return 0;
    }

    return max( 0, absint( wp_unslash( $_COOKIE['bw_redirect_hops'] ) ) );
}

/**
 * Aggiorna il cookie hop redirect.
 *
 * @param int $count Nuovo valore.
 * @return void
 */
function bw_set_redirect_hop_count( $count ) {
    if ( headers_sent() ) {
        return;
    }

    $count      = max( 0, absint( $count ) );
    $expiration = time() + MINUTE_IN_SECONDS;

    setcookie(
        'bw_redirect_hops',
        (string) $count,
        $expiration,
        COOKIEPATH ? COOKIEPATH : '/',
        COOKIE_DOMAIN,
        is_ssl(),
        true
    );
}

/**
 * Resetta il contatore hop quando la richiesta converge senza redirect.
 *
 * @return void
 */
function bw_reset_redirect_hop_count() {
    if ( headers_sent() || ! isset( $_COOKIE['bw_redirect_hops'] ) ) {
        return;
    }

    setcookie(
        'bw_redirect_hops',
        '',
        time() - HOUR_IN_SECONDS,
        COOKIEPATH ? COOKIEPATH : '/',
        COOKIE_DOMAIN,
        is_ssl(),
        true
    );
}

/**
 * Normalizza URL/path in una forma canonica deterministica basata sul path.
 * La query viene separata/parse-ata ma non partecipa al nodo canonico di matching.
 *
 * Regole:
 * - rimuove scheme/host nel confronto interno
 * - forza leading slash singolo
 * - collassa slash multipli
 * - normalizza trailing slash (eccetto root)
 * - ignora fragment
 * - canonicalizza il path in lowercase
 *
 * @param string $url URL o path inserito.
 * @return string Path canonico o stringa vuota se non normalizzabile.
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

    $path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '';

    if ( '' === $path ) {
        $path = '/';
    }

    $path = preg_replace( '#/+#', '/', $path );

    if ( null === $path ) {
        return '';
    }

    $path = '/' . ltrim( $path, '/' );
    $path = strtolower( $path );

    if ( '/' !== $path ) {
        $path = rtrim( $path, '/' );
        if ( '' === $path ) {
            $path = '/';
        }
    }

    return $path;
}

/**
 * Normalizza il ruleset redirect e scarta regole non sicure in save-time.
 *
 * @param mixed $new_value Nuovo valore opzione.
 * @param mixed $old_value Valore precedente opzione.
 * @return array
 */
function bw_redirects_enforce_save_time_safety( $new_value, $old_value ) {
    if ( ! is_array( $new_value ) ) {
        return is_array( $old_value ) ? $old_value : [];
    }

    $sanitized_rules   = [];
    $source_target_map = [];

    foreach ( $new_value as $redirect ) {
        if ( ! is_array( $redirect ) ) {
            continue;
        }

        $source = isset( $redirect['source'] ) ? trim( (string) $redirect['source'] ) : '';
        $target = isset( $redirect['target'] ) ? trim( (string) $redirect['target'] ) : '';

        if ( '' === $source || '' === $target ) {
            continue;
        }

        $normalized_source = bw_normalize_redirect_path( $source );
        $safe_target       = esc_url_raw( $target );
        $target_path       = bw_normalize_redirect_path( $safe_target );

        if ( '' === $normalized_source || '' === $safe_target || '' === $target_path ) {
            continue;
        }

        if ( bw_is_protected_redirect_url( $normalized_source ) || bw_is_protected_redirect_url( $safe_target ) ) {
            continue;
        }

        // Self-loop.
        if ( $target_path === $normalized_source ) {
            continue;
        }

        // Regola duplicata source: first-wins deterministico.
        if ( isset( $source_target_map[ $normalized_source ] ) ) {
            continue;
        }

        // Two-node loop: A->B + B->A.
        if ( isset( $source_target_map[ $target_path ] ) && $source_target_map[ $target_path ] === $normalized_source ) {
            continue;
        }

        $source_target_map[ $normalized_source ] = $target_path;
        $sanitized_rules[]                       = [
            'source' => $normalized_source,
            'target' => $safe_target,
        ];
    }

    return $sanitized_rules;
}
add_filter( 'pre_update_option_bw_redirects', 'bw_redirects_enforce_save_time_safety', 10, 2 );

/**
 * Esegue il redirect sul frontend se l'URL richiesto corrisponde a uno dei pattern salvati,
 * rispettando i vincoli di autorità e sicurezza.
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
    $request_path_only  = bw_redirect_get_path_only( $normalized_request );

    if ( '' === $normalized_request || bw_is_protected_redirect_path( $request_path_only ) ) {
        bw_reset_redirect_hop_count();
        return;
    }

    $indexed_redirects = [];

    foreach ( $redirects as $redirect ) {
        if ( ! is_array( $redirect ) ) {
            continue;
        }

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

        // Protected-route gate on both source and target.
        if ( bw_is_protected_redirect_url( $normalized_source ) || bw_is_protected_redirect_url( $safe_target ) ) {
            continue;
        }

        if ( isset( $indexed_redirects[ $normalized_source ] ) ) {
            continue;
        }

        $indexed_redirects[ $normalized_source ] = $safe_target;
    }

    if ( ! isset( $indexed_redirects[ $normalized_request ] ) ) {
        bw_reset_redirect_hop_count();
        return;
    }

    $safe_target       = $indexed_redirects[ $normalized_request ];
    $current_full_url  = home_url( $normalized_request );
    $target_path       = bw_normalize_redirect_path( $safe_target );
    $target_path_only  = bw_redirect_get_path_only( $target_path );
    $max_redirect_hops = bw_get_redirect_max_hops();
    $current_hops      = bw_get_redirect_current_hop_count();

    // Fail-open su condizioni non sicure.
    if (
        '' === $target_path ||
        bw_is_protected_redirect_path( $target_path_only ) ||
        $current_full_url === $safe_target ||
        $target_path === $normalized_request
    ) {
        bw_reset_redirect_hop_count();
        return;
    }

    // Direct two-node runtime loop guard.
    if ( isset( $indexed_redirects[ $target_path ] ) ) {
        $reverse_target_path = bw_normalize_redirect_path( $indexed_redirects[ $target_path ] );

        if ( $reverse_target_path === $normalized_request ) {
            bw_reset_redirect_hop_count();
            return;
        }
    }

    // Hop-limit guard per evitare catene non convergenti.
    if ( $current_hops >= $max_redirect_hops ) {
        bw_reset_redirect_hop_count();
        return;
    }

    bw_set_redirect_hop_count( $current_hops + 1 );
    if ( wp_safe_redirect( $safe_target, 301 ) ) {
        exit;
    }

    bw_reset_redirect_hop_count();
}
add_action( 'template_redirect', 'bw_maybe_redirect_request', 10 );
