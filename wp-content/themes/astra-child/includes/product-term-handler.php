<?php
function wbp_process_sale($post_ID, $post)
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
    wbp_set_product_term($product, $term_id, 'cat', $is_on_sale);
  }

  // Product Sale Tag
  $term = get_term_by('name', WC_COMMON_TAXONOMIES['sale'], 'product_tag');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'tag', $is_on_sale);
  }

  // Product Sale Label
  $term = get_term_by('name', WC_PRODUCT_LABELS['sale'], 'product_label');
  $term_id = $term->term_id;
  if ($term_id) {
    wbp_set_product_term($product, $term_id, 'label', $is_on_sale);
  }

  // Product Sale Attribute
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

function wbp_find_kleinanzeige(int $id): stdClass | null
{
  $pageNum = 1;
  while ($pageNum <= KLEINANZEIGEN_TOTAL_PAGES) {
    $data = wbp_get_json_data($pageNum);
    $ads = $data->ads;
    foreach ($ads as $val) {
      if ($val->id == (int) $id) {
        $ad = $val;
        $pageNum = KLEINANZEIGEN_TOTAL_PAGES;
        break;
      }
    };
    $pageNum++;
  }
  return $ad ?? null;
}

function wbp_process_kleinanzeigen($post_ID, $post)
{
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

  $kleinanzeigen_id = sanitize_text_field($_POST['kleinanzeigen_id']);

  $product = wc_get_product($post_ID);
  $title = $product->get_title();
  $content = $product->get_description();

  if (!empty($kleinanzeigen_id)) {

    $ad = wbp_find_kleinanzeige($kleinanzeigen_id);
    $ad_title = $ad->title;
    $sku_error = false;

    remove_action('save_post', 'wbp_save_post', 99);

    if (empty($title)) {
      $title = $ad_title;
      $content = __('Ready to import ad. Click "Import ad" to start.', 'astra-child');
    }

    $sku = $product->get_sku();
    if ($sku !== $kleinanzeigen_id) {
      try {
        $product->set_sku($kleinanzeigen_id);
        $product->save();
      } catch (Exception $e) {
        $sku_error = true;
      }
    }

    // $prepare = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_status != '%s' AND post_status != '%s' AND post_title != '' AND post_title = %s", 'inherit', 'trash', $ad_title);
    // $results = $wpdb->get_results($prepare);
    // $title_exists = count($results) >= 2;

    if ($sku_error) {
      $title = wp_strip_all_tags(wbp_sanitize_title($ad_title . " [ DUPLIKAT " . 0 . " ID " . $kleinanzeigen_id . " ]"));
      $content = '<b style="color: red;">' . __('A Product with the same Ad ID already exists. Enter a different Ad ID or delete this draft.', 'astra-child') . '</b>';
    }

    wp_insert_post([
      'ID' => $post_ID,
      'post_type' => 'product',
      'post_status' => $_POST['post_status'],
      'post_content' => $content,
      'post_excerpt' => $product->get_short_description(),
      'post_title' => $title
    ]);

    if ($sku_error) {
      disable_sku($post_ID);
    } else {
      enable_sku($post_ID, $ad);
    }
  } else {
    disable_sku($post_ID);
  }
}

function wbp_set_product_term($product, $term_id, $type, $bool)
{
  $product_id = $product->get_id();
  $term_ids = wp_list_pluck(get_the_terms($product_id, 'product_' . $type), 'term_id');
  $term_ids = array_unique(array_map('intval', $term_ids));
  $term_ids = wbp_toggle_array_item($term_ids, $term_id, $bool);

  return wp_set_object_terms($product_id, $term_ids, 'product_' . $type);
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
  $term_ids = wbp_toggle_array_item($term_ids, $term_id, $bool); // remove or add the term

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

function wbp_toggle_array_item($ids, $id, $bool = null)
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

function __get_the_terms($post_ID, $taxonomy)
{
  $terms = get_the_terms($post_ID, $taxonomy);
  if ($terms) {
    return $terms;
  } else {
    return array();
  }
}

function enable_sku($post_ID, $ad)
{
  update_post_meta((int) $post_ID, 'kleinanzeigen_id', $ad->id);
  update_post_meta((int) $post_ID, 'kleinanzeigen_url', wbp_get_kleinanzeigen_url($ad->url));
  update_post_meta((int) $post_ID, 'kleinanzeigen_search_url', wbp_get_kleinanzeigen_search_url($ad->id));
  update_post_meta((int) $post_ID, 'kleinanzeigen_record', html_entity_decode(json_encode($ad, JSON_UNESCAPED_UNICODE)));
}

function disable_sku($post_ID)
{
  $product = wc_get_product($post_ID);
  $product->set_sku('');
  $product->save();

  delete_post_meta($post_ID, 'kleinanzeigen_id');
  delete_post_meta($post_ID, 'kleinanzeigen_url');
  delete_post_meta($post_ID, 'kleinanzeigen_search_url');
  delete_post_meta($post_ID, 'kleinanzeigen_record');
}
