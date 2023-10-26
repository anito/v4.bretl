<?php
if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kleinanzeigen_Scan_List_Table extends WP_List_Table
{
  private $hidden_columns = array(
    'id'
  );

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-scan-kleinanzeigen-ad',
      'plural' => 'wp-list-scan-kleinanzeigen-ads',
      'ajax' => true
    ));
  }

  /**
   * @Override of display method
   */

  function display()
  {

    /**
     * Adds a nonce field
     */
    wp_nonce_field('ajax-custom-scan-list-nonce', '_ajax_custom_scan_list_nonce');

    /**
     * Adds field order and orderby
     */
    echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
    echo '<input type="hidden" id="page" name="page" value="' . $this->_pagination_args['orderby'] . '" />';

    parent::display();
  }

  function render_header($args = array())
  {
    $defaults = array(
      'template' => '__blank__',
      'subheader' => '__SUBHEADER__'
    );
    $options = wp_parse_args($args, $defaults);
    wbp_include_kleinanzeigen_template('/dashboard/' . $options['template'] . '.php', false, $options);
  }

  function render_footer($args = array())
  {
    $defaults = array(
      'template' => '__blank__'
    );
    $options = wp_parse_args($args, $defaults);
    wbp_include_kleinanzeigen_template('/dashboard/' . $options['template'] . '.php', false, $options);
  }

  /**
   * @Override ajax_response method
   */

  function ajax_response()
  {

    check_ajax_referer('ajax-custom-scan-list-nonce', '_ajax_custom_scan_list_nonce');
    $scan_type = isset($_GET['scan_type']) ? $_GET['scan_type'] : '';

    $ads = wbp_get_all_ads();
    $args = array(
      'status' => 'publish',
      'limit' => -1
    );
    $products = wc_get_products($args);
    $data = wbp_get_scan_data($products, $ads, $scan_type);
    $this->setData($data);

    extract($this->_args);
    extract($this->_pagination_args, EXTR_SKIP);

    ob_start();
    if (!empty($_REQUEST['no_placeholder']))
      $this->display_rows();
    else
      $this->display_rows_or_placeholder();
    $rows = ob_get_clean();

    ob_start();
    $this->print_column_headers();
    $headers = ob_get_clean();

    ob_start();
    $this->pagination('top');
    $pagination_top = ob_get_clean();

    ob_start();
    $this->pagination('bottom');
    $pagination_bottom = ob_get_clean();

    $response = array('rows' => $rows);
    $response['pagination']['top'] = $pagination_top;
    $response['pagination']['bottom'] = $pagination_bottom;
    $response['column_headers'] = $headers;

    if (isset($total_items))
      $response['total_items_i18n'] = sprintf(_n('1 item', '%s items', $total_items), number_format_i18n($total_items));

    if (isset($total_pages)) {
      $response['total_pages'] = $total_pages;
      $response['total_pages_i18n'] = number_format_i18n($total_pages);
    }

    die(json_encode($response));
  }

  /**
   * Define the columns that are going to be used in the table
   * @return array $columns, the array of columns to use with the table
   */

  function get_columns()
  {
    return array(
      'id' => 'ID',
      'status-start' => '',
      'image' => __('Bild'),
      'title' => __('Titel'),
      'ka-price' => __('KA Preis'),
      'shop-price' => __('Shop Preis'),
      'actions' => __('Aktionen'),
    );
  }

  /**
   * Decide which columns to activate the sorting functionality on
   * @return array $sortable, the array of columns that can be sorted by the user
   */
  function get_sortable_columns()
  {
    return array();
  }

  /**
   * Display the rows of records in the table
   * @return string, echo the markup of the rows
   */
  function display_rows()
  {
    $records = $this->items;
    foreach ($records as $record) {
      $this->render_row($record);
    }
  }

  function setData($data)
  {
    $this->items = $data;
    $this->prepare_items();
  }

  /**
   * Prepare the table with different parameters, pagination, columns and table elements
   */
  function prepare_items()
  {
    /**
     * How many records for page do you want to show?
     */
    $per_page = 25;

    /**
     * Define of column_headers. It's an array that contains:
     * columns of List Table
     * hiddens columns of table
     * sortable columns of table
     * optionally primary column of table
     */
    $columns  = $this->get_columns();
    $hidden   = $this->hidden_columns;
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);

    $data = $this->has_items() ? $this->items : array();

    /**
     * Get current page calling get_pagenum method
     */
    $current_page = $this->get_pagenum();

    $total_items = count($data);

    $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

    $this->items = $data;

    /**
     * Call to _set_pagination_args method for informations about
     * total items, items for page, total pages and ordering
     */
    $this->set_pagination_args(
      array(
        'total_items'  => $total_items,
        'per_page'      => $per_page,
        'total_pages'  => ceil($total_items / $per_page),
        'orderby'      => !empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
        'order'        => !empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
      )
    );
  }

  function render_row_($id)
  {
    $wp_list_table = _get_list_table('WP_Posts_List_Table', array('screen' => 'modal'));
    $wp_list_table->display_rows(array(get_post($id)), 0);
  }

  function render_row($item)
  {
    // Extracts $products | $scan_type | $record
    extract($item);

    list($columns, $hidden) = $this->get_column_info();

    $post_ID = $product->get_id();
    $sku = $product->get_sku();
    $product_by_sku = wbp_get_product_by_sku($sku);
    $shop_price_html = wp_kses_post($product->get_price_html());
    $ka_price = isset($record) ? $record->price : '-';

?>
    <tr id="<?php echo $post_ID ?>">
      <?php

      foreach ($columns as $column_name => $column_display_name) {
        $class = $column_name . ' column column-' . $column_name;
        if (in_array($column_name, $hidden)) $class .= ' hidden';

        // Setup Actions
        $post_ID = $product->get_id();
        $title = $product->get_title();
        $editlink  = admin_url('post.php?action=edit&post=' . $post_ID);
        $deletelink  = get_delete_post_link($post_ID);
        $permalink = get_permalink($post_ID);
        $classes = "";
        $post_status = $product->get_status();
        $image = get_the_post_thumbnail_url($post_ID);

        switch ($scan_type) {
          case 'invalid-ad':
            $published = 'publish' === $product->get_status();
            $disabled['deactivate'] = !$sku && !$published;
            $disabled['disconnect'] = !$sku;
            $label = array(
              'deactivate' => (!$disabled['deactivate']) ? __('Hide', 'astra-child') : __('Disconnected', 'astra-child'),
              'disconnect' => $product->get_sku() ? __('Don\'t hide', 'astra-child') : __('Disconnected', 'astra-child')
            );
            $actions = wbp_include_kleinanzeigen_template('/dashboard/invalid-sku-result-row.php', true, compact('post_ID', 'sku', 'label', 'scan_type', 'disabled'));
            break;
          case 'invalid-price':
            $price = wbp_extract_kleinanzeigen_price($ka_price);
            $shop_price = $product->get_price();
            $disabled = $price === $shop_price;
            $label = $price !== $shop_price ? __('Accept price', 'astra-child') : __('Price accepted', 'astra-child');
            $actions = wbp_include_kleinanzeigen_template('/dashboard/invalid-price-result-row.php', true, compact('post_ID', 'sku', 'price', 'label', 'scan_type', 'disabled'));
            break;
          default:
        }
        switch ($post_status) {
          case 'publish':
            $status = $sku ? 'connected-publish' : 'disconnected-publish';
            break;
          case 'draft':
            $status = $sku ? 'connected-draft' : 'disconnected-draft';
            break;
          default:
            $status = $sku ? 'connected-unknown' : 'disconnected-unknown';
            break;
        }

        switch ($column_name) {
          case "status-start": {
      ?>
              <td class="status <?php echo $class . ' ' . $status ?>"></td>
            <?php
              break;
            }
          case "image": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><img src="<?php echo $image ?>" width="90" /></div>
              </td>
            <?php
              break;
            }
          case "title": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><a href="<?php echo $permalink ?>" target="_blank"><?php echo $title ?></a></div>
              </td>
            <?php
              break;
            }
          case "ka-price": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php echo $ka_price ?></div>
              </td>
            <?php
              break;
            }
          case "shop-price": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php echo $shop_price_html ?></div>
              </td>
            <?php
              break;
            }
          case "actions": {
            ?>
              <td class="<?php echo $class ?>" style="vertical-align: middle;">
                <div class="column-content"><?php echo $actions ?></div>
              </td>
            <?php
              break;
            }
          case "shop-status-end": {
            ?>
              <td class="status <?php echo $status . ' ' . $class ?>"></td>
      <?php
              break;
            }
        }
      }
      ?>
      <script>
        jQuery(document).ready(($) => {
          const table = $('.wp-list-scan-kleinanzeigen-ads');

          $('.deactivate-all').on('click', function(e) {
            const el = $(e.target);
            window.dispatchEvent(
              new CustomEvent("deactivate:all", {
                detail: {
                  data
                },
              })
            );
            el.on('deactivated:all', handleLabel)
          })

          $('.disconnect-all').on('click', function(e) {
            const el = $(e.target);
            window.dispatchEvent(
              new CustomEvent("disconnect:all", {
                detail: {
                  data
                },
              })
            );
            el.on('deactivated:all', handleLabel)
          })

          // Scan Price Results
          $('.fix-price-all', table).on('click', function(e) {
            const el = $(e.target);
            window.dispatchEvent(
              new CustomEvent("fixprice:all", {
                detail: {
                  data
                },
              })
            );
            el.on('fixed-price:all', handleLabel);
          })

          function handleLabel(e) {
            const el = e.target;
            const label = $(el).data('success-label');
            if (label) {
              $(el).html(label);
            }
            $(el).addClass('disabled')
              .off('data:parsed', handleLabel);
          }
        })
      </script>
    </tr>

<?php
  }
}
