<?php
if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kleinanzeigen_List_Table extends WP_List_Table
{
  private $hidden_columns = array(
    'id'
  );
  private $data;

  private static $INVISIBLE;
  private static $PRICE_DIFF;
  private static $CONTAINS_DEFAULT_CAT;
  private static $NO_CAT;

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-kleinanzeigen-ad',
      'plural' => 'wp-list-kleinanzeigen-ads',
      'ajax' => true
    ));

    self::$INVISIBLE = __('Not visible', 'astra-child');
    self::$PRICE_DIFF = __('Price deviation', 'astra-child');
    self::$CONTAINS_DEFAULT_CAT = __('Contains Default Category', 'astra-child');
    self::$NO_CAT = __('Invalid Category', 'astra-child');
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

  function render_head()
  {
    $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
    $data = wbp_get_json_data();

    if (isset($data)) {
      $categories = $data->categoriesSearchData;
    }

    // Total published products
    $args = array(
      'status' => 'publish',
      'limit' => -1,
    );
    $published_no_sku = wc_get_products(array_merge($args, array('sku_compare' => 'NOT EXISTS')));
    $published_has_sku = wc_get_products(array_merge($args, array('sku_compare' => 'EXISTS')));

    $products = array('publish' => array(), 'draft' => array(), 'unknown' => array(), 'other' => array(), 'no-sku' => array(), 'todos' => array());
    foreach ($this->items as $item) {

      $product_by_sku = wbp_get_product_by_sku($item->id);
      if (!$product_by_sku) {
        $product_by_title = wbp_get_product_by_title($item->title);
      }
      $product = $product_by_sku ? $product_by_sku : ($product_by_title ? $product_by_title : false);
      $product ? (!$product_by_sku ? $products['no-sku'][]  = $item->id : null) : null;
      $product ? (wbp_has_price_diff($item, $product) ? $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::$PRICE_DIFF) : null) : null;

      if ($product) {
        switch ($product->get_status()) {
          case 'publish':
            $products['publish'][] = $product;
            break;
          case 'draft':
            $products['draft'][] = $product;
            $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::$INVISIBLE);
            break;
          default:
            $products['other'][] = $product;
        }
        $default_cat_id = get_option('default_product_cat');
        $cat_terms = wbp_get_product_cats($product->get_id());
        $ids = wp_list_pluck($cat_terms, 'term_id');
        if (in_array($default_cat_id, $ids)) {
          $default_cat = get_term_by('id', $default_cat_id, 'product_cat');
          if (1 === count($ids)) {
            $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::$NO_CAT);
          } else {
            $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::$CONTAINS_DEFAULT_CAT . ' (' . $default_cat->name . ')');
          }
        }
      } else {
        $products['unknown'][] = $item->id;
        $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::$INVISIBLE);
      }
    }
    $items = $this->items;
    $tasks = $this->render_tasks();
    wbp_include_kleinanzeigen_template('header.php', false, compact('products', 'items', 'paged', 'categories', 'published_has_sku', 'published_no_sku', 'tasks'));
  }

  function render_tasks()
  {
    $tasks = array(array(
      'name' => 'invalid-ad',
      'priority' => 1,
      'product_ids' => array()
    ), array(
      'name' => 'invalid-price',
      'priority' => 1,
      'product_ids' => array()
    ), array(
      'name' => 'has-sku',
      'priority' => 0,
      'product_ids' => array()
    ), array(
      'name' => 'no-sku',
      'priority' => 0,
      'product_ids' => array()
    ));
    $ads = wbp_get_all_ads();
    $args = array(
      'status' => 'publish',
      'limit' => -1
    );
    $products = wc_get_products($args);
    foreach ($tasks as $key => $task) {
      $data = wbp_get_task_data($products, $ads, $task['name']);
      foreach ($data as $item) {
        $tasks[$key]['product_ids'][] = $item['product']->get_ID();
      }
    }
    return $tasks;
  }

  /**
   * @Override ajax_response method
   */

  function ajax_response()
  {

    check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

    $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
    $data = wbp_get_json_data(array('paged' => $paged));
    $this->setData($data);

    extract($this->_args);
    extract($this->_pagination_args, EXTR_SKIP);

    ob_start();
    $this->render_head();
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

    $response['rows'] = $rows;
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
      'status-start' => __(''),
      'image' => __('Bild'),
      'title' => __('Titel'),
      'date' => __('Änd.-Datum'),
      'price' => __('KA Preis'),
      'shop-price' => __('Shop Preis'),
      'shop-categories' => __('Kategorien'),
      'shop-brands' => __('Hersteller'),
      'shop-labels' => __('Labels'),
      'shop-featured' => '<i class="dashicons dashicons-star-filled" style="font-size: 1.3em; vertical-align: middle"></i>',
      'shop-actions' => __('Aktionen'),
      'shop-actions-import' => __('Import'),
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
    $this->data = $data;
    $this->items = $data->ads;
    // $this->items = $data;

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

    /**
     * We must check this in case we deal with faked page numbers (query ?paged)
     * 
     */
    if ($current_page <= ceil($total_items / $per_page)) {
      $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
    }

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

  function render_featured_column($product, $record)
  {
    if ($product) {
      $product_id = $product->get_id();
      $url = wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_feature_product&product_id=' . $product_id), 'woocommerce-feature-product');
      echo '<a href="' . esc_url($url) . '" aria-label="' . esc_attr__('Toggle featured', 'woocommerce') . '" id="feature-post-' . $product_id . '" data-post-id="' . $product_id . '" data-kleinanzeigen-id="' . $record->id . '">';
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

  function render_row($record)
  {
    list($columns, $hidden) = $this->get_column_info();

    $product_by_sku = wbp_get_product_by_sku($record->id);
    if (!$product_by_sku) {
      $product_by_title = wbp_get_product_by_title($record->title);
    }
    $product = $product_by_sku ?? $product_by_title ?? null;

    $diff_classes = array();
    $product_labels = array();
    $cat_terms = array();
    $brand_terms = array();

    if ($product) {

      $post_ID = $product->get_id();
      if (wbp_has_price_diff($record, $product)) {
        $diff_classes[] = 'diff price-diff';
      }

      $label_terms = wbp_get_product_labels($post_ID);
      if ($label_terms) {
        $product_labels = wp_list_pluck($label_terms, 'name');
      }
      $brand_terms = wbp_get_product_brands($post_ID);
      $cat_terms = wbp_get_product_cats($post_ID);

      $price = wp_kses_post($product->get_price_html());
    } else {

      $price = '<span class="na">&ndash;</span>';
    }

?>
    <tr id="ad-id-<?php echo $record->id ?>" <?php if (!empty($diff_classes)) { ?>class="<?php echo implode(' ', $diff_classes) ?>" <?php } ?>>
      <?php



      foreach ($columns as $column_name => $column_display_name) {
        $class = $column_name . ' column column-' . $column_name;
        if (in_array($column_name, $hidden)) $style = ' style="display:none;"';

        // Setup Kleinanzeigen actions
        if ($product) {
          $post_ID = $product->get_id();
          $editlink  = admin_url('post.php?action=edit&post=' . $post_ID);
          $deletelink  = get_delete_post_link($post_ID);
          $permalink = get_permalink($post_ID);
          $classes = "";
          $post_status = $product->get_status();
          $featured = $product->is_featured();
          switch ($post_status) {
            case 'draft':
              $status_name = __("Draft");
              break;
            case 'pending':
              $status_name = __("Pending Review");
              break;
            case 'trash':
              $status_name = __("Trash");
              $classes = "hidden";
              break;
            case 'publish':
              $status_name = __("Published");
              break;
          }

          if (!$product_by_sku) {
            $label = __('Connect', 'astra-child');
            $action = 'connect-' . $post_ID;
            $icon = 'admin-links';
            $type = 'button';
          } else {
            $label = __('Disconnect', 'astra-child');
            $action = 'disconnect-' . $post_ID;
            $icon = 'editor-unlink';
            $type = 'button';
          }
          $kleinanzeigen_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-actions.php', true, array_merge(compact('post_ID', 'record', 'post_status', 'classes'), array('connected' => $product_by_sku))) .
            wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-toggle-link-control.php', true, compact('post_ID', 'record', 'classes', 'label', 'action', 'icon', 'type')) .
            '</div>';
        }

        // Setup Shop actions
        if ($product_by_sku) {

          $status = $post_status === 'publish' ? 'connected-publish' : ($post_status === 'draft' ? 'connected-draft' : 'connected-unknown');
          $shop_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/common-links.php', true, compact('status_name', 'post_status', 'post_ID', 'record', 'classes', 'deletelink', 'editlink', 'permalink')) .
            wbp_include_kleinanzeigen_template('dashboard/toggle-publish-link.php', true, compact('post_status', 'post_ID', 'record')) .
            '</div>';
        } elseif ($product) {

          $status = $post_status === 'publish' ? 'disconnected-publish' : ($post_status === 'draft' ? 'disconnected-draft' : 'disconnected-unknown');
          $label = __('Verknüpfen');
          $action = 'connect-' . $post_ID;
          $icon = 'admin-links';
          $shop_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/common-links.php', true, compact('status_name', 'post_status', 'post_ID', 'record', 'classes', 'deletelink', 'editlink', 'permalink')) .
            wbp_include_kleinanzeigen_template('dashboard/toggle-publish-link.php', true, compact('post_status', 'post_ID', 'record')) .
            '</div>';
        } else {

          $status = 'invalid';
          $action = 'create';
          $icon = 'plus';
          $type = 'button';
          $kleinanzeigen_actions = wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-create-control.php', true, compact('record', 'action', 'icon', 'type'));
          $shop_actions = '';
        }


        switch ($column_name) {
          case "status-start": {
      ?>
              <td class="status <?php echo $status . ' ' . $class ?>"></td>
            <?php
              break;
            }
          case "image": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><a href="<?php echo wbp_get_kleinanzeigen_url($record->url) ?>" target="_blank"><img src="<?php echo stripslashes($record->image) ?>" width="128" /></a></div>
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
                <div class="column-content"><a href="<?php echo wbp_get_kleinanzeigen_url($record->url) ?>" target="_blank"><?php echo $record->title ?></a></div>
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
          case "shop-price": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content "><?php echo $price ?></div>
              </td>
            <?php
              break;
            }
          case "shop-categories": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo implode(', ', array_map(function ($term) use ($column_name) {
                                              return '<a href="' . home_url() . '/' . $term->taxonomy . '/' . $term->slug . '" target="_blank">' . $term->name . '</a>';
                                            }, $cat_terms !== false ? $cat_terms : [])); ?></div>
              </td>
            <?php
              break;
            }
          case "shop-brands": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo implode(', ', array_map(function ($term) use ($column_name) {
                                              return '<a href="' . home_url() . '/' . $term->taxonomy . '/' . $term->slug . '" target="_blank">' . $term->name . '</a>';
                                            }, $brand_terms !== false ? $brand_terms : [])); ?></div>
              </td>
            <?php
              break;
            }
          case "shop-labels": { ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo implode(', ', $product_labels) ?></div>
              </td>
            <?php
              break;
            }
          case "shop-featured": { ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo $this->render_featured_column($product, $record) ?></div>
              </td>
            <?php
              break;
            }
          case "shop-actions": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo $shop_actions ?></div>
              </td>
            <?php
              break;
            }
          case "shop-actions-import": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo $kleinanzeigen_actions ?></div>
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
            createPost,
            deletePost,
            importImages,
            importData,
            connect,
            disconnect,
            publishPost,
            featurePost
          } = ajax_object;

          const post_ID = "<?php echo $product ? $post_ID : '' ?>";
          const record = <?php echo json_encode($record) ?>;
          const kleinanzeigen_id = record.id;

          const publishEl = $(`#ad-id-${kleinanzeigen_id} #publish-post-${post_ID}`);
          $(publishEl).on('click', function(e) {
            e.preventDefault();

            publishPost(e);
          })

          const featuredEl = $(`#ad-id-${kleinanzeigen_id} #feature-post-${post_ID}`);
          $(featuredEl).on('click', function(e) {
            e.preventDefault();

            featurePost(e);
          })

          const connEl = $(`#ad-id-${kleinanzeigen_id} a[data-action^=connect-]`);
          $(connEl).on('click', function(e) {
            e.preventDefault();

            connect(e);
          })

          const disconnEl = $(`#ad-id-${kleinanzeigen_id} a[data-action^=disconnect-]`);
          $(disconnEl).on('click', function(e) {
            e.preventDefault();

            disconnect(e);
          })

          const createEl = $(`#ad-id-${kleinanzeigen_id} a[data-action=create]`);
          $(createEl).on('click', function(e) {
            e.preventDefault();

            createPost(e);
          })

          const impDataEl = $(`#ad-id-${kleinanzeigen_id} a[data-action^=import-data-]`);
          $(impDataEl).on('click', function(e) {
            e.preventDefault();

            importData(e);
          })

          const impImagesEl = $(`#ad-id-${kleinanzeigen_id} a[data-action^=import-images-]`);
          $(impImagesEl).on('click', function(e) {
            e.preventDefault();

            importImages(e);
          })

          const delEl = $(`#ad-id-${kleinanzeigen_id} a[data-action=delete-post]`)
          $(delEl).on('click', function(e) {
            e.preventDefault();

            if (!confirm('Du bist dabei das Produkt unwiderruflich zu löschen. Möchtest Du fortfahren?')) return;

            delEl.html('löschen...')
            deletePost(e);
          })

          const trEl = $(`#ad-id-${kleinanzeigen_id}`);
          trEl.on('data:parsed', (e) => {

            if ('create' === e.detail?.action) {
              impImagesEl.click();
            }

          })
        })
      </script>
    </tr>

<?php
  }
}
