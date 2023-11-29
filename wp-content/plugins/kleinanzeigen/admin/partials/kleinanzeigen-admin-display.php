<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap" id="kleinanzeigen-admin-display">
  <div id="icon-themes" class="icon32"></div>
  <h2><?php echo __('Overview', 'kleinanzeigen') ?></h2>
  <!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
  <?php settings_errors(); ?>
  <form method="POST" action="options.php">
  </form>



</div>