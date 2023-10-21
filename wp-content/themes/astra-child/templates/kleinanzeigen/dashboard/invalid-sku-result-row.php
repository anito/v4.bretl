<div class="actions" data-id="<?php echo $post_ID ?>">
  <a href="#" type="button" class="button button-primary button-small action-button deactivate <?php echo $disabled['deactivate'] ? 'disabled' : '' ?>" data-scan-type="<?php echo $scan_type ?>" data-action="deactivate" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal" data-disconnect="__disconnect__"><?php echo $label['deactivate'] ?></a>
  <a href="#" type="button" class="button button-primary button-small action-button disconnect <?php echo $disabled['disconnect'] ? 'disabled' : '' ?>" data-scan-type="<?php echo $scan_type ?>" data-action="disconnect" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label['disconnect'] ?></a>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = $(`.actions[data-id=${id}]`);

    // Scan Invalid Results
    $('.deactivate:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("deactivate:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })


    $('.disconnect:not(.disabled)', parent).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("disconnect:item", {
          detail: {
            e
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })

    function handleLabel(e) {
      console.log('handleLabel', e.detail);
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