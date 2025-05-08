<?php

/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

function twentytwentyfive_child_init()
{
  /**
   * Define Constants
   */
  define('CHILD_THEME_VERSION', wp_get_theme()->get('Version'));
  define('AJAX_FRONT_VARS', array(
    'admin_ajax'  => admin_url('admin-ajax.php'),
    'is_login'    => is_login(),
    'user'        => json_encode(twentytwentyfive_get_current_user()),
    'home_url'    => home_url(),
    'nonce'       => wp_create_nonce()
  ));
  // IUBENDA
  define('IUBENDA_VARS', array(
    'siteId' => defined('IUBENDA_SITE_ID') ? IUBENDA_SITE_ID : '',
    'cookiePolicyId' => defined('IUBENDA_COOKIE_POLICY_ID') ? IUBENDA_COOKIE_POLICY_ID : ''
  ));
}
add_filter('init', 'twentytwentyfive_child_init');

// Enqueues style.css on the front.
if (! function_exists('twentytwentyfive_child_enqueue_styles')) :
  /**
   * Enqueues style.css on the front.
   *
   * @since Twenty Twenty-Five 1.0
   *
   * @return void
   */
  function twentytwentyfive_child_enqueue_styles()
  {
    wp_enqueue_style(
      "twentytwentyfive-style",
      get_parent_theme_file_uri('/style.css'),
      array(),
      CHILD_THEME_VERSION
    );
    wp_enqueue_style(
      'twentytwentyfive-child-style',
      get_stylesheet_directory_uri() . '/style.css',
      array(),
      CHILD_THEME_VERSION
    );
    // Iubenda
    wp_enqueue_script(
      'iubenda',
      get_stylesheet_directory_uri() . '/js/iubenda.js',
      array(),
      CHILD_THEME_VERSION,
      true
    );
    wp_enqueue_script(
      'iubenda-autoblocking',
      'https://cs.iubenda.com/autoblocking/' . IUBENDA_VARS['siteId'] . '.js',
      array('iubenda'),
      CHILD_THEME_VERSION,
      true
    );
    wp_enqueue_script(
      'iubenda-cs',
      '//cdn.iubenda.com/cs/iubenda_cs.js',
      array('iubenda'),
      CHILD_THEME_VERSION,
      true
    );
    wp_localize_script('iubenda', 'Iubenda', IUBENDA_VARS);
  }
endif;
add_action('wp_enqueue_scripts', 'twentytwentyfive_child_enqueue_styles');

if (! function_exists('twentytwentyfive_child_parse_taxonomy_root_request')) :
  function twentytwentyfive_child_parse_taxonomy_root_request($wp)
  {
    if (!isset($wp->query_vars['name']))
      return;

    $tax_name      = 'product_' . $wp->query_vars['name'];

    // Bail out if no taxonomy QV was present, or if the term QV is.
    if (empty($tax_name) || isset($wp->query_vars['term']))
      return;

    $tax           = get_taxonomy($tax_name);
    $tax_query_var = $tax->query_var;

    // Bail out if a tax-specific qv for the specific taxonomy is present.
    if (isset($wp->query_vars[$tax_query_var]))
      return;

    $tax_term_slugs = get_terms(
      [
        'taxonomy' => $tax_name,
        'fields'   => 'slugs'
      ]
    );

    if (is_wp_error($tax_term_slugs)) {
      return;
    }

    // Unlike "taxonomy"/"term" QVs, tax-specific QVs can specify an AND/OR list of terms.
    $wp->set_query_var($tax_query_var, implode(',', $tax_term_slugs));
  }
endif;
add_action('parse_request', 'twentytwentyfive_child_parse_taxonomy_root_request');

if (! function_exists('twentytwentyfive_child_register_tax_root_rewrite')) :
  function twentytwentyfive_child_register_tax_root_rewrite($name, $types, $tax)
  {
    if (empty($tax['publicly_queryable']))
      return;

    $slug = (empty($tax['rewrite']) || empty($tax['rewrite']['slug'])) ? $name :  $tax['rewrite']['slug'];

    add_rewrite_rule("^$slug/?$", "index.php?taxonomy=$name", 'top');
  }
endif;
add_action('registered_taxonomy', 'twentytwentyfive_child_register_tax_root_rewrite', 10, 3);

if (! function_exists('twentytwentyfive_child_template')) :
  function twentytwentyfive_child_template($template)
  {
    // if (is_tax('product_brand') && is_post_type_archive('product_brand'))
    if (is_tax('product_brand'))
      return get_taxonomy_template();

    return $template;
  }
endif;
add_filter('template_include', 'twentytwentyfive_child_template');

if (! function_exists('twentytwentyfive_get_current_user')) :
  function twentytwentyfive_get_current_user()
  {
    $cur_user = wp_get_current_user();
    unset($cur_user->user_pass);
    return $cur_user;
  }
endif;

/**
 *   Register mime type SVG
 *
 *   @param $mimes WP types array
 *
 *   @return array
 */
if (! function_exists('twentytwentyfive_allow_mime_type_svg')) :
  function twentytwentyfive_allow_mime_type_svg($mimes)
  {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
  }
endif;
add_filter('upload_mimes', 'twentytwentyfive_allow_mime_type_svg');
