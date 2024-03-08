<?php

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kleinanzeigen_Tasks_List_Table extends WP_List_Table
{
  private $vars;
  private static $DEFAULT_CAT_ID;
  private $hidden_columns = array(
    'id'
  );

  function __construct()
  {
    parent::__construct(array(
      'singular'  => 'wp-list-kleinanzeigen-task',
      'plural'    => 'wp-list-kleinanzeigen-tasks',
      'ajax'      => true
    ));
    self::$DEFAULT_CAT_ID = (int) get_option('default_product_cat');
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Orphaned products', 'kleinanzeigen'),
              'subheader' => sprintf(__('For the following products the ad on which they where based on can no longer be traced and must therefore be manually disconnected. To do so choose on of the given actions. After an action has been taken the product will no longer be listetd here. You can also automate this process in %s.', 'kleinanzeigen'), '<a href="' . admin_url('/admin.php?page=' . wbp_ka()->get_plugin_name() . '-settings') . '" target="_blank">' . __('Settings', 'kleinanzeigen') . '</a>')
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Price deviations', 'kleinanzeigen'),
              'subheader' => __('List of products whoes prices deviating from their ads', 'kleinanzeigen')
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      case 'invalid-cat':
        $default_cat = get_term_by('id', self::$DEFAULT_CAT_ID, 'product_cat');
        $subheader = '';
        $subheader .= __('Please assign the below listed products an appropriate category in order to get located by your visitors.', 'kleinanzeigen');
        $vars = array(
          'header-template' => array(
            'template' => 'modal-table-header',
            'args' => array(
              'header' => sprintf(__('Products of category "%1$s"', 'kleinanzeigen'), $default_cat->name),
              'subheader' => $subheader
            )
          ),
          'footer-template' => array(
            'template' => 'blank',
            'args' => array()
          )
        );
        break;
      case 'drafts':
        $vars = array(
          'header-template' => array(
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Drafts', 'kleinanzeigen'),
              'subheader' => __('List of non published products', 'kleinanzeigen')
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Linked products', 'kleinanzeigen'),
              'subheader' => __('List of linked products', 'kleinanzeigen')
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Autonomous products', 'kleinanzeigen'),
              'subheader' => __('List of published products not related to an ad', 'kleinanzeigen')
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Featured products', 'kleinanzeigen'),
              'subheader' => __('List of featured products', 'kleinanzeigen')
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
            'template' => 'modal-table-header',
            'args' => array(
              'header' => __('Title', 'kleinanzeigen'),
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
        'args' => array()
      ),
      'footer-template' => array(
        'template' => 'blank',
        'args' => array()
      )
    ));
  }

  function render_header()
  {
    extract($this->vars['header-template']);
    wbp_ka()->include_template("/dashboard/{$template}.php", false, $args);
  }

  function render_footer()
  {
    extract($this->vars['footer-template']);
    wbp_ka()->include_template("/dashboard/{$template}.php", false, $args);
  }

  /**
   * @Override ajax_response method
   */

  function ajax_response()
  {
    check_ajax_referer('ajax-custom-task-list-nonce', '_ajax_custom_task_list_nonce');

    $task_type = isset($_GET['task_type']) ? $_GET['task_type'] : '';

    $args = array(
      'status' => array('publish'),
      'limit' => -1
    );
    $items = wbp_fn()->build_tasks($task_type, $args)['items'];
    $this->setData($items);

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
      'status-start'    => '',
      'image'           => __('Image', 'kleinanzeigen'),
      'title'           => __('Title', 'kleinanzeigen'),
      'ka-price'        => __('KA Price', 'kleinanzeigen'),
      'shop-price'      => __('Shop Price', 'kleinanzeigen'),
      'shop-categories' => __('Categories', 'kleinanzeigen'),
      'featured'        => '<i class="dashicons dashicons-star-filled" style="font-size: 1.3em; vertical-align: middle"></i>',
      'actions'         => __('Actions', 'kleinanzeigen'),
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
    $per_page = get_option('kleinanzeigen_items_per_page', 25);

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
        'per_page'     => $per_page,
        'total_pages'  => ceil($total_items / $per_page),
        'orderby'      => !empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
        'order'        => !empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
      )
    );
  }

  public function render_empty_row()
  {
    echo '<tr style="display: none;"></tr>';
  }

  function render_terms($terms)
  {
    echo implode(', ', array_map(function ($term) {
      $classes = array();
      if ('product_cat' === $term->taxonomy && self::$DEFAULT_CAT_ID === $term->term_id) {
        $classes[] = 'todo';
      }
      return '<a class="' . implode(' ', $classes) . '" href="' . home_url() . '/' . $term->taxonomy . '/' . $term->slug . '" target="_blank">' . $term->name . '</a>';
    }, $terms !== false ? $terms : []));
  }

  function render_row($item)
  {
    // Extracts $products | $task_type | $record
    extract($item);

    if (!$product) {
      return $this->render_empty_row();
    }

    list($columns, $hidden) = $this->get_column_info();

    $post_ID = $product->get_id();
    $sku = $product->get_sku();
    $product_by_sku = $this->get_product_by_sku($sku);
    $shop_price_html = wp_kses_post($product->get_price_html());
    $ka_price = isset($record) ? $record->price : '-';
    // $date = isset($record) ? $record->date : '';
    $diff_classes = array();
    if (!is_null($record) && wbp_fn()->has_price_diff($record, $product)) {
      $diff_classes[] = 'diff';
      $diff_classes[] = 'price-diff';
    }
    if (is_null($record)) {
      $diff_classes[] = 'broken';
    }

?>
    <tr id="<?php echo $post_ID ?>" <?php if (!empty($diff_classes)) { ?>class="<?php echo implode(' ', $diff_classes) ?>" <?php } ?>>
      <?php

      // Setup Actions
      $post_ID = $product->get_id();
      $title = $product->get_name();
      $editlink  = admin_url('post.php?action=edit&post=' . $post_ID);
      $deletelink  = get_delete_post_link($post_ID);
      $permalink = get_permalink($post_ID);
      $classes = "";
      $post_status = $product->get_status();
      $image = get_the_post_thumbnail_url($post_ID);
      $cat_terms = get_the_terms($post_ID, 'product_cat');

      switch ($task_type) {
        case 'invalid-ad':
          $published = 'publish' === $product->get_status();
          $disabled['disconnect'] = !$sku;
          $disabled['activate-deactivate'] = !$sku;
          $disabled['delete'] = !$post_ID;
          $label = array(
            'disconnect'  => $product->get_sku() ? __('Keep', 'kleinanzeigen') : __('Disconnected', 'kleinanzeigen'),
            'activate-deactivate'  => $product->get_sku() ? ($published ? __('Hide', 'kleinanzeigen') : __('Publish', 'kleinanzeigen')) : __('Disconnected', 'kleinanzeigen'),
            'delete'      => (!$disabled['delete']) ? __('Delete', 'kleinanzeigen') : __('Deleted', 'kleinanzeigen')
          );
          $actions = wbp_ka()->include_template('/dashboard/invalid-sku-result-row.php', true, compact('post_ID', 'sku', 'label', 'task_type', 'disabled', 'published'));
          break;
        case 'invalid-price':
          $price = Utils::extract_kleinanzeigen_price($ka_price);
          $shop_price = $product->get_price();
          $disabled = $price === $shop_price;
          $label = $price !== $shop_price ? __('Accept KA price', 'kleinanzeigen') : __('KA Price accepted', 'kleinanzeigen');
          $actions = wbp_ka()->include_template('/dashboard/invalid-price-result-row.php', true, compact('post_ID', 'sku', 'price', 'label', 'task_type', 'disabled'));
          break;
        case 'invalid-cat':
        case 'drafts':
        case 'has-sku':
        case 'no-sku':
        case 'featured':
          $label = array(
            'publish' => $post_status === 'publish' ? __('Hide', 'kleinanzeigen') : __('Publish'),
            'edit'    => __('Edit', 'kleinanzeigen')
          );
          $actions = wbp_ka()->include_template('/dashboard/toggle-publish-result-row.php', true, compact('post_ID', 'sku', 'label', 'task_type'));
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

      foreach ($columns as $column_name => $column_display_name) {
        $class = $column_name . ' column column-' . $column_name;
        if (in_array($column_name, $hidden)) $class .= ' hidden';

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
          case "shop-categories": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php $this->render_terms($cat_terms); ?></a></div>
              </td>
            <?php
              break;
            }
          case "featured": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php $this->render_featured_column($product, $task_type) ?></div>
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
          } = KleinanzeigenAjax;

          const table = $('.wp-list-kleinanzeigen-tasks');
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
