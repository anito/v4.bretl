<?php

/**
 * Kleinanzeigen plugin file.
 *
 * @package Kleinanzeigen\Admin
 */

class Kleinanzeigen_Database
{
  public static $instance;

  public function __construct()
  {
  }
  public function create_table($table_name)
  {
  }

  public function insert_job($data)
  {

    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';
    $data = wp_parse_args($data, array('created' => current_time('mysql')));
    
    $update = "count = VALUES (count);";
    $query = '';

    $query .= "INSERT INTO {$table_name} (";

    $c = 0;
    foreach ($data as $key => $val) {
      if ($c === 0) {
        $query .= "{$key}";
      } else {
        $query .= ", {$key}";
      }
      $c++;
    }

    $query .= ") VALUES (";

    $c = 0;
    foreach ($data as $key => $val) {
      if ($c === 0) {
        $query .= "'{$val}'";
      } else {
        $query .= ", '{$val}'";
      }
      $c++;
    }

    $query .= ") ON DUPLICATE KEY UPDATE ";
    $query .= $update;

    $success = $wpdb->query($query);
    if ($success) {
      return true;
    } else {
      return false;
    }
  }

  public function get_job($slug = '') {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $results = $wpdb->get_results(
      $wpdb->prepare("SELECT * FROM %1s WHERE name = %2s", $table_name, $slug)
    );

    return $results;
  }

  public function get_jobs() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $results = $wpdb->get_results("SELECT * FROM {$table_name}");

    return $results;
  }

  public static function get_instance()
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }
}

if (!function_exists('wbp_db')) {

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return Kleinanzeigen_Database
   */
  function wbp_db(): Kleinanzeigen_Database
  {
    return Kleinanzeigen_Database::get_instance();
  }
}

wbp_db();
