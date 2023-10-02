<div class="section-wrapper">
  <div class="left-sections">
    <section class="wbp-kleinanzeigen-section">
      <div class="section-inner">

        <div class="">

          <h2><?php echo sprintf(__('Seite: %s', 'wbp'), $page) ?></h2>
          <h2><small><?php echo sprintf(__('Anzeigen: %s', 'wbp'), count($data->ads)); ?><span class="<?php echo $total != count($published) ? 'warning' : '' ?>" style="padding-left: 20px; font-size: 0.8em; font-weight: 300;"><?php echo sprintf(__('(Gesamt: %d / %s)', 'wbp'), $total, count($published)); ?></span></small></h2>
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
            <a href="<?php echo KLEINANZEIGEN_CUSTOMER_URL . '?pageNum=' . $i ?>" type="button" class="button <?php echo ($i == (int) $page ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
          <?php } ?>
        </div>

        <?php $title = "Verberge alle mit Kleinanzeigen verlinkten Produkte im Shop, welche aufgrund Verkauf o.ä. nicht mehr auf Kleinanzeigen gefunden werden können." ?>
        <div class="scan-pages">
          <i class="dashicons dashicons-editor-help" title="<?php echo $title ?>"></i>
          <a href="" type="button" class="scan info" title="<?php echo $title ?>">Alle Produkte im Shop verbergen (auf Entwurf umstellen), deren Kleinanzeige nicht mehr auffindbar ist (z.B. durch Verkauf).</a>
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
                <span>
                  <span class="count"><?php echo count($products['publish']) ?></span>
                  <span class="indicator indicator-publish"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-hidden"></i><?php echo __('Entwürfe:', 'wbp') ?></span>
                <span>
                  <span class="count"><?php echo count($products['draft']) ?></span>
                  <span class="indicator indicator-draft"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-editor-help"></i><?php echo __('Unbekannt:', 'wbp') ?></span>
                <span>
                  <span class="count"><?php echo count($products['unknown']) ?></span>
                  <span class="indicator indicator-unknown"></span>
                </span>
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
      <div class="section-inner" style="padding-top: 50px; min-width: 140px;">

        <div class="box-wrapper inset">
          <span class="status-wrapper">
            <span class="color-box status connected-publish"><?php echo count($products['publish']) ?></span>
            <span class="description">Veröffentlicht</span>
          </span>
        </div>
        <div class="box-wrapper inset">
          <span class="status-wrapper">
            <span class="color-box status connected-draft"><?php echo count($products['draft']) ?></span>
            <span class="description">Entwurf </span>
          </span>
        </div>
        <div class="box-wrapper inset">
          <span class="status-wrapper">
            <span class="color-box status invalid"><?php echo count($products['unknown']) ?></span>
            <span class="description">Unbekannt</span>
          </span>
        </div>
        <div class="box-wrapper inset">
          <span class="status-wrapper">
            <span class="color-box status disconnected"><span class="inner"><?php echo count($products['no-sku']) ?></span></span>
            <span class="description">Nicht verknüpft</span>
          </span>
        </div>
        <div class="box-wrapper summary-status divider-top">
          <span class="status-wrapper">
            <?php if (0 === count($todos)) : ?>
              <div>
                <i class="dashicons dashicons-yes"></i><span><?php echo  __('Alles erledigt'); ?></span>
              </div>
            <?php else : ?>
              <div class="trigger"><span class="text">
                  <?php if (1 === count($todos)) :
                    echo sprintf(__('%d Meldung'), count($todos));
                  else :
                    echo sprintf(__('%d Meldungen'), count($todos)); ?>

                  <?php endif ?>
                </span><i class="dashicons dashicons-arrow-right"></i>
              </div>
              <div class="outer">
                <div class="content">
                  <ul>
                    <?php foreach ($todos as $todo) { ?>
                      <li class="todo">
                        <span class="title"><?php echo $todo['title'] ?>:</span>
                        <span class="reason"><?php echo $todo['reason'] ?></span>
                      </li>
                    <?php } ?>
                  </ul class="todos">
                </div>
              </div>
            <?php endif ?>
          </span>
        </div>
      </div>
    </section>
  </div>
</div>
<script>
  jQuery(document).ready(($) => {

    const {
      init_head
    } = ajax_object;

    init_head();

    $('.trigger').click(function() {
      $(this).toggleClass('active');
    })

  })
</script>