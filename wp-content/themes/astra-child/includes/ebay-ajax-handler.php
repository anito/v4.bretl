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
  if (isset($_REQUEST['formdata'])) {
    $screen = $_REQUEST['screen'];
    $formdata = $_REQUEST['formdata'];
    $post_ID = isset($formdata['post_ID']) ? $formdata['post_ID'] : false;
    $ebay_id_raw = isset($formdata['ebay_id']) ? $formdata['ebay_id'] : false;
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
      'content' => $response,
      'screen' => $screen
    ]
  );
  wp_die();
}

function wbp_ajax_publish_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : null;
  $ebay_id = isset($_REQUEST['ebay_id']) ? (int) $_REQUEST['ebay_id'] : null;
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;

  $pageNum = $_COOKIE['ebay-table-page'];

  if ($post_ID) {
    wp_update_post(array(
      'ID' => $post_ID,
      'post_status' => 'publish',
    ));
  }

  ob_start();
  switch ($screen) {
    case 'edit-product':

      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);

      break;
    case 'toplevel_page_ebay':

      $wp_list_table = new Ebay_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($ebay_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);

      break;
  }
  $row = ob_get_clean();

  ob_start();
  switch ($screen) {
    case 'toplevel_page_ebay':
      $wp_list_table->render_head($pageNum);
      break;
  }
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'ebay_id'])
  ]);

  wp_die();
}

function wbp_ajax_connect()
{
  $post_ID = isset($_POST['post_ID']) ? $_POST['post_ID'] : null;
  $ebay_id = isset($_POST['ebay_id']) ? $_POST['ebay_id'] : null;

  if (!isset($post_ID) || !isset($ebay_id)) {
    $success = false;
  } else {
    $success = true;
  }

  $product = wc_get_product($post_ID);
  if ($product) {
    try {
      $product->set_sku($ebay_id);
    } catch (Exception $e) {
    }
    $product->save();
  }

  update_post_meta((int) $post_ID, 'ebay_id', $ebay_id);
  update_post_meta((int) $post_ID, 'ebay_url', EBAY_URL . '/s-' . $ebay_id . '/k0');

  $pageNum = $_COOKIE['ebay-table-page'];

  ob_start();
  $wp_list_table = new Ebay_List_Table();
  $data = wbp_get_json_data($pageNum);
  $wp_list_table->setData($data);
  list($columns, $hidden) = $wp_list_table->get_column_info();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($ebay_id, $ids);
  $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
  $row = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'post_ID', 'ebay_id', 'success'])
  ]);

  wp_die();
}

function wbp_ajax_disconnect()
{
  $post_ID = isset($_POST['post_ID']) ? $_POST['post_ID'] : null;
  $ebay_id = isset($_POST['ebay_id']) ? $_POST['ebay_id'] : null;

  if (!isset($post_ID) || !isset($ebay_id)) {
    $success = false;
  } else {
    $success = true;
  }

  $product = wc_get_product($post_ID);
  if ($product) {
    try {
      $product->set_sku('');
    } catch (Exception $e) {
    }
    $product->save();
  }

  delete_post_meta((int) $post_ID, 'ebay_id');
  delete_post_meta((int) $post_ID, 'ebay_url');

  $pageNum = $_COOKIE['ebay-table-page'];

  ob_start();
  $wp_list_table = new Ebay_List_Table();
  $data = wbp_get_json_data($pageNum);
  $wp_list_table->setData($data);
  list($columns, $hidden) = $wp_list_table->get_column_info();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($ebay_id, $ids);
  $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
  $row = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'post_ID', 'ebay_id', 'success'])
  ]);

  wp_die();
}

function wbp_ajax_import_ebay_data()
{
  if (isset($_REQUEST['postdata'])) {

    $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
    $ebay_id = isset($_REQUEST['postdata']['ebay_id']) ? $_REQUEST['postdata']['ebay_id'] : null;
  }
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $ebaydata = isset($_REQUEST['ebaydata']) ? $_REQUEST['ebaydata'] : null;

  if ($ebay_id) {
    $ebay_id = parse_ebay_id($ebay_id);
  }

  $pageNum = $_COOKIE['ebay-table-page'];

  if (
    ($ebaydata) &&
    ($title = isset($ebaydata['title']) ? $ebaydata['title'] : null) &&
    ($price = isset($ebaydata['price']) ? $ebaydata['price'] : null) &&
    ($content = isset($ebaydata['description']) ? $ebaydata['description'] : null)
  ) {

    if (!$post_ID) {
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
  switch ($screen) {
    case 'edit-product':
      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);
      break;
    case 'toplevel_page_ebay':
      $wp_list_table = new Ebay_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($ebay_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);

      break;
  }
  $row = ob_get_clean();

  ob_start();
  switch ($screen) {
    case 'toplevel_page_ebay':
      $wp_list_table->render_head($pageNum);
      break;
  }
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'ebay_id'])
  ]);

  wp_die();
}

function wbp_ajax_import_ebay_images()
{
  if (isset($_REQUEST['postdata'])) {

    $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
    $ebay_id = isset($_REQUEST['postdata']['ebay_id']) ? $_REQUEST['postdata']['ebay_id'] : null;
  }

  if ($ebay_id) {
    $ebay_id = parse_ebay_id($ebay_id);
  }
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $ebaydata = isset($_REQUEST['ebaydata']) ? $_REQUEST['ebaydata'] : null;
  $ebay_images = isset($ebaydata['images']) ? $ebaydata['images'] : [];
  $pageNum = $_COOKIE['ebay-table-page'];

  wbp_remove_attachments($post_ID);

  $ids = [];
  for ($i = 0; $i < count($ebay_images); $i++) {
    $url = $ebay_images[$i];
    $ids[] = wbp_upload_image($url, $post_ID);
    if ($i === 0) {
      set_post_thumbnail((int) $post_ID, $ids[0]);
    }
  }

  unset($ids[0]); // remove main image from gallery
  update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
  update_post_meta((int) $post_ID, 'ebay_id', $ebay_id);

  wp_update_post(array(
    'ID' => $post_ID,
    'post_type' => 'product',
  ));

  ob_start();
  switch ($screen) {
    case 'edit-product':
      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);
      break;
    case 'toplevel_page_ebay':
      $wp_list_table = new Ebay_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($ebay_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);

      break;
  }
  $row = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'post_ID', 'ebay_id'])
  ]);
  wp_die();
}

function wbp_ajax_delete_images()
{
  if (isset($_REQUEST['post_ID'])) {
    $post_ID = $_REQUEST['post_ID'];
    wbp_remove_attachments($post_ID);
  }

  ob_start();
  $wp_list_table = new Extended_WC_Admin_List_Table_Products();
  $wp_list_table->render_row($post_ID);
  $row = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'post_ID'])
  ]);
  wp_die();
}

function wbp_ajax_delete_post()
{
  if (!empty($_REQUEST['post_ID'])) {
    $post_ID = $_REQUEST['post_ID'];
  }
  if (!empty($_REQUEST['ebay_id'])) {
    $ebay_id = $_REQUEST['ebay_id'];
  }
  $pageNum = $_COOKIE['ebay-table-page'];

  $product = wc_get_product($post_ID);

  if ($product) {
    $product->delete(true);
  }

  ob_start();
  $wp_list_table = new Ebay_List_Table();
  $data = wbp_get_json_data($pageNum);
  $wp_list_table->setData($data);
  list($columns, $hidden) = $wp_list_table->get_column_info();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($ebay_id, $ids);
  $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
  $row = ob_get_clean();

  ob_start();
  $wp_list_table->render_head($pageNum);
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'ebay_id'])
  ]);
  wp_die();
}

function wbp_ajax_get_product_categories()
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

function wbp_ajax_get_brands()
{
  $brands = array();
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
