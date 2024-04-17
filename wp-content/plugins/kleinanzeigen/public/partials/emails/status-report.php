<?php

/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 6.0.0
 */

/**
 * Thumbnail
 * <p class="center"><?php printf('<a href="%1$s"><img width="140" src="%2$s" class="thumbnail"/></a>', esc_html($permalink), esc_html($thumbnail)); ?></p>
 */

defined('ABSPATH') || exit;

do_action('kleinanzeigen_email_header', $email_heading); ?>

<h3><?php echo (esc_html__('Summary of products based on Kleinanzeigen', 'kleinanzeigen')); ?></h3>
<div class="margin-top-20 line-b">
  <div class="margin-30">
    <?php foreach ($tasks as $key => $task) : ?>
      <p class="margin-bottom-20">
        <span class="italic"><?php echo $task['name'] ?>: </span>
        <span class="right <?php echo $task['count'] ? $task['class'] ?? '' : ''; ?>"><?php echo $task['count'] ?></span>
      </p>
    <?php endforeach ?>
  </div>
</div>
<p class="small"><?php printf(esc_html__('Manage your Kleinanzeigen.de products in your online store here: %1$s', 'kleinanzeigen'), esc_html($plugin_link)); ?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content)
{
  echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('kleinanzeigen_email_footer');
