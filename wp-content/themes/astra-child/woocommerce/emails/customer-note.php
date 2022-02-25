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

$hide_admin=false;
$show_prices = false;
$show_prices = apply_filters('gpls_woo_rfq_show_prices_customer_email', $show_prices);

if ($show_prices == false) {
    // add_filter('woocommerce_email_order_items_args', 'filter_gpls_woo_rfq_add_hide_prices_to_wc_emails', 100, 1);
} else {
    // add_filter('woocommerce_email_order_items_args', 'filter_gpls_woo_rfq_add_hide_prices_to_wc_emails', 100, 1);
}



/**
 * @hooked WC_Emails::email_header() Output the email header
 */

do_action('woocommerce_email_header', $email_heading, $email);


?>


    <p><?php printf(__("Hello, a note has just been added to your order:", 'woo-rfq-for-woocommerce')); ?></p>

    <blockquote><?php echo wpautop(wptexturize($customer_note)) ?></blockquote>

<?php



    $include_respond_link = get_post_meta($order->get_id(), '_include_respond_link', true);
    $include_respond_link_url = home_url() . '/customer-respond/?respond_to_offer=true&id=' . $order->get_id() . '&key=' . $order->get_order_key();

    if ($include_respond_link == "yes") {

        printf('' . __('You can respond by clicking %s', 'woo-rfq-for-woocommerce'), '<a href="' . esc_url($include_respond_link_url) . '">' . __('here', 'woo-rfq-for-woocommerce') . '</a>.');

    }

?>

    <p><?php printf(__("For your reference, your order details are shown below.", 'woo-rfq-for-woocommerce')); ?></p>
    <h2><?php printf(__('Order #%s', 'woo-rfq-for-woocommerce'), $order->get_order_number()); ?></h2>

    <table class="td" cellspacing="0" cellpadding="6"
           style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <thead>
        <tr>
            <th class="td" scope="col"
                style="text-align:left;"><?php printf(__('Product', 'woo-rfq-for-woocommerce')); ?></th>
            <th class="td" scope="col"
                style="text-align:left;"><?php printf(__('Quantity', 'woo-rfq-for-woocommerce')); ?></th>
            <?php if ($show_prices == 'yes')  : ?>
                <th class="td" scope="col"
                    style="text-align:left;"><?php printf(__('Price', 'woo-rfq-for-woocommerce')); ?></th>
            <?php endif; ?>

        </tr>
        </thead>
        <tbody>
        <?php


        ?>

        <?php echo rfqtk_get_email_order_items($order, array(
            'items' =>$order->get_items(),
            'show_sku' => false,
            'show_image' => true,
            'image_size' => array(128, 128),
            'plain_text' => $plain_text,
            'show_prices' => $show_prices,
            'hide_admin'=>false,

        )); ?>

        </tbody>
        <tfoot>
        <?php

        ?>
        <?php if ($show_prices == true)  : ?>
            <?php
            if ($totals = $order->get_order_item_totals()) {
                $i = 0;
                foreach ($totals as $total) {
                    $i++;
                    ?>
                    <tr>
                    <th class="td" scope="row" colspan="2"
                        style="text-align:left; <?php if ($i == 1) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
                    <td class="td"
                        style="text-align:left; <?php if ($i == 1) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
                    </tr><?php
                }
            }
            ?>

        <?php endif; ?>

        </tfoot>
    </table>

<?php

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



if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}


do_action('woocommerce_email_footer', $email);

