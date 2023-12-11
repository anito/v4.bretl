<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/includes
 * @author     Ben Shadle <benshadle@gmail.com>
 */
class Kleinanzeigen_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$wpdb->query("DROP TABLE IF EXISTS $table_name;");

		delete_option('kleinanzeigen_db_version');
	}

}
