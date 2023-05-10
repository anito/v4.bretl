<?php

use Automattic\WooCommerce\Internal\Admin\Orders\Edit;

class Ebay_List_Table extends WP_List_Table
{

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-ebay-ad',
      'plural' => 'wp-list-ebay-ads',
      'ajax' => false
    ));
  }

  /**
   * Define the columns that are going to be used in the table
   * @return array $columns, the array of columns to use with the table
   */
  function get_columns()
  {
    return array(
      'image' => __('Bild'),
      'id' => __('ID'),
      'title' => __('Titel'),
      'date' => __('Datum'),
      'status' => __('Status'),
      'description' => __('Description'),
    );
  }

  /**
   * Decide which columns to activate the sorting functionality on
   * @return array $sortable, the array of columns that can be sorted by the user
   */
  public function get_sortable_columns()
  {
    return array(
      'title' => 'title',
    );
  }

  /**
   * Display the rows of records in the table
   * @return string, echo the markup of the rows
   */
  function display_rows()
  {

    $records = $this->items;
    list($columns, $hidden) = $this->get_column_info();

    if (!empty($records)) {
      foreach ($records as $record) {

        echo '<tr id="ad-id-' . $record->id . '">';
        foreach ($columns as $column_name => $column_display_name) {

          $class = 'class="' . $column_name . ' column-' . $column_name . '"';
          $style = "";
          if (in_array($column_name, $hidden)) $style = ' style="display:none;"';
          $attributes = $class . $style;

          $product = wbp_get_product_by_sku($record->id);
          if(isset($product)) {
            $classes = "";
            $status = $product->get_status();
            switch($status) {
              case 'draft':
                $stat = __("Draft");
                break;
              case 'pending':
                $stat = __("Pending Review");
                break;
              case 'trash':
                $stat = __("Trash");
                $classes="hidden";
                break;
              case 'publish':
              case 'publish':
                $stat = __("Published");
                break;
              }
            $editlink  = admin_url('post.php?action=edit&post=' . $product->id);
            $stat = '<div><div>' . $stat . '</div><a class="' . $classes . '" href="' . $editlink . '">' . __('Edit') . '</div></div>';
            
          } else {
            $stat = wbp_include_ebay_template('dashboard/import-data.php', true, array('sku' => $record->id));
          }

          switch ($column_name) {
            case "image":
              echo '<td ' . $attributes . '><div class="column-content"><a href="' . EBAY_URL . stripslashes($record->url) . '" target="_blank"><img src="' . stripslashes($record->image) . '" width="128" /></a></div></td>';
              break;
            case "id":
              echo '<td ' . $attributes . '><div class="column-content center">' . stripslashes($record->id) . '</div></td>';
              break;
            case "title":
              echo '<td ' . $attributes . '><div class="column-content"><a href="' . EBAY_URL . stripslashes($record->url) . '" target="_blank">' . stripslashes($record->title) . '</a></div></td>';
              break;
            case "date":
              echo '<td ' . $attributes . '><div class="column-content center">' . $record->date . '</div></td>';
              break;
            case "status":
              echo '<td ' . $attributes . '><div class="column-content">' . $stat . '</div></td>';
              break;
            case "description":
              echo '<td ' . $attributes . '><div class="column-content">' . $record->description . '</div></td>';
              break;
          }
        }
        echo '</tr>';
      }
    }
  }

  /**
   * Prepare the table with different parameters, pagination, columns and table elements
   */
  function prepare_items()
  {
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = array();
    $this->_column_headers = array($columns, $hidden, $sortable);
  }

  function fetchItems() {
    $results = [];
    $pages = wbp_get_json_data();
    foreach ($pages as $key => $page) {
      if (!empty($page)) {
        $ads = json_decode($page);
        $results = $ads->ads;
      }
    }
    $this->items = $results;
    $this->prepare_items();
    return $results;
  }
}
