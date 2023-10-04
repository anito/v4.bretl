<?php
require_once __DIR__ . '/includes/sender-email.php';
require_once __DIR__ . '/includes/duplicate-content.php';
require_once __DIR__ . '/includes/register-wc-taxonomies.php';
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
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.4');

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
  wp_enqueue_style("parent-style", get_parent_theme_file_uri('/style.css'));
  wp_enqueue_style('astra-child-theme', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');

  // Homepage hero
  $js_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.js');
  $css_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.css');

  // wp_enqueue_script('app-hero', $js_uri, false, '0.0.1', 'all');
  // wp_enqueue_style('app-hero', $css_uri, false, '0.0.1', 'all');
  // wp_localize_script('app-hero', 'app_hero', array(
  //   'app_url' => get_stylesheet_directory_uri() . '/js/hero/dist/',
  //   'stylesheet_url' => get_stylesheet_directory_uri()
  // ));

  wp_register_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), '1.0', true);
  wp_enqueue_script('main');

  if (!IS_DEV_MODE) {

    // Vendor scripts

  }
}
add_action('wp_enqueue_scripts', 'add_scripts');

/**
 * Admin Styles for WC Screens
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

function wbp_has_price_diff($record, $product)
{
  $regex = '/^([\d.]+)/';
  preg_match($regex, $record->price, $matches);
  $kleinanzeigen_price = !empty($matches) ? str_replace('.', '', $matches[0]) : 0;
  $woo_price = $product->get_price();

  return $kleinanzeigen_price !== $woo_price;
}


function wbp_title_contains($string, $title, $searchtype = 'default')
{
  switch ($searchtype) {
    case 'like':
      preg_match('/' . strtolower($string) . '/', strtolower($title), $matches);
      break;
    default:
      preg_match('/\b' . strtolower($string) . '\b/', strtolower($title), $matches);
  }

  if (!empty($matches[0])) {
    return true;
  }
  return false;
}

// Callable product title functions
function wbp_handle_product_title_sale($args)
{
  $product = $args['product'];
  $price = $args['price'];

  $regular_price = (int) $price + (int) $price * 10 / 100;
  $product->set_regular_price($regular_price);
  $product->set_sale_price($price);
  return wbp_handle_product_term($args['term_name'], $product);;
}

function wbp_handle_product_title_default($args)
{
  $product = $args['product'];
  return wbp_handle_product_term($args['term_name'], $product);
}

function wbp_handle_product_term($name, $product)
{
  $term_id = wbp_add_product_term($name, 'label');
  if ($term_id) {
    require_once __DIR__ . '/includes/product-term-handler.php';
    wbp_set_product_term($product, $term_id, 'label', true);
  }
  return $product;
}

function wbp_publish_guard($data)
{
  require_once __DIR__ . '/includes/kleinanzeigen-ajax-handler.php';
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

function wbp_get_kleinanzeigen_url($id)
{
  return KLEINANZEIGEN_URL . ($id ? '/s-' . $id . '/k0' : '/');
}

function wbp_get_kleinanzeigen_json_url($page)
{
  return KLEINANZEIGEN_CUSTOMER_URL . '?pageNum=' . $page ?? 1;
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

  require_once __DIR__ . '/includes/product-term-handler.php';
  wbp_process_sale($post_ID, $post);
  if (!is_ajax()) {
    wbp_process_kleinanzeigen($post_ID, $post);
  }
}
add_action("save_post", "wbp_save_post", 99, 3);
add_action("save_post", "wbp_quick_edit_product_save", 99, 3);
add_action("wp_insert_post_data", "wbp_publish_guard", 99, 3);
add_filter('wp_insert_post_empty_content', function () {
  return false;
});

// Woo internal product save
function wbp_product_before_save($product)
{
  require_once __DIR__ . '/includes/product-term-handler.php';
  wbp_process_featured($product);
}
add_action("woocommerce_before_product_object_save", "wbp_product_before_save", 99, 2);

function wbp_before_delete($post_ID)
{
  wbp_remove_attachments($post_ID);
}
add_action('before_delete_post', 'wbp_before_delete');

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
// add_action('wp_enqueue_scripts', 'wbp_detectTrident');

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
  wp_enqueue_script('ajax-callback', get_stylesheet_directory_uri() . '/js/ajax.js');

  // kleinanzeigen doesn't accept a wc_remote_get from referrers w/o valid certificates
  $valid_cert = !!check_cert();
  $admin_ajax_local = admin_url('admin-ajax.php');
  if ($valid_cert) {
    $admin_ajax_remote = $admin_ajax_local;
  } else {
    // fallback to https://dev.bretl.webpremiere.de/wp-admin/admin-ajax.php
    $admin_ajax_remote = 'https://dev.bretl.webpremiere.de/wp-admin/admin-ajax.php';
  }

  $screen_id = get_current_screen()->id;
  $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
  wp_localize_script('ajax-callback', 'ajax_object', array(
    'admin_ajax_remote' => $admin_ajax_remote,
    'admin_ajax_local' => $admin_ajax_local,
    'home_url' => home_url(),
    'screen' => $screen_id,
    'edit_link' => admin_url('post.php?action=edit&post='),
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

function _ajax_get_remote()
{
  wbp_get_remote();
}

function _ajax_connect()
{
  wbp_ajax_connect();
}

function _ajax_disconnect()
{
  wbp_ajax_disconnect();
}

function _ajax_publish_post()
{
  wbp_ajax_toggle_publish_post();
}

function _ajax_import_kleinanzeigen_data()
{
  wbp_ajax_import_kleinanzeigen_data();
}

function _ajax_import_kleinanzeigen_images()
{
  wbp_ajax_import_kleinanzeigen_images();
}

function _ajax_delete_post()
{
  wbp_ajax_delete_post();
}

function _ajax_delete_images()
{
  wbp_ajax_delete_images();
}

function _ajax_get_product_categories()
{
  wbp_ajax_get_product_categories();
}

function _ajax_get_brands()
{
  wbp_ajax_get_brand_images();
}

function wbp_get_product_brands($post)
{
  return get_the_terms($post, 'brands');
}

function wbp_get_product_cats($post)
{
  return get_the_terms($post, 'product_cat');
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

  require_once __DIR__ . '/includes/kleinanzeigen-ajax-handler.php';
  require_once __DIR__ . '/includes/ajax-table.php';
  require_once __DIR__ . '/includes/product-term-handler.php';

  add_action('wp_ajax__ajax_connect', '_ajax_connect');
  add_action('wp_ajax__ajax_disconnect', '_ajax_disconnect');
  add_action('wp_ajax__ajax_get_remote', '_ajax_get_remote');
  add_action('wp_ajax__ajax_get_brands', '_ajax_get_brands');
  add_action('wp_ajax__ajax_delete_post', '_ajax_delete_post');
  add_action('wp_ajax__ajax_publish_post', '_ajax_publish_post');
  add_action('wp_ajax__ajax_delete_images', '_ajax_delete_images');
  add_action('wp_ajax__ajax_import_kleinanzeigen_data', '_ajax_import_kleinanzeigen_data');
  add_action('wp_ajax__ajax_import_kleinanzeigen_images', '_ajax_import_kleinanzeigen_images');
  add_action('wp_ajax__ajax_get_product_categories', '_ajax_get_product_categories');

  add_action('wp_ajax_nopriv__ajax_connect', '_ajax_connect');
  add_action('wp_ajax_nopriv__ajax_disconnect', '_ajax_disconnect');
  add_action('wp_ajax_nopriv__ajax_get_remote', '_ajax_get_remote');
  add_action('wp_ajax_nopriv__ajax_get_brands', '_ajax_get_brands');
  add_action('wp_ajax_nopriv__ajax_delete_post', '_ajax_delete_post');
  add_action('wp_ajax_nopriv__ajax_publish_post', '_ajax_publish_post');
  add_action('wp_ajax_nopriv__ajax_delete_images', '_ajax_delete_images');
  add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_data', '_ajax_import_kleinanzeigen_data');
  add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_images', '_ajax_import_kleinanzeigen_images');
  add_action('wp_ajax_nopriv__ajax_get_product_categories', '_ajax_get_product_categories');
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

function kleinanzeigen_metabox_callback($post) {

  wp_nonce_field('kleinanzeigen_nonce', 'kleinanzeigen_nonce');

  $id = get_post_meta($post->ID, 'kleinanzeigen_id', true);
  wbp_include_kleinanzeigen_template('metaboxes/kleinanzeigen-import.php', false, array('id' => $id));
}

function save_kleinanzeigen_meta_box_data($post_id)
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

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }
  }

  if (!isset($_POST['kleinanzeigen_id'])) {
    return;
  }

  $id = sanitize_text_field($_POST['kleinanzeigen_id']);

  update_post_meta($post_id, 'kleinanzeigen_id', $id);
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

  $tabs['request_form'] = array(
    'title'   => __('Anfrage', 'astra-child'),
    'priority'   => 30,
    'callback'   => 'wbp_woo_tab_request_form'
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

function wbp_woo_tab_request_form()
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

function wbp_product_custom_fields()
{

  global $woocommerce, $post;
  echo '<div class=" product_custom_field ">';

?>
  <?php

  woocommerce_wp_text_input(
    array(
      'id' => 'kleinanzeigen_url',
      'label' => __('Ad Url', 'astra-child'),
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
  if (isset($_POST['kleinanzeigen_url']))
    update_post_meta($post_id, 'kleinanzeigen_url', esc_attr($_POST['kleinanzeigen_url']));
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

function wbp_add_kleinanzeigen_admin_menu_page()
{
  $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIGlkPSJ1dWlkLWNmNTA5MjcyLTAwZTItNGIyZS1iZmVlLTU2ZWYwOWQ4YTA0ZiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTE0Ljk4IDEzMC45OCI+PGcgaWQ9InV1aWQtZGFkMmZmYTQtODM3Ny00ODc2LTg5NjQtZDc4MTJhYTQ0ZTkxIj48cGF0aCBkPSJtODQuOTksMTMwLjk4Yy0xNy41NiwwLTI2LjE1LTEyLjI1LTI3Ljg5LTE0Ljc5LTUuMTgsNS4xLTEzLjAxLDE0Ljc5LTI3LjEsMTQuNzktMTYuMjcsMC0yOS45OS0xMi4zLTI5Ljk5LTMyLjI0VjMyLjI0QzAsMTIuMjUsMTMuNzQsMCwyOS45OSwwczI5Ljk5LDEzLjAyLDI5Ljk5LDMxLjk0YzMuMTctMS4xMyw2LjU0LTEuNzEsMTAtMS43MSwxNi43NCwwLDI5Ljk5LDEzLjczLDI5Ljk5LDMwLjIzLDAsNC42My0uODcsOC43NC0yLjc5LDEyLjY4LDEwLjYyLDQuNzYsMTcuNzgsMTUuNDYsMTcuNzgsMjcuNjMsMCwxNi42Ny0xMy40NiwzMC4yMy0yOS45OSwzMC4yM1ptLTIwLjY0LTIyLjA5YzQuMzEsNy41NSwxMS41NywxMi4wMiwyMC42NCwxMi4wMiwxMS4wMiwwLDIwLTkuMDQsMjAtMjAuMTUsMC04Ljc5LTUuNjEtMTYuNDQtMTMuNjctMTkuMTNsLTI2Ljk3LDI3LjI2aDBaTTMwLDEwLjA4Yy05Ljk1LDAtMjAsNi44NS0yMCwyMi4xN3Y2Ni41YzAsMTUuMzEsMTAuMDQsMjIuMTcsMjAsMjIuMTcsNy45LDAsMTIuMjctNC4wMiwxOS4zMS0xMS4xMmwzLjEyLTMuMTRjLTEuNjEtNC44My0yLjQ0LTEwLjItMi40NC0xNS45N3YtNTguNDRjMC0xNS4zMS0xMC4wNC0yMi4xNy0yMC0yMi4xN2gwWm0yOS45OSwzMi45MnY0Ny42OWMwLDIuNy4yMyw1LjI3LjY1LDcuNjlsMjEuNzMtMjEuOWM2LjMxLTYuMzYsNy42MS0xMSw3LjYxLTE2LjAxLDAtMTAuNy04LjU0LTIwLjE1LTIwLTIwLjE1LTMuNTcsMC02Ljk4LjkyLTEwLDIuNjloMFoiIGZpbGw9IiMxZDRiMDAiLz48L2c+PC9zdmc+';

  add_menu_page('kleinanzeigen', 'kleinanzeigen', 'edit_posts', 'kleinanzeigen', 'wbp_display_kleinanzeigen_list', $icon_svg, 10);
  if (!empty($_GET['page']) && 'kleinanzeigen' == $_GET['page']) {

    if (!defined('KLEINANZEIGEN_TEMPLATE_PATH')) {
      define('KLEINANZEIGEN_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/kleinanzeigen/');
    }
    require_once __DIR__ . '/includes/ajax-table.php';

    wbp_kleinanzeigen_register_scripts();
    register_admin_content();
  }
}
add_action('admin_menu', 'wbp_add_kleinanzeigen_admin_menu_page');

function wbp_display_kleinanzeigen_list()
{

  echo '<div id="wbp-kleinanzeigen-wrap">';

  wbp_include_kleinanzeigen_template('page-header.php');

  do_action('wbp_kleinanzeigen_admin_after_header');

  echo '<div id="pages-results"></div>';

  $pages = wbp_kleinanzeigen_get_submenu_items();

  foreach ($pages as $page) {
    if (isset($page['menu_slug'])) {
      wbp_kleinanzeigen_display_admin_page($page['menu_slug']);
    }
  }

  do_action('wbp_kleinanzeigen_admin_before_closing_wrap');

  // closes main plugin wrapper div. #wp-optimize-wrap
  echo '</div><!-- END #wbp-kleinanzeigen-wrap -->';
}

function register_admin_content()
{
  add_action('wbp_kleinanzeigen_admin_page_wbp_kleinanzeigen_dashboard', 'output_dashboard_tab', 20);
}

function wbp_kleinanzeigen_register_scripts()
{
  add_action('admin_enqueue_scripts', 'wbp_kleinanzeigen_admin_enqueue_styles');
  add_action('admin_enqueue_scripts', 'wbp_add_admin_ajax_scripts');
}

function wbp_kleinanzeigen_admin_enqueue_styles()
{
  wp_enqueue_style('ajax-kleinanzeigen-json', get_stylesheet_directory_uri() . '/style-admin-kleinanzeigen.css');
}

function wbp_include_kleinanzeigen_template($path, $return_instead_of_echo = false, $extract_these = array())
{
  if ($return_instead_of_echo) ob_start();

  $template_file = KLEINANZEIGEN_TEMPLATE_PATH . $path;

  if (!file_exists($template_file)) {
    error_log("WBP Kleinanzeigen: template not found: " . $template_file);
    echo __('Error:', 'wbp') . ' ' . __('template not found', 'wbp') . " (" . $path . ")";
  } else {
    extract($extract_these);
    include $template_file;
  }

  if ($return_instead_of_echo) return ob_get_clean();
}

function wbp_kleinanzeigen_get_submenu_items()
{
  $sub_menu_items = array(
    array(
      'page_title' => __('Dashborad', 'wbp'),
      'menu_title' => __('Dashboard', 'wbp'),
      'menu_slug' => 'wbp_kleinanzeigen_dashboard',
      'function' => 'display_admin',
      'icon' => 'admin-settings',
      'create_submenu' => true,
      'order' => 60,
    )
  );

  return $sub_menu_items;
}

function wbp_kleinanzeigen_display_admin_page($page)
{

  $active_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

  echo '<div class="wbp-kleinanzeigen-page' . ($active_page == $page ? ' active' : '') . '" data-whichpage="' . $page . '">';

  echo '<div class="wbp-kleinanzeigen-main">';


  // if no tabs defined for $page then use $page as $active_tab for load template, doing related actions e t.c.
  $active_tab = $page;

  do_action('wbp_kleinanzeigen_admin_page_' . $page, $active_tab);

  echo '</div><!-- END .wbp-kleinanzeigen-main -->';

  do_action('wbp_kleinanzeigen_admin_after_page_' . $page, $active_tab);

  echo '</div><!-- END .wbp-kleinanzeigen-page -->';
}

function output_dashboard_tab()
{
  wbp_include_kleinanzeigen_template('dashboard/dashboard.php', false, array('pages' => 5, 'load_data' => false));
}


function wbp_get_json_data($page)
{
  setcookie('kleinanzeigen-table-page', $page);
  $remoteUrl = wbp_get_kleinanzeigen_json_url($page);
  $response = get_remote($remoteUrl);
  // $response = file_get_contents(__DIR__ . '/sample' . $page . '.json');
  if (!is_wp_error($response)) {
    return json_decode($response['body']);
  } else {
    return $response;
  }
}

function get_remote($url)
{
  return wp_remote_get($url);
}

function wbp_get_product_by_sku_($sku)
{
  global $wpdb;
  $post_ID = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

  if (isset($post_ID)) {
    return wc_get_product($post_ID);
  }
}

function wbp_get_product_by_sku($sku)
{
  $p = wc_get_products(array(
    'sku' => $sku
  ));

  if (!empty($p)) {
    return $p[0];
  }

  return false;
}

function wbp_get_product_by_title($title)
{
  global $wpdb;

  // $sql = $wpdb->prepare(
  //   "
  // 		SELECT ID
  // 		FROM $wpdb->posts
  // 		WHERE post_title = %s
  // 		AND post_type = %s
  // 	",
  //   $title,
  //   'product'
  // );
  $post_ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title='%s' AND post_type LIKE '%s' LIMIT 1", $title, 'product'));

  if (isset($post_ID)) {
    return wc_get_product($post_ID);
  }
}

function wbp_remove_attachments($post_ID)
{
  $product = wc_get_product($post_ID);
  if ($product) {
    $attachment_ids[] = $product->get_image_id();
    $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
    for ($i = 0; $i < count($attachment_ids); $i++) {
      wp_delete_post($attachment_ids[$i]);
    }
  }
}


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
