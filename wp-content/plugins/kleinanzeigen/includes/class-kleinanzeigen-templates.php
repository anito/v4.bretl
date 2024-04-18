<?php

// If this file is called directly, abort.
if (!defined('WPINC'))
{
  die();
}

// If class `Kleinanzeigen_Templates` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Templates'))
{


  class Kleinanzeigen_Templates
  {

    private static $instance = null;
    protected $plugin_path;
    protected $plugin_url;

    public function __construct()
    {

      $this->register();
    }

    public function register()
    {
      add_action('display_timestamp', array($this, 'add_templates'));
    }

    public function plugin_path($path = null)
    {

      if (!$this->plugin_path)
      {
        $this->plugin_path = trailingslashit(dirname(__FILE__, 2));
      }

      return $this->plugin_path . $path;
    }

    public function include_template($path, $return_instead_of_echo = false, $extract_these = array())
    {
      $not_found = false;
      if ($return_instead_of_echo) ob_start();

      $template_file = $this->get_template($path);

      if (!file_exists($template_file))
      {

        $not_found = true;

        // Switch template path from `admin/` to `public/`
        $removed = remove_filter('kleinanzeigen/template-path', array(Kleinanzeigen_Admin::get_instance(), 'template_path'));
        if ($removed)
        {
          // Recursive call
          return $this->include_template($path, $return_instead_of_echo, $extract_these);
        }
      }

      if ($not_found)
      {
        error_log("Template not found: " . $template_file);
        echo __('Error:', 'kleinanzeigen') . ' ' . __('Template not found', 'kleinanzeigen') . " (" . $path . ")";
      }

      extract($extract_these);
      include $template_file;

      if ($return_instead_of_echo) return ob_get_clean();
    }

    private function get_template($name = null)
    {

      $template = $this->template_path() . 'partials/' . $name;
      return $this->plugin_path($template);
    }

    private function template_path()
    {
      $path = apply_filters('kleinanzeigen/template-path', null);
      return $path ?? trailingslashit('public');
    }

    public function add_templates()
    {
      $this->include_template('timestamp.php', false);
      $this->include_template('js.php', false);
    }

    public function display_status_report_link()
    {
      return $this->include_template('status-report-link.php', true);
    }

    public function display_timestamp()
    {
      do_action('display_timestamp');
    }

    public static function get_instance()
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance)
      {
        self::$instance = new self;
      }
      return self::$instance;
    }
  }
}

Kleinanzeigen_Templates::get_instance();
