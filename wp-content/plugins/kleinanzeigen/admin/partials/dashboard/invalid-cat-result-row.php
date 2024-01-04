<?php $edit_link = get_edit_post_link($post_ID); ?>
<div class="actions" data-id="<?php echo $post_ID ?>">
  <a href="<?php echo $edit_link; ?>" target="_blank" type="button" class="button button-primary button-small action-button edit-product" data-task-type="<?php echo $task_type ?>" data-action="edit-product" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label ?></a>
</div>