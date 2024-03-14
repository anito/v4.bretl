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

  public function unregister_job($id)
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
      $wpdb->prepare("SELECT * FROM $table_name WHERE done = %d", $done)
    );

    return $todos;
  }

  public function clear_jobs() {
    $jobs = $this->get_jobs();
    foreach($jobs as $job) {
      $this->remove_job($job->id);
    }
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

  public function register_job($data)
  {

    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';
    $data = wp_parse_args($data, array('created' => current_time('mysql')));

    $update = "uid = VALUES (uid);";
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

    if ($wpdb->query($query)) {
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
      $wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug)
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

  public function get_job_by_uid($uid, $type = '')
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kleinanzeigen_jobs';

    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE type='%s' AND uid='%s'", $type, $uid));

    return $id;
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
