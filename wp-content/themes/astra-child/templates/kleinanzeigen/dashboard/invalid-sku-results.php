<div id="table-task-list-outer" class="list-outer">
  <div class="ka-list-header" style="display: flex; flex-direction: column;">
    <div style="display: flex; justify-content: center;">
      <i class="dashicons dashicons-admin-generic" style="align-self: center; margin-right: 10px;"></i>
      <h5><?php echo __('Evaluation Results', 'astra-child') ?></h5>
    </div>
    <div style="display: flex; justify-content: center;">
      <p style="margin-top: -20px; margin-bottom: 20px;"><?php echo __('Products based on not accessible ads', 'astra-child') ?></p>
    </div>
  </div>
  <div class="ka-list-content">
    <table id="table-task-list" class="table-task-list wp-list-table striped <?php echo empty($data['products']) ? 'empty' : '' ?>">
      <thead>
        <th id="task-result-image" class="column-image">Bild</th>
        <th id="task-result-title" class="column-title">Titel</th>
        <th id="task-result-actions" class="column-actions">Aktionen</th>
      </thead>
      <tbody>
        <?php if (empty($data['products'])) : ?>
          <tr>
            <td colspan="3" class="" style="height: 60px; text-align: center;">
              <?php echo __('No products found', 'astra-child') ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($data['products'] as $published) : ?>
            <tr id="<?php echo $published['post_ID'] ?>">
              <td class="column-image"><img width="80" src="<?php echo $published['image']; ?>" alt=""></td>
              <td class="column-title"><a href="<?php echo  $published['permalink'] ?>" target="_blank"><?php echo $published['title'] ?></a></td>
              <td class="column-actions">
                <a href="#" type="button" class="button button-primary button-small action-button deactivate" data-action="deactivate" data-success-label="<?php echo __('Deactivated', 'astra-child') ?>" data-post-id="<?php echo $published['post_ID'] ?>" data-kleinanzeigen-id="<?php echo $published['sku'] ?>" data-disconnect="disconnect" data-screen="modal"><?php echo __('Deactivate', 'astra-child') ?></a>
                <a href="#" type="button" class="button button-primary button-small action-button disconnect" data-action="disconnect" data-success-label="<?php echo __('Disconnected', 'astra-child') ?>" data-post-id="<?php echo $published['post_ID'] ?>" data-kleinanzeigen-id="<?php echo $published['sku'] ?>" data-screen="modal"><?php echo __('Just disconnect', 'astra-child') ?></a>
              </td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>
<div class="button-controls left">
  <div class="button-group">
    <a href="#" type="button" class="button action-button button-primary deactivate-all <?php echo empty($data['products']) ? 'disabled' : '' ?>" data-action="deactivate-all"><?php echo __('Deactivate all', 'astra-child') ?></a>
    <a href="#" type="button" class="button action-button button-primary disconnect-all <?php echo empty($data['products']) ? 'disabled' : '' ?>" data-action="disconnect-all"><?php echo __('Just disconnect all', 'astra-child') ?></a>
  </div>
</div>

<script>
  jQuery(document).ready(($) => {

    const table = $('#table-task-list');

    $('.deactivate', table).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("save:item", {
          detail: {
            e
          },
        })
      );
    })

    $('.deactivate', table).on('deactivate', function(e) {
      if (e.target.dataset.successLabel) {
        $(e.target).html(e.target.dataset.successLabel);
      }
      $(e.target).addClass('disabled');
    })

    $('.disconnect', table).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("disconnect:item", {
          detail: {
            e
          },
        })
      );
    })

    $('.disconnect', table).on('disconnect', function(e) {
      if (e.target.dataset.successLabel) {
        $(e.target).html(e.target.dataset.successLabel);
      }
      $(e.target).addClass('disabled');
    })

    $('.deactivate-all').on('click', function() {
      window.dispatchEvent(
        new CustomEvent("deactivate:all", {
          detail: {
            data
          },
        })
      );
    })

    $('.disconnect-all').on('click', function() {
      window.dispatchEvent(
        new CustomEvent("disconnect:all", {
          detail: {
            data
          },
        })
      );
    })

    window.addEventListener('deactivated:all', function(e) {
      $('.deactivate-all').addClass('disabled');
    })

    window.addEventListener('disconnected:all', function(e) {
      $('.disconnect-all').addClass('disabled');
    })

    const data = <?php echo json_encode($data) ?>;

  })
</script>