<?php
global $wp_list_table;
global $wp_task_list_table;

$wp_list_table = new Kleinanzeigen_List_Table();
$wp_task_list_table = new Kleinanzeigen_Task_List_Table();

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

function wbp_reg_increment($matches)
{
  return $matches[1] . ++$matches[2] . $matches[3];
}

function wbp_sanitize_title($title, $appendix = "")
{
  preg_match('/((?!DUPLIKAT \d+).)*(\[ DUPLIKAT \d+ \])/', $title . $appendix, $matches);
  if (isset($matches[0])) {
    return preg_replace_callback('/(.*\[ DUPLIKAT )(\d+)( \])/', "wbp_reg_increment", $matches[0]);
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

  if (is_callable('write_log')) {
    // write_log($response);
  }

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

  if ($record) {
    $record = html_entity_decode(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
    update_post_meta($post_ID, 'kleinanzeigen_record', $record);
  }

  die(json_encode(
    [
      'post_ID' => $post_ID,
      'kleinanzeigen_id' => $kleinanzeigen_id,
      'content' => $response,
      'record' => $record,
      'screen' => $screen
    ]
  ));
}

function wbp_ajax_fix_price()
{
  $post_ID = isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
  $price = isset($_REQUEST['price']) ? $_REQUEST['price'] : null;
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;

  if (isset($post_ID) && isset($price)) {
    $product = wc_get_product($post_ID);
    if ($product) {
      if ($product->is_on_sale()) {
        wbp_set_pseudo_sale_price($product, $price, 10);
      } else {
        $product->set_regular_price($price);
      }
      $product->save();
    }
  }

  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
    case 'modal':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      $head = render_head();
      break;
  }

  $modal_row = null;
  if ('modal' === $screen) {
    $record = get_record($kleinanzeigen_id);
    $modal_row = render_task_list_row($post_ID, array('record' => $record));
  }

  die(json_encode([
    'data' => compact(array('row', 'modal_row', 'post_ID', 'kleinanzeigen_id', 'head'))
  ]));
}

function wbp_ajax_toggle_publish_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $disconnect = isset($_REQUEST['disconnect']) ? $_REQUEST['disconnect'] === '__disconnect__' : null;
  $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
  $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

  // Maybe remove sku from product
  if (is_null($disconnect)) {
    $force_status = null;
    $post_status = get_post_status($post_ID) === 'draft' ? 'publish' : 'draft';
  } else {
    disable_sku($post_ID);
    $force_status = 'draft';
  }

  if ($post_ID) {
    wp_update_post(array(
      'ID' => $post_ID,
      'post_status' => $force_status ?? $post_status
    ));
  }

  ob_start();
  switch ($screen) {
    case 'edit-product':
      $row = render_wc_admin_list_row($post_ID);
      break;
    case 'toplevel_page_kleinanzeigen':
    case 'modal':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      break;
  }

  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
    case 'modal':
      $head = render_head();
      break;
  }

  $modal_row = null;
  if ('modal' === $screen) {
    $record = get_record($kleinanzeigen_id);
    $modal_row = render_task_list_row($post_ID, array('record' => $record));
  }

  die(json_encode([
    'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]));
}

function wbp_ajax_feature_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

  if($post_ID) {
    $product = wc_get_product($post_ID);
    $product->set_featured(!$product->is_featured());
    $product->save();
  }

  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
    case 'modal':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      $head = render_head();
      break;
  }

  switch ($screen) {
    case 'modal':
      $record = get_record($kleinanzeigen_id);
      $modal_row = render_task_list_row($post_ID, array('record' => $record));
  }

  die(json_encode([
    'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]));
}

// Receives data from base64_encoded json object
function wbp_ajax_save_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
  $args = isset($_REQUEST['args']) ? $_REQUEST['args'] : null;
  $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

  if(!is_null($args)) {
    $args = (array) json_decode(base64_decode($args));
  } else {
    $args = array();
  }

  if ($post_ID) {
    $postarr = array_merge(array(
      'ID' => $post_ID
    ), $args);

    $success = wp_update_post($postarr);
  }

  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
    case 'modal':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      $head = render_head();
      break;
  }

  switch ($screen) {
    case 'modal':
      $record = get_record($kleinanzeigen_id);
      $modal_row = render_task_list_row($post_ID, array('record' => $record));
  }

  die(json_encode([
    'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]));
}

function wbp_ajax_connect()
{
  global $wp_list_table;

  $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
  $ad = wbp_find_kleinanzeige($kleinanzeigen_id);
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

  if (isset($post_ID) && isset($ad)) {
    enable_sku($post_ID, $ad);
  } else {
    disable_sku($post_ID);
  }

  $data = prepare_list_table($paged);
  $row = render_list_row($kleinanzeigen_id, $data);
  $head = render_head();

  die(json_encode([
    'data' => compact(array('row', 'head', 'post_ID', 'kleinanzeigen_id'))
  ]));
}

function wbp_ajax_disconnect()
{
  $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

  disable_sku($post_ID);

  switch ($screen) {
    case 'edit-product':
      $row = render_wc_admin_list_row($post_ID);
      break;
    case 'modal':
    case 'toplevel_page_kleinanzeigen':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      $head = render_head();
      break;
  }

  $modal_row = null;
  if ('modal' === $screen) {
    $modal_row = render_task_list_row($post_ID);
  }

  die(json_encode(array(
    'data' => compact(array('row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'))
  )));
}

function wbp_ajax_import_kleinanzeigen_data()
{
  if (isset($_REQUEST['postdata'])) {
    $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
    $kleinanzeigen_id = isset($_REQUEST['postdata']['kleinanzeigen_id']) ? $_REQUEST['postdata']['kleinanzeigen_id'] : null;
  }

  $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
  $kleinanzeigendata = isset($_REQUEST['kleinanzeigendata']) ? (object) $_REQUEST['kleinanzeigendata'] : null;

  if ($kleinanzeigen_id) {
    $kleinanzeigen_id = parse_kleinanzeigen_id($kleinanzeigen_id);
  }

  if ($kleinanzeigendata) {
    $content = isset($kleinanzeigendata->content) ? $kleinanzeigendata->content : null;
    $record = isset($kleinanzeigendata->record) ? (object) $kleinanzeigendata->record : null;
    $searchable_content = '';

    if ($record) {
      // Ad is publicly available
      $title = $record->title;
      $price = wbp_extract_kleinanzeigen_price($record->price);
      $excerpt = $record->description;
      $tags = !empty($record->tags) ? $record->tags : [];
      $url = $record->url;
      $searchable_content = $title . ' ' . $excerpt;
    } else {
      // Ad is reserved or deleted, don't do anything, keep existing content
      die();
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
          'Aktionspreis',
          'match_type' => 'like',
          'fn' => 'sale',
        ),
        'allrad' => array('Allrad', 'match_type' => 'like'),
        'vorführ' => array('Vorführmaschine', 'match_type' => 'like'),
        'topzustand' => 'Top',
        'topausstattung' => 'Top',
        'aktion' => array('Aktion', 'fn' => array('aktion', 'default')),
        'aktionsmodell' => 'Aktion',
        'klima' => 'Klima',
        'am lager' => 'Sofort lieferbar',
        'sofort verfügbar' => 'Sofort lieferbar',
        'sofort lieferbar' => 'Sofort lieferbar',
        'lagermaschine' => 'Sofort lieferbar',
        'leicht gebraucht' => 'Leicht Gebraucht',
        'limited edition' => 'Limited Edition',
        'lim. edition' => 'Limited Edition',
        'mietmaschine' => 'Mieten',
        'neu' => 'Neu',
        'neumaschine' => 'Neu',
        'neufahrzeug' => 'Neu',
        'neues modell' => 'Neues Modell',
        'top modell' => 'Top Modell',
        'neuwertig' => array('Neuwertig', 'match_type' => 'like'),
      );

      // handle contents
      foreach ($parts as $key => $val) {

        if (wbp_text_contains($key, $searchable_content, isset($val['match_type']) ? $val['match_type'] : null)) {

          $fns = isset($val['fn']) ? $val['fn'] : 'default';
          $fns = !is_array($fns) ? array($fns) : $fns;

          foreach ($fns as $fn) {
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
      }

      // handle brands
      $brands = get_terms([
        'taxonomy' => 'product_brand',
        'hide_empty' => false
      ]);

      foreach ($brands as $brand) {
        $exists = false;
        if (wbp_text_contains('(?:Motorenhersteller:?\s*(' . $brand->name . '))', $content, 'raw')) {
          $exists = true;
        }
        if (wbp_text_contains(esc_html($brand->name), esc_html($searchable_content))) {
          $exists = true;
        }
        if (true === $exists) {
          wbp_set_product_term($product, $brand->term_id, 'brand', true);
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

  switch ($screen) {
    case 'edit-product':
      $row = render_wc_admin_list_row($post_ID);
      break;
    case 'toplevel_page_kleinanzeigen':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      break;
  }

  switch ($screen) {
    case 'toplevel_page_kleinanzeigen':
      $head = render_head();
      break;
  }

  die(json_encode([
    'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id'])
  ]));
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
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

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

  switch ($screen) {
    case 'edit-product':
      $row = render_wc_admin_list_row($post_ID);
      break;
    case 'toplevel_page_kleinanzeigen':
      $data = prepare_list_table($paged);
      $row = render_list_row($kleinanzeigen_id, $data);
      break;
  }

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
  $row = render_wc_admin_list_row($post_ID);

  echo json_encode([
    'data' => compact(['row', 'post_ID'])
  ]);
  wp_die();
}

function wbp_ajax_delete_post()
{
  $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
  $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
  
  $product = wc_get_product($post_ID);
  if ($product) {
    $product->delete(true);
  }

  $data = prepare_list_table($paged);
  $row = render_list_row($kleinanzeigen_id, $data);
  $head = render_head();

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
  foreach (get_terms(['taxonomy' => 'product_brand']) as $key => $term) {
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

function render_wc_admin_list_row($post_ID) {
  $wp_wc_admin_list_table = new Extended_WC_Admin_List_Table_Products();

  ob_start();
  $wp_wc_admin_list_table->render_row($post_ID);
  return ob_get_clean();
}

function prepare_list_table($paged) {
  global $wp_list_table;

  $data = wbp_get_json_data(array('paged' => $paged));
  $wp_list_table->setData($data);
  return $data;
}

function render_list_row($kleinanzeigen_id, $data)
{
  global $wp_list_table;

  ob_start();
  $ads = $data->ads;
  $ids = array_column($ads, 'id');
  $record_key = array_search($kleinanzeigen_id, $ids);
  if (isset($record_key)) {
    $record = $ads[$record_key];
    $wp_list_table->render_row($record);
  }
  return ob_get_clean();
}

function render_task_list_row($post_ID, $args = array())
{
  global $wp_task_list_table;

  $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;

  $product = wc_get_product($post_ID);
  $data = array_merge(array('product' => $product, 'task_type' => $task_type), $args);
  $wp_task_list_table->setData($data);

  ob_start();
  $wp_task_list_table->render_row($data);
  return ob_get_clean();
}

function render_head()
{
  global $wp_list_table;

  ob_start();
  $wp_list_table->render_head();
  return ob_get_clean();
}

function wbp_render_tasks()
{
  global $wp_list_table;
  
  ob_start();
  $wp_list_table->render_tasks();
  return ob_get_clean();
}

function get_record($id) {
  $ads = wbp_get_all_ads();
  $ids = array_column($ads, 'id');
  $record_key = array_search($id, $ids);
  return $ads[$record_key];
}
