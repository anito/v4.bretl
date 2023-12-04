<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Register_WC_Taxonomies` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Register_WC_Taxonomies')) {


  class Kleinanzeigen_Register_WC_Taxonomies
  {

    private static $instance = null;

    private $th = null;

    public function __construct()
    {
    }

    public static function get_instance()
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance) {
        self::$instance = new self;
      }
      return self::$instance;
    }

    public function copy_brand_terms()
    {
      $terms = get_terms('product_brands', array('hide_empty' => false));

      foreach ($terms as $term) {
        wp_insert_term($term->name, 'product_brand', array(
          'alias_of'    => '',
          'description' => $term->description,
          'parent'      => 0,
          'slug'        => $term->slug,
        ));
      }

      $products = wc_get_products(array(
        'status' => array('draft', 'pending', 'private', 'publish'),
        'limit' => -1
      ));

      foreach ($products as $product) {
        $post_ID = $product->get_id();
        $terms = wbp_th()->get_product_terms($post_ID, 'brands');
        if ($terms) {
          $term_slugs = wp_list_pluck($terms, 'slug');
          foreach ($terms as $term) {
            $result = wp_set_object_terms($post_ID, $term_slugs, 'product_brand', true);
          }
        }
      }
    }

    public function create_terms()
    {
      if (defined('WC_TERMS')) {

        $tags = get_terms('product_tag', array('hide_empty' => false));
        $tag_names = wp_list_pluck($tags, 'name');

        // $this->term_handler->
        foreach (WC_TERMS as $slug => $names) {
          if (!is_array($names)) {
            $names = array($names);
          }

          foreach($names as $name) {
            if (!in_array($name, $tag_names)) {
              wbp_th()->add_the_product_term($name, 'tag');
            }
          }
        }
      }
    }

    public function create_attribute_taxonomies()
    {
      if (!class_exists('WooCommerce', false)) {
        return;
      }

      $taxonomies = wc_get_attribute_taxonomies();
      if (defined('WC_CUSTOM_PRODUCT_ATTRIBUTES')) {
        foreach (WC_CUSTOM_PRODUCT_ATTRIBUTES as $name) {
          $labels = wp_list_pluck($taxonomies, 'attribute_label');
          $labels = array_flip($labels);
          if (empty($labels[$name])) {
            wc_create_attribute([
              'name' => $name,
              'has_archives' => 1
            ]);
          }
        }
      }
    }

    public function create_taxonomy_product_labels()
    {
      $terms = get_terms('product_label', array('hide_empty' => false));
      $term_names = wp_list_pluck($terms, 'name');

      if (defined('WC_PRODUCT_LABELS')) {

        foreach (WC_PRODUCT_LABELS as $name) {

          if (!in_array($name, $term_names)) {
            wbp_th()->add_the_product_term($name, 'label');
          }
        }
      }
    }
  }
}

if (!function_exists('wbp_rt')) {

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return object
   */
  function wbp_rt(): Kleinanzeigen_Register_WC_Taxonomies
  {
    return Kleinanzeigen_Register_WC_Taxonomies::get_instance();
  }
}

wbp_rt();
