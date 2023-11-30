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
class Kleinanzeigen
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
   * The current screen ID
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $version    The current screen ID
   */
  protected $screen_id;
  
  protected $plugin_path;
  protected $plugin_url;

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
    if (!defined('KLEINANZEIGEN_VERSION')) {
      $data = $this->get_plugin_data($file);
      define('KLEINANZEIGEN_VERSION', $data['Version']);
    }
    self::$version = KLEINANZEIGEN_VERSION;
    self::$plugin_name = strtolower($data['Name']);

    $this->load_dependencies();
    $this->set_locale();
    $this->define_admin_hooks();
    $this->define_public_hooks();
  }

  private function get_plugin_data($plugin_file)
  {

    $default_headers = array(
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

    return $plugin_data;
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

    $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
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

    // $admin = Kleinanzeigen_Admin::get_instance();
    $admin = new Kleinanzeigen_Admin();

    $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
    $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

    $this->loader->add_action('admin_init', wbp_fn(), 'table_ajax_handler', -888);
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

    $plugin_public = new Kleinanzeigen_Public();

    $this->loader->add_action('init', $plugin_public, 'register_shortcode');
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
  }

  public function plugin_path($path = null)
  {

    if (!$this->plugin_path) {
      $this->plugin_path = trailingslashit(dirname(__FILE__, 2));
    }

    return $this->plugin_path . $path;
  }

  public function assets_url()
  {

    return trailingslashit($this->plugin_url('assets'));
  }

  public function plugin_url($path = null)
  {

    if (!$this->plugin_url) {
      $this->plugin_url = plugin_dir_url(__FILE__);
    }

    return $this->plugin_url . $path;
  }

  public function include_template($path, $return_instead_of_echo = false, $extract_these = array())
  {
    if ($return_instead_of_echo) ob_start();

    $template_file = $this->get_template($path);

    if (!file_exists($template_file)) {
      error_log("Template not found: " . $template_file);
      echo __('Error:', 'kleinanzeigen') . ' ' . __('Template not found', 'kleinanzeigen') . " (" . $path . ")";
    } else {
      extract($extract_these);
      include $template_file;
    }

    if ($return_instead_of_echo) return ob_get_clean();
  }

  private function get_template($name = null)
  {

    $template = $this->template_path() . $name;
    return $this->plugin_path($template);
  }

  private function template_path()
  {
    $path = (is_admin() ? 'admin' : 'public') . '/partials';
    return apply_filters('kleinanzeigen/template-path', trailingslashit($path));
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

  public static function get_instance($file)
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self($file);
    }
    return self::$instance;
  }
}
