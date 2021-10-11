<?php
function wbp_process_sale($post_id, $post)
{

  $product = wc_get_product($post_id);

  if (!$product) {
    return;
  }

  $is_on_sale = $product->is_on_sale();

  // Product Category
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_cat');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'cat', $is_on_sale);
  }

  // Product Tag
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_tag');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'tag', $is_on_sale);
  }

  // Product Feature attribute
  wbp_set_pa_feature_term($product, WC_COMMON_TAXONOMIES['sale'], $is_on_sale);
}

function wbp_process_featured($product)
{
  $is_featured = $product->is_featured();

  $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_cat');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'cat', $is_featured);
  }

  $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_tag');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'tag', $is_featured);
  }

  wbp_set_pa_feature_term($product, WC_COMMON_TAXONOMIES['featured'], $is_featured);
}

function wbp_set_product_term($product, $term_id, $type, $bool)
{
  $product_id = $product->get_id();

  switch ($type) {
    case 'cat':
      $term_ids = $product->get_category_ids();
      break;
    case 'tag':
      $term_ids = $product->get_tag_ids();
  }
  $term_ids = array_unique(array_map('intval', $term_ids));
  $term_ids = wbp_sanitize_ids($term_ids, $term_id, $bool);

  wp_set_object_terms($product_id, $term_ids, 'product_' . $type);
}

/**
 * Add or remove the "sale" term to/from product attribute "Merkmale"
 */
function wbp_set_pa_feature_term($product, $term_name, $bool)
{
  $name = WC_CUSTOM_PRODUCT_ATTRIBUTES['default'];
  $slug = wc_sanitize_taxonomy_name(stripslashes($name));

  $taxonomy = 'pa_' . $slug;
  $product_id = $product->get_id();
  $attributes = $product->get_attributes();

  $term_ids = [];
  if(! empty($attributes) && isset($attributes[$taxonomy])) {
    $term_ids = array_merge($term_ids, $attributes[$taxonomy]['data']['options']);
  }
  

  if(! term_exists($term_name, $taxonomy)) {
    wp_insert_term($term_name, $taxonomy);
  }

  $term_id = get_term_by('name', $term_name, $taxonomy)->term_id;
  $term_ids = wbp_sanitize_ids($term_ids, $term_id, $bool); // remove or add the term

  wp_set_object_terms($product_id, $term_ids, $taxonomy);

  $attributes[$taxonomy] = array(
    'name'          => $taxonomy,
    'value'         => $term_ids,
    'position'      => 1,
    'is_visible'    => 1,
    'is_variation'  => 0,
    'is_taxonomy'   => '1'
  );

  update_post_meta($product_id, '_product_attributes', $attributes);
}

function wbp_sanitize_ids($ids=[], $id, $bool)
{
  if (!$bool) {
    # remove id
    $ids = array_diff($ids, array($id));
  } else {
    # add id
    $ids = array_diff($ids, array($id));
    $ids[] = $id;
  }
  return $ids;
}
