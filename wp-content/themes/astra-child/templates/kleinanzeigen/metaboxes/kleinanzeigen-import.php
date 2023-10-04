<div style="display: flex;">
  <label for="id">ID</label>
  <input type="text" placeholder="ID" value="<?php echo esc_attr($id); ?>" name="id" />
</div>
<?php if (isset($record)) { ?>
  <hr style="padding: 10px 0;">
  <div class="button-group" style="display: flex; flex-direction: column;">
    <div id="import-kleinanzeigen-data-wbp-action" style="display:flex; padding: 10px 0;">
      <span class="spinner"></span>
      <input type="submit" name="import-kleinanzeigen-data" id="import-kleinanzeigen-data" class="kleinanzeigen-sync-action-button button button-primary button-large" value="<?php echo __('Import Data', 'astra-child') ?>">
    </div>
    <div id="import-kleinanzeigen-images-wbp-action" style="display:flex; padding: 10px 0;">
      <span class="spinner"></span>
      <input type="submit" name="import-kleinanzeigen-images" id="import-kleinanzeigen-images" class="kleinanzeigen-sync-action-button button button-primary button-large" value="<?php echo __('Import Images', 'astra-child') ?>">
    </div>
    <div id="get-kleinanzeigen-ad-wbp-action" style="display:flex; padding: 10px 0;">
      <span class="spinner"></span>
      <input type="submit" name="get-kleinanzeigen-ad" id="get-kleinanzeigen-ad" class="kleinanzeigen-sync-action-button button button-large" value="<?php echo __('Load Ad', 'astra-child') ?>">
    </div>
  </div>
<?php } ?>