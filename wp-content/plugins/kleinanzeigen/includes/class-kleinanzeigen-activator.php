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

require_once plugin_dir_path(__FILE__) . 'class-kleinanzeigen-installer.php';

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
class Kleinanzeigen_Activator extends Kleinanzeigen_Installer
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
    global $wpdb;

    self::add_job_db();
    self::user_caps();
    // self::setup_environment(); // TODO
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
      'read_private_pages'     => false,
      'read_private_posts'     => false,
      'edit_posts'             => false,
      'edit_pages'             => false,
      'edit_published_posts'   => false,
      'edit_published_pages'   => false,
      'edit_private_pages'     => false,
      'edit_private_posts'     => false,
      'edit_others_posts'      => false,
      'edit_others_pages'      => false,
      'publish_posts'          => false,
      'publish_pages'          => false,
      'delete_posts'           => false,
      'delete_pages'           => false,
      'delete_private_pages'   => false,
      'delete_private_posts'   => false,
      'delete_published_pages' => false,
      'delete_published_posts' => false,
      'delete_others_posts'    => false,
      'delete_others_pages'    => false,
      'manage_categories'      => false,
      'manage_links'           => false,
      'moderate_comments'      => false,
      'upload_files'           => true,
      'export'                 => false,
      'import'                 => false,
      'list_users'             => true,
      'edit_theme_options'     => false,
    );

    foreach ($caps as $key => $granted) {

      $shop_manager->add_cap($key, $granted);
    }

    $capabilities = self::get_core_capabilities();

    $wp_roles = new WP_Roles();

    foreach ($capabilities as $cap_group) {
      foreach ($cap_group as $cap) {
        $wp_roles->add_cap('shop_manager', $cap);
        $wp_roles->add_cap('administrator', $cap);
      }
    }
  }

  private static function setup_environment()
  {

    self::register_taxonomies();
  }

  private static function add_job_db()
  {

    global $wpdb;
    global $kleinanzeigen_db_version;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id int(255) AUTO_INCREMENT,
      slug varchar(100) NOT NULL,
      type varchar(100) NOT NULL,
      uid varchar(100) UNIQUE NOT NULL,
      done tinyint(1) DEFAULT 0,
      created datetime NOT NULL,
      PRIMARY KEY  (id)
      ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('kleinanzeigen_db_version', $kleinanzeigen_db_version);
  }

  private static function register_taxonomies()
  {

    $s = register_taxonomy(
      'product_lapel',
      'product',
      array(
        'hierarchical'          => false,
        'update_count_callback' => '_wc_term_recount',
        'label'                 => __('Lapels', 'kleinanzeigen'),
        'labels'                => array(
          'name'                  => __('Product labels', 'kleinanzeigen'),
          'singular_name'         => __('Lapel', 'kleinanzeigen'),
          'menu_name'             => _x('Lapels', 'Admin menu name', 'kleinanzeigen'),
          'search_items'          => __('Search labels', 'kleinanzeigen'),
          'all_items'             => __('All labels', 'kleinanzeigen'),
          'parent_item'           => __('Parent label', 'kleinanzeigen'),
          'parent_item_colon'     => __('Parent label:', 'kleinanzeigen'),
          'edit_item'             => __('Edit label', 'kleinanzeigen'),
          'update_item'           => __('Update label', 'kleinanzeigen'),
          'add_new_item'          => __('Add new label', 'kleinanzeigen'),
          'new_item_name'         => __('New label name', 'kleinanzeigen'),
          'not_found'             => __('No labels found', 'kleinanzeigen'),
          'item_link'             => __('Product Lapel Link', 'kleinanzeigen'),
          'item_link_description' => __('A link to a product label.', 'kleinanzeigen'),
        ),
        'show_in_rest'          => true,
        'show_ui'               => true,
        "show_in_menu"          => true,
        "show_in_nav_menus"     => true,
        "show_admin_column"     => true,
        'query_var'             => true,
        'capabilities'          => array(
          'manage_terms' => 'manage_product_terms',
          'edit_terms'   => 'edit_product_terms',
          'delete_terms' => 'delete_product_terms',
          'assign_terms' => 'assign_product_terms',
        ),
        'rewrite'               => array(
          'slug'         => 'product_lapel',
          'with_front'   => true,
          'hierarchical' => false,
        ),
      )
    );
    register_taxonomy_for_object_type('product_lapel', 'product');
  }
}
