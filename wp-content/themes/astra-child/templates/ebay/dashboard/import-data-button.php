<div id="import-ebay-data-wbp-action-<?php echo $sku ?>" class="column-content">
  <span class="spinner"></span>
  <a id="import-ebay-data-<?php echo $sku ?>" href="<?php echo admin_url(('admin-ajax.php?sku=') . $sku . '&action=' . $action) ?>" data-action="<?php echo $action ?>" data-ebay-id="<?php echo $sku ?>" class="button button-primary button-small"><?php echo $label ?></a>
</div>
<script>
  jQuery(document).ready(($) => {
    const {
      importEbayData,
      edit_link
    } = ajax_object;

    const sku = '<?php echo $sku; ?>';

    const importDataButton = document.getElementById('import-ebay-data-<?php echo $sku ?>');

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