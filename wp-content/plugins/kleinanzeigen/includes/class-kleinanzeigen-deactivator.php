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

require_once plugin_dir_path(__FILE__) . 'class-kleinanzeigen-installer.php';

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
class Kleinanzeigen_Deactivator extends Kleinanzeigen_Installer
{

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    1.0.0
   */
  public static function deactivate()
  {

    self::user_caps();
    self::remove_job_db();
    self::delete_options();
    self::unschedule_cron_jobs();
  }

  private static function delete_options()
  {
    delete_option('kleinanzeigen_account_name');
    delete_option('kleinanzeigen_items_per_page');
    delete_option('kleinanzeigen_crawl_interval');
    delete_option('kleinanzeigen_schedule_new_ads');
    delete_option('kleinanzeigen_schedule_invalid_ads');
    delete_option('kleinanzeigen_send_cc_mail_on_new_ad');
    delete_option('kleinanzeigen_schedule_invalid_prices');
  }

  private static function unschedule_cron_jobs()
  {
    wp_unschedule_hook('kleinanzeigen_sync_price');
    wp_unschedule_hook('kleinanzeigen_activate_url');
    wp_unschedule_hook('kleinanzeigen_deactivate_url');
    wp_unschedule_hook('kleinanzeigen_renamed_ads');
    wp_unschedule_hook('kleinanzeigen_invalid_ad_action');
    wp_unschedule_hook('kleinanzeigen_create_new_products');
  }

  private static function user_caps()
  {

    $shop_manager = get_role('shop_manager');

    $caps = array(
      'level_9'                => true,
      'level_8'                => true,
      'level_7'                => true,
      'level_6'                => true,
      'level_5'                => true,
      'level_4'                => true,
      'level_3'                => true,
      'level_2'                => true,
      'level_1'                => true,
      'level_0'                => true,
      'read'                   => true,
      'read_private_pages'     => true,
      'read_private_posts'     => true,
      'edit_posts'             => true,
      'edit_pages'             => true,
      'edit_published_posts'   => true,
      'edit_published_pages'   => true,
      'edit_private_pages'     => true,
      'edit_private_posts'     => true,
      'edit_others_posts'      => true,
      'edit_others_pages'      => true,
      'publish_posts'          => true,
      'publish_pages'          => true,
      'delete_posts'           => true,
      'delete_pages'           => true,
      'delete_private_pages'   => true,
      'delete_private_posts'   => true,
      'delete_published_pages' => true,
      'delete_published_posts' => true,
      'delete_others_posts'    => true,
      'delete_others_pages'    => true,
      'manage_categories'      => true,
      'manage_links'           => true,
      'moderate_comments'      => true,
      'upload_files'           => true,
      'export'                 => true,
      'import'                 => true,
      'list_users'             => true,
      'edit_theme_options'     => true,
    );

    foreach ($caps as $key => $granted) {

      $shop_manager->add_cap($key, $granted);
    }

    $capabilities = self::get_core_capabilities();

    $wp_roles = new WP_Roles();

    foreach ($capabilities as $cap_group) {
      foreach ($cap_group as $cap) {
        $wp_roles->add_cap('shop_manager', $cap);
      }
    }
  }

  private static function remove_job_db()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");

    delete_option('kleinanzeigen_db_version');
  }
}
