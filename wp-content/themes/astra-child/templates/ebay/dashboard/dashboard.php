<div class="wpo_section wpo_group">

  <?php
  require_once get_stylesheet_directory() . '/includes/class-admin-ebay-list-table.php';

  $wp_list_table = new Ebay_List_Table();
  if(isset($data)) {
    $wp_list_table->setData($data->ads);
    $categories = $data->searchData;
    $total = 0;
    foreach ($categories as $category) {
      $total += $category->totalAds;
    }
  } else {
    $wp_list_table->setData([]);
  }
  ?>

  <form name="ebay" id="ebay">

    <h3><?php _e('eBay Anzeigen', 'wbp') ?></h3>
    <h4 style="margin: 0;"><?php echo sprintf(__('Seite: %s', 'wbp'), $page) ?>&nbsp;<small>&nbsp;<?php echo sprintf(__('    Anzeigen: %s (Gesamt: %d)', 'wbp'), count($data->ads), $total); ?></small></h4>
    <h4 style="margin: 5px 0 30px;"><small>
        <?php foreach ($categories as $category) { ?>
          <?php echo sprintf(__('%s (%s)', 'wbp'), $category->title, $category->totalAds) ?>
        <?php } ?>
      </small></h4>

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