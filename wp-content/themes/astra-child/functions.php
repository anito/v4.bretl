<?php
require_once __DIR__ . '/includes/sender-email.php';
require_once __DIR__ . '/includes/duplicate-content.php';

function wbp_init()
{
  $theme = wp_get_theme();
  /**
   * Define Constants
   */
  define('CHILD_THEME_ASTRA_CHILD_VERSION', $theme->__get('version'));
}
add_filter('init', 'wbp_init');

/**
 * CSRF allowed domains
 */
function add_allowed_origins($origins)
{
  return array_merge($origins, [
    'https://dev.auto-traktor-bretschneider.de',
    'https://dev.auto-traktor-bretschneider.mbp',
    'http://localhost:5173'
  ]);
}
add_filter('allowed_http_origins', 'add_allowed_origins');

/**
 * App asset names (e.g. *.js. *.css files) changing per app distribution
 * This method takes care of it automatically using glob
 */
function wbp_get_themes_file($file_path)
{
  $regex = '/^([\w\-\/.]*)(\/wp-content\/themes[\w\-\.\/]+)/';
  return preg_replace($regex, '\2', glob($file_path)[0]);
}

/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */
function add_scripts()
{
  $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

  // Theme styles
  wp_enqueue_style("parent-style", get_parent_theme_file_uri('/style.css'), array(), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
  wp_enqueue_style('astra-child-theme', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');

  // Homepage hero
  if(0) {
    $js_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.js');
    $css_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.css');
  
    wp_enqueue_script('app-hero', $js_uri, false, CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
    wp_enqueue_style('app-hero', $css_uri, false, CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
    wp_localize_script('app-hero', 'app_hero', array(
      'app_url' => get_stylesheet_directory_uri() . '/js/hero/dist/',
      'stylesheet_url' => get_stylesheet_directory_uri()
    ));
  }

  wp_register_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), CHILD_THEME_ASTRA_CHILD_VERSION, true);
  wp_enqueue_script('main');

  if (!IS_DEV_MODE) {

    // Vendor scripts

  }
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
    'aktionspreise' => 'date',
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

    wp_register_script('browser_sniffer', get_stylesheet_directory_uri() . '/js/browser_support.js', array('jquery'), CHILD_THEME_ASTRA_CHILD_VERSION, true);
    wp_localize_script('browser_sniffer', '__browser', array('name' => $browser['name'], 'version' => $browser['version'], 'platform' => $browser['platform']));
    wp_enqueue_script('browser_sniffer');
  }
}
function unsupported_browsers_template()
{
  get_template_part('custom-templates/custom', 'unsupported-browser');
}
// add_action('wp_enqueue_scripts', 'wbp_detectTrident');

function add_cp_data_attribute($tag, $handle, $src)
{
  if ('consent-pro' === $handle) {
    $tag = str_replace('src=', 'data-domain-script=' . CONSENT_PRO_ID . ' src=', $tag);
  }
  return $tag;
}
add_filter('script_loader_tag', 'add_cp_data_attribute', 10, 3);

function get_certificate()
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
 * Replace default Elementor image placeholdder
 */
function custom_elementor_placeholder_image()
{
  return get_stylesheet_directory_uri() . '/images/placeholder.jpg';
}
add_filter('elementor/utils/get_placeholder_image_src', 'custom_elementor_placeholder_image');

/**
 * Add Metabox to product screen
 *
 */
function kleinanzeigen_meta_box()
{

  $screens = array('edit-product', 'product');

  foreach ($screens as $screen) {
    add_meta_box(
      'kleinanzeigen',
      __('Ads', 'astra-child'),
      'kleinanzeigen_metabox_callback',
      $screen
    );
  }
}
// add_action('add_meta_boxes', 'kleinanzeigen_meta_box');

function kleinanzeigen_metabox_callback($post)
{

  wp_nonce_field('kleinanzeigen_nonce', 'kleinanzeigen_nonce');

  $id = get_post_meta($post->ID, 'kleinanzeigen_id', true);
  wbp_include_kleinanzeigen_template('metaboxes/kleinanzeigen-import.php', false, array('id' => $id));
}

function save_kleinanzeigen_meta_box_data($post_ID)
{

  // Check if our nonce is set.
  if (!isset($_POST['kleinanzeigen_nonce'])) {
    return;
  }

  // Verify that the nonce is valid.
  if (!wp_verify_nonce($_POST['kleinanzeigen_nonce'], 'kleinanzeigen_nonce')) {
    return;
  }

  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  // Check the user's permissions.
  if (isset($_POST['post_type']) && 'product' == $_POST['post_type']) {

    if (!current_user_can('edit_post', $post_ID)) {
      return;
    }
  }

  if (!isset($_POST['kleinanzeigen_id'])) {
    return;
  }

  $id = sanitize_text_field($_POST['kleinanzeigen_id']);

  update_post_meta($post_ID, 'kleinanzeigen_id', $id);
}
// add_action('save_post', 'save_kleinanzeigen_meta_box_data');

/**
 * In order to improve SEO,
 * display the product title again in product description
 *
 */
function wbp_woo_custom_tabs($tabs)
{
  global $product;

  $tabs['description'] = array(
    'title' => __('Description', 'woocommerce'),
    'priority'   => 10,
    'callback' => 'wbp_woo_tab_content',
  );

  $meta = get_post_meta($product->id);
  $datasheets = $meta['_datasheet'][0];
  if (!empty($datasheets)) {
    $tabs['datasheets'] = array(
      'title'   => __('Datasheet', 'astra-child'),
      'priority'   => 20,
      'callback'   => 'wbp_woo_tab_datasheets'
    );
  }

  $tabs['quote_request_form'] = array(
    'title'   => __('Quote Request', 'astra-child'),
    'priority'   => 30,
    'callback'   => 'wbp_woo_tab_quote_request'
  );

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

function wbp_woo_tab_quote_request()
{
  if (REQUEST_FORM_SHORTCODE_ID) {
    echo  do_shortcode('[elementor-template id="' . REQUEST_FORM_SHORTCODE_ID . '"]');
  } else {
    echo '<p>Um den Inhalt dieses Tabs anzuzeigen, muss eine Shortcode ID in der wp-config.php hinterlegt werden.</p>';
  }
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
add_filter('woocommerce_product_tabs', 'wbp_woo_custom_tabs', 98);
add_filter('woocommerce_cart_needs_payment', '__return_false');
// add_filter('woocommerce_cart_hide_zero_taxes', '__return_false');
// add_filter('woocommerce_order_hide_zero_taxes', '__return_false');

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
 * Change Variable Price Range Html
 */
function wbp_format_variation_price_range($price, $from, $to)
{
  // $price = sprintf( _x( '%1$s &ndash; %2$s', 'Price range: from-to', 'woocommerce' ), is_numeric( $from ) ? wc_price( $from ) : $from, is_numeric( $to ) ? wc_price( $to ) : $to );
  $price = sprintf(_x('from %1$s', 'Price range: from', 'astra-child'), is_numeric($from) ? wc_price($from) : $from);
  return $price;
}
add_filter('woocommerce_format_price_range', 'wbp_format_variation_price_range', 10, 3);

function wbp_modify_cart_product_subtotal_label($product_subtotal, $product, $quantity, $cart)
{
  $tax_class = $product->get_tax_class();
  $rates = WC_Tax::get_rates_for_tax_class($tax_class);
  foreach ($rates as $rate) {
    $label = $rate->tax_rate_name;
    if (str_contains(strtolower($tax_class), DIFF_TAX)) {
      $product_subtotal .= ' <small class="tax-label">' . $label . '</small>';
    }
  }
  return $product_subtotal;
}
add_filter('woocommerce_cart_product_subtotal', 'wbp_modify_cart_product_subtotal_label', 10, 4);
