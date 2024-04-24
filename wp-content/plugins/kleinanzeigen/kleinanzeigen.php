<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wplauncher.com
 * @since             1.0.0
 * @package           Kleinanzeigen
 *
 * @wordpress-plugin
 * Plugin Name:       Kleinanzeigen
 * Plugin URI:        https://www.wplauncher.com
 * Description:       Synchronize Kleinanzeigen to your Woocommerce Shop
 * Version:           0.4.7
 * Author:            Axel Nitzschner
 * Author URI:        https://webpremiere.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kleinanzeigen
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

/**
 * Plugin specific constants
 * 
 */
define('KLEINANZEIGEN_URL', 'https://www.kleinanzeigen.de');
define('WC_TERMS', [
  'rent' => array('miete', 'mieten')
]);
define('WC_PRODUCT_LABELS', [
  'sale' => 'Aktionspreis',
]);
define('WC_COMMON_TAXONOMIES', [
  'rent' => 'Mietmaschinen',
  'aktionswochen' => 'Aktionswochen',
  'aktion' => 'Aktionen',
  'sale' => 'Aktionspreise',
  'featured' => 'Empfehlungen',
]);
define('WC_CUSTOM_PRODUCT_ATTRIBUTES', [
  'specials' => 'Merkmale',
  'rent' => 'Mietdauer',
]);
define('ALLOW_DUPLICATE_TITLES', true);
define('USE_AD_DUMMY_DATA', 0);
define('ITEMS_PER_PAGE', 25);

global $kleinanzeigen_db_version;
$kleinanzeigen_db_version = '1.0';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kleinanzeigen-activator.php
 */
function activate_kleinanzeigen()
{
  require_once plugin_dir_path(__FILE__) . 'includes/class-kleinanzeigen-activator.php';
  Kleinanzeigen_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kleinanzeigen-deactivator.php
 */
function deactivate_kleinanzeigen()
{
  require_once plugin_dir_path(__FILE__) . 'includes/class-kleinanzeigen-deactivator.php';
  Kleinanzeigen_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_kleinanzeigen');
register_deactivation_hook(__FILE__, 'deactivate_kleinanzeigen');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-kleinanzeigen-templates.php';
require plugin_dir_path(__FILE__) . 'includes/class-kleinanzeigen.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 * @return Kleinanzeigen
 */
if (!function_exists('wbp_ka')) {

  function wbp_ka(): Kleinanzeigen
  {

    return Kleinanzeigen::get_instance(__FILE__);
  }
}
wbp_ka()->run();
