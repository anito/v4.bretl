<?php
require_once __DIR__ . '/includes/sender_email.php';
require_once __DIR__ . '/includes/duplicate_content.php';
require_once __DIR__ . '/includes/register_wc_taxonomies.php';
require_once __DIR__ . '/includes/class-extended-wc-admin-list-table-products.php';

function wbp_product_table_list_loader()
{
  if (!is_ajax()) {
    new Extended_WC_Admin_List_Table_Products();
  }
}
add_filter('init', 'wbp_product_table_list_loader');

/**
 * CSRF allowed domains
 */
function add_allowed_origins($origins)
{
  return array_merge($origins, [
    'https://dev.auto-traktor-bretschneider.de',
    'http://localhost:5173'
  ]);
}
add_filter('allowed_http_origins', 'add_allowed_origins');

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
  wp_enqueue_script('app-hero', get_stylesheet_directory_uri() . '/js/hero/dist/assets/index-18d5f512.js', false, '0.0.1', 'all');
  wp_enqueue_style('app-hero', get_stylesheet_directory_uri() . '/js/hero/dist/assets/index-94df8f4b.css', false, '0.0.1', 'all');
  wp_localize_script('app-hero', 'app_hero', array(
    'app_url' => get_stylesheet_directory_uri() . '/js/hero/public/',
    'stylesheet_url' => get_stylesheet_directory_uri()
  ));
}
add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);

/**
 * Admin Styles fpr WC Screens
 */
function wbp_wc_screen_styles($hook)
{
  $screen    = get_current_screen();
  $screen_id = $screen ? $screen->id : '';
  if (in_array($screen_id, wc_get_screen_ids())) {
    wp_register_style('wbp_woocommerce_admin_styles', get_stylesheet_directory_uri() . '/style-admin.css', array(), CHILD_THEME_ASTRA_CHILD_VERSION);
    wp_enqueue_style('wbp_woocommerce_admin_styles');
  }
}

function wbp_publish_guard($data)
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  if (!is_valid_title($data['post_title']) && $data['post_status'] == 'publish') {
    $data['post_status'] = 'draft';
    // prevent adding duplicate DUPLIKAT info to title
    $data['post_title'] = wbp_sanitize_title($data['post_title']);
  }
  return $data;
}

function wbp_quick_edit_product_save($post)
{
  if (!class_exists('WooCommerce', false)) return 0;

  // Check for a quick editsave action
  if (is_ajax() && isset($_POST['ID'])) {
    // Render custom columns
    new Extended_WC_Admin_List_Table_Products();
  };
}

function wbp_get_ebay_url($id) {
  return EBAY_URL . ($id ? '/s-' . $id . '/k0' : '/');
}

function wbp_save_post($post_ID, $post)
{
  if (!class_exists('WooCommerce', false)) return 0;
  if ($post->post_type != "product" || $post->post_status == 'trash') return 0;

  $product = wc_get_product($post_ID);
  if (!$product) return 0;


  if ($product->is_type('variation')) {
    $variation = new WC_Product_Variation($product);
    $post_ID = $variation->get_parent_id();
    $product = wc_get_product($post_ID);
  }

  require_once __DIR__ . '/includes/product_term_handler.php';
  wbp_process_sale($post_ID, $post);
  wbp_process_ebay($post_ID, $post);
}
add_filter('wp_insert_post_empty_content', function () {
  return false;
});
add_action("save_post", "wbp_save_post", 99, 3);
add_action("save_post", "wbp_quick_edit_product_save", 99, 3);
add_action("wp_insert_post_data", "wbp_publish_guard", 99, 3);

function wbp_product_before_save($product)
{
  require_once __DIR__ . '/includes/product_term_handler.php';
  wbp_process_featured($product);
}
add_action("woocommerce_before_product_object_save", "wbp_product_before_save", 99, 2);

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
  } elseif (preg_match('/MSIE\s{1}([0-9.]*)/u', $ua, $match)) {
    $match = (int)array_pop($match);
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

function wbp_add_admin_ajax_scripts()
{
  global $wp;
  wp_enqueue_script('ajax-callback', get_stylesheet_directory_uri() . '/js/ajax.js');

  // ebay doesn't accept a wc_remote_get from referrers w/o valid certificates
  // if validation fails, fallback to https://dev.bretl.webpremiere.de/wp-admin/admin-ajax.php
  $valid_cert = !!check_cert();
  $local_url = admin_url('admin-ajax.php');
  if (!$valid_cert) {
    $remote_url = 'https://dev.bretl.webpremiere.de/wp-admin/admin-ajax.php';
  } else {
    $remote_url = $local_url;
  }

  $screen_id = get_current_screen()->id;
  $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
  wp_localize_script('ajax-callback', 'ajax_object', array(
    'remote_url' => $remote_url,
    'local_url' => $local_url,
    'home_url' => home_url(),
    'screen' => $screen_id,
    'relocate_url' => home_url($request_url),
    'nonce' => wp_create_nonce()
  ));
}

function check_cert()
{
  $g = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
  $r = stream_socket_client(
    "ssl://" . HOST . ":443",
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT,
    $g
  ); // returns ressource|false
  if (false === $r) {
    return false;
  }
  $cont = stream_context_get_params($r);
  return ($cont["options"]["ssl"]["peer_certificate"]);
}

function wbp_remote()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_get_remote();
}

function wbp_publish()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_publish_post();
}

function wbp_ebay_data()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_import_ebay_data();
}

function wbp_ebay_images()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_import_ebay_images();
}

function wbp_del_images()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_delete_images();
}

function wbp_product_categories()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_get_product_categories();
}

function wbp_brands()
{
  require_once __DIR__ . '/includes/ebay_ajax_handler.php';
  wbp_get_brands();
}

function wbp_product_set_attributes($post_id, $attributes)
{
  $i = 0;
  // Loop through the attributes array
  foreach ($attributes as $name => $value) {
    $product_attributes[$i] = array(
      'name' => htmlspecialchars(stripslashes($name)), // set attribute name
      'value' => $value, // set attribute value
      'position' => 1,
      'is_visible' => 1,
      'is_variation' => 0,
      'is_taxonomy' => 0
    );

    $i++;
  }

  // Now update the post with its new attributes
  update_post_meta($post_id, '_product_attributes', $product_attributes);
}

if (is_admin()) {
  add_action('admin_enqueue_scripts', 'wbp_add_admin_ajax_scripts', 10);
  add_action('admin_enqueue_scripts', 'wbp_wc_screen_styles');

  add_action('wp_ajax_wbp_publish', 'wbp_publish');
  add_action('wp_ajax_wbp_remote', 'wbp_remote');
  add_action('wp_ajax_wbp_brands', 'wbp_brands');
  add_action('wp_ajax_wbp_ebay_data', 'wbp_ebay_data');
  add_action('wp_ajax_wbp_ebay_images', 'wbp_ebay_images');
  add_action('wp_ajax_wbp_product_categories', 'wbp_product_categories');

  add_action('wp_ajax_nopriv_wbp_publish', 'wbp_publish', 1);
  add_action('wp_ajax_nopriv_wbp_remote', 'wbp_remote');
  add_action('wp_ajax_nopriv_wbp_brands', 'wbp_brands');
  add_action('wp_ajax_nopriv_wbp_ebay_data', 'wbp_ebay_data');
  add_action('wp_ajax_nopriv_wbp_ebay_images', 'wbp_ebay_images');
  add_action('wp_ajax_nopriv_wbp_product_categories', 'wbp_product_categories');

  add_action('wp_ajax_wbp_del_images', 'wbp_del_images');
}

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

if (class_exists('gpls_woo_rfq_product_meta')) {
  remove_action('woocommerce_product_options_advanced', array('gpls_woo_rfq_product_meta', 'gpls_woo_rfq_add_custom_general_fields'), 11, 0);
  remove_action('woocommerce_process_product_meta', array('gpls_woo_rfq_product_meta', 'gpls_woo_rfq_add_custom_general_fields_save'), 11, 1);
}

function wbp_product_custom_fields()
{

  global $woocommerce, $post;
  echo '<div class=" product_custom_field ">';

?>
  <?php

  woocommerce_wp_text_input(
    array(
      'id' => 'ebay_url',
      'label' => __('EBAY URL', 'astra-child'),
      'placeholder' => 'Link to eBay Kleinanzeigen',
      'desc_tip' => 'true',
      'description' => __("Enable eBay link for this product", 'astra-child'),
      'data_type' => 'url'
    )
  );
  echo '</div>';
}

function wbp_product_custom_fields_save($post_id)
{
  // Custom Product Text Field
  if (isset($_POST['ebay_url']))
    update_post_meta($post_id, 'ebay_url', esc_attr($_POST['ebay_url']));
}
// add_action('woocommerce_product_options_general_product_data', 'wbp_product_custom_fields');
// add_action('woocommerce_process_product_meta', 'wbp_product_custom_fields_save');

/**
 * Replace default Elementor image placeholdder
 */
function custom_elementor_placeholder_image()
{
  return get_stylesheet_directory_uri() . '/images/placeholder.jpg';
}
add_filter('elementor/utils/get_placeholder_image_src', 'custom_elementor_placeholder_image');
