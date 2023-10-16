<div class="wbp-section wbp-dashboard-section">

  <header id="head-wrap"></header>

  <form name="kleinanzeigen-list" id="kleinanzeigen-list" method="get">

    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <input type="hidden" name="order" value="<?php echo isset($_REQUEST['order']) ? $_REQUEST['order'] : ''; ?>" />
    <input type="hidden" name="orderby" value="<?php echo isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : ''; ?>" />

    <div id="kleinanzeigen-table">
      <?php
      wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
      ?>
    </div>

  </form>
  <div class="ka-list-modal">
    <div class="ka-list-modal-background close">
      <div class="ka-list-modal-body">
        <div id="ka-list-modal-content" class="ka-list-modal-inner"></div>
        <div class="button-controls right">
          <a href="#" type="button" class="button button-primary close"><?php echo __('Close', 'astra-child') ?></a>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  jQuery(document).ready(($) => {

    $('.ka-list-modal .close').on('click', function(e) {
      if (e.target === e.currentTarget) {
        $('body').removeClass('show-modal');
      }
    })

  })
</script>

<style>
  :root {
    --admin-sidebar: 160px;
    --admin-topbar: 32px;
  }

  body.folded {
    --admin-sidebar: 36px;
  }

  body.show-modal {
    overflow: hidden;
  }

  .ka-list-modal {
    position: absolute;
    top: 0;
    left: -20px;
    display: none;
  }

  .ka-list-modal-background {
    background-color: #00000050;
    position: fixed;
    z-index: 99;
    display: flex;
    height: calc(100vh - var(--admin-topbar));
    width: calc(100vw - var(--admin-sidebar));
    justify-content: center;
    align-items: center;
  }

  .ka-list-modal-body {
    width: 80%;
    height: 600px;
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    position: relative;
  }

  body.show-modal .ka-list-modal {
    display: block;
  }

  /**
   * Styles for imported templates
   */
  #table-scan-list .column-image {
    width: 90px;
  }

  #table-scan-list .column-title {
    width: auto;
  }

  #table-scan-list .column-shop-price {
    width: 20%;
  }

  #table-scan-list .column-actions {
    width: 30%;
    text-align: center;
  }

  .ka-list-content {
    height: auto;
    overflow-y: auto;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .ka-list-header {
    display: flex;
    justify-content: center;
  }

  .ka-list-header h5 {
    font-size: 1.5em;
  }

  #table-scan-list {
    table-layout: fixed;
    border-collapse: collapse;
    width: 100%;
    height: 440px;
    display: inline-block;
    overflow: auto;
  }

  #table-scan-list.empty {
    display: table;
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

  #table-scan-list tbody {
    overflow: auto;
  }

  #table-scan-list tbody,
  #table-scan-list thead {
    display: inline-table;
    width: 100%;
  }

  .ka-list-modal .button-controls {
    position: absolute;
    bottom: 10px;
  }

  .ka-list-modal .button-controls.right {
    right: 20px;
  }

  .ka-list-modal .button-controls.left {
    left: 20px;
  }

  #table-scan-list tbody td .action-button {
    width: 110px;
    margin: 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .action-button.disabled {
    pointer-events: none;
  }
</style>