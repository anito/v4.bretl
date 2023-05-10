<div id="import-ebay-data-wbp-action-<?php echo $sku ?>" class="column-content">
  <span class="spinner"></span>
  <input type="submit" id="import-ebay-data-<?php echo $sku ?>" disabled name="import-ebay-data" data-ebay-id="<?php echo $sku ?>" class="import-ebay-data button button-primary button-small" style="" value="Produkt anlegen">
</div>
<script>
  jQuery(document).ready(($) => {
    const {
      importEbayData,
      edit_link
    } = ajax_object;

    const sku = '<?php echo $sku; ?>';

    const importDataButton = document.getElementById('import-ebay-data-<?php echo $sku ?>');

    setTimeout(() => {
      importDataButton?.addEventListener("click", importEbayData);

      if (sku) {
        importDataButton && (importDataButton.disabled = false);
      }
    }, 200)
  });
</script>