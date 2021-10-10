<?php
function wbp_process_sales($post_id, $post)
{

  $product = wc_get_product($post_id);

  if (!$product) {
    return;
  }

  $is_on_sale = $product->is_on_sale();

  // Product Category
  $term = get_term_by('name', 'Aktion', 'product_cat');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'cat', $is_on_sale);
  }

  // Product Tag
  $term = get_term_by('name', 'Aktion', 'product_tag');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'tag', $is_on_sale);
  }

  // Product Feature attribute
  wbp_set_pa_feature_term($product, 'Aktion', $is_on_sale);
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
 * Add or remove the "sale" term to/from product "Feature"
 */
function wbp_set_pa_feature_term($product, $term, $is_on_sale)
{
  $taxonomy = 'pa_merkmale';
  $product_id = $product->get_id();

  // $terms = wc_get_product_terms($product_id, $taxonomy); // returns WP_Term array
  $atts = $product->get_attribute($taxonomy); // returns comma separated string
  $atts = explode(', ', $atts);
  $atts = wbp_sanitize_ids($atts, $term, $is_on_sale);

  wp_set_object_terms($product_id, $atts, 'pa_merkmale');
}

function wbp_sanitize_ids($ids, $id, $bool)
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
