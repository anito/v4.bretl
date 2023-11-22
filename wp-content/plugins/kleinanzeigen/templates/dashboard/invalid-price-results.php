<div id="table-task-list-outer" class="list-outer">
  <div class="ka-list-header" style="display: flex; flex-direction: column;">
    <div style="display: flex; justify-content: center;">
      <i class="dashicons dashicons-admin-generic" style="align-self: center; margin-right: 10px;"></i>
      <h5><?php echo __('Evaluation Results', 'wbp-kleinanzeigen') ?></h5>
    </div>
    <div style="display: flex; justify-content: center;">
      <p style="margin-top: -20px; margin-bottom: 20px;"><?php echo __('Products with price differences', 'wbp-kleinanzeigen') ?></p>
    </div>
  </div>
  <div class="ka-list-content">
    <table id="table-task-list" class="table-task-list wp-list-table striped <?php echo empty($data['products']) ? 'empty' : '' ?>">
      <thead>
        <th id="task-result-image" class="column-image">Bild</th>
        <th id="task-result-title" class="column-title">Titel</th>
        <th id="task-result-price" class="column-shop-price">Preis</th>
        <th id="task-result-actions" class="column-actions">Aktionen</th>
      </thead>
      <tbody>
        <?php if (empty($data['products'])) : ?>
          <tr>
            <td colspan="4" class="" style="height: 60px; text-align: center;">
              <?php echo __('No products found', 'wbp-kleinanzeigen') ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($data['products'] as $published) : ?>
            <tr id="<?php echo $published['post_ID'] ?>">
              <td class="column-image"><img width="80" src="<?php echo $published['image']; ?>" alt=""></td>
              <td class="column-title"><a href="<?php echo  $published['permalink'] ?>" target="_blank"><?php echo $published['title'] ?></a></td>
              <td class="column-shop-price">
                <div class="ad-price price">
                  <span><?php echo __('KA price', 'wbp-kleinanzeigen') ?>:</span>
                  <span><?php echo $published['ka_price'] ?></span>
                </div>
                <div class="shop-price price">
                  <span><?php echo __('Shop price', 'wbp-kleinanzeigen') ?>:</span>
                  <span><?php echo $published['shop_price'] ?></span>
                </div>
              </td>
              <td class="column-actions">
                <a href="#" type="button" class="button button-primary button-small action-button fix-price" data-action="fix-price" data-success-label="<?php echo __('Fixed', 'wbp-kleinanzeigen') ?>" data-post-id="<?php echo $published['post_ID'] ?>" data-kleinanzeigen-id="<?php echo $published['sku'] ?>" data-price="<?php echo $published['price'] ?>" data-ka-price="<?php echo $published['ka_price'] ?>" data-screen="modal"><?php echo __('Fix price', 'wbp-kleinanzeigen') ?></a>
              </td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>
<div class="button-controls left">
  <a href="#" type="button" class="button action-button button-primary fix-price-all <?php echo empty($data['products']) ? 'disabled' : '' ?>" data-action="fix-price-all"><?php echo __('Fix all prices', 'wbp-kleinanzeigen') ?></a>
</div>

<script>
  jQuery(document).ready(($) => {

    const table = $('#table-task-list');

    $('.fix-price-all', table).on('click', function() {
      window.dispatchEvent(
        new CustomEvent("fixprice:all", {
          detail: {
            data
          },
        })
      );
    })

    window.addEventListener('fixed-price:all', function(e) {
      $('.fix-price-all', table).addClass('disabled');
    })

    const data = <?php echo json_encode($data) ?>;

  })
</script>

<style>
  .column-shop-price .price {
    display: flex;
    justify-content: space-between;
  }
</style>