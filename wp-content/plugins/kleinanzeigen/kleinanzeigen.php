<?php

/**
 * Plugin Name:     Kleinanzeigen.de
 * Plugin URI:      https://wordpress.org/plugins/kleinanzeigen.de/
 * Description:     Plugin Description
 * Author:          Axel Nitzschner
 * Author URI:      https://webpremiere.de/plugins/kleinanzeigen-import-plugin
 * Text Domain:     wbp-kleinanzeigen
 * Domain Path:     /languages
 * Version:         0.1.1
 *
 * @package         Kleinanzeigen
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}
define("KLEINANZEIGEN_URL", 'https://www.kleinanzeigen.de');
define("KLEINANZEIGEN_CUSTOMER_URL", KLEINANZEIGEN_URL . '/pro/Auto-Traktor-Bretschneider/ads/');
define("KLEINANZEIGEN_PER_PAGE", 25);

define("USE_AD_DUMMY_DATA", 0);

define("WC_TERMS", [
  'rent' => array('miete', 'mieten')
]);
define("WC_PRODUCT_LABELS", [
  'sale' => 'Aktionspreis',
]);
define("WC_COMMON_TAXONOMIES", [
  'rent' => 'Mietmaschinen',
  'aktionswochen' => 'Aktionswochen',
  'aktion' => 'Aktionen',
  'sale' => 'Aktionspreise',
  'featured' => 'Empfehlungen',
]);
define("WC_CUSTOM_PRODUCT_ATTRIBUTES", [
  'specials' => 'Merkmale',
  'rent' => 'Mietdauer',
]);

// If class `Webpremiere_Kleinanzeigen` doesn't exists yet.
if (!class_exists('Webpremiere_Kleinanzeigen')) {

  class Webpremiere_Kleinanzeigen
  {

    private static $instance = null;

    private $version = '0.0.1';

    private $plugin_path = null;

    private $plugin_url = null;

    public function __construct()
    {


      // Load the CX Loader.
      add_action('after_setup_theme', array($this, 'module_loader'), -20);

      // Internationalize the text strings used.
      add_action('init', array($this, 'lang'), -999);

      // Load files.
      add_action('init', array($this, 'init'), -999);

      // Dashboard Init
      add_action('init', array($this, 'dashboard_init'), -999);

      // Register activation and deactivation hook.
      register_activation_hook(__FILE__, array($this, 'activation'));
      register_deactivation_hook(__FILE__, array($this, 'deactivation'));
    }

    public function get_version()
    {
      return $this->version;
    }

    public static function get_instance()
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance) {
        self::$instance = new self;
      }
      return self::$instance;
    }

    public function init()
    {

      $this->load_files();
      $this->register();
    }

    public function dashboard_init()
    {
    }

    public function module_loader()
    {
    }

    public function load_files()
    {
      require $this->plugin_path('includes/class-kleinanzeigen.php');
      require $this->plugin_path('includes/class-kleinanzeigen-term-handler.php');
      require $this->plugin_path('includes/class-kleinanzeigen-register-wc-taxonomies.php');
    }

    public function register()
    {
      add_action('admin_init', array(wbp_th(), 'get_wc_taxonomies'));
      add_filter('woocommerce_product_data_store_cpt_get_products_query', array(wbp_th(), 'handle_cpt_get_products_query'), 10, 2);

      add_action('admin_init', array(wbp_rt(), 'create_terms'));
      add_action('admin_init', array(wbp_rt(), 'create_attribute_taxonomies'));
      add_action('init', array(wbp_rt(), 'create_taxonomy_product_labels'));
    }

    public function plugin_path($path = null)
    {

      if (!$this->plugin_path) {
        $this->plugin_path = trailingslashit(plugin_dir_path(__FILE__));
      }

      return $this->plugin_path . $path;
    }

    public function template_path()
    {

      return apply_filters('kleinanzeigen/template-path', 'templates/');
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

    public function get_template($name = null)
    {

      $template = locate_template($this->template_path() . $name);

      if (!$template) {
        $template = $this->plugin_path('templates/' . $name);
      }

      if (file_exists($template)) {
        return $template;
      } else {
        return false;
      }
    }

    public function lang()
    {
      load_plugin_textdomain('wbp-kleinanzeigen', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activation()
    {
    }

    public function deactivation()
    {
    }
  }
}

if (!function_exists('wbp_kleinanzeigen')) {

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return object
   */
  function wbp_kleinanzeigen()
  {
    return Webpremiere_Kleinanzeigen::get_instance();
  }
}

wbp_kleinanzeigen();
