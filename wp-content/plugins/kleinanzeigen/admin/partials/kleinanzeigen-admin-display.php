<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.wplauncher.com
 * @since      1.0.0
 *
 * @package    Kleinanzeigen
 * @subpackage Kleinanzeigen/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap" id="kleinanzeigen-admin-display">
  <div id="icon-themes" class="icon32"></div>
  <h2><?php echo __('Overview', 'kleinanzeigen') ?></h2>
  <header id="kleinanzeigen-head-wrap">
    <div class="summary-content"></div>
    <div class="loading-splash">
      <div class="splash-inner">
        <h2><span class="spinner is-active" style="float: left; margin: 0 7px 0;"></span><?php echo __('Loading data', 'kleinanzeigen') ?></h2>
        <div class="disclaimer">Kleinanzeigen Import &copy; Axel Nitzschner</div>
      </div>
    </div>
  </header>
  <form name="kleinanzeigen-list" id="kleinanzeigen-list" method="get">

    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <input type="hidden" name="order" value="<?php echo isset($_REQUEST['order']) ? $_REQUEST['order'] : ''; ?>" />
    <input type="hidden" name="orderby" value="<?php echo isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : ''; ?>" />

    <div id="kleinanzeigen-list-display">
      <?php
      wp_nonce_field('ajax-nonce-custom-list', '_ajax_nonce_custom_list');
      ?>
    </div>

  </form>
  <div class="ka-list-modal">
    <div class="ka-list-modal-background close">
      <div class="ka-list-modal-body">
        <div class="button-controls right">
          <a href="#" type="button" class="button button-primary close"><?php echo __('Close', 'kleinanzeigen') ?></a>
        </div>
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
        <div class="button-controls bottom right">
          <a href="#" type="button" class="button button-primary close"><?php echo __('Close', 'kleinanzeigen') ?></a>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  jQuery(document).ready(($) => {

    const resize = function() {
      root.style.setProperty('--modal-height', window.innerHeight - 450 + 'px');
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
    margin-bottom: 0px;
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
    --modal-padding: 20px;
    height: calc(100vh - var(--admin-topbar) - calc(2 * var(--modal-padding)));
    background-color: white;
    padding: var(--modal-padding);
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

  .ka-list-header>.section.second-section {
    width: 70%;
    align-self: center;
  }

  .ka-list-header h2 {
    font-size: 1.5em;
  }

  .ka-list-header h5 {
    font-size: 1em;
    font-weight: 300;
    margin: 0.3em 0;
  }

  .ka-list-modal .button-controls {
    position: absolute;
    top: 20px;
  }

  .ka-list-modal .button-controls.bottom {
    bottom: 10px;
    top: initial;
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
    margin: 20px 0;
  }

  .action-button.disabled {
    pointer-events: none;
  }

  #kleinanzeigen-list-display a {
    pointer-events: none;
  }

  #kleinanzeigen-list-display.pointer-ready a {
    pointer-events: all;
  }

  .loading-splash::after {
    content: '';
    position: fixed;
    display: block;
    top: var(--admin-topbar);
    right: 0;
    width: calc(100vw - var(--admin-sidebar));
    height: calc(100vh - var(--admin-topbar));
    visibility: hidden;
    transition: all .5s 0.01s ease-out;
    background: rgb(255 255 255);
    opacity: 0;
  }

  .loading .loading-splash::after {
    visibility: visible;
    opacity: .3;
    z-index: 100;
  }

  .splash-inner {
    position: fixed;
    top: calc(50vh - 50px);
    left: calc(50vw - 180px + var(--admin-sidebar));
    z-index: 101;
    width: 180px;
    height: 80px;
    border-radius: 10px;
    border: 1px solid #b4b4b4;
    background: #fff;
    opacity: 0;
    vertical-align: middle;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transform: translateY(-70px);
    transition: all .5s .2s ease-out;
  }

  .loading .splash-inner {
    transform: translateY(-40px);
    opacity: 1;
    z-index: 101;
  }

  .splash-inner h2 {
    transform: translateY(-10px);
  }

  .splash-inner .disclaimer {
    position: absolute;
    bottom: 0;
    font-size: 0.5em;
    font-style: italic;
    color: #888;
    padding: 8px;
    text-align: center;
    line-height: 1.2em;
  }
</style>