<?php
require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/list-tables/class-wc-admin-list-table-products.php';


class Extended_WC_Admin_List_Table_Products extends WC_Admin_List_Table_Products
{
  protected $screen = '';
  protected $is_fetch = false;
  protected $is_quickedit = false;
  protected $is_pageload = false;

  function __construct()
  {
    $this->list_table_type = 'product';

    if(is_ajax()) {
      if (isset($_POST['ID'])) {
        $this->is_quickedit = true;
      } else {
        $this->is_fetch = true;
      }
    } else {
      $this->is_pageload = true;
    }

    // if (is_ajax()) {
    //   $render_wc = true;
    // } elseif (!function_exists('get_current_screen')) {
    //   $render_wc = false;
    // }

    // No screen ID on full page load (render build in woo rows)
    // if (!function_exists('get_current_screen')) {
    //   $render_wc = false;
    // }

    add_filter('list_table_primary_column', array($this, 'list_table_primary_column'), 10, 2);
    add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_columns'));
    add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_custom_columns'), 11);

    if (($this->is_fetch)) {
      add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_columns'), 10, 2);
      add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_custom_columns'), 11, 2);
    }

    if ($this->is_pageload | $this->is_quickedit) {
      add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_custom_columns'), 11, 2);
    }

  }

  function render_row($id)
  {
    $wp_list_table = _get_list_table('WP_Posts_List_Table', array('screen' => 'edit-product'));
    $wp_list_table->display_rows(array(get_post($id)), 0);
  }

  function define_custom_columns($columns)
  {
    $date = $columns['date'];
    $price = $columns['price'];
    $tag = $columns['product_tag'];
    $cat = $columns['product_cat'];
    $featured = $columns['featured'];

    unset($columns['sku']);
    unset($columns['price']);
    unset($columns['date']);
    unset($columns['featured']);
    unset($columns['product_cat']);
    unset($columns['product_tag']);

    $columns['price'] = $price;
    $columns['product_label'] = 'Labels';
    $columns['featured'] = $featured;
    $columns['date'] = $date;
    $columns['product_tag'] = $tag;
    $columns['product_cat'] = $cat;
    $columns['sku'] = 'KA';
    $columns['sync'] = 'KA Aktionen';
    
    return $columns;
  }

  function render_custom_columns($column_name, $post_id)
  {
    $post = get_post($post_id);
    $product = wc_get_product($post_id);
    if ($product) {

      $post_status = $post->post_status;
      $sku = $product->get_sku($post_id);
      $sku = is_numeric($sku) ? $sku : false;

      $product_label_terms = get_the_terms($post_id, 'product_label');
      $product_labels = array_map(function ($item) {
        return $item->name;
      }, !is_wp_error($product_label_terms) ? ($product_label_terms ? $product_label_terms : []) : []);

    } else return 0;

    switch ($column_name) {
      case 'product_label': {
          echo implode(', ', $product_labels);
          break;
        }
      case 'sku': {
          echo '<a href="' . esc_html(wbp_get_kleinanzeigen_url($sku)) . '" target="_blank"</a>';
          break;
        }
      case 'sync': {
?>
          <div class="sync-column-content">
            <div id="import-kleinanzeigen-data-wbp-action-<?php echo $post_id ?>" style="flex: 1;">
              <span class="spinner"></span>
              <a id="import-kleinanzeigen-data-<?php echo $post_id ?>" disabled name="import-kleinanzeigen-data" data-kleinanzeigen-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-kleinanzeigen-data button button-primary button-small"><?php echo __('Import Data', 'astra-child') ?></a>
            </div>
            <div id="import-kleinanzeigen-images-wbp-action-<?php echo $post_id ?>" style="flex: 1;">
              <span class="spinner"></span>
              <span class="kleinanzeigen-images-wrapper" style="display: flex;">
                <a id="import-kleinanzeigen-images-<?php echo $post_id ?>" disabled name="import-kleinanzeigen-images" data-kleinanzeigen-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-kleinanzeigen-images button button-primary button-small"><?php echo __('Import Images', 'astra-child') ?></a>
                <a id="delete-kleinanzeigen-images-<?php echo $post_id ?>" name="delete-kleinanzeigen-images" data-kleinanzeigen-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="delete-kleinanzeigen-images button button-primary button-small">
                  <i class="dashicons dashicons-trash" style="font-size: 1.3em; vertical-align: middle"></i>
                </a>
              </span>
            </div>
            <div id="disconnect-kleinanzeigen-wbp-action-' ?><?php echo $sku ?>">
              <span class="spinner"></span>
              <a id="disconnect-kleinanzeigen-<?php echo $sku ?>" disabled href="<?php echo admin_url(('admin-ajax.php?sku=') . $sku . '&action=disconnect') ?>" data-action="disconnect-<?php echo $post_id ?>" data-kleinanzeigen-id="<?php echo $sku ?>" class="button button-primary button-small"><i class="dashicons dashicons-editor-unlink"></i><?php echo __('Disconnect', 'astra-child') ?></a>
            </div>
            <div id="publish-post-wbp-action-<?php echo $post_id ?>" class="publish-column-content">
              <span class="spinner"></span>
              <a id="publish-post-<?php echo $post_id ?>" name="publish-post" data-post-status="<?php echo $post_status ?>" data-post-id="<?php echo $post_id ?>" class="publish-post button button-secondary button-small"><i class="dashicons dashicons-<?php echo ($post_status === 'publish') ?  'hidden' : 'visibility' ?>"></i><?php echo ($post_status === 'publish') ?  __('Hide', 'astra-child') : __('Publish') ?></a>
            </div>
          </div>
          <script>
            jQuery(document).ready(($) => {
              const {
                publishPost,
                importData,
                importImages,
                deleteImages,
                disconnect
              } = ajax_object;

              const sku = '<?php echo $sku; ?>';
              const post_status = '<?php echo $post_status ?>';

              const tr = document.getElementById('post-<?php echo $post_id ?>');
              const publishButton = tr?.querySelector('#publish-post-<?php echo $post_id ?>');
              const importDataButton = tr?.querySelector('#import-kleinanzeigen-data-<?php echo $post_id ?>');
              const importImagesButton = tr?.querySelector('#import-kleinanzeigen-images-<?php echo $post_id ?>');
              const deleteImagesButton = tr?.querySelector('#delete-kleinanzeigen-images-<?php echo $post_id ?>');
              const disconnectButton = tr?.querySelector('#disconnect-kleinanzeigen-<?php echo $sku ?>');

              setTimeout(() => {
                publishButton?.addEventListener("click", publishPost);
                deleteImagesButton?.addEventListener("click", deleteImages);

                post_status == 'draft' && publishButton?.removeAttribute('disabled');

                if (sku) {
                  importDataButton?.addEventListener("click", importData);
                  importImagesButton?.addEventListener("click", importImages);
                  disconnectButton?.addEventListener("click", disconnect);

                  importDataButton?.removeAttribute('disabled');
                  importImagesButton?.removeAttribute('disabled');
                  disconnectButton?.removeAttribute('disabled');
                }
              }, 200)
            });
          </script>
<?php
          break;
        }
    }
  }
}
