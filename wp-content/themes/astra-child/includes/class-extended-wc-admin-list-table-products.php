<?php
require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/list-tables/class-wc-admin-list-table-products.php';


class Extended_WC_Admin_List_Table_Products extends WC_Admin_List_Table_Products
{
  function __construct()
  {
    $this->list_table_type = 'product';

    if (is_ajax()) {
      // add_action('manage_posts_extra_tablenav', array($this, 'maybe_render_blank_state'));
      // add_filter('view_mode_post_types', array($this, 'disable_view_mode'));
      // add_action('restrict_manage_posts', array($this, 'restrict_manage_posts'));
      // add_filter('request', array($this, 'request_query'));
      // add_filter('post_row_actions', array($this, 'row_actions'), 100, 2);
      // add_filter('default_hidden_columns', array($this, 'default_hidden_columns'), 10, 2);
      add_filter('list_table_primary_column', array($this, 'list_table_primary_column'), 1, 2);
      // add_filter('manage_edit-' . $this->list_table_type . '_sortable_columns', array($this, 'define_sortable_columns'));
      add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_columns'), 1, 2);
      add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_columns'), 1, 2);
    }

    add_filter('manage_' . $this->list_table_type . '_posts_columns', array($this, 'define_custom_columns'), 1);
    add_action('manage_' . $this->list_table_type . '_posts_custom_column', array($this, 'render_custom_columns'), 1, 2);
  }

  function render_row($id)
  {
    $wp_list_table = _get_list_table('WP_Posts_List_Table', array('screen' => 'edit-product'));
    $wp_list_table->display_rows(array(get_post($id)), 0);
  }

  function define_custom_columns($columns)
  {
    $columns['sync'] = 'eBay Aktionen';
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
    }
    switch ($column_name) {
      case 'sync': {
?>
          <div class="sync-column-content" style="display:flex; flex-direction: column; width: 113px;">
            <div id="import-ebay-data-action'<?php echo $post_id ?>" style="display:flex; position: relative; flex: 1;">
              <span class="spinner" style="position: absolute; left: -35px;"></span>
              <input type="submit" id="import-ebay-data-<?php echo $post_id ?>" disabled name="import-ebay-data" data-ebay-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-ebay-data button button-primary button-small" style="flex: 1; margin-bottom: 3px;" value="Daten importieren">
            </div>
            <div id="import-ebay-images-action'<?php echo $post_id ?>" style="display:flex; position: relative; flex: 1;">
              <span class="spinner" style="position: absolute; left: -35px;"></span>
              <input type="submit" id="import-ebay-images-<?php echo $post_id ?>" disabled name="import-ebay-images" data-ebay-id="<?php echo $sku ?>" data-post-id="<?php echo $post_id ?>" class="import-ebay-images button button-primary button-small" style="flex: 1; margin-bottom: 3px;" value="Fotos importieren">
            </div>
            <div id="publish-post-action-'<?php echo $post_id ?>" class="publish-column-content" style="display:flex; position: relative;">
              <span class="spinner" style="position: absolute; left: -35px;"></span>
              <input type="submit" id="publish-post-'<?php echo $post_id ?>" disabled name="publish-post" data-post-status="<?php echo $post_status ?>" data-post-id="<?php echo $post_id ?>" class="publish-post button button-primary button-small" style="flex: 1; " value="<?php echo __('Publish') ?>">
            </div>
          </div>
          <script>
            jQuery(document).ready(($) => {

              const sku = '<?php echo $sku; ?>';
              const post_status = '<?php echo $post_status ?>';

              const tr = document.getElementById('post-<?php echo $post_id ?>')
              const publishButton = tr?.querySelector('input.publish-post')
              const importDataButton = tr?.querySelector('input.import-ebay-data')
              const importImagesButton = tr?.querySelector('input.import-ebay-images')

              publishButton.disabled = post_status == 'publish'
              if (sku) {
                tr.querySelector('.import-ebay-data')?.removeAttribute('disabled');
                tr.querySelector('.import-ebay-images')?.removeAttribute('disabled');
              }
              const {
                publishPost,
                importEbayData,
                importEbayImages
              } = ajax_object;
              publishButton?.addEventListener("click", publishPost);
              importDataButton?.addEventListener("click", importEbayData);
              importImagesButton?.addEventListener("click", importEbayImages);
            })
          </script>
<?php
          break;
        }
    }
  }
}
