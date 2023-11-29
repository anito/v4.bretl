<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Term_Handler` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Term_Handler')) {


  class Kleinanzeigen_Term_Handler {

    private static $instance = null;

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

    // Remove default category if necessary
    public function maybe_remove_default_cat($post_ID)
    {
      $default_cat_id = get_option('default_product_cat');
      $cat_terms = $this->get_product_cats($post_ID);
      $ids = wp_list_pluck($cat_terms, 'term_id');
    
      $count = count($ids);
      if (in_array($default_cat_id, $ids) && 2 <= $count) {
        $this->set_product_term($post_ID, $default_cat_id, 'cat', false);
      }
    }
    
    public function process_sale($post_ID, $post)
    {
    
      $product = wc_get_product($post_ID);
    
      if (!$product) {
        return;
      }
    
      $is_on_sale = $product->is_on_sale();
    
      // Product Sale Category
      $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_cat');
      $term_id = $term->term_id;
      if ($term_id) {
        $this->set_product_term($product, $term_id, 'cat', $is_on_sale);
      }
    
      // Product Sale Tag
      $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_tag');
      $term_id = $term->term_id;
      if ($term_id) {
        $this->set_product_term($product, $term_id, 'tag', $is_on_sale);
      }
    
      // Product Sale Label
      $term = get_term_by('name', WC_PRODUCT_LABELS['sale'], 'product_label');
      $term_id = $term->term_id;
      if ($term_id) {
        $this->set_product_term($product, $term_id, 'label', $is_on_sale);
      }
    
      // Product Sale Attribute
      if (SYNC_COMMON_TAX_AND_ATTS) {
        // $this->set_pa_term($product, WC_COMMON_TAXONOMIES['sale'], $is_on_sale);
      }
    }
    
    // Woo featured taxonomy
    public function process_featured($product)
    {
      $product_id = $product->get_id();
      $cats = $this->get_product_cats($product_id);
      $cats = !is_array($cats) ? array() : $cats;
      $tags = $this->get_product_tags($product_id);
      $tags = !is_array($tags) ? array() : $tags;
    
      if (isset($_GET['action']) && $_GET['action'] !== 'woocommerce_feature_product') {
        $is_terms_featured = in_array(WC_COMMON_TAXONOMIES['featured'], array_unique(wp_list_pluck(array_merge($cats, $tags), 'name')));
        $product->set_featured($is_terms_featured);
      }
      $is_featured = $product->is_featured();
    
      $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_cat');
      if ($term) {
        $term_id = $term->term_id;
        $this->set_product_term($product, $term_id, 'cat', $is_featured);
      }
    
      // Product Featured tag
      $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_tag');
      if ($term) {
        $term_id = $term->term_id;
        $this->set_product_term($product, $term_id, 'tag', $is_featured);
      }
    
      // Product Featured attribute
      if (SYNC_COMMON_TAX_AND_ATTS) {
        // $this->set_pa_term($product, WC_COMMON_TAXONOMIES['featured'], $is_featured);
      }
    }

    public function set_product_term($product, $term_id, $type, $bool)
    {
      if ($product instanceof WC_Product) {
        $product_id = $product->get_id();;
      } else {
        $product_id = $product;
      }
      $terms = get_the_terms($product_id, 'product_' . $type);
      $term_ids = wp_list_pluck($terms, 'term_id');
      $term_ids = array_unique(array_map('intval', $term_ids));
      $term_ids = $this->toggle_array_item($term_ids, $term_id, $bool);

      return wp_set_object_terms($product_id, $term_ids, 'product_' . $type);
    }

    public function set_product_term_($product, $term_ids, $type, $bool)
    {
      if (!is_array($term_ids)) {
        $term_ids = array($term_ids);
      }
      $term_ids = array_map('intval', $term_ids);

      if ($product instanceof WC_Product) {
        $product_id = $product->get_id();;
      } else {
        $product_id = $product;
      }

      $set_term = function($term_id) use($product_id, $type, $bool) {
        $terms = get_the_terms($product_id, 'product_' . $type);
        $term_ids = wp_list_pluck($terms, 'term_id');
        $term_ids = array_unique(array_map('intval', $term_ids));
        $term_ids = $this->toggle_array_item($term_ids, $term_id, $bool);

        return wp_set_object_terms($product_id, $term_ids, 'product_' . $type);
      };


      $i = 0;
      $_term_ids = [];
      while ($i < count($term_ids)) {
        $ids = $set_term($term_ids[$i]);
        if(! is_wp_error($ids)) {
          $term_ids = array_unique(array_merge($_term_ids, $ids));
        }
        $i++;
      }
    
      return $_term_ids;
      // return wp_get_object_terms($product_id, 'product_' . $type);
    }
    
    /**
     * Add or remove term to/from product attribute
     */
    public function set_pa_term($product, $taxonomy_name, $term_name, $bool, $args = array())
    {
      $taxonomy = wc_attribute_taxonomy_name($taxonomy_name);
      $product_id = $product->get_id();
    
      if (!term_exists($term_name, $taxonomy)) {
        wp_insert_term($term_name, $taxonomy);
      }
    
      $terms = wp_get_object_terms($product_id, $taxonomy);
      $term_ids = wp_list_pluck($terms, 'term_id');
    
      $term_id = get_term_by('name', $term_name, $taxonomy)->term_id;
      $term_ids = $this->toggle_array_item($term_ids, $term_id, $bool); // remove or add the term
    
      wp_set_object_terms($product_id, $term_ids, $taxonomy);
    
      $prev_attributes = get_post_meta($product_id, '_product_attributes', true);
      $attributes = ! empty($prev_attributes) ? $prev_attributes : array();
      $attributes[$taxonomy] = wp_parse_args($args, array(
        'name'          => $taxonomy,
        'value'         => $term_ids,
        'position'      => 1,
        'is_visible'    => 1,
        'is_variation'  => 0,
        'is_taxonomy'   => 1
      ));
    
      update_post_meta($product_id, '_product_attributes', $attributes);
      return $term_ids;
    }

    public function get_product_terms($post,
      $type
    ) {
      return get_the_terms($post, 'product_' . $type);
    }
    public function get_product_brands($post)
    {
      return $this->get_product_terms($post, 'brand');
    }
    public function get_product_cats($post)
    {
      return $this->get_product_terms($post, 'cat');
    }
    public function get_product_tags($post)
    {
      return $this->get_product_terms($post, 'tag');
    }
    public function get_product_labels($post)
    {
      return $this->get_product_terms($post, 'label');
    }

    public function toggle_array_item($ids, $id, $bool = null)
    {
      if (!isset($bool)) {
        $bool = in_array($id, $ids);
      }
      # remove id
      $ids = array_diff($ids, array($id));
      if (true === $bool) {
        $ids[] = $id;
      }
      return $ids;
    }

    public function add_the_product_term($name, $type)
    {
      $term = wp_insert_term(
        $name,
        'product_' . $type,
      );
      if (is_wp_error($term)) {
        $term_id = $term->error_data['term_exists'] ?? null;
      } else {
        $term_id = $term['term_id'];
      }
      return $term_id;
    }

    public function get_wc_taxonomies()
    {
      $cats = get_terms('product_cat', array('hide_empty' => false));
      $cat_names = wp_list_pluck($cats, 'name');

      $tags = get_terms('product_tag', array('hide_empty' => false));
      $tag_names = wp_list_pluck($tags, 'name');

      if (defined('WC_COMMON_TAXONOMIES')) {

        foreach (WC_COMMON_TAXONOMIES as $name) {

          if (!in_array($name, $cat_names)) {
            $this->add_the_product_term($name, 'cat');
          }
          if (!in_array($name, $tag_names)) {
            $this->add_the_product_term($name, 'tag');
          }
        }
      }
    }
  }
}

if (!function_exists('wbp_th')) {

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return object
   */
  function wbp_th()
  {
    return Kleinanzeigen_Term_Handler::get_instance();
  }
}

wbp_th();




