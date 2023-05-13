<?php
require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/list-tables/class-wc-admin-list-table-products.php';


class Extended_WC_Admin_List_Table_Products extends WC_Admin_List_Table_Products
{
  protected $screen = '';

  function __construct()
  {
    $this->list_table_type = 'product';

    $render_wc = true;

    // Ajax load
    if (is_ajax() && isset($_POST['ID'])) {
      // When quick editing woocommerce renders its own rows
      $render_wc = false;
    }

    // Full page load - woocommerce renders its own rows
    if (!function_exists('get_current_screen')) {
      $render_wc = false;
    }

    if ($render_wc) {
      // add_filter('request', array($this, 'request_query'));
      add_filter('list_table_primary_column', array($this, 'list_table_primary_column'), 10, 2);
      add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_columns'));
      add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_columns'), 10, 2);
    }
    add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_custom_columns'), 11);
    add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_custom_columns'), 1, 2);
  }

  function render_row($id)
  {
    $wp_list_table = _get_list_table('WP_Posts_List_Table', array('screen' => 'edit-product'));
    $wp_list_table->display_rows(array(get_post($id)), 0);
  }

  function define_custom_columns($columns)
  {
    unset($columns['sku']);
    $columns['sku'] = 'Ebay';
    $columns['sync'] = 'Ebay Aktionen';
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
    } else return 0;

    switch ($column_name) {
      case 'sku': {
          echo '<a href="' . esc_html(wbp_get_ebay_url($sku)) . '" target="_blank"</a>';
          break;
        }
      case 'sync': {
?>
          <div class="sync-column-content">
            <div id="import-ebay-data-wbp-action-<?php echo $post_id ?>" style="flex: 1;">
              <span class="spinner"></span>
              <a id="import-ebay-data-<?php echo $post_id ?>" disabled name="import-ebay-data" data-ebay-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-ebay-data button button-primary button-small" style="">Daten importieren</a>
            </div>
            <div id="import-ebay-images-wbp-action-<?php echo $post_id ?>" style="flex: 1;">
              <span class="spinner"></span>
              <span class="ebay-images-wrapper" style="display: flex;">
                <a id="import-ebay-images-<?php echo $post_id ?>" disabled name="import-ebay-images" data-ebay-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-ebay-images button button-primary button-small" style="">Fotos importieren</a>
                <a id="delete-ebay-images-<?php echo $post_id ?>" name="delete-ebay-images" data-ebay-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="delete-ebay-images button button-primary button-small" style="">
                  <i class="dashicons dashicons-trash" style="font-size: 1.3em; vertical-align: middle"></i>
                </a>
              </span>
            </div>
            <div id="publish-post-wbp-action-<?php echo $post_id ?>" class="publish-column-content">
              <span class="spinner"></span>
              <a id="publish-post-<?php echo $post_id ?>" disabled name="publish-post" data-post-status="<?php echo $post_status ?>" data-post-id="<?php echo $post_id ?>" class="publish-post button button-secondary button-small" style=""><?php echo __('Publish') ?></a>
            </div>
          </div>
          <script>
            jQuery(document).ready(($) => {
              const {
                publishPost,
                importData,
                importImages,
                deleteImages
              } = ajax_object;

              const sku = '<?php echo $sku; ?>';
              const post_status = '<?php echo $post_status ?>';

              const tr = document.getElementById('post-<?php echo $post_id ?>');
              const publishButton = tr?.querySelector('#publish-post-<?php echo $post_id ?>');
              const importDataButton = tr?.querySelector('#import-ebay-data-<?php echo $post_id ?>');
              const importImagesButton = tr?.querySelector('#import-ebay-images-<?php echo $post_id ?>');
              const deleteImagesButton = tr?.querySelector('#delete-ebay-images-<?php echo $post_id ?>');

              setTimeout(() => {
                publishButton?.addEventListener("click", publishPost);
                deleteImagesButton?.addEventListener("click", deleteImages);

                post_status == 'draft' && publishButton?.removeAttribute('disabled');

                if (sku) {
                  importDataButton?.addEventListener("click", importData);
                  importImagesButton?.addEventListener("click", importImages);

                  importDataButton?.removeAttribute('disabled');
                  importImagesButton?.removeAttribute('disabled');
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
