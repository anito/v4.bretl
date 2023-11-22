<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen` doesn't exists yet.
if (!class_exists('Kleinanzeigen')) {

  class Kleinanzeigen extends Webpremiere_Kleinanzeigen
  {
    private static $instance = null;
    private $screen_id = '';

    public function __construct()
    {
      $this->register();
    }

    public static function get_instance()
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance) {
        self::$instance = new self;
      }
      return self::$instance;
    }

    public function register()
    {
      add_action('admin_menu', array($this, 'add_admin_menu_page'), 10);
      add_filter('admin_init', array($this, 'load_files'), 10);
      add_filter('admin_init', array($this, 'table_ajax_handler'), 11);
      add_action('admin_init', array($this, 'register_scripts'), 10);

      add_action('current_screen', array($this, 'on_current_screen'));
      add_filter('get_the_terms', array($this, 'get_the_terms'), 10, 3);
      add_filter('get_the_terms', array($this, 'label_filter'), 20, 3);

      add_action("woocommerce_before_product_object_save", array($this, "product_before_save"), 99, 2);

      add_action("save_post", array($this, "save_post"), 99, 3);
      add_action("save_post", array($this, "quick_edit_product_save"), 10, 3);
      add_filter("update_post_metadata", array($this, 'prevent_metadata_update'), 10, 4);

      add_action("wp_insert_post_data", array($this, "publish_guard"), 99, 3);
      add_filter('wp_insert_post_empty_content', array($this, 'return_false'));
    }

    // Woo internal product save
    public function product_before_save($product)
    {
      wbp_th()->process_featured($product);
    }

    public function include_kleinanzeigen_template($path, $return_instead_of_echo = false, $extract_these = array())
    {
      if ($return_instead_of_echo) ob_start();

      $template_file = $this->get_template($path);

      if (!file_exists($template_file)) {
        error_log("Template not found: " . $template_file);
        echo __('Error:', 'wbp-kleinanzeigen') . ' ' . __('Template not found', 'wbp-kleinanzeigen') . " (" . $path . ")";
      } else {
        extract($extract_these);
        include $template_file;
      }

      if ($return_instead_of_echo) return ob_get_clean();
    }

    public function load_files()
    {
      require_once $this->plugin_path('/includes/class-kleinanzeigen-table-ajax.php');
      require_once $this->plugin_path('/includes/class-kleinanzeigen-table-ajax-modal.php');

      require_once $this->plugin_path('/includes/class-kleinanzeigen-table-ajax-action-handler.php');
    }

    public function on_current_screen($screen)
    {
      $this->screen_id = $screen->id;

      switch ($this->screen_id) {
        case 'edit-product':
          require_once $this->plugin_path('/includes/class-extended-wc-admin-list-table-products.php');

          new Extended_WC_Admin_List_Table_Products();

          break;
        case 'toplevel_page_wbp-kleinanzeigen':
          require_once $this->plugin_path('/includes/class-admin-kleinanzeigen-list-table.php');
          require_once $this->plugin_path('/includes/class-admin-kleinanzeigen-list-table-tasks.php');

          // setcookie('ka-paged', 1);
          break;
      }
    }

    public function get_screen()
    {
      return $this->screen_id;
    }

    public function table_ajax_handler()
    {
      new Kleinanzeigen_Ajax_Table();
      new Kleinanzeigen_Ajax_Table_Modal();
      new Kleinanzeigen_Ajax_Action_Handler();
    }

    public function register_scripts()
    {
      add_action('admin_enqueue_scripts', array($this, 'add_admin_enqueue_styles'));
      add_action('admin_enqueue_scripts', array($this, 'add_admin_ajax_scripts'));
    }

    public function add_admin_enqueue_styles()
    {
      if (in_array($this->screen_id, wc_get_screen_ids())) {
        $screen_id = 'woocommerce';
      } else {
        $screen_id = preg_replace('/toplevel_page_/', '', $this->screen_id);
      }
      switch ($screen_id) {
        case 'woocommerce':
        case 'wbp-kleinanzeigen':
          wp_enqueue_style("{$screen_id}-css", $this->assets_url() . "css/style-admin-{$screen_id}.css", array(), $this->get_version());
          break;
      }
    }

    public function add_admin_ajax_scripts()
    {
      wp_enqueue_script("kleinanzeigen-ajax", $this->assets_url() . "js/ajax.js", array(), $this->get_version());

      wp_localize_script('kleinanzeigen-ajax', 'ajax_object', array(
        'admin_ajax' => admin_url('admin-ajax.php'),
        'home_url' => home_url(),
        'screen' => $this->screen_id,
        'edit_link' => admin_url('post.php?action=edit&post='),
        'nonce' => wp_create_nonce()
      ));
    }

    public function get_all_ads()
    {
      // Get first set of data to discover page count
      $data = Utils::error_check(Utils::get_json_data(), 'error-message.php');
      $ads = $data->ads;
      $categories = $data->categoriesSearchData;
      $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
      $num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);

      // Get rest of pages
      for ($paged = 2; $paged <= $num_pages; $paged++) {
        $page_data = Utils::get_json_data(array('paged' => $paged));
        $page_data  = Utils::error_check($page_data, 'error-message.php');
        $ads = array_merge($ads, $page_data->ads);
      }
      return $ads;
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

    public function get_task_list_items($products, $ads, $task_type)
    {
      $ad_ids = wp_list_pluck($ads, 'id');
      $records = wp_list_pluck((array) $ads, 'id');
      $records = array_flip($records);
      $items = [];
      if ($task_type === 'no-sku') {
        $products_no_sku = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'NOT EXISTS'));
        $record = null;
        foreach ($products_no_sku as $product) {
          $items[] = compact('product', 'task_type', 'record');
        }
        return $items;
      }
      if ($task_type === 'has-sku') {
        $record = null;
        $products_has_sku = wc_get_products(array('status' => 'publish', 'limit' => -1, 'sku_compare' => 'EXISTS'));
        foreach ($products_has_sku as $product) {
          $sku = (int) $product->get_sku();
          $record = isset($records[$sku]) ? $ads[$records[$sku]] : null;
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
          $record = isset($records[$sku]) ? $ads[$records[$sku]] : null;
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
        $title = $product->get_title();
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
                $record = $ads[$records[$sku]];
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

    public function add_admin_menu_page()
    {
      $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjkuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9InV1aWQtY2Y1MDkyNzItMDBlMi00YjJlLWJmZWUtNTZlZjA5ZDhhMDRmIgoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTE1IDEzMSIKCSBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMTUgMTMxOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6I0E3QUFBRDt9Cjwvc3R5bGU+CjxnIGlkPSJ1dWlkLWRhZDJmZmE0LTgzNzctNDg3Ni04OTY0LWQ3ODEyYWE0NGU5MSI+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODAuOCwxMjFjLTE0LjksMC0yMi4yLTEwLjQtMjMuNi0xMi41Yy00LjQsNC4zLTExLDEyLjUtMjMsMTIuNWMtMTMuOCwwLTI1LjQtMTAuNC0yNS40LTI3LjNWMzcuMwoJCUM4LjgsMjAuNCwyMC40LDEwLDM0LjIsMTBzMjUuNCwxMSwyNS40LDI3LjFjMi43LTEsNS41LTEuNCw4LjUtMS40YzE0LjIsMCwyNS40LDExLjYsMjUuNCwyNS42YzAsMy45LTAuNyw3LjQtMi40LDEwLjcKCQljOSw0LDE1LjEsMTMuMSwxNS4xLDIzLjRDMTA2LjIsMTA5LjUsOTQuOCwxMjEsODAuOCwxMjFMODAuOCwxMjF6IE02My4zLDEwMi4zYzMuNyw2LjQsOS44LDEwLjIsMTcuNSwxMC4yCgkJYzkuMywwLDE2LjktNy43LDE2LjktMTcuMWMwLTcuNC00LjgtMTMuOS0xMS42LTE2LjJMNjMuMywxMDIuM0M2My4zLDEwMi4zLDYzLjMsMTAyLjMsNjMuMywxMDIuM3ogTTM0LjIsMTguNQoJCWMtOC40LDAtMTYuOSw1LjgtMTYuOSwxOC44djU2LjNjMCwxMyw4LjUsMTguOCwxNi45LDE4LjhjNi43LDAsMTAuNC0zLjQsMTYuNC05LjRsMi42LTIuN2MtMS40LTQuMS0yLjEtOC42LTIuMS0xMy41VjM3LjMKCQlDNTEuMSwyNC40LDQyLjYsMTguNSwzNC4yLDE4LjVMMzQuMiwxOC41TDM0LjIsMTguNXogTTU5LjYsNDYuNHY0MC40YzAsMi4zLDAuMiw0LjUsMC42LDYuNWwxOC40LTE4LjZjNS4zLTUuNCw2LjQtOS4zLDYuNC0xMy42CgkJYzAtOS4xLTcuMi0xNy4xLTE2LjktMTcuMUM2NSw0NC4yLDYyLjIsNDQuOSw1OS42LDQ2LjRMNTkuNiw0Ni40TDU5LjYsNDYuNHoiLz4KPC9nPgo8L3N2Zz4K';

      add_menu_page(__('Kleinanzeigen', 'wbp-kleinanzeigen'), __('Kleinanzeigen', 'wbp-kleinanzeigen'), 'edit_posts', 'wbp-kleinanzeigen', array($this, 'display_kleinanzeigen_list'), $icon_svg, 10);

      $submenu_items = $this->kleinanzeigen_get_submenu_items();
      foreach ($submenu_items as $key => $item) {
        add_submenu_page('wbp-kleinanzeigen', $item['page_title'], $item['menu_title'], 'edit_posts', $item['menu_slug'], array($this, $item['callback']), $item['order']);
      }

      if (!empty($_GET['page']) && 'wbp-kleinanzeigen' == $_GET['page']) {

        if (!defined('KLEINANZEIGEN_TEMPLATE_PATH')) {
          // define('KLEINANZEIGEN_TEMPLATE_PATH', plugins_url() . '/templates/kleinanzeigen/');
        }
      }
      $this->register_admin_content();
    }

    public function kleinanzeigen_get_submenu_items()
    {
      $sub_menu_items = array(
        array(
          'page_title' => esc_html__('Overview', 'wbp-kleinanzeigen'),
          'menu_title' => esc_html__('Overview', 'wbp-kleinanzeigen'),
          'menu_slug' => 'wbp-kleinanzeigen',
          'callback' => 'display_kleinanzeigen_list',
          'icon' => 'admin-settings',
          'create_submenu' => true,
          'order' => 0,
        ),
        array(
          'page_title' => esc_html__('Settings', 'wbp-kleinanzeigen'),
          'menu_title' => esc_html__('Settings', 'wbp-kleinanzeigen'),
          'menu_slug' => 'wbp-kleinanzeigen-settings',
          'callback' => 'display_kleinanzeigen_settings',
          'icon' => 'admin-settings',
          'create_submenu' => true,
          'order' => 1,
        )
      );

      return $sub_menu_items;
    }

    public function display_kleinanzeigen_list()
    {

      echo '<div id="kleinanzeigen-table-list-wrap">';

      do_action('wbp_kleinanzeigen_admin_header', array('title' => __('Overview', 'wbp-kleinanzeigen')));

      echo '<div id="pages-results"></div>';

      $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
      $this->kleinanzeigen_display_admin_page($page);

      do_action('wbp_kleinanzeigen_admin_before_closing_wrap');

      // closes main plugin wrapper div
      echo '</div><!-- END #kleinanzeigen-table-list-wrap -->';
    }

    public function display_kleinanzeigen_settings()
    {

      echo '<div id="kleinanzeigen-settings-wrap">';

      do_action('wbp_kleinanzeigen_admin_header', array('title' => __('Settings', 'wbp-kleinanzeigen')));

      echo '<div id="page-settings"></div>';

      $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
      $this->kleinanzeigen_display_admin_page($page);

      do_action('wbp_kleinanzeigen_admin_before_closing_wrap');

      // closes main plugin wrapper div
      echo '</div><!-- END #kleinanzeigen-settings-wrap -->';
    }

    public function register_admin_content()
    {
      add_action('wbp_kleinanzeigen_admin_header', array($this, 'output_page_header'), 20);

      add_action('wbp_kleinanzeigen_admin_before_closing_wrap', array($this, 'output_before_closing_wrap'), 20);
      add_action('wbp_kleinanzeigen_admin_after_page_wbp-kleinanzeigen', array($this, 'output_after_page'), 20);
      add_action('wbp_kleinanzeigen_admin_after_page_wbp-kleinanzeigen-settings', array($this, 'output_after_page'), 20);

      add_action('wbp_kleinanzeigen_admin_page_wbp-kleinanzeigen', array($this, 'output_dashboard_tab'), 20);
      add_action('wbp_kleinanzeigen_admin_page_wbp-kleinanzeigen-settings', array($this, 'output_settings_tab'), 20);

      do_action('page_rendered');
    }

    public function kleinanzeigen_display_admin_page($slug)
    {

      $active_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

      echo '<div class="wbp-kleinanzeigen-page' . ($active_page == $slug ? ' active' : '') . '" data-whichpage="' . $slug . '">';

      echo '<div class="wbp-kleinanzeigen-main">';


      // if no tabs defined for $page then use $slug as $active_tab for load template, doing related actions e t.c.
      $active_tab = $slug;

      do_action("wbp_kleinanzeigen_admin_page_{$slug}", $slug);

      echo '</div><!-- END .wbp-kleinanzeigen-main -->';

      do_action("wbp_kleinanzeigen_admin_after_page_{$slug}", $slug);

      echo '</div><!-- END .wbp-kleinanzeigen-page -->';
    }

    public function output_dashboard_tab()
    {
      $this->include_kleinanzeigen_template('dashboard/dashboard.php', false, array());
    }

    public function output_settings_tab()
    {
      $this->include_kleinanzeigen_template('dashboard/settings.php', false, array());
    }

    public function output_page_header($args)
    {
      $this->include_kleinanzeigen_template('page-header.php', false, $args);
    }

    public function output_before_closing_wrap()
    {
      echo '<div class="sub-content"></div>';
    }

    public function output_after_page()
    {
      echo '<div class="after-page"></div>';
    }

    public function publish_guard($post)
    {
      if ('product' !== $post['post_type']) return $post;

      // Check for valid product title when publishing
      require_once $this->plugin_path('/includes/class-utils.php');
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

    public function find_kleinanzeige(int $id): stdClass | null
    {
      $paged = 1;
      $num_pages = 1;
      while ($paged <= $num_pages) {
        $data = Utils::get_json_data(array('paged' => $paged));
        if (1 === $num_pages) {
          $categories = $data->categoriesSearchData;
          $total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
          $num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);
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
      $title = $product->get_title();
      $content = $product->get_description();
      $sku_errors = [];

      if (!empty($kleinanzeigen_id)) {
        $ad = $this->find_kleinanzeige($kleinanzeigen_id);
      } else {
        $ad = null;
      }

      $recover = function ($post_ID) {
        $json = get_post_meta($post_ID, 'kleinanzeigen_record', true);
        return (object) json_decode($json, true);
      };
      $comment = function ($pos_key = 1, $content = '') {
        $pos = array_combine(range(0, 2), array('', '-start', '-end'));
        $name = isset($pos[$pos_key]) ? $pos[$pos_key] : $pos[0];
        return '<!--COMMENT' . strtoupper($name) . '-->' . $content;
      };

      $is_recover = "true" === get_post_meta($post_ID, 'kleinanzeigen_recover', true);
      if ($is_recover) {
        $ad = $recover($post_ID);
        $_POST['kleinanzeigen_id'] = $ad->id;
      }

      if ($ad) {
        $ad_title = $ad->title;
        $title = $ad_title;

        $sku = $is_recover ? (string) $ad->id : $product->get_sku();
        $sku_needs_update = $is_recover || $sku !== $kleinanzeigen_id;
        if ($sku_needs_update) {
          // Throws error if sku already exists
          $success = $this->enable_sku($product, $ad);
          if (is_wp_error($success)) {
            $error = new WP_Error(400, __('A Product with the same Ad ID already exists. Delete this draft or enter a different Ad ID.', 'wbp-kleinanzeigen'));
            $sku_errors[] = $error;
          }
        }
      } else {
        $this->disable_sku($product);
      }

      // Check for duplicate titles

      global $wpdb;

      $prepare = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'product' AND post_status != '%s' AND post_status != '%s' AND post_title != '' AND post_title = %s", 'inherit', 'trash', $title);
      $results = $wpdb->get_results($prepare);
      if (count($results) >= 1) {
        foreach ($results as $result) {
          if ($result->ID != $post_ID) {
            $sku_errors[] = new WP_Error(400, __('A product with the same title already exists. Delete this draft or enter a different title.', 'wbp-kleinanzeigen'));
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
        'post_status' => !empty($sku_errors) ? 'draft' : (isset($_POST['post_status']) ? $_POST['post_status'] : ''),
        'post_content' => $content,
        'post_excerpt' => $product->get_short_description(),
        'post_title' => $title
      ]);
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
      $product = $args['product'];
      $price = $args['price'];

      $this->set_pseudo_sale_price($product, $price);
      return $this->handle_product_label($args['term_name'], $product);;
    }

    public function handle_product_contents_aktion($args)
    {
      $product = $args['product'];

      $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['aktion']) ? WC_COMMON_TAXONOMIES['aktion'] : '', 'product_cat');

      if ($term) {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }
      return $product;
    }

    public function handle_product_contents_rent($args)
    {
      extract($args);

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
        wp_set_object_terms($product_id, 'variable', 'product_type');
      }

      $attr_name = WC_CUSTOM_PRODUCT_ATTRIBUTES['rent'];

      // Check if the Term name exist and if not create it
      $attributes = $this->get_mietdauer_attributes($attr_name, (int) $price);
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
      $product = $args['product'];

      $term = get_term_by('name', isset(WC_COMMON_TAXONOMIES['aktionswochen']) ? WC_COMMON_TAXONOMIES['aktionswochen'] : '', 'product_cat');

      if ($term) {
        wbp_th()->set_product_term($product, $term->term_id, 'cat', true);
      }
      return $product;
    }

    public function handle_product_contents_default($args)
    {
      $product = $args['product'];
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
      if (!defined('MUTUALLY_EXCLUSIVE_LABEL_NAMES')) {
        define(
          'MUTUALLY_EXCLUSIVE_LABEL_NAMES',
          array(
            array(
              'neuwertig',
              'neu'
            ),
            array(
              'aktionswochen',
              'aktion'
            ),
          )
        );
      }

      foreach (MUTUALLY_EXCLUSIVE_LABEL_NAMES as $group) {
        $term_slugs = wp_list_pluck($terms, 'slug');
        $intersection = array_intersect($term_slugs, $group);
        if (count($intersection) > 1) {
          $exclusive_term = get_term_by('slug', $group[0], 'product_label');
          $diff_term_slugs = array_diff($term_slugs, $group);
          $diff_terms = array_map(function ($slug) {
            return get_term_by('slug', $slug, 'product_label');
          }, $diff_term_slugs);
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

    public function get_mietdauer_attributes($attr_name, $price)
    {
      return array(
        'attributes' => array(
          $attr_name => array(
            array(
              'name' => __('1 Tag', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price)
            ),
            array(
              'name' => __('2 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 2)
            ),
            array(
              'name' => __('3 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 3)
            ),
            array(
              'name' => __('4 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 4)
            ),
            array(
              'name' => __('5 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 5)
            ),
            array(
              'name' => __('6 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 6)
            ),
            array(
              'name' => __('7 Tage', 'wbp-kleinanzeigen'),
              'attributes' => array('regular_price' => (int) $price * 7)
            ),
          )
        ),
        'sku' => '',
      );
    }

    public function enable_sku($product, $ad)
    {
      try {
        $product->set_sku($ad->id);
        $product->save();
      } catch (WC_Data_Exception $e) {
        return new WP_Error($e->getErrorCode(), $e->getMessage(), array_merge($e->getErrorData(), array('product' => $product)));
      }
      $post_ID = $product->get_id();
      update_post_meta((int) $post_ID, 'kleinanzeigen_id', $ad->id);
      update_post_meta((int) $post_ID, 'kleinanzeigen_url', $this->get_kleinanzeigen_url($ad->url));
      update_post_meta((int) $post_ID, 'kleinanzeigen_search_url', $this->get_kleinanzeigen_search_url($ad->id));
      update_post_meta((int) $post_ID, 'kleinanzeigen_record', html_entity_decode(json_encode($ad, JSON_UNESCAPED_UNICODE)));
      return $product;
    }

    public function disable_sku(WC_Product $product)
    {
      $product->set_sku('');
      $product->save();

      $post_ID = $product->get_id();
      delete_post_meta($post_ID, 'kleinanzeigen_id');
      delete_post_meta($post_ID, 'kleinanzeigen_url');
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
    
    public function return_false()
    {
      return false;
    }


  }
}

if (!function_exists('wbp_ka')) {

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return object
   */
  function wbp_ka(): Kleinanzeigen
  {
    return Kleinanzeigen::get_instance();
  }
}

wbp_ka();
