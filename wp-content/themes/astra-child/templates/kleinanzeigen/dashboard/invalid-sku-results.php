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
    <table id="table-scan-list" class="table-scan-list wp-list-table striped <?php echo empty($data['deactivated']) ? 'empty' : '' ?>">
      <thead>
        <th id="scan-result-image" class="column-image">Bild</th>
        <th id="scan-result-title" class="column-title">Titel</th>
        <th id="scan-result-price" class="column-shop-price">Preis</th>
        <th id="scan-result-actions" class="column-actions">Aktionen</th>
      </thead>
      <tbody>
        <?php if (empty($data['deactivated'])) : ?>
          <tr id="<?php echo $published['id'] ?>">
            <td colspan="4" class="" style="height: 60px; text-align: center;">
              <?php echo __('No invalid ads found', 'astra-child') ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($data['deactivated'] as $published) : ?>
            <tr id="<?php echo $published['id'] ?>">
              <td class="column-image"><img width="80" src="<?php echo $published['image']; ?>" alt=""></td>
              <td class="column-title"><?php echo $published['title'] ?></td>
              <td class="column-shop-price">
                <div class="shop-price"><?php echo $published['shop_price'] ?></div>
                <div class="ad-price"><?php echo $published['price'] ?></div>
              </td>
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
</div>
<div class="button-wrap left">
  <div class="button-group">
    <a href="#" type="button" class="button button-primary hide-all <?php echo empty($data['deactivated']) ? 'disabled' : '' ?>"><?php echo __('Hide all', 'astra-child') ?></a>
    <a href="#" type="button" class="button button-primary disconnect-all <?php echo empty($data['deactivated']) ? 'disabled' : '' ?>"><?php echo __('Disconnect all', 'astra-child') ?></a>
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

    const data = <?php echo json_encode($data) ?>;

  })
</script>