<?php
/**
 *  Function to change email address
 **/
// add_filter( 'wp_mail_from', 'wpb_sender_email' );
function wpb_sender_email( $original_email_address ) {
    $url = home_url();
    $pattern = '/(https?):\/\/(.*)/';
    preg_match( $pattern, $url, $matches );
    return 'onlineshop@' . $matches[2];
}

/**
 * Hooking up our functions to WordPress filter
 * Function to change sender name
 */
// add_filter( 'wp_mail_from_name', 'wpb_sender_name' );
function wpb_sender_name( $original_email_from ) {
    return 'Lehmann Trading GmbH';
}