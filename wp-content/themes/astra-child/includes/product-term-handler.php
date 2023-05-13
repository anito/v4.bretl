<?php
function wbp_process_sale($post_id, $post)
{

  $product = wc_get_product($post_id);

  if (!$product) {
    return;
  }

  $is_on_sale = $product->is_on_sale();

  // Product Sale Category
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_cat');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'cat', $is_on_sale);
  }

  // Product Sale Tag
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_tag');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'tag', $is_on_sale);
  }

  // Product Sale attribute
  if (SYNC_COMMON_TAX_AND_ATTS) {
    wbp_set_pa_term($product, WC_COMMON_TAXONOMIES['sale'], $is_on_sale);
  }
}

// Woo featured taxonomy
function wbp_process_featured($product)
{
  $product_id = $product->get_id();
  $cats = __get_the_terms($product_id, 'product_cat');
  $tags = __get_the_terms($product_id, 'product_tag');

  if (isset($_GET['action']) && $_GET['action'] !== 'woocommerce_feature_product') {
    $is_terms_featured = in_array(WC_COMMON_TAXONOMIES['featured'], array_unique(wp_list_pluck(array_merge($cats, $tags), 'name')));
    $product->set_featured($is_terms_featured);
  }
  $is_featured = $product->is_featured();

  $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_cat');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'cat', $is_featured);
  }

  // Product Featured tag
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['featured'], 'product_tag');
  if ($term) {
    $term_id = $term->term_id;
    wbp_set_product_term($product, $term_id, 'tag', $is_featured);
  }

  // Product Featured attribute
  if (SYNC_COMMON_TAX_AND_ATTS) {
    wbp_set_pa_term($product, WC_COMMON_TAXONOMIES['featured'], $is_featured);
  }
}

function wbp_process_ebay($post_id, $post)
{
  global $wpdb;

  $product = wc_get_product($post_id);

  $title = $product->get_title();
  $prepare = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_status != '%s' AND post_status != '%s' AND post_title != '' AND post_title = %s", 'inherit', 'trash', $title);
  $posts_like_title = $wpdb->get_results($prepare);

  $meta = get_post_meta($post_id);
  $ebay_id = isset($meta['ebay_id'][0]) ? parse_ebay_id($meta['ebay_id'][0]) : "";

  if (!empty($ebay_id)) {
    if (empty($title)) {
      wp_insert_post([
        'ID' => $post_id,
        'post_type' => 'product',
        'post_title' => "Entwurf ID " . $ebay_id
      ]);
    }
    if (count($posts_like_title) >= 2) {
      wp_insert_post([
        'ID' => $post_id,
        'post_type' => 'product',
        'post_content' => $post->post_content,
        'post_title' => wp_strip_all_tags(wbp_sanitize_title($title . " [ DUPLIKAT " . 0 . " ID " . $ebay_id . " ]"))
      ]);
    }

    try {
      $product->set_sku($ebay_id);
    } catch (Exception $e) {
    }
    $product->save();

    update_post_meta((int) $post_id, 'ebay_id', $ebay_id);
    update_post_meta((int) $post_id, 'ebay_url', wbp_get_ebay_url($ebay_id));
  } else {
    $product->set_sku('');
    $product->save();

    delete_post_meta($post_id, 'ebay_id');
    delete_post_meta($post_id, 'ebay_url');
  }

  // Finally set product 'ebay' tag if sku is present
  $term = get_term_by('slug', 'ebay', 'product_tag');
  $term_id = $term->term_id;
  wbp_set_product_term($product, $term_id, 'tag', !empty($ebay_id));
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
 * Add or remove term to/from product attribute
 */
function wbp_set_pa_term($product, $term_name, $bool, $attr = 'specials')
{
  $name = WC_CUSTOM_PRODUCT_ATTRIBUTES[$attr];
  $slug = wc_sanitize_taxonomy_name(stripslashes($name));

  $taxonomy = 'pa_' . $slug;
  $product_id = $product->get_id();
  $attributes = $product->get_attributes();

  $term_ids = [];
  if (!empty($attributes) && isset($attributes[$taxonomy])) {
    $term_ids = array_merge($term_ids, $attributes[$taxonomy]['data']['options']);
  }


  if (!term_exists($term_name, $taxonomy)) {
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

function wbp_get_product_term($name, $type)
{
  $term_id = get_term_by('name', 'product_' . $type);
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

function __get_the_terms($post_id, $taxonomy)
{
  $terms = get_the_terms($post_id, $taxonomy);
  if ($terms) {
    return $terms;
  } else {
    return array();
  }
}
