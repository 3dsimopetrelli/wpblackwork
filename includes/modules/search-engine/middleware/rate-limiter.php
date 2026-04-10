<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_request_fingerprint()
{
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'unknown';
    $user_agent = substr($user_agent, 0, 128);

    return md5($remote_addr . '|' . $user_agent);
}

function bw_fpw_get_rate_limit_config($action_key, $is_logged_in)
{
    if ($is_logged_in) {
        $limits = [
            'bw_fpw_get_subcategories' => ['limit' => 300, 'window' => 60],
            'bw_fpw_get_tags' => ['limit' => 300, 'window' => 60],
            'bw_fpw_filter_posts' => ['limit' => 200, 'window' => 60],
            'bw_header_live_search' => ['limit' => 180, 'window' => 60],
        ];
    } else {
        $limits = [
            'bw_fpw_get_subcategories' => ['limit' => 60, 'window' => 60],
            'bw_fpw_get_tags' => ['limit' => 50, 'window' => 60],
            'bw_fpw_filter_posts' => ['limit' => 35, 'window' => 60],
            'bw_header_live_search' => ['limit' => 60, 'window' => 60],
        ];
    }

    return isset($limits[$action_key]) ? $limits[$action_key] : ['limit' => 40, 'window' => 60];
}

function bw_fpw_get_rate_limit_cookie_name()
{
    return 'bw_fpw_rl';
}

function bw_fpw_get_rate_limit_cookie_fingerprint_hash($fingerprint)
{
    return hash('sha256', (string) $fingerprint);
}

function bw_fpw_get_rate_limit_cookie_signature($payload)
{
    return hash_hmac('sha256', (string) $payload, wp_salt('auth'));
}

function bw_fpw_get_rate_limit_block_transient_key($action_key, $fingerprint)
{
    return 'bw_fpw_rl_block_' . md5((string) $action_key . '|' . (string) $fingerprint);
}

function bw_fpw_get_rate_limit_counter_transient_key($action_key, $fingerprint)
{
    return 'bw_fpw_rl_' . md5((string) $action_key . '|' . (string) $fingerprint);
}

function bw_fpw_get_rate_limit_cache_group()
{
    return 'bw_fpw_rate_limit';
}

function bw_fpw_get_rate_limit_cached_value($key, &$found = false)
{
    if (wp_using_ext_object_cache()) {
        $value = wp_cache_get($key, bw_fpw_get_rate_limit_cache_group(), false, $found);
        return $value;
    }

    $found = false;
    $value = get_transient($key);

    if (false !== $value) {
        $found = true;
    }

    return $value;
}

function bw_fpw_set_rate_limit_cached_value($key, $value, $ttl)
{
    $ttl = max(1, (int) $ttl);

    if (wp_using_ext_object_cache()) {
        return wp_cache_set($key, $value, bw_fpw_get_rate_limit_cache_group(), $ttl);
    }

    return set_transient($key, $value, $ttl);
}

function bw_fpw_add_rate_limit_cached_value($key, $value, $ttl)
{
    $ttl = max(1, (int) $ttl);

    if (wp_using_ext_object_cache()) {
        return wp_cache_add($key, $value, bw_fpw_get_rate_limit_cache_group(), $ttl);
    }

    if (false !== get_transient($key)) {
        return false;
    }

    return set_transient($key, $value, $ttl);
}

function bw_fpw_increment_rate_limit_cached_counter($key, $ttl)
{
    $ttl = max(1, (int) $ttl);

    if (!wp_using_ext_object_cache()) {
        $bucket = get_transient($key);

        if (!is_array($bucket) || !isset($bucket['count'])) {
            $bucket = ['count' => 0];
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        set_transient($key, $bucket, $ttl);

        return (int) $bucket['count'];
    }

    if (bw_fpw_add_rate_limit_cached_value($key, 1, $ttl)) {
        return 1;
    }

    $count = wp_cache_incr($key, 1, bw_fpw_get_rate_limit_cache_group());

    if (false !== $count) {
        return (int) $count;
    }

    $found = false;
    $existing = bw_fpw_get_rate_limit_cached_value($key, $found);
    $count = $found ? ((int) $existing + 1) : 1;
    bw_fpw_set_rate_limit_cached_value($key, $count, $ttl);

    return $count;
}

function bw_fpw_get_rate_limit_cookie_state_result($fingerprint, $window_seconds)
{
    $cookie_name = bw_fpw_get_rate_limit_cookie_name();
    $cookie_value = isset($_COOKIE[$cookie_name]) ? (string) wp_unslash($_COOKIE[$cookie_name]) : '';

    if ('' === $cookie_value || false === strpos($cookie_value, '.')) {
        return [
            'valid' => false,
            'reason' => 'missing',
            'state' => [],
        ];
    }

    [$encoded_payload, $signature] = explode('.', $cookie_value, 2);

    if ('' === $encoded_payload || '' === $signature) {
        return [
            'valid' => false,
            'reason' => 'invalid',
            'state' => [],
        ];
    }

    $expected_signature = bw_fpw_get_rate_limit_cookie_signature($encoded_payload);

    if (!hash_equals($expected_signature, $signature)) {
        return [
            'valid' => false,
            'reason' => 'invalid',
            'state' => [],
        ];
    }

    $json = base64_decode(strtr($encoded_payload, '-_', '+/'), true);
    $state = json_decode(is_string($json) ? $json : '', true);

    if (!is_array($state)) {
        return [
            'valid' => false,
            'reason' => 'invalid',
            'state' => [],
        ];
    }

    $window_start = isset($state['w']) ? (int) $state['w'] : 0;
    $fingerprint_hash = isset($state['f']) ? (string) $state['f'] : '';
    $counts = isset($state['c']) && is_array($state['c']) ? $state['c'] : [];
    $expected_hash = bw_fpw_get_rate_limit_cookie_fingerprint_hash($fingerprint);
    $current_window_start = (int) (floor(time() / max(1, (int) $window_seconds)) * max(1, (int) $window_seconds));

    if ($window_start !== $current_window_start || $fingerprint_hash !== $expected_hash) {
        return [
            'valid' => false,
            'reason' => 'stale',
            'state' => [],
        ];
    }

    return [
        'valid' => true,
        'reason' => 'ok',
        'state' => [
            'w' => $window_start,
            'f' => $fingerprint_hash,
            'c' => $counts,
        ],
    ];
}

function bw_fpw_increment_rate_limit_server_counter($action_key, $fingerprint, $window_seconds)
{
    $transient_key = bw_fpw_get_rate_limit_counter_transient_key($action_key, $fingerprint);
    return bw_fpw_increment_rate_limit_cached_counter($transient_key, $window_seconds);
}

function bw_fpw_set_rate_limit_cookie_state($fingerprint, $window_seconds, $state)
{
    if (headers_sent()) {
        return false;
    }

    $window_seconds = max(1, (int) $window_seconds);
    $window_start = (int) (floor(time() / $window_seconds) * $window_seconds);
    $expires = $window_start + $window_seconds;
    $payload = [
        'w' => $window_start,
        'f' => bw_fpw_get_rate_limit_cookie_fingerprint_hash($fingerprint),
        'c' => isset($state['c']) && is_array($state['c']) ? $state['c'] : [],
    ];
    $payload_json = wp_json_encode($payload);

    if (!is_string($payload_json) || '' === $payload_json) {
        return false;
    }

    $encoded_payload = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');
    $cookie_value = $encoded_payload . '.' . bw_fpw_get_rate_limit_cookie_signature($encoded_payload);
    $cookie_options = [
        'expires' => $expires,
        'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    $set = setcookie(bw_fpw_get_rate_limit_cookie_name(), $cookie_value, $cookie_options);

    if ($set) {
        $_COOKIE[bw_fpw_get_rate_limit_cookie_name()] = $cookie_value;
    }

    return $set;
}

function bw_fpw_is_throttled_request($action_key)
{
    $is_logged_in = is_user_logged_in();
    $config = bw_fpw_get_rate_limit_config($action_key, $is_logged_in);

    if ($is_logged_in) {
        $fingerprint = 'u' . get_current_user_id();
        $transient_key = bw_fpw_get_rate_limit_counter_transient_key($action_key, $fingerprint);
        $count = bw_fpw_increment_rate_limit_cached_counter($transient_key, (int) $config['window']);

        return $count > (int) $config['limit'];
    }

    $fingerprint = bw_fpw_get_request_fingerprint();
    $window_seconds = max(1, (int) $config['window']);
    $block_key = bw_fpw_get_rate_limit_block_transient_key($action_key, $fingerprint);
    $cookie_result = bw_fpw_get_rate_limit_cookie_state_result($fingerprint, $window_seconds);
    $cookie_state = isset($cookie_result['state']) && is_array($cookie_result['state']) ? $cookie_result['state'] : [];
    $current_window_start = (int) (floor(time() / $window_seconds) * $window_seconds);

    $is_blocked = false;
    bw_fpw_get_rate_limit_cached_value($block_key, $is_blocked);

    if ($is_blocked) {
        return true;
    }

    if (empty($cookie_result['valid'])) {
        $server_count = bw_fpw_increment_rate_limit_server_counter($action_key, $fingerprint, $window_seconds);

        $cookie_state = [
            'w' => $current_window_start,
            'f' => bw_fpw_get_rate_limit_cookie_fingerprint_hash($fingerprint),
            'c' => [],
        ];

        $counts = isset($cookie_state['c']) && is_array($cookie_state['c']) ? $cookie_state['c'] : [];
        $counts[$action_key] = $server_count;
        $cookie_state['c'] = $counts;
        bw_fpw_set_rate_limit_cookie_state($fingerprint, $window_seconds, $cookie_state);

        if ($server_count > (int) $config['limit']) {
            $ttl = max(1, ($current_window_start + $window_seconds) - time());
            bw_fpw_set_rate_limit_cached_value($block_key, 1, $ttl);

            return true;
        }

        return false;
    }

    $counts = isset($cookie_state['c']) && is_array($cookie_state['c']) ? $cookie_state['c'] : [];
    $counts[$action_key] = isset($counts[$action_key]) ? ((int) $counts[$action_key] + 1) : 1;
    $cookie_state['c'] = $counts;

    if (!bw_fpw_set_rate_limit_cookie_state($fingerprint, $window_seconds, $cookie_state)) {
        $server_count = bw_fpw_increment_rate_limit_server_counter($action_key, $fingerprint, $window_seconds);

        if ($server_count > (int) $config['limit']) {
            $ttl = max(1, ($current_window_start + $window_seconds) - time());
            bw_fpw_set_rate_limit_cached_value($block_key, 1, $ttl);

            return true;
        }

        return false;
    }

    if ((int) $counts[$action_key] > (int) $config['limit']) {
        $ttl = max(1, ($current_window_start + $window_seconds) - time());
        bw_fpw_set_rate_limit_cached_value($block_key, 1, $ttl);

        return true;
    }

    return false;
}

function bw_fpw_send_throttled_response($action_key, $widget_id = '')
{
    if ('bw_fpw_filter_posts' === $action_key) {
        $safe_widget_id = bw_fpw_normalize_widget_id($widget_id);
        ob_start();
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php esc_html_e('No results found.', 'bw-elementor-widgets'); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr($safe_widget_id); ?>">
                <?php esc_html_e('RESET FILTERS', 'bw-elementor-widgets'); ?>
            </button>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(
            [
                'html' => $html,
                'tags_html' => '',
                'available_tags' => [],
                'has_posts' => false,
                'throttled' => true,
            ]
        );
    }

    wp_send_json_success([]);
}
