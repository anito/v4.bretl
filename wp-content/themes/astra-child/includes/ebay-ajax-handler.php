<?php
function parse_ebay_id($val)
{
  preg_match('/(\/?)(\d{8,})/', $val, $matches);
  if (isset($matches[2])) {
    return $matches[2];
  }
  return false;
}
function is_valid_title($val)
{
  preg_match('/\[ DUPLIKAT \d+ ID \d{8,} \]/', $val, $matches);
  if (isset($matches[0])) {
    return false;
  }
  return true;
}

function wbp_increment($matches)
{
  return $matches[1] . ++$matches[2] . $matches[3];
}

function wbp_sanitize_title($title, $appendix = "")
{
  preg_match('/((?!DUPLIKAT \d+ ID).)*(\[ DUPLIKAT \d+ ID \d{8,} \])/', $title . $appendix, $matches);
  if (isset($matches[0])) {
    return preg_replace_callback('/(.*\[ DUPLIKAT )(\d+)( ID \d{8,} \])/', "wbp_increment", $matches[0]);
  }
  return $title;
}

function wbp_sanitize_excerpt($content, $count)
{
  $content = preg_replace('/<[^>]*>/', ' ', $content); //clear html tags
  $content = preg_replace('/[\s+\n]/', ' ', $content); // clear multiple whitespace
  return substr($content, 0, $count);
}

function wbp_get_remote()
{
  if (isset($_POST['formdata'])) {
    $formdata = $_POST['formdata'];
    $post_ID = isset($formdata['post_ID']) ? $formdata['post_ID'] : new WC_Product();
    $ebay_id_raw = isset($formdata['ebay_id']) ? $formdata['ebay_id'] : null;
    $ebay_id = parse_ebay_id($ebay_id_raw);

    $remoteUrl = wbp_get_ebay_url($ebay_id);
  } else {
    $remoteUrl = home_url();
  }

  $response = wp_remote_get($remoteUrl);

  echo json_encode(
    [
      'post_ID' => $post_ID,
      'ebay_id' => $ebay_id,
      'content' => $response
    ]
  );
  wp_die();
}

function wbp_publish_post()
{
  $post_ID = isset($_POST['post_ID']) ? (int) $_POST['post_ID'] : null;
  if ($post_ID) {
    wp_update_post(array(
      'ID' => $post_ID,
      'post_status' => 'publish',
    ));
  }

  ob_start();

  $table = new Extended_WC_Admin_List_Table_Products();
  $table->render_row($post_ID);

  echo json_encode([
    'html' => ob_get_clean(),
    'post' => compact(['post_ID'])
  ]);

  // $post = get_post($post_ID);
  // echo json_encode([
  //   'success' => $post_ID === $result,
  //   'data' => compact(['post_ID', 'error']),
  // ]);
  wp_die();
}

function wbp_import_ebay_data()
{
  $postdata = isset($_POST['postdata']) ? $_POST['postdata'] : null;
  if ($postdata && isset($postdata['post_ID'])) {
    $post_ID = (int) $postdata['post_ID'];
  }
  if ($postdata && isset($postdata['ebay_id'])) {
    $ebay_id_raw = $postdata['ebay_id'];
    $ebay_id = parse_ebay_id($ebay_id_raw);
  }
  $ebaydata = isset($_POST['ebaydata']) ? $_POST['ebaydata'] : null;
  if (
    ($ebaydata) &&
    ($title = isset($ebaydata['title']) ? $ebaydata['title'] : null) &&
    ($price = isset($ebaydata['price']) ? $ebaydata['price'] : null) &&
    ($content = isset($ebaydata['description']) ? $ebaydata['description'] : null)
  ) {

    if (!isset($post_ID)) {
      $product = new WC_Product();
      $product->set_name($title);
      $product->save();
      $post_ID = $product->get_id();
    } else {
      $product = wc_get_product($post_ID);
    }

    if ($product) {
      $product->set_regular_price($price);
      try {
        $product->set_sku($ebay_id);
      } catch (Exception $e) {
      }

      $product->save();
    }

    update_post_meta((int) $post_ID, 'ebay_id', $ebay_id);
    update_post_meta((int) $post_ID, 'ebay_url', EBAY_URL . '/s-' . $ebay_id . '/k0');

    wp_insert_post(array(
      'ID' => $post_ID,
      'post_title' => $title,
      'post_type' => 'product',
      'post_status' => 'draft',
      'post_content' => $content,
      'post_excerpt' => wbp_sanitize_excerpt($content, 300)
    ), true);
  }

  ob_start();

  $table = new Extended_WC_Admin_List_Table_Products();
  $table->render_row($post_ID);

  echo json_encode([
    'html' => ob_get_clean(),
    'post' => compact(['post_ID'])
  ]);

  // echo json_encode([
  //   'success' => $post_ID === $result,
  //   'data' => compact(['post_ID', 'ebay_id', 'post_status', 'price', 'content']),
  // ]);

  get_sample_permalink(get_post($post_ID));
  wp_die();
}

function wbp_import_ebay_images()
{
  $postdata = $_POST['postdata'];
  if (isset($postdata['post_ID'])) {
    $post_ID = (int) $postdata['post_ID'];
  }
  if (isset($postdata['ebay_id'])) {
    $ebay_id_raw = $postdata['ebay_id'];
    $ebay_id = parse_ebay_id($ebay_id_raw);
  }
  $ebaydata = $_POST['ebaydata'];
  $ebay_images = isset($ebaydata['images']) ? $ebaydata['images'] : [];

  wbp_remove_attachments($post_ID);

  $ids = [];
  for ($i = 0; $i < count($ebay_images); $i++) {
    $url = $ebay_images[$i];
    $ids[] = wbp_upload_image($url, $post_ID);
    if ($i === 0) {
      set_post_thumbnail((int) $post_ID, $ids[0]);
    }
  }

  unset($ids[0]);
  update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
  update_post_meta((int) $post_ID, 'ebay_id', $ebay_id);

  wp_update_post(array(
    'ID' => $post_ID,
    'post_type' => 'product',
  ));

  ob_start();

  $table = new Extended_WC_Admin_List_Table_Products();
  $table->render_row($post_ID);

  echo json_encode([
    'html' => ob_get_clean(),
    'post' => compact(['post_ID'])
  ]);
  wp_die();
}

function wbp_delete_images()
{
  if (isset($_POST['post_ID'])) {
    $post_ID = $_POST['post_ID'];
    wbp_remove_attachments($post_ID);
  }

  ob_start();

  $table = new Extended_WC_Admin_List_Table_Products();
  $table->render_row($post_ID);

  echo json_encode([
    'html' => ob_get_clean(),
    'post' => compact(['post_ID'])
  ]);
  wp_die();
}

function wbp_get_product_categories()
{
  foreach (get_terms(['taxonomy' => 'product_cat']) as $key => $term) {
    $cats[] = [
      'id' => $term->term_id,
      'slug' => $term->slug,
      'name' => $term->name
    ];
  };
  echo json_encode([
    'cats' => $cats,
  ]);
  wp_die();
}

function wbp_get_brands()
{
  foreach (get_terms(['taxonomy' => 'brands']) as $key => $term) {
    $image_ids = get_metadata('term', $term->term_id, 'image');
    if (!empty($image_ids)) {
      $image_url = wp_get_attachment_image_url($image_ids[0]);

      $brands[] = [
        'id' => $term->term_id,
        'slug' => $term->slug,
        'name' => $term->name,
        'image_url' => $image_url
      ];
    }
  };
  echo json_encode([
    'brands' => $brands
  ]);
  wp_die();
}

function wbp_upload_image($url, $post_ID)
{
  $attachmentId = null;
  if ($url !== "") {
    $file = array();
    $file['name'] = $url;
    $file['tmp_name'] = download_url($url);

    if (!is_wp_error($file['tmp_name'])) {
      $attachmentId = media_handle_sideload($file, $post_ID);

      if (is_wp_error($attachmentId)) {
        @unlink($file['tmp_name']);
      } else {
        $url = wp_get_attachment_url($attachmentId);
      }
    } else {
      // @unlink($file['tmp_name']);
    }
  }
  return $attachmentId;
}
