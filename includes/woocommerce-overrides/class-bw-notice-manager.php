<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BW_Notice_Manager
 * 
 * Manages and suppresses specific WooCommerce notices.
 */
class BW_Notice_Manager
{

    /**
     * Initialize hooks.
     */
    public static function init()
    {
        // Suppress specific error messages
        add_filter('woocommerce_add_error', [__CLASS__, 'suppress_errors']);
    }

    /**
     * Filter error messages to suppress unwanted ones.
     *
     * @param string $error The error message.
     * @return string The filtered error message (empty string to suppress).
     */
    public static function suppress_errors($error)
    {
        // List of strings/patterns to suppress
        // "You cannot add another..." corresponds to 'cannot_add_another' text in WC
        $suppressed_strings = [
            'cannot add another',
            'non puoi aggiungere un altro', // Italian common translation
            // Add more snippets here if needed
        ];

        // Clean the error string (strip tags to ensure matching)
        $clean_error = strip_tags($error);

        foreach ($suppressed_strings as $suppress) {
            if (stripos($clean_error, $suppress) !== false) {
                // Log suppression if debugging is needed
                // error_log( 'BW Notice Manager suppressed: ' . $clean_error );
                return '';
            }
        }

        return $error;
    }
}
