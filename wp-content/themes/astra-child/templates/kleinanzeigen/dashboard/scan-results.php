<div id="table-scan-list-outer" class="list-outer">
  <div class="list-header" style="display: flex; flex-direction: column;">
    <div style="display: flex; justify-content: center;">
      <i class="dashicons dashicons-admin-generic" style="align-self: center; margin-right: 10px;"></i>
      <h5><?php echo __('Evaluation Results', 'astra-child') ?></h5>
    </div>
    <div style="display: flex; justify-content: center;">
      <p style="margin-top: -20px; margin-bottom: 20px;"><?php echo __('Products based on invalid ads', 'astra-child') ?></p>
    </div>
  </div>
  <div class="list-wrap">
    <table id="table-scan-list" class="table-scan-list striped">
      <thead>
        <th id="scan-result-image" class="column-image">Bild</th>
        <th id="scan-result-title" class="column-title">Titel</th>
        <th id="scan-result-price" class="column-title">Preis</th>
        <th id="scan-result-actions" class="column-actions">Aktionen</th>
      </thead>
      <tbody>
        <?php if (!count($data['deactivated'])) : ?>
          <tr id="<?php echo $published['id'] ?>">
            <td colspan="3" class="" style="height: 60px; text-align: center;">
              <?php echo __('No invalid ads found', 'astra-child') ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($data['deactivated'] as $published) : ?>
            <tr id="<?php echo $published['id'] ?>">
              <td class="column-image"><img width="80" src="<?php echo $published['image']; ?>" alt=""></td>
              <td class="column-title"><?php echo $published['title'] ?></td>
              <td class="column-price"><?php echo $published['price'] ?></td>
              <td class="column-actions">
                <a href="#" type="button" class="button button-primary button-small action-button hide" data-success-label="<?php echo __('Hidden', 'astra-child') ?>" data-post-id="<?php echo $published['id'] ?>" data-kleinanzeigen-id="<?php echo $published['sku'] ?>" data-screen="modal"><?php echo __('Hide', 'astra-child') ?></a>
                <a href="#" type="button" class="button button-primary button-small action-button disconnect" data-success-label="<?php echo __('Disconnected', 'astra-child') ?>" data-post-id="<?php echo $published['id'] ?>" data-kleinanzeigen-id="<?php echo $published['sku'] ?>" data-screen="modal"><?php echo __('Disconnect', 'astra-child') ?></a>
              </td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
  <div class="button-wrap">
    <div class="button-group">
      <a href="#" type="button" class="button button-primary hide-all <?php echo !count($data['deactivated']) ? 'disabled' : '' ?>"><?php echo __('Hide all', 'astra-child') ?></a>
      <a href="#" type="button" class="button button-primary disconnect-all <?php echo !count($data['deactivated']) ? 'disabled' : '' ?>"><?php echo __('Disconnect all', 'astra-child') ?></a>
    </div>
    <div>
      <a href="#" type="button" class="button button-primary close"><?php echo __('Close', 'astra-child') ?></a>
    </div>
  </div>
</div>

<script>
  jQuery(document).ready(($) => {

    const outerEl = $('#table-scan-list-outer');
    const table = $('#table-scan-list');

    $('.hide', table).on('click', function(e) {
      window.dispatchEvent(
        new CustomEvent("deactivate:item", {
          detail: {
            e
          },
        })
      );
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

    $('.hide-all', outerEl).on('click', function() {
      window.dispatchEvent(
        new CustomEvent("deactivate:all", {
          detail: {
            data
          },
        })
      );
    })

    $('.disconnect-all', outerEl).on('click', function() {
      window.dispatchEvent(
        new CustomEvent("disconnect:all", {
          detail: {
            data
          },
        })
      );
    })

    $('.list-modal .close').on('click', function(e) {
      if (e.target === e.currentTarget) {
        $('body').removeClass('show-modal');
      }
    }, )

    const data = <?php echo json_encode($data) ?>;

  })
</script>

<style>
  .column-image {
    width: 90px;
  }

  .column-title {
    width: auto;
  }

  .column-actions {
    width: 30%;
    text-align: center;
  }

  .list-outer {
    position: relative;
  }

  .list-wrap {
    height: 420px;
    overflow-y: auto;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .list-header {
    display: flex;
    justify-content: center;
  }

  .list-header h5 {
    font-size: 1.5em;
  }

  #table-scan-list {
    table-layout: fixed;
    border-collapse: collapse;
    width: 100%;
    height: 350px;
  }

  #table-scan-list thead td,
  #table-scan-list thead th {
    background-color: #fff;
    outline: 1px solid #c3c4c7;
    position: sticky;
    top: 0;
    height: 30px;
    padding: 5px;
  }

  #table-scan-list tbody td {
    padding: 3px;
  }

  #table-scan-list.striped>tbody> :nth-child(odd) {
    background-color: #f6f7f7;
  }

  tbody {
    overflow: auto;
  }

  .button-wrap {
    display: flex;
    justify-content: space-between;
    position: absolute;
    bottom: -44px;
    width: 100%;
  }

  .action-button {
    width: 110px;
  }

  .action-button.disabled {
    pointer-events: none;
  }
</style>