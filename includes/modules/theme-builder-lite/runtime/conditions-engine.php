<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_rule_int_list')) {
    function bw_tbl_rule_int_list($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $item = absint($item);
            if ($item > 0) {
                $result[$item] = $item;
            }
        }

        return array_values($result);
    }
}

if (!function_exists('bw_tbl_normalize_single_rule')) {
    function bw_tbl_normalize_single_rule($raw_rule)
    {
        if (!is_array($raw_rule)) {
            return null;
        }

        $type = isset($raw_rule['type']) ? sanitize_key((string) $raw_rule['type']) : '';
        if ('' === $type) {
            return null;
        }

        if ('post_category' === $type) {
            $terms = bw_tbl_rule_int_list(isset($raw_rule['terms']) ? $raw_rule['terms'] : []);
            if (empty($terms)) {
                return null;
            }

            return [
                'type' => 'post_category',
                'terms' => $terms,
            ];
        }

        if ('post_id' === $type) {
            $ids = bw_tbl_rule_int_list(isset($raw_rule['ids']) ? $raw_rule['ids'] : []);
            if (empty($ids)) {
                return null;
            }

            return [
                'type' => 'post_id',
                'ids' => $ids,
            ];
        }

        if ('page_id' === $type) {
            $ids = bw_tbl_rule_int_list(isset($raw_rule['ids']) ? $raw_rule['ids'] : []);
            if (empty($ids)) {
                return null;
            }

            return [
                'type' => 'page_id',
                'ids' => $ids,
            ];
        }

        return null;
    }
}

if (!function_exists('bw_tbl_normalize_display_rules')) {
    function bw_tbl_normalize_display_rules($raw)
    {
        $raw = is_array($raw) ? $raw : [];

        $include_raw = isset($raw['include']) && is_array($raw['include']) ? $raw['include'] : [];
        $exclude_raw = isset($raw['exclude']) && is_array($raw['exclude']) ? $raw['exclude'] : [];

        $include = [];
        foreach ($include_raw as $raw_rule) {
            $rule = bw_tbl_normalize_single_rule($raw_rule);
            if (is_array($rule)) {
                $include[] = $rule;
            }
        }

        $exclude = [];
        foreach ($exclude_raw as $raw_rule) {
            $rule = bw_tbl_normalize_single_rule($raw_rule);
            if (is_array($rule)) {
                $exclude[] = $rule;
            }
        }

        return [
            'include' => $include,
            'exclude' => $exclude,
        ];
    }
}

if (!function_exists('bw_tbl_get_template_display_rules')) {
    function bw_tbl_get_template_display_rules($template_id)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return [
                'include' => [],
                'exclude' => [],
            ];
        }

        $raw = get_post_meta($template_id, 'bw_tbl_display_rules_v1', true);
        return bw_tbl_normalize_display_rules($raw);
    }
}

if (!function_exists('bw_tbl_rule_applies_to_context_type')) {
    function bw_tbl_rule_applies_to_context_type($rule_type, $template_type)
    {
        $rule_type = sanitize_key((string) $rule_type);
        $template_type = sanitize_key((string) $template_type);

        if ('single_post' === $template_type) {
            return in_array($rule_type, ['post_category', 'post_id'], true);
        }

        if ('single_page' === $template_type) {
            return in_array($rule_type, ['page_id'], true);
        }

        if (in_array($template_type, ['search', 'error_404'], true)) {
            return false;
        }

        return false;
    }
}

if (!function_exists('bw_tbl_rule_matches_context')) {
    function bw_tbl_rule_matches_context($rule, $context)
    {
        if (!is_array($rule) || !is_array($context)) {
            return false;
        }

        $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
        $current_post_id = isset($context['post_id']) ? absint($context['post_id']) : 0;
        $current_page_id = isset($context['page_id']) ? absint($context['page_id']) : 0;
        $current_terms = isset($context['post_category_term_ids']) && is_array($context['post_category_term_ids']) ? $context['post_category_term_ids'] : [];

        if ('post_category' === $type) {
            $rule_terms = isset($rule['terms']) && is_array($rule['terms']) ? $rule['terms'] : [];
            return !empty(array_intersect($rule_terms, $current_terms));
        }

        if ('post_id' === $type) {
            $ids = isset($rule['ids']) && is_array($rule['ids']) ? $rule['ids'] : [];
            return $current_post_id > 0 && in_array($current_post_id, $ids, true);
        }

        if ('page_id' === $type) {
            $ids = isset($rule['ids']) && is_array($rule['ids']) ? $rule['ids'] : [];
            return $current_page_id > 0 && in_array($current_page_id, $ids, true);
        }

        return false;
    }
}

if (!function_exists('bw_tbl_template_matches_context')) {
    function bw_tbl_template_matches_context($template_id, $context)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0 || !is_array($context)) {
            return false;
        }

        $template_type = get_post_meta($template_id, 'bw_template_type', true);
        $template_type = bw_tbl_sanitize_template_type($template_type);
        $context_type = isset($context['template_type']) ? sanitize_key((string) $context['template_type']) : '';

        return '' !== $context_type && $template_type === $context_type;
    }
}

if (!function_exists('bw_tbl_template_matches_rules')) {
    function bw_tbl_template_matches_rules($template_id, $context, $wp_query_state = null)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0 || !is_array($context)) {
            return false;
        }

        if (!bw_tbl_template_matches_context($template_id, $context)) {
            return false;
        }

        $template_type = isset($context['template_type']) ? sanitize_key((string) $context['template_type']) : '';
        if ('' === $template_type) {
            return false;
        }

        $rules = bw_tbl_get_template_display_rules($template_id);
        $exclude_rules = isset($rules['exclude']) && is_array($rules['exclude']) ? $rules['exclude'] : [];
        $include_rules = isset($rules['include']) && is_array($rules['include']) ? $rules['include'] : [];

        foreach ($exclude_rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $rule_type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if (!bw_tbl_rule_applies_to_context_type($rule_type, $template_type)) {
                continue;
            }

            if (bw_tbl_rule_matches_context($rule, $context)) {
                return false;
            }
        }

        $applicable_include = [];
        foreach ($include_rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $rule_type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if (!bw_tbl_rule_applies_to_context_type($rule_type, $template_type)) {
                continue;
            }

            $applicable_include[] = $rule;
        }

        if (empty($applicable_include)) {
            return true;
        }

        foreach ($applicable_include as $rule) {
            if (bw_tbl_rule_matches_context($rule, $context)) {
                return true;
            }
        }

        return false;
    }
}
