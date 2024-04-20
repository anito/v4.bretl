<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load colors.
$bg        = get_option( 'woocommerce_email_background_color' );
$body      = get_option( 'woocommerce_email_body_background_color' );
$base      = get_option( 'woocommerce_email_base_color' );
$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );
$text      = get_option( 'woocommerce_email_text_color' );

// Pick a contrasting color for links.
$link_color = wc_hex_is_light( $base ) ? $base : $base_text;

if ( wc_hex_is_light( $body ) ) {
	$link_color = wc_hex_is_light( $base ) ? $base_text : $base;
}

$bg_darker_10    = wc_hex_darker( $bg, 10 );
$bg_lighter_10   = wc_hex_lighter( $bg, 10 );
$body_darker_10  = wc_hex_darker( $body, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$base_lighter_40 = wc_hex_lighter( $base, 40 );
$text_lighter_10 = wc_hex_lighter( $text, 10 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );
$text_lighter_30 = wc_hex_lighter( $text, 30 );
$text_lighter_40 = wc_hex_lighter( $text, 40 );
$text_lighter_50 = wc_hex_lighter( $text, 50 );
$text_lighter_60 = wc_hex_lighter( $text, 60 );
$text_lighter_70 = wc_hex_lighter( $text, 70 );
$text_lighter_80 = wc_hex_lighter( $text, 80 );
$text_lighter_90 = wc_hex_lighter( $text, 90 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>
body {
	background-color: <?php echo esc_attr( $bg ); ?>;
	padding: 0;
	text-align: center;
}

.text-lighter-10 {
	color: <?php echo esc_attr( $text_lighter_10 ); ?>;
}

.text-lighter-20 {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
}

.text-lighter-30 {
	color: <?php echo esc_attr( $text_lighter_30 ); ?>;
}

.text-lighter-40 {
	color: <?php echo esc_attr( $text_lighter_40 ); ?>;
}

.text-lighter-50 {
	color: <?php echo esc_attr( $text_lighter_50 ); ?>;
}

.text-lighter-60 {
	color: <?php echo esc_attr( $text_lighter_60 ); ?>;
}

.text-lighter-70 {
	color: <?php echo esc_attr( $text_lighter_70 ); ?>;
}

.text-lighter-80 {
	color: <?php echo esc_attr( $text_lighter_80 ); ?>;
}

.boxed {
  background: <?php echo esc_attr( $bg_lighter_10 ); ?>;
  padding: 0 5px;
  border-radius: 3px;
  border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
}

#outer_wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
}

#wrapper {
	margin: 0 auto;
	padding: 70px 0;
	-webkit-text-size-adjust: none !important;
	width: 100%;
	max-width: 600px;
}

#template_container {
	box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;
	background-color: <?php echo esc_attr( $body ); ?>;
	border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
	border-radius: 3px !important;
}

#template_header {
	background-color: <?php echo esc_attr( $base ); ?>;
	border-radius: 3px 3px 0 0 !important;
	color: <?php echo esc_attr( $base_text ); ?>;
	border-bottom: 0;
	font-weight: bold;
	line-height: 100%;
	vertical-align: middle;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

#template_header .title {
	padding: 36px 48px;
	display: block;
}

#template_header .date {
	font-weight: 100;
	vertical-align: bottom;
	text-align: right;
	padding: 10px;
}

#template_header .thumbnail {
	margin: 0;
  padding: 0;
	width: 117px;
  height: 117px;
  border: 0 none;
  border-radius: 0;
	overflow: hidden;
	position: relative;
	background-image: url(<?php echo $thumbnail ?? ''; ?>);
	background-size: cover;
  background-position: center;
}

#template_header .thumbnail::after {
    content: '';
    position: absolute;
		z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
}

#template_header h1,
#template_header h1 a {
	color: <?php echo esc_attr( $base_text ); ?>;
	background-color: inherit;
}

#template_header_image h2 {
	color: <?php echo esc_attr( $base ); ?>;
	margin-bottom: 10px;
	font-size: 2em;
}

#template_header_image img {
	margin-left: 0;
	margin-right: 0;
}

#template_footer td {
	padding: 0;
	border-radius: 6px;
}

#template_footer #credit {
	border: 0;
	color: <?php echo esc_attr( $text_lighter_40 ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 12px;
	line-height: 150%;
	text-align: center;
	padding: 24px 0;
}

#template_footer #credit p {
	margin: 0 0 16px;
}

#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
	padding: 48px 48px 32px;
}

#body_content table td td {
	padding: 12px;
}

#body_content table td th {
	padding: 12px;
}

#body_content td ul.wc-item-meta {
	font-size: small;
	margin: 1em 0 0;
	padding: 0;
	list-style: none;
}

#body_content td ul.wc-item-meta li {
	margin: 0.5em 0 0;
	padding: 0;
}

#body_content td ul.wc-item-meta li p {
	margin: 0;
}

#body_content p {
	margin: 0 0 16px;
}

#body_content_inner {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 14px;
	line-height: 150%;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
	vertical-align: middle;
}

.address {
	padding: 12px;
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
}

.text {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

.link {
  color: <?php echo esc_attr( $link_color ); ?>;
}

.center {
  text-align: center;
}

.normal {
  font-style: normal;
}

.italic {
  font-style: italic;
}

.bold {
  font-weight: 600;
}

#body_content .margin-top-10 {
  margin-top: 10px;
}

#body_content .margin-right-10 {
  margin-right: 10px;
}

#body_content .margin-bottom-10 {
  margin-bottom: 10px;
}

#body_content .margin-left-10 {
  margin-left: 10px;
}

#body_content .margin-top-20 {
  margin-top: 20px;
}

#body_content .margin-right-20 {
  margin-right: 20px;
}

#body_content .margin-bottom-20 {
  margin-bottom: 20px;
}

#body_content .margin-left-20 {
  margin-left: 20px;
}

#body_content .margin-top-30 {
  margin-top: 30px;
}

#body_content .margin-right-30 {
  margin-right: 30px;
}

#body_content .margin-bottom-30 {
  margin-bottom: 30px;
}

#body_content .margin-left-30 {
  margin-left: 30px;
}

#body_content .margin-top-40 {
  margin-top: 40px;
}

#body_content .margin-right-40 {
  margin-right: 40px;
}

#body_content .margin-bottom-40 {
  margin-bottom: 40px;
}

#body_content .margin-left-40 {
  margin-left: 40px;
}

#body_content .margin-top-50 {
  margin-top: 50px;
}

#body_content .margin-right-50 {
  margin-right: 50px;
}

#body_content .margin-bottom-50 {
  margin-bottom: 50px;
}

#body_content .margin-left-50 {
  margin-left: 50px;
}

#body_content .margin-10 {
  margin: 10px;
}

#body_content .margin-20 {
  margin: 20px;
}

#body_content .margin-30 {
  margin: 30px;
}

#body_content .margin-40 {
  margin: 50px;
}

#body_content .margin-50 {
  margin: 50px;
}

#body_content .warning {
	color: #F44336;
}

#body_content .line-b {
	border-bottom: 1px solid;
}

#body_content .line-t {
	border-top: 1px solid;
}

.button {
  display: inline-block;
  width: 180px;
  border: 2px solid <?php echo esc_attr( $link_color ); ?>; 
  border-radius: 5px;
  min-height: 26px;
  line-height: 2.18181818;
  padding: 5px 8px;
  text-align: center;
  margin: 10px;
  text-decoration: none;
}

.button.button-primary {
  color: <?php echo esc_attr( $base_text ); ?>;
  background-color: <?php echo esc_attr( $link_color ); ?>; 
  border: 0 none;
}

.thumbnail {
  margin: 10px;
  padding: 2px;
  border: 2px solid <?php echo esc_attr( $link_color ); ?>
  border-radius: 8px;
}

.status-field {
	margin: 20px;
}

.left {
	float: left;
}

.right {
	float: right;
}

h1 {
	color: <?php echo esc_attr( $base ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 30px;
	font-weight: 300;
	line-height: 150%;
	margin: 0;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
}

h2 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 18px;
	font-weight: bold;
	line-height: 130%;
	margin: 0 0 18px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 16px;
	font-weight: bold;
	line-height: 130%;
	margin: 16px 0 8px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
	color: <?php echo esc_attr( $link_color ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

img {
	border: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	height: auto;
	outline: none;
	text-decoration: none;
	text-transform: capitalize;
	vertical-align: middle;
	margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: 10px;
	max-width: 100%;
}

.w-50 {
	width: 50%;
}

.inline-block {
	display: inline-block;
}

.flex {
	display: flex;
}

.inline-block {
	display: inline-block;
}

.success {
	color: green;
}

.warning {
	color: orange;
}

.error {
	color: red;
}

.large {
	font-size: 14px;
}

.medium {
	font-size: 12px;
}

.small {
	font-size: 10px;
}

/**
 * Media queries are not supported by all email clients, however they do work on modern mobile
 * Gmail clients and can help us achieve better consistency there.
 */
@media screen and (max-width: 600px) {
	#header_wrapper .title {
		padding: 27px 36px !important;
		font-size: 24px;
	}

	#body_content table > tbody > tr > td {
		padding: 10px !important;
	}

	#body_content_inner {
		font-size: 10px !important;
	}
}
<?php
