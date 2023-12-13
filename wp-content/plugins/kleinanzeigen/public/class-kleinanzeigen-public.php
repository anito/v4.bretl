<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/public
 * @author     Ben Shadle <benshadle@gmail.com>
 */
class Kleinanzeigen_Public extends Kleinanzeigen
{

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct()
  {
    self::$plugin_name = parent::$plugin_name;
    self::$version = parent::$version;
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
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

    wp_enqueue_style(self::$plugin_name, plugin_dir_url(__FILE__) . 'css/kleinanzeigen-public.css', array(), self::$version, 'all');
  }

  /**
   * Register the JavaScript for the public-facing side of the site.
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

    wp_enqueue_script(self::$plugin_name, plugin_dir_url(__FILE__) . 'js/kleinanzeigen-public.js', array('jquery'), self::$version, true);
  }

  public function template_path($path)
  {
    return trailingslashit('public');
  }

  function shortcode_timestamp($atts)
  {
    $atts = shortcode_atts(array(
      'timestamp' => wp_next_scheduled('kleinanzeigen_sync_price'),
    ), $atts, 'timestamp');

    return $this->include_template('timestamp.php', true, $atts);
  }

  public function register_shortcodes()
  {
    add_shortcode('timestamp', array($this, 'shortcode_timestamp'));
  }

  public function register_and_build()
  {
  }
}
