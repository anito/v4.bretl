<div class="section-wrapper">
  <div class="left-sections">
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
  
        <div class="divider-bottom">
  
          <h2>&nbsp;</h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-visibility"></i><?php echo __('Veröffentlicht:', 'wbp') ?></span>
                <span class="count"><?php echo count($products['publish']) ?></span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-hidden"></i><?php echo __('Entwürfe:', 'wbp') ?></span>
                <span class="count"><?php echo count($products['draft']) ?></span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-warning"></i><?php echo __('Unbekannt:', 'wbp') ?></span>
                <span class="count"><?php echo count($products['unknown']) ?></span>
              </div>
            </small></h2>
  
        </div>
        <div class="info-box">
          <ul>
            <li>
              <i class="dashicons dashicons-warning"></i>
              <span>Bitte beachte, dass nach einem Datenimport der Produktstatus immer zu <strong>Entwurf</strong> wechselt.</span>
            </li>
          </ul>
        </div>
  
      </div>
    </section>
  </div>
  <div class="right-sections">
    <section id="color-definitions">
      <div class="section-inner">
  
        <h4>Farbschlüssel</h4>
          <div class="box-wrapper">
            <span class="color-box status connected-publish"></span>
            <span class="description">Veröffentlicht</span>
          </div>
          <div class="box-wrapper">
            <span class="color-box status connected-draft"></span>
            <span class="description">Entwurf </span>
          </div>
          <div class="box-wrapper divider-bottom">
            <span class="color-box status invalid"></span>
            <span class="description">Produkt unbekannt</span>
          </div>
          <div class="box-wrapper">
            <span class="color-box status disconnected"></span>
            <span class="description">Nicht verknüpft</span>
          </div>
      </div>
    </section>
  </div>
</div>
<script>
  jQuery(document).ready(($) => {

    const {
      init_header
    } = ajax_object;

    init_header();

  })
</script>