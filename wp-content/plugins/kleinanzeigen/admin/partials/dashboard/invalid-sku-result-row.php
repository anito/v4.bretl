<div class="actions" data-id="<?php echo $post_ID ?>">
  <a href="#" type="button" class="button button-primary button-small action-button disconnect <?php echo $disabled['disconnect'] ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label['disconnect'] ?></a>
  <a href="#" type="button" class="button button-primary button-small action-button deactivate <?php echo $disabled['activate-deactivate'] ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-args="<?php echo base64_encode(json_encode(array('post_status' => $published ? 'draft' : 'publish'))) ?>" data-screen="modal"><?php echo $label['activate-deactivate'] ?></a>
  <a href="#" type="button" class="button button-primary button-small action-button delete <?php echo $disabled['delete'] ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-args="<?php echo base64_encode(json_encode(array('post_status' => 'trash'))) ?>" data-screen="modal"><?php echo $label['delete'] ?></a>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = $(`.actions[data-id=${id}]`);

    // Scan Invalid Results
    $('.deactivate:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })


    $('.disconnect:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })

    $('.delete:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })

    function handleLabel(e) {
      const el = e.target;
      const label = $(el).data('success-label');
      if (label) {
        $(el).html(label);
      }
      $(el).addClass('disabled')
        .off('data:parsed', handleLabel);
    }

  })(jQuery)
</script>