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

function wbp_get_ebay_ad()
{
  $formdata = $_POST['formdata'];
  $post_ID = $formdata['post_ID'];
  $ebay_id_raw = $formdata['ebay_id'];
  $ebay_id = parse_ebay_id($ebay_id_raw);

  $url = EBAY_URL . ($ebay_id ? '/s-' . $ebay_id . '/k0' : '/');
  $response = wp_remote_get($url);

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

    $product = wc_get_product($post_ID);
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
      'post_excerpt' => substr($content, 0, 200)
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

  remove_attachments($post_ID);

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
    remove_attachments($post_ID);
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

function remove_attachments($post_ID)
{
  $product = wc_get_product($post_ID);
  $attachment_ids = array();
  $attachment_ids[] = $product->get_image_id();
  $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
  for ($i = 0; $i < count($attachment_ids); $i++) {
    wp_delete_post($attachment_ids[$i]);
  }
}
