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
        add_filter('woocommerce_add_error', [__CLASS__, 'suppress_message']);
        // Suppress success/info messages
        add_filter('woocommerce_add_notice', [__CLASS__, 'suppress_message'], 10, 2);
        add_filter('woocommerce_add_success', [__CLASS__, 'suppress_message']); // Back compat
    }

    /**
     * Filter messages to suppress unwanted ones.
     *
     * @param string $message The message.
     * @param string $notice_type Optional. The notice type (success, notice, error).
     * @return string The filtered message (empty string to suppress).
     */
    public static function suppress_message($message, $notice_type = '')
    {
        // List of strings/patterns to suppress
        $suppressed_strings = [
            'cannot add another',
            'non puoi aggiungere un altro',
            'choose product options', // "Please choose product options"
            'scegliere le opzioni del prodotto',
            'has been added to your cart', // Hide "added to cart" success spam
            'è stato aggiunto al tuo carrello',
            'have been added to your cart',
            'sono stati aggiunti al tuo carrello',
            'added to cart',
        ];

        // Clean the message string
        $clean_message = strip_tags($message);

        foreach ($suppressed_strings as $suppress) {
            if (stripos($clean_message, $suppress) !== false) {
                return '';
            }
        }

        return $message;
    }
}
