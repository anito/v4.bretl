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
  $post_id = $formdata['post_ID'];
  $post_status = $formdata['post_status'];
  $ebay_id_raw = $formdata['ebay_id'];
  $ebay_id = parse_ebay_id($ebay_id_raw);

  $url = EBAY_URL . ($ebay_id ? '/s-' . $ebay_id . '/k0' : '/');
  $response = wp_remote_get($url);

  echo json_encode(
    [
      'post_id' => $post_id,
      'ebay_id' => $ebay_id,
      'post_status' => $post_status,
      'content' => $response
    ]
  );
  wp_die();
}

function wbp_import_ebay_data()
{
  $postdata = $_POST['postdata'];
  if (isset($postdata['post_id'])) {
    $post_id = $postdata['post_id'];
  }
  if (isset($postdata['ebay_id'])) {
    $ebay_id_raw = $postdata['ebay_id'];
    $ebay_id = parse_ebay_id($ebay_id_raw);
  }
  $ebaydata = $_POST['ebaydata'];
  if (
    ($title = isset($ebaydata['title']) ? $ebaydata['title'] : null) &&
    ($price = isset($ebaydata['price']) ? $ebaydata['price'] : null) &&
    ($content = isset($ebaydata['description']) ? $ebaydata['description'] : null)
  ) {

    $product = wc_get_product($post_id);
    $product->set_regular_price($price);
    try {
      $product->set_sku($ebay_id);
    } catch (Exception $e) {
    }
    $product->save();

    update_post_meta((int) $post_id, 'ebay_id', $ebay_id);
    update_post_meta((int) $post_id, 'ebay_url', EBAY_URL . '/s-' . $ebay_id . '/k0');

    $result = wp_insert_post(array(
      'ID' => $post_id,
      'post_title' => $title,
      'post_type' => 'product',
      'post_status' => 'draft',
      'post_content' => $content
    ), true);
    $is_error = !is_int($result);
  } else {
    $is_error = true;
  }

  echo json_encode(['success' => !$is_error,
    'data' => [
      'post_id' => $post_id,
      'ebay_id' => $ebay_id,
      'price' => $price,
      'content' => $content,
      'error' => $is_error ? $result : false
    ],
  ]);
  wp_die();
}

function wbp_import_ebay_images()
{
  $postdata = $_POST['postdata'];
  if (isset($postdata['post_id'])) {
    $post_id = $postdata['post_id'];
  }
  if (isset($postdata['ebay_id'])) {
    $ebay_id_raw = $postdata['ebay_id'];
    $ebay_id = parse_ebay_id($ebay_id_raw);
  }
  $ebaydata = $_POST['ebaydata'];
  $ebay_images = isset($ebaydata['images']) ? $ebaydata['images'] : [];

  remove_attachments($post_id);

  $ids = [];
  for ($i = 0; $i < count($ebay_images); $i++) {
    $url = $ebay_images[$i];
    $ids[] = wbp_upload_image($url, $post_id);
    if ($i === 0) {
      set_post_thumbnail((int) $post_id, $ids[0]);
    }
  }

  unset($ids[0]);
  update_post_meta((int) $post_id, '_product_image_gallery', implode(',', $ids));
  update_post_meta((int) $post_id, 'ebay_id', $ebay_id);

  $id = wp_update_post(array(
    'ID' => $post_id,
    'post_type' => 'product',
  ));

  echo json_encode([
    'success' => !!$id,
    'data' => [
      'post_id' => $post_id,
      'ebay_id' => $ebay_id,
      'images' => $ids,
    ],
  ]);
  wp_die();
}

function wbp_delete_images()
{
  if (isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    remove_attachments($post_id);
  }

  echo json_encode([
    'success' => true,
    'data' => ['post_id' => $post_id]
  ]);
  wp_die();
}

function wbp_upload_image($url, $post_id)
{
  $attachmentId = null;
  if ($url !== "") {
    $file = array();
    $file['name'] = $url;
    $file['tmp_name'] = download_url($url);

    if (is_wp_error($file['tmp_name'])) {
      @unlink($file['tmp_name']);
    } else {
      $attachmentId = media_handle_sideload($file, $post_id);

      if (is_wp_error($attachmentId)) {
        @unlink($file['tmp_name']);
      } else {
        $url = wp_get_attachment_url($attachmentId);
      }
    }
  }
  return $attachmentId;
}

function remove_attachments($post_id)
{
  $product = wc_get_product($post_id);
  $attachment_ids = array();
  $attachment_ids[] = $product->get_image_id();
  $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
  for ($i = 0; $i < count($attachment_ids); $i++) {
    wp_delete_post($attachment_ids[$i]);
  }
}
