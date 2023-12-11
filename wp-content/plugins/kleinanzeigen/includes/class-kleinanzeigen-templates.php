<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Templates` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Templates')) {


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
      add_action('init', array($this, 'wbp_header_before'));
    }
    
    public function plugin_path($path = null)
    {

      if (!$this->plugin_path) {
        $this->plugin_path = trailingslashit(dirname(__FILE__, 2));
      }

      return $this->plugin_path . $path;
    }

    public function include_template($path, $return_instead_of_echo = false, $extract_these = array())
    {
      if ($return_instead_of_echo) ob_start();

      $template_file = $this->get_template($path);

      if (!file_exists($template_file)) {
        error_log("Template not found: " . $template_file);
        echo __('Error:', 'kleinanzeigen') . ' ' . __('Template not found', 'kleinanzeigen') . " (" . $path . ")";
      } else {
        extract($extract_these);
        include $template_file;
      }

      if ($return_instead_of_echo) return ob_get_clean();
    }

    private function get_template($name = null)
    {

      $template = $this->template_path() . 'partials/' . $name;
      return $this->plugin_path($template);
    }

    private function template_path()
    {

      return apply_filters('kleinanzeigen/template-path', null);
    }

    public function wbp_header_before() {
      if(current_user_can('edit_posts')) {
        add_action('astra_header_before', array($this, 'add_timestamp_template'));
      }
      add_action('kleinanzeigen_header_before', array($this, 'add_timestamp_template'));
    }

    public function add_timestamp_template() {
      $this->include_template('timestamp.php', false);
    }

    public function kleinanzeigen_header_before() {
      do_action('kleinanzeigen_header_before');
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
}

Kleinanzeigen_Templates::get_instance();
