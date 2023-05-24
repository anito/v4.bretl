<div class="wpo_section wpo_group">
  
  <header id="head-wrap"></header>

  <form name="kleinanzeigen-list" id="kleinanzeigen-list" method="get">
    
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <input type="hidden" name="order" value="<?php echo isset($_REQUEST['order']) ? $_REQUEST['order'] : ''; ?>" />
    <input type="hidden" name="orderby" value="<?php echo isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : ''; ?>" />

    <div id="ts-history-table">
      <?php
      wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
      ?>
    </div>

  </form>

</div>

<script>
</script>