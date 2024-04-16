<?php
$cats = array_map(function ($category) {
  return "{$category->title} ({$category->totalAds})";
}, $categories);
$ad_cats = implode(', ', $cats);
$total_ads = array_sum(wp_list_pluck($categories, 'totalAds'));
$num_pages = ceil($total_ads / get_option('kleinanzeigen_items_per_page', ITEMS_PER_PAGE));
$todos = $products['todos'];

define('ORIENTATION_COOKIE_KEY', 'kleinanzeigen-view-o');
$getOrientationCookie = function ($val) {
  $val = isset($_COOKIE[ORIENTATION_COOKIE_KEY]) ? $_COOKIE[ORIENTATION_COOKIE_KEY] : $val;
  setcookie(ORIENTATION_COOKIE_KEY, $val, strtotime('+1 year'));
  return $val;
};
$orientation = $getOrientationCookie('horizontal');
$orientation_arr = array('cookie_key' => ORIENTATION_COOKIE_KEY, 'cookie_val' => $orientation);

$ids = wp_list_pluck((array) $items, 'id');
$keyed_items = array_combine($ids, $items);

?>
<div class="section-wrapper">
  <div class="left-sections sections">
    <div class="left-top-section">
      <section class="section-inner" style="display: flex; justify-content: space-between; padding-bottom: 0;">
        <div style="flex: 1 0 60%; ">
          <h2><?php echo __('Dashboard', 'kleinanzeigen'); ?></h2>
        </div>
        <div class="" style="display: flex; flex: 1 0 auto; align-items: center;">
          <h2 style="display: flex; justify-content: space-between; align-items: baseline; flex: 1 0 auto; padding: 0 20px; font-weight: 400;">
            <span><small><?php echo sprintf(__('Page:', 'kleinanzeigen') . ' %s', $paged) ?></small></span>
            <span><small><?php echo sprintf(__('%s out of %s ads', 'kleinanzeigen'), count($items), $total_ads); ?></small></span>
          </h2>
        </div>
      </section>
    </div>
    <div class="left-middle-section">

      <section class="wbp-kleinanzeigen">
        <div class="section-inner">
          <div class="flex flex-vertical">
            <div class="overview-wrap">
              <fieldset class="fieldset tasks">
                <legend><?php echo __('Publishings', 'kleinanzeigen') ?></legend>
                <div class="tasks">
                  <div class="task">
                    <div class="task-name">
                      <span><strong><?php echo __('Ads', 'kleinanzeigen'); ?></strong></span>
                      <span style="display: inline-block; padding: 0 10px;">>></span>
                      <span><?php echo $ad_cats; ?></span>
                    </div>
                    <div class="task-value"><strong><?php echo $total_ads; ?></strong></div>
                  </div>
                  <div class="task">
                    <div class="task-name"><strong><?php echo __('Shop products', 'kleinanzeigen'); ?></strong></div>
                    <div class="task-value"><strong><?php echo count($published_has_sku) + count($published_no_sku) ?></strong></div>
                  </div>
                  <div class="task">
                    <div class="task-name">
                      <a href="#" class="start-task" data-task-type="has-sku"><?php echo __('Linked products', 'kleinanzeigen'); ?></a>
                      <i class="dashicons dashicons-warning hidden" title="<?php echo __('Contains invalid linked products', 'kleinanzeigen') ?>"></i>
                    </div>
                    <div class="task-value"><?php echo count($published_has_sku); ?></div>
                  </div>
                  <div class="task">
                    <div class="task-name"><a href="#" class="start-task" data-task-type="no-sku"><?php echo __('Autonomous products', 'kleinanzeigen'); ?></a></div>
                    <div class="task-value"><?php echo count($published_no_sku); ?></div>
                  </div>
                  <div class="task">
                    <div class="task-name"><a href="#" class="start-task" data-task-type="featured"><?php echo __('Featured products', 'kleinanzeigen'); ?></a></div>
                    <div class="task-value"><?php echo count($featured_products); ?></div>
                  </div>
                </div>
              </fieldset>
            </div>
          </div>
          
          <div id="inconsistencies" class="hidden">
            <fieldset class="fieldset tasks" style="position: relative;">
              <?php $title = "Zeige alle Produkte des Shops, die auf Kleinanzeigen.de nicht mehr auffindbar sind." ?>
              <legend><?php echo __('Action required', 'kleinanzeigen') ?></legend>
              <div class="tasks">
                <div class="task invalid-ad">
                  <div class="task-name">
                    <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    <a href="#" class="start-task" data-task-type="invalid-ad"><?php echo __('Orphaned products', 'kleinanzeigen'); ?></a>
                  </div>
                  <div class="task-value">0</div>
                </div>
                <?php $title = "Zeige alle Produkte des Shops, deren Preise nicht mehr mit denen auf Kleinanzeigen.de übereinstimmen." ?>
                <div class="task invalid-price">
                  <div class="task-name">
                    <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    <a href="#" class="start-task" data-task-type="invalid-price"><?php echo __('Price deviations', 'kleinanzeigen'); ?></a>
                  </div>
                  <div class="task-value">0</div>
                </div>
                <?php $title = "Zeige alle Produkte die Probleme mit der Kategoriezuweisung haben." ?>
                <div class="task invalid-cat">
                  <div class="task-name">
                    <i class="dashicons dashicons-bell" title="<?php echo $title ?>"></i>
                    <a href="#" class="start-task" data-task-type="invalid-cat"><?php echo __('Improve category', 'kleinanzeigen'); ?></a>
                  </div>
                  <div class="task-value">0</div>
                </div>
              </div>
            </fieldset>
          </div>

          <div id="drafts" class="flex flex-vertical hidden">
            <div class="overview-wrap">
              <fieldset class="fieldset tasks">
                <legend><?php echo __('Drafts', 'kleinanzeigen') ?></legend>
                <div class="tasks">
                  <div class="task">
                    <div class="task-name">
                      <a href="#" class="start-task" data-task-type="drafts"><?php echo __('Non published products', 'kleinanzeigen'); ?></a>
                    </div>
                    <div class="task-value"><?php echo count($drafts_has_sku); ?></div>
                  </div>
                </div>
              </fieldset>
            </div>
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
                    <span class="icon-text"><i class="icon dashicons dashicons-visibility"></i><?php echo __('Published', 'kleinanzeigen') ?>:</span>
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
                    <span class="icon-text"><i class="icon dashicons dashicons-hidden"></i><?php echo __('Drafts', 'kleinanzeigen') ?>:</span>
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
                    <span class="icon-text"><i class="icon dashicons dashicons-editor-help"></i><?php echo __('Unknown', 'kleinanzeigen') ?>:</span>
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
                <span><?php echo __('Note, that product status will be changing to <em>Draft</em> after data import.', 'kleinanzeigen') ?></span>
              </li>
            </ul>
          </div>

        </div>
      </section>
    </div>
    <div class="left-lower-section">
      <section class="pagination">
        <?php
        $remote_url = Utils::parse_remote_url();
        for ($i = 1; $i <= $num_pages; $i++) {
        ?>
          <a href="<?php echo $remote_url . '?paged=' . $i ?>" type="button" class="button <?php echo ($i == (int) $paged ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
        <?php } ?>
      </section>
    </div>
  </div>
  <div id="color-definitions" class="right-sections sections">
    <section class="color-definition">
      <div class="section-inner">
        <div class="first-section orientation <?php echo $orientation ?>">
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status connected-publish"><?php echo count($products['publish']) ?></span>
              <span class="description"><?php echo __('Published', 'kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status connected-draft"><?php echo count($products['draft']) ?></span>
              <span class="description"><?php echo __('Hidden', 'kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status invalid"><?php echo count($products['unknown']) ?></span>
              <span class="description"><?php echo __('Unknown', 'kleinanzeigen') ?></span>
            </span>
          </div>
          <div class="box-wrapper chip">
            <span class="status-wrapper">
              <span class="color-box status disconnected"><span class="inner"><?php echo count($products['no-sku']) ?></span></span>
              <span class="description"><?php echo __('Unlinked', 'kleinanzeigen') ?></span>
            </span>
          </div>
        </div>
        <div class="summary-status">
          <?php if (!empty($todos)) : ?>
            <div class="trigger chip">
              <span class="text-wrapper">
                <i class="dashicons dashicons-bell rise-shake"></i>
                <span class="text" style="padding: 0 5px;">
                  <?php echo sprintf('%d ' . _n('Product', 'Products', 1 === count($todos), 'kleinanzeigen'), count($todos)); ?>
                </span>
              </span>
              <i class="dashicons dashicons-arrow-right"></i>
            </div>
            <div class="outer">
              <div class="content">
                <ul>
                  <?php foreach ($todos as $id => $todo) {
                    $reasons = implode(', ', $todo['reason']);
                    $title = $keyed_items[$id]->title;
                  ?>
                    <li class="todo">
                      <span class="count"><?php echo count($todo['reason']); ?></span>
                      <span class="title"><a href="#ad-id-<?php echo $id ?>"><?php echo $keyed_items[$id]->title ?></a>:</span>
                      <span class="reason"><?php echo $reasons ?></span>
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
  jQuery(document).ready(async ($) => {

    const _tasks = <?php echo json_encode($tasks) ?>;
    const _orientation_obj = <?php echo json_encode($orientation_arr) ?>;
    const {
      render_tasks,
      createOrientation
    } = {
      ...KleinanzeigenAjax,
      ...KleinanzeigenUtils
    };

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
    border: 1px solid #b0b0b0;
    border-radius: 6px;
    background-color: #f8fbff;
    padding: 10px;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 300;
    box-shadow: -29px 30px 78px 12px rgb(0 134 255 / 15%);
  }

  #kleinanzeigen-head-wrap .fieldset legend {
    color: #ffffff;
    background-color: #516329;
    font-size: 10px;
    border: 0 none;
    border-radius: 99px;
    padding: 0 10px;
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

  #kleinanzeigen-head-wrap .left-sections section h2 {
    margin-bottom: 10px;
  }

  #kleinanzeigen-head-wrap .section-inner .warning {
    color: red !important;
  }

  #kleinanzeigen-head-wrap .flex {
    display: flex;
  }

  #kleinanzeigen-head-wrap .flex.flex-vertical {
    flex-direction: column;
  }
</style>