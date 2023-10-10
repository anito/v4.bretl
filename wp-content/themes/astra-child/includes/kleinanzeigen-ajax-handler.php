<?php
function parse_kleinanzeigen_id($val)
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

function remote_call($url, $tries = 3, $retry = 1)
{
  $response = wp_remote_get(esc_url_raw($url), array(
    'timeout' => 10
  ));

  if (!is_wp_error($response) && ($response['response']['code'] === 200)) {
    return $response;
  } elseif ($retry++ < $tries) {
    sleep($retry * 2);
    return remote_call($url, $tries, $retry);
  }
  return $response;
}

function wbp_get_remote()
{
  if (isset($_REQUEST['formdata'])) {
    $screen = $_REQUEST['screen'];
    $formdata = $_REQUEST['formdata'];
    $post_ID = isset($formdata['post_ID']) ? $formdata['post_ID'] : false;
    $kleinanzeigen_id_raw = isset($formdata['kleinanzeigen_id']) ? $formdata['kleinanzeigen_id'] : false;
    $kleinanzeigen_id = parse_kleinanzeigen_id($kleinanzeigen_id_raw);

    $remoteUrl = wbp_get_kleinanzeigen_search_url($kleinanzeigen_id);
  } else {
    $remoteUrl = home_url();
  }

  $response = remote_call($remoteUrl, 5);
  $record = wbp_find_kleinanzeige($kleinanzeigen_id) ?? '';
  $record = html_entity_decode(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
  update_post_meta($post_ID, 'kleinanzeigen_record', $record);


  echo json_encode(
    [
      'post_ID' => $post_ID,
      'kleinanzeigen_id' => $kleinanzeigen_id,
      'content' => $response,
      'record' => $record,
      'screen' => $screen
    ]
  );
  wp_die();
}

function wbp_ajax_toggle_publish_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : null;
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;

  $pageNum = $_COOKIE['kleinanzeigen-table-page'];

  $curr_status = get_post_status($post_ID);
  if ($post_ID) {
    wp_update_post(array(
      'ID' => $post_ID,
      'post_status' => $curr_status === 'draft' ? 'publish' : 'draft',
    ));
  }

  ob_start();
  switch ($screen) {
    case 'edit-product':

      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);

      break;
    case 'toplevel_page_kleinanzeigen':

      $wp_list_table = new Kleinanzeigen_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($kleinanzeigen_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);

      break;
  }
  $row = ob_get_clean();

  ob_start();
  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
      $wp_list_table->render_head($pageNum);
      break;
  }
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]);

  wp_die();
}

function wbp_ajax_connect()
{
  $post_ID = isset($_POST['post_ID']) ? $_POST['post_ID'] : null;
  $kleinanzeigen_id = isset($_POST['kleinanzeigen_id']) ? $_POST['kleinanzeigen_id'] : null;
  $ad = wbp_find_kleinanzeige($kleinanzeigen_id);

  if (!isset($post_ID) || !isset($ad)) {
    $success = false;
  } else {
    $success = true;
  }

  $product = wc_get_product($post_ID);
  if ($product) {
    try {
      $product->set_sku($kleinanzeigen_id);
    } catch (Exception $e) {
    }
    $product->save();
  }

  if ($success) {
    enable_sku($post_ID, $ad);
  } else {
    disable_sku($post_ID);
  }

  $pageNum = $_COOKIE['kleinanzeigen-table-page'];

  ob_start();
  $wp_list_table = new Kleinanzeigen_List_Table();
  $data = wbp_get_json_data($pageNum);
  $wp_list_table->setData($data);
  list($columns, $hidden) = $wp_list_table->get_column_info();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($kleinanzeigen_id, $ids);
  $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
  $row = ob_get_clean();

  ob_start();
  $wp_list_table->render_head($pageNum);
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id', 'success'])
  ]);

  wp_die();
}

function wbp_ajax_disconnect()
{
  $post_ID = isset($_POST['post_ID']) ? $_POST['post_ID'] : null;
  $kleinanzeigen_id = isset($_POST['kleinanzeigen_id']) ? $_POST['kleinanzeigen_id'] : null;

  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;

  if (!isset($post_ID) || !isset($kleinanzeigen_id)) {
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

  disable_sku($post_ID);

  $pageNum = $_COOKIE['kleinanzeigen-table-page'];

  ob_start();
  switch ($screen) {
    case 'edit-product':
      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);
      break;
    case 'toplevel_page_kleinanzeigen':
      $wp_list_table = new Kleinanzeigen_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($kleinanzeigen_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
      break;
  }
  $row = ob_get_clean();

  if ('toplevel_page_kleinanzeigen' === $screen) {
    ob_start();
    $wp_list_table->render_head($pageNum);
  }
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id', 'success'])
  ]);

  wp_die();
}

function wbp_ajax_import_kleinanzeigen_data()
{
  if (isset($_REQUEST['postdata'])) {
    $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
    $kleinanzeigen_id = isset($_REQUEST['postdata']['kleinanzeigen_id']) ? $_REQUEST['postdata']['kleinanzeigen_id'] : null;
  }

  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $kleinanzeigendata = isset($_REQUEST['kleinanzeigendata']) ? $_REQUEST['kleinanzeigendata'] : null;

  if ($kleinanzeigen_id) {
    $kleinanzeigen_id = parse_kleinanzeigen_id($kleinanzeigen_id);
  }

  $pageNum = $_COOKIE['kleinanzeigen-table-page'];
  
  if (!empty($kleinanzeigendata)) {
    ($content = isset($kleinanzeigendata['content']) ? $kleinanzeigendata['content'] : null);
    ($record = isset($kleinanzeigendata['record']) ? $kleinanzeigendata['record'] : null);
    $record = (object) $record;
    $contents = '';

    if ($record) {
      $title = $record->title;
      $price = preg_replace('/[\s.,a-zA-Z€]*/', '', $record->price);
      $excerpt = $record->description;
      $tags = !empty($record->tags) ? $record->tags : [];
      $url = $record->url;
      $contents = $title . ' ' . $excerpt;
    }

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
      $parts = array(
        'aktionspreis' => array(
          'term_name' => 'Aktionspreis',
          'fn' => 'sale',
        ),
        'allrad' => array('Allrad', 'match_type' => 'like'),
        'vorführ' => array('Vorführmaschine', 'match_type' => 'like'),
        'topzustand' => 'Top',
        'topausstattung' => 'Top',
        'aktion' => 'Aktion',
        'aktionsmodell' => 'Aktion',
        'am lager' => 'Am Lager',
        'klima' => 'Klima',
        'lagermaschine' => 'Am Lager',
        'leicht gebraucht' => 'Leicht Gebraucht',
        'limited edition' => 'Limited Edition',
        'lim. edition' => 'Limited Edition',
        'mietmaschine' => 'Mieten',
        'neu' => 'Neu',
        'neues modell' => 'Neues Modell',
        'top modell' => 'Top Modell',
        'neumaschine' => 'Neu',
        'neuwertig' => array('Neuwertig', 'match_type' => 'like'),
        'sofort verfügbar' => 'Am Lager',
      );

      // handle labels
      foreach ($parts as $key => $val) {

        if (wbp_text_contains($key, $contents, isset($val['match_type']) ? $val['match_type'] : null)) {

          $fn = isset($val['fn']) ? $val['fn'] : 'default';
          if (is_callable('wbp_handle_product_contents_' . $fn, false, $callable_name)) {

            if (!is_array($val)) {
              $term_name = $val;
            } elseif (isset($val[0])) {
              $term_name = $val[0];
            }
            $product = call_user_func('wbp_handle_product_contents_' . $fn, compact('product', 'price', 'title', 'content', 'term_name'));
          }
        }
      }

      // handle brands
      $brands = get_terms([
        'taxonomy' => 'product_brands',
        'hide_empty' => false
      ]);

      foreach ($brands as $brand) {
        if (wbp_text_contains($brand->name, $contents)) {
          $ids = wbp_set_product_term($product, $brand->term_id, 'brands', true);
        }
      }


      // handle product attributes
      foreach ($tags as $key => $tag) {
        wbp_set_pa_term($product, $tag, true);
      }

      try {
        $product->set_sku($kleinanzeigen_id);
      } catch (Exception $e) {
      }
      $product->save();
    }

    if ($record) {
      enable_sku($post_ID, $record);
    }

    wp_insert_post(array(
      'ID' => $post_ID,
      'post_title' => $title,
      'post_type' => 'product',
      'post_status' => 'draft',
      'post_content' => $content,
      'post_excerpt' => $excerpt // wbp_sanitize_excerpt($content, 300)
    ), true);
  }

  ob_start();
  switch ($screen) {
    case 'edit-product':
      $wp_list_table = new Extended_WC_Admin_List_Table_Products();
      $wp_list_table->render_row($post_ID);
      break;
    case 'toplevel_page_kleinanzeigen':
      $wp_list_table = new Kleinanzeigen_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($kleinanzeigen_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
      break;
  }
  $row = ob_get_clean();

  ob_start();
  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
      $wp_list_table->render_head($pageNum);
      break;
  }
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]);

  wp_die();
}

function wbp_ajax_import_kleinanzeigen_images()
{
  if (isset($_REQUEST['postdata'])) {

    $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
    $kleinanzeigen_id = isset($_REQUEST['postdata']['kleinanzeigen_id']) ? $_REQUEST['postdata']['kleinanzeigen_id'] : null;
  }

  if ($kleinanzeigen_id) {
    $kleinanzeigen_id = parse_kleinanzeigen_id($kleinanzeigen_id);
  }
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $kleinanzeigendata = isset($_REQUEST['kleinanzeigendata']) ? $_REQUEST['kleinanzeigendata'] : null;
  $kleinanzeigen_images = isset($kleinanzeigendata['images']) ? $kleinanzeigendata['images'] : [];
  $pageNum = $_COOKIE['kleinanzeigen-table-page'];

  wbp_remove_attachments($post_ID);

  $ids = [];
  for ($i = 0; $i < count($kleinanzeigen_images); $i++) {
    $url = $kleinanzeigen_images[$i];
    $ids[] = wbp_upload_image($url, $post_ID);
    if ($i === 0) {
      set_post_thumbnail((int) $post_ID, $ids[0]);
    }
  }

  unset($ids[0]); // remove main image from gallery
  update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
  update_post_meta((int) $post_ID, 'kleinanzeigen_id', $kleinanzeigen_id);

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
    case 'toplevel_page_kleinanzeigen':
      $wp_list_table = new Kleinanzeigen_List_Table();
      $data = wbp_get_json_data($pageNum);
      $wp_list_table->setData($data);
      list($columns, $hidden) = $wp_list_table->get_column_info();
      $ads = $data->ads;
      $ids = array_column($ads, 'id');
      $record_key = array_search($kleinanzeigen_id, $ids);
      $wp_list_table->render_row($ads[$record_key], $columns, $hidden);

      break;
  }
  $row = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'post_ID', 'kleinanzeigen_id'])
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
  if (!empty($_REQUEST['kleinanzeigen_id'])) {
    $kleinanzeigen_id = $_REQUEST['kleinanzeigen_id'];
  }
  $pageNum = $_COOKIE['kleinanzeigen-table-page'];

  $product = wc_get_product($post_ID);

  if ($product) {
    $product->delete(true);
  }

  ob_start();
  $wp_list_table = new Kleinanzeigen_List_Table();
  $data = wbp_get_json_data($pageNum);
  $wp_list_table->setData($data);
  list($columns, $hidden) = $wp_list_table->get_column_info();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($kleinanzeigen_id, $ids);
  $wp_list_table->render_row($ads[$record_key], $columns, $hidden);
  $row = ob_get_clean();

  ob_start();
  $wp_list_table->render_head($pageNum);
  $head = ob_get_clean();

  echo json_encode([
    'data' => compact(['row', 'head', 'kleinanzeigen_id'])
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

function wbp_ajax_get_brand_images()
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
