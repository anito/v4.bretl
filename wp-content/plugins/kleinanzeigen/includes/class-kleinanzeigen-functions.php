<?php

use Pelago\Emogrifier\CssInliner;

// If this file is called directly, abort.
if (!defined('WPINC'))
{
  die();
}

// If class `Kleinanzeigen_Functions` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Functions'))
{


  class Kleinanzeigen_Functions
  {

    /**
     * The instance
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The instance
     */
    private static $instance = null;

    /**
     * The current screen ID
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $version    The current screen ID
     */
    public $screen_id;

    public function __construct()
    {

      $this->register();
    }

    public function register()
    {
      add_action('current_screen', array($this, 'on_current_screen'));
      add_filter('get_the_terms', array($this, 'get_the_terms'), 10, 3);
      add_filter('get_the_terms', array($this, 'label_filter'), 20, 3);
      // add_filter('shutdown', array($this, 'log_query'));

      add_action('woocommerce_before_product_object_save', array($this, 'product_before_save'), 99, 2);
      add_filter('woocommerce_product_data_store_cpt_get_products_query', array($this, 'handle_cpt_get_products_query'), 10, 2);

      add_filter('wp_insert_post_data', array($this, 'before_insert_post'), 99, 2);
      add_action('save_post_product', array($this, 'save_post_product'), 99, 3);
      add_action('save_post_product', array($this, 'quick_edit_product_save'), 10, 1);
      add_action('before_delete_post', array('Utils', 'remove_attachments'));

      add_action('wp_insert_post_data', array($this, 'publish_guard'), 99, 3);
      add_filter('wp_insert_post_empty_content', '__return_false');
    }

    public function log_query()
    {
      global $wpdb;

      if (is_null($wpdb->queries)) return;

      foreach ($wpdb->queries as $q)
      {
        Utils::write_log($q);
      }
    }

    // Woo internal product save
    public function product_before_save($product)
    {
      wbp_th()->process_featured($product);
    }

    public function load_files()
    {
      require_once wbp_ka()->plugin_path('includes/class-kleinanzeigen-table-ajax-action-handler.php');
    }

    /**
     * Handle a custom query var e.g. 'sku_compare' in wc_get_products args 
     * @param array $query - args for WP_Query.
     * @param array $query_vars - Query vars from WC_Product_Query.
     * @return array modified $query
     * 
     * Example:       wc_get_products(array(
     *                  'status'      => 'publish',
     *                  'limit'       => -1,
     *                  'sku_compare' => 'NOT EXISTS' // key the (modified) query vars are filtered for where _sku NOT EXISTS
     */
    static public function handle_cpt_get_products_query($query, $query_vars)
    {
      if (!empty($query_vars['sku_compare']))
      {
        $query['meta_query'] = array('relation' => 'AND');
        $query['meta_query'][] = array(
          'key' => '_sku',
          'compare' => esc_attr($query_vars['sku_compare']),
        );
      }
      if (!empty($query_vars['kleinanzeigen_id']))
      {
        $query['meta_query'][] = array(
          'meta_key' => '_kleinanzeigen_id',
          'value' => esc_attr($query_vars['kleinanzeigen_id']),
        );
      }
      if (!empty($query_vars['featured_products']))
      {
        $query['meta_query'][] = array(
          'taxonomy'         => 'product_visibility',
          'terms'            => 'featured',
          'field'            => 'name',
          'operator'         => 'IN',
          'include_children' => false, // optional
        );
      }

      return $query;
    }

    public function on_current_screen($screen)
    {
      $this->screen_id = $screen->id;

      switch ($this->screen_id)
      {
        case 'edit-product':
          require_once wbp_ka()->plugin_path('includes/class-kleinanzeigen-wc-admin-list-table-products.php');

          new Extended_WC_Admin_List_Table_Products();

          break;
        case 'toplevel_page_kleinanzeigen':
          require_once wbp_ka()->plugin_path('includes/class-kleinanzeigen-list-table.php');
          require_once wbp_ka()->plugin_path('includes/class-kleinanzeigen-list-table-tasks.php');

          // setcookie('ka-paged', 1);
          break;
      }
    }

    public function get_screen_id()
    {
      return $this->screen_id;
    }

    public function table_ajax_handler()
    {
      new Kleinanzeigen_Ajax_Table();
      new Kleinanzeigen_Ajax_Table_Modal();
      new Kleinanzeigen_Ajax_Action_Handler();
    }

    public function get_transient_data()
    {

      require_once wbp_ka()->plugin_path('includes/class-utils.php');

      if (false === ($data = get_transient('kleinanzeigen_data')))
      {

        Utils::write_log('Fetching data...');

        $time = time();
        $ads =  Utils::get_all_ads();
        if (!is_wp_error($ads))
        {
          $data = array();
          array_walk_recursive($ads['data'], function ($a) use (&$data)
          {
            $data[] = $a;
          });
        }
        else
        {
          wp_die();
        }

        Utils::write_log('Done in ' . time() - $time . 's');

        // Set transient expiration to cron interval
        $interval_option = get_option('kleinanzeigen_crawl_interval');
        $schedule = $this->get_schedule_by_slug($interval_option);
        $expires = $schedule['interval'];

        set_transient('kleinanzeigen_data', $data, $expires);
      }
      else
      {
        Utils::write_log('Using transient data...');
      }
      return $data;
    }

    public function verify_account()
    {
      return is_wp_error(Utils::get_page_data()) ? false : true;
    }

    public function get_schedule_by_slug($slug)
    {
      $schedule = null;
      $schedules = Kleinanzeigen_Admin::get_schedule();
      foreach ($schedules as $key => $val)
      {
        if ($key === $slug)
        {
          $schedule = $schedules[$key];
          break;
        }
      };
      return $schedule;
    }

    function build_tasks($name = '', $args = array())
    {
      /**
       * All inconsistency relevant tasks should be set to priority => 1
       * Status is `publish` unless stated differently in 
       */
      $tasks = array(
        'invalid-sku' => array(
          'priority' => 1,
          'items' => array(),
          'status' => array('publish', 'draft')
        ),
        'invalid-price' => array(
          'priority' => 1,
          'items' => array(),
          'status' => array('publish', 'draft')
        ),
        'invalid-cat' => array(
          'priority' => 1,
          'items' => array(),
          'status' => array('publish', 'draft')
        ),
        'drafts' => array(
          'priority' => 1,
          'items' => array(),
          'status' => array('draft')
        ),
        'has-sku' => array(
          'priority' => 1,
          'items' => array(),
        ),
        'no-sku' => array(
          'priority' => 1,
          'items' => array(),
        ),
        'drafts-no-sku' => array(
          'priority' => 1,
          'items' => array(),
          'status' => array('draft')
        ),
        'featured' => array(
          'priority' => 1,
          'items' => array(),
        ),
        'new-product' => array(
          'priority' => 1,
          'items' => array()
        ),
        'updated-product' => array(
          'items' => array(),
          'status' => array('publish', 'draft')
        ),
        'repair_url' => array(
          'items' => array()
        ),
        'disconnected' => array(
          'items' => array(),
          'status' => array('publish', 'draft')
        ),
      );

      if ($name && isset($tasks[$name]))
      {
        $tasks = array($name => $tasks[$name]);
      }
      else
      {
        // Remove non pageload tasks (priority !== 1)
        $tasks = array_filter($tasks, function ($task)
        {
          return isset($task['priority']) && $task['priority'] === 1;
        });
      }

      $get_query_args = function ($task_name) use ($tasks, $args)
      {
        $status = isset($tasks[$task_name]['status']) ? $tasks[$task_name]['status'] : array('publish');
        $args = wp_parse_args($args, array('status' => $status, 'limit' => -1));
        return $args;
      };

      $ads = $this->get_transient_data();

      foreach ($tasks as $task_name => $task)
      {

        $items = $this->get_task_list_items($ads, $task_name, $get_query_args($task_name));

        foreach ($items as $item)
        {
          $tasks[$task_name]['items'][] = array(
            'task_type'   => $task_name,
            'record'      => $item['record'],
            'product'     => $item['product'],
          );
        }
      }

      return isset($tasks[$name]) ? $tasks[$name] : $tasks;
    }

    public function get_invalid_cat_products($args)
    {

      $suffix = '';
      if (isset($args['status']))
      {
        $status = $args['status'];
        $suffix = is_array($status) ? implode('_', $status) : $status;
        $suffix = '_' . $suffix;
      }
      if (false === ($data = get_transient("missing_cat_products{$suffix}")))
      {

        $default_cat = get_term_by('id', get_option('default_product_cat'), 'product_cat');
        $special_term_slugs = array_merge(array($default_cat->slug), array_keys(WC_COMMON_TAXONOMIES));
        $term_slugs = array_map(function ($term)
        {
          return $term->slug;
        }, get_terms(
          array(
            'taxonomy'    => array('product_cat'),
            'hide_empty'  => false,
            'number'       => 0
          )
        ));
        $default_term_slugs = array_diff($term_slugs, $special_term_slugs);
        $defaults = array(
          'post_type'       => 'product',
          'post_status'     => array('publish'),
          'posts_per_page'  => -1,
          'tax_query'       => array(
            array(
              'relation'    => 'OR',
              'taxonomy'    => 'product_cat',
              'field'       => 'slug',
              'operator'    => 'NOT IN',
              'terms'       => $default_term_slugs,
            ),
          ),
        );

        $options = array();
        $args = array_merge($defaults, $args);

        array_walk($args, function ($arg, $k) use (&$options)
        {
          if ('status' === $k) $options['post_status'] = $arg;
          else $options[$k] = $arg;
        });

        $query = new WP_Query($options);
        $posts = $query->get_posts();
        $data = array_map('wc_get_product', $posts);
        set_transient("missing_cat_products{$suffix}", $data, 2);
      }
      return $data;
    }

    public function product_has_missing_cat($product)
    {
    }

    public function get_product_from_ad($ad)
    {
      $found_by = '';

      // Find by `sku`
      $product = $this->find_product_by_sku($ad->id);

      if ($product)
      {

        $found_by = 'sku';
        $product = $this->maybe_update_ad_product($product->get_id(), $ad);
      }
      else
      {

        // Find by `title` AND `price`
        $product = $this->find_product_by_title_and_price($ad->title, Utils::extract_kleinanzeigen_price($ad->price));

        if ($product)
        {

          $found_by = 'title';
        }
      }

      if (empty($found_by))
      {
        Utils::write_log($ad->title);
      }
      return compact('product', 'found_by');
    }

    public function maybe_update_ad_product($post_ID, $record)
    {
      $args = array();
      $product = wc_get_product($post_ID);

      /**
       * @return boolean
       */
      $is_diff_title = function () use ($product, $record, &$args)
      {
        $diff = ($name = html_entity_decode($product->get_name())) !== $record->title ? $name : false;
        if ($diff)
        {
          $title = substr($record->title, 0, 15);
          Utils::log("##### {$title} #####");
          Utils::log("{$name} => {$record->title}");

          /**
           * Fetch new content
           * Assuming that when title has changed, the ad has been edited so that the content also might be affected
           */
          $doc = wbp_fn()->get_dom_document($record);
          $el = $doc->getElementById('viewad-description-text');
          $content = $doc->saveHTML($el);
          $args['post_content'] = $content;

          // Update metadata from new title & content
          $this->set_product_data($product, $record, $content);
        }
        return $diff;
      };
      /**
       * @return boolean
       */
      $is_diff_date = function () use ($post_ID, $record, &$args)
      {
        $record_date = $this->ka_formatted_date($record->date, 'Y-m-d');
        $date = get_the_date('Y-m-d', $post_ID);
        $record_datetime = $this->ka_formatted_date($record->date, 'Y-m-d H:i:s');
        $datetime = get_the_date('Y-m-d H:i:s', $post_ID);

        $diff = false;
        // Date updated
        if ($record_date !== $date)
        {
          Utils::log("##### Date update #####");
          $diff = true;
        }
        else
        {
          $record_timestamp = strtotime($record_datetime);
          $datetime_timestamp = strtotime($datetime);
          // For multiple datetime updates per day
          if ($record_timestamp > $datetime_timestamp)
          {
            Utils::log("### DateTime update ###");
            $diff = true;
          }
        }

        if ($diff)
        {
          $title = substr($record->title, 0, 15);
          Utils::log($title);
          Utils::log("{$datetime} => {$record_datetime}");
          Utils::log('#######################');

          $args['post_date'] = $record_datetime;
          $args['post_date_gmt'] = get_gmt_from_date($record_datetime);
          $args['edit_date'] = true;
        }

        return $diff;
      };

      if ($product && ($is_diff_title() || $is_diff_date()))
      {

        // Avoid recursion
        remove_action('save_post_product', array($this, 'save_post_product'), 99);
        wp_update_post(
          array_merge(array(
            'ID'            => $post_ID,
            'post_type'     => 'product',
            'post_status'   => $product->get_status(),
            'post_title'    => $record->title,
            'post_excerpt'  => $record->description
          ), $args)
        );
        add_action('save_post_product', array($this, 'save_post_product'), 99, 3);
      }

      return $product;
    }

    public function find_product_by_title_and_price($title, $price)
    {
      global $wpdb;

      $posts_table = $wpdb->posts;
      $postmeta_table = $wpdb->postmeta;

      // Takes into account both a previously htmlentity encoded title and the decoded title version
      $post_ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $posts_table, $postmeta_table WHERE ($posts_table.post_title='%s' OR $posts_table.post_title='%s') AND $posts_table.post_type LIKE '%s' AND $postmeta_table.meta_key='%s' AND $postmeta_table.meta_value='%s' LIMIT 1", $title, htmlentities($title), 'product', '_price', $price));

      if (isset($post_ID))
      {
        return wc_get_product((int) $post_ID);
      }
      return false;
    }

    public function find_product_by_sku($sku)
    {
      global $wpdb;

      $postmeta_table = $wpdb->postmeta;

      $post_ID = $wpdb->get_var($wpdb->prepare("SELECT post_ID FROM $postmeta_table WHERE $postmeta_table.meta_key='%s' AND $postmeta_table.meta_value='%s' LIMIT 1", '_sku', $sku));

      if (isset($post_ID))
      {
        return wc_get_product((int) $post_ID);
      }
      return false;
    }

    public function kleinanzeigen_product_exists($records)
    {
      global $wpdb;
      $prepare = $wpdb->prepare("SELECT 'post_ID', 'meta_value' FROM $wpdb->postmeta WHERE meta_key='_kleinanzeigen_id' LIMIT 1000");
      $result = $wpdb->get_var($prepare);
      $query = new WP_Query(array(
        'meta_key' => '_kleinanzeigen_id',
        'meta_compare' => 'EXISTS'
      ));

      return $result;
    }

    protected function get_task_list_items($ads, $task_type, $args = array('status' => 'publish'))
    {

      $ids = wp_list_pluck((array) $ads, 'id');
      $keyed_records = array_combine($ids, $ads);
      $items = array();

      if ('no-sku' === $task_type)
      {

        $products = wc_get_products(array_merge($args, array('sku_compare' => 'NOT EXISTS')));
        $record = null;
        foreach ($products as $product)
        {
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('drafts-no-sku' === $task_type)
      {

        $products = wc_get_products(array_merge($args, array('sku_compare' => 'NOT EXISTS')));
        $record = null;
        foreach ($products as $product)
        {
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('invalid-cat' === $task_type)
      {

        $products = $this->get_invalid_cat_products($args);
        foreach ($products as $product)
        {
          $sku = $product->get_sku();
          $record = isset($keyed_records[$sku]) ? $keyed_records[$sku] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('has-sku' === $task_type)
      {
        $record = null;
        $products = wc_get_products(array_merge($args, array('sku_compare' => 'EXISTS')));
        foreach ($products as $product)
        {
          $sku = (int) $product->get_sku();
          $record = isset($keyed_records[$sku]) ? $keyed_records[$sku] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('drafts' === $task_type)
      {
        $record = null;
        $products = wc_get_products(array_merge($args, array('sku_compare' => 'EXISTS')));
        foreach ($products as $product)
        {
          $sku = (int) $product->get_sku();
          $record = isset($keyed_records[$sku]) ? $keyed_records[$sku] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('featured' === $task_type)
      {
        $record = null;
        $featured_posts = $this->get_featured_products($args);
        foreach ($featured_posts as $post)
        {
          $product = new WC_Product($post->ID);
          $sku = (int) $product->get_sku();
          $record = isset($keyed_records[$sku]) ? $keyed_records[$sku] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('updated-product' === $task_type)
      {
        $products = wc_get_products(array_merge($args, array('sku_compare' => 'EXISTS')));
        foreach ($products as $product)
        {
          $sku = (int) $product->get_sku();
          $record = isset($keyed_records[$sku]) ? $keyed_records[$sku] : null;
          if (!is_null($record)) $this->maybe_update_ad_product($product->get_id(), $record);
        }
        // Return empty array since all product implicitly should have been repaired
        return $items;
      }
      if ('new-product' === $task_type)
      {
        $product = null;
        $products = wc_get_products(array('status' => array('publish', 'draft', 'trash'), 'limit' => -1, 'sku_compare' => 'EXISTS'));

        $skus = array_map(function ($product)
        {
          return (int) $product->get_sku();
        }, $products);

        $diffs = array_diff($ids, $skus);
        $diffs = array_filter($diffs, function ($val) use ($keyed_records)
        {
          $title = $keyed_records[$val]->title;
          $identical_products = $this->product_title_equals($title);
          return 0 === count($identical_products);
        });

        $items = array();
        foreach ($diffs as $key => $sku)
        {
          $record = $keyed_records[$sku];
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ('disconnected' === $task_type)
      {

        foreach ($ads as $record)
        {

          $query_args = array(
            'meta_query'  => array(
              'relation' => 'AND',
              array(
                'key'     => '_sku',
                'compare' => 'NOT EXISTS',
              ),
              array(
                'key'     => '_price',
                'value' => (float) Utils::extract_kleinanzeigen_price($record->price),
              ),
              array(
                'key'     => '_cron_last_state',
                'compare' => 'EXISTS'
              ),
            ),
            'title' => $record->title
          );
          $query = new WC_Product_Query(array_merge($args, $query_args));
          $products = $query->get_products();

          if (!empty($products))
          {
            $product = array_shift($products);
            $items[] = compact('product', 'task_type', 'record');
          }
        }
        return $items;
      }

      $products = wc_get_products($args);

      foreach ($products as $product)
      {
        $post_ID = $product->get_id();
        $sku = (int) $product->get_sku();
        $image = wp_get_attachment_image_url($product->get_image_id());
        $shop_price = wp_kses_post($product->get_price_html());
        $shop_price_raw = $product->get_price();
        $ka_price = '-';
        $title = $product->get_name();
        $permalink = get_permalink($post_ID);

        if (!empty($sku))
        {
          switch ($task_type)
          {
            case 'invalid-sku':
              $record = null;
              if (!in_array($sku, $ids))
              {
                $items[] = compact('product', 'task_type', 'record');
              }
              break;
            case 'invalid-price':
              if (in_array($sku, $ids))
              {
                $record = $keyed_records[$sku];
                if ($this->has_price_diff($record, $product))
                {
                  $items[] = compact('product', 'task_type', 'record');
                }
              }
              break;
          }
        }
      }
      return $items;
    }

    public function publish_guard($post)
    {
      if ('product' !== $post['post_type']) return $post;

      // Check for valid product title when publishing
      require_once wbp_ka()->plugin_path('includes/class-utils.php');
      if (!Utils::is_valid_title($post['post_title']) && $post['post_status'] == 'publish')
      {
        $post['post_status'] = 'draft';
        // prevent adding duplicate DUPLIKAT info to title
        $post['post_title'] = Utils::sanitize_dup_title($post['post_title']);
      }
      return $post;
    }

    public function quick_edit_product_save($data)
    {
      if (is_int($data)) return $data;

      // Check for a quick editsave action
      if (wp_doing_ajax() && isset($_POST['ID']))
      {
        // Render custom columns
        new Extended_WC_Admin_List_Table_Products();
      };
    }

    public function fix_price($post_ID, $price)
    {
      $product = wc_get_product($post_ID);

      if ($product)
      {
        if ($product->is_on_sale())
        {
          $this->set_pseudo_sale_price($product, $price, 10);
        }
        else
        {
          $product->set_regular_price($price);
        }
        $product->save();

        // Fix price for WC_CUSTOM_PRODUCT_ATTRIBUTES[$key] (e.g. Mietmaschinen)
        $categories = wbp_th()->get_product_terms($post_ID, 'cat');
        if (!empty($categories))
        {
          $key = 'rent';
          $cat_name = WC_COMMON_TAXONOMIES[$key];
          $cat_names = wp_list_pluck($categories, 'name');
          if (in_array($cat_name, $cat_names))
          {
            $attr_name = WC_CUSTOM_PRODUCT_ATTRIBUTES[$key];
            $attr_slug = wc_attribute_taxonomy_name($attr_name);

            $attributes = self::get_mietdauer_attributes($attr_name, (int) $price);
            $terms = $attributes['attributes'][$attr_name];
            foreach ($terms as $key => $term)
            {
              $term_name = $term['name'];
              $attributes = array_merge($term['attributes'], array('menu_order' => $key));
              wbp_th()->set_pa_term($product, $attr_name, $term['name'], true, array('is_variation' => 1));
              $this->create_product_variation($product->get_id(), $term_name, $attr_name, $attributes);
            }
          }
        }
      }
    }

    public function find_kleinanzeige($id): stdClass | null
    {
      $id = (int) $id;
      $paged = 1;
      $num_pages = 1;
      while ($paged <= $num_pages)
      {
        $data = Utils::get_page_data(array('paged' => $paged));
        if (1 === $num_pages)
        {
          $categories = $data->categoriesSearchData;
          $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
          $num_pages = ceil($total_ads / get_option('kleinanzeigen_items_per_page', ITEMS_PER_PAGE));
        }
        if (!is_wp_error($data))
        {
          $ads = $data->ads;
          foreach ($ads as $val)
          {
            if ($val->id == (int) $id)
            {
              $ad = $val;
              $paged = $num_pages;
              break;
            }
          };
        }
        $paged++;
      }
      return $ad ?? null;
    }

    public function before_insert_post($data, $postarr)
    {

      if ("product" != $data['post_type']) return $data;

      // Decode auto-encoded htmlentities in title e.g. &gt;Aktion&lt => >Aktion<
      $data['post_title'] = html_entity_decode($data['post_title']);

      return $data;
    }

    public function save_post_product($post_ID, $post, $update)
    {
      $product = wc_get_product($post_ID);
      if (!$product) return 0;


      if ($product->is_type('variation'))
      {
        $variation = new WC_Product_Variation($product);
        $post_ID = $variation->get_parent_id();
        $product = wc_get_product($post_ID);
      }

      wbp_th()->process_sale($post_ID, $post);
      wbp_th()->maybe_remove_default_cat($post_ID);

      if (wp_doing_ajax())
      {
        $ad = isset($_POST['kleinanzeigen_id']) ? $this->find_kleinanzeige((int) $_POST['kleinanzeigen_id']) : null;

        if (is_null($ad))
        {
          $this->disable_sku($product);
        }
        else
        {
          $this->enable_sku($product, $ad);
        }
      }
      elseif (wp_doing_cron())
      {
        // Nothing here
      }
      else
      {
        $this->process_kleinanzeigen($product);
      }
    }

    public function process_kleinanzeigen($product)
    {
      $post_ID = $product->get_id();

      // If this is an autosave, our form has not been submitted, so we don't want to do anything.
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
      if (wp_doing_ajax()) return;
      if (!current_user_can('edit_post', $post_ID)) return;

      $title = $product->get_name();
      $content = $product->get_description();
      $errors = [];
      $ad = null;
      $recovered = false;
      $kleinanzeigen_id = '';
      $date = '';

      /**
       * Helper function recover
       */
      $recover = function ($post_ID) use (&$recovered)
      {

        $json = get_post_meta($post_ID, '_kleinanzeigen_record', true);
        $_POST['_kleinanzeigen_recover'] = '';

        $ad_obj = (object) json_decode($json, true);

        if (isset($ad_obj->id))
        {
          $recovered = true;
          return $ad_obj;
        }
      };

      /**
       * Helper function commnet
       */
      $comment = function ($pos_key = 1, $content = '')
      {

        $pos = array_combine(range(0, 2), array('', '-start', '-end'));
        $name = isset($pos[$pos_key]) ? $pos[$pos_key] : $pos[0];
        return '<!--COMMENT' . strtoupper($name) . '-->' . $content;
      };

      if (isset($_POST['_kleinanzeigen_id']))
      {
        $kleinanzeigen_id = sanitize_text_field($_POST['_kleinanzeigen_id']);
      }

      $req_recover = isset($_POST['_kleinanzeigen_recover']) ? "true" == $_POST['_kleinanzeigen_recover'] : false;

      if (empty($kleinanzeigen_id))
      {
        if ($req_recover)
        {
          $ad = $recover($post_ID);
        }
      }
      else
      {
        $ad = $this->find_kleinanzeige($kleinanzeigen_id);
      }

      if ($ad)
      {

        $ad_title = $ad->title;
        $title = $ad_title;
        $date = $ad->date;

        $success = wbp_fn()->enable_sku($product, $ad);

        if (is_wp_error($success))
        {
          $errors[] = new WP_Error(400, __('A Product with the same Ad ID already exists. Delete this draft or enter a different Ad ID.', 'kleinanzeigen'));
        }
        else
        {
          $_POST['_kleinanzeigen_id'] = $ad->id;
          $_POST['_sku'] = $ad->id;
        }
      }
      else
      {
        wbp_fn()->disable_sku($product);
        $_POST['_kleinanzeigen_id'] = '';
        $_POST['_sku'] = '';
      }

      if (!ALLOW_DUPLICATE_TITLES)
      {

        // Check for duplicate titles
        $results = $this->product_title_equals($title);

        foreach ($results as $result)
        {
          if ((int) $result->ID != $post_ID)
          {
            $errors[] = new WP_Error(400, __('A product with the same title already exists. Delete this draft or enter a different title.', 'kleinanzeigen'));
          }
        }
      }

      // Cleanup comments
      $content = preg_replace('/(?:' . $comment(1) . ')\s*(.|\r|\n)*(?:' . $comment(2) . ')/', '', $content);

      if (!empty($errors))
      {
        $title = wp_strip_all_tags(Utils::sanitize_dup_title($title . " [ DUPLIKAT " . 0 . " ]"));
        $before_content = $comment(1, '<div style="max-width: 100%;">');
        $inner_content = '';
        $after_content = $comment(2, '</div>');
        foreach ($errors as $error)
        {
          $inner_content .= '<div style="display: inline-block; border: 1px solid red; border-radius: 5px; border-left: 5px solid red; margin-bottom: 5px; padding: 5px 10px; color: #f44;"><b style="font-size: 14px;">' . $error->get_error_message() . '</div>';
        }
        $content = sprintf('%1$s' . $inner_content . '%2$s' . '%3$s', $before_content, $after_content, $content);
      }
      else
      {
        $title = Utils::sanitize_dup_title($title, '', array('cleanup' => true));
      }


      Utils::log("#### Update Post ####");
      Utils::log("{$post_ID} {$title}");
      Utils::log("#######################");

      $date = wbp_fn()->ka_formatted_date($date);
      $gmt = get_gmt_from_date($date);

      // Avoid recursion
      remove_action('save_post_product', array($this, 'save_post_product'), 99);
      wp_update_post([
        'ID'            => $post_ID,
        'post_type'     => 'product',
        'post_status'   => !empty($errors) ? 'draft' : (isset($_POST['post_status']) ? $_POST['post_status'] : $product->get_status()),
        'post_date'     => $date,
        'post_date_gmt' => $gmt,
        'edit_date'     => true,
        'post_content'  => $content,
        'post_excerpt'  => $product->get_short_description(),
        'post_title'    => html_entity_decode($title) // Eventhough this will be encoded during save, we catch this up again in wp_update_post_data filter
      ]);
      add_action('save_post_product', array($this, 'save_post_product'), 99, 3);
    }

    public function product_title_exists($title)
    {
      return count($this->product_title_equals($title)) >= 1;
    }

    public function product_title_equals($title)
    {

      global $wpdb;

      $prepare = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'product' AND post_status != '%s' AND post_status != '%s' AND post_title != '' AND post_title = %s", 'inherit', 'trash', $title);
      return $wpdb->get_results($prepare);
    }

    /**
     * Add or remove term to/from product attribute
     */
    public function set_pa_term($product, $taxonomy_name, $term_name, $bool, $args = array())
    {
      $taxonomy = wc_attribute_taxonomy_name($taxonomy_name);
      $product_id = $product->get_id();

      if (!term_exists($term_name, $taxonomy))
      {
        wp_insert_term($term_name, $taxonomy);
      }

      $terms = wp_get_object_terms($product_id, $taxonomy);
      $term_ids = wp_list_pluck($terms, 'term_id');

      $term_id = get_term_by('name', $term_name, $taxonomy)->term_id;
      $term_ids = Utils::toggle_array_item($term_ids, $term_id, $bool); // remove or add the term

      wp_set_object_terms($product_id, $term_ids, $taxonomy);

      $prev_attributes = get_post_meta($product_id, '_product_attributes', true);
      $attributes = !empty($prev_attributes) ? $prev_attributes : array();
      $attributes[$taxonomy] = wp_parse_args($args, array(
        'name'          => $taxonomy,
        'value'         => $term_ids,
        'position'      => 1,
        'is_visible'    => 1,
        'is_variation'  => 0,
        'is_taxonomy'   => 1
      ));

      update_post_meta($product_id, '_product_attributes', $attributes);
      return $term_ids;
    }

    public function get_kleinanzeigen_url($url)
    {
      return KLEINANZEIGEN_URL . $url;
    }

    public function get_kleinanzeigen_search_url($id)
    {
      return KLEINANZEIGEN_URL . ($id ? '/s-' . $id . '/k0' : '/');
    }

    public function has_price_diff($record, $product)
    {

      $price = Utils::extract_kleinanzeigen_price($record->price);
      $woo_price = $product->get_price();

      return $price !== $woo_price;
    }

    public function create_ad_product($record, $content)
    {

      // Only create product if sku will be unique
      $products = wc_get_products(array('sku' => $record->id, 'limit' => 1));
      if (!empty($products))
      {
        $ids = array_map(function ($product)
        {
          return $product->get_id();
        }, $products);
        return new WP_Error('400', __('Artikelnummer existiert bereits.', 'kleinanzeigen'), array(
          'data' => array(
            'product_ids' => $ids
          )
        ));
      }

      $product = new WC_Product();
      $product->set_name($record->title);
      $product->set_sku($record->id);
      $product->set_status('draft');
      $post_ID = $product->save();
      $title = $record->title;

      $this->set_product_data($product, $record, $content);

      Utils::log("##### New Product #####");
      Utils::log("{$post_ID} {$title}");
      Utils::log("#######################");

      wp_update_post(array(
        'ID'            => $post_ID,
        'post_title'    => $title,
        'post_type'     => 'product',
        'post_status'   => $product->get_status(),
        'post_content'  => $content,
        'post_excerpt'  => $record->description, // Utils::sanitize_excerpt($content, 300)
      ), true);

      $maybe_duplicate_sku = $this->enable_sku($product, $record);
      if (is_wp_error($maybe_duplicate_sku))
      {
        return $maybe_duplicate_sku;
      }

      return $post_ID;
    }

    public function get_dom_document($record)
    {

      $remoteUrl = wbp_fn()->get_kleinanzeigen_search_url($record->id);
      $contents = file_get_contents($remoteUrl);
      libxml_use_internal_errors(true);
      $doc = new DOMDocument();
      $doc->loadHTML($contents);
      return $doc;
    }

    public function get_document_images($doc)
    {
      $images = array();
      $xpath = new DOMXpath($doc);
      $items = $xpath->query("//*[@id='viewad-product']//*[@data-ix]//img/@data-imgsrc");

      foreach ($items as $item)
      {
        $images[] = $item->value;
      }
      return array_unique($images);
    }

    public function create_product_images($post_ID, $images = array())
    {

      if (count($images))
      {
        Utils::remove_attachments($post_ID);

        $ids = [];
        for ($i = 0; $i < count($images); $i++)
        {
          $url = $images[$i];
          $attachment_id = Utils::upload_image($url, $post_ID);
          if ($attachment_id)
          {
            $ids[] = $attachment_id;
            if ($i === 0)
            {
              set_post_thumbnail((int) $post_ID, $ids[0]);
            }
          }
        }

        if (count($ids)) unset($ids[0]); // remove main image from gallery
        if (count($ids))
        {
          update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
        }
      }
      return $ids;
    }

    public function get_product_variation($product, $term_name, $taxonomy)
    {
      $avail_variations = $product->get_available_variations();
      $variations = array_filter($avail_variations, function ($variation) use ($taxonomy, $term_name)
      {
        $attribute = $variation['attributes']['attribute_' . $taxonomy];
        return $attribute === sanitize_title($term_name);
      });
      return reset($variations);
    }

    public function create_product_variation($product_id, $term_name, $attr_name, $attributes)
    {
      $product = new WC_Product_Variable($product_id);

      $taxonomy = wc_attribute_taxonomy_name($attr_name);

      // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
      if (!taxonomy_exists($taxonomy))
      {
        register_taxonomy(
          $taxonomy,
          'product_variation',
          array(
            'hierarchical' => false,
            'label' => $attr_name,
            'query_var' => true,
            'rewrite' => array('slug' => sanitize_title($attr_name)), // The base slug
          ),
        );
      }

      // Get the post Terms names from the parent variable product.
      $post_term_names =  wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));

      // Check if the post term exist and if not we set it in the parent variable product.
      if (!in_array($term_name, $post_term_names))
        wp_set_post_terms($product_id, $term_name, $taxonomy, true);

      $variation_post = array(
        'post_title'  => $product->get_name(),
        'post_name'   => 'product-' . $product_id . '-variation',
        'post_status' => 'publish',
        'post_parent' => $product_id,
        'post_type'   => 'product_variation',
        'menu_order'  => $attributes['menu_order'],
        'guid'        => $product->get_permalink()
      );

      // Create the product variation or use an existing one
      $variation_arr = $this->get_product_variation($product, $term_name, $taxonomy);
      if ($variation_arr)
      {
        $variation_id = $variation_arr['variation_id'];
      }
      else
      {
        $variation_id = wp_insert_post($variation_post);
      }
      $variation = new WC_Product_Variation($variation_id);

      $variation->set_regular_price($attributes['regular_price']);
      $variation->set_sku(!empty($attributes['sku']) ? $attributes['sku'] : '');
      $variation->save();

      if (!term_exists($term_name, $taxonomy))
      {
        wp_insert_term($term_name, $taxonomy); // Create the term
      }
      $term_slug = get_term_by('name', $term_name, $taxonomy)->slug; // Get the term slug
      update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
      return $variation;
    }

    public function set_pseudo_sale_price($product, $price, $percent = 10)
    {
      $regular_price = (int) $price + (int) $price * $percent / 100;
      $product->set_regular_price($regular_price);
      $product->set_sale_price($price);
      $product->save();
    }

    public function text_contains($needles, $haystack, $searchtype = '')
    {
      $needles = explode(',', $needles);
      $ret = false;
      foreach ($needles as $needle)
      {

        $needle = preg_quote(trim($needle));
        switch ($searchtype)
        {
          case 'raw':
            preg_match('/' . wp_unslash($needle) . '/i', $haystack, $matches);
            break;
          case 'like':
            preg_match('/' . wp_unslash($needle) . '/i', $haystack, $matches);
            break;
          default:
            preg_match('/(?:^|\b)' . $needle . '(?!\w)/i', $haystack, $matches);
        }

        if (!empty($matches[0]))
        {
          $ret = true;
          break;
        }
      }
      return $ret;
    }

    // Callable product contents functions
    public function handle_product_contents_sale($args)
    {
      extract($args);
      $product = wc_get_product($product_id);

      $this->set_pseudo_sale_price($product, $price);
      return $this->handle_product_label($args['term_name'], $product);
    }

    public function handle_product_contents_aktion($args)
    {
      extract($args);
      $product = wc_get_product($product_id);

      $term = wbp_th()->get_product_term('aktion', 'cat');

      if ($term)
      {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }
      return $product;
    }

    public function handle_product_contents_rent($args)
    {
      extract($args);
      $product = wc_get_product($product_id);

      /**
       * Handle Product Category
       */
      $term = wbp_th()->get_product_term('rent', 'cat');

      if ($term)
      {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }

      /**
       * Handle products tags
       */
      $terms = [];
      $term_names = isset(WC_TERMS['rent']) ? WC_TERMS['rent'] : array();
      foreach ($term_names as $term_name)
      {
        $term = get_term_by('name', $term_name, 'product_tag');
        wbp_th()->set_product_term($product, $term->term_id, 'tag', true);
      }

      /**
       * Handle Mietmaschinen Variations
       */
      $product_id = $product->get_id();
      if ('variable' !== $product->get_type())
      {
        $product = new WC_Product_Variable($product);
        $product->save();
        wp_set_object_terms($product_id, 'variable', 'product_type');
      }

      $attr_name = WC_CUSTOM_PRODUCT_ATTRIBUTES['rent'];

      // Check if the Term name exist and if not create it
      $attributes = self::get_mietdauer_attributes($attr_name, (int) $price);
      $terms = $attributes['attributes'][$attr_name];
      foreach ($terms as $key => $term)
      {
        $term_name = $term['name'];
        $attributes = array_merge($term['attributes'], array('menu_order' => $key));
        $this->set_pa_term($product, $attr_name, $term['name'], true, array('is_variation' => 1));
        $this->create_product_variation($product_id, $term_name, $attr_name, $attributes);
      }

      return $product;
    }

    public function handle_product_contents_aktionswochen($args)
    {
      extract($args);
      $product = wc_get_product($product_id);

      $term = wbp_th()->get_product_term('aktionswochen', 'cat');

      if ($term)
      {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }
      return $product;
    }

    public function handle_product_contents_default($args)
    {
      extract($args);
      $product = wc_get_product($product_id);

      $this->handle_product_label($args['term_name'], $product);
      return $product;
    }

    public function handle_product_label($name, $product)
    {
      $term_id = wbp_th()->add_the_product_term($name, 'label');
      if ($term_id)
      {
        wbp_th()->set_product_term($product, $term_id, 'label', true);
      }
      return $product;
    }

    public function get_the_terms($terms, $post_ID, $taxonomy)
    {
      if (is_wp_error($terms))
      {
        return array();
      }
      return $terms;
    }

    public function label_filter($terms, $post_ID, $taxonomy)
    {
      if (!is_wp_error($terms) && 'product_label' === $taxonomy)
      {
        $terms = $this->filter_exclusive_label_terms($terms);
      }
      return $terms;
    }

    public function filter_exclusive_label_terms($terms)
    {

      $exclusive_labels = array(
        array(
          'neuwertig',
          'neu'
        ),
        array(
          'leicht gebraucht',
          'gebraucht'
        ),
        array(
          'guter zustand',
          'neu'
        ),
        array(
          'top zustand',
          'neu'
        ),
        array(
          'aktionswochen',
          'aktion'
        ),
      );

      foreach ($exclusive_labels as $group)
      {
        $term_names = array_map('strtolower', wp_list_pluck($terms, 'name'));
        $intersection = array_intersect($term_names, $group);
        if (count($intersection) > 1)
        {
          $exclusive_term = get_term_by('name', $group[0], 'product_label');
          $diff_term_names = array_diff($term_names, $group);
          $diff_terms = array_map(function ($name)
          {
            return get_term_by('name', $name, 'product_label');
          }, $diff_term_names);
          $terms = array_merge(array($exclusive_term), $diff_terms);
        }
      }
      return $terms;
    }

    public function get_featured_products($args = array())
    {

      $args = wp_parse_args($args, array('status' => 'publish'));

      $tax_query[] = array(
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'featured',
        'operator' => 'IN', // or 'NOT IN' to exclude feature products
      );

      $query = new WP_Query(array(
        'post_type'           => 'product',
        'post_status'         => $args['status'],
        'posts_per_page'      => -1,
        'tax_query'           => $tax_query
      ));
      return $query->get_posts();
    }

    public static function get_mietdauer_attributes($attr_name, $price)
    {
      return array(
        'attributes' => array(
          $attr_name => array(
            array(
              'name' => __('1 Day', 'kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 2),
              'attributes' => array('regular_price' => (int) $price * 2)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 3),
              'attributes' => array('regular_price' => (int) $price * 3)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 4),
              'attributes' => array('regular_price' => (int) $price * 4)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 5),
              'attributes' => array('regular_price' => (int) $price * 5)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 6),
              'attributes' => array('regular_price' => (int) $price * 6)
            ),
            array(
              'name' => sprintf(__('%d Days', 'kleinanzeigen'), 7),
              'attributes' => array('regular_price' => (int) $price * 7)
            ),
          )
        ),
        'sku' => '',
      );
    }

    public function enable_sku(&$product, $ad)
    {
      if (is_int($product))
      {
        $product = wc_get_product($product);
      }

      $post_ID = (int) $product->get_id();

      try
      {
        if (is_object($ad) && isset($ad->id))
        {
          $product->set_sku($ad->id);
          $product->save();
        }
        else
        {
          $message = __('Ad is not a valid object', 'kleinanzeigen');
          return new WP_Error(400, $message, array(
            'data' => array(
              'product' => $product,
              'message' => $message
            )
          ));
        }
      }
      catch (WC_Data_Exception $e)
      {
        return new WP_Error($e->getErrorCode(), $e->getMessage(), array_merge(
          $e->getErrorData(),
          array(
            'data' => array(
              'product' => $product,
              'message' => $e->getMessage()
            )
          )
        ));
      }

      $this->set_sku($post_ID, $ad);

      if (wp_doing_cron())
      {
        delete_post_meta($post_ID, '_cron_last_state');
      }

      return $product;
    }

    public function disable_sku_url($post_ID)
    {
      delete_post_meta($post_ID, '_kleinanzeigen_url');
      delete_post_meta($post_ID, '_kleinanzeigen_search_url');
    }

    private function set_sku($post_ID, $ad)
    {
      update_post_meta($post_ID, '_kleinanzeigen_id', $ad->id);
      update_post_meta($post_ID, '_kleinanzeigen_url', $this->get_kleinanzeigen_url($ad->url));
      update_post_meta($post_ID, '_kleinanzeigen_search_url', $this->get_kleinanzeigen_search_url($ad->id));
      update_post_meta($post_ID, '_kleinanzeigen_record', html_entity_decode(json_encode($ad, JSON_UNESCAPED_UNICODE)));
    }

    public function disable_sku(WC_Product &$product)
    {

      $product->set_sku('');
      $product->save();

      $post_ID = (int) $product->get_id();
      delete_post_meta($post_ID, '_kleinanzeigen_id');
      delete_post_meta($post_ID, '_kleinanzeigen_url');
      delete_post_meta($post_ID, '_kleinanzeigen_search_url');

      return $product;
    }

    function delete_product($id, $force = false)
    {
      $product = wc_get_product($id);

      if (empty($product))
        return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

      // If we're forcing, then delete permanently.
      if ($force)
      {
        if ($product->is_type('variable'))
        {
          foreach ($product->get_children() as $child_id)
          {
            $child = wc_get_product($child_id);
            $child->delete(true);
          }
        }
        elseif ($product->is_type('grouped'))
        {
          foreach ($product->get_children() as $child_id)
          {
            $child = wc_get_product($child_id);
            $child->set_parent_id(0);
            $child->save();
          }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
      }
      else
      {
        $product->delete();
        $result = 'trash' === $product->get_status();
      }

      if (!$result)
      {
        return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
      }

      // Delete parent product transients.
      if ($parent_id = wp_get_post_parent_id($id))
      {
        wc_delete_product_transients($parent_id);
      }
      return true;
    }

    public function parse_kleinanzeigen_id($val)
    {
      preg_match('/(\/?)(\d{8,})/', $val, $matches);
      if (isset($matches[2]))
      {
        return $matches[2];
      }
      return false;
    }

    public function remote_call($url, $tries = 3, $retry = 1)
    {
      $response = wp_remote_get(esc_url_raw($url), array(
        'timeout' => 10
      ));

      if (!is_wp_error($response) && ($response['response']['code'] === 200))
      {
        return $response;
      }
      elseif ($retry++ < $tries)
      {
        sleep($retry * 2);
        return $this->remote_call($url, $tries, $retry);
      }
      return $response;
    }

    public function set_product_data(WC_Product &$product, $record, $content)
    {
      $title = $record->title;
      $price = Utils::extract_kleinanzeigen_price($record->price);
      $excerpt = $record->description;
      $tags = !empty($record->tags) ? $record->tags : [];
      $url = $record->url;
      $searchable_content = $title . ' ' . $content;
      $product_id = $product->get_id();

      if ($product->get_price() !== $price)
      {
        $product->set_regular_price($price);
        $product->save();
      }

      $definitions = array(
        'Aktionspreis'        => array('aktionspreis', 'match_type' => 'like', 'fn' => 'sale'),
        'Allrad'              => array('allrad', 'match_type' => 'like'),
        'Vorführmaschine'     => array(
          array('vorführm', 'match_type' => 'like'),
          array('vfm'),
        ),
        'Guter Zustand'       => array('guter zustand'),
        'Sonderpreis'         => array('sonderpreis'),
        'Aktion'              => array('aktion, aktionsmodell', 'fn' => 'aktion'),
        'Aktionswochen'       => array('aktionswochen', 'fn' => 'aktionswochen'),
        'klima'               => array('klima'),
        'Sofort lieferbar'    => array('am lager, sofort verfügbar, sofort lieferbar, lagermaschine'),
        'Leicht Gebraucht'    => array('leicht gebraucht'),
        'Gebraucht'           => array('gebrauchtmaschine'),
        'Limited Edition'     => array('limited edition, lim. edition'),
        'Mieten'              => array('mietmaschine', 'fn' => 'rent'),
        'Neu'                 => array('neumaschine, neufahrzeug'),
        'Neues Modell'        => array('neues modell'),
        'Top Modell'          => array('top modell, topmodell'),
        'Top Zustand'         => array('topzust, top zust', 'match_type' => 'like'),
        'Top Ausstattung'     => array('topausst, top ausstat', 'match_type' => 'like'),
        'Von Privat'          => array(
          array('von privat, rein privat, aus privater hand, privatbesitzer, im kundenauftrag, privatauftrag, privat vom Besitzer'),
          array('kommission, kommision', 'match_type' => 'like')
        ),
        'Neuwertig'           => array('neuwertig', 'match_type' => 'like'),
      );

      // Handle contents
      $parts = [];
      array_walk($definitions, function ($part, $k) use (&$parts)
      {
        if (is_array($part[0]))
        {
          $i = 0;
          while (isset($part[$i]) && is_array($part[$i]))
          {
            $parts[] = array_merge(array('term_name' => $k, 'needles' => array_shift($part[$i])), $part[$i]);
            $i++;
          }
        }
        else
        {
          $parts[] = array_merge(array('term_name' => $k, 'needles' => array_shift($part)), $part);
        }
      });

      foreach ($parts as $part)
      {

        if ($this->text_contains($part['needles'], $searchable_content, isset($part['match_type']) ? $part['match_type'] : null))
        {

          $fns = isset($part['fn']) ? $part['fn'] : array();
          $fns = !is_array($fns) ? array($fns) : $fns;
          $fns = array_merge($fns, array('default'));

          foreach ($fns as $fn)
          {
            if (is_callable(array($this, 'handle_product_contents_' . $fn), false, $callable_name))
            {

              $term_name = $part['term_name'];

              $product = call_user_func(array($this, 'handle_product_contents_' . $fn), compact('product_id', 'price', 'title', 'content', 'term_name'));
            }
          }
        }
      }


      // Handle brands
      $brands = get_terms([
        'taxonomy' => 'product_brand',
        'hide_empty' => false
      ]);

      foreach ($brands as $brand)
      {
        $exists = false;
        if ($this->text_contains('(?:Motorenhersteller:?\s*(' . preg_quote($brand->name) . '))', $content, 'raw'))
        {
          $exists = true;
        }
        elseif ($this->text_contains('(?<!kein )' . preg_quote($brand->name), esc_html($searchable_content), 'raw'))
        {
          $exists = true;
        }
        if (true === $exists)
        {
          wbp_th()->set_product_term($product, $brand->term_id, 'brand', true);
        }
      }

      // Handle product attributes
      foreach ($tags as $key => $tag)
      {
        wbp_th()->set_pa_term($product, WC_CUSTOM_PRODUCT_ATTRIBUTES['specials'], $tag, true);
      }

      return $product;
    }

    public function get_users_by_capabilty($capabilties, $args = array(), $includes = array())
    {
      global $wpdb;
      $defaults = array(
        'fields'    => 'ID',
        'relation'  => 'OR',
        'compare'   => 'LIKE',
        'limit'     => -1
      );
      $options = wp_parse_args($args, $defaults);
      $capabilities = !is_array($capabilties) ? array($capabilties) : $capabilties;
      $capabilities_meta_key = $wpdb->prefix . 'capabilities';

      $meta_query = array_reduce(
        $capabilities,
        function ($cum, $val) use ($capabilities_meta_key, $options)
        {
          array_push($cum, array(
            'key' => $capabilities_meta_key,
            'value' => $val,
            'compare' => $options['compare']
          ));
          return $cum;
        },
        array(
          'relation' => $options['relation']
        )
      );

      $query =  new WP_User_Query(array(
        'fields' => $options['fields'],
        'meta_query' => $meta_query,
        'include' => $includes
      ));
      return $query->get_results();
    }

    public function _ajax_status_mail()
    {
      check_ajax_referer('ajax-nonce', '_ajax_nonce');
      $receipients = array(
        'to_email'  => wp_get_current_user()->user_email,
      );
      $res = $this->sendMailStatusReport($receipients);

      die(json_encode([
        'data' => array(
          'success' => $res,
          'message' => $res ? __('Please check your inbox', 'kleinanzeigen') : __('Mail could not be sent', 'kleinanzeigen')
        )
      ]));
    }

    public function _ajax_poll()
    {
      $this->ajax_poll();
    }

    public function _ajax_ping()
    {
      $this->ajax_ping();
    }

    public function _ajax_cron()
    {
      $this->ajax_cron();
    }

    public function ajax_poll()
    {
      $action = isset($_REQUEST['_poll_action']) ? $_REQUEST['_poll_action'] : false;

      if ($action)
      {
        $action = Utils::base_64_decode($action);
        if (is_callable($action))
        {
          call_user_func($action);
        }
      }
      die("0");
    }

    public function ajax_ping($is_retry = false)
    {
      check_ajax_referer('ajax-nonce', '_ajax_nonce');

      $post_ID = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : null;

      $success = false;
      $json = get_post_meta($post_ID, '_kleinanzeigen_record', true);
      $ad = (object) json_decode($json, true);
      if (isset($ad->url))
      {
        $url = wbp_fn()->get_kleinanzeigen_url($ad->url);
        $success = Utils::url_exists($url);
      }
      else
      {
        // meta seem to be missing, add it
        $kleinanzeigen_id = get_post_meta($post_ID, '_kleinanzeigen_id', true);
        $ad = wbp_fn()->find_kleinanzeige($kleinanzeigen_id);
        update_post_meta($post_ID, '_kleinanzeigen_record', html_entity_decode(json_encode($ad, JSON_UNESCAPED_UNICODE)));

        if (false === $is_retry)
        {
          return $this->ajax_ping(true);
        }
      }

      die(json_encode(array(
        'success' => $success
      )));
    }

    public function ajax_cron()
    {
      check_ajax_referer('ajax-nonce', '_ajax_nonce');

      $res = wp_remote_get(home_url('wp-cron.php'), array('sslverify' => false));

      if (is_wp_error($res))
      {
        foreach ($res->error_data as $error)
        {
          Utils::write_log($error);
        };
      }
      else
      {
        sleep(5);
      }

      $get_jobs = function ()
      {
        $jobs = [];
        $cron_list = _get_cron_array();
        array_walk($cron_list, function ($cronjobs, $timestamp) use (&$jobs)
        {
          if (is_array($cronjobs))
          {
            foreach ($cronjobs as $key => $cron)
            {
              if (0 === strpos($key, 'kleinanzeigen'))
              {

                $jobs[] = array_merge(
                  array(
                    'slug'      => $key,
                    'name'      => str_replace('_', ' ', preg_replace('/(kleinanzeigen_)(\w*)/', '<span style="font-size: 1.5em;">&#x27B8;&nbsp;</span><span>$2</span>', $key)),
                    'timestamp' => $timestamp * 1000
                  ),
                  $cron[array_key_first($cron)]
                );
              }
            }
          }
        });

        return $jobs;
      };

      $get_todos = function ()
      {
        return wbp_db()->get_todos(0);
      };
      $get_completed = function ()
      {
        return wbp_db()->get_todos();
      };

      $jobs = $get_jobs();
      $todos = $get_todos();
      $completed = $get_completed();

      $max_execution_time = (int) ini_get('max_execution_time');
      $started = time();
      $is_within_met = function () use ($started, $max_execution_time)
      {
        return time() < $max_execution_time + $started - 5;
      };

      while (count($completed) < count($todos) && $is_within_met())
      {
        sleep(1);
        $completed = array_merge($completed, $get_completed());
      }

      $ids = wp_list_pluck($completed, 'id');
      foreach ($ids as $id)
      {
        wbp_db()->remove_job($id);
      }

      $success = true;
      $data = array(
        'jobs'      => $jobs,
        'todos'     => $todos,
        'completed' => $completed
      );

      die(json_encode(compact('data', 'success')));
    }

    public function dropdown_invalid_ads($selected)
    {
      $r = '';

      $actions = $this->get_invalid_ad_actions();

      $r .= "\n\t<option value=0>" . __('No action', 'kleinanzeigen') . "</option>";
      foreach ($actions as $action => $val)
      {
        // Preselect specified action.
        if ($selected === $action)
        {
          $r .= "\n\t<option selected='selected' value='" . esc_attr($action) . "'>" . $val['name'] . "</option>";
        }
        else
        {
          $r .= "\n\t<option value='" . esc_attr($action) . "'>" . $val['name'] . "</option>";
        }
      }

      return $r;
    }

    public function dropdown_crawl_interval($selected, $schedules)
    {
      $r = '';

      foreach ($schedules as $action => $name)
      {
        // Preselect specified action.
        if ($selected === $action)
        {
          $r .= "\n\t<option selected='selected' value='" . esc_attr($action) . "'>$name[display]</option>";
        }
        else
        {
          $r .= "\n\t<option value='" . esc_attr($action) . "'>$name[display]</option>";
        }
      }

      return $r;
    }

    public function sendMailNewProduct($post_ID, $record, $receipients = array())
    {

      $edit_link          = admin_url('post.php?action=edit&post=' . $post_ID);
      $permalink          = $permalink = get_permalink($post_ID);
      $previewlink        = get_preview_post_link($post_ID);
      $product_title      = $record->title;
      $post_status        = get_post_status($post_ID);
      // $thumbnail          = get_the_post_thumbnail_url($post_ID);
      $thumbnail          = $record->image;
      $kleinanzeigen_url  = $this->get_kleinanzeigen_url($record->url);
      $email_heading      = __('New product', 'kleinanzeigen');

      $blogname           = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
      $plugin_name        = wbp_ka()->get_plugin_name();
      $plugin_link        = admin_url("admin.php?page={$plugin_name}");
      $additional_content = '';

      add_action('kleinanzeigen_email_header', array($this, 'email_header'));
      add_action('kleinanzeigen_email_footer', array($this, 'email_footer'));
      add_filter('woocommerce_email_footer_text', array($this, 'replace_placeholders'));

      $email_content = wbp_ka()->include_template('emails/new-product.php', true, compact('product_title', 'post_status', 'edit_link', 'permalink', 'previewlink', 'plugin_link', 'thumbnail', 'blogname', 'email_heading', '_kleinanzeigen_url', 'additional_content'));
      $email_content = $this->style_inline($email_content, compact('thumbnail'));

      return $this->sendMail($email_heading, $email_content, $receipients);
    }

    public function sendMailStatusReport($receipients = array())
    {
      $errors = array();
      foreach ($receipients as $receipient)
      {
        $send_mail_users = wbp_fn()->get_users_by_meta('kleinanzeigen_send_mail_on_new_ad');
        $send_mail_users_mails = wp_list_pluck($send_mail_users, 'user_email');

        if (in_array($receipient, $send_mail_users_mails))
        {
          mail_setting_text = __('Yes', 'kleinanzeigen');
        }
        else
        {
          $mail_setting_text = __('No', 'kleinanzeigen');
        }

        $inactive_ad_setting = get_option('kleinanzeigen_schedule_invalid_ads');
        switch ($inactive_ad_setting)
        {
          case ('publish'):
            $inactive_ad_text = __('Publish ad', 'kleinanzeigen');
            break;
          case ('deactivate'):
            $inactive_ad_text = __('Deactivate ad', 'kleinanzeigen');
            break;
          case ('delete'):
            $inactive_ad_text = __('Delete ad', 'kleinanzeigen');
            break;
          default:
            $inactive_ad_text = __('No action', 'kleinanzeigen');
        }

        $price_setting = (int) get_option('kleinanzeigen_schedule_invalid_prices');
        switch ($price_setting)
        {
          case (0):
            $price_setting_text = __('No', 'kleinanzeigen');
            break;
          case (1):
            $price_setting_text = __('Yes', 'kleinanzeigen');
            break;
        }

        $interval =  get_option('kleinanzeigen_send_status_mail');
        switch ($interval)
        {
          case ('daily'):
            $email_heading = __('Daily status report', 'kleinanzeigen');
            break;
          case ('weekly'):
            $email_heading = __('Weekly status report', 'kleinanzeigen');
            break;
          case ('monthly'):
            $email_heading = __('Monthly status report', 'kleinanzeigen');
            break;
          default:
            $email_heading = __('Current status report', 'kleinanzeigen');
        }

        $blogname           = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $plugin_name        = wbp_ka()->get_plugin_name();
        $plugin_link        = admin_url("admin.php?page={$plugin_name}");
        $additional_content = '';
        $next_event         = null;

        // Next schedule
        $timestamp = $this->get_next_scheduled();
        if ($timestamp)
        {

          $timezone = new DateTimeZone('Europe/Berlin');

          $date = new DateTime();
          $date->setTimestamp($timestamp);
          $date->setTimezone($timezone);


          $fmt = new IntlDateFormatter(
            'de-DE',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Berlin',
            IntlDateFormatter::GREGORIAN
          );
          $fmt->setPattern('EEEE, dd.MM.YYYY hh:mm');
          $next_event = $fmt->format($date);
        }

        $ads = $this->get_transient_data();

        // Tree
        $tree = array(
          array('items' => $ads, 'text' => __('Kleinanzeigen', 'kleinanzeigen')),
          array('level' => 0, 'items' => wc_get_products(array('status' => array('publish'), 'limit' => -1)), 'text' => __('Total published products', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('no-sku', array('status' => 'publish'))['items'], 'product'), 'text' => __('Autonomous', 'kleinanzeigen')),
            array('level' => 1, 'items' => $published_sku = wp_list_pluck($this->build_tasks('has-sku', array('status' => 'publish'))['items'], 'product'), 'text' => __('Linked', 'kleinanzeigen'), 'childs' => array(
              array('level' => 2, 'items' => $published_invalid = wp_list_pluck($this->build_tasks('invalid-sku', array('status' => 'publish'))['items'], 'product'), 'text' => __('Invalid link', 'kleinanzeigen'), 'info' => __('Action required', 'kleinanzeigen')),
              array('level' => 2, 'items' => array_diff($published_sku, $published_invalid), 'text' => __('Valid link', 'kleinanzeigen'))
            ))
          )),
          array('level' => 0, 'items' => wc_get_products(array('status' => array('draft'), 'limit' => -1)), 'text' => __('Total hidden products', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('no-sku', array('status' => 'draft'))['items'], 'product'), 'text' => __('Autonomous', 'kleinanzeigen')),
            array('level' => 1, 'items' => $drafts_sku = wp_list_pluck($this->build_tasks('drafts')['items'], 'product'), 'text' => __('Linked', 'kleinanzeigen'), 'childs' => array(
              array('level' => 2, 'items' => $drafts_invalid = wp_list_pluck($this->build_tasks('invalid-sku', array('status' => 'draft'))['items'], 'product'), 'text' => __('Invalid link', 'kleinanzeigen')),
              array('level' => 2, 'items' => array_diff($drafts_sku, $drafts_invalid), 'text' => __('Valid link', 'kleinanzeigen'), 'info' => __('Ready for publication', 'kleinanzeigen'))
            ))
          )),
          array('level' => 0, 'items' => wp_list_pluck($this->build_tasks('no-sku', array('status' => array('publish', 'draft')))['items'], 'product'), 'text' => __('Total autonomous products', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('no-sku', array('status' => 'publish'))['items'], 'product'), 'text' => __('Published', 'kleinanzeigen')),
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('no-sku', array('status' => 'draft'))['items'], 'product'), 'text' => __('Draft', 'kleinanzeigen'))
          )),
          array('level' => 0, 'items' => array_merge($published_invalid, $drafts_invalid), 'text' => __('Total invalid links', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => $published_invalid = wp_list_pluck($this->build_tasks('invalid-sku', array('status' => 'publish'))['items'], 'product'), 'text' => __('Published', 'kleinanzeigen'), 'info' => __('Action required', 'kleinanzeigen')),
            array('level' => 1, 'items' => $drafts_invalid = wp_list_pluck($this->build_tasks('invalid-sku', array('status' => 'draft'))['items'], 'product'), 'text' => __('Draft', 'kleinanzeigen'))
          )),
          array('level' => 0, 'items' => wp_list_pluck($this->build_tasks('invalid-cat')['items'], 'product'), 'text' => __('Improper category', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('invalid-cat', array('status' => 'publish'))['items'], 'product'), 'text' => __('Published', 'kleinanzeigen'), 'info' => __('Action required', 'kleinanzeigen')),
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('invalid-cat', array('status' => 'draft'))['items'], 'product'), 'text' => __('Draft', 'kleinanzeigen'))
          )),
          array('level' => 0, 'items' => wp_list_pluck($this->build_tasks('featured', array('status' => array('publish', 'draft')))['items'], 'product'), 'text' => __('Featured products', 'kleinanzeigen'), 'childs' => array(
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('featured', array('status' => 'publish'))['items'], 'product'), 'text' => __('Published', 'kleinanzeigen')),
            array('level' => 1, 'items' => wp_list_pluck($this->build_tasks('featured', array('status' => 'draft'))['items'], 'product'), 'text' => __('Draft', 'kleinanzeigen'))
          ))
        );

        add_action('kleinanzeigen_email_header', array($this, 'email_header'));
        add_action('kleinanzeigen_email_footer', array($this, 'email_footer'));
        add_filter('woocommerce_email_footer_text', array($this, 'replace_placeholders'));

        $email_content = wbp_ka()->include_template('emails/status-report.php', true, compact('plugin_link', 'blogname', 'email_heading', 'additional_content', 'tree', 'next_event', 'inactive_ad_text', 'mail_setting_text', 'price_setting_text'));
        $email_content = $this->style_inline($email_content);

        $success = $this->sendMail($email_heading, $email_content, array($receipient));
        if (is_wp_error($success))
        {
          $errors[] = $success;
        }
      }
      return !count($errors);
    }

    public function sendMail($subject, $email_content, $receipients = array())
    {
      $to_email           = isset($receipients['to_email']) ? $receipients['to_email'] : '';
      $bcc                = isset($receipients['bcc']) ? $receipients['bcc'] : '';
      $cc                 = isset($receipients['cc']) ? $receipients['cc'] : '';

      $headers            = array(
        'content-type: text/html',
        "Cc:  {$cc}",
        "Bcc: {$bcc}"
      );

      return wp_mail($to_email, $subject, $email_content, $headers);
    }

    /**
     * Get the email header.
     *
     * @param mixed $email_heading Heading for the email.
     */
    public function email_header($args)
    {
      wbp_ka()->include_template('emails/email-header.php', false, $args);
    }

    /**
     * Get the email footer.
     */
    public function email_footer()
    {
      wbp_ka()->include_template('emails/email-footer.php');
    }

    public function get_next_scheduled($user_id = null)
    {
      if (is_null($user_id))
      {
        $user = wp_get_current_user();
      }
      else
      {
        $user = get_user_by('ID', $user_id);
      }

      $user_schedule = $this->get_users_schedules($user->ID);
      $schedule = key($user_schedule);
      $schedules = $this->get_users_schedules();

      if (!empty($schedules))
      {
        $emails = $schedules[$schedule];
        $args = array(json_encode(array('emails' => $emails), JSON_UNESCAPED_SLASHES));;
        return wp_next_scheduled("kleinanzeigen_report_{$schedule}", $args);
      }
      return null;
    }

    public function get_users_schedules($includes = array())
    {

      // Get authorized users
      $users = wbp_fn()->get_users_by_capabilty(array('administrator', 'shop_manager'), array('fields' => array('ID', 'user_email')), $includes);

      $user_intervals = array();
      foreach ($users as $key => $user)
      {
        $interval = get_user_meta($user->ID, 'kleinanzeigen_send_status_mail', true);
        $user_intervals[$interval][] = $user->user_email;
      }
      return array_filter($user_intervals, function ($interval)
      {
        return ('never' != $interval) && !empty($interval);
      }, ARRAY_FILTER_USE_KEY);
    }

    public function get_users_by_meta($field, $caps = array('administrator', 'shop_manager'))
    {

      // Get authorized users
      $users = wbp_fn()->get_users_by_capabilty($caps, array('fields' => array('ID', 'user_email')));

      // Filter for users that have opt in
      return array_filter($users, function ($user) use ($field)
      {
        return '1' == get_user_meta($user->ID, $field, true);
      });
    }

    public function replace_placeholders($string)
    {
      $domain = wp_parse_url(home_url(), PHP_URL_HOST);

      return str_replace(
        array(
          '{site_title}',
          '{site_address}',
          '{site_url}',
          '{woocommerce}',
          '{WooCommerce}',
        ),
        array(
          wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
          $domain,
          $domain,
          '<a href="https://woocommerce.com">WooCommerce</a>',
          '<a href="https://woocommerce.com">WooCommerce</a>',
        ),
        $string
      );
    }

    /**
     * Apply inline styles to dynamic content.
     *
     * We only inline CSS for html emails, and to do so we use Emogrifier library (if supported).
     *
     * @version 4.0.0
     * @param string|null $content Content that will receive inline styles.
     * @return string
     */
    public function style_inline($content, $args = array())
    {

      require_once wbp_ka()->plugin_path('vendor/autoload.php');

      $css_inliner_class = CssInliner::class;

      if (class_exists($css_inliner_class))
      {
        try
        {
          $css = wbp_ka()->include_template('emails/email-styles.php', true, $args);
          $css_inliner = CssInliner::fromHtml($content)->inlineCss($css);

          do_action('woocommerce_emogrifier', $css_inliner, $this);

          $dom_document = $css_inliner->getDomDocument();

          Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromDomDocument($dom_document)->removeElementsWithDisplayNone();
          $content = Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter::fromDomDocument($dom_document)
            ->convertCssToVisualAttributes()
            ->render();
        }
        catch (Exception $e)
        {
        }
      }

      return $content;
    }

    public function ka_formatted_date($date = '', $format = 'Y-m-d H:i:s')
    {
      if (is_null($date) || empty($date))
      {
        $timestamp = time();
      }
      else
      {
        $timestamp = 0 === strpos($date, 'Heute')
          ? strtotime(str_replace('Heute', 'Today', $date))
          : (0 === strpos($date, 'Gestern')
            ? strtotime(str_replace('Gestern', 'Yesterday', $date))
            : strtotime($date)
          );
      }
      return date($format, $timestamp);
    }

    public function get_record($id)
    {
      $ads = $this->get_transient_data();
      $ids = array_column($ads, 'id');
      $record_key = array_search($id, $ids);
      return is_int($record_key) ? $ads[$record_key] : null;
    }

    public function get_invalid_ad_actions()
    {
      return array(
        'publish' => array(
          'name' => __('Publish', 'kleinanzeigen'),
          'postarr' => array('post_status' => 'publish')
        ),
        'deactivate' => array(
          'name' => __('Deactivate', 'kleinanzeigen'),
          'postarr' => array('post_status' => 'draft')
        ),
        'delete' => array(
          'name' => __('Delete', 'kleinanzeigen'),
          'postarr' => array('post_status' => 'trash')
        ),
      );
    }

    public static function get_instance($file = null): Kleinanzeigen_Functions
    {

      // If the single instance hasn't been set, set it now.
      if (null == self::$instance)
      {
        self::$instance = new self;
      }
      return self::$instance;
    }
  }
}

if (!function_exists('wbp_fn'))
{

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return Kleinanzeigen_Functions
   */
  function wbp_fn(): Kleinanzeigen_Functions
  {
    return Kleinanzeigen_Functions::get_instance();
  }
}

wbp_fn();
