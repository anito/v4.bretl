<div class="section-wrapper">
  <div class="left-sections">
    <section class="wbp-kleinanzeigen-section">
      <div class="section-inner">

        <div class="">

          <h2><?php echo sprintf(__('Page: %s', 'astra-child'), $paged) ?></h2>
          <h2><small><?php echo sprintf(__('Ads: %s', 'astra-child'), count($data->ads)); ?><span class="<?php echo $total != count($published) ? 'warning' : '' ?>" style="padding-left: 20px; font-size: 0.8em; font-weight: 300;"><?php echo sprintf(__('(Total: %d / %s)', 'astra-child'), $total, count($published)); ?></span></small></h2>
          <h4><small>
              <?php foreach ($categories as $category) { ?>
                <?php echo sprintf(__('%s (%s)', 'astra-child'), $category->title, $category->totalAds) ?>
              <?php } ?>
            </small>
          </h4>

        </div>

        <div class="pagination">
          <?php for ($i = 1; $i <= $pages; $i++) {
          ?>
            <a href="<?php echo KLEINANZEIGEN_CUSTOMER_URL . '?paged=' . $i ?>" type="button" class="button <?php echo ($i == (int) $paged ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
          <?php } ?>
        </div>

        <div class="scan-pages">
          <?php $title = "Zeige alle Produkte des Shops, die auf Kleinanzeigen.de nicht mehr auffindbar sind." ?>
          <div class="scan-page">
            <div class="general-action">
              <div class="action-header">
                <i class="dashicons dashicons-editor-help align-center" title="<?php echo $title ?>"></i>
                <span><b>Nach Reservierung / Verkauf / Deaktivierung</b></span>
              </div>
              <div class="action-buttons">
                <a href="#" type="button" class="start-scan info button button-primary button-small" data-scan-type="invalid-ad" title="<?php echo $title ?>"><?php echo __('Show affected products', 'astra-child') ?></a>
              </div>
            </div>
          </div>
          <?php $title = "Zeige alle Produkte des Shops, deren Preise nicht mehr mit denen auf Kleinanzeigen.de übereinstimmen." ?>
          <div class="scan-page">
            <div class="general-action">
              <div class="action-header">
                <i class="dashicons dashicons-editor-help align-center" title="<?php echo $title ?>"></i>
                <span><b>Nach Preisanpassung</b></span>
              </div>
              <div class="action-buttons">
                <a href="#" type="button" class="start-scan info button button-primary button-small" data-scan-type="invalid-price" title="<?php echo $title ?>"><?php echo __('Show affected products', 'astra-child') ?></a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>
    <section class="wbp-shop-section">
      <div class="section-inner">

        <div class="divider-bottom">

          <h2>&nbsp;</h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-visibility"></i><?php echo __('Published', 'astra-child') ?>:</span>
                <span>
                  <span class="count"><?php echo count($products['publish']) ?></span>
                  <span class="indicator indicator-publish"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-hidden"></i><?php echo __('Drafts', 'astra-child') ?>:</span>
                <span>
                  <span class="count"><?php echo count($products['draft']) ?></span>
                  <span class="indicator indicator-draft"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-editor-help"></i><?php echo __('Unknown', 'astra-child') ?>:</span>
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
              <span>Bitte beachte, dass nach einem Datenimport das Produkt immer den Status <strong>Entwurf</strong> erhält und erneut veröffentlicht werden muss.</span>
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
            <span class="description">Ausgeblendet</span>
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
                <i class="dashicons dashicons-yes"></i><span><?php echo  __('All done', 'astra-child'); ?></span>
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

<style>
  .scan-pages {
    padding: 10px;
    background-color: #f8fbff;
    border: 1px solid #eee;
  }

  .scan-pages .scan-page {
    margin: 10px 0px 30px;
    font-size: 12px;
  }

  .scan-pages .scan-page:last-child {
    margin-bottom: 10px;
  }

  .scan-page .explanation {
    padding: 5px;
    background-color: aqua;
    font-size: .8em;
    font-weight: 100;
  }

  .scan-pages .scan-page .general-action .action-header {
    margin-bottom: 10px;
  }

  .scan-pages .scan-page .general-action .action-buttons {
    margin-left: 30px;
  }

  .scan-pages .scan-page .general-action .action-buttons .button {
    min-width: 200px;
    text-align: center;
  }

  .scan-page .dashicons.align-center {
    margin-right: 10px;
    align-self: center;
  }
</style>