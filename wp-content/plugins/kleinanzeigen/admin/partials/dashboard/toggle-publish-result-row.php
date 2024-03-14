<?php $edit_link = get_edit_post_link($post_ID); ?>
<div class="actions" data-id="<?php echo $post_ID ?>">
  <a href="#" type="button" class="button button-primary button-small action-button hide-action" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label['publish'] ?></a>
  <a href="<?php echo $edit_link; ?>" target="_blank" type="button" class="button button-primary button-small action-button edit-product" data-task-type="<?php echo $task_type ?>" data-action="edit-product" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label['edit'] ?></a>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = $(`.actions[data-id=${id}]`);

    // Scan Price Results
    $('.hide-action:not(.disabled)', parent).on('click', function(e) {
      document.dispatchEvent(
        new CustomEvent("toggle-publish:item", {
          detail: {
            e
          },
        })
      );
      $(document).on('data:parsed', handleLabel);
    })


    function handleLabel(e) {}
  })(jQuery)
</script>