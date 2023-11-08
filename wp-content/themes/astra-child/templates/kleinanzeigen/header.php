<?php
$cats = array_map(function ($category) {
  return sprintf(__('%s (%s)', 'astra-child'), $category->title, $category->totalAds);
}, $categories);
$ad_cats = implode(', ', $cats);
$total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
$num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);
$todos = $products['todos']; ?>
<div class="section-wrapper">
  <div class="left-sections">
    <section class="wbp-kleinanzeigen-section">
      <div class="section-inner">
        <div style="display: flex; flex-direction: column;">
          <h2 style="display: flex; justify-content: space-between;"><?php echo sprintf(__('Page: %s', 'astra-child'), $paged) ?><small style="margin-left: 20px; font-size: 12px;"><?php echo sprintf(__('Ads: %s', 'astra-child'), count($items)); ?></small></h2>
          <div class="overview-wrap">
            <fieldset class="fieldset">
              <legend><?php echo __('Total published', 'astra-child') ?></legend>
              <div class="overview-grid">
                <div class="overview-col-key a">
                  <span><?php echo __('Ads', 'astra-child'); ?></span>
                  <span style="display: inline-block; padding: 0 10px;">>></span>
                  <span><?php echo $ad_cats; ?></span>
                </div>
                <div class="overview-col-key b task"><a href="#" class="start-task" data-task-type="has-sku"><?php echo __('Linked Products', 'astra-child'); ?></a></div>
                <div class="overview-col-key c task"><a href="#" class="start-task" data-task-type="no-sku"><?php echo __('Unlinked Products', 'astra-child'); ?></a></div>
                <div class="overview-col-key d task"><a href="#" class="start-task" data-task-type="featured"><?php echo __('Featured products', 'astra-child'); ?></a></div>
                <div class="overview-col-val a"><?php echo $total_ads; ?></div>
                <div class="overview-col-val b"><?php echo count($published_has_sku); ?></div>
                <div class="overview-col-val c"><?php echo count($published_no_sku); ?></div>
                <div class="overview-col-val d"><?php echo count($featured_products); ?></div>
              </div>
            </fieldset>
          </div>
        </div>

        <div id="inconsistencies" class="hidden">
          <fieldset class="fieldset tasks" style="flex-direction: column; background-color: #fff0f0;">
            <?php $title = "Zeige alle Produkte des Shops, die auf Kleinanzeigen.de nicht mehr auffindbar sind." ?>
            <legend><?php echo __('Inconsistencies', 'astra-child') ?></legend>
            <div class="task invalid-ad">
              <div class="general-action">
                <div class="action-header">
                  <div class="">
                    <div class="task-count-wrapper warning">
                      <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    </div>
                    <span class="action-header-title"><a href="#" class="start-task info" data-task-type="invalid-ad" title="<?php echo $title ?>"><?php echo __('Invalid Ad IDs', 'astra-child') ?></a></span>
                    (<span class="task-count">0</span>)
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
                    <span class="action-header-title"><a href="#" class="start-task info" data-task-type="invalid-price" title="<?php echo $title ?>"><?php echo __('Price deviations', 'astra-child') ?></a></span>
                    (<span class="task-count">0</span>)
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

    const tasks = <?php echo json_encode($tasks) ?>;
    const {
      init_head,
      render_tasks
    } = ajax_object;

    init_head();
    render_tasks(tasks);

    $('.trigger').click(function() {
      $(this).toggleClass('active');
    })

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

  .wbp-kleinanzeigen-section .task-count-wrapper .dashicons {
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
</style>