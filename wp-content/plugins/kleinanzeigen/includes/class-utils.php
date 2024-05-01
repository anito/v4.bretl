<?php

// If this file is called directly, abort.
if (!defined('WPINC'))
{
  die();
}

// If class `Webpremiere_Kleinanzeigen` doesn't exists yet.
if (!class_exists('Utils'))
{


  class Utils
  {
    static function get_page_data($args = array())
    {
      if (USE_AD_DUMMY_DATA)
      {
        return self::get_static_page_data($args);
      }
      else
      {
        return self::get_remote_page_data($args);
      }
    }

    private static function get_remote_page_data($args = array())
    {
      $defaults = array(
        'pageSize' => get_option('kleinanzeigen_items_per_page', ITEMS_PER_PAGE),
        'paged' => 1
      );
      $options = wp_parse_args($args, $defaults);
      extract($options);

      $base_url = self::parse_remote_url();

      if (!$base_url)
      {
        $dir = 'data/empty';
        $fn = 'page.json';
        $file = wbp_ka()->plugin_path(trailingslashit($dir) . $fn);

        if (!file_exists($file))
        {
          $response = new WP_Error(403, __('Could not fetch empty dataset.', 'kleinanzeigen'));
        }
        else
        {
          $response['body'] = self::read($file);
        }
      }
      else
      {
        $remote_url = $base_url . '?pageNum=' . $paged . '&pageSize=' . $pageSize;
        $response = wp_remote_get($remote_url);
      }

      if (!is_wp_error($response))
      {
        $data = $response['body'];
        return json_decode($data);
      }
      else
      {
        return $response;
      }
    }

    private static function get_static_page_data($args = array())
    {
      $defaults = array(
        'pageSize' => get_option('kleinanzeigen_items_per_page', ITEMS_PER_PAGE),
        'paged' => 1
      );
      $options = wp_parse_args($args, $defaults);
      extract($options);

      $dir = wbp_ka()->plugin_path('data/samples');

      $create_file = function ($page) use ($dir, $pageSize)
      {
        $fn = 'page-' . $page . '.json';
        $base_url = self::parse_remote_url();
        $remote_url = $base_url . '?pageNum=' . $page . '&pageSize=' . $pageSize;
        $response = wp_remote_get($remote_url);
        self::write($fn, $response['body'], $dir);
      };

      $file = trailingslashit($dir) . 'page-' . $paged . '.json';
      if (!file_exists($file)) $create_file($paged);
      $response['body'] = self::read($file);

      if (!is_wp_error($response))
      {
        $data = $response['body'];
        return json_decode($data);
      }
      else
      {
        return $response;
      }
    }

    public static function get_all_ads()
    {

      // Get first set of data to discover page count
      $data = Utils::account_error_check(Utils::get_page_data(), 'error-message.php');

      if (is_wp_error($data))
      {
        return $data;
      }

      $ads[] = $data->ads;
      $categories = $data->categoriesSearchData;
      $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
      $num_pages = ceil($total_ads / get_option('kleinanzeigen_items_per_page', ITEMS_PER_PAGE));

      // Get remaining pages
      for ($paged = 2; $paged <= $num_pages; $paged++)
      {
        $page_data = Utils::get_page_data(array('paged' => $paged));
        $page_data  = Utils::account_error_check($page_data, 'error-message.php');
        if (!is_wp_error($page_data)) $ads[] = $page_data->ads;
      }
      return array('pages' => $num_pages, 'data' => $ads);
    }

    public static function parse_remote_url()
    {
      $url = trailingslashit(KLEINANZEIGEN_URL);
      $pro_account = get_option('kleinanzeigen_is_pro_account', '') === "1" ? 'pro/' : '';
      $account_name = get_option('kleinanzeigen_account_name', '');
      return !empty($account_name) ? sanitize_url("{$url}{$pro_account}{$account_name}/ads", array('http', 'https')) : null;
    }

    static function log($message)
    {
      $dir = wbp_ka()->plugin_path('logs');
      $fn = 'debug-' . date('Y-m-d') . '.log';
      $file = trailingslashit($dir) . $fn;

      if (!file_exists($dir))
      {
        mkdir($dir);
      };

      if (is_array($message) || is_object($message))
      {
        $message = print_r($message, true);
      }

      $file = fopen($file, "a");
      echo fwrite($file, "[" . date('d-M-y H:i:s T') . "] " . $message . "\n");
      fclose($file);
    }

    static function write_log($log)
    {
      if (is_array($log) || is_object($log)) {
        error_log(print_r($log, true));
      } else {
        error_log($log);
      }
    }

    static function read($file)
    {
      if (!file_exists($file))
      {
        return;
      }
      return file_get_contents($file);
    }

    static function write($fn, $data, $dir = 'tmp',)
    {
      $paths = array_filter(explode('/', $dir));

      if (!file_exists($dir))
      {
        mkdir($dir);
      };
      // array_reduce($paths, function ($cum, $cur) {
      //   $path = ($cum ? trailingslashit($cum) : '') . $cur;
      //   $d = wbp_ka()->plugin_path($path);
      //   if (!file_exists($dir)) {
      //     mkdir($dir);
      //   }
      //   return $path;
      // });

      $file = trailingslashit($dir) . $fn;

      $changePerms = function ($path)
      {
        $currentPerms = fileperms($path) & 0777;
        $worldWritable = $currentPerms | 0007;
        if ($worldWritable == $currentPerms)
        {
          return;
        }

        chmod($path, $worldWritable);
      };

      // $changePerms($file);
      file_put_contents($file, $data);
    }

    static function toggle_array_item($ids, $id, $bool = null)
    {
      if (!isset($bool))
      {
        $bool = in_array($id, $ids);
      }
      # remove id
      $ids = array_diff($ids, array($id));
      if (true === $bool)
      {
        $ids[] = $id;
      }
      return $ids;
    }

    static function account_error_check($data, $error_template)
    {

      if (!$data)
      {
        $data = new WP_Error(403, __('No account data found', 'kleinanzeigen'));
      }
      if (wp_doing_ajax() && is_wp_error($data))
      {
        die(json_encode(array(
          "head" => wbp_ka()->include_template($error_template, true, array('message' => $data->get_error_message()))
        )));
      }
      return $data;
    }

    static function extract_kleinanzeigen_price($text)
    {
      return preg_replace('/[\s.,a-zA-Z€\$]*/', '', $text);
    }

    static function upload_image($url, $post_ID)
    {
      if (!function_exists('download_url'))
      {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
      }

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
          // The error
          $attachmentId = $file['tmp_name'];
        }
      }
      return $attachmentId;
    }

    static function remove_attachments($post_ID)
    {

      $product = wc_get_product($post_ID);
      if ($product)
      {
        $attachment_ids[] = $product->get_image_id();
        $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
        for ($i = 0; $i < count($attachment_ids); $i++)
        {
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

      if (true === $options['cleanup'])
      {
        $title = preg_replace('/(\[ DUPLIKAT \d+ \])/', '', $title . $appendix);
      }
      else
      {
        preg_match('/((?!DUPLIKAT \d+).)*(\[ DUPLIKAT \d+ \])/', $title . $appendix, $matches);
        if (isset($matches[0]))
        {
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

    static function base_64_encode($val)
    {
      return base64_encode(json_encode($val));
    }

    static function base_64_decode($val)
    {
      return (array) json_decode(base64_decode($val));
    }

    static function url_exists($url = NULL)
    {
      if ($url == NULL) return false;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $data = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      return $httpcode >= 200 && $httpcode < 300;
    }

    static function is_valid_title($val)
    {
      preg_match('/\[ DUPLIKAT \d+ ID \d{8,} \]/', $val, $matches);
      if (isset($matches[0]))
      {
        return false;
      }
      return true;
    }
  }
}
