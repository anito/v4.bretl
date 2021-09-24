<?php
/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.

 * @author      WooThemes
 * @package     WooCommerce/Templates/Emails
 * @version     4.0.0
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 4.0.0

 * changes: hardcoded path to email header logo: $url_logo
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$image = get_option('woocommerce_email_header_image');

// Load colours
$bg = get_option('woocommerce_email_background_color');
$body = get_option('woocommerce_email_body_background_color');
$base = get_option('woocommerce_email_base_color');
$base_text = wc_light_or_dark($base, '#202020', '#ffffff');
$text = get_option('woocommerce_email_text_color');

$bg_darker_10 = wc_hex_darker($bg, 10);
$base_lighter_20 = wc_hex_lighter($base, 20);
$text_lighter_20 = wc_hex_lighter($text, 20);

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$wrapper = "
    background-color: " . esc_attr($bg) . ";
    width:100%;
    -webkit-text-size-adjust:none !important;
    margin:0;
    padding: 70px 0 70px 0;
";
$template_container = "
    box-shadow:0 0 0 3px rgba(0,0,0,0.025) !important;
    border-radius:6px !important;
    background-color: " . esc_attr($body) . ";
    border: 1px solid $bg_darker_10;
    border-radius:6px !important;
";
$template_header = "
    background-color: " . esc_attr($base) . ";
    color: $base_text;
    border-top-left-radius:6px !important;
    border-top-right-radius:6px !important;
    border-bottom: 0;
    font-family:Arial;
    font-weight:bold;
    line-height:100%;
    vertical-align:middle;
";
$body_content = "
    background-color: " . esc_attr($body) . ";
    border-radius:6px !important;
";
$body_content_inner = "
    color: $text_lighter_20;
    font-family:Arial;
    font-size:14px;
    line-height:150%;
    text-align:left;
";
$header_content_h1 = "
    color: " . esc_attr($base_text) . ";
    margin:0;
    padding: 28px 24px;
    text-shadow: 0 1px 0 $base_lighter_20;
    display:block;
    font-family:Arial;
    font-size:30px;
    font-weight:bold;
    text-align:left;
    line-height: 150%;
";
$header_image = "
	width: 300px;"
;
?>
<!DOCTYPE html>
<html <?php language_attributes();?>>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset');?>" />
		<title><?php echo get_bloginfo('name', 'display'); ?></title>
        <style type="text/css">
            h2 {
                font-size: 1.3em !important;
            }
            h3 {
                font-size: 1em !important;
            }
            h4 {
                font-size: 1em !important;
            }
            .wc-gzd-item-desc {
                margin: 0 !important;
                margin-top: 0.5em !important;
            }
            .wc-gzd-item-desc p {
                font-size: 0.9em !important;
                line-height: 1em !important;
                padding-bottom: 5px !important;
                margin: 0 !important;
            }
            .wc-gzd-email-attach-post {
                font-size: 0.8em !important;
            }
            .wc-gzd-email-attach-post h4, .wc-gzd-email-attach-post .wc-gzd-email-attached-content h4 {
                font-size: 14px !important;
            }
            .wc-gzd-email-attach-post {
                margin: 1em 0 !important;
            }
            .wc-gzd-email-attached-content h1, .wc-gzd-email-attached-content h2 {
                font-size: 15px !important;
                text-align: left !important;
                text-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 100% !important;
            }
            a, a:hover, a:visited, a:focus {
                color: #333 !important;
                text-decoration: underline;
            }
        </style>
        </head>
    	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="<?php echo $wrapper; ?>" >
            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
                <tr>
                    <td align="center" valign="top">
                        <div id="template_header_image">
                            <?php if ($image): ?>
                                <p style="margin-top: 0;">
                                    <a href="<?php echo esc_url(home_url('/')); ?>">
                                        <img style="<?php echo $header_image; ?>" src="<?php echo $image; ?>" alt="<?php echo get_bloginfo('name', 'display') ?>" />
                                    </a>
                                </p>
                            <?php else: ?>
                                <a href="<?php echo esc_url(home_url('/')); ?>">
                                    <h1 style="font-size: 1em; color: #333; text-align: center;"><?php echo bloginfo('name', 'display'); ?></h1>
                                </a>
                            <?php endif;?>
                        </div>
                            <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
							<tr>
								<td align="center" valign="top">
									<!-- Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header">
										<tr>
											<td id="header_wrapper">
												<h1><?php echo $email_heading; ?></h1>
											</td>
										</tr>
									</table>
									<!-- End Header -->
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Body -->
									<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
										<tr>
											<td valign="top" id="body_content">
												<!-- Content -->
												<table border="0" cellpadding="20" cellspacing="0" width="100%">
													<tr>
														<td valign="top">
															<div id="body_content_inner">
