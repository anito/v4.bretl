<?php if ($connected) {
?>

  <div>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_data')" data-post-id="<?php echo $product->get_id() ?>" data-ebay-id="<?php echo $record->id ?>" data-action="import-data-<?php echo $record->id ?>"><?php echo __('Daten importieren', 'wbp') ?></a>
  </div>
  <div>
    <a class="button button-primary button-small <?php echo $classes ?>" href="<?php echo admin_url('admin-ajax.php?sku=') . $record->id  ?>&action=import_images" data-post-id="<?php echo $product->get_id() ?>" data-ebay-id="<?php echo $record->id ?>" data-action="import-images-' . $record->id ?>"><?php echo __('Fotos importieren', 'wbp') ?></a>
  </div>

<?php }
