<?php
function wbp_create_terms()
{
  if (defined('WC_TERMS')) {

    $tags = get_terms('product_tag', array('hide_empty' => false));
    $tag_slugs = wp_list_pluck($tags, 'slug');

    foreach (WC_TERMS as $slug => $name) {

      if (!in_array($slug, $tag_slugs)) {
        wbp_add_product_term($slug, 'tag');
      }
    }
  }
}
add_action('admin_init', 'wbp_create_terms');

function wbp_create_attribute_taxonomies()
{
  if (!class_exists('WooCommerce', false)) {
    return;
  }

  $attributes_taxonomies = [];
  if (defined('WC_CUSTOM_PRODUCT_ATTRIBUTES')) {
    foreach (WC_CUSTOM_PRODUCT_ATTRIBUTES as $attribute) {
      $attributes_taxonomies[] = $attribute;
    }
  }

  foreach ($attributes_taxonomies as $tax) {
    $attributes = wc_get_attribute_taxonomies();
    $attribute = wp_list_pluck($attributes, 'attribute_name');
    if (empty($attribute[$tax])) {
      wc_create_attribute([
        'name' => $tax,
        'has_archives' => 1
      ]);
    }
  }
}
add_action('admin_init', 'wbp_create_attribute_taxonomies');

function wbp_create_taxonomy_product_labels()
{
  $terms = get_terms('product_label', array('hide_empty' => false));
  $term_names = wp_list_pluck($terms, 'name');

  if (defined('WC_PRODUCT_LABELS')) {

    foreach (WC_PRODUCT_LABELS as $name) {

      if (!in_array($name, $term_names)) {
        wbp_add_product_term($name, 'label');
      }
    }
  }
}
add_action('init', 'wbp_create_taxonomy_product_labels');

function wbp_get_wc_taxonomies()
{
  $cats = get_terms('product_cat', array('hide_empty' => false));
  $cat_names = wp_list_pluck($cats, 'name');

  $tags = get_terms('product_tag', array('hide_empty' => false));
  $tag_names = wp_list_pluck($tags, 'name');

  if (defined('WC_COMMON_TAXONOMIES')) {

    foreach (WC_COMMON_TAXONOMIES as $name) {

      if (!in_array($name, $cat_names)) {
        wbp_add_product_term($name, 'cat');
      }
      if (!in_array($name, $tag_names)) {
        wbp_add_product_term($name, 'tag');
      }
    }
  }
}
add_action('admin_init', 'wbp_get_wc_taxonomies');

function wbp_add_product_term($name, $type)
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
