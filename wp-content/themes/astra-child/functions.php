<?php
require_once __DIR__ . '/includes/sender-email.php';
require_once __DIR__ . '/includes/duplicate-content.php';
require_once __DIR__ . '/includes/register-wc-taxonomies.php';
require_once __DIR__ . '/includes/class-extended-wc-admin-list-table-products.php';

function wbp_init()
{
  if (!wp_doing_ajax()) {
    $theme = wp_get_theme();
    /**
     * Define Constants
     */
    define('CHILD_THEME_ASTRA_CHILD_VERSION', $theme->__get('version'));
  }
}
add_filter('init', 'wbp_init');

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
  $js_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.js');
  $css_uri = wbp_get_themes_file(get_stylesheet_directory() . '/js/hero/dist/assets/index-*.css');

  // wp_enqueue_script('app-hero', $js_uri, false, CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
  // wp_enqueue_style('app-hero', $css_uri, false, CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
  // wp_localize_script('app-hero', 'app_hero', array(
  //   'app_url' => get_stylesheet_directory_uri() . '/js/hero/dist/',
  //   'stylesheet_url' => get_stylesheet_directory_uri()
  // ));

  wp_register_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), CHILD_THEME_ASTRA_CHILD_VERSION, true);
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

function wbp_extract_kleinanzeigen_price($text)
{
  // $regex = '/^([\d.]+)/';
  // preg_match($regex, $text, $matches);
  // return !empty($matches) ? str_replace('.', '', $matches[0]) : 0;
  return preg_replace('/[\s.,a-zA-Z€\$]*/', '', $text);
}

function wbp_has_price_diff($record, $product)
{
  $kleinanzeigen_price = wbp_extract_kleinanzeigen_price($record->price);
  $woo_price = $product->get_price($record);

  return $kleinanzeigen_price !== $woo_price;
}

function wbp_set_pseudo_sale_price($product, $price, $percent = 10)
{
  $regular_price = (int) $price + (int) $price * $percent / 100;
  $product->set_regular_price($regular_price);
  $product->set_sale_price($price);
}

function wbp_text_contains($needle, $haystack, $searchtype = 'default')
{
  $needle = preg_quote($needle);
  switch ($searchtype) {
    case 'raw':
      preg_match('/' . $needle . '/', $haystack, $matches);
      break;
    case 'like':
      preg_match('/' . strtolower($needle) . '/', strtolower($haystack), $matches);
      break;
    default:
      preg_match('/\b' . strtolower($needle) . '\b/', strtolower($haystack), $matches);
  }

  if (!empty($matches[0])) {
    return true;
  }
  return false;
}

// Callable product contents functions
function wbp_handle_product_contents_sale($args)
{
  $product = $args['product'];
  $price = $args['price'];

  wbp_set_pseudo_sale_price($product, $price);
  return wbp_handle_product_label($args['term_name'], $product);;
}

function wbp_handle_product_contents_aktion($args)
{
  $product = $args['product'];

  $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['aktion']) ? WC_COMMON_TAXONOMIES['aktion'] : '', 'product_cat');

  if ($term) {
    require_once __DIR__ . '/includes/product-term-handler.php';
    wbp_set_product_term($product, $term->term_id, 'cat', true);
  }
  return $product;
}

function wbp_handle_product_contents_default($args)
{
  $product = $args['product'];
  return wbp_handle_product_label($args['term_name'], $product);
}

function wbp_handle_product_label($name, $product)
{
  $term_id = wbp_add_the_product_term($name, 'label');
  if ($term_id) {
    require_once __DIR__ . '/includes/product-term-handler.php';
    wbp_set_product_term($product, $term_id, 'label', true);
  }
  return $product;
}

function wbp_get_the_terms($terms, $post_ID, $taxonomy)
{
  if (is_wp_error($terms)) {
    return array();
  }
  return $terms;
}
add_filter('get_the_terms', 'wbp_get_the_terms', 10, 3);

// function wbp_label_filter($terms, $post_ID, $taxonomy)
// {
//   $terms = wbp_get_product_labels($post_ID);
//   return wbp_filter_exclusive_label_terms($terms);
// }
// add_filter('get_the_terms', 'wbp_label_filter', 20, 3);

function wbp_label_filter($terms, $post_ID, $taxonomy)
{
  if (!is_wp_error($terms) && 'product_label' === $taxonomy) {
    $terms = wbp_filter_exclusive_label_terms($terms);
  }
  return $terms;
}
add_filter('get_the_terms', 'wbp_label_filter', 20, 3);

function wbp_filter_exclusive_label_terms($terms)
{
  if (!defined('MUTUALLY_EXCLUSIVE_LABEL_NAMES')) {
    define(
      'MUTUALLY_EXCLUSIVE_LABEL_NAMES',
      array(
        array(
          'neuwertig',
          'neu'
        ),
      )
    );
  }

  foreach (MUTUALLY_EXCLUSIVE_LABEL_NAMES as $group) {
    $term_slugs = array_map(function ($term) {
      return $term->slug;
    }, $terms);
    $intersection = array_intersect($term_slugs, $group);
    if (count($intersection) > 1) {
      $exclusive_term = get_term_by('slug', $group[0], 'product_label');
      $diff_term_slugs = array_diff($term_slugs, $group);
      $diff_terms = array_map(function ($slug) {
        return get_term_by('slug', $slug, 'product_label');
      }, $diff_term_slugs);
      $terms = array_merge(array($exclusive_term), $diff_terms);
    }
  }
  return $terms;
}

function wbp_publish_guard($data)
{
  require_once __DIR__ . '/includes/kleinanzeigen-ajax-action-handler.php';
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
  if (wp_doing_ajax() && isset($_POST['ID'])) {
    // Render custom columns
    new Extended_WC_Admin_List_Table_Products();
  };
}

function wbp_get_kleinanzeigen_url($url)
{
  return KLEINANZEIGEN_URL . $url;
}

function wbp_get_kleinanzeigen_search_url($id)
{
  return KLEINANZEIGEN_URL . ($id ? '/s-' . $id . '/k0' : '/');
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
  wbp_maybe_remove_default_cat($post_ID);
  if (!wp_doing_ajax()) {
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

function wbp_add_admin_ajax_scripts()
{
  wp_enqueue_script('ajax-callback', get_stylesheet_directory_uri() . '/js/ajax.js', array(), CHILD_THEME_ASTRA_CHILD_VERSION);

  $screen_id = get_current_screen()->id;
  $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
  wp_localize_script('ajax-callback', 'ajax_object', array(
    'admin_ajax' => admin_url('admin-ajax.php'),
    'home_url' => home_url(),
    'screen' => $screen_id,
    'edit_link' => admin_url('post.php?action=edit&post='),
    'relocate_url' => home_url($request_url),
    'nonce' => wp_create_nonce()
  ));
}

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

function _ajax_fix_price()
{
  wbp_ajax_fix_price();
}

function _ajax_toggle_publish_post()
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

function wbp_get_product_terms($post, $type)
{
  return get_the_terms($post, 'product_' . $type);
}
function wbp_get_product_brands($post)
{
  return wbp_get_product_terms($post, 'brand');
}
function wbp_get_product_cats($post)
{
  return wbp_get_product_terms($post, 'cat');
}
function wbp_get_product_tags($post)
{
  return wbp_get_product_terms($post, 'tag');
}
function wbp_get_product_labels($post)
{
  return wbp_get_product_terms($post, 'label');
}

function wbp_set_product_attributes($post_ID, $attributes)
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
  update_post_meta($post_ID, '_product_attributes', $product_attributes);
}

function wbp_on_current_screen($screen)
{
  switch ($screen->id) {
    case 'edit-product':
      require_once __DIR__ . '/includes/kleinanzeigen-ajax-action-handler.php';
      new Extended_WC_Admin_List_Table_Products();
      break;
    case 'toplevel_page_kleinanzeigen':
      // setcookie('ka-paged', 1);
      break;
  }
}

function wbp_admin_init()
{
  if (!wp_doing_ajax()) {
    add_action('current_screen', 'wbp_on_current_screen');
  }

  add_action('admin_enqueue_scripts', 'wbp_add_admin_ajax_scripts', 10);
  add_action('admin_enqueue_scripts', 'wbp_wc_screen_styles');

  require_once __DIR__ . '/includes/kleinanzeigen-ajax-table.php';
  require_once __DIR__ . '/includes/kleinanzeigen-ajax-table-modal.php';
  require_once __DIR__ . '/includes/kleinanzeigen-ajax-action-handler.php';
  require_once __DIR__ . '/includes/product-term-handler.php';

  add_action('wp_ajax__ajax_connect', '_ajax_connect');
  add_action('wp_ajax__ajax_fix_price', '_ajax_fix_price');
  add_action('wp_ajax__ajax_disconnect', '_ajax_disconnect');
  add_action('wp_ajax__ajax_get_remote', '_ajax_get_remote');
  add_action('wp_ajax__ajax_get_brands', '_ajax_get_brands');
  add_action('wp_ajax__ajax_delete_post', '_ajax_delete_post');
  add_action('wp_ajax__ajax_toggle_publish_post', '_ajax_toggle_publish_post');
  add_action('wp_ajax__ajax_delete_images', '_ajax_delete_images');
  add_action('wp_ajax__ajax_import_kleinanzeigen_data', '_ajax_import_kleinanzeigen_data');
  add_action('wp_ajax__ajax_import_kleinanzeigen_images', '_ajax_import_kleinanzeigen_images');
  add_action('wp_ajax__ajax_get_product_categories', '_ajax_get_product_categories');

  add_action('wp_ajax_nopriv__ajax_connect', '_ajax_connect');
  add_action('wp_ajax_nopriv__ajax_fix_price', '_ajax_fix_price');
  add_action('wp_ajax_nopriv__ajax_disconnect', '_ajax_disconnect');
  add_action('wp_ajax_nopriv__ajax_get_remote', '_ajax_get_remote');
  add_action('wp_ajax_nopriv__ajax_get_brands', '_ajax_get_brands');
  add_action('wp_ajax_nopriv__ajax_delete_post', '_ajax_delete_post');
  add_action('wp_ajax_nopriv__ajax_toggle_publish_post', '_ajax_toggle_publish_post');
  add_action('wp_ajax_nopriv__ajax_delete_images', '_ajax_delete_images');
  add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_data', '_ajax_import_kleinanzeigen_data');
  add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_images', '_ajax_import_kleinanzeigen_images');
  add_action('wp_ajax_nopriv__ajax_get_product_categories', '_ajax_get_product_categories');
}
add_action('admin_init', 'wbp_admin_init');

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

/**
 * Replace default Elementor image placeholdder
 */
function custom_elementor_placeholder_image()
{
  return get_stylesheet_directory_uri() . '/images/placeholder.jpg';
}
add_filter('elementor/utils/get_placeholder_image_src', 'custom_elementor_placeholder_image');

/**
 * Kleinanzeigen.de
 */
function wbp_get_task_data($products, $ads, $task_type)
{
  $ad_ids = wp_list_pluck($ads, 'id');
  $records = wp_list_pluck((array) $ads, 'id');
  $records = array_flip($records);
  $items = [];
  if ($task_type === 'no-sku') {
    $products_no_sku = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'NOT EXISTS'));
    $record = null;
    foreach ($products_no_sku as $product) {
      $items[] = compact('product', 'task_type', 'record');
    }
    return $items;
  }
  if ($task_type === 'has-sku') {
    $record = null;
    $products_has_sku = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'EXISTS'));
    foreach ($products_has_sku as $product) {
      $sku = (int) $product->get_sku();
      $record = $ads[$records[$sku]];
      $items[] = compact('product', 'task_type', 'record');
    }
    return $items;
  }
  foreach ($products as $product) {
    $post_ID = $product->get_id();
    $sku = (int) $product->get_sku();
    $image = wp_get_attachment_image_url($product->get_image_id());
    $shop_price = wp_kses_post($product->get_price_html());
    $shop_price_raw = $product->get_price();
    $ka_price = '-';
    $title = $product->get_title();
    $permalink = get_permalink($post_ID);

    if (!empty($sku)) {
      switch ($task_type) {
        case 'invalid-ad':
          $record = null;
          if (!in_array($sku, $ad_ids)) {
            $items[] = compact('product', 'task_type', 'record');
          }
          break;
        case 'invalid-price':
          if (in_array($sku, $ad_ids)) {
            $record = $ads[$records[$sku]];
            if (wbp_has_price_diff($record, $product)) {
              $items[] = compact('product', 'task_type', 'record');
            }
          }
          break;
      }
    }
  }
  return $items;
}

function wbp_add_kleinanzeigen_admin_menu_page()
{
  load_textdomain('astra-child', get_stylesheet_directory() . '/languages/de_DE.mo');
  $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjkuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9InV1aWQtY2Y1MDkyNzItMDBlMi00YjJlLWJmZWUtNTZlZjA5ZDhhMDRmIgoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTE1IDEzMSIKCSBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMTUgMTMxOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6I0E3QUFBRDt9Cjwvc3R5bGU+CjxnIGlkPSJ1dWlkLWRhZDJmZmE0LTgzNzctNDg3Ni04OTY0LWQ3ODEyYWE0NGU5MSI+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODAuOCwxMjFjLTE0LjksMC0yMi4yLTEwLjQtMjMuNi0xMi41Yy00LjQsNC4zLTExLDEyLjUtMjMsMTIuNWMtMTMuOCwwLTI1LjQtMTAuNC0yNS40LTI3LjNWMzcuMwoJCUM4LjgsMjAuNCwyMC40LDEwLDM0LjIsMTBzMjUuNCwxMSwyNS40LDI3LjFjMi43LTEsNS41LTEuNCw4LjUtMS40YzE0LjIsMCwyNS40LDExLjYsMjUuNCwyNS42YzAsMy45LTAuNyw3LjQtMi40LDEwLjcKCQljOSw0LDE1LjEsMTMuMSwxNS4xLDIzLjRDMTA2LjIsMTA5LjUsOTQuOCwxMjEsODAuOCwxMjFMODAuOCwxMjF6IE02My4zLDEwMi4zYzMuNyw2LjQsOS44LDEwLjIsMTcuNSwxMC4yCgkJYzkuMywwLDE2LjktNy43LDE2LjktMTcuMWMwLTcuNC00LjgtMTMuOS0xMS42LTE2LjJMNjMuMywxMDIuM0M2My4zLDEwMi4zLDYzLjMsMTAyLjMsNjMuMywxMDIuM3ogTTM0LjIsMTguNQoJCWMtOC40LDAtMTYuOSw1LjgtMTYuOSwxOC44djU2LjNjMCwxMyw4LjUsMTguOCwxNi45LDE4LjhjNi43LDAsMTAuNC0zLjQsMTYuNC05LjRsMi42LTIuN2MtMS40LTQuMS0yLjEtOC42LTIuMS0xMy41VjM3LjMKCQlDNTEuMSwyNC40LDQyLjYsMTguNSwzNC4yLDE4LjVMMzQuMiwxOC41TDM0LjIsMTguNXogTTU5LjYsNDYuNHY0MC40YzAsMi4zLDAuMiw0LjUsMC42LDYuNWwxOC40LTE4LjZjNS4zLTUuNCw2LjQtOS4zLDYuNC0xMy42CgkJYzAtOS4xLTcuMi0xNy4xLTE2LjktMTcuMUM2NSw0NC4yLDYyLjIsNDQuOSw1OS42LDQ2LjRMNTkuNiw0Ni40TDU5LjYsNDYuNHoiLz4KPC9nPgo8L3N2Zz4K';

  add_menu_page(__('Kleinanzeigen', 'astra-child'), __('Kleinanzeigen', 'astra-child'), 'edit_posts', 'kleinanzeigen', 'wbp_display_kleinanzeigen_list', $icon_svg, 10);

  $submenu_items = wbp_kleinanzeigen_get_submenu_items();
  foreach ($submenu_items as $key => $item) {
    add_submenu_page('kleinanzeigen', $item['page_title'], $item['menu_title'], 'edit_posts', $item['menu_slug'], $item['callback'], $item['order']);
  }

  if (!empty($_GET['page']) && 'kleinanzeigen' == $_GET['page']) {

    if (!defined('KLEINANZEIGEN_TEMPLATE_PATH')) {
      // define('KLEINANZEIGEN_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/kleinanzeigen/');
    }
    // require_once __DIR__ . '/includes/kleinanzeigen-ajax-table.php';
  }
  wbp_register_admin_content();
  wbp_kleinanzeigen_register_scripts();
}
add_action('admin_menu', 'wbp_add_kleinanzeigen_admin_menu_page');

function wbp_display_kleinanzeigen_list()
{

  echo '<div id="kleinanzeigen-table-list-wrap">';

  do_action('wbp_kleinanzeigen_admin_header', array('title' => __('Overview', 'astra-child')));

  echo '<div id="pages-results"></div>';

  $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
  wbp_kleinanzeigen_display_admin_page($page);

  do_action('wbp_kleinanzeigen_admin_before_closing_wrap');

  // closes main plugin wrapper div
  echo '</div><!-- END #kleinanzeigen-table-list-wrap -->';
}

function wbp_display_kleinanzeigen_settings()
{

  echo '<div id="kleinanzeigen-settings-wrap">';

  do_action('wbp_kleinanzeigen_admin_header', array('title' => __('Settings', 'astra-child')));

  echo '<div id="page-settings"></div>';

  $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
  wbp_kleinanzeigen_display_admin_page($page);

  do_action('wbp_kleinanzeigen_admin_before_closing_wrap');

  // closes main plugin wrapper div
  echo '</div><!-- END #kleinanzeigen-settings-wrap -->';
}

function wbp_register_admin_content()
{
  add_action('wbp_kleinanzeigen_admin_header', 'output_header', 20);

  add_action('wbp_kleinanzeigen_admin_before_closing_wrap', 'output_before_closing_wrap', 20);
  add_action('wbp_kleinanzeigen_admin_after_page_kleinanzeigen', 'output_after_page', 20);
  add_action('wbp_kleinanzeigen_admin_after_page_kleinanzeigen-settings', 'output_after_page', 20);

  add_action('wbp_kleinanzeigen_admin_page_kleinanzeigen', 'output_dashboard_tab', 20);
  add_action('wbp_kleinanzeigen_admin_page_kleinanzeigen-settings', 'output_settings_tab', 20);
}

function wbp_kleinanzeigen_register_scripts()
{
  add_action('admin_enqueue_scripts', 'wbp_kleinanzeigen_admin_enqueue_styles');
  add_action('admin_enqueue_scripts', 'wbp_add_admin_ajax_scripts');
}

function wbp_kleinanzeigen_admin_enqueue_styles()
{
  wp_enqueue_style('ajax-kleinanzeigen-json', get_stylesheet_directory_uri() . '/style-admin-kleinanzeigen.css', array(), CHILD_THEME_ASTRA_CHILD_VERSION);
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
      'page_title' => esc_html__('Overview', 'astra-child'),
      'menu_title' => esc_html__('Overview', 'astra-child'),
      'menu_slug' => 'kleinanzeigen',
      'callback' => 'wbp_display_kleinanzeigen_list',
      'icon' => 'admin-settings',
      'create_submenu' => true,
      'order' => 0,
    ),
    array(
      'page_title' => esc_html__('Settings', 'astra-child'),
      'menu_title' => esc_html__('Settings', 'astra-child'),
      'menu_slug' => 'kleinanzeigen-settings',
      'callback' => 'wbp_display_kleinanzeigen_settings',
      'icon' => 'admin-settings',
      'create_submenu' => true,
      'order' => 1,
    )
  );

  return $sub_menu_items;
}

function wbp_kleinanzeigen_display_admin_page($slug)
{

  $active_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

  echo '<div class="wbp-kleinanzeigen-page' . ($active_page == $slug ? ' active' : '') . '" data-whichpage="' . $slug . '">';

  echo '<div class="wbp-kleinanzeigen-main">';


  // if no tabs defined for $page then use $slug as $active_tab for load template, doing related actions e t.c.
  $active_tab = $slug;

  do_action('wbp_kleinanzeigen_admin_page_' . $slug, $slug);

  echo '</div><!-- END .wbp-kleinanzeigen-main -->';

  do_action('wbp_kleinanzeigen_admin_after_page_' . $slug, $slug);

  echo '</div><!-- END .wbp-kleinanzeigen-page -->';
}

function output_dashboard_tab()
{
  wbp_include_kleinanzeigen_template('dashboard/dashboard.php', false, array());
}

function output_settings_tab()
{
  wbp_include_kleinanzeigen_template('dashboard/settings.php', false, array());
}

function output_header($args)
{
  wbp_include_kleinanzeigen_template('page-header.php', false, $args);
}

function output_before_closing_wrap()
{
  echo '<div class="sub-content"></div>';
}

function output_after_page()
{
  echo '<div class="after-page"></div>';
}

function wbp_get_json_data($args = array())
{
  $defaults = array(
    'pageSize' => KLEINANZEIGEN_PER_PAGE,
    'paged' => 1
  );
  $options = wp_parse_args($args, $defaults);

  $remoteUrl = KLEINANZEIGEN_CUSTOMER_URL . '?pageNum=' . $options['paged'] . '&pageSize=' . $options['pageSize'];
  $response = wp_remote_get($remoteUrl);
  // $response = file_get_contents(__DIR__ . '/sample' . $page . '.json');
  if (!is_wp_error($response)) {
    return json_decode($response['body']);
  } else {
    return $response;
  }
}

function wbp_error_check($data, $error_template)
{
  if (is_wp_error($data)) {
    die(json_encode(array(

      "head" => wbp_include_kleinanzeigen_template($error_template, true, array('message' => $data->get_error_message()))

    )));
  }
  return $data;
}

function wbp_get_all_ads()
{
  // Get first set of data to discover page count
  $data =  wbp_error_check(wbp_get_json_data(), 'error-message.php');
  $ads = $data->ads;
  $categories = $data->categoriesSearchData;
  $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
  $num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);
  for ($paged = 2; $paged <= $num_pages; $paged++) {
    $page_data  = wbp_error_check(wbp_get_json_data(array('paged' => $paged)), 'error-message.php');
    $ads = array_merge($ads, $page_data->ads);
  }
  return $ads;
}

function wbp_get_product_by_sku_($sku)
{
  global $wpdb;
  $post_ID = $wpdb->get_var($wpdb->prepare("SELECT post_ID FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

  if (isset($post_ID)) {
    return wc_get_product($post_ID);
  }
}

function wbp_get_product_by_sku($sku)
{
  if ($sku) {
    $p = wc_get_products(array(
      'sku' => $sku
    ));

    if (!empty($p)) {
      return $p[0];
    }
  }
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
