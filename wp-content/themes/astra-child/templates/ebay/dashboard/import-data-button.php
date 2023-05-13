<div id="import-ebay-data-wbp-action-<?php echo $record->id ?>" class="column-content">
  <span class="spinner"></span>
  <a id="import-ebay-data-<?php echo $record->id ?>" href="<?php echo admin_url(('admin-ajax.php?sku=') . $record->id . '&action=' . $action) ?>" data-action="<?php echo $action ?>" data-ebay-id="<?php echo $record->id ?>" class="button button-primary button-small"><i class="dashicons dashicons-<?php echo $icon ?>"></i><?php echo $label ?></a>
</div>
<script>
  jQuery(document).ready(($) => {
    const {
      importData,
      edit_link
    } = ajax_object;

    const sku = '<?php echo $record->id; ?>';

    const importDataButton = document.getElementById('import-ebay-data-<?php echo $record->id ?>');

    function handleEvent(e) {
      const {
        success
      } = e.detail;
      if (success) {
        e.target.setAttribute("value", "Daten importiert");
      } else {
        e.target.setAttribute("value", "Fehler");
      }
    }

    setTimeout(() => {
      importDataButton.addEventListener('ebay:data-import', handleEvent)
    }, 200)
  });
</script>