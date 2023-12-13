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
class Kleinanzeigen_Activator
{

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    1.0.0
   */
  public static function activate()
  {

    self::add_job_db();
    self::user_caps();
  }

  private static function user_caps()
  {

    $shop_manager = get_role('shop_manager');

    $caps = array(
      'level_9'                => false,
      'level_8'                => false,
      'level_7'                => false,
      'level_6'                => false,
      'level_5'                => false,
      'level_4'                => false,
      'level_3'                => false,
      'level_2'                => false,
      'level_1'                => false,
      'level_0'                => false,
      'read'                   => true,
      'read_private_pages'     => true,
      'read_private_posts'     => true,
      'edit_posts'             => true,
      'edit_pages'             => false,
      'edit_published_posts'   => true,
      'edit_published_pages'   => false,
      'edit_private_pages'     => true,
      'edit_private_posts'     => true,
      'edit_others_posts'      => true,
      'edit_others_pages'      => false,
      'publish_posts'          => true,
      'publish_pages'          => false,
      'delete_posts'           => true,
      'delete_pages'           => false,
      'delete_private_pages'   => true,
      'delete_private_posts'   => true,
      'delete_published_pages' => false,
      'delete_published_posts' => true,
      'delete_others_posts'    => true,
      'delete_others_pages'    => true,
      'manage_categories'      => false,
      'manage_links'           => false,
      'moderate_comments'      => false,
      'upload_files'           => true,
      'export'                 => true,
      'import'                 => true,
      'list_users'             => true,
      'edit_theme_options'     => false,
    );

    foreach($caps as $key => $granted) {
      
      $shop_manager->add_cap($key, $granted);

    }
  }

  private static function add_job_db()
  {

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
