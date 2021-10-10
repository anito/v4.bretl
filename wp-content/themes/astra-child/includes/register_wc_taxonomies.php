<?php
function wbp_create_attribute_taxonomies()
{
  $attributes_taxonomies = [
    'feature'
  ];
  foreach ($attributes_taxonomies as $tax) {
    wbp_create_attribute_taxonomy($tax);
  }
}
function wbp_create_attribute_taxonomy($tax)
{
  $attributes = wc_get_attribute_taxonomies();
  $plucked = wp_list_pluck($attributes, 'attribute_name');
  if (empty($plucked[$tax])) {
    $id = wc_create_attribute([
      'name' => $tax,
    ]);
  }
}
add_action('admin_init', 'wbp_create_attribute_taxonomies');

function wbp_get_wc_taxonomies() {
  $taxonomies = get_terms('product_cat', array('hide_empty' => false));
  $tags = get_terms('product_tag', array('hide_empty' => false));
  
  $tax_names = wp_list_pluck($taxonomies, 'name');
  $tag_names = wp_list_pluck($tags, 'name');
  
  if(defined('WC_COMMON_TAXONOMIES')) {

    foreach(WC_COMMON_TAXONOMIES as $name) {
      
      if(! in_array($name, $tax_names)) {
        wbp_add_product_term($name, 'cat');
      }
      if(! in_array($name, $tag_names)) {
        wbp_add_product_term($name, 'tag');
      }
    }

  }

}
add_action('admin_init', 'wbp_get_wc_taxonomies');

function wbp_add_product_term($name, $type) {
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