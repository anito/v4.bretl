<div class="actions" data-id="<?php echo $post_ID ?>">
  <fieldset class="radio-fieldset hcenter">
    <span>
      <input type="radio" name="disconnect-type-<?php echo $post_ID ?>" id="disconnect-publish-<?php echo $post_ID ?>" value="<?php echo base64_encode(json_encode(array('post_status' => 'publish'))); ?>">
      <label for="disconnect-publish-<?php echo $post_ID ?>"><?php echo $label['publish'] ?></label>
    </span>
    <span>
      <input type="radio" name="disconnect-type-<?php echo $post_ID ?>" id="disconnect-deactivate-<?php echo $post_ID ?>" value="<?php echo base64_encode(json_encode(array('post_status' => 'draft'))); ?>">
      <label for="disconnect-deactivate-<?php echo $post_ID ?>"><?php echo $label['deactivate'] ?></label>
    </span>
    <span>
      <input type="radio" name="disconnect-type-<?php echo $post_ID ?>" id="disconnect-delete-<?php echo $post_ID ?>" value="<?php echo base64_encode(json_encode(array('post_status' => 'trash'))); ?>">
      <label for="disconnect-delete-<?php echo $post_ID ?>"><?php echo $label['delete'] ?></label>
    </span>
    <a href="#" type="button" class="button button-primary button-small action-button disconnect <?php echo $disabled['disconnect'] ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal"><?php echo $label['disconnect'] ?></a>
  </fieldset>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = $(`tr#post-id-${id}`);

    const disable = (el) => $(el).attr('disabled', 'disabled').addClass('disabled');
    const enable = (el) => $(el).removeAttr('disabled').removeClass('disabled')
    const getVal = () => $('input[type=radio]:checked', parent).val();
    const check = () => {
      getVal() ?
      enable($('.disconnect', parent)) :
      disable($('.disconnect', parent));
    }

    $(`input[type=radio]`, parent).on('change', check);

    $('.disconnect', parent).on('click', function(e) {
      const action = getVal();
      window.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e,
            action
          },
        })
      );
      $(e.target).on('data:parsed', handleLabel)
    })
    check();

    function handleLabel(e) {
      const el = e.target;
      const label = $(el).data('success-label');
      if (label) {
        $(el).html(label);
      }
      disable($(el)).off('data:parsed', handleLabel);
    }

  })(jQuery)
</script>