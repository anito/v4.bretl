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

do_action('kleinanzeigen_email_header', compact('email_heading', 'thumbnail')); ?>

<p><?php printf(esc_html__('Hi %s,', 'kleinanzeigen'), esc_html($blogname)); ?></p>
<p><?php printf(esc_html__('The product %1$s has been automatically created based on the following ad:', 'kleinanzeigen'), '<strong>' . esc_html($product_title) . '</strong>'); ?></p>
<p><?php echo esc_html($kleinanzeigen_url); ?></p>
<p class="center margin-top-20">
  <span><?php echo '<a href="' . esc_html($previewlink) . '" class="button button-primary">' . esc_html__('Preview', 'kleinanzeigen') . '</a>'; ?></span>
  <span><?php echo '<a href="' . esc_html($edit_link) . '" class="button button-primary">' . esc_html__('Edit', 'kleinanzeigen') . '</a>'; ?></span>
</p>
<div class="line-b">
  <?php if ('publish' !== $post_status) : ?>
    <p class="small"><?php _e('☞ This product hasn\'t been published yet. Only administrators and shopmanagers may view or edit unpublished products.', 'kleinanzeigen'); ?></p>
  <?php endif ?>
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
