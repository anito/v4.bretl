<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/admin
 * @author     Ben Shadle <benshadle@gmail.com>
 */
class Kleinanzeigen_Admin extends Kleinanzeigen
{

  private static $instance;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct()
  {

    add_action('init', array($this, 'loadFiles'), -999);
    add_action('admin_menu', array($this, 'addPluginAdminMenu'), 9);
    add_action('admin_init', array($this, 'registerAndBuildFields'));

    // Cron jobs
    add_action('kleinanzeigen_sync_price', array($this, 'job_sync_price'));
    add_action('kleinanzeigen_invalid_ad_action', array($this, 'job_invalid_ad_action'));
    add_action('kleinanzeigen_remove_url_invalid_sku', array($this, 'job_remove_url_invalid_sku'));
    add_action('kleinanzeigen_create_new_products', array($this, 'job_create_product'));
    add_filter('pre_update_option', array($this, 'pre_update_option'), 10, 3);
    add_action('init', array($this, 'register_jobs'));

    add_filter('cron_schedules', array($this, 'schedules'));
  }

  public function loadFiles()
  {
    require_once $this->plugin_path('includes/class-kleinanzeigen-database.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-functions.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-term-handler.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-list-table.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax-modal.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-register-wc-taxonomies.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-list-table-tasks.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-wc-admin-list-table-products.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax-action-handler.php');
  }

  /**
   * Register the stylesheets for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

    $screen_id = wbp_fn()->screen_id;
    if (in_array($screen_id, wc_get_screen_ids())) {
      $screen_id = 'woocommerce';
    } elseif (strpos($screen_id, self::$plugin_name) !== false) {
      $screen_id = self::$plugin_name;
    }
    switch ($screen_id) {
      case 'woocommerce':
      case self::$plugin_name:
        wp_enqueue_style(self::$plugin_name, plugin_dir_url(__FILE__) . "css/style-admin-{$screen_id}.css", array(), self::$version, 'all');
        break;
    }
  }

  /**
   * Register the JavaScript for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Kleinanzeigen_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Kleinanzeigen_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script(self::$plugin_name . '-ajax', plugin_dir_url(__FILE__) . 'js/ajax.js', array('jquery'), self::$version, false);
    wp_enqueue_script(self::$plugin_name . '-utils', plugin_dir_url(__FILE__) . 'js/utils.js', array('jquery'), self::$version, false);

    wp_localize_script(self::$plugin_name . '-ajax', 'KleinanzeigenAjax', array(
      'admin_ajax'  => admin_url('admin-ajax.php'),
      'plugin_name' => self::$plugin_name,
      'home_url'    => home_url(),
      'screen'      => wbp_fn()->screen_id,
      'edit_link'   => admin_url('post.php?action=edit&post='),
      'nonce'       => wp_create_nonce()
    ));
    wp_localize_script(self::$plugin_name . '-utils', 'KleinanzeigenUtils', array(
      'admin_ajax'  => admin_url('admin-ajax.php'),
      'screen'      => wbp_fn()->screen_id,
    ));
  }

  public function template_path($path)
  {
    return is_admin() ? trailingslashit('admin') : $path;
  }

  public function schedules($schedules = array())
  {
    $schedules = array_merge($schedules, array(
      'every_minute' => array(
        'interval' => 60,
        'display'  => __('Every minute', 'kleinanzeigen'),
      ),
      'two_minutes' => array(
        'interval' => 120,
        'display'  => __('Every 2 minutes', 'kleinanzeigen'),
      ),
      'three_minutes' => array(
        'interval' => 180,
        'display'  => __('Every 3 minutes', 'kleinanzeigen'),
      ),
      'fore_minutes' => array(
        'interval' => 240,
        'display'  => __('Every 4 minutes', 'kleinanzeigen'),
      ),
      'five_minutes' => array(
        'interval' => 5 * 60,
        'display'  => __('Every 5 minutes', 'kleinanzeigen'),
      ),
      'ten_minutes' => array(
        'interval' => 10 * 60,
        'display'  => __('Every 10 minutes', 'kleinanzeigen'),
      ),
      'daily' => array(
        'interval' => 24 * 60 * 60,
        'display'  => __('Every day', 'kleinanzeigen'),
      )
    ));
    return $schedules;
  }

  public function pre_update_option($value, $option, $old_value)
  {

    if ('kleinanzeigen_crawl_interval' === $option && $old_value !== $value) {

      wp_unschedule_hook('kleinanzeigen_remove_url_invalid_sku');
      wp_unschedule_hook('kleinanzeigen_sync_price');
      wp_unschedule_hook('kleinanzeigen_invalid_ad_action');
      wp_unschedule_hook('kleinanzeigen_create_new_products');
    }

    return $value;
  }

  public function register_jobs()
  {

    $interval = get_option('kleinanzeigen_crawl_interval');

    // Remove ophaned links from product
    if ("1" === get_option('kleinanzeigen_schedule_remove_orphaned_links')) {
      if (!wp_next_scheduled('kleinanzeigen_remove_url_invalid_sku')) {
        wp_schedule_event(time(), $interval, 'kleinanzeigen_remove_url_invalid_sku');
      }
    } else {
      wp_unschedule_hook('kleinanzeigen_remove_url_invalid_sku');
    }

    // Sync price
    if ("1" === get_option('kleinanzeigen_schedule_invalid_prices')) {
      if (!wp_next_scheduled('kleinanzeigen_sync_price')) {
        wp_schedule_event(time(), $interval, 'kleinanzeigen_sync_price');
      }
    } else {
      wp_unschedule_hook('kleinanzeigen_sync_price');
    }

    // Sync invalid ads
    if ("0" !== get_option('kleinanzeigen_schedule_invalid_ads', '0')) {
      if (!wp_next_scheduled('kleinanzeigen_invalid_ad_action')) {
        wp_schedule_event(time(), $interval, 'kleinanzeigen_invalid_ad_action');
      }
    } else {
      wp_unschedule_hook('kleinanzeigen_invalid_ad_action');
    }

    // Sync create new products
    if ("1" === get_option('kleinanzeigen_schedule_new_ads', '0')) {
      if (!wp_next_scheduled('kleinanzeigen_create_new_products')) {
        wp_schedule_event(time(), $interval, 'kleinanzeigen_create_new_products');
      }
    } else {
      wp_unschedule_hook('kleinanzeigen_create_new_products');
    }
  }

  public function job_remove_url_invalid_sku()
  {
    $items = wbp_fn()->build_tasks('invalid-ad')['items'];
    $product_ids = wp_list_pluck($items, 'product_id');

    $ids = array_filter($product_ids, function ($id) {
      return !empty(get_metadata('post', $id, 'kleinanzeigen_url'));
    });

    wbp_db()->insert_job(array(
      'slug'  => 'kleinanzeigen_remove_url_invalid_sku',
      'count' => count($ids)
    ));

    foreach ($ids as $id) {
      wbp_fn()->disable_sku_url($id);
    }
  }

  public function job_sync_price()
  {
    $items = wbp_fn()->build_tasks('invalid-price')['items'];

    write_log('invalid prices => ' . count($items));

    wbp_db()->insert_job(array(
      'slug'  => 'kleinanzeigen_sync_price',
      'count' => count($items)
    ));

    foreach ($items as $item) {
      $price = Utils::extract_kleinanzeigen_price($item['record']->price);
      wbp_fn()->fix_price($item['product_id'], $price);
    }
  }

  public function job_create_product()
  {
    $items = wbp_fn()->build_tasks('new-product')['items'];

    wbp_db()->insert_job(array(
      'slug'  => 'kleinanzeigen_create_new_products',
      'count' => count($items)
    ));

    foreach ($items as $item) {
      libxml_use_internal_errors(true);

      $record = (object) $item['record'];
      $remoteUrl = wbp_fn()->get_kleinanzeigen_search_url($record->id);
      
      $contents = file_get_contents($remoteUrl);

      $doc = new DOMDocument();
      $doc->loadHTML($contents);

      $post_ID = wbp_fn()->create_product($record, $doc);

      wbp_fn()->create_product_images($post_ID, $doc);

      wbp_fn()->sendMail($post_ID, $record);

      update_post_meta((int) $post_ID, 'kleinanzeigen_id', $record->id);
    }
  }

  public function job_invalid_ad_action()
  {
    $action = get_option('kleinanzeigen_schedule_invalid_ads');
    $items = wbp_fn()->build_tasks('invalid-ad')['items'];

    wbp_db()->insert_job(array(
      'slug'  => 'kleinanzeigen_invalid_ad_action',
      'count' => count($items)
    ));

    foreach ($items as $item) {
      $post_ID = $item['product_id'];

      unset($args);
      switch ($action) {
        case 'keep':
          $args = array();
          break;
        case 'deactivate':
          $args = array('post_status' => 'draft');
          break;
        case 'delete':
          $args = array('post_status' => 'trash');
          break;
        default:
      }

      if (isset($args)) {

        $postarr = array_merge(array(
          'ID' => $post_ID
        ), $args);

        wp_update_post($postarr);
        $product = wc_get_product($post_ID);

        if ("trash" === $product->get_status()) {
          wbp_fn()->delete_product($post_ID, true);
        }
      }
    }
  }

  public function addPluginAdminMenu()
  {
    $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjkuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9InV1aWQtY2Y1MDkyNzItMDBlMi00YjJlLWJmZWUtNTZlZjA5ZDhhMDRmIgoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTE1IDEzMSIKCSBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMTUgMTMxOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6I0E3QUFBRDt9Cjwvc3R5bGU+CjxnIGlkPSJ1dWlkLWRhZDJmZmE0LTgzNzctNDg3Ni04OTY0LWQ3ODEyYWE0NGU5MSI+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODAuOCwxMjFjLTE0LjksMC0yMi4yLTEwLjQtMjMuNi0xMi41Yy00LjQsNC4zLTExLDEyLjUtMjMsMTIuNWMtMTMuOCwwLTI1LjQtMTAuNC0yNS40LTI3LjNWMzcuMwoJCUM4LjgsMjAuNCwyMC40LDEwLDM0LjIsMTBzMjUuNCwxMSwyNS40LDI3LjFjMi43LTEsNS41LTEuNCw4LjUtMS40YzE0LjIsMCwyNS40LDExLjYsMjUuNCwyNS42YzAsMy45LTAuNyw3LjQtMi40LDEwLjcKCQljOSw0LDE1LjEsMTMuMSwxNS4xLDIzLjRDMTA2LjIsMTA5LjUsOTQuOCwxMjEsODAuOCwxMjFMODAuOCwxMjF6IE02My4zLDEwMi4zYzMuNyw2LjQsOS44LDEwLjIsMTcuNSwxMC4yCgkJYzkuMywwLDE2LjktNy43LDE2LjktMTcuMWMwLTcuNC00LjgtMTMuOS0xMS42LTE2LjJMNjMuMywxMDIuM0M2My4zLDEwMi4zLDYzLjMsMTAyLjMsNjMuMywxMDIuM3ogTTM0LjIsMTguNQoJCWMtOC40LDAtMTYuOSw1LjgtMTYuOSwxOC44djU2LjNjMCwxMyw4LjUsMTguOCwxNi45LDE4LjhjNi43LDAsMTAuNC0zLjQsMTYuNC05LjRsMi42LTIuN2MtMS40LTQuMS0yLjEtOC42LTIuMS0xMy41VjM3LjMKCQlDNTEuMSwyNC40LDQyLjYsMTguNSwzNC4yLDE4LjVMMzQuMiwxOC41TDM0LjIsMTguNXogTTU5LjYsNDYuNHY0MC40YzAsMi4zLDAuMiw0LjUsMC42LDYuNWwxOC40LTE4LjZjNS4zLTUuNCw2LjQtOS4zLDYuNC0xMy42CgkJYzAtOS4xLTcuMi0xNy4xLTE2LjktMTcuMUM2NSw0NC4yLDYyLjIsNDQuOSw1OS42LDQ2LjRMNTkuNiw0Ni40TDU5LjYsNDYuNHoiLz4KPC9nPgo8L3N2Zz4K';
    add_menu_page(self::$plugin_name, __('Kleinanzeigen', 'kleinanzeigen'), 'administrator', self::$plugin_name, array($this, 'displayPluginAdminDashboard'), $icon_svg, 10);
    add_submenu_page(self::$plugin_name, __('Kleinanzeigen Settings', 'kleinanzeigen'), __('Settings', 'kleinanzeigen'), 'administrator', self::$plugin_name . '-settings', array($this, 'displayPluginAdminSettings'));
  }

  public function displayPluginAdminDashboard()
  {
    $this->include_template(self::$plugin_name . '-page-header.php');
    $this->include_template('dashboard/' . self::$plugin_name . '-admin-display.php');
  }

  public function displayPluginAdminSettings()
  {
    // set this var to be used in the settings-display view
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

    if (isset($_GET['error_message'])) {
      add_action('admin_notices', array($this, 'kleinanzeigenSettingsMessages'));
      do_action('admin_notices', $_GET['error_message']);
    }
    if (isset($_GET['settings-updated'])) {
    }

    if (!Utils::get_json_data()) {
      add_settings_error('kleinanzeigen_account_name', 403, sprintf(__('The account %1$s could not be found', 'kleinanzeigen'), '<em class="">"' . get_option('kleinanzeigen_account_name') . '"</em>'), 'error');
    }

    $this->include_template(self::$plugin_name . '-page-header.php');
    $this->include_template(self::$plugin_name . '-admin-settings-display.php');
  }

  public function kleinanzeigenSettingsMessages($error_message)
  {
    switch ($error_message) {
      case '1':
        $message = __('There was an error adding this setting. Please try again. If this persists, shoot us an email.', 'kleinanzeigen');
        $err_code = esc_attr('kleinanzeigen_initial_zoomlevel');
        $setting_field = 'kleinanzeigen_initial_zoomlevel';
        break;
    }
    $type = 'error';
    add_settings_error(
      $setting_field,
      $err_code,
      $message,
      $type
    );
  }

  public function registerAndBuildFields()
  {
    $register = function ($id, $callback = '') {
      register_setting(
        'kleinanzeigen_account_settings',
        $id,
        array(
          'sanitize_callback' => $callback,
          'show_in_rest'      => true
        )
      );
    };

    /**
     * First, we add_settings_section. This is necessary since all future settings must belong to one.
     * Second, add_settings_field
     * Third, register_setting
     */
    add_settings_section(
      'kleinanzeigen_account_section', // ID used to identify this section and with which to register options
      __('Account', 'kleinanzeigen'), // Title
      array($this, 'kleinanzeigen_display_general_account'), // Callback
      'kleinanzeigen_account_settings' // Page on which to add this section of options
    );

    // Account name
    $args = array(
      'type'              => 'input',
      'subtype'           => 'text',
      'id'                => 'kleinanzeigen_account_name',
      'name'              => 'kleinanzeigen_account_name',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option'
    );
    add_settings_field(
      $args['id'],
      __('Account name', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_account_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Pro account
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_is_pro_account',
      'name'              => 'kleinanzeigen_is_pro_account',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option'
    );
    add_settings_field(
      $args['id'],
      __('Pro Account', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_account_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Ads per page
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'number',
      'id'                => 'kleinanzeigen_items_per_page',
      'name'              => 'kleinanzeigen_items_per_page',
      'required'          => 'true',
      'get_options_list'  => '',
      'disabled'          => true,
      'value_type'        => 'normal',
      'wp_data'           => 'option'
    );
    add_settings_field(
      $args['id'],
      __('Ads per page', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_account_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Section Scheduling
    add_settings_section(
      'kleinanzeigen_background_tasks_section', // ID used to identify this section and with which to register options
      __('Auto sync', 'kleinanzeigen'), // Title
      array($this, 'kleinanzeigen_background_tasks_section'), // Callback
      'kleinanzeigen_account_settings' // Page on which to add this section of options
    );

    // Schedule invalid prices
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_schedule_invalid_prices',
      'name'              => 'kleinanzeigen_schedule_invalid_prices',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => __('Replace product price by updated Kleinanzeigen price', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Price changes', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Schedule orphaned links
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_schedule_remove_orphaned_links',
      'name'              => 'kleinanzeigen_schedule_remove_orphaned_links',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => __('Remove orphaned Kleinanzeigen links on products', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Orphaned links', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Schedule invalid ad action
    unset($args);
    $args = array(
      'type'              => 'select',
      'id'                => 'kleinanzeigen_schedule_invalid_ads',
      'name'              => 'kleinanzeigen_schedule_invalid_ads',
      'required'          => 'true',
      'get_options_list'  => wbp_fn()->dropdown_invalid_ads(get_option('kleinanzeigen_schedule_invalid_ads', '0')),
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'description'       => __('Determine how to proceed with the product when its ad has become invalid due to reservation, deactivation or deletion', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Orphaned ad action', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Schedule new ads
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_schedule_new_ads',
      'name'              => 'kleinanzeigen_schedule_new_ads',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => __('Automatically create product for new ad', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Autocreate new products', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Send email new ads
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_send_mail_on_new_ad',
      'name'              => 'kleinanzeigen_send_mail_on_new_ad',
      'required'          => 'true',
      'get_options_list'  => '',
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => __('Send email when product was automatically created', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Send email on autocreation', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));

    // Cron Job Interval
    unset($args);
    $args = array(
      'type'              => 'select',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_crawl_interval',
      'name'              => 'kleinanzeigen_crawl_interval',
      'required'          => 'true',
      'get_options_list'  => wbp_fn()->dropdown_crawl_interval(get_option('kleinanzeigen_crawl_interval', 'every_minute'), $this->schedules()),
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => __('Select the interval at which Kleinanzeigen.de should be crawled for changes', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Crawl interval', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_background_tasks_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'));
  }

  public function sanitize_option($data)
  {
    return $data;
  }

  public function kleinanzeigen_display_general_account()
  {
    echo '<em>' . __('Please enter your account data.', 'kleinanzeigen') . '</em>';
  }

  public function kleinanzeigen_background_tasks_section()
  {
    echo '<em>' . __('Ads on Kleinanzeigen.de will be periodically evaluated. Perform the following if an ad has been altered:', 'kleinanzeigen') . '</em>';
  }

  public function kleinanzeigen_render_settings_field($args)
  {
    /**
     * EXAMPLE INPUT
     * 'type'             => 'input',
     * 'subtype'          => '',
     * 'id'               => self::$plugin_name.'_example_setting',
     * 'name'             => self::$plugin_name.'_example_setting',
     * 'required'         => 'required="required"',
     * 'get_option_list'  => "",
     * 'value_type' = serialized OR normal,
     * 'wp_data'=>(option or post_meta),
     * 'post_id' =>
     */
    if ($args['wp_data'] == 'option') {
      $wp_data_value = get_option($args['name']);
    } elseif ($args['wp_data'] == 'post_meta') {
      $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
    }

    switch ($args['type']) {

      case 'input':
        $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
        if ($args['subtype'] != 'checkbox') {
          $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
          $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
          $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
          $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
          $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
          if (isset($args['disabled'])) {
            // hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
          } else {
            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
          }
          /*<input required="required" '.$disabled.' type="number" step="any" id="'.self::$plugin_name.'_cost2" name="'.self::$plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.self::$plugin_name.'_cost" step="any" name="'.self::$plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/
        } else {
          $checked = ($value) ? 'checked' : '';
          echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" name="' . $args['name'] . '" size="40" value="1" ' . $checked . ' />';
        }

        break;
      case 'select': ?>
        <select name="<?php echo $args['id'] ?>" id="<?php echo $args['id'] ?>"><?php echo $args['get_options_list']; ?></select>
<?php
        break;
      default:
        # code...
        break;
    }

    if (isset($args['label'])) {
      echo '<label for="' . $args['id'] . '" class="description">&nbsp;' . $args['label'] . '</label>';
    }
    if (isset($args['description'])) {
      echo '<p class="description">' . $args['description'] . '</p>';
    }
  }

  public static function get_instance($file = null)
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }
}
