<?php
if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kleinanzeigen_List_Table extends WP_List_Table
{

  function __construct()
  {
    parent::__construct(array(
      'singular' => 'wp-list-kleinanzeigen-ad',
      'plural' => 'wp-list-kleinanzeigen-ads',
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

  protected const INVISIBLE = 'Nicht sichtbar';
  protected const PRICE_DIFF = 'Preisdifferenz';
  protected const WRONG_CAT = 'Kategorie';

  /**
   * @Build Head
   */

  function render_head($page = 1)
  {
    $data = $this->data;

    if (isset($data)) {
      $categories = $data->searchData;
      $total = 0;
      foreach ($categories as $category) {
        $total += $category->totalAds;
      }
    }

    // Total published products
    $args = array(
      'status' => 'publish',
      'limit' => -1
    );
    $published = wc_get_products($args);

    $products = array('publish' => array(), 'draft' => array(), 'unknown' => array(), 'other' => array(), 'no-sku' => array(), 'todos' => array());
    foreach ($this->items as $item) {

      $product_by_sku = wbp_get_product_by_sku($item->id);
      if (!$product_by_sku) {
        $product_by_title = wbp_get_product_by_title($item->title);
      }
      $product = $product_by_sku ?? $product_by_title ?? false;
      $product ? (!$product_by_sku ? $products['no-sku'][]  = $item->id : null) : null;
      $product ? ($this->has_price_diff($item, $product) ? $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::PRICE_DIFF) : null) : null;

      if ($product) {
        switch ($product->get_status()) {
          case 'publish':
            $products['publish'][] = $product;
            break;
          case 'draft':
            $products['draft'][] = $product;
            $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::INVISIBLE);
            break;
          default:
            $products['other'][] = $product;
        }
        $cats = get_the_terms($product->get_id(), 'product_cat');
        $slugs = wp_list_pluck($cats, 'slug');
        if (in_array(DEFAULT_CAT_SLUG, $slugs)) {
          $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::WRONG_CAT . ' (' . implode(', ', wp_list_pluck($cats, 'name')) . ')');
        }
      } else {
        $products['unknown'][] = $item->id;
        $products['todos'][] = array('title' => $item->title, 'reason' => Kleinanzeigen_List_Table::INVISIBLE);
      }
    }
    wbp_include_kleinanzeigen_template('header.php', false, array('data' => $data, 'page' => $page, 'pages' => 5, 'categories' => $categories, 'total' => $total, 'published' => $published, 'products' => $products, 'todos' => $products['todos']));
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
    $this->render_head($pageNum);
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
      'status-start' => __(''),
      'image' => __('Bild'),
      'title' => __('Titel'),
      'date' => __('Änd.-Datum'),
      'price' => __('KA Preis'),
      'shop-price' => __('Shop Preis'),
      'shop-categories' => __('Kategorien'),
      'shop-brands' => __('Hersteller'),
      'shop-actions' => __('Aktionen'),
      'shop-actions-import' => __('Import'),
      'shop-status-end' => __('')
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

  function has_price_diff($record, $product)
  {
    $regex = '/^([\d.]+)/';
    preg_match($regex, $record->price, $matches);
    $raw_kleinanzeigen_price = !empty($matches) ? str_replace('.', '', $matches[0]) : 0;
    $raw_shop_price = $product->get_price();

    return $raw_kleinanzeigen_price !== $raw_shop_price;
  }

  function setData($data)
  {
    $this->data = $data;
    $this->items = $data->ads;

    $this->prepare_items();
  }

  function render_row($record, $columns, $hidden)
  {
    $product_by_sku = wbp_get_product_by_sku($record->id);
    if (!$product_by_sku) {
      $product_by_title = wbp_get_product_by_title($record->title);
    }
    $product = $product_by_sku ?? $product_by_title ?? false;

    $diff_classes = array();
    $brands = array();
    $cats = array();

    if ($product) {
      $price = wc_price($product->get_price());
      if ($this->has_price_diff($record, $product)) $diff_classes[] = 'diff-price';
    } else {
      $price = '';
    }

?>
    <tr id="ad-id-<?php echo $record->id ?>" <?php if (!empty($diff_classes)) { ?>class="<?php echo implode(' ', $diff_classes) ?>" <?php } ?>>
      <?php



      foreach ($columns as $column_name => $column_display_name) {
        $class = $column_name . ' column column-' . $column_name;
        if (in_array($column_name, $hidden)) $style = ' style="display:none;"';

        // Setup Kleinanzeigen actions
        if ($product) {
          $post_id = $product->get_id();
          $editlink  = admin_url('post.php?action=edit&post=' . $post_id);
          $deletelink  = get_delete_post_link($post_id);
          $permalink = get_permalink($post_id);
          $classes = "";
          $post_status = $product->get_status();
          $brands = wp_list_pluck(wbp_get_product_brands($post_id), 'name');
          $cats = wp_list_pluck(wbp_get_product_cats($post_id), 'name');
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
            $label = __('Verknüpfen');
            $action = 'connect-' . $post_id;
            $icon = 'admin-links';
            $type = 'button';
          } else {
            $label = __('Loslösen');
            $action = 'disconnect-' . $post_id;
            $icon = 'editor-unlink';
            $type = 'button';
          }
          $kleinanzeigen_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-actions.php', true, array_merge(compact('post_id', 'record', 'post_status', 'classes'), array('connected' => $product_by_sku))) .
            wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-activate-control.php', true, compact('post_id', 'record', 'classes', 'label', 'action', 'icon', 'type')) .
            '</div>';
        }

        // Setup Shop actions
        if ($product_by_sku) {

          $status = $post_status === 'publish' ? 'connected-publish' : ($post_status === 'draft' ? 'connected-draft' : 'connected-unknown');
          $shop_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/common-links.php', true, compact('status_name', 'post_status', 'post_id', 'record', 'classes', 'deletelink', 'editlink', 'permalink')) .
            wbp_include_kleinanzeigen_template('dashboard/toggle-publish-link.php', true, compact('post_status', 'post_id', 'record')) .
            '</div>';
        } elseif ($product) {

          $status = $post_status === 'publish' ? 'disconnected-publish' : ($post_status === 'draft' ? 'disconnected-draft' : 'disconnected-unknown');
          $label = __('Verknüpfen');
          $action = 'connect-' . $post_id;
          $icon = 'admin-links';
          $shop_actions =
            '<div>' .
            wbp_include_kleinanzeigen_template('dashboard/common-links.php', true, compact('status_name', 'post_status', 'post_id', 'record', 'classes', 'deletelink', 'editlink', 'permalink')) .
            wbp_include_kleinanzeigen_template('dashboard/toggle-publish-link.php', true, compact('post_status', 'post_id', 'record')) .
            '</div>';
        } else {

          $status = 'invalid';
          $label = __('Anlegen');
          $action = 'create';
          $icon = 'plus';
          $type = 'button';
          $kleinanzeigen_actions = wbp_include_kleinanzeigen_template('dashboard/kleinanzeigen-activate-control.php', true, compact('record', 'label', 'action', 'icon', 'type'));
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
                <div class="column-content"><a href="<?php echo KLEINANZEIGEN_URL . stripslashes($record->url) ?>" target="_blank"><img src="<?php echo stripslashes($record->image) ?>" width="128" /></a></div>
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
                <div class="column-content"><a href="<?php echo KLEINANZEIGEN_URL . stripslashes($record->url) ?>" target="_blank"><?php echo $record->title ?></a></div>
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
                <div class="column-content"><?php echo implode(', ', $cats) ?></div>
              </td>
            <?php
              break;
            }
          case "shop-brands": {
            ?>
              <td class="<?php echo $class ?>">
                <div class="column-content"><?php echo implode(', ', $brands) ?></div>
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
            publishPost
          } = ajax_object;

          const post_id = "<?php echo $product ? $post_id : '' ?>";
          const record = <?php echo json_encode($record) ?>;
          const kleinanzeigen_id = record.id;

          const publishEl = $(`#ad-id-${kleinanzeigen_id} #publish-post-${post_id}`);
          $(publishEl).on('click', function(e) {
            e.preventDefault();

            publishPost(e);
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
          trEl.get()[0].addEventListener('data:action', (e) => {

            if ('create' === e.detail?.action) {
              $(impImagesEl).get()[0]?.addEventListener('data:action', function(e) {

                $('a[data-action=edit-post]', trEl)
                const href = $('a[data-action=edit-post]', trEl).attr('href');
                const {
                  data: {
                    imageCount
                  }
                } = e.detail;
                if (confirm(`Das Produkt "${record.title}" inklusive ${imageCount} Produktfotos wurde angelegt.\n\nJetzt zum Produkt gehen um Eigenschaften wie Produkt-Kategorie, Hersteller etc. hinzuzufügen?`)) {
                  const tab = window.open(href, 'edit-tab');
                  tab.focus();
                }

              });
              impImagesEl.data('bulk-action', 'create');
              impImagesEl.click();
            }

          })
        })
      </script>
    </tr>

<?php
  }
}