<?php

use Elementor\Core\Files\CSS\Post_Preview;
use Pelago\Emogrifier\CssInliner;

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Functions` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Functions')) {


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

      add_action("woocommerce_before_product_object_save", array($this, "product_before_save"), 99, 2);
      add_filter('woocommerce_product_data_store_cpt_get_products_query', array($this, 'handle_cpt_get_products_query'), 10, 2);

      add_action("save_post", array($this, "save_post"), 99, 2);
      add_action("save_post", array($this, "quick_edit_product_save"), 10, 1);
      add_filter("update_post_metadata", array($this, 'prevent_metadata_update'), 10, 4);

      add_action("wp_insert_post_data", array($this, "publish_guard"), 99, 3);
      add_filter('wp_insert_post_empty_content', array($this, 'return_false'));
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
     * Handle a custom 'sku_compare' query var in wc_get_products args 
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
      if (!empty($query_vars['sku_compare'])) {
        $query['meta_query'][] = array(
          'key' => '_sku',
          'compare' => esc_attr($query_vars['sku_compare']),
        );
      }
      if (!empty($query_vars['kleinanzeigen_id'])) {
        $query['meta_query'][] = array(
          'meta_key' => 'kleinanzeigen_id',
          'value' => esc_attr($query_vars['kleinanzeigen_id']),
        );
      }
      if (!empty($query_vars['featured_products'])) {
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

      switch ($this->screen_id) {
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

    public function get_all_ads()
    {
      require_once wbp_ka()->plugin_path('includes/class-utils.php');
      // Get first set of data to discover page count
      $data = Utils::account_error_check(Utils::get_json_data(), 'error-message.php');
      $ads = $data->ads;
      $categories = $data->categoriesSearchData;
      $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
      $num_pages = ceil($total_ads / get_option('kleinanzeigen_items_per_page', 25));

      // Get remaining pages
      for ($paged = 2; $paged <= $num_pages; $paged++) {
        $page_data = Utils::get_json_data(array('paged' => $paged));
        $page_data  = Utils::account_error_check($page_data, 'error-message.php');
        $ads = array_merge($ads, $page_data->ads);
      }
      return $ads;
    }

    function build_tasks($name = '', $status = array('publish'))
    {
      // All inconsistency relevant tasks should be set to priority => 1
      $tasks = array(
        'invalid-ad' => array(
          'priority' => 1,
          'items' => array()
        ),
        'invalid-price' => array(
          'priority' => 1,
          'items' => array()
        ),
        'invalid-cat' => array(
          'priority' => 1,
          'items' => array()
        ),
        'has-sku' => array(
          'priority' => 2,
          'items' => array()
        ),
        'no-sku' => array(
          'priority' => 2,
          'items' => array()
        ),
        'featured' => array(
          'priority' => 2,
          'items' => array()
        ),
        'new-product' => array(
          'priority' => 3,
          'items' => array()
        )
      );

      if ($name && isset($tasks[$name])) {
        $tasks = array($name => $tasks[$name]);
      } else {
        // Remove high workload tasks (priority === 3)
        $tasks = array_filter($tasks, function ($task) {
          return 3 !== $task['priority'];
        });
      }

      $ads = $this->get_all_ads();
      $args = array(
        'status' => $status,
        'limit' => -1
      );
      $products = wc_get_products($args);

      foreach ($tasks as $task_name => $task) {
        $items = $this->get_task_list_items($products, $ads, $task_name);
        foreach ($items as $item) {
          $id = $item['product'] ? $item['product']->get_ID() : null;
          $tasks[$task_name]['items'][] = array(
            'record' => $item['record'],
            'product_id' => $id
          );
        }
      }

      return isset($tasks[$name]) ? $tasks[$name] : $tasks;
    }

    public function get_invalid_cat_products()
    {

      $default_cat_id = get_option('default_product_cat');
      $default_cat = get_term_by('id', $default_cat_id, 'product_cat');

      $args = array(
        'post_type' => 'product',
        'post_status' => array('publish'),
        'tax_query' => array(
          array(
            'taxonomy'  => 'product_cat',
            'field'     => 'slug',
            'terms' => array($default_cat->slug),
          ),
        ),
      );
      $query = new WP_Query($args);
      $posts = $query->get_posts();
      return array_map(function ($post) {
        return wc_get_product($post->ID);
      }, $posts);
    }

    public function get_product_by_title($title)
    {
      global $wpdb;

      // $sql = $wpdb->prepare(
      //   "
      // 		SELECT ID
      // 		FROM $wpdb->posts
      // 		WHERE post_title = %s
      // 		AND post_type = %s
      // 	",
      //   $title,
      //   'product'
      // );
      $post_ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title='%s' AND post_type LIKE '%s' LIMIT 1", $title, 'product'));

      if (isset($post_ID)) {
        return wc_get_product($post_ID);
      }
    }

    public function get_product_by_sku_($sku)
    {
      global $wpdb;
      $post_ID = $wpdb->get_var($wpdb->prepare("SELECT post_ID FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

      if (isset($post_ID)) {
        return wc_get_product($post_ID);
      }
    }

    public function get_product_by_sku($sku)
    {
      if ($sku) {
        $p = wc_get_products(array(
          'sku' => $sku
        ));

        if (!empty($p)) {
          return $p[0];
        }
      }
    }

    public function kleinanzeigen_product_exists($records)
    {
      global $wpdb;
      $prepare = $wpdb->prepare("SELECT 'post_ID', 'meta_value' FROM $wpdb->postmeta WHERE meta_key='kleinanzeigen_id' LIMIT 1000");
      $result = $wpdb->get_var($prepare);
      $query = new WP_Query(array(
        'meta_key' => 'kleinanzeigen_id',
        'meta_compare' => 'EXISTS'
      ));

      return $result;
    }

    public function get_task_list_items($products, $ads, $task_type)
    {
      $ad_ids = wp_list_pluck($ads, 'id');
      $records = wp_list_pluck((array) $ads, 'id');
      $_records = array_flip($records);
      $items = array();
      if ($task_type === 'no-sku') {
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'NOT EXISTS'));
        $record = null;
        foreach ($products as $product) {
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ($task_type === 'invalid-cat') {
        $products = $this->get_invalid_cat_products();
        foreach ($products as $product) {
          $sku = $product->get_sku();
          $record = isset($_records[$sku]) ? $ads[$_records[$sku]] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ($task_type === 'has-sku') {
        $record = null;
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'EXISTS'));
        foreach ($products as $product) {
          $sku = (int) $product->get_sku();
          $record = isset($_records[$sku]) ? $ads[$_records[$sku]] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ($task_type === 'featured') {
        $record = null;
        $featured_posts = $this->get_featured_products();
        foreach ($featured_posts as $post) {
          $product = new WC_Product($post->ID);
          $sku = (int) $product->get_sku();
          $record = isset($_records[$sku]) ? $ads[$_records[$sku]] : null;
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ($task_type === 'new-product') {
        $product = null;
        $products = wc_get_products(array('status' => array('publish', 'draft', 'trash'), 'limit' => -1, 'sku_compare' => 'EXISTS'));

        $skus = array_map(function ($product) {
          return (int) $product->get_sku();
        }, $products);

        $diffs = array_diff($records, $skus);
        $diffs = array_filter($diffs, function($val) use($ads, $_records) {
          $title = $ads[$_records[$val]]->title;
          $identical_products = $this->product_title_equals($title);
          return 0 === count($identical_products);
        }, ARRAY_FILTER_USE_BOTH);

        $items = array();
        foreach ($diffs as $key => $sku) {
          $record = $ads[$_records[$sku]];
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      foreach ($products as $product) {
        $post_ID = $product->get_id();
        $sku = (int) $product->get_sku();
        $image = wp_get_attachment_image_url($product->get_image_id());
        $shop_price = wp_kses_post($product->get_price_html());
        $shop_price_raw = $product->get_price();
        $ka_price = '-';
        $title = $product->get_name();
        $permalink = get_permalink($post_ID);

        if (!empty($sku)) {
          switch ($task_type) {
            case 'invalid-ad':
              $record = null;
              if (!in_array($sku, $ad_ids)) {
                $items[] = compact('product', 'task_type', 'record');
              }
              break;
            case 'invalid-price':
              if (in_array($sku, $ad_ids)) {
                $record = $ads[$_records[$sku]];
                if ($this->has_price_diff($record, $product)) {
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
      if (!Utils::is_valid_title($post['post_title']) && $post['post_status'] == 'publish') {
        $post['post_status'] = 'draft';
        // prevent adding duplicate DUPLIKAT info to title
        $post['post_title'] = Utils::sanitize_dup_title($post['post_title']);
      }
      return $post;
    }

    public function quick_edit_product_save($data)
    {
      if (is_int($data)) return $data;
      if ('product' !== $data['post_type']) return $data;

      // Check for a quick editsave action
      if (wp_doing_ajax() && isset($_POST['ID'])) {
        // Render custom columns
        new Extended_WC_Admin_List_Table_Products();
      };
    }

    public function fix_price($post_ID, $price)
    {
      $product = wc_get_product($post_ID);

      if ($product) {
        if ($product->is_on_sale()) {
          $this->set_pseudo_sale_price($product, $price, 10);
        } else {
          $product->set_regular_price($price);
        }
        $product->save();

        // Fix price for WC_CUSTOM_PRODUCT_ATTRIBUTES[$key] (e.g. Mietmaschinen)
        $categories = get_the_terms($post_ID, 'product_cat');
        if (!empty($categories)) {
          $key = 'rent';
          $cat_name = WC_COMMON_TAXONOMIES[$key];
          $cat_names = wp_list_pluck($categories, 'name');
          if (in_array($cat_name, $cat_names)) {
            $attr_name = WC_CUSTOM_PRODUCT_ATTRIBUTES[$key];
            $attr_slug = wc_attribute_taxonomy_name($attr_name);

            $attributes = self::get_mietdauer_attributes($attr_name, (int) $price);
            $terms = $attributes['attributes'][$attr_name];
            foreach ($terms as $key => $term) {
              $term_name = $term['name'];
              $attributes = array_merge($term['attributes'], array('menu_order' => $key));
              wbp_th()->set_pa_term($product, $attr_name, $term['name'], true, array('is_variation' => 1));
              $this->create_product_variation($product->get_id(), $term_name, $attr_name, $attributes);
            }
          }
        }
      }
    }

    public function find_kleinanzeige(int $id): stdClass | null
    {
      $paged = 1;
      $num_pages = 1;
      while ($paged <= $num_pages) {
        $data = Utils::get_json_data(array('paged' => $paged));
        if (1 === $num_pages) {
          $categories = $data->categoriesSearchData;
          $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
          $num_pages = ceil($total_ads / get_option('kleinanzeigen_items_per_page', 25));
        }
        if (!is_wp_error($data)) {
          $ads = $data->ads;
          foreach ($ads as $val) {
            if ($val->id == (int) $id) {
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

    public function save_post($post_ID, $post)
    {
      if (!class_exists('WooCommerce', false)) return 0;
      if ($post->post_type != "product" || $post->post_status == 'trash') return 0;

      $product = wc_get_product($post_ID);
      if (!$product) return 0;


      if ($product->is_type('variation')) {
        $variation = new WC_Product_Variation($product);
        $post_ID = $variation->get_parent_id();
        $product = wc_get_product($post_ID);
      }

      wbp_th()->process_sale($post_ID, $post);
      wbp_th()->maybe_remove_default_cat($post_ID);

      if (!wp_doing_ajax()) {
        $this->process_kleinanzeigen($post_ID, $post);
      } else {
        $ad = isset($_POST['kleinanzeigendata']['record']) ? (object) $_POST['kleinanzeigendata']['record'] : null;
        $ad = is_null($ad) ? (isset($_POST['kleinanzeigen_id']) ? $this->find_kleinanzeige((int) $_POST['kleinanzeigen_id']) : null) : $ad;

        if (is_null($ad)) {
          $this->disable_sku($product);
        } else {
          $this->enable_sku($product, $ad);
        }
      }
    }

    public function process_kleinanzeigen($post_ID, $post)
    {
      // If this is an autosave, our form has not been submitted, so we don't want to do anything.
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
      }

      if (wp_doing_ajax()) {
        return;
      }

      // Check the user's permissions.
      if (isset($_POST['post_type']) && 'product' == $_POST['post_type']) {

        if (!current_user_can('edit_post', $post_ID)) {
          return;
        }
      }

      if (isset($_POST['kleinanzeigen_id'])) {
        $kleinanzeigen_id = sanitize_text_field($_POST['kleinanzeigen_id']);
      } else {
        $kleinanzeigen_id = '';
      }

      $product = wc_get_product($post_ID);
      $title = $product->get_name();
      $content = $product->get_description();
      $sku_errors = [];
      $ad = null;

      if (!empty($kleinanzeigen_id)) {
        $ad = $this->find_kleinanzeige($kleinanzeigen_id);
      }

      $recover = function ($post_ID) use (&$is_recovered) {

        $json = get_post_meta($post_ID, 'kleinanzeigen_record', true);
        $_POST['kleinanzeigen_recover'] = '';

        $ad_obj = (object) json_decode($json, true);

        if (isset($ad_obj->id)) {
          $is_recovered = true;
          return $ad_obj;
        }
      };

      $comment = function ($pos_key = 1, $content = '') {

        $pos = array_combine(range(0, 2), array('', '-start', '-end'));
        $name = isset($pos[$pos_key]) ? $pos[$pos_key] : $pos[0];
        return '<!--COMMENT' . strtoupper($name) . '-->' . $content;
      };


      $recover_requested = "true" === get_post_meta($post_ID, 'kleinanzeigen_recover', true);
      if (!$ad && $recover_requested) {
        $is_recovered = false;
        $ad = $recover($post_ID);
        $_POST['kleinanzeigen_id'] = isset($ad->id) ? $ad->id : '';
      }

      // $ad = $recover($post_ID);

      if ($ad) {
        $ad_title = $ad->title;
        $title = $ad_title;

        $sku = (string) $is_recovered ? $ad->id : $product->get_sku();
        $sku_needs_update = $is_recovered || $sku !== $kleinanzeigen_id;

        if ($sku_needs_update) {

          // Throws error if sku already exists
          $success = $this->enable_sku($product, $ad);
          if (is_wp_error($success)) {
            $error = new WP_Error(400, __('A Product with the same Ad ID already exists. Delete this draft or enter a different Ad ID.', 'kleinanzeigen'));
            $sku_errors[] = $error;
          }
        }
      } else {
        $this->disable_sku($product);
      }

      // Check for duplicate titles
      $results = $this->product_title_equals($title);

      if (count($results) >= 1) {
        foreach ($results as $result) {
          if ((int) $result->ID != $post_ID) {
            $sku_errors[] = new WP_Error(400, __('A product with the same title already exists. Delete this draft or enter a different title.', 'kleinanzeigen'));
          }
        }
      }

      // Cleanup comments
      $content = preg_replace('/(?:' . $comment(1) . ')\s*(.|\r|\n)*(?:' . $comment(2) . ')/', '', $content);

      if (!empty($sku_errors)) {
        $title = wp_strip_all_tags(Utils::sanitize_dup_title($title . " [ DUPLIKAT " . 0 . " ]"));
        $before_content = $comment(1, '<div style="max-width: 100%;">');
        $inner_content = '';
        $after_content = $comment(2, '</div>');
        foreach ($sku_errors as $sku_error) {
          $inner_content .= '<div style="display: inline-block; border: 1px solid red; border-radius: 5px; border-left: 5px solid red; margin-bottom: 5px; padding: 5px 10px; color: #f44;"><b style="font-size: 14px;">' . $sku_error->get_error_message() . '</div>';
        }
        $content = sprintf('%1$s' . $inner_content . '%2$s' . '%3$s', $before_content, $after_content, $content);
      } else {
        $title = Utils::sanitize_dup_title($title, '', array('cleanup' => true));
      }

      // Avoid recursion
      remove_action('save_post', array($this, 'save_post'), 99);
      wp_insert_post([
        'ID' => $post_ID,
        'post_type' => 'product',
        'post_status' => !empty($sku_errors) ? 'draft' : (isset($_POST['post_status']) ? $_POST['post_status'] : $product->get_status()),
        'post_content' => $content,
        'post_excerpt' => $product->get_short_description(),
        'post_title' => $title
      ]);
      add_action('save_post', array($this, 'save_post'), 99, 2);
    }

    public function product_title_equals($title)
    {

      global $wpdb;

      $prepare = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'product' AND post_status != '%s' AND post_status != '%s' AND post_title != '' AND post_title = %s", 'inherit', 'trash', $title);
      return $wpdb->get_results($prepare);
    }

    public function prevent_metadata_update($data, $id, $meta_key, $meta_value)
    {
      if ('kleinanzeigen_id' !== $meta_key) return $data;

      $product = wc_get_product($id);
      $sku = $product->get_sku();
      if (!$sku) return 1;
    }

    /**
     * Add or remove term to/from product attribute
     */
    public function set_pa_term($product, $taxonomy_name, $term_name, $bool, $args = array())
    {
      $taxonomy = wc_attribute_taxonomy_name($taxonomy_name);
      $product_id = $product->get_id();

      if (!term_exists($term_name, $taxonomy)) {
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
      $kleinanzeigen_price = Utils::extract_kleinanzeigen_price($record->price);
      $woo_price = $product->get_price($record);

      return $kleinanzeigen_price !== $woo_price;
    }

    public function create_product($record, $doc)
    {

      $el = $doc->getElementById('viewad-description-text');
      $content = $doc->saveHTML($el);

      $product = new WC_Product();
      $product->set_name($record->title);
      $product->set_status('draft');
      $post_ID = $product->save();

      $this->set_product_data($product, $record, $content);

      wp_insert_post(array(
        'ID' => $post_ID,
        'post_title' => $record->title,
        'post_type' => 'product',
        'post_status' => $product->get_status(),
        'post_content' => $content,
        'post_excerpt' => $record->description // Utils::sanitize_excerpt($content, 300)
      ), true);

      $this->enable_sku($product, $record);

      return $post_ID;
    }

    public function create_product_images($post_ID, $doc)
    {

      $images = array();
      $xpath = new DOMXpath($doc);
      $items = $xpath->query("//*[@id='viewad-product']//*[@data-ix]//img/@data-imgsrc");

      foreach ($items as $item) {
        $images[] = $item->value;
      }
      $images = array_unique($images);

      if (count($images)) {
        Utils::remove_attachments($post_ID);

        $ids = [];
        for ($i = 0; $i < count($images); $i++) {
          $url = $images[$i];
          $ids[] = Utils::upload_image($url, $post_ID);
          if ($i === 0) {
            set_post_thumbnail((int) $post_ID, $ids[0]);
          }
        }

        unset($ids[0]); // remove main image from gallery
        if (count($ids)) {
          update_post_meta((int) $post_ID, '_product_image_gallery', implode(',', $ids));
        }
      }
    }

    public function get_product_variation($product, $term_name, $taxonomy)
    {
      $avail_variations = $product->get_available_variations();
      $variations = array_filter($avail_variations, function ($variation) use ($taxonomy, $term_name) {
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
      if (!taxonomy_exists($taxonomy)) {
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
      if ($variation_arr) {
        $variation_id = $variation_arr['variation_id'];
      } else {
        $variation_id = wp_insert_post($variation_post);
      }
      $variation = new WC_Product_Variation($variation_id);

      $variation->set_regular_price($attributes['regular_price']);
      $variation->set_sku(!empty($attributes['sku']) ? $attributes['sku'] : '');
      $variation->save();

      if (!term_exists($term_name, $taxonomy)) {
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

    public function text_contains($needle, $haystack, $searchtype = '')
    {
      $needle = preg_quote($needle);
      switch ($searchtype) {
        case 'raw':
          preg_match('/' . wp_unslash($needle) . '/i', $haystack, $matches);
          break;
        case 'like':
          preg_match('/' . wp_unslash($needle) . '/i', $haystack, $matches);
          break;
        default:
          preg_match('/(?:^|\b)' . $needle . '(?!\w)/i', $haystack, $matches);
      }

      if (!empty($matches[0])) {
        return true;
      }
      return false;
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

      $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['aktion']) ? WC_COMMON_TAXONOMIES['aktion'] : '', 'product_cat');

      if ($term) {
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
      $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['rent']) ? WC_COMMON_TAXONOMIES['rent'] : '', 'product_cat');

      if ($term) {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }

      /**
       * Handle products tags
       */
      $terms = [];
      $term_names = isset(WC_TERMS['rent']) ? WC_TERMS['rent'] : array();
      foreach ($term_names as $term_name) {
        $term = get_term_by('name', $term_name, 'product_tag');
        wbp_th()->set_product_term($product, $term->term_id, 'tag', true);
      }

      /**
       * Handle Mietmaschinen Variations
       */
      $product_id = $product->get_ID();
      if ('variable' !== $product->get_type()) {
        $product = new WC_Product_Variable($product);
        $product->save();
        wp_set_object_terms($product_id, 'variable', 'product_type');
      }

      $attr_name = WC_CUSTOM_PRODUCT_ATTRIBUTES['rent'];

      // Check if the Term name exist and if not create it
      $attributes = self::get_mietdauer_attributes($attr_name, (int) $price);
      $terms = $attributes['attributes'][$attr_name];
      foreach ($terms as $key => $term) {
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

      $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['aktionswochen']) ? WC_COMMON_TAXONOMIES['aktionswochen'] : '', 'product_cat');

      if ($term) {
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
      if ($term_id) {
        wbp_th()->set_product_term($product, $term_id, 'label', true);
      }
      return $product;
    }

    public function get_the_terms($terms, $post_ID, $taxonomy)
    {
      if (is_wp_error($terms)) {
        return array();
      }
      return $terms;
    }

    public function label_filter($terms, $post_ID, $taxonomy)
    {
      if (!is_wp_error($terms) && 'product_label' === $taxonomy) {
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

      foreach ($exclusive_labels as $group) {
        $term_names = array_map('strtolower', wp_list_pluck($terms, 'name'));
        $intersection = array_intersect($term_names, $group);
        if (count($intersection) > 1) {
          $exclusive_term = get_term_by('name', $group[0], 'product_label');
          $diff_term_names = array_diff($term_names, $group);
          $diff_terms = array_map(function ($name) {
            return get_term_by('name', $name, 'product_label');
          }, $diff_term_names);
          $terms = array_merge(array($exclusive_term), $diff_terms);
        }
      }
      return $terms;
    }

    public function get_featured_products()
    {
      $tax_query[] = array(
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'featured',
        'operator' => 'IN', // or 'NOT IN' to exclude feature products
      );

      $query = new WP_Query(array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
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
      try {
        $product->set_sku($ad->id);
        $product->save();
      } catch (WC_Data_Exception $e) {
        return new WP_Error($e->getErrorCode(), $e->getMessage(), array_merge($e->getErrorData(), array('product' => $product)));
      }
      $post_ID = (int) $product->get_id();
      update_post_meta($post_ID, 'kleinanzeigen_id', $ad->id);
      update_post_meta($post_ID, 'kleinanzeigen_url', $this->get_kleinanzeigen_url($ad->url));
      update_post_meta($post_ID, 'kleinanzeigen_search_url', $this->get_kleinanzeigen_search_url($ad->id));
      update_post_meta($post_ID, 'kleinanzeigen_record', html_entity_decode(json_encode($ad, JSON_UNESCAPED_UNICODE)));
      return $product;
    }

    public function disable_sku_url($id)
    {
      delete_post_meta($id, 'kleinanzeigen_url');
      delete_post_meta($id, 'kleinanzeigen_search_url');
    }

    public function disable_sku(WC_Product &$product)
    {

      $product->set_sku('');
      $product->save();

      $post_ID = (int) $product->get_id();
      delete_post_meta($post_ID, 'kleinanzeigen_id');
      $deleted = delete_post_meta($post_ID, 'kleinanzeigen_url');
      delete_post_meta($post_ID, 'kleinanzeigen_search_url');

      return $product;
    }

    function delete_product($id, $force = false)
    {
      $product = wc_get_product($id);

      if (empty($product))
        return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

      // If we're forcing, then delete permanently.
      if ($force) {
        if ($product->is_type('variable')) {
          foreach ($product->get_children() as $child_id) {
            $child = wc_get_product($child_id);
            $child->delete(true);
          }
        } elseif ($product->is_type('grouped')) {
          foreach ($product->get_children() as $child_id) {
            $child = wc_get_product($child_id);
            $child->set_parent_id(0);
            $child->save();
          }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
      } else {
        $product->delete();
        $result = 'trash' === $product->get_status();
      }

      if (!$result) {
        return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
      }

      // Delete parent product transients.
      if ($parent_id = wp_get_post_parent_id($id)) {
        wc_delete_product_transients($parent_id);
      }
      return true;
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

    public function set_product_data(WC_Product &$product, $record, $content)
    {
      $title = $record->title;
      $price = Utils::extract_kleinanzeigen_price($record->price);
      $excerpt = $record->description;
      $tags = !empty($record->tags) ? $record->tags : [];
      $url = $record->url;
      $searchable_content = $title . ' ' . $excerpt;
      $product_id = $product->get_id();

      $product->set_regular_price($price);
      $product->save();

      $parts = array(
        'aktionspreis' => array(
          'Aktionspreis',
          'match_type' => 'like',
          'fn' => 'sale',
        ),
        'allrad'              => array('Allrad', 'match_type' => 'like'),
        'vorführ'             => array('Vorführmaschine', 'match_type' => 'like'),
        'topzustand'          => 'Top',
        'top zustand'         => 'Top',
        'guter zustand'       => 'Guter Zustand',
        'topausstattung'      => 'Top',
        'top ausstattung'     => 'Top',
        'aktion'              => array('Aktion', 'fn' => array('aktion', 'default')),
        'aktionswochen'       => array('Aktionswochen', 'fn' => array('aktionswochen', 'default')),
        'aktionsmodell'       => 'Aktion',
        'klima'               => 'Klima',
        'am lager'            => 'Sofort lieferbar',
        'sofort verfügbar'    => 'Sofort lieferbar',
        'sofort lieferbar'    => 'Sofort lieferbar',
        'lagermaschine'       => 'Sofort lieferbar',
        'leicht gebraucht'    => 'Leicht Gebraucht',
        'limited edition'     => 'Limited Edition',
        'lim. edition'        => 'Limited Edition',
        'mietmaschine'        => array('Mieten', 'fn' => array('rent', 'default')),
        'neu'                 => 'Neu',
        'neumaschine'         => 'Neu',
        'neufahrzeug'         => 'Neu',
        'neues modell'        => 'Neues Modell',
        'top modell'          => 'Top Modell',
        'von privat'          => 'Von Privat',
        'im kundenauftrag'    => 'Von Privat',
        'privatauftrag'       => 'Von Privat',
        'neuwertig'           => array('Neuwertig', 'match_type' => 'like'),
      );

      // Handle contents
      foreach ($parts as $key => $val) {

        if ($this->text_contains($key, $searchable_content, isset($val['match_type']) ? $val['match_type'] : null)) {

          $fns = isset($val['fn']) ? $val['fn'] : 'default';
          $fns = !is_array($fns) ? array($fns) : $fns;

          foreach ($fns as $fn) {
            if (is_callable(array($this, 'handle_product_contents_' . $fn), false, $callable_name)) {

              if (!is_array($val)) {
                $term_name = $val;
              } elseif (isset($val[0])) {
                $term_name = $val[0];
              }

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

      foreach ($brands as $brand) {
        $exists = false;
        if ($this->text_contains('(?:Motorenhersteller:?\s*(' . $brand->name . '))', $content, 'raw')) {
          $exists = true;
        } elseif ($this->text_contains(esc_html($brand->name), esc_html($searchable_content))) {
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

      return $product;
    }

    public function ajax_poll()
    {
      die(json_encode(array()));
    }

    public function ajax_cron()
    {
      $job = isset($_POST['job']) ? json_decode(wp_unslash($_POST['job'])) : null;

      $job_results = wbp_db()->get_jobs();

      $get_jobs = function () {
        $cron_list = _get_cron_array();
        $jobs = array_filter($cron_list, function ($cron) {
          return 0 === strpos(key($cron), 'kleinanzeigen');
        });
        return array_map(function ($job, $key) {
          $timestamp = $key;
          $slug = key($job);
          $uid = key($job[$slug]);
          return array(
            'slug'      => $slug,
            'timestamp' => $timestamp * 1000,
            'schedule'  => $job[$slug][$uid]['schedule'],
            'interval'  => $job[$slug][$uid]['interval'],
          );
        }, $jobs, array_keys($jobs));
      };
      $jobs = $get_jobs();
      $data = array(
        'jobs'        => $jobs,
        'jobResults'  => $job_results
      );
      die(json_encode(compact('data')));
    }

    public function return_false()
    {
      return false;
    }

    public static function get_instance($file = null): Kleinanzeigen_Functions
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance) {
        self::$instance = new self;
      }
      return self::$instance;
    }

    public function dropdown_invalid_ads($selected)
    {
      $r = '';

      $actions = array(
        'keep' => __('Keep', 'kleinanzeigen'),
        'deactivate' => __('Deactivate', 'kleinanzeigen'),
        'delete' => __('Delete', 'kleinanzeigen'),
      );

      $r .= "\n\t<option value=0>" . __('No action', 'kleinanzeigen') . "</option>";
      foreach ($actions as $action => $name) {
        // Preselect specified action.
        if ($selected === $action) {
          $r .= "\n\t<option selected='selected' value='" . esc_attr($action) . "'>$name</option>";
        } else {
          $r .= "\n\t<option value='" . esc_attr($action) . "'>$name</option>";
        }
      }

      return $r;
    }

    public function dropdown_crawl_interval($selected, $schedules)
    {
      $r = '';

      foreach ($schedules as $action => $name) {
        // Preselect specified action.
        if ($selected === $action) {
          $r .= "\n\t<option selected='selected' value='" . esc_attr($action) . "'>$name[display]</option>";
        } else {
          $r .= "\n\t<option value='" . esc_attr($action) . "'>$name[display]</option>";
        }
      }

      return $r;
    }

    public function sendMail($post_ID, $record)
    {
      add_action('kleinanzeigen_email_header', array($this, 'email_header'));
      add_action('kleinanzeigen_email_footer', array($this, 'email_footer'));
      add_filter('woocommerce_email_footer_text', array($this, 'replace_placeholders'));

      $author_email       = wbp_ka()->get_plugin_author()->email;
      $mail_dev_only      = get_option('kleinanzeigen_send_cc_mail_on_new_ad', '');

      $additional_content = '';
      $to_email           = get_bloginfo('admin_email');
      $blogname           = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
      $edit_link          = admin_url('post.php?action=edit&post=' . $post_ID);
      $permalink          = $permalink = get_permalink($post_ID);
      $previewlink        = get_preview_post_link($post_ID);
      $plugin_name        = wbp_ka()->get_plugin_name();
      $plugin_link        = admin_url("admin.php?page={$plugin_name}");
      $product_title      = $record->title;
      $post_status        = get_post_status($post_ID);
      $thumbnail          = get_the_post_thumbnail_url($post_ID);
      $kleinanzeigen_url  = $this->get_kleinanzeigen_url($record->url);
      $email_heading      = __('New product online', 'kleinanzeigen');
      $headers = array(
        'content-type: text/html',
        "Bcc: {$author_email}"
      );
      if (IS_SUBDOMAIN_DEV) {
        $headers[] = "Cc:  {$mail_dev_only}";
      }

      $email_content = wbp_ka()->include_template('emails/new-product.php', true, compact('product_title', 'post_status', 'edit_link', 'permalink', 'previewlink', 'plugin_link', 'thumbnail', 'blogname', 'email_heading', 'kleinanzeigen_url', 'additional_content'));
      $email_content = $this->style_inline($email_content);

      wp_mail($to_email, $email_heading, $email_content, $headers);
    }

    /**
     * Get the email header.
     *
     * @param mixed $email_heading Heading for the email.
     */
    public function email_header($email_heading)
    {
      wbp_ka()->include_template('emails/email-header.php', false, compact('email_heading'));
    }

    /**
     * Get the email footer.
     */
    public function email_footer()
    {
      wbp_ka()->include_template('emails/email-footer.php');
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
    public function style_inline($content)
    {

      require_once wbp_ka()->plugin_path('vendor/autoload.php');

      $css_inliner_class = CssInliner::class;

      if (class_exists($css_inliner_class)) {
        try {
          $css = wbp_ka()->include_template('emails/email-styles.php', true);
          $css_inliner = CssInliner::fromHtml($content)->inlineCss($css);

          do_action('woocommerce_emogrifier', $css_inliner, $this);

          $dom_document = $css_inliner->getDomDocument();

          Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromDomDocument($dom_document)->removeElementsWithDisplayNone();
          $content = Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter::fromDomDocument($dom_document)
            ->convertCssToVisualAttributes()
            ->render();
        } catch (Exception $e) {
        }
      }

      return $content;
    }
  }
}

if (!function_exists('wbp_fn')) {

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
