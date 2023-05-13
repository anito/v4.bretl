<div>
  <a class="<?php echo $classes ?>" href="<?php echo  $permalink ?>" target="_blank"><?php echo  __('View') ?></a>
</div>
<div>
  <a class="<?php echo $classes ?>" href="<?php echo $editlink ?>" target="_blank"><?php echo __('Edit') ?></a>
</div>
<div>
  <a data-action="delete-post" data-post-id="<?php echo $product->get_id() ?>" data-ebay-id="<?php echo $record->id ?>" data-ebay-page="<?php echo $record->id ?>" class="<?php echo $classes ?>" href="<?php echo $deletelink ?>"><?php echo  __('Delete') ?></a>
</div>