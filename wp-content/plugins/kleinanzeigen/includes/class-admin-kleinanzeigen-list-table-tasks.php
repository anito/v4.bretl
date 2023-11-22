<?php

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kleinanzeigen_Tasks_List_Table extends WP_List_Table
{
  private $vars;

  private $hidden_columns = array(
    'id'
  );

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-task-kleinanzeigen-ad',
      'plural' => 'wp-list-task-kleinanzeigen-ads',
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
    wp_nonce_field('ajax-custom-task-list-nonce', '_ajax_custom_task_list_nonce');

    /**
     * Adds field order and orderby
     */
    echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
    echo '<input type="hidden" id="page" name="page" value="' . $this->_pagination_args['orderby'] . '" />';

    parent::display();
  }

  function render_featured_column($product, $task_type)
  {
    if ($product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();
      $url = wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_feature_product&product_id=' . $product_id), 'woocommerce-feature-product');
      echo '<a href="' . esc_url($url) . '" aria-label="' . esc_attr__('Toggle featured', 'woocommerce') . '" id="task-feature-post-' . $product_id . '" data-kleinanzeigen-id="' . $sku . '" data-post-id="' . $product_id . '" data-screen="modal" data-task-type="' . $task_type . '">';
      if ($product->is_featured()) {
        echo '<span class="wc-featured tips" data-tip="' . esc_attr__('Yes', 'woocommerce') . '"><i class="dashicons dashicons-star-filled" style="font-size: 1.3em; vertical-align: middle"></i></span>';
      } else {
        echo '<span class="wc-featured not-featured tips" data-tip="' . esc_attr__('No', 'woocommerce') . '"><i class="dashicons dashicons-star-empty" style="font-size: 1.3em; vertical-align: middle"></i></span>';
      }
      echo '</a>';
    } else {
      echo '-';
    }
  }

  function set_vars($task_type)
  {
    switch ($task_type) {
      case 'invalid-ad':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Invalid links', 'wbp-kleinanzeigen'),
              'subheader' => 'Liste von verknüpften Produkten deren Anzeige nicht mehr auffindbar ist'
            )
          ),
          'footer-template' => array(
            'template' => 'footer-invalid-ad',
            'args' => array()
          )
        );
        break;
      case 'invalid-price':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Price deviations', 'wbp-kleinanzeigen'),
              'subheader' => 'Auflistung von Produkten mit Preisunterschied zur verknüpften Anzeige'
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      case 'has-sku':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Linked products', 'wbp-kleinanzeigen'),
              'subheader' => 'Auflistung verknüpfter Produkte'
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      case 'no-sku':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Unlinked products', 'wbp-kleinanzeigen'),
              'subheader' => 'Auflistung von Produkten ohne Verknüpfung'
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      case 'featured':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Featured products', 'wbp-kleinanzeigen'),
              'subheader' => 'Auflistung von empfohlenen Produkten'
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      default:
        $vars = array(
          'header-template' => array(
            'template' => 'modal-header',
            'args' => array(
              'header' => __('Title', 'wbp-kleinanzeigen'),
              'subheader' => ''
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
    }
    $this->vars = wp_parse_args($vars, array(
      'header-template' => array(
        'template' => 'blank',
        'args' => array()),
      'footer-template' => array(
        'template' => 'blank',
        'args' => array()
      )));
  }

  function render_header()
  {
    extract($this->vars['header-template']);
    wbp_ka()->include_kleinanzeigen_template("/dashboard/{$template}.php", false, $args);
  }

  function render_footer()
  {
    extract($this->vars['footer-template']);
    wbp_ka()->include_kleinanzeigen_template("/dashboard/{$template}.php", false, $args);
  }

  /**
   * @Override ajax_response method
   */

  function ajax_response()
  {
    check_ajax_referer('ajax-custom-task-list-nonce', '_ajax_custom_task_list_nonce');

    $task_type = isset($_GET['task_type']) ? $_GET['task_type'] : '';

    $ads = wbp_ka()->get_all_ads();
    $args = array(
      'status' => 'publish',
      'limit' => -1
    );
    $products = wc_get_products($args);
    $data = wbp_ka()->get_task_list_items($products, $ads, $task_type);
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
      'featured' => '<i class="dashicons dashicons-star-filled" style="font-size: 1.3em; vertical-align: middle"></i>',
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
    $per_page = KLEINANZEIGEN_PER_PAGE;

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

  function render_row($item)
  {
    // Extracts $products | $task_type | $record
    extract($item);

    list($columns, $hidden) = $this->get_column_info();

    $post_ID = $product->get_id();
    $sku = $product->get_sku();
    $product_by_sku = $this->get_product_by_sku($sku);
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

        switch ($task_type) {
          case 'invalid-ad':
            $published = 'publish' === $product->get_status();
            $disabled['deactivate'] = !$sku && !$published;
            $disabled['disconnect'] = !$sku;
            $disabled['delete'] = !$post_ID;
            $label = array(
              'disconnect' => $product->get_sku() ? __('Autonomous', 'wbp-kleinanzeigen') : __('Disconnected', 'wbp-kleinanzeigen'),
              'deactivate' => (!$disabled['deactivate']) ? __('Hide', 'wbp-kleinanzeigen') : __('Disconnected', 'wbp-kleinanzeigen'),
              'delete' => (!$disabled['delete']) ? __('Delete', 'wbp-kleinanzeigen') : __('Deleted', 'wbp-kleinanzeigen')
            );
            $actions = wbp_ka()->include_kleinanzeigen_template('/dashboard/invalid-sku-result-row.php', true, compact('post_ID', 'sku', 'label', 'task_type', 'disabled'));
            break;
          case 'invalid-price':
            $price = Utils::extract_kleinanzeigen_price($ka_price);
            $shop_price = $product->get_price();
            $disabled = $price === $shop_price;
            $label = $price !== $shop_price ? __('Accept KA price', 'wbp-kleinanzeigen') : __('KA Price accepted', 'wbp-kleinanzeigen');
            $actions = wbp_ka()->include_kleinanzeigen_template('/dashboard/invalid-price-result-row.php', true, compact('post_ID', 'sku', 'price', 'label', 'task_type', 'disabled'));
            break;
          case 'has-sku':
          case 'no-sku':
          case 'featured':
            $label = $post_status === 'publish' ? __('Hide', 'wbp-kleinanzeigen') : __('Publish');
            $actions = wbp_ka()->include_kleinanzeigen_template('/dashboard/toggle-publish-result-row.php', true, compact('post_ID', 'sku', 'label', 'task_type'));
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
          case "featured": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php echo $this->render_featured_column($product, $task_type) ?></div>
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
          const {
            featurePost
          } = ajax_object;

          const table = $('.wp-list-task-kleinanzeigen-ads');
          const post_ID = "<?php echo $product ? $post_ID : '' ?>";

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

          const featuredEl = $(`#task-feature-post-${post_ID}`);
          $(featuredEl).on('click', function(e) {
            e.preventDefault();

            featurePost(e);
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
