<div id="import-kleinanzeigen-data-wbp-<?php if ($type === 'button') echo 'action-' ?><?php echo $record->id ?>">
  <span class="spinner"></span>
  <a id="import-kleinanzeigen-data-<?php echo $record->id ?>" href="<?php echo admin_url(('admin-ajax.php?sku=') . $record->id . '&action=' . $action) ?>" data-action="<?php echo $action ?>" data-kleinanzeigen-id="<?php echo $record->id ?>" class="<?php if ($type === 'button') echo 'button button-primary button-small' ?>"><i class="dashicons dashicons-<?php echo $icon ?>"></i><?php echo __('Create', 'kleinanzeigen') ?></a>
</div>