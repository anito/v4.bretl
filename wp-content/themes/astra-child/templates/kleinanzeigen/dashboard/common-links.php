<div>
  <?php echo $status_name ?>
</div>
<div>
  <a data-action="view-post" href="<?php echo  $permalink ?>" target="_blank" class="<?php echo $classes ?>"><?php echo  __('View') ?></a>
</div>
<div>
  <a data-action="edit-post" href="<?php echo $editlink ?>" target="_blank" class="<?php echo $classes ?>"><?php echo __('Edit') ?></a>
</div>
<div>
  <a data-action="delete-post" href="<?php echo $deletelink ?>" target="_blank" data-kleinanzeigen-id="<?php echo $record->id ?>" data-post-id="<?php echo $post_ID ?>"><?php echo __('Delete') ?></a>
</div>