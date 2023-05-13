<?php
if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Ebay_List_Table extends WP_List_Table
{

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-ebay-ad',
      'plural' => 'wp-list-ebay-ads',
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
    wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

    /**
     * Adds field order and orderby
     */
    echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
    echo '<input type="hidden" id="page" name="page" value="' . $this->_pagination_args['orderby'] . '" />';

    parent::display();
  }

  /**
   * @Build Head
   */

  function display_head($page = 1)
  {
    $data = $this->data;

    if (isset($data)) {
      $categories = $data->searchData;
      $total = 0;
      foreach ($categories as $category) {
        $total += $category->totalAds;
      }
    }

    wbp_include_ebay_template('header.php', false, array('data' => $data, 'page' => $page, 'pages' => 5, 'categories' => $categories, 'total' => $total));
  }

  /**
   * @Override ajax_response method
   */

  function ajax_response()
  {

    check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

    $pageNum = !empty($_REQUEST['pageNum']) ? $_REQUEST['pageNum'] : 1;
    $data = wbp_get_json_data($pageNum);
    $this->setData($data);

    extract($this->_args);
    extract($this->_pagination_args, EXTR_SKIP);

    ob_start();
    $this->display_head($pageNum);
    $head = ob_get_clean();

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
    $response['head'] = $head;

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
      'image' => __('Bild'),
      'title' => __('Titel'),
      'date' => __('Datum'),
      'price' => __('Preis'),
      'status' => __('Shop Status'),
      'description' => __('Description'),
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

    list($columns, $hidden) = $this->get_column_info();

    $records = $this->items;
    foreach ($records as $record) {
      $this->render_row($record, $columns, $hidden);
    }
  }

  private $hidden_columns = array(
    'id'
  );

  private $data = array();
  private $pageNum = 1;

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

    $data = $this->items;

    function usort_reorder($a, $b)
    {

      $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'date';
      $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
      $result = strcmp($a->{$orderby}, $b->{$orderby});
      return ('asc' === $order) ? $result : -$result;
    }
    // usort($data, 'usort_reorder');

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

  function setData($data)
  {
    $this->data = $data;
    $this->items = $data->ads;

    $this->prepare_items();
  }

  function getRecord($id)
  {
    foreach ($this->items as $item) {
      if ($item->id === $id) {
        $record = $item;
      }
    }
    return $record;
  }

  function render_row($record, $columns, $hidden)
  { ?>
    <tr id="ad-id-<?php echo $record->id ?>">
      <?php
      foreach ($columns as $column_name => $column_display_name) {
        $class = $column_name . ' column-' . $column_name;
        if (in_array($column_name, $hidden)) $style = ' style="display:none;"';

        $product_by_sku = wbp_get_product_by_sku($record->id);
        if (!$product_by_sku) {
          $product_by_title = wbp_get_product_by_title($record->title);
        }
        $product = $product_by_sku ?? $product_by_title ?? false;

        if ($product) {
          $editlink  = admin_url('post.php?action=edit&post=' . $product->get_id());
          $deletelink  = get_delete_post_link($product->get_id()) . '&screen=ebay';
          $permalink = get_permalink($product->get_id());
          $classes = "";
          $status = $product->get_status();
          switch ($status) {
            case 'draft':
              $stat = __("Draft");
              break;
            case 'pending':
              $stat = __("Pending Review");
              break;
            case 'trash':
              $stat = __("Trash");
              $classes = "hidden";
              break;
            case 'publish':
            case 'publish':
              $stat = __("Published");
              break;
          }
        }

        if ($product_by_sku) {

          $stat =
            '<div><div>' . $stat . '</div>' .
            wbp_include_ebay_template('dashboard/common-links.php', true, compact('product', 'record', 'classes', 'deletelink', 'editlink', 'permalink')) .
            '<div>' .
            '<a class="' . $classes . '" href="' . admin_url(('admin-ajax.php?sku=') . $record->id  . '&action=import_data') . '" data-screen="ebay" data-post-id="' . $product->get_id() . '" data-ebay-id="' . $record->id . '" data-action="import-data-' . $record->id . '">' . __('Daten importieren', 'wbp') . '</a>' .
            '</div>' .
            '<div>' .
            '<a class="' . $classes . '" href="' . admin_url(('admin-ajax.php?sku=') . $record->id  . '&action=import_images') . '" data-post-id="' . $product->get_id() . '" data-ebay-id="' . $record->id . '" data-action="import-images-' . $record->id . '">' . __('Fotos importieren', 'wbp') . '</a>' .
            '</div>' .
            '<div>' .
            '<a class="' . $classes . '" href="' . admin_url(('admin-ajax.php?sku=') . $record->id  . '&action=disconnect') . '" data-ebay-id="' . $record->id . '" data-action="disconnect-' . $product->get_id() . '">' . __('Verknüpfung aufheben', 'wbp') . '</a>' .
            '</div>' .
            '</div>';
        } elseif ($product) {

          $label = __('Verknüpfen');
          $action = 'connect-' . $product->get_id();

          $stat = wbp_include_ebay_template('dashboard/import-data-button.php', true, compact('product', 'record', 'label', 'action')) .
            wbp_include_ebay_template('dashboard/common-links.php', true, compact('product', 'record', 'classes', 'deletelink', 'editlink', 'permalink'));
        } else {

          $label = __('Anlegen');
          $action = 'create';
          $stat = wbp_include_ebay_template('dashboard/import-data-button.php', true, compact('record', 'label', 'action'));
        }


        switch ($column_name) {
          case "image": {
      ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><a href="<?php echo EBAY_URL . stripslashes($record->url) ?>" target="_blank"><img src="<?php echo stripslashes($record->image) ?>" width="128" /></a></div>
              </td>
            <?php
              break;
            }
          case "id": {
            ?>
              <td <?php echo $class ?>>
                <div class="column-content center"><?php echo $record->id ?></div>
              </td>
            <?php
              break;
            }
          case "title": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><a href="<?php echo EBAY_URL . stripslashes($record->url) ?>" target="_blank"><?php echo $record->title ?></a></div>
              </td>
            <?php
              break;
            }
          case "date": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content center"><?php echo $record->date ?></div>
              </td>
            <?php
              break;
            }
          case "price": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php echo $record->price ?></div>
              </td>
            <?php
              break;
            }
          case "status": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo $stat ?></div>
              </td>
            <?php
              break;
            }
          case "description": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo $record->description ?></div>
              </td>
      <?php
              break;
            }
        }
      }
      ?>
      <script>
        jQuery(document).ready(($) => {
          const {
            deletePost,
            importImages,
            importData,
            connectEbay,
            disconnectEbay,
          } = ajax_object;

          const ebay_id = "<?php echo $record->id ?>";

          const connEl = $(`#ad-id-${ebay_id} a[data-action^=connect-]`);
          $(connEl).on('click', function(e) {
            e.preventDefault();

            connectEbay(e);
          })

          const disconnEl = $(`#ad-id-${ebay_id} a[data-action^=disconnect-]`);
          $(disconnEl).on('click', function(e) {
            e.preventDefault();

            disconnectEbay(e);
          })

          const impDataEl = $(`#ad-id-${ebay_id} a[data-action=create], #ad-id-${ebay_id} a[data-action^=import-data-]`);
          $(impDataEl).on('click', function(e) {
            e.preventDefault();

            importData(e);
          })

          const impImagesEl = $(`#ad-id-${ebay_id} a[data-action^=import-images-]`);
          $(impImagesEl).on('click', function(e) {
            e.preventDefault();

            importImages(e);
          })

          const delEl = $(`#ad-id-${ebay_id} a[data-action=delete-post]`)
          $(delEl).on('click', function(e) {
            e.preventDefault();

            if (!confirm('Du bist dabei das Produkt unwiderruflich zu löschen. Möchtest Du fortfahren?')) return;

            delEl.html('löschen...')
            deletePost(e);
          })
        })
      </script>
    </tr>

<?php
  }
}
