<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Ajax_Action_Handler` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Ajax_Action_Handler')) {

  class Kleinanzeigen_Ajax_Action_Handler extends Kleinanzeigen
  {

    public function __construct()
    {
      $this->register_ajax_handler();
    }

    public function register_ajax_handler()
    {
      add_action('wp_ajax__ajax_connect', array($this, '_ajax_connect'));
      add_action('wp_ajax__ajax_fix_price', array($this, '_ajax_fix_price'));
      add_action('wp_ajax__ajax_disconnect', array($this, '_ajax_disconnect'));
      add_action('wp_ajax__ajax_get_remote', array($this, '_ajax_get_remote'));
      add_action('wp_ajax__ajax_get_brands', array($this, '_ajax_get_brands'));
      add_action('wp_ajax__ajax_delete_post', array($this, '_ajax_delete_post'));
      add_action('wp_ajax__ajax_save_post', array($this, '_ajax_save_post'));
      add_action('wp_ajax__ajax_feature_post', array($this, '_ajax_feature_post'));
      add_action('wp_ajax__ajax_toggle_publish_post', array($this, '_ajax_toggle_publish_post'));
      add_action('wp_ajax__ajax_delete_images', array($this, '_ajax_delete_images'));
      add_action('wp_ajax__ajax_import_kleinanzeigen_data', array($this, '_ajax_import_kleinanzeigen_data'));
      add_action('wp_ajax__ajax_import_kleinanzeigen_images', array($this, '_ajax_import_kleinanzeigen_images'));
      add_action('wp_ajax__ajax_get_product_categories', array($this, '_ajax_get_product_categories'));
      add_action('wp_ajax__ajax_heartbeat', array($this, '_ajax_heartbeat'));

      add_action('wp_ajax_nopriv__ajax_connect', array($this, '_ajax_connect'));
      add_action('wp_ajax_nopriv__ajax_fix_price', array($this, '_ajax_fix_price'));
      add_action('wp_ajax_nopriv__ajax_disconnect', array($this, '_ajax_disconnect'));
      add_action('wp_ajax_nopriv__ajax_get_remote', array($this, '_ajax_get_remote'));
      add_action('wp_ajax_nopriv__ajax_get_brands', array($this, '_ajax_get_brands'));
      add_action('wp_ajax_nopriv__ajax_delete_post', array($this, '_ajax_delete_post'));
      add_action('wp_ajax_nopriv__ajax_save_post', array($this, '_ajax_save_post'));
      add_action('wp_ajax_nopriv__ajax_feature_post', array($this, '_ajax_feature_post'));
      add_action('wp_ajax_nopriv__ajax_toggle_publish_post', array($this, '_ajax_toggle_publish_post'));
      add_action('wp_ajax_nopriv__ajax_delete_images', array($this, '_ajax_delete_images'));
      add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_data', array($this, '_ajax_import_kleinanzeigen_data'));
      add_action('wp_ajax_nopriv__ajax_import_kleinanzeigen_images', array($this, '_ajax_import_kleinanzeigen_images'));
      add_action('wp_ajax_nopriv__ajax_get_product_categories', array($this, '_ajax_get_product_categories'));
      add_action('wp_ajax_nopriv__ajax_heartbeat', array($this, '_ajax_heartbeat'));
    }

    public function _ajax_get_remote()
    {
      $this->get_remote();
    }

    public function _ajax_connect()
    {
      $this->ajax_connect();
    }

    public function _ajax_disconnect()
    {
      $this->ajax_disconnect();
    }

    public function _ajax_fix_price()
    {
      $this->ajax_fix_price();
    }

    public function _ajax_toggle_publish_post()
    {
      $this->ajax_toggle_publish_post();
    }

    public function _ajax_feature_post()
    {
      $this->ajax_feature_post();
    }

    public function _ajax_save_post()
    {
      $this->ajax_save_post();
    }

    public function _ajax_import_kleinanzeigen_data()
    {
      $this->ajax_import_kleinanzeigen_data();
    }

    public function _ajax_import_kleinanzeigen_images()
    {
      $this->ajax_import_kleinanzeigen_images();
    }

    public function _ajax_delete_post()
    {
      $this->ajax_delete_post();
    }

    public function _ajax_delete_images()
    {
      $this->ajax_delete_images();
    }

    public function _ajax_get_product_categories()
    {
      $this->ajax_get_product_categories();
    }

    public function _ajax_get_brands()
    {
      $this->ajax_get_brand_images();
    }

    public function _ajax_heartbeat()
    {
      $this->ajax_heartbeat();
    }

    public function parse_kleinanzeigen_id($val)
    {
      preg_match('/(\/?)(\d{8,})/', $val, $matches);
      if (isset($matches[2])) {
        return $matches[2];
      }
      return false;
    }

    public function remote_call($url, $tries = 3, $retry = 1)
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
        return $this->remote_call($url, $tries, $retry);
      }
      return $response;
    }

    public function ajax_heartbeat() {
      $data = $_POST['heartbeat'];
      die(json_encode(compact('data')));
    }

    public function get_remote()
    {
      if (isset($_REQUEST['formdata'])) {
        $screen = $_REQUEST['screen'];
        $formdata = $_REQUEST['formdata'];
        $post_ID = isset($formdata['post_ID']) ? $formdata['post_ID'] : false;
        $kleinanzeigen_id_raw = isset($formdata['kleinanzeigen_id']) ? $formdata['kleinanzeigen_id'] : false;
        $kleinanzeigen_id = $this->parse_kleinanzeigen_id($kleinanzeigen_id_raw);

        $remoteUrl = wbp_fn()->get_kleinanzeigen_search_url($kleinanzeigen_id);
      } else {
        $remoteUrl = home_url();
      }

      $response = $this->remote_call($remoteUrl, 5);
      $record = wbp_fn()->find_kleinanzeige($kleinanzeigen_id) ?? '';

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

    public function ajax_fix_price()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
      $price = isset($_REQUEST['price']) ? $_REQUEST['price'] : null;
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;

      if (isset($post_ID) && isset($price)) {
        wbp_fn()->fix_price($post_ID, $price);
      }

      switch ($screen) {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      if ('modal' === $screen) {
        $record = $this->get_record($kleinanzeigen_id);
        $modal_row = $this->render_task_list_row($post_ID, array('record' => $record));
      }

      die(json_encode([
        'data' => compact(array('row', 'modal_row', 'post_ID', 'kleinanzeigen_id', 'head'))
      ]));
    }

    public function ajax_toggle_publish_post()
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
        $post_status = get_post_status($post_ID) === 'draft' ? 'publish' : 'draft';
      } else {
        $product = wc_get_product($post_ID);
        if($product) {
          wbp_fn()->disable_sku($product);
          $product->save();
        }
        $post_status = 'draft';
      }

      if ($post_ID) {
        wp_update_post(array(
          'ID' => $post_ID,
          'post_status' => $post_status
        ));
      }

      ob_start();
      switch ($screen) {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      switch ($screen) {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      if ('modal' === $screen) {
        $record = $this->get_record($kleinanzeigen_id);
        $modal_row = $this->render_task_list_row($post_ID, array('record' => $record));
      }

      die(json_encode([
        'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
      ]));
    }

    public function ajax_feature_post()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      if ($post_ID) {
        $product = wc_get_product($post_ID);
        $product->set_featured(!$product->is_featured());
        $product->save();
      }

      switch ($screen) {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      switch ($screen) {
        case 'modal':
          $record = $this->get_record($kleinanzeigen_id);
          $modal_row = $this->render_task_list_row($post_ID, array('record' => $record));
      }

      die(json_encode([
        'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
      ]));
    }

    // Receives data from base64_encoded json object
    public function ajax_save_post()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? (int) $_REQUEST['kleinanzeigen_id'] : '';
      $args = isset($_REQUEST['args']) ? $_REQUEST['args'] : null;
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      if (!is_null($args)) {
        $args = (array) json_decode(base64_decode($args));
      } else {
        $args = array();
      }

      if ($post_ID) {
        $postarr = array_merge(array(
          'ID' => $post_ID
        ), $args);

        $success = wp_update_post($postarr);
        $product = wc_get_product($post_ID);
        if("trash" === $product->get_status()) {
          wbp_fn()->delete_product($post_ID, true);
        }
      }

      switch ($screen) {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      switch ($screen) {
        case 'modal':
          $record = $this->get_record($kleinanzeigen_id);
          $modal_row = $this->render_task_list_row($post_ID, array('record' => $record));
      }

      die(json_encode([
        'data' => compact(['row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'])
      ]));
    }

    public function ajax_connect()
    {
      global $wp_list_table;

      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
      $ad = wbp_fn()->find_kleinanzeige($kleinanzeigen_id);
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      $product = wc_get_product($post_ID);
      if ($product) {
        if (isset($ad)) {
          wbp_fn()->enable_sku($product, $ad);
        } else {
          wbp_fn()->disable_sku($product);
        }
        $product->save();
      }

      $data = $this->prepare_list_table($paged);
      $row = $this->render_list_row($kleinanzeigen_id, $data);
      $head = $this->render_head();

      die(json_encode([
        'data' => compact(array('row', 'head', 'post_ID', 'kleinanzeigen_id'))
      ]));
    }

    public function ajax_disconnect()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      $product = wc_get_product($post_ID);
      if ($product) {
        wbp_fn()->disable_sku($product);
        $product->save();
      }

      switch ($screen) {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'modal':
        case 'toplevel_page_kleinanzeigen':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      if ('modal' === $screen) {
        $modal_row = $this->render_task_list_row($post_ID);
      }

      die(json_encode(array(
        'data' => compact(array('row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'))
      )));
    }

    public function ajax_import_kleinanzeigen_data()
    {
      if (isset($_REQUEST['postdata'])) {
        $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
        $kleinanzeigen_id = isset($_REQUEST['postdata']['kleinanzeigen_id']) ? $_REQUEST['postdata']['kleinanzeigen_id'] : null;
      }

      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
      $kleinanzeigendata = isset($_REQUEST['kleinanzeigendata']) ? (object) $_REQUEST['kleinanzeigendata'] : null;

      if ($kleinanzeigen_id) {
        $kleinanzeigen_id = $this->parse_kleinanzeigen_id($kleinanzeigen_id);
      }

      if ($kleinanzeigendata) {
        $searchable_content = '';
        $content = isset($kleinanzeigendata->content) ? $kleinanzeigendata->content : null;
        $record = isset($kleinanzeigendata->record) ? (object) $kleinanzeigendata->record : null;

        if ($record) {
          // Ad is publicly available
          $title = $record->title;
          $price = Utils::extract_kleinanzeigen_price($record->price);
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
            'aktionswochen' => array('Aktionswochen', 'fn' => array('aktionswochen', 'default')),
            'aktionsmodell' => 'Aktion',
            'klima' => 'Klima',
            'am lager' => 'Sofort lieferbar',
            'sofort verfügbar' => 'Sofort lieferbar',
            'sofort lieferbar' => 'Sofort lieferbar',
            'lagermaschine' => 'Sofort lieferbar',
            'leicht gebraucht' => 'Leicht Gebraucht',
            'limited edition' => 'Limited Edition',
            'lim. edition' => 'Limited Edition',
            'mietmaschine' => array('Mieten', 'fn' => array('rent', 'default')),
            'neu' => 'Neu',
            'neumaschine' => 'Neu',
            'neufahrzeug' => 'Neu',
            'neues modell' => 'Neues Modell',
            'top modell' => 'Top Modell',
            'neuwertig' => array('Neuwertig', 'match_type' => 'like'),
          );

          // Handle contents
          foreach ($parts as $key => $val) {

            if (wbp_fn()->text_contains($key, $searchable_content, isset($val['match_type']) ? $val['match_type'] : null)) {

              $fns = isset($val['fn']) ? $val['fn'] : 'default';
              $fns = !is_array($fns) ? array($fns) : $fns;

              foreach ($fns as $fn) {
                if (is_callable(array(wbp_fn(), 'handle_product_contents_' . $fn), false, $callable_name)) {

                  if (!is_array($val)) {
                    $term_name = $val;
                  } elseif (isset($val[0])) {
                    $term_name = $val[0];
                  }
                  $product = call_user_func(array(wbp_fn(), 'handle_product_contents_' . $fn), compact('product', 'price', 'title', 'content', 'term_name'));
                }
              }
            }
          }

          // Handle brands
          $brands = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => false
          ]);

          foreach ($brands as $brand) {
            $exists = false;
            if (wbp_fn()->text_contains('(?:Motorenhersteller:?\s*(' . $brand->name . '))', $content, 'raw')) {
              $exists = true;
            } elseif (wbp_fn()->text_contains(esc_html($brand->name), esc_html($searchable_content))) {
              $exists = true;
            }
            if (true === $exists) {
              wbp_th()->set_product_term($product, $brand->term_id, 'brand', true);
            }
          }

          // Handle product attributes
          foreach ($tags as $key => $tag) {
            wbp_th()->set_pa_term($product, WC_CUSTOM_PRODUCT_ATTRIBUTES['specials'], $tag, true);
          }

          if ($record) {
            $product = wbp_fn()->enable_sku($product, $record);
            if (!is_wp_error($product)) {
              $product->save();
            } else {
              $error_data = $product->get_error_data();
              if (isset($error_data['resource_id'])) {
                if (is_callable('write_log')) {
                  // write_log($error_data);
                }
                wp_delete_post($error_data['resource_id'], true);
              }
            };
          }
        }


        wp_insert_post(array(
          'ID' => $post_ID,
          'post_title' => $title,
          'post_type' => 'product',
          'post_status' => 'draft',
          'post_content' => $content,
          'post_excerpt' => $excerpt // Utils::sanitize_excerpt($content, 300)
        ), true);
      }

      switch ($screen) {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      switch ($screen) {
        case 'toplevel_page_kleinanzeigen':
          $head = $this->render_head();
          break;
      }

      die(json_encode([
        'data' => compact(['row', 'head', 'post_ID', 'kleinanzeigen_id'])
      ]));
    }

    public function ajax_import_kleinanzeigen_images()
    {
      if (isset($_REQUEST['postdata'])) {

        $post_ID = isset($_REQUEST['postdata']['post_ID']) ? $_REQUEST['postdata']['post_ID'] : null;
        $kleinanzeigen_id = isset($_REQUEST['postdata']['kleinanzeigen_id']) ? $_REQUEST['postdata']['kleinanzeigen_id'] : null;
      }

      if ($kleinanzeigen_id) {
        $kleinanzeigen_id = $this->parse_kleinanzeigen_id($kleinanzeigen_id);
      }
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $kleinanzeigendata = isset($_REQUEST['kleinanzeigendata']) ? $_REQUEST['kleinanzeigendata'] : null;
      $kleinanzeigen_images = isset($kleinanzeigendata['images']) ? $kleinanzeigendata['images'] : [];
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      Utils::remove_attachments($post_ID);

      $ids = [];
      for ($i = 0; $i < count($kleinanzeigen_images); $i++) {
        $url = $kleinanzeigen_images[$i];
        $ids[] = Utils::upload_image($url, $post_ID);
        if ($i === 0) {
          set_post_thumbnail((int) $post_ID, $ids[0]);
        }
      }

      unset($ids[0]); // remove main image from gallery
      update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
      update_post_meta((int) $post_ID, 'kleinanzeigen_id', $kleinanzeigen_id);

      switch ($screen) {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      echo json_encode([
        'data' => compact(['row', 'post_ID', 'kleinanzeigen_id'])
      ]);
      wp_die();
    }

    public function ajax_delete_images()
    {
      if (isset($_REQUEST['post_ID'])) {
        $post_ID = $_REQUEST['post_ID'];
        Utils::remove_attachments($post_ID);
      }
      $row = $this->render_wc_admin_list_row($post_ID);

      echo json_encode([
        'data' => compact(['row', 'post_ID'])
      ]);
      wp_die();
    }

    public function ajax_delete_post()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : '';
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      $product = wc_get_product($post_ID);
      if ($product) {
        $product->delete(true);
      }

      $data = $this->prepare_list_table($paged);
      $row = $this->render_list_row($kleinanzeigen_id, $data);
      $head = $this->render_head();

      echo json_encode([
        'data' => compact(['row', 'head', 'kleinanzeigen_id'])
      ]);
      wp_die();
    }

    public function ajax_get_product_categories()
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

    public function ajax_get_brand_images()
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

    public function upload_image($url, $post_ID)
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

    public function render_wc_admin_list_row($post_ID)
    {
      require_once wbp_ka()->plugin_path('includes/class-kleinanzeigen-wc-admin-list-table-products.php');
      $wp_wc_admin_list_table = new Extended_WC_Admin_List_Table_Products();

      ob_start();
      $wp_wc_admin_list_table->render_row($post_ID);
      return ob_get_clean();
    }

    public function prepare_list_table($paged)
    {
      global $wp_list_table;

      $data = Utils::get_json_data(array('paged' => $paged));
      $wp_list_table->setData($data);
      return $data;
    }

    public function render_list_row($kleinanzeigen_id, $data)
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

    public function render_task_list_row($post_ID, $args = array())
    {
      global $wp_tasks_list_table;

      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;

      $product = wc_get_product($post_ID);
      $data = array_merge(array('product' => $product, 'task_type' => $task_type), $args);
      $wp_tasks_list_table->setData($data);

      ob_start();
      $wp_tasks_list_table->render_row($data);
      return ob_get_clean();
    }

    public function render_head()
    {
      global $wp_list_table;

      ob_start();
      $wp_list_table->render_head();
      return ob_get_clean();
    }

    public function build_tasks()
    {
      global $wp_list_table;

      ob_start();
      $wp_list_table->build_tasks();
      return ob_get_clean();
    }

    public function get_record($id)
    {
      $ads = wbp_fn()->get_all_ads();
      $ids = array_column($ads, 'id');
      $record_key = array_search($id, $ids);
      return is_int($record_key) ? $ads[$record_key] : null;
    }
  }
}
