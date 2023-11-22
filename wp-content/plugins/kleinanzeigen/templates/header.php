<?php
$cats = array_map(function ($category) {
  return sprintf(__('%s (%s)', 'wbp-kleinanzeigen'), $category->title, $category->totalAds);
}, $categories);
$ad_cats = implode(', ', $cats);
$total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
$num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);
$todos = $products['todos'];
?>
<div class="section-wrapper">
  <div class="left-sections">
    <section class="wbp-kleinanzeigen-section">
      <div class="section-inner">
        <div style="display: flex; flex-direction: column;">
          <h2 style="display: flex; justify-content: space-between;"><?php echo sprintf(__('Page: %s', 'wbp-kleinanzeigen'), $paged) ?><small style="margin-left: 20px; font-size: 12px;"><?php echo sprintf(__('Ads: %s', 'wbp-kleinanzeigen'), count($items)); ?></small></h2>
          <div class="overview-wrap">
            <fieldset class="fieldset">
              <legend><?php echo __('Total published', 'wbp-kleinanzeigen') ?></legend>
              <div class="overview tasks">
                <div class="task">
                  <div class="task-name">
                    <span><strong><?php echo __('Ads', 'wbp-kleinanzeigen'); ?></strong></span>
                    <span style="display: inline-block; padding: 0 10px;">>></span>
                    <span><?php echo $ad_cats; ?></span>
                  </div>
                  <div class="task-value"><strong><?php echo $total_ads; ?></strong></div>
                </div>
                <div class="task">
                  <div class="task-name"><strong><?php echo __('Shop products', 'wbp-kleinanzeigen'); ?></strong></div>
                  <div class="task-value"><strong><?php echo count($published_has_sku) + count($published_no_sku) ?></strong></div>
                </div>
                <div class="task">
                  <div class="task-name">
                    <a href="#" class="start-task" data-task-type="has-sku"><?php echo __('Linked products', 'wbp-kleinanzeigen'); ?></a>
                    <i class="dashicons dashicons-warning hidden" title="<?php echo __('Contains invalid linked products', 'wbp-kleinanzeigen') ?>"></i>
                  </div>
                  <div class="task-value"><?php echo count($published_has_sku); ?></div>
                </div>
                <div class="task">
                  <div class="task-name"><a href="#" class="start-task" data-task-type="no-sku"><?php echo __('Autonomous products (Unlinked products)', 'wbp-kleinanzeigen'); ?></a></div>
                  <div class="otask-value"><?php echo count($published_no_sku); ?></div>
                </div>
                <div class="task">
                  <div class="task-name"><a href="#" class="start-task" data-task-type="featured"><?php echo __('Featured products', 'wbp-kleinanzeigen'); ?></a></div>
                  <div class="task-value"><?php echo count($featured_products); ?></div>
                </div>
              </div>
            </fieldset>
          </div>
        </div>

        <div id="inconsistencies" class="hidden">
          <fieldset class="fieldset tasks" style="flex-direction: column; background-color: #fff0f0;">
            <?php $title = "Zeige alle Produkte des Shops, die auf Kleinanzeigen.de nicht mehr auffindbar sind." ?>
            <legend><?php echo __('Inconsistencies discovered', 'wbp-kleinanzeigen') ?></legend>
            <div class="task invalid-ad">
              <div class="general-action">
                <div class="action-header">
                  <div class="">
                    <div class="task-count-wrapper warning">
                      <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    </div>
                    <span class="action-header-title"><a href="#" class="start-task info" data-task-type="invalid-ad" title="<?php echo $title ?>"><?php echo __('Invalid links', 'wbp-kleinanzeigen') ?></a></span>
                    (<span id="invalid-ads-count" class="task-count">0</span>)
                  </div>
                </div>
              </div>
            </div>
            <?php $title = "Zeige alle Produkte des Shops, deren Preise nicht mehr mit denen auf Kleinanzeigen.de übereinstimmen." ?>
            <div class="task invalid-price">
              <div class="general-action">
                <div class="action-header">
                  <div class="">
                    <div class="task-count-wrapper warning">
                      <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    </div>
                    <span class="action-header-title"><a href="#" class="start-task info" data-task-type="invalid-price" title="<?php echo $title ?>"><?php echo __('Price deviations', 'wbp-kleinanzeigen') ?></a></span>
                    (<span id="invalid-price-count" class="task-count">0</span>)
                  </div>
                </div>
              </div>
            </div>
          </fieldset>
        </div>

        <div class="pagination">
          <?php for ($i = 1; $i <= $num_pages; $i++) {
          ?>
            <a href="<?php echo KLEINANZEIGEN_CUSTOMER_URL . '?paged=' . $i ?>" type="button" class="button <?php echo ($i == (int) $paged ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
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
                <span><i class="dashicons dashicons-visibility"></i><?php echo __('Published', 'wbp-kleinanzeigen') ?>:</span>
                <span>
                  <span class="count"><?php echo count($products['publish']) ?></span>
                  <span class="indicator indicator-publish"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-hidden"></i><?php echo __('Drafts', 'wbp-kleinanzeigen') ?>:</span>
                <span>
                  <span class="count"><?php echo count($products['draft']) ?></span>
                  <span class="indicator indicator-draft"></span>
                </span>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <span><i class="dashicons dashicons-editor-help"></i><?php echo __('Unknown', 'wbp-kleinanzeigen') ?>:</span>
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
                <i class="dashicons dashicons-yes"></i><span><?php echo  __('All done', 'wbp-kleinanzeigen'); ?></span>
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
                        <span class="title"><a href="#ad-id-<?php echo $todo['id'] ?>"><?php echo $todo['title'] ?></a>:</span>
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

    const tasks = <?php echo json_encode($tasks) ?>;
    const {
      init_head,
      render_tasks
    } = ajax_object;

    init_head();
    render_tasks(tasks);

    $('.trigger', '.right-sections').click(function() {
      $(this).toggleClass('active');
    })

    if ($('#invalid-ads-count').text() > 0) {
      const el = $('.task [data-task-type="has-sku"]');
      const parent = el.closest('.task');
      parent.addClass('warning');
      $('.dashicons-warning', parent).removeClass('hidden');
    }

  })
</script>

<style>
  .wbp-kleinanzeigen-section .tasks .task .explanation {
    padding: 5px;
    background-color: aqua;
    font-size: .8em;
    font-weight: 100;
  }

  .wbp-kleinanzeigen-section .tasks .task .general-action .action-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .wbp-kleinanzeigen-section .tasks .task .general-action .action-buttons {
    display: flex;
    align-items: center;
    margin-left: 30px;
  }

  .wbp-kleinanzeigen-section .tasks .task .general-action .action-buttons .dashicons {
    font-size: 15px;
    line-height: 18px;
    margin-left: 10px;
    width: 15px;
    height: 15px;
  }

  .wbp-kleinanzeigen-section .tasks .task .general-action .action-buttons .button {
    min-width: 200px;
    text-align: center;
  }

  .wbp-kleinanzeigen-section .action-header-title {
    display: inline-block;
    margin-right: 10px;
    line-height: 1em;
  }

  .wbp-kleinanzeigen-section .task-count {
    display: inline-block;
  }

  .wbp-kleinanzeigen-section .task-count-wrapper {
    margin-right: 8px;
    display: inline-block;
    text-align: center;
    font-size: 11px;
  }

  .wbp-kleinanzeigen-section .task-count-wrapper.chip {
    border-radius: 99px;
    border: 1px solid;
    padding: 1px 4px;
  }

  .wbp-kleinanzeigen-section .dashicons {
    font-size: 14px;
    vertical-align: middle;
    width: 10px;
    height: 10px;
    line-height: 0.5em;
  }

  .wbp-kleinanzeigen-section .chip.task-count {
    border-radius: 99px;
    border: 1px solid;
    padding: 3px 3px;
    width: 12px;
    height: 12px;
    display: inline-block;
    text-align: center;
  }

  .overview.tasks {
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .overview.tasks .task {
    display: flex;
    justify-content: space-between;
  }

  #kleinanzeigen-head-wrap .wbp-shop-section .dashicons {
    margin-right: 10px;
  }

  #kleinanzeigen-head-wrap .left-sections .section-inner {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
    padding: 10px;
  }

  #kleinanzeigen-head-wrap .left-sections .section-inner .overview-wrap {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  #kleinanzeigen-head-wrap .fieldset {
    display: flex;
    flex: 1;
    border: 1px solid #eee;
    background-color: #f8fbff;
    padding: 10px;
    font-size: 11px;
    font-weight: 300;
  }

  #kleinanzeigen-head-wrap .fieldset legend {
    padding: 2px 5px;
    background-color: #ffffff;
    font-size: 10px;
    border: 1px solid #eee;
  }

  #kleinanzeigen-head-wrap .section-inner .warning {
    color: red !important;
  }

  #kleinanzeigen-head-wrap .left-sections section:not(:last-child) .section-inner {
    border-right: 1px solid #eee;
    padding-right: 30px;
  }

  #kleinanzeigen-head-wrap .left-sections section {
    display: flex;
    padding: 20px;
  }

  #kleinanzeigen-head-wrap .left-sections section:first-child {
    max-width: 490px;
  }

  #kleinanzeigen-head-wrap .left-sections section:not(:first-child) {
    padding-left: 0px;
  }

  #kleinanzeigen-head-wrap .left-sections section:not(:first-child) .section-inner {
    padding-left: 10px;
    padding-right: 30px;
  }

  #kleinanzeigen-head-wrap .left-sections .wbp-shop-section {
    max-width: 220px;
  }

  #kleinanzeigen-head-wrap .left-sections .info {
    display: inline-block;
    max-width: 300px;
  }

  #kleinanzeigen-head-wrap .info-box li .dashicons {
    margin-right: 0.2em;
    display: inline;
    font-size: 1em;
    line-height: 1em;
    vertical-align: middle;
  }

  #kleinanzeigen-head-wrap .left-sections section h2 {
    margin: 5px 0 20px;
  }

  #kleinanzeigen-head-wrap .left-sections section h2:first-child {
    margin-bottom: 20px;
  }

  #kleinanzeigen-head-wrap .left-sections section h2 {
    margin-bottom: 10px;
  }
</style>