<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Webpremiere_Kleinanzeigen` doesn't exists yet.
if (!class_exists('Utils')) {


  class Utils
  {
    static function get_json_data($args = array())
    {
      $defaults = array(
        'pageSize' => get_option('kleinanzeigen_items_per_page', 25),
        'paged' => 1
      );
      $options = wp_parse_args($args, $defaults);
      extract($options);

      $remote_url = self::parse_remote_url();

      if (!$remote_url) {
        $fetch_type = 'emptydata';
      } else {
        $fetch_type = USE_AD_DUMMY_DATA ? 'dummydata' : '';
      }

      switch($fetch_type) {
        case 'emptydata':
          $dir = 'data/empty';
          $fn = 'page.json';
          $file = wbp_ka()->plugin_path(trailingslashit($dir) . $fn);

          if (!file_exists($file)) {
            $response = new WP_Error(403, __('Could not fetch empty dataset.', 'kleinanzeigen'));
          } else {
            $response['body'] = self::read($file);
          }

          break;
        case 'dummydata':
          $dir = 'data/samples';
          $fn = 'page-' . $paged . '.json';
          $file = wbp_ka()->plugin_path(trailingslashit($dir) . $fn);

          if (!file_exists($file)) {
            $response = wp_remote_get($remote_url);
            self::write($fn, $response['body'], $dir);
          }

          $response['body'] = self::read($file);
          break;
        default:
          $remote_url = $remote_url . '?pageNum=' . $paged . '&pageSize=' . $pageSize;
          $response = wp_remote_get($remote_url);
      }

      if (!is_wp_error($response)) {
        $data = $response['body'];
        return json_decode($data);
      } else {
        return $response;
      }
    }

    public static function parse_remote_url()
    {
      $url = trailingslashit(KLEINANZEIGEN_URL);
      $pro_account = get_option('kleinanzeigen_is_pro_account', '') === "1" ? 'pro/' : '';
      $account_name = get_option('kleinanzeigen_account_name', '');
      return !empty($account_name) ? sanitize_url("{$url}{$pro_account}{$account_name}/ads", array('http', 'https')) : null;
    }

    static function write_log($vars) {
      if(! IS_PRODUCTION && is_callable('write_log')) {
        write_log($vars);
      }
    }

    static function read($file)
    {
      if (!file_exists($file)) {
        return;
      }
      return file_get_contents($file);
    }

    static function write($fn, $data, $dir = 'tmp',)
    {
      $paths = array_filter(explode('/', $dir));
      $dir = array_reduce($paths, function($cum, $cur) {
        $path = ($cum ? trailingslashit($cum) : '') . $cur;
        $dir = wbp_ka()->plugin_path($path);
        if (!file_exists($dir)) {
          mkdir($dir);
        }
        return $path;
      });

      $file = wbp_ka()->plugin_path($dir . '/' . $fn);

      $changePerms = function ($path) {
        $currentPerms = fileperms($path) & 0777;
        $worldWritable = $currentPerms | 0007;
        if ($worldWritable == $currentPerms) {
          return;
        }

        chmod($path, $worldWritable);
      };

      $changePerms($file);
      file_put_contents($file, $data);
    }

    static function toggle_array_item($ids, $id, $bool = null)
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

    static function account_error_check($data, $error_template)
    {

      if (!$data) {
        $data = new WP_Error(403, __('No account data found', 'kleinanzeigen'));
      }
      if (wp_doing_ajax() && is_wp_error($data)) {
        die(json_encode(array(
          "head" => wbp_ka()->include_template($error_template, true, array('message' => $data->get_error_message()))
        )));
      }
      return $data;
    }

    static function extract_kleinanzeigen_price($text)
    {
      // $regex = '/^([\d.]+)/';
      // preg_match($regex, $text, $matches);
      // return !empty($matches) ? str_replace('.', '', $matches[0]) : 0;
      return preg_replace('/[\s.,a-zA-Z€\$]*/', '', $text);
    }

    static function upload_image($url, $post_ID)
    {
      if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
      }

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

    static function remove_attachments($post_ID)
    {

      $product = wc_get_product($post_ID);
      if ($product) {
        $attachment_ids[] = $product->get_image_id();
        $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
        for ($i = 0; $i < count($attachment_ids); $i++) {
          wp_delete_post($attachment_ids[$i]);
        }
      }
    }

    static function reg_increment($matches)
    {
      return $matches[1] . ++$matches[2] . $matches[3];
    }

    static function sanitize_dup_title($title, $appendix = "", $args = array())
    {
      $defaults = array('cleanup' => false);
      $args = !is_array($args) ? array($args) : $args;
      $options = wp_parse_args($args, $defaults);

      if (true === $options['cleanup']) {
        $title = preg_replace('/(\[ DUPLIKAT \d+ \])/', '', $title . $appendix);
      } else {
        preg_match('/((?!DUPLIKAT \d+).)*(\[ DUPLIKAT \d+ \])/', $title . $appendix, $matches);
        if (isset($matches[0])) {
          return preg_replace_callback('/(.*\[ DUPLIKAT )(\d+)( \])/', array('self', 'reg_increment'), $matches[0]);
        }
      }
      return $title;
    }

    static function sanitize_excerpt($content, $count)
    {
      $content = preg_replace('/<[^>]*>/', ' ', $content); //clear html tags
      $content = preg_replace('/[\s+\n]/', ' ', $content); // clear multiple whitespace
      return substr($content, 0, $count) . '...';
    }

    static function base_64_encode($val) {
      return base64_encode(json_encode($val));
    }

    static function base_64_decode($val) {
      return (array) json_decode(base64_decode($val));
    }

    static function is_valid_title($val)
    {
      preg_match('/\[ DUPLIKAT \d+ ID \d{8,} \]/', $val, $matches);
      if (isset($matches[0])) {
        return false;
      }
      return true;
    }
  }
}
