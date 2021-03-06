<?php
require_once __DIR__ . '/includes/sender_email.php';
require_once __DIR__ . '/includes/duplicate_content.php';
require_once __DIR__ . '/includes/register_wc_taxonomies.php';

/**
 * Define Constants
 */
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.3');

/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */
function child_enqueue_styles()
{
  wp_enqueue_style('astra-child-theme', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
}
add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);

/**
 * Admin Styles fpr WC Screens
 */
function wbp_admin_style($hook)
{
  $screen    = get_current_screen();
  $screen_id = $screen ? $screen->id : '';
  if (in_array($screen_id, wc_get_screen_ids())) {
    wp_register_style('wbp_woocommerce_admin_styles', get_stylesheet_directory_uri() . '/style-admin.css', array(), CHILD_THEME_ASTRA_CHILD_VERSION);
    wp_enqueue_style('wbp_woocommerce_admin_styles');
  }
}
add_action('admin_enqueue_scripts', 'wbp_admin_style');

function wbp_check_sale_before_save($post_id, $post)
{
  if (!class_exists('WooCommerce', false)) {
    return;
  }

  $product = wc_get_product($post_id);

  if (!$product) {
    return 0;
  }

  if ($product->is_type('variation')) {
    $variation = new WC_Product_Variation($product);
    $post_id = $variation->get_parent_id();
    $product = wc_get_product($post_id);
  }

  require_once __DIR__ . '/includes/product_term_handler.php';
  wbp_process_sale($post_id, $post);
}
add_action("save_post", "wbp_check_sale_before_save", 99, 3);

function wbp_check_featured_before_save($post)
{
  require_once __DIR__ . '/includes/product_term_handler.php';
  wbp_process_featured($post);
}
add_action("woocommerce_before_product_object_save", "wbp_check_featured_before_save", 99, 2);

function add_scripts()
{
  wp_enqueue_style("parent-style", get_parent_theme_file_uri('/style.css'));

  // wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

  // wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

  $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
  // Function to add analyticstracking.js to the site
  if (!IS_DEV_MODE) {
    $current_user = wp_get_current_user();
    $user_id = (0 == $current_user->ID) ? '' : $current_user->ID;
    wp_register_script('google-analytics', get_stylesheet_directory_uri() . '/js/analyticstracking.js', false, '1.0', true);
    wp_enqueue_script('google-analytics');
    // hand over the userID to the analytics script
    wp_localize_script('google-analytics', 'atts', array('user_id' => $user_id, 'ga_id' => GA_ID));
  }

  wp_register_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), '1.0', true);
  wp_enqueue_script('main');
  wp_register_script('helper', get_stylesheet_directory_uri() . '/js/helper.js', array('jquery'), '1.0', true);
  wp_enqueue_script('helper');
}
add_action('wp_enqueue_scripts', 'add_scripts');

/**
 * Default sort for shop and specific categories
 */
function wbp_custom_default_orderby($sortby)
{

  if (is_shop()) {
    return 'date';
  }

  global $wp_query;

  // categories sorting table
  $orderby = array(
    'sale' => 'date',
  );

  $cat = $wp_query->get_queried_object();
  $slug = $cat->slug;

  if (array_key_exists($slug, $orderby)) {
    $sortby = $orderby[$slug];
  }

  return $sortby;
}
add_filter('woocommerce_default_catalog_orderby', 'wbp_custom_default_orderby');

/**
 * Unsupprted Browsers IE 11 and lower
 */
function wbp_detectTrident($current_theme)
{
  $ua = $_SERVER['HTTP_USER_AGENT'];
  $browser = ['name' => '', 'version' => '', 'platform' => ''];
  if (preg_match('/Trident\/([0-9.]*)/u', $ua, $match)) {
    $match = (int)array_pop($match) + 4;
    // write_log( "Trident:" );
    // write_log( $match );
  } elseif (preg_match('/MSIE\s{1}([0-9.]*)/u', $ua, $match)) {
    $match = (int)array_pop($match);
    // write_log( "MSIE:" );
    // write_log( $match );
  }
  if (!empty($match) && ($match <= 11)) {
    $browser['name'] = 'ie';
    $browser['version'] = $match;
    add_action('wp_footer', 'unsupported_browsers_template', 100);

    wp_register_script('browser_sniffer', get_stylesheet_directory_uri() . '/js/browser_support.js', ['jquery'], '0.1', true);
    wp_localize_script('browser_sniffer', '__browser', array('name' => $browser['name'], 'version' => $browser['version'], 'platform' => $browser['platform']));
    wp_enqueue_script('browser_sniffer');
  }
}
function unsupported_browsers_template()
{
  get_template_part('custom-templates/custom', 'unsupported-browser');
}
add_action('wp_enqueue_scripts', 'wbp_detectTrident');

/**
 * Enqueue vendor scripts
 */
function enqueue_vendors()
{
  $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

  if (!IS_DEV_MODE) {

    /**
     * Consens Pro
     */
    wp_enqueue_style('consent-pro', get_stylesheet_directory_uri() . '/consent-pro/style.css');
    wp_enqueue_script('consent-pro', 'https://cookie-cdn.cookiepro.com/scripttemplates/otSDKStub.js');
  }

  /**
   * Animate css
   * https://github.com/daneden/animate.css
   *
   */
  wp_enqueue_style('animate.css', '/node_modules/animate.css/animate' . $suffix . '.css');
}
add_action('wp_enqueue_scripts', 'enqueue_vendors', 15);

function add_cp_data_attribute($tag, $handle, $src)
{
  if ('consent-pro' === $handle) {
    $tag = str_replace('src=', 'data-domain-script=' . CONSENT_PRO_ID . ' src=', $tag);
  }
  return $tag;
}
add_filter('script_loader_tag', 'add_cp_data_attribute', 10, 3);

/**
 * Hook for Jet Engines Forms
 */
function add_ajax_scripts()
{
  wp_enqueue_script('ajax-callback', get_stylesheet_directory_uri() . '/js/ajax.js', array(), '1.0.0', true);

  wp_localize_script('ajax-callback', 'ajax_object', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'ajaxnonce' => wp_create_nonce('ajax_post_validation'),
  ));
}
add_action('wp_enqueue_scripts', 'add_ajax_scripts', 15);

function wbp_update_post()
{
  $post_id = $_POST['post_id'];
  $post_status = $_POST['post_status'];
  wp_update_post(array(
    'ID' => $post_id,
    'post_status' => $post_status,
  ));

  wp_die();
}
add_action('wp_ajax_wbp_update_post', 'wbp_update_post');

function wbp_get_post()
{
  $post_id = $_POST['post_id'];
  $post = get_post($post_id);

  wp_die();
}
add_action('wp_ajax_wbp_get_post', 'wbp_get_post');

/**
 * Replace Elementors with Woos Placeholder Image (can be defined in woo product settings)
 */
function wbp_get_wc_placeholder_image($default_placeholder)
{

  $placeholder = wc_placeholder_img_src('woocommerce_image');

  return $placeholder;
}
add_filter('jet-woo-builder/template-functions/product-thumbnail-placeholder', 'wbp_get_wc_placeholder_image');

/**
 * In order to improve SEO,
 * display the product title again in product description
 *
 */
function wbp_woo_custom_description_tab($tabs)
{
  // Get $product object
  global $product;

  $tabs['description'] = array(
    'title' => __('Description', 'woocommerce'),
    'priority'   => 10,
    'callback' => 'wbp_woo_tab_content',
  );

  // $tabs['technical'] = array(
  //   'title'   => __('Technical Details', 'astra-child'),
  //   'priority'   => 20,
  //   'callback'   => 'wbp_woo_tab_technical'
  // );

  $meta = get_post_meta($product->id);
  $datasheets = $meta['_datasheet'][0];
  if (!empty($datasheets)) {
    $tabs['datasheets'] = array(
      'title'   => __('Datasheet', 'astra-child'),
      'priority'   => 30,
      'callback'   => 'wbp_woo_tab_datasheets'
    );
  }
  unset($tabs['reviews']);
  unset($tabs['additional_information']);

  return $tabs;
}
function wbp_woo_tab_content($tab_name, $tab)
{

  global $product;

  $title = $product->get_title();
  $content = wpautop($product->get_description()); // prevent to strip out all \n !!!
  echo '<h6 style="font-weight:600; opacity: 0.5; margin-bottom: 10px;">Highlights</h6><h5 style="margin-bottom: 30px;">' . $title . '</h5>' . do_shortcode($content); // keep possible shortcode
}
function wbp_woo_tab_technical()
{
  echo '<p>Der Tab <strong>' . __('Technical Details', 'astra-child') . '</strong> kann auf Wunsch implementiert werden.</p>';
}
function wbp_woo_tab_datasheets()
{
  global $product;

  $meta = get_post_meta($product->id);
  $dg = $meta['_datasheet'][0];

  echo do_shortcode($dg);
}
add_filter('woocommerce_product_tabs', 'wbp_woo_custom_description_tab', 98);

add_filter('woocommerce_cart_needs_payment', '__return_false');

/**
 * Quote Plugin
 */
function wbp_get_price_visibility($show)
{
  return array(
    'show' => (isset($show['to_admin']) ? $show['to_admin'] : false) || (isset($show['to_customer']) ? $show['to_customer'] : false),
    'class' => (isset($show['to_customer']) && !$show['to_customer']) ? 'price-hidden' : ''
  );
};
add_filter('wbp_show_prices', function () {
  if (!defined('SHOW_CUSTOMER_EMAIL_PRICE')) {
    return false;
  }
  return SHOW_CUSTOMER_EMAIL_PRICE;
});

/**
 * Change required billing & shipping address fields
 */
function wbp_filter_default_address_fields($address_fields)
{
  // Only on checkout page
  if (!is_checkout()) return $address_fields;

  // list all non required field
  $key_fields = array('country', 'first_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode');

  foreach ($key_fields as $key_field)
    $address_fields[$key_field]['required'] = false;

  return $address_fields;
}
add_filter('woocommerce_default_address_fields', 'wbp_filter_default_address_fields', 20, 1);

function wbp_wc_coupons_frontend_enabled($is_enabled)
{
  if (!is_admin() && function_exists('astra_get_option')) {
    return astra_get_option('checkout-coupon-display');;
  }
  return true;
}
add_filter('woocommerce_coupons_enabled', 'wbp_wc_coupons_frontend_enabled');

function wbp_return_theme_author($author)
{
  $author = array(
    'theme_name'       => __('Axel Nitzschner', 'astra-child'),
    'theme_author_url' => 'https://webpremiere.de/',
  );
  return $author;
}
add_filter('astra_theme_author', 'wbp_return_theme_author');

/**
 * Change Variable Price Html
 */
function wbp_format_variation_price_range($price, $from, $to)
{
  // $price = sprintf( _x( '%1$s &ndash; %2$s', 'Price range: from-to', 'woocommerce' ), is_numeric( $from ) ? wc_price( $from ) : $from, is_numeric( $to ) ? wc_price( $to ) : $to );
  $price = sprintf(_x('from %1$s', 'Price range: from', 'astra-child'), is_numeric($from) ? wc_price($from) : $from);
  return $price;
}
add_filter('woocommerce_format_price_range', 'wbp_format_variation_price_range', 10, 3);
