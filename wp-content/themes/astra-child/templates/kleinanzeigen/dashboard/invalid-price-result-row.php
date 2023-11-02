<div class="actions" data-id="<?php echo $post_ID ?>">
  <a href="#" type="button" class="button button-primary button-small action-button fix-price <?php echo $disabled ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-action="fix-price" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-price="<?php echo $price ?>" data-screen="modal"><?php echo $label ?></a>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = $(`.actions[data-id=${id}]`);

    // Scan Price Results
    $('.fix-price:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("fixprice:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel);
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