<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 * @author     Ben Shadle <benshadle@gmail.com>
 */
class Kleinanzeigen extends Kleinanzeigen_Templates
{

  private static $instance;

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      Kleinanzeigen_Loader    $loader    Maintains and registers all hooks for the plugin.
   */
  protected $loader;

  /**
   * The unique identifier of this plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $plugin_name    The string used to uniquely identify this plugin.
   */
  protected static $plugin_name;

  /**
   * The current version of the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $version    The current version of the plugin.
   */
  protected static $version;

  /**
   * The plugin data.
   *
   * @since    1.0.0
   * @access   protected
   * @var      object    $plugin data    The plugin data.
   */
  protected static $plugin_data;

  /**
   * The capability of the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $capability    The capability of the plugin.
   */
  protected static $capability;

  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the admin area and
   * the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function __construct($file)
  {
    self::$plugin_data = $this->get_plugin_data($file);

    self::$capability = 'manage_product_terms';
    self::$version = self::$plugin_data->Version;
    self::$plugin_name = strtolower(self::$plugin_data->Name);

    $this->load_dependencies();
    $this->set_locale();
    $this->define_admin_hooks();
    $this->define_public_hooks();
  }

  private function get_plugin_data($plugin_file)
  {

    $default_headers = array(
      'Test'        => 'Plugin Name',
      'Name'        => 'Plugin Name',
      'PluginURI'   => 'Plugin URI',
      'Version'     => 'Version',
      'Description' => 'Description',
      'Author'      => 'Author',
      'AuthorURI'   => 'Author URI',
      'TextDomain'  => 'Text Domain',
      'DomainPath'  => 'Domain Path',
      'Network'     => 'Network',
      'RequiresWP'  => 'Requires at least',
      'RequiresPHP' => 'Requires PHP',
      'UpdateURI'   => 'Update URI',
    );

    $plugin_data = get_file_data($plugin_file, $default_headers, 'plugin');

    return (object) $plugin_data;
  }

  /**
   * Load the required dependencies for this plugin.
   *
   * Include the following files that make up the plugin:
   *
   * - Kleinanzeigen_Loader. Orchestrates the hooks of the plugin.
   * - Kleinanzeigen_i18n. Defines internationalization functionality.
   * - Kleinanzeigen_Admin. Defines all hooks for the admin area.
   * - Kleinanzeigen_Public. Defines all hooks for the public side of the site.
   *
   * Create an instance of the loader which will be used to register the hooks
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies()
  {

    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
    require_once $this->plugin_path('includes/class-kleinanzeigen-loader.php');

    /**
     * The class responsible for defining internationalization functionality
     * of the plugin.
     */
    require_once $this->plugin_path('includes/class-kleinanzeigen-i18n.php');

    /**
     * The class responsible for defining all actions that occur in the admin area.
     */
    require_once $this->plugin_path('admin/class-kleinanzeigen-admin.php');

    /**
     * The class responsible for defining all main functions
     */
    require_once $this->plugin_path('includes/class-kleinanzeigen-functions.php');

    /**
     * The class responsible for registering and rendering templates
     * side of the site.
     */
    require_once $this->plugin_path('includes/class-kleinanzeigen-templates.php');

    /**
     * The class responsible for defining all actions that occur in the public-facing
     * side of the site.
     */
    require_once $this->plugin_path('public/class-kleinanzeigen-public.php');

    $this->loader = new Kleinanzeigen_Loader();
  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Kleinanzeigen_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function set_locale()
  {

    $plugin_i18n = new Kleinanzeigen_i18n();

    $this->loader->add_action('plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain'));
  }

  /**
   * Register all of the hooks related to the admin area functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_admin_hooks()
  {

    $admin = Kleinanzeigen_Admin::get_instance();
    $fns = wbp_fn();

    $this->loader->add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
    $this->loader->add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
    $this->loader->add_action('admin_init', array($fns, 'table_ajax_handler'), -888);

    // Catch ajax referer for cron
    // $this->loader->add_action('check_ajax_referer', array($fns, 'check_ajax_referer'), 10, 2);

    // Ajax actions
    $this->loader->add_action('wp_ajax__ajax_poll', array($fns, '_ajax_poll'));
    $this->loader->add_action('wp_ajax__ajax_cron', array($fns, '_ajax_cron'));
    $this->loader->add_action('wp_ajax__ajax_ping', array($fns, '_ajax_ping'));
    $this->loader->add_action('wp_ajax__ajax_status_mail', array($fns, '_ajax_status_mail'));
    
    $this->loader->add_action('wp_ajax_nopriv__ajax_poll', array($fns, '_ajax_poll'));

    $this->loader->add_filter('kleinanzeigen/template-path', array($admin, 'template_path'));
  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_public_hooks()
  {

    if (is_admin()) return;

    $plugin_public = new Kleinanzeigen_Public();

    $this->loader->add_action('init', array($plugin_public, 'register_shortcodes'));
    $this->loader->add_action('init', array($plugin_public, 'register_and_build'));
    $this->loader->add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
    $this->loader->add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));

    $this->loader->add_filter('kleinanzeigen/template-path', array($plugin_public, 'template_path'));
  }


  public function assets_url()
  {

    return trailingslashit($this->plugin_url('assets'));
  }

  public function plugin_url($path = '')
  {

    if (!$this->plugin_url) {
      $this->plugin_url = plugin_dir_url(__FILE__);
    }

    return $this->plugin_url . $path;
  }

  /**
   * Run the loader to execute all of the hooks with WordPress.
   *
   * @since    1.0.0
   */
  public function run()
  {
    $this->loader->run();
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @since     1.0.0
   * @return    string    The name of the plugin.
   */
  public function get_plugin_name()
  {
    return self::$plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @since     1.0.0
   * @return    Kleinanzeigen_Loader    Orchestrates the hooks of the plugin.
   */
  public function get_loader()
  {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @since     1.0.0
   * @return    string    The version number of the plugin.
   */
  public function get_version()
  {
    return self::$version;
  }

  public function get_plugin_author()
  {

    if (!defined('HOST')) {
      define('HOST', $_SERVER['HTTP_HOST']);
    }

    $parts = explode('.', HOST);
    $last = count($parts) - 1;
    $tld = $parts[$last];
    
    $author['email'] = "info@webpremiere.{$tld}";
    $author['name'] = self::$plugin_data->Name;

    return (object) $author;
  }

  public static function get_instance($file =  null)
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self($file);
    }
    return self::$instance;
  }
}
