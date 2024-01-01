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

  public function remove_job($id)
  {

    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $query = "DELETE FROM {$table_name} WHERE id = {$id}";
    $success = $wpdb->query($query);
    if ($success) {
      return true;
    } else {
      return false;
    }
  }

  public function job_done($id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $query = "UPDATE {$table_name} SET done = 1 WHERE id = {$id}";
    $success = $wpdb->query($query);
    if ($success) {
      return true;
    } else {
      return false;
    }
  }

  public function get_todos($done = 1)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $todos = $wpdb->get_results(
      $wpdb->prepare("SELECT * FROM %1s WHERE done = %2d", $table_name, $done)
    );

    return $todos;
  }

  public function remove_jobs($ids)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    foreach ($ids as $id) {

      $wpdb->delete($table_name, array(
        'id' => $id
      ), array(
        '%d',
      ));
    }
  }
  public function insert_job($data)
  {

    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';
    $data = wp_parse_args($data, array('created' => current_time('mysql')));

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

    $query .= ")";

    $success = $wpdb->query($query);
    if ($success) {
      return $wpdb->insert_id;
    } else {
      return false;
    }
  }

  public function get_job($slug = '')
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $results = $wpdb->get_results(
      $wpdb->prepare("SELECT * FROM %1s WHERE slug = %2s", $table_name, $slug)
    );

    return $results;
  }

  public function get_jobs()
  {
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
