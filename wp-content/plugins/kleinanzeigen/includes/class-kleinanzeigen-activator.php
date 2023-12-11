<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 * @author     Ben Shadle <benshadle@gmail.com>
 */
class Kleinanzeigen_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		global $kleinanzeigen_db_version;

		$table_name = $wpdb->prefix . 'kleinanzeigen_jobs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
  slug varchar(100) NOT NULL,
	count mediumint(9) DEFAULT 0,
  created datetime NOT NULL,
  PRIMARY KEY  (slug)
) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('kleinanzeigen_db_version', $kleinanzeigen_db_version);
	}

}
