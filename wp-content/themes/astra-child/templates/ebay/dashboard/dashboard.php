<div class="wpo_section wpo_group">

  <?php
  require_once get_stylesheet_directory() . '/includes/class-admin-ebay-list-table.php';

  $wp_list_table = new Ebay_List_Table();
  $items = $wp_list_table->fetchItems();
  ?>

  <form name="ebay" id="ebay">

    <h3><?php _e('eBay Anzeigen', 'wbp') . ' | Seite ' . $page; ?></h3>
    <h4><?php echo sprintf(__('Seite %s | Anzahl: %s', 'wbp'), $page, count($items)) ?></h4>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++) {
        echo '<input type="submit" class="button ' . ($i == $page ? ' button-primary' : '') . '" name="page_number" value="' . $i . '" />';
      } ?>
      <input type="hidden" name="page" value="ebay">
    </div>
    <?php
    $wp_list_table->display();
    ?>

  </form>
</div>