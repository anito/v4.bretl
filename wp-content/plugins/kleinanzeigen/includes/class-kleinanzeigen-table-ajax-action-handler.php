<?php

// If this file is called directly, abort.
if (!defined('WPINC'))
{
  die();
}

// If class `Kleinanzeigen_Ajax_Action_Handler` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Ajax_Action_Handler'))
{

  class Kleinanzeigen_Ajax_Action_Handler extends Kleinanzeigen
  {

    public function __construct()
    {
      $this->register_ajax_handler();
    }

    public function register_ajax_handler()
    {

      add_action('wp_ajax__ajax_connect', array($this, '_ajax_connect'));
      add_action('wp_ajax__ajax_get_nonce', array($this, '_ajax_get_nonce'));
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

      add_action('wp_ajax_nopriv__ajax_connect', array($this, '_ajax_connect'));
      add_action('wp_ajax_nopriv__ajax_get_nonce', array($this, '_ajax_get_nonce'));
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
    }

    public function _ajax_get_nonce()
    {
      $action = !empty($_REQUEST['_ajax_action_name']) ? $_REQUEST['_ajax_action_name'] : false;
      $this->get_nonce($action);
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

    public function get_nonce($action)
    {
      $nonce = wp_create_nonce($action);
      die(json_encode($nonce));
    }

    public function get_remote()
    {
      if (!isset($_REQUEST['formdata'])) return;
      $formdata = $_REQUEST['formdata'];

      if (!isset($formdata['post_ID'])) return;
      $post_ID = $formdata['post_ID'];

      if (!isset($formdata['kleinanzeigen_id'])) return;
      $kleinanzeigen_id_raw = $formdata['kleinanzeigen_id'];
      $kleinanzeigen_id = wbp_fn()->parse_kleinanzeigen_id($kleinanzeigen_id_raw);

      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;

      $remoteUrl  = wbp_fn()->get_kleinanzeigen_search_url($kleinanzeigen_id);
      $response   = wbp_fn()->remote_call($remoteUrl, 5);
      $record     = wbp_fn()->find_kleinanzeige($kleinanzeigen_id) ?? null;

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

      if (isset($post_ID) && isset($price))
      {
        wbp_fn()->fix_price($post_ID, $price);
      }

      switch ($screen)
      {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      if ('modal' === $screen)
      {
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
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
      
      $status = get_post_status($post_ID);
      $new_post_status = 'draft' === $status ? 'publish' : 'draft';
      $title = get_the_title($post_ID);

      Utils::write_log("## Ajax Toggle State ##");
      Utils::write_log("{$status} => {$new_post_status}: {$post_ID} {$title}");
      Utils::write_log("#######################");

      if ($post_ID)
      {
        $date = get_the_date('Y-m-d H:i:s', $post_ID);
        $gmt = get_gmt_from_date($date);
        wp_update_post(array(
          'ID'            => $post_ID,
          'post_status'   => $new_post_status,
          'post_date'     => $date,
          'post_date_gmt' => $gmt,
        ));
      }

      ob_start();
      switch ($screen)
      {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      $head = null;
      switch ($screen)
      {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      if ('modal' === $screen)
      {
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

      if ($post_ID)
      {
        $product = wc_get_product($post_ID);
        $product->set_featured(!$product->is_featured());
        $product->save();
      }

      switch ($screen)
      {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      switch ($screen)
      {
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
      $args = isset($_REQUEST['args']) ? $_REQUEST['args'] : base64_encode(json_encode(array()));
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      $args = (array) json_decode(base64_decode($args));

      if ($post_ID)
      {
        $title = wc_get_product($post_ID)->get_title();
        $postarr = array_merge(array(
          'ID' => $post_ID
        ), $args);

        Utils::write_log("## Ajax Save Post ##");
        Utils::write_log("{$post_ID} {$title}");
        Utils::write_log("#######################");

        wp_update_post($postarr);
        $product = wc_get_product($post_ID);
        if ("trash" === $product->get_status())
        {
          wbp_fn()->delete_product($post_ID, true);
        }
      }

      switch ($screen)
      {
        case 'toplevel_page_kleinanzeigen':
        case 'modal':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          $head = $this->render_head();
          break;
      }

      $modal_row = null;
      switch ($screen)
      {
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
      if ($product)
      {
        if (isset($ad))
        {
          wbp_fn()->enable_sku($product, $ad, true);
        }
        else
        {
          wbp_fn()->disable_sku($product, true);
        }
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
      if ($product)
      {
        wbp_fn()->disable_sku($product, true);
      }

      switch ($screen)
      {
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
      if ('modal' === $screen)
      {
        $modal_row = $this->render_task_list_row($post_ID);
      }

      die(json_encode(array(
        'data' => compact(array('row', 'modal_row', 'head', 'post_ID', 'kleinanzeigen_id'))
      )));
    }

    public function ajax_import_kleinanzeigen_data()
    {
      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : null;

      if ($kleinanzeigen_id)
      {
        $kleinanzeigen_id = wbp_fn()->parse_kleinanzeigen_id($kleinanzeigen_id);
      }

      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);


      $searchable_content = '';
      $content = isset($_REQUEST['content']) ? $_REQUEST['content'] : null;
      $record = isset($_REQUEST['record']) ? (object) $_REQUEST['record'] : null;

      if (!$record)
      {
        die();
      }

      if (!$post_ID)
      {
        $product = new WC_Product();
        $product->set_name($record->title);
        $post_ID = $product->save();
      }
      else
      {
        $product = wc_get_product($post_ID);
      }

      if ($product)
      {
        wbp_fn()->set_product_data($product, $record, $content);
        $product = wbp_fn()->enable_sku($product, $record);
        $title = wc_get_product($post_ID)->get_title();
        $date = wbp_fn()->ka_formatted_date($record->date);
        $gmt = get_gmt_from_date($date);

        if (is_wp_error($product))
        {
          $error_data = $product->get_error_data();
          if (isset($error_data['resource_id']))
          {
            wp_delete_post($error_data['resource_id'], true);
          }
          die();
        };
      }

      Utils::write_log("##### Ajax Import #####");
      Utils::write_log("{$post_ID} {$title}");
      Utils::write_log("#######################");

      wp_update_post(array(
        'ID'            => $post_ID,
        'post_title'    => $record->title,
        'post_type'     => 'product',
        'post_status'   => 'draft',
        'post_date'     => $date,
        'post_date_gmt' => $gmt,
        'edit_date'     => true,
        'post_content'  => $content,
        'post_excerpt'  => $record->description // Utils::sanitize_excerpt($content, 300)
      ), true);

      switch ($screen)
      {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      $head = null;
      switch ($screen)
      {
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

      $post_ID =  isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;
      $kleinanzeigen_id = isset($_REQUEST['kleinanzeigen_id']) ? $_REQUEST['kleinanzeigen_id'] : null;

      if ($kleinanzeigen_id)
      {
        $kleinanzeigen_id = wbp_fn()->parse_kleinanzeigen_id($kleinanzeigen_id);
      }

      $kleinanzeigen_images = isset($_REQUEST['images']) ? $_REQUEST['images'] : [];
      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);

      Utils::remove_attachments($post_ID);

      $ids = [];
      $count = 0;
      $errors = 0;
      for ($i = 0; $i < count($kleinanzeigen_images); $i++)
      {
        $url = $kleinanzeigen_images[$i];
        $image_id = Utils::upload_image($url, $post_ID);
        if (!is_wp_error($image_id))
        {
          $ids[] = $image_id;
          $count++;
          if ($i === 0)
          {
            set_post_thumbnail((int) $post_ID, $ids[0]);
          }
        }
        else
        {
          $errors++;
        }
      }

      unset($ids[0]); // remove main image from gallery
      update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
      update_post_meta((int) $post_ID, 'kleinanzeigen_id', $kleinanzeigen_id);

      switch ($screen)
      {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
        case 'toplevel_page_kleinanzeigen':
          $data = $this->prepare_list_table($paged);
          $row = $this->render_list_row($kleinanzeigen_id, $data);
          break;
      }

      echo json_encode([
        'data' => compact(['row', 'post_ID', 'kleinanzeigen_id', 'count', 'errors'])
      ]);
      wp_die();
    }

    public function ajax_delete_images()
    {
      if (!isset($_REQUEST['post_ID'])) return;

      $screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : null;
      $post_ID = $_REQUEST['post_ID'];
      Utils::remove_attachments($post_ID);

      $row = null;
      switch ($screen)
      {
        case 'edit-product':
          $row = $this->render_wc_admin_list_row($post_ID);
          break;
      }

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
      if ($product)
      {
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
      foreach (get_terms(['taxonomy' => 'product_cat']) as $key => $term)
      {
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
      foreach (get_terms(['taxonomy' => 'product_brand']) as $key => $term)
      {
        $image_ids = get_metadata('term', $term->term_id, 'image');
        if (!empty($image_ids))
        {
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
      if ($url !== "")
      {
        $file = array();
        $file['name'] = $url;
        $file['tmp_name'] = download_url($url);

        if (!is_wp_error($file['tmp_name']))
        {
          $attachmentId = media_handle_sideload($file, $post_ID);

          if (is_wp_error($attachmentId))
          {
            @unlink($file['tmp_name']);
          }
          else
          {
            $url = wp_get_attachment_url($attachmentId);
          }
        }
        else
        {
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

      $data = Utils::get_page_data(array('paged' => $paged));
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
      if (isset($record_key))
      {
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

    public function get_record($id)
    {
      return wbp_fn()->get_record($id);
    }
  }
}
