<div class="section-wrapper">
  <section class="wbp-ebay-section">
    <div class="section-inner">

      <div class="">

        <h2><?php echo sprintf(__('Seite: %s', 'wbp'), $page) ?></h2>
        <h2><small><?php echo sprintf(__('Anzeigen: %s (Gesamt: %d)', 'wbp'), count($data->ads), $total); ?></small></h2>
        <h4><small>
            <?php foreach ($categories as $category) { ?>
              <?php echo sprintf(__('%s (%s)', 'wbp'), $category->title, $category->totalAds) ?>
            <?php } ?>
          </small>
        </h4>

      </div>
      <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++) {
        ?>
          <a href="<?php echo EBAY_CUSTOMER_URL . '?pageNum=' . $i ?>" type="button" class="button <?php echo ($i == (int) $page ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
        <?php } ?>
      </div>
    </div>
  </section>
  <section class="wbp-shop-section">
    <div class="section-inner">

      <div class="">

        <h2><?php echo __('Shop:', 'wbp') ?></h2>
        <h2><small><?php echo sprintf(__('Veröffentlicht: %s ', 'wbp'), count($products['publish'])); ?></small></h2>
        <h2><small><?php echo sprintf(__('Entwürfe: %s', 'wbp'), count($products['draft'])); ?></small></h2>
        <h2><small><?php echo sprintf(__('Unbekannt: %s', 'wbp'), $products['unknown']); ?></small></h2>

      </div>

    </div>
  </section>
  <section id="color-definitions">
    <div class="section-inner">

      <h2>Farbschema</h4>
        <h4><small>Produktstatus im Shop</small></h4>
        <div class="box-wrapper">
          <span class="color-box status connected-publish"></span>
          <span class="description">Veröffentlicht (verknüpft)</span>
        </div>
        <div class="box-wrapper">
          <span class="color-box status disconnected-publish"></span>
          <span class="description">Veröffentlicht (nicht verknüpft)</span>
        </div>
        <div class="box-wrapper">
          <span class="color-box status connected-draft"></span>
          <span class="description">Entwurf (verknüpft) </span>
        </div>
        <div class="box-wrapper">
          <span class="color-box status disconnected-draft"></span>
          <span class="description">Entwurf (nicht verknüpft) </span>
        </div>
        <div class="box-wrapper">
          <span class="color-box status invalid"></span>
          <span class="description">Produkt unbekannt</span>
        </div>
    </div>
  </section>
</div>
<script>
  jQuery(document).ready(($) => {

    const {
      init_header
    } = ajax_object;

    init_header();

  })
</script>