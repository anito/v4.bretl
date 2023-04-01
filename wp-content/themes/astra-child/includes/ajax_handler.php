<?php
function parseEbayId($val)
{
  preg_match('/(\/?)(\d{10,})/', $val, $matches);
  if (isset($matches[2])) {
    return $matches[2];
  }
  return null;
}

function wbp_get_ebay_ad()
{
  $formData = $_POST['formdata'];
  $post_id = $formData['post_ID'];
  $post_status = $formData['post_status'];
  $ebay_id_raw = $formData['ebay_id'];
  $ebay_id = parseEbayId($ebay_id_raw);

  $response = wp_remote_get(EBAY_URL . $ebay_id ? '/s-' . $ebay_id . '/k0' : '/');

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
    preg_match('/(\/?)(\d{10,})/', $ebay_id_raw, $matches);
    if (isset($matches[2])) $ebay_id = $matches[2];
  }
  $ebaydata = $_POST['ebaydata'];
  if (
    ($title = isset($ebaydata['title']) ? $ebaydata['title'] : null) &&
    ($price = isset($ebaydata['price']) ? $ebaydata['price'] : null) &&
    ($content = isset($ebaydata['description']) ? $ebaydata['description'] : null)
  ) {

    update_post_meta((int) $post_id, '_regular_price', $price);
    update_post_meta((int) $post_id, '_price', $price);
    update_post_meta((int) $post_id, 'ebay_id', $ebay_id);

    $id = wp_insert_post(array(
      'ID' => $post_id,
      'post_type' => 'product',
      'post_title' => $title,
      'post_content' => $content
    ));
  }


  echo json_encode([
    'success' => !!$id,
    'data' => [
      'post_id' => $post_id,
      'ebay_id' => $ebay_id,
      'price' => $price,
      'content' => $content,
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
    preg_match('/(\/?)(\d{10,})/', $ebay_id_raw, $matches);
    if (isset($matches[2])) $ebay_id = $matches[2];
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
