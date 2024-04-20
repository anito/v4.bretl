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

do_action('kleinanzeigen_email_header', compact('email_heading'));

$first = array_shift($tree);

function buildTree($tree, $level = 0)
{
?>
  <?php foreach ($tree as $key => $leave) :
    $level        = $leave['level'];
    $margin_left  = $level * 20;
    switch ($level)
    {
      case 0:
        $font_size    = 'large';
        $font_style   = 'normal';
        $font_weight  = 'bold';
        break;
      case 1:
        $font_size    = 'medium';
        $font_style   = 'normal';
        $font_weight  = '';
        break;
      case 2:
        $font_size    = 'medium';
        $font_style   = 'normal';
        $font_weight  = '';
        break;
      default:
    }
  ?>

    <div class="inner-section level-<?php echo $leave['level'] ?>">
      <p class="margin-bottom-20 <?php echo "margin-left-{$margin_left} {$font_size} {$font_weight} {$font_style}" ?>">
        <span><?php echo $leave['text']; ?></span>
        <span class="right normal margin-left-10" style="min-width: 30px; text-align: right;"><?php echo count($leave['items']); ?></span>
        <?php if (isset($leave['info']) && count($leave['items'])) : ?>
          <span class="right boxed normal"><small><?php echo $leave['info'] ?></small></span>
        <?php endif ?>
      </p>
      <?php if (isset($leave['childs'])) buildTree($leave['childs'], ++$level); ?>
    </div>

<?php endforeach;
} ?>

<h3 class="center margin-bottom-40"><?php echo (esc_html__('Summary of products based on Kleinanzeigen', 'kleinanzeigen')); ?></h3>
<div class="margin-top-20">
  <div class="margin-30">
    <div class="line-b margin-bottom-30 margin-top-20">
      <h4 class="margin-bottom-30">
        <span class="normal"><?php echo $first['text'] ?></span>
        <span class="right"><?php echo count($first['items']); ?></span>
      </h4>
    </div>
    <?php buildTree($tree); ?>
  </div>
  <div class="line-t margin-bottom-20 margin-top-30"></div>
  <div class="medium">
    <span class="inline-block w-50 bold"><?php echo __('Setting for orphaned ads:', 'kleinanzeigen'); ?></span>
    <span class="inline-block italic"><?php echo $inactive_ad_text; ?></span>
  </div>
  <div class="line-t margin-bottom-30 margin-top-20"></div>
</div>

<p class="small"><?php printf(esc_html__('Manage your Kleinanzeigen.de products in your online store here: %1$s', 'kleinanzeigen'), esc_html($plugin_link)); ?></p>
<?php if ($next_event) : ?>
  <p class="small inline-block line-t margin-top-40 text-lighter-50" style="font-weight: 200;"><?php echo sprintf(__('Next scheduled report delivery on %s', 'kleinanzeigen'), $next_event); ?></p>
<?php endif ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content)
{
  echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('kleinanzeigen_email_footer');
