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
  <div class="list-modal">
    <div class="list-modal-background close">
      <div class="list-modal-body">
        <div id="list-modal-content" class="list-modal-inner"></div>
      </div>
    </div>
  </div>

</div>

<script>
  jQuery(document).ready(($) => {})
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

  .list-modal {
    position: absolute;
    top: 0;
    left: -20px;
    display: none;
  }

  .list-modal-background {
    background-color: #00000050;
    position: fixed;
    display: flex;
    height: calc(100vh - var(--admin-topbar));
    width: calc(100vw - var(--admin-sidebar));
    justify-content: center;
    align-items: center;
  }

  .list-modal-body {
    width: 60%;
    height: 500px;
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    position: relative;
  }

  body.show-modal .list-modal {
    display: block;
  }
</style>