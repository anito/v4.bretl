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

  private static $schedule;

  public static $events;

  const EVERY_MINUTE    = 'every_minute';
  const FIVE_MINUTES    = 'five_minutes';
  const TEN_MINUTES     = 'ten_minutes';
  const THIRTY_MINUTES  = 'thirty_minutes';
  const HOURLY          = 'hourly';
  const DAILY           = 'daily';
  const WEEKLY          = 'weekly';
  const MONTHLY         = 'monthly';
  const NEVER           = 'never';

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
    add_action('init', array($this, 'register_event_types'), -998);
    add_action('admin_menu', array($this, 'addPluginAdminMenu'), 9);
    add_filter("option_page_capability_kleinanzeigen_account_settings", array($this, 'get_capability'));
    add_action('admin_init', array($this, 'registerAndBuildFields'));

    // Cron jobs
    add_action('kleinanzeigen_report_daily', array($this, 'job_report'));
    add_action('kleinanzeigen_report_weekly', array($this, 'job_report'));
    add_action('kleinanzeigen_report_monthly', array($this, 'job_report'));
    add_action('kleinanzeigen_report_every_minute', array($this, 'job_report'));
    add_action('kleinanzeigen_sync_price', array($this, 'job_sync_price'));
    add_action('kleinanzeigen_updated_ads', array($this, 'job_updated_ads'));
    add_action('kleinanzeigen_activate_url', array($this, 'job_activate_url'));
    add_action('kleinanzeigen_deactivate_url', array($this, 'job_deactivate_url'));
    add_action('kleinanzeigen_create_products', array($this, 'job_create_products'));
    add_action('kleinanzeigen_invalid_ad_action', array($this, 'job_invalid_ad_action'));

    // User sepecific options
    add_filter('pre_update_option_kleinanzeigen_send_mail_on_new_ad', array($this, 'update_user_meta_callback'), 10, 3);
    add_filter('option_kleinanzeigen_send_mail_on_new_ad', array($this, 'get_user_meta_callback'), 10, 2);
    add_filter('pre_update_option_kleinanzeigen_send_status_mail', array($this, 'update_user_meta_callback'), 10, 3);
    add_filter('option_kleinanzeigen_send_status_mail', array($this, 'get_user_meta_callback'), 10, 2);

    // Cron specific options
    add_action('update_option_kleinanzeigen_account_name', array($this, 'invalidate_cron_callback'));
    add_action('update_option_kleinanzeigen_crawl_interval', array($this, 'invalidate_cron_callback'));
    add_action('update_option_kleinanzeigen_is_pro_account', array($this, 'invalidate_cron_callback'));
    add_action('update_option', array($this, 'unschedule_all_events'), 1, 3);


    // add_action('init', array($this, 'unregister_jobs'));
    add_action('init', array($this, 'register_jobs'));

    // add_filter('cron_schedules', array($this, 'schedules'));
  }

  public function loadFiles()
  {
    require_once $this->plugin_path('includes/class-kleinanzeigen-database.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-functions.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-list-table.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-term-handler.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax-modal.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-list-table-tasks.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-register-wc-taxonomies.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-table-ajax-action-handler.php');
    require_once $this->plugin_path('includes/class-kleinanzeigen-wc-admin-list-table-products.php');
  }

  /**
   * Register the stylesheets for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

    $screen_id = wbp_fn()->screen_id;
    if (in_array($screen_id, wc_get_screen_ids()))
    {
      $screen_id = 'woocommerce';
    }
    elseif (strpos($screen_id, self::$plugin_name) !== false)
    {
      $screen_id = self::$plugin_name;
    }
    switch ($screen_id)
    {
      case 'woocommerce':
      case self::$plugin_name:
        wp_enqueue_style(self::$plugin_name, plugin_dir_url(__FILE__) . "css/style-admin-{$screen_id}.css", array(), self::$version, 'all');
        break;
    }
  }

  public function admin_dir_url()
  {
    return plugin_dir_url(__FILE__);
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
      'edit_link'   => admin_url('post.php?action=edit&post=')
    ));
    wp_localize_script(self::$plugin_name . '-utils', 'KleinanzeigenUtils', array(
      'admin_ajax'  => admin_url('admin-ajax.php'),
      'screen'      => wbp_fn()->screen_id,
    ));
  }

  public function register_event_types()
  {

    self::$events = array(
      'mandatory' => array(
        'kleinanzeigen_activate_url',
        'kleinanzeigen_deactivate_url',
        'kleinanzeigen_updated_ads',
        'kleinanzeigen_report_every_minute',
        'kleinanzeigen_report_daily',
        'kleinanzeigen_report_weekly',
        'kleinanzeigen_report_monthly'
      ),
      'optional' => array(
        'kleinanzeigen_sync_price',
        'kleinanzeigen_invalid_ad_action',
        'kleinanzeigen_create_products',
      ),
    );
  }

  public function get_capability($capabilities)
  {
    return self::$capability;
  }

  public function template_path($path)
  {
    return is_admin() ? trailingslashit('admin') : $path;
  }

  public static function get_schedule()
  {
    return (self::$schedule = array(
      self::NEVER => array(
        'interval' => 0,
        'display'  => __('Never', 'kleinanzeigen'),
      ),
      self::EVERY_MINUTE => array(
        'default'  => true,
        'interval' => 60,
        'display'  => __('Every minute', 'kleinanzeigen'),
      ),
      self::FIVE_MINUTES => array(
        'default'  => true,
        'interval' => 5 * 60,
        'display'  => __('Every 5 minutes', 'kleinanzeigen'),
      ),
      self::TEN_MINUTES => array(
        'default'  => true,
        'interval' => 10 * 60,
        'display'  => __('Every 10 minutes', 'kleinanzeigen'),
      ),
      self::THIRTY_MINUTES => array(
        'default'  => true,
        'interval' => 30 * 60,
        'display'  => __('Every 30 minutes', 'kleinanzeigen'),
      ),
      self::HOURLY => array(
        'interval' => 60 * 60,
        'display'  => __('Every hour', 'kleinanzeigen'),
      ),
      self::DAILY => array(
        'interval' => 24 * 60 * 60,
        'display'  => __('Every day', 'kleinanzeigen'),
      ),
      self::WEEKLY => array(
        'interval' => 7 * 24 * 60 * 60,
        'display'  => __('Weekly', 'kleinanzeigen'),
      ),
      self::MONTHLY => array(
        'interval' => 365 * 24 * 60 * 60,
        'display'  => __('Monthly', 'kleinanzeigen'),
      )
    ));
  }

  public function schedules($filter = null)
  {
    if (is_null($filter)) return self::get_schedule();
    return array_filter(self::get_schedule(), function ($key) use ($filter)
    {
      return in_array($key, $filter);
    }, ARRAY_FILTER_USE_KEY);
  }

  public function invalidate_cron_callback()
  {

    setcookie('ka-paged', 1);
    delete_transient('kleinanzeigen_data');
    wbp_db()->clear_jobs();
  }

  public function unschedule_all_events($option, $new_value, $old_value)
  {

    if (0 === strpos($option, 'kleinanzeigen_') && $old_value !== $new_value)
    {

      $this->unschedule_events(array('mandatory', 'optional'));
    }
  }

  private function unschedule_events($types)
  {
    $event_names = [];
    $types = !is_array($types) ? array($types) : $types;
    foreach ($types as $type)
    {
      if (!array_key_exists($type, self::$events)) continue;
      foreach (self::$events[$type] as $name)
      {
        $event_names[] = $name;
      }
    }

    foreach ($event_names as $name)
    {
      if (wp_next_scheduled($name))
      {
        wp_unschedule_hook($name);
      }
    }
  }

  public function unregister_jobs()
  {
    wp_unschedule_hook('kleinanzeigen_sync_price');
    wp_unschedule_hook('kleinanzeigen_activate_url');
    wp_unschedule_hook('kleinanzeigen_deactivate_url');
    wp_unschedule_hook('kleinanzeigen_updated_ads');
    wp_unschedule_hook('kleinanzeigen_sync_price');
    wp_unschedule_hook('kleinanzeigen_invalid_ad_action');
    wp_unschedule_hook('kleinanzeigen_create_products');
  }

  public function register_jobs()
  {

    $i = 0;
    $time = function ($offset = 30) use (&$i)
    {
      $time = time() + $i++ * $offset;
      return $time;
    };

    /*
     * Repair url
     * Mandatory, no option available
    */
    if (!wp_next_scheduled('kleinanzeigen_activate_url'))
    {
      wp_schedule_event($time(), self::EVERY_MINUTE, 'kleinanzeigen_activate_url');
    }

    /*
     * Remove ophaned links from product
     * Mandatory, no option available
    */
    if (!wp_next_scheduled('kleinanzeigen_deactivate_url'))
    {
      wp_schedule_event($time(), self::EVERY_MINUTE, 'kleinanzeigen_deactivate_url');
    }

    /*
     * Repair changed title
     * Mandatory, no option available
    */
    if (!wp_next_scheduled('kleinanzeigen_updated_ads'))
    {
      wp_schedule_event($time(), self::EVERY_MINUTE, 'kleinanzeigen_updated_ads');
    }

    /*
     * Status report
     * Mandatory, based on user options
    */
    $schedules = wbp_fn()->get_users_schedules();
    foreach ($schedules as $schedule => $emails)
    {
      $args = array(json_encode(array('emails' => $emails), JSON_UNESCAPED_SLASHES));
      if (!wp_next_scheduled("kleinanzeigen_report_{$schedule}", $args))
      {
        switch ($schedule)
        {
          case self::EVERY_MINUTE:
            $next = time();
            break;
          case self::DAILY:
            $next = strtotime("next Day") + (6 * 60 * 60);
            break;
          case self::WEEKLY:
            $next = strtotime("next Monday") + (6 * 60 * 60);
            break;
          case self::MONTHLY:
            $next = strtotime("first Monday of next Month") + (6 * 60 * 60);
            break;
          default:
            $next = null;
        }
        if ($next)
        {

          wp_schedule_event($next, $schedule, "kleinanzeigen_report_{$schedule}", $args);
        }
      }
    }

    $crawl_interval = get_option('kleinanzeigen_crawl_interval');
    if (self::NEVER !== $crawl_interval)
    {
      /*
       * Sync price
       * Optional, option available
      */
      if ("1" === get_option('kleinanzeigen_schedule_invalid_prices'))
      {
        if (!wp_next_scheduled('kleinanzeigen_sync_price'))
        {
          wp_schedule_event($time(), $crawl_interval, 'kleinanzeigen_sync_price');
        }
      }

      /*
       * Sync invalid ads
       * Optional, option available
      */
      if ("0" !== get_option('kleinanzeigen_schedule_invalid_ads', '0'))
      {
        if (!wp_next_scheduled('kleinanzeigen_invalid_ad_action'))
        {
          wp_schedule_event($time(), $crawl_interval, 'kleinanzeigen_invalid_ad_action');
        }
      }

      /*
       * Sync create new products
       * Optional, option availabele
      */
      if ("1" === get_option('kleinanzeigen_schedule_new_ads', '0'))
      {
        if (!wp_next_scheduled('kleinanzeigen_create_products'))
        {
          wp_schedule_event($time(), $crawl_interval, 'kleinanzeigen_create_products');
        }
      }
    }
    else
    {
      $this->unschedule_events('optional');
    }
  }

  public function job_sync_price()
  {
    $items = wbp_fn()->build_tasks('invalid-price')['items'];

    foreach ($items as $item)
    {

      $job_id = wbp_db()->register_job(array(
        'slug'  => 'kleinanzeigen_sync_price',
        'type'  => 'record',
        'uid'   => $item['record']->id
      ));

      $price = Utils::extract_kleinanzeigen_price($item['record']->price);
      wbp_fn()->fix_price($item['product']->get_ID(), $price);

      if ($job_id)
      {
        wbp_db()->unregister_job($job_id);
      }
    }
  }

  public function job_updated_ads()
  {
    // This job doesn't require to be registered in the active jobs db table
    // since it only rarily occures
    wbp_fn()->build_tasks('updated-product');
  }

  public function job_create_products()
  {

    set_time_limit(300);

    $items = wbp_fn()->build_tasks('new-product')['items'];

    foreach ($items as $item)
    {

      $record = (object) $item['record'];

      if (!ALLOW_DUPLICATE_TITLES && wbp_fn()->product_title_exists($record->title))
      {
        continue;
      }

      if (wbp_db()->get_job_by_uid($record->id, 'record'))
      {
        continue;
      };

      Utils::write_log("##### New Product #####");
      Utils::write_log($record->title);
      Utils::write_log("#######################");

      $job_id = wbp_db()->register_job(array(
        'slug'  => 'kleinanzeigen_create_products',
        'type'  => 'record',
        'uid'   => $record->id
      ));

      $doc = wbp_fn()->get_dom_document($record);

      $el = $doc->getElementById('viewad-description-text');
      $content = $doc->saveHTML($el);

      $post_ID = wbp_fn()->create_ad_product($record, $content);

      if (!is_wp_error($post_ID))
      {

        $images = wbp_fn()->get_document_images($doc);

        wbp_fn()->create_product_images($post_ID, $images);

        $users = wbp_fn()->get_users_by_meta('kleinanzeigen_send_mail_on_new_ad');
        $user_mails = wp_list_pluck($users, 'user_email');
        $to_email = array_intersect($user_mails, array(get_bloginfo('admin_email')));
        $bcc_emails = array_diff($user_mails, $to_email);
        $to_email = empty($to_email) ? (!empty($bcc_emails) ? array_splice($bcc_emails, 0, 1) : array()) : $to_email;

        $receipients = array(
          'to_email'  => implode(',', $to_email),
          'bcc'       => implode(',', $bcc_emails),
          'cc'        => implode(',', IS_SUBDOMAIN_DEV ? array(get_option('kleinanzeigen_send_cc_mail')) : array())
        );

        wbp_fn()->sendMailNewProduct($post_ID, $record, $receipients);

        update_post_meta((int) $post_ID, 'kleinanzeigen_id', $record->id);
      }
      else
      {
        $error_data = $post_ID->get_error_data();
        $error_message = $post_ID->get_error_message();
      }

      if ($job_id)
      {
        wbp_db()->unregister_job($job_id);
      }
    }
  }

  public function job_activate_url()
  {
    $items = wbp_fn()->build_tasks('has-sku')['items'];

    foreach ($items as $item)
    {

      $post_ID = $item['product']->get_ID();
      $record = $item['record'];

      $urls_valid = get_post_meta($post_ID, 'kleinanzeigen_url', true) &&
        get_post_meta($post_ID, 'kleinanzeigen_search_url', true);
      $record_exists = !is_null($record);

      if (!$urls_valid && $record_exists)
      {

        $job_id = wbp_db()->register_job(array(
          'slug'  => 'kleinanzeigen_activate_url',
          'type'  => 'product',
          'uid' => $post_ID
        ));

        wbp_fn()->enable_sku($post_ID, $record);

        if ($job_id)
        {
          wbp_db()->unregister_job($job_id);
        }
      }
    }
  }

  public function job_deactivate_url()
  {
    $items = wbp_fn()->build_tasks('invalid-sku')['items'];
    $products = wp_list_pluck($items, 'product');
    $ids = array_map(function ($product)
    {
      return $product->get_ID();
    }, $products);

    $ids = array_filter($ids, function ($id)
    {
      return !empty(get_metadata('post', $id, 'kleinanzeigen_url'));
    });

    foreach ($ids as $id)
    {
      $job_id = wbp_db()->register_job(array(
        'slug'  => 'kleinanzeigen_deactivate_url',
        'type'  => 'product',
        'uid'   => $id
      ));

      wbp_fn()->disable_sku_url($id);

      if ($job_id)
      {
        wbp_db()->unregister_job($job_id);
      }
    }
  }

  public function job_invalid_ad_action()
  {
    $action = get_option('kleinanzeigen_schedule_invalid_ads');
    $items = wbp_fn()->build_tasks('invalid-sku')['items'];

    foreach ($items as $item)
    {
      $post_ID = $item['product']->get_ID();

      $job_id = wbp_db()->register_job(array(
        'slug'  => 'kleinanzeigen_invalid_ad_action',
        'type'  => 'product',
        'uid' => $post_ID
      ));

      switch ($action)
      {
        case 'publish':
          $args = array('post_status' => 'publish');
          break;
        case 'deactivate':
          $args = array('post_status' => 'draft');
          break;
        case 'delete':
          $args = array('post_status' => 'trash');
          break;
        default:
      }
      $actions = wbp_fn()->get_invalid_ad_actions();

      if (isset($actions[$action]['postarr']))
      {
        $args = $actions[$action]['postarr'];
        $postarr = array_merge(array(
          'ID' => $post_ID
        ), $args);

        /**
         * Here the actual job will be done by updating the post
         */
        wp_update_post($postarr);
        $product = wc_get_product($post_ID);

        if ("trash" === $product->get_status())
        {
          wbp_fn()->delete_product($post_ID, true);
        }
      }

      if ($job_id)
      {
        wbp_db()->unregister_job($job_id);
      }
    }
  }

  // Job status report
  public function job_report($arg)
  {

    $args = (object) json_decode($arg);
    $user_mails = $args->emails;
    $to_emails = $user_mails;
    $bcc_email = get_bloginfo('admin_email');

    $receipients = array(
      'to_email'  => implode(',', $to_emails),
      'bcc'       => $bcc_email,
    );

    wbp_fn()->sendMailStatusReport($receipients);
  }

  public function addPluginAdminMenu()
  {
    $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjkuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9InV1aWQtY2Y1MDkyNzItMDBlMi00YjJlLWJmZWUtNTZlZjA5ZDhhMDRmIgoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTE1IDEzMSIKCSBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMTUgMTMxOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6I0E3QUFBRDt9Cjwvc3R5bGU+CjxnIGlkPSJ1dWlkLWRhZDJmZmE0LTgzNzctNDg3Ni04OTY0LWQ3ODEyYWE0NGU5MSI+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODAuOCwxMjFjLTE0LjksMC0yMi4yLTEwLjQtMjMuNi0xMi41Yy00LjQsNC4zLTExLDEyLjUtMjMsMTIuNWMtMTMuOCwwLTI1LjQtMTAuNC0yNS40LTI3LjNWMzcuMwoJCUM4LjgsMjAuNCwyMC40LDEwLDM0LjIsMTBzMjUuNCwxMSwyNS40LDI3LjFjMi43LTEsNS41LTEuNCw4LjUtMS40YzE0LjIsMCwyNS40LDExLjYsMjUuNCwyNS42YzAsMy45LTAuNyw3LjQtMi40LDEwLjcKCQljOSw0LDE1LjEsMTMuMSwxNS4xLDIzLjRDMTA2LjIsMTA5LjUsOTQuOCwxMjEsODAuOCwxMjFMODAuOCwxMjF6IE02My4zLDEwMi4zYzMuNyw2LjQsOS44LDEwLjIsMTcuNSwxMC4yCgkJYzkuMywwLDE2LjktNy43LDE2LjktMTcuMWMwLTcuNC00LjgtMTMuOS0xMS42LTE2LjJMNjMuMywxMDIuM0M2My4zLDEwMi4zLDYzLjMsMTAyLjMsNjMuMywxMDIuM3ogTTM0LjIsMTguNQoJCWMtOC40LDAtMTYuOSw1LjgtMTYuOSwxOC44djU2LjNjMCwxMyw4LjUsMTguOCwxNi45LDE4LjhjNi43LDAsMTAuNC0zLjQsMTYuNC05LjRsMi42LTIuN2MtMS40LTQuMS0yLjEtOC42LTIuMS0xMy41VjM3LjMKCQlDNTEuMSwyNC40LDQyLjYsMTguNSwzNC4yLDE4LjVMMzQuMiwxOC41TDM0LjIsMTguNXogTTU5LjYsNDYuNHY0MC40YzAsMi4zLDAuMiw0LjUsMC42LDYuNWwxOC40LTE4LjZjNS4zLTUuNCw2LjQtOS4zLDYuNC0xMy42CgkJYzAtOS4xLTcuMi0xNy4xLTE2LjktMTcuMUM2NSw0NC4yLDYyLjIsNDQuOSw1OS42LDQ2LjRMNTkuNiw0Ni40TDU5LjYsNDYuNHoiLz4KPC9nPgo8L3N2Zz4K';
    add_menu_page(self::$plugin_name, __('Kleinanzeigen', 'kleinanzeigen'), self::$capability, self::$plugin_name, array($this, 'displayPluginAdminDashboard'), $icon_svg, 10);
    add_submenu_page(self::$plugin_name, __('Kleinanzeigen Settings', 'kleinanzeigen'), __('Settings', 'kleinanzeigen'), self::$capability, self::$plugin_name . '-settings', array($this, 'displayPluginAdminSettings'));
  }

  public function displayPluginAdminDashboard()
  {
    $this->include_template(self::$plugin_name . '-page-header.php');
    $this->include_template(self::$plugin_name . '-admin-display.php');
  }

  public function displayPluginAdminSettings()
  {
    // set this var to be used in the settings-display view
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

    if (isset($_GET['error_message']))
    {
      add_action('admin_notices', array($this, 'kleinanzeigenSettingsMessages'));
      do_action('admin_notices', $_GET['error_message']);
    }
    if (isset($_GET['settings-updated']))
    {
    }

    if (is_wp_error(Utils::get_page_data()))
    {
      add_settings_error('kleinanzeigen_account_name', 403, sprintf(__('The account %1$s could not be found', 'kleinanzeigen'), '<em class="">"' . get_option('kleinanzeigen_account_name') . '"</em>'), 'error');
    }

    $this->include_template(self::$plugin_name . '-page-header.php');
    $this->include_template(self::$plugin_name . '-admin-settings-display.php');
  }

  public function kleinanzeigenSettingsMessages($error_message)
  {
    switch ($error_message)
    {
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

  // User specific option
  public function get_user_meta_callback($value, $option)
  {

    $user_id = wp_get_current_user()->ID;
    $value = get_user_meta($user_id, $option, true);
    return $value;
  }

  public function update_user_meta_callback($new, $old, $option)
  {
    if ($new === $old) return;

    $user_id = wp_get_current_user()->ID;
    update_user_meta($user_id, $option, $new);

    // Handle mail report usage
    if ('kleinanzeigen_send_status_mail' == $option)
    {
      foreach (_get_cron_array() as $timestamp => $jobs)
      {
        foreach ($jobs as $name => $job)
        {
          if (0 === strpos($name, 'kleinanzeigen_report'))
          {
            foreach ($job as $key => $job_details)
            {
              if ($job_details['schedule'] === $old)
              {
                $args = $job_details['args'];
                wp_unschedule_event($timestamp, "kleinanzeigen_report_{$old}", $args);
              }
              if ($job_details['schedule'] === $new)
              {
                $args = $job_details['args'];
                wp_unschedule_event($timestamp, "kleinanzeigen_report_{$new}", $args);
              }
            }
          }
        }
      };
    }
  }

  public function registerAndBuildFields()
  {

    $register = function ($id, $callback = '', $default = '')
    {

      register_setting(
        'kleinanzeigen_account_settings',
        $id,
        array(
          'sanitize_callback' => $callback,
          'show_in_rest'      => true,
          'default'           => $default
        )
      );
    };

    $get_default = function ($args)
    {
      $args = wp_parse_args($args, array('subtype' => ''));
      switch ($args['subtype'])
      {
        case 'text':
          $default = '';
          break;
        case 'number':
        case 'checkbox':
          $default = 0;
          break;
        default:
          $default = '';
      }
      return $default;
    };

    // Section Account
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
      'required'          => '',
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
    $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

    // Pro account
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'checkbox',
      'id'                => 'kleinanzeigen_is_pro_account',
      'name'              => 'kleinanzeigen_is_pro_account',
      'required'          => '',
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
    $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

    // Ads per page
    unset($args);
    $args = array(
      'type'              => 'input',
      'subtype'           => 'number',
      'id'                => 'kleinanzeigen_items_per_page',
      'name'              => 'kleinanzeigen_items_per_page',
      'required'          => '',
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
    $register($args['id'], array($this, 'sanitize_option'), ITEMS_PER_PAGE);

    // Section Scheduling
    add_settings_section(
      'kleinanzeigen_background_tasks_section', // ID used to identify this section and with which to register options
      __('Auto sync', 'kleinanzeigen'), // Title
      array($this, 'kleinanzeigen_background_tasks_section'), // Callback
      'kleinanzeigen_account_settings' // Page on which to add this section of options
    );

    // Cron Job Interval
    unset($args);
    $args = array(
      'type'              => 'select',
      'id'                => 'kleinanzeigen_crawl_interval',
      'name'              => 'kleinanzeigen_crawl_interval',
      'required'          => '',
      'get_options_list'  => wbp_fn()->dropdown_crawl_interval(get_option('kleinanzeigen_crawl_interval', self::EVERY_MINUTE), $this->schedules(array(self::NEVER, self::EVERY_MINUTE, self::FIVE_MINUTES, self::TEN_MINUTES, self::THIRTY_MINUTES, self::HOURLY))),
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
    $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

    if (self::NEVER !== get_option('kleinanzeigen_crawl_interval'))
    {

      // Schedule invalid prices
      unset($args);
      $args = array(
        'type'              => 'input',
        'subtype'           => 'checkbox',
        'id'                => 'kleinanzeigen_schedule_invalid_prices',
        'name'              => 'kleinanzeigen_schedule_invalid_prices',
        'required'          => '',
        'get_options_list'  => '',
        'value_type'        => 'normal',
        'wp_data'           => 'option',
        'label'             => __('Automatically accept price changes', 'kleinanzeigen'),
      );
      add_settings_field(
        $args['id'],
        __('Prices', 'kleinanzeigen'),
        array($this, 'kleinanzeigen_render_settings_field'),
        'kleinanzeigen_account_settings',
        'kleinanzeigen_background_tasks_section',
        $args
      );
      $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

      // Schedule new ads
      unset($args);
      $args = array(
        'type'              => 'input',
        'subtype'           => 'checkbox',
        'id'                => 'kleinanzeigen_schedule_new_ads',
        'name'              => 'kleinanzeigen_schedule_new_ads',
        'required'          => '',
        'get_options_list'  => '',
        'value_type'        => 'normal',
        'wp_data'           => 'option',
        'label'             => __('Automatically create product from new ad', 'kleinanzeigen'),
      );
      add_settings_field(
        $args['id'],
        __('Autocreate new products', 'kleinanzeigen'),
        array($this, 'kleinanzeigen_render_settings_field'),
        'kleinanzeigen_account_settings',
        'kleinanzeigen_background_tasks_section',
        $args
      );
      $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

      // Send email new ads
      unset($args);
      $args = array(
        'type'              => 'input',
        'subtype'           => 'checkbox',
        'id'                => 'kleinanzeigen_send_mail_on_new_ad',
        'name'              => 'kleinanzeigen_send_mail_on_new_ad',
        'required'          => '',
        'get_options_list'  => '',
        'value_type'        => 'normal',
        'wp_data'           => 'option',
        'label'             => sprintf(__('Send me an email ( %s ) after a product has been created', 'kleinanzeigen'), '<span class="boxed">' . wp_get_current_user()->user_email . '</span>'),
      );
      add_settings_field(
        $args['id'],
        __('Send email on autocreation', 'kleinanzeigen'),
        array($this, 'kleinanzeigen_render_settings_field'),
        'kleinanzeigen_account_settings',
        'kleinanzeigen_background_tasks_section',
        $args
      );
      $register($args['id'], array($this, 'sanitize_option'), $get_default($args));

      if (wp_get_current_user()->has_cap('administrator'))
      {
        // Send CC to-email new ads
        unset($args);
        $args = array(
          'type'              => 'input',
          'subtype'           => 'text',
          'id'                => 'kleinanzeigen_send_cc_mail',
          'name'              => 'kleinanzeigen_send_cc_mail',
          'required'          => '',
          'disabled'          => current_user_can('edit_posts') ? false : true,
          'get_options_list'  => '',
          'value_type'        => 'normal',
          'wp_data'           => 'option',
        );
        add_settings_field(
          $args['id'],
          sprintf(__('Additional Mail %s', 'kleinanzeigen'), '<br /><small style="font-weight: 300;">(' . __('During development only', 'kleinanzeigen') . ')</small>'),
          array($this, 'kleinanzeigen_render_settings_field'),
          'kleinanzeigen_account_settings',
          'kleinanzeigen_background_tasks_section',
          $args
        );
        $register($args['id'], array($this, 'sanitize_option'), $get_default($args));
      }

      // Schedule invalid ad action
      unset($args);
      $args = array(
        'type'              => 'select',
        'id'                => 'kleinanzeigen_schedule_invalid_ads',
        'name'              => 'kleinanzeigen_schedule_invalid_ads',
        'required'          => '',
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
      $register($args['id'], array($this, 'sanitize_option'), $get_default($args));
    };

    // Section Misc
    add_settings_section(
      'kleinanzeigen_misc_section', // ID used to identify this section and with which to register options
      __('Misc', 'kleinanzeigen'), // Title
      '__return_empty_string', // Callback
      'kleinanzeigen_account_settings' // Page on which to add this section of options
    );

    // Schedule status report
    unset($args);
    $args = array(
      'type'              => 'select',
      'id'                => 'kleinanzeigen_send_status_mail',
      'name'              => 'kleinanzeigen_send_status_mail',
      'required'          => '',
      'get_options_list'  => wbp_fn()->dropdown_crawl_interval(get_user_meta(get_current_user_id(), 'kleinanzeigen_send_status_mail', true), $this->schedules(array(self::NEVER, self::DAILY, self::WEEKLY, self::MONTHLY))),
      'value_type'        => 'normal',
      'wp_data'           => 'option',
      'label'             => wbp_ka()->display_status_report_link(),
      'description'       => __('Select the interval at which you want to receive a status report', 'kleinanzeigen'),
    );
    add_settings_field(
      $args['id'],
      __('Status report', 'kleinanzeigen'),
      array($this, 'kleinanzeigen_render_settings_field'),
      'kleinanzeigen_account_settings',
      'kleinanzeigen_misc_section',
      $args
    );
    $register($args['id'], array($this, 'sanitize_option'), $get_default($args));
  }

  public function sanitize_option($data)
  {
    return $data;
  }

  public function kleinanzeigen_display_general_account()
  {
    echo '<em>' . __('Please enter your account data.', 'kleinanzeigen') . '</em>';
  }

  public function kleinanzeigen_display_misc()
  {
    echo '<em>' . __('Additional settings.', 'kleinanzeigen') . '</em>';
  }

  public function kleinanzeigen_background_tasks_section()
  {
    echo '<em>' . __('Ads on Kleinanzeigen.de may get evaluated on a regular base. Select the interval and tasks to be performed.', 'kleinanzeigen') . '</em>';
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
     * 'disabled'         => true | false,
     * 'get_option_list'  => "",
     * 'value_type' = serialized OR normal,
     * 'wp_data'=>(option or post_meta),
     * 'post_id' =>
     */
    if ($args['wp_data'] == 'option')
    {
      $wp_data_value = get_option($args['name']);
    }
    elseif ($args['wp_data'] == 'post_meta')
    {
      $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
    }

    switch ($args['type'])
    {

      case 'input':
        $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
        if ($args['subtype'] != 'checkbox')
        {
          $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
          $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
          $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
          $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
          $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
          if (isset(($args['disabled'])) && true === $args['disabled'])
          {
            // hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
          }
          else
          {
            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" ' .  $args['required'] . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" data-value="' . esc_attr($value) . '" value="' . esc_attr($value) . '" />' . $prependEnd;
          }
          /*<input required="required" '.$disabled.' type="number" step="any" id="'.self::$plugin_name.'_cost2" name="'.self::$plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.self::$plugin_name.'_cost" step="any" name="'.self::$plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/
        }
        else
        {
          $checked = ($value) ? 'checked' : '';
          echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" ' .  $args['required'] . ' name="' . $args['name'] . '" data-value="' . esc_attr($value) . '" size="40" value="1" ' . $checked . ' />';
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

    if (isset($args['label']))
    {
      echo '<label for="' . $args['id'] . '" class="description">&nbsp;' . $args['label'] . '</label>';
    }
    if (isset($args['description']))
    {
      echo '<p class="description"><i><small>' . $args['description'] . '</small></i></p>';
    }
  }

  public static function get_instance($file = null)
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance)
    {
      self::$instance = new self;
    }
    return self::$instance;
  }
}
