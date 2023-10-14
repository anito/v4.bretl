<?php
require_once get_stylesheet_directory() . '/includes/class-admin-kleinanzeigen-list-table.php';
define('KLEINANZEIGEN_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/kleinanzeigen/');

/**
 * Action wp_ajax for fetching ajax_response
 */

function _ajax_fetch_sts_history()
{
  setcookie('kleinanzeigen-table-page', $_REQUEST['pageNum']);
  $wp_list_table = new Kleinanzeigen_List_Table();
  $wp_list_table->ajax_response();
}
add_action('wp_ajax__ajax_fetch_sts_history', '_ajax_fetch_sts_history');
add_action('wp_ajax_nopriv__ajax_fetch_sts_history', '_ajax_fetch_sts_history');

/**
 * Action wp_ajax for fetching the first time table structure
 */

function _ajax_sts_display()
{

  check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce', true);

  $wp_list_table = new Kleinanzeigen_List_Table();

  $pageNum = !empty($_REQUEST['pageNum']) ? $_REQUEST['pageNum'] : 1;
  $data = wbp_get_json_data($pageNum);

  if (is_wp_error($data)) {
    die(json_encode(array(

      "head" => wbp_include_kleinanzeigen_template('error-message.php', true, array('message' => $data->get_error_message()))

    )));
  }

  $wp_list_table->setData($data);

  ob_start();
  $wp_list_table->display();
  $display = ob_get_clean();

  ob_start();
  $wp_list_table->render_head();
  $head = ob_get_clean();

  die(json_encode(compact('head', 'display')));
}
add_action('wp_ajax__ajax_sts_display', '_ajax_sts_display');
add_action('wp_ajax_nopriv__ajax_sts_display', '_ajax_sts_display');

function _ajax_sts_scan()
{
  // Keep in mind wbp_get_json_data will alter the page number cookie, so save it and reset it later
  $current_page = $_COOKIE['kleinanzeigen-table-page'];
  $scan_type = isset($_REQUEST['scan_type']) ? $_REQUEST['scan_type'] : null;

  $all_ads = array();
  for ($pageNum = 1; $pageNum <= KLEINANZEIGEN_TOTAL_PAGES; $pageNum++) {
    $page_data  = wbp_get_json_data($pageNum);

    if (is_wp_error($page_data)) {
      die(json_encode(array(

        "head" => wbp_include_kleinanzeigen_template('error-message.php', true, array('message' => $page_data->get_error_message()))

      )));
    }
    $all_ads = array_merge($all_ads, $page_data->ads);
  }

  $args = array(
    'status' => 'publish',
    'limit' => -1
  );
  $published_products = wc_get_products($args);

  $ad_ids = wp_list_pluck($all_ads, 'id');

  $deactivated = [];
  $price_diffs = [];
  foreach ($published_products as $product) {
    $id = $product->get_id();
    $sku = (int) $product->get_sku();
    $image = wp_get_attachment_image_url($product->get_image_id());
    $shop_price = wp_kses_post($product->get_price_html());
    $shop_price_raw = $product->get_price();
    $price = '-';
    $title = $product->get_title();

    // invalid ads
    if (!empty($sku)) {
      if (!in_array($sku, $ad_ids)) {
        $deactivated[] = compact('id', 'sku', 'image', 'title', 'shop_price', 'price');
      } else {
        $records = array_filter($all_ads, function ($ad) use ($sku) {
          return $ad->id === $sku;
        });
        if (!empty($records)) {
          $key = array_key_first($records);
          $record = $records[$key];
          if (wbp_has_price_diff($record, $product)) {
            $price = wbp_sanitize_kleinanzeigen_price($record->price);
            $ad_price = $record->price;
            $price_diffs[] = compact('id', 'sku', 'image', 'title', 'shop_price', 'price', 'ad_price');
          }
        }
      }
    }
  }

  // Reset page number
  setcookie('kleinanzeigen-table-page', $current_page);
  $response = array(
    "data" => compact('all_ads', 'deactivated', 'price_diffs')
  );

  ob_start();
  switch($scan_type) {
    case 'invalid-ad':
      wbp_include_kleinanzeigen_template('/dashboard/invalid-sku-results.php', false, $response);
      break;
    case 'invalid-price':
      wbp_include_kleinanzeigen_template('/dashboard/invalid-price-results.php', false, $response);
      break;
  }

  $content = ob_get_clean();
  die(json_encode($content));
}
add_action('wp_ajax__ajax_sts_scan', '_ajax_sts_scan');
add_action('wp_ajax_nopriv__ajax_sts_scan', '_ajax_sts_scan');

/**
 * fetch_ts_script function based from Charlie's original function
 */

function fetch_ts_script()
{
  $screen = get_current_screen();

  /**
   * For testing purpose, finding Screen ID
   */

?>

  <script>
    console.log("<?php echo $screen->id; ?>")
  </script>

  <?php

  if ("toplevel_page_kleinanzeigen" != $screen->id) {
    return;
  }

  ?>

  <script type="text/javascript">
    (function($) {

      list = {

        /**
         * method display:
         * get first sets of data
         **/

        display: function() {

          $('.wp-list-table').addClass('loading');

          $.ajax({

            url: ajaxurl,
            dataType: 'json',
            data: {
              _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
              action: '_ajax_sts_display'
            },
            success: function(response) {

              $('.wp-list-table').removeClass('loading');

              $("#head-wrap").html(response.head);

              $("#kleinanzeigen-table").html(response.display);

              $("tbody").on("click", ".toggle-row", function(e) {
                e.preventDefault();
                $(this).closest("tr").toggleClass("is-expanded")
              });

              $('.pagination a').on('click', function(e) {
                e.preventDefault();

                const query = this.search.substring(1);

                const data = {
                  pageNum: list.__query(query, 'pageNum') || '1',
                };
                list.update(data);
              })

              list.init();
            },
            error: function(ajax, error, message) {
              $("#head-wrap").html(message);
            }
          });

        },

        init: function() {

          let timeoutId;
          const delay = 500;

          $('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
            e.preventDefault();
            const query = this.search.substring(1);

            const data = {
              paged: list.__query(query, 'paged') || '1',
              order: list.__query(query, 'order') || 'asc',
              orderby: list.__query(query, 'orderby') || 'title'
            };
            list.update(data);
          });

          $('input[name=paged]').on('keyup', function(e) {

            if (13 == e.which)
              e.preventDefault();

            const data = {
              paged: parseInt($('input[name=paged]').val()) || '1',
              order: $('input[name=order]').val() || 'asc',
              orderby: $('input[name=orderby]').val() || 'title'
            };

            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(function() {
              list.update(data);
            }, delay);
          });

          $('#kleinanzeigen-list').on('submit', function(e) {

            e.preventDefault();

          });

          $('.wp-list-table').removeClass('loading');

        },

        init_head: function() {

          $('.pagination a').on('click', function(e) {
            e.preventDefault();

            const query = this.search.substring(1);

            const data = {
              pageNum: list.__query(query, 'pageNum') || '1',
            };
            list.update(data);
          })

          $('.scan-pages a.start-scan').on('click', function(e) {
            e.preventDefault();
            const el = e.target;
            const parent = $(el).parents('.scan-pages');
            const scan_type = $(el).data('scan-type');

            $.ajax({

              url: ajaxurl,
              dataType: 'json',
              data: {
                _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                action: '_ajax_sts_scan',
                scan_type
              },
              beforeSend: function(data) {},
              success: function(response) {
                $('#list-modal-content').html(response);
                $('body').addClass('show-modal');
              },
              error: function(response) {
                console.log(response)
              },
            })

          })
        },

        /** AJAX call
         *
         * Send the call and replace table parts with updated version!
         *
         * @param    object    data The data to pass through AJAX
         */
        update: function(data) {

          $('.wp-list-table').addClass('loading');

          $.ajax({

            url: ajaxurl,
            data: $.extend({
                _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                action: '_ajax_fetch_sts_history',
              },
              data
            ),
            success: function(response) {

              response = $.parseJSON(response);

              if (response.head)
                $("#head-wrap").html(response.head);
              if (response.rows.length)
                $('#the-list').html(response.rows);
              if (response.column_headers.length)
                $('thead tr, tfoot tr').html(response.column_headers);
              if (response.pagination.top.length)
                $('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
              if (response.pagination.bottom.length)
                $('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());
              if (response.row)
                $('#the-list tr').html($(response.pagination.bottom).html());

              $('.wp-list-table').removeClass('loading');

              setTimeout(list.init, 100);
            }
          });
        },

        /**
         * Filter the URL Query to extract variables
         *
         * @see http://css-tricks.com/snippets/javascript/get-url-variables/
         *
         * @param    string    query The URL query part containing the variables
         * @param    string    variable Name of the variable we want to get
         *
         * @return   string|boolean The variable value if available, false else.
         */
        __query: function(query, variable) {

          const vars = query.split("&");
          for (let i = 0; i < vars.length; i++) {
            const pair = vars[i].split("=");
            if (pair[0] == variable)
              return pair[1];
          }
          return false;
        }
      }

      list.display();

      ajax_object.init_head = list.init_head;

    })(jQuery);
  </script>
<?php
}

add_action('admin_footer', 'fetch_ts_script');
