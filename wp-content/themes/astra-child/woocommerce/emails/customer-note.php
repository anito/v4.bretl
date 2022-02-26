<?php


/**
 * Customer note email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-note.php.
 *
 */

if (!defined('ABSPATH')) {
	exit;
}

add_filter('wbp_woo_show_prices_customer_email', function($args) {
	return array(
			'to_customer' => true
	);
});

/**
 * @hooked WC_Emails::email_header() Output the email header
 */

do_action('woocommerce_email_header', $email_heading, $email);


?>

<p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>
<p><?php esc_html_e('The following note has been added to your order:', 'woocommerce'); ?></p>

<blockquote class="blockquote-customer-note"><?php echo wpautop( wptexturize( make_clickable( $customer_note ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></blockquote>

<p><?php esc_html_e('As a reminder, here are your order details:', 'woocommerce'); ?></p>

<?php

add_filter('woocommerce_email_order_items_args', function ($args) {
	$args['show_image'] = true;
	$args['image_size'] = array(64, 64);
	return $args;
});
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);



/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
	echo wp_kses_post(wpautop(wptexturize($additional_content)));
}


do_action('woocommerce_email_footer', $email);
