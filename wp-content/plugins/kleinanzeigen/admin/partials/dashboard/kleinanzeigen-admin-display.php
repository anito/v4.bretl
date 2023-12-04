<div class="wrap" id="kleinanzeigen-admin-display">
  <div id="icon-themes" class="icon32"></div>
  <h2><?php echo __('Overview', 'kleinanzeigen') ?></h2>
  <header id="kleinanzeigen-head-wrap">
    <h2><span class="spinner is-active" style="float: left; margin: 0 7px 0;"></span><?php echo __('Loading', 'kleinanzeigen') ?>...</h2>
  </header>
  <form name="kleinanzeigen-list" id="kleinanzeigen-list" method="get">

    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <input type="hidden" name="order" value="<?php echo isset($_REQUEST['order']) ? $_REQUEST['order'] : ''; ?>" />
    <input type="hidden" name="orderby" value="<?php echo isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : ''; ?>" />

    <div id="kleinanzeigen-list-display">
      <?php
      wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
      ?>
    </div>

  </form>
  <div class="ka-list-modal">
    <div class="ka-list-modal-background close">
      <div class="ka-list-modal-body">
        <div id="ka-list-modal-content" class="ka-list-modal-inner">
          <div class="header"></div>
          <form action="" name="kleinanzeigen-task-list" id="kleinanzeigen-task-list" method="get">
            <?php
            wp_nonce_field('ajax-custom-task-list-nonce', '_ajax_custom_task_list_nonce');
            ?>
            <div class="body" id="kleinanzeigen-task-list-display"></div>
          </form>
          <div class="footer"></div>
          <div class="script"></div>
        </div>
        <div class="button-controls right">
          <a href="#" type="button" class="button button-primary close"><?php echo __('Close', 'kleinanzeigen') ?></a>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  jQuery(document).ready(($) => {

    const resize = function() {
      root.style.setProperty('--modal-height', window.innerHeight - 550 + 'px');
    }

    const root = document.querySelector(':root');
    $(window).on('resize', resize)
    resize();

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
    scroll-behavior: smooth;
  }

  body.folded {
    --admin-sidebar: 36px;
  }

  body.show-modal {
    overflow: hidden;
  }

  .ka-list-modal {
    position: absolute;
    pointer-events: none;
    z-index: 0;
    opacity: 0;
    top: 0;
    left: -20px;
    transition: opacity .3s ease-in;
  }

  .ka-list-modal form {
    margin-bottom: 50px;
  }

  body.show-modal .ka-list-modal {
    pointer-events: all;
    transition: opacity .3s ease-in;
    z-index: 99;
    opacity: 1;
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
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    position: relative;
  }

  .ka-list-modal-body tbody {
    height: var(--modal-height, 500px);
  }

  .ka-list-header {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .ka-list-header>.section {
    display: flex;
    justify-content: center;
  }

  .ka-list-header h2 {
    font-size: 1.5em;
  }

  .ka-list-header h5 {
    font-size: 1em;
    margin: 0.3em 0;
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

  .ka-list-content {
    height: auto;
    overflow-y: auto;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
  }

  .ka-list-modal .footer {
    position: absolute;
    bottom: 20px;
  }

  .action-button.disabled {
    pointer-events: none;
  }
</style>