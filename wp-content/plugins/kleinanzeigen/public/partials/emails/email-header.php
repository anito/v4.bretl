<?php

/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 7.4.0
 */

if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

$timestamp = time();
$timezone = new DateTimeZone('Europe/Berlin');

$date = new DateTime();
$date->setTimestamp($timestamp);
$date->setTimezone($timezone);


$fmt = new IntlDateFormatter(
  'de-DE',
  IntlDateFormatter::FULL,
  IntlDateFormatter::FULL,
  'Europe/Berlin',
  IntlDateFormatter::GREGORIAN
);
$fmt->setPattern('EEEE, dd.MM.YYYY HH:mm');
$date = $fmt->format($date);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo get_bloginfo('name', 'display'); ?></title>
</head>

<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
  <table width="100%" id="outer_wrapper">
    <tr>
      <td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
      <td width="600">
        <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
          <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            <tr>
              <td align="center" valign="top">
                <div id="template_header_image">
                  <a href="<?php echo esc_attr(get_bloginfo('url', 'display')); ?>">
                    <?php
                    $img = get_option('woocommerce_email_header_image');
                    if ($img)
                    {
                      echo '<p style="margin-top:0;"><img width="250" src="' . esc_url($img) . '" alt="' . esc_attr(get_bloginfo('name', 'display')) . '" /></p>';
                    }
                    else
                    {
                      echo  '<h2 class="center">' . esc_attr(get_bloginfo('name', 'display')) . '</h2>';
                    }
                    ?>
                  </a>
                </div>
                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container">
                  <tr>
                    <td align="center" valign="top">
                      <!-- Header -->
                      <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
                        <tr id="header_wrapper">
                          <td class="title">
                            <h1><?php echo esc_html($email_heading); ?></h1>
                          </td>
                          <?php if (isset($thumbnail)) : ?>
                            <td class="thumbnail"></td>
                          <?php else : ?>
                            <td class="date"><small><?php echo $date; ?></small></td>
                          <?php endif ?>
                        </tr>
                      </table>
                      <!-- End Header -->
                    </td>
                  </tr>
                  <tr>
                    <td align="center" valign="top">
                      <!-- Body -->
                      <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                        <tr>
                          <td valign="top" id="body_content">
                            <!-- Content -->
                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top">
                                  <div id="body_content_inner">