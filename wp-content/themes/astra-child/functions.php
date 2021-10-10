<?php
require_once __DIR__ . '/includes/sender_email.php';
require_once __DIR__ . '/includes/duplicate_content.php';

/**
 * Define Constants
 */
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.2');

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

function wbp_check_sale($post_id, $post)
{
  if (!class_exists('WooCommerce', false)) {
    return;
  }

  require_once __DIR__ . '/includes/product_term_handler.php';

  $product = wc_get_product($post_id);

  if (!$product) {
    return 0;
  }

  if ($product->is_type('variation')) {
    $variation = new WC_Product_Variation($product);
    $post_id = $variation->get_parent_id();
    $product = wc_get_product($post_id);
  }

  wbp_process_sales($post_id, $post);
}
add_action("save_post", "wbp_check_sale", 99, 3);
add_action("woocommerce_before_product_object_save", "wbp_check_sale", 99, 2);

function wbp_check_featured_before_save($product, $data_store)
{
  require_once __DIR__ . '/includes/product_term_handler.php';

  $is_featured = $product->is_featured();

  $term = get_term_by('name', 'Featured', 'product_cat');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'cat', $is_featured);
  }

  $term = get_term_by('name', 'Featured', 'product_tag');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'tag', $is_featured);
  }

  wbp_set_pa_feature_term($product, 'Featured', $is_featured);
}
add_action("woocommerce_before_product_object_save", "wbp_check_featured_before_save", 99, 2);

function add_scripts()
{
    wp_enqueue_style("parent-style", get_parent_theme_file_uri('/style.css'));

    wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

    wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

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
 * Register some extra Footer
 */
function wbp_register_sidebar_widgets()
{
    register_sidebar(array(
        'name' => __('Account Sidebar', 'astra-child'),
        'id' => 'wbp_account',
        'before_widget' => '<div class="column widget %2$s" id="%1$s">',
        'after_widget' => '</div>',
        'before_title' => '<span class="widgettitle">',
        'after_title' => '</span>',
        'description' => __('Sidebar for Account', 'astra-child'),
    ));
}
add_action('widgets_init', 'wbp_register_sidebar_widgets', 11);

/**
 * Default sort for shop and specific categories
 */
function custom_default_orderby($sortby)
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
add_filter('woocommerce_default_catalog_orderby', 'custom_default_orderby');

/**
 * Unsupprted Browsers IE 11 and lower
 */
function detectTrident($current_theme)
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
add_action('wp_enqueue_scripts', 'detectTrident');

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
 * Replace Elementors with Woos Placeholder Image (which we can define in the woo product settings)
 */
add_filter('jet-woo-builder/template-functions/product-thumbnail-placeholder', 'wbp_get_wc_placeholder_image');

function wbp_get_wc_placeholder_image($default_placeholder)
{
    $placeholder = wc_placeholder_img_src('woocommerce_image');

    return $placeholder;
}
/**
 * In order to improve SEO,
 * display the product title in product description
 *
 */
add_filter('woocommerce_product_tabs', 'woo_custom_description_tab', 98);
function woo_custom_description_tab($tabs)
{
    // Get $product object
    global $product;

    $tabs['description'] = array(
        'title' => __('Description', 'woocommerce'),
        'callback' => 'woo_custom_description_tab_content',
    );

    return $tabs;
}

function woo_custom_description_tab_content($tab_name, $tab)
{
    global $product;
    $title = $product->get_title();
    $content = $product->get_description();
    // don't forget to process any shortcode
    echo '<h3>' . $title . ': Highlights</h3>' . do_shortcode($content);
}
