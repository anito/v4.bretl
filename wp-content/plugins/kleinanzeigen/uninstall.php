<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function kleinanzeigen_delete_options()
  {
    delete_option('kleinanzeigen_account_name');
    delete_option('kleinanzeigen_items_per_page');
    delete_option('kleinanzeigen_crawl_interval');
    delete_option('kleinanzeigen_schedule_new_ads');
    delete_option('kleinanzeigen_schedule_invalid_ads');
    delete_option('kleinanzeigen_send_cc_mail');
    delete_option('kleinanzeigen_schedule_invalid_prices');
  }

 function kleinanzeigen_uninstall_table()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");

    delete_option('kleinanzeigen_db_version');
  }


kleinanzeigen_uninstall_table();
kleinanzeigen_delete_options();
