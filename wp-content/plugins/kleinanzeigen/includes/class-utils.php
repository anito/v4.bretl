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
        'pageSize' => KLEINANZEIGEN_PER_PAGE,
        'paged' => 1
      );
      $options = wp_parse_args($args, $defaults);
      extract($options);

      $remoteUrl = KLEINANZEIGEN_CUSTOMER_URL . '?pageNum=' . $paged . '&pageSize=' . $pageSize;

      if(USE_AD_DUMMY_DATA) {
        $dir = 'sample-data';
        $fn = 'page-' . $paged . '.json';
        $file = wbp_ka()->plugin_path($dir . '/' . $fn);
        
        if(!file_exists($file)) {
          $response = wp_remote_get($remoteUrl);
          self::write($fn, $response['body'], $dir);
        }

        $response['body'] = self::read($file);
      } else {
        $response = wp_remote_get($remoteUrl);
      }

      if (!is_wp_error($response)) {
        $data = $response['body'];
        return json_decode($data);
      } else {
        return $response;
      }
    }

    static function read($file) {
      if (!file_exists($file)) {
        return;
      }
      return file_get_contents($file);
    }

    static function write($fn, $data, $dir = 'tmp', ) {
      $dir = wbp_ka()->plugin_path($dir);
      if(! file_exists($dir)) {
        mkdir($dir);
      }

      $file = $dir . '/' . $fn;

      $changePerms = function ($path) {
        $currentPerms = fileperms($path) & 0777;
        $worldWritable = $currentPerms | 0007;
        if ($worldWritable == $currentPerms) {
          return;
        }

        $res = chmod($path, $worldWritable);
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

    static function error_check($data, $error_template)
    {
      if (is_wp_error($data)) {
        die(json_encode(array(

          "head" => wbp_include_kleinanzeigen_template($error_template, true, array('message' => $data->get_error_message()))

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

    static function sanitize_title($title, $appendix = "")
    {
      preg_match('/((?!DUPLIKAT \d+).)*(\[ DUPLIKAT \d+ \])/', $title . $appendix, $matches);
      if (isset($matches[0])) {
        return preg_replace_callback('/(.*\[ DUPLIKAT )(\d+)( \])/', array('self', 'reg_increment'), $matches[0]);
      }
      return $title;
    }

    static function sanitize_excerpt($content, $count)
    {
      $content = preg_replace('/<[^>]*>/', ' ', $content); //clear html tags
      $content = preg_replace('/[\s+\n]/', ' ', $content); // clear multiple whitespace
      return substr($content, 0, $count);
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
