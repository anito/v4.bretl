<?php if ($connected) {
?>

  <div id="import-ebay-data-action-<?php echo $record->id ?>">
    <span class="spinner"></span>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_data')" data-post-id="<?php echo $post_id ?>" data-ebay-id="<?php echo $record->id ?>" data-action="import-data-<?php echo $record->id ?>"><?php echo __('Daten importieren', 'wbp') ?></a>
  </div>
  <div id="import-ebay-images-action-<?php echo $record->id ?>">
    <span class="spinner"></span>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_images" data-post-id="<?php echo $post_id ?>" data-ebay-id="<?php echo $record->id ?>" data-action="import-images-' . $record->id ?>"><?php echo __('Fotos importieren', 'wbp') ?></a>
  </div>
  <div id="publish-post-wbp-action-<?php echo $post_id ?>">
    <span class="spinner"></span>
    <a data-action="publish-post" id="publish-post-<?php echo $post_id ?>" <?php if ($post_status === 'publish') echo 'disabled' ?> name="publish-post" data-post-status="<?php echo $post_status ?>" data-ebay-id="<?php echo $record->id ?>" data-post-id="<?php echo $post_id ?>" class="publish-post button button-secondary button-small"><?php echo __('Publish') ?></a>
  </div>

<?php }
