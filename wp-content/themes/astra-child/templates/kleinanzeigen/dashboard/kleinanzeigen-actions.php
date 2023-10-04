<?php if ($connected) {
?>

  <div id="import-kleinanzeigen-data-wbp-action-<?php echo $record->id ?>">
    <span class="spinner"></span>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_data')" data-post-id="<?php echo $post_id ?>" data-kleinanzeigen-id="<?php echo $record->id ?>" data-action="import-data-<?php echo $record->id ?>"><?php echo __('Import Data', 'astra-child') ?></a>
  </div>
  <div id="import-kleinanzeigen-images-wbp-action-<?php echo $record->id ?>">
    <span class="spinner"></span>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_images" data-post-id="<?php echo $post_id ?>" data-kleinanzeigen-id="<?php echo $record->id ?>" data-action="import-images-<?php echo $record->id ?>"><?php echo __('Import Images', 'astra-child') ?></a>
  </div>
  <div id="publish-post-wbp-action-<?php echo $post_id ?>" class="hidden">
    <span class="spinner"></span>
    <a data-action="publish-post" id="publish-post-<?php echo $post_id ?>" name="publish-post" data-post-status="<?php echo $post_status ?>" data-kleinanzeigen-id="<?php echo $record->id ?>" data-post-id="<?php echo $post_id ?>" class="publish-post button button-secondary button-small"><?php echo ($post_status === 'publish') ?  __('Draft') : __('Publish') ?></a>
  </div>

<?php }
