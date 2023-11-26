<?php
$cats = array_map(function ($category) {
  return sprintf(__('%s (%s)', 'wbp-kleinanzeigen'), $category->title, $category->totalAds);
}, $categories);
$ad_cats = implode(', ', $cats);
$total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
$num_pages = ceil($total_ads / KLEINANZEIGEN_PER_PAGE);
$todos = $products['todos'];

define('ORIENTATION_COOKIE_KEY', 'kleinanzeigen-view-o');
$getOrientationCookie = function ($val) {
  $val = isset($_COOKIE[ORIENTATION_COOKIE_KEY]) ? $_COOKIE[ORIENTATION_COOKIE_KEY] : $val;
  setcookie(ORIENTATION_COOKIE_KEY, $val, strtotime('+1 year'));
  return $val;
};
$orientation = $getOrientationCookie('horizontal');
$orientation_arr = array('cookie_key' => ORIENTATION_COOKIE_KEY, 'cookie_val' => $orientation);
?>
<div class="section-wrapper">
  <div class="left-sections sections">
    <section class="wbp-kleinanzeigen">
      <div class="section-inner">
        <div style="display: flex; flex-direction: column;">
          <h2 style="display: flex; justify-content: space-between;"><?php echo sprintf(__('Page: %s', 'wbp-kleinanzeigen'), $paged) ?><small style="margin-left: 20px; font-size: 12px;"><?php echo sprintf(__('Ads: %s', 'wbp-kleinanzeigen'), count($items)); ?></small></h2>
          <div class="overview-wrap">
            <fieldset class="fieldset tasks">
              <legend><?php echo __('Total published', 'wbp-kleinanzeigen') ?></legend>
              <div class="tasks">
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
          <fieldset class="fieldset tasks">
            <?php $title = "Zeige alle Produkte des Shops, die auf Kleinanzeigen.de nicht mehr auffindbar sind." ?>
            <legend><?php echo __('Inconsistencies discovered', 'wbp-kleinanzeigen') ?></legend>
            <div class="tasks">
              <div class="task invalid-ad">
                <div class="general-action">
                  <div class="action-header">
                    <div class="">
                      <div class="task-count-wrapper">
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
                      <div class="task-count-wrapper">
                        <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                      </div>
                      <span class="action-header-title"><a href="#" class="start-task info" data-task-type="invalid-price" title="<?php echo $title ?>"><?php echo __('Price deviations', 'wbp-kleinanzeigen') ?></a></span>
                      (<span id="invalid-price-count" class="task-count">0</span>)
                    </div>
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
    <section class="wbp-shop">
      <div class="section-inner">

        <div class="divider-bottom">

          <h2>&nbsp;</h2>
          <h2><small>
              <div class="summary">
                <div class="title">
                  <span class="icon-text"><i class="icon dashicons dashicons-visibility"></i><?php echo __('Published', 'wbp-kleinanzeigen') ?>:</span>
                </div>
                <div class="info">
                  <span class="count"><?php echo count($products['publish']) ?></span>
                  <span class="indicator indicator-publish"></span>
                </div>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <div class="title">
                  <span class="icon-text"><i class="icon dashicons dashicons-hidden"></i><?php echo __('Drafts', 'wbp-kleinanzeigen') ?>:</span>
                </div>
                <div class="info">
                  <span class="count"><?php echo count($products['draft']) ?></span>
                  <span class="indicator indicator-draft"></span>
                </div>
              </div>
            </small></h2>
          <h2><small>
              <div class="summary">
                <div class="title">
                  <span class="icon-text"><i class="icon dashicons dashicons-editor-help"></i><?php echo __('Unknown', 'wbp-kleinanzeigen') ?>:</span>
                </div>
                <div class="info">
                  <span class="count"><?php echo count($products['unknown']) ?></span>
                  <span class="indicator indicator-unknown"></span>
                </div>
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
  <div id="color-definitions" class="right-sections sections">
    <section class="color-definition">
      <div class="section-inner">
        <div class="first-section orientation <?php echo $orientation ?>">
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status connected-publish"><?php echo count($products['publish']) ?></span>
              <span class="description"><?php echo __('Published', 'wbp-kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status connected-draft"><?php echo count($products['draft']) ?></span>
              <span class="description"><?php echo __('Hidden', 'wbp-kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status invalid"><?php echo count($products['unknown']) ?></span>
              <span class="description"><?php echo __('Unknown', 'wbp-kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status disconnected"><span class="inner"><?php echo count($products['no-sku']) ?></span></span>
              <span class="description"><?php echo __('Unlinked', 'wbp-kleinanzeigen') ?></span>
            </span>
          </div>
        </div>
        <div class="summary-status">
          <?php if (!empty($todos)) : ?>
            <div class="trigger chip">
              <span class="text-wrapper">
                <i class="dashicons dashicons-bell"></i>
                <span class="text">
                  <?php if (1 === count($todos)) :
                    echo sprintf(__('%d'), count($todos));
                  else :
                    echo sprintf(__('%d'), count($todos)); ?>

                  <?php endif ?>
                </span>
                <i class="dashicons dashicons-arrow-right"></i>
              </span>
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
        </div>
        <div class="view-switcher">
          <i class="dashicons dashicons-image-rotate-right" style="font-size: .7em;"></i>
        </div>
      </div>
    </section>
  </div>
</div>
<script>
  jQuery(document).ready(($) => {

    const _tasks = <?php echo json_encode($tasks) ?>;
    const _orientation_obj = <?php echo json_encode($orientation_arr) ?>;
    const {
      init_head,
      render_tasks,
      heartbeat,
      createOrientation
    } = {
      ...KleinanzeigenAjax,
      ...KleinanzeigenUtils
    };

    init_head();
    render_tasks(_tasks);

    $('.trigger', '.right-sections').click(function() {
      $(this).toggleClass('active');
    })

    if ($('#invalid-ads-count').text() > 0) {
      const el = $('.task [data-task-type="has-sku"]');
      const parent = el.closest('.task');
      $('.dashicons-warning', parent).removeClass('hidden');
    }

    const orientation = createOrientation(_orientation_obj);
    const {
      horizontal,
      vertical
    } = orientation.getValues();

    const toggleTarget = $('.right-sections .first-section');
    const cb = (val) => {
      toggleTarget.toggleClass(vertical, vertical === val);
      toggleTarget.toggleClass(horizontal, horizontal === val);
    }

    $('.view-switcher').on('click', () => {
      const val = orientation.toggle(cb);
    })

  })
</script>

<style>
  #kleinanzeigen-head-wrap .wbp-shop-section .dashicons {
    margin-right: 10px;
  }

  #kleinanzeigen-head-wrap .fieldset {
    border: 0 solid #eee;
    background-color: #f8fbff;
    padding: 10px;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 300;
  }

  #kleinanzeigen-head-wrap #inconsistencies .fieldset {
    background-color: #fff0f0;
    border-color: #f6d6d6;
  }

  #kleinanzeigen-head-wrap .fieldset legend {
    padding: 2px 5px;
    background-color: #ffffff;
    font-size: 10px;
    border: 1px solid #eee;
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

  #kleinanzeigen-head-wrap .section-inner .warning {
    color: red !important;
  }
</style>