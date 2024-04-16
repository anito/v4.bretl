<div class="actions" data-id="<?php echo $post_ID ?>">
  <fieldset class="radio-fieldset hcenter">
    <?php foreach ($actions as $key => $val) : ?>
      <span>
        <input type="radio" name="disconnect-type-<?php echo $post_ID ?>" id="disconnect-<?php echo "{$key}-{$post_ID}"; ?>" value="<?php echo base64_encode(json_encode($val['postarr'])); ?>">
        <label for="disconnect-<?php echo "{$key}-{$post_ID}"; ?>"><?php echo $val['name']; ?></label>
      </span>
    <?php endforeach ?>
    <a href="#" type="button" class="button button-primary button-small action-button disconnect <?php echo $disabled['disconnect'] ? 'disabled' : '' ?>" data-task-type="<?php echo $task_type ?>" data-post-id="<?php echo $post_ID ?>" data-kleinanzeigen-id="<?php echo $sku ?>" data-screen="modal" data-success-label="<?php echo __('Disconnected', 'kleinanzeigen'); ?>"><?php echo $label['disconnect'] ?></a>
    <a href="#" class="reset">
      <span class="dashicons dashicons-undo" style="font-size: 0.9em;"></span>
    </a>
  </fieldset>
</div>

<script>
  (($) => {
    const id = <?php echo $post_ID ?>;
    const parent = () => $(`tr#post-id-${id}`);

    const disable = (el) => $(el).attr('disabled', 'disabled').addClass('disabled');
    const enable = (el) => $(el).removeAttr('disabled').removeClass('disabled')
    const getVal = () => $('input[type=radio]:checked', parent()).val();
    const check = () => {
      const buttonEl = $('.disconnect', parent());
      // Return if product has already been disconnected
      if (buttonEl.data('deactivated')) return;

      getVal() ?
        enable(buttonEl) :
        disable(buttonEl);
    }

    $(`input[type=radio]`, parent()).on('change', check);

    $('.disconnect', parent()).on('click', function(e) {
      const action = getVal();
      document.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e,
            action
          },
        })
      );
      $(document).on('data:parsed', handleLabel);
    })
    $('.reset', parent()).on('click', function(e) {
      $(`input[type=radio]`, parent()).each((i, el) => {
        el.checked && (el.checked = false);
      })
      check();
    })
    check();

    function handleLabel(e) {
      const {
        row
      } = e.originalEvent.detail;
      $('.disconnect', row).data('deactivated', true);
    }

  })(jQuery)
</script>