<?php
require_once get_stylesheet_directory() . '/includes/class-admin-kleinanzeigen-scan-list-table.php';
if (!defined('KLEINANZEIGEN_TEMPLATE_PATH')) {
  define('KLEINANZEIGEN_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/kleinanzeigen/');
}

/**
 * Action wp_ajax for fetching ajax_response
 */
function _ajax_fetch_kleinanzeigen_scan_history()
{
  $wp_list_table = new Kleinanzeigen_Scan_List_Table();
  $wp_list_table->ajax_response();
}
add_action('wp_ajax__ajax_fetch_kleinanzeigen_scan_history', '_ajax_fetch_kleinanzeigen_scan_history');
add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_scan_history', '_ajax_fetch_kleinanzeigen_scan_history');

/**
 * Action wp_ajax for fetching the first time table structure
 */
function _ajax_fetch_kleinanzeigen_scan_display()
{

  check_ajax_referer('ajax-custom-scan-list-nonce', '_ajax_custom_scan_list_nonce', true);
  $scan_type = isset($_COOKIE['kleinanzeigen-scan-type']) ? $_COOKIE['kleinanzeigen-scan-type'] : '';

  $wp_list_table = new Kleinanzeigen_Scan_List_Table();

  $ads = wbp_get_all_ads();
  $args = array(
    'status' => 'publish',
    'limit' => -1
  );
  $products = wc_get_products($args);
  $data = wbp_get_scan_data($products, $ads, $scan_type);
  $wp_list_table->setData($data);

  ob_start();
  $wp_list_table->display();
  $display = ob_get_clean();

  ob_start();
  $wp_list_table->render_head();
  $head = ob_get_clean();

  die(json_encode(compact('head', 'display')));
}
add_action('wp_ajax__ajax_fetch_kleinanzeigen_scan_display', '_ajax_fetch_kleinanzeigen_scan_display');
add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_scan_display', '_ajax_fetch_kleinanzeigen_scan_display');

/**
 * fetch_ka_modal_script function based from Charlie's original function
 */
function fetch_ka_modal_script()
{

?>

  <script type="text/javascript">
    (function($) {

      const list = {

        /**
         * method display:
         * get first sets of data
         **/

        display: function() {

          $('.wp-list-scan-kleinanzeigen-ads').addClass('loading');

          $.ajax({

            url: ajaxurl,
            dataType: 'json',
            data: {
              _ajax_custom_scan_list_nonce: $('#_ajax_custom_scan_list_nonce').val(),
              action: '_ajax_fetch_kleinanzeigen_scan_display'
            },
            success: function(response) {

              $('.wp-list-scan-kleinanzeigen-ads').removeClass('loading');

              $("#kleinanzeigen-scan-list-display").html(response.display);

              $(".wp-list-scan-kleinanzeigen-ads tbody").on("click", ".toggle-row", function(e) {
                e.preventDefault();
                $(this).closest("tr").toggleClass("is-expanded")
              });

              list.init();
            },
            error: function(ajax, error, message) {
              $("#kleinanzeigen-head-wrap").html(message);
            }
          });

        },

        init: function() {

          let timeoutId;
          const delay = 500;

          $('#kleinanzeigen-scan-list-display .tablenav-pages a, #kleinanzeigen-scan-list-display .manage-column.sortable a, #kleinanzeigen-scan-list-display .manage-column.sorted a').on('click', function(e) {
            e.preventDefault();
            const query = this.search.substring(1);

            const data = {
              paged: list.__query(query, 'paged') || '1',
              order: list.__query(query, 'order') || 'asc',
              orderby: list.__query(query, 'orderby') || 'title',
              scan_type: list.__query(query, 'scan_type') || '',
            };
            list.update(data);
          });

          $('#kleinanzeigen-scan-list-display input[name=paged]').on('keyup', function(e) {

            if (13 == e.which)
              e.preventDefault();

            const data = {
              paged: parseInt($('#kleinanzeigen-scan-list-display input[name=paged]').val()) || '1',
              order: $('#kleinanzeigen-scan-list-display input[name=order]').val() || 'asc',
              orderby: $('#kleinanzeigen-scan-list-display input[name=orderby]').val() || 'title'
            };

            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(function() {
              list.update(data);
            }, delay);
          });

          $('#kleinanzeigen-scan-list').on('submit', function(e) {

            e.preventDefault();

          });

          $('.wp-list-scan-kleinanzeigen-ads').removeClass('loading');

        },

        /** AJAX call
         *
         * Send the call and replace table parts with updated version!
         *
         * @param    object    data The data to pass through AJAX
         */
        update: function(data) {

          $('.wp-list-scan-kleinanzeigen-ads').addClass('loading');

          $.ajax({

            url: ajaxurl,
            data: $.extend({
                _ajax_custom_scan_list_nonce: $('#_ajax_custom_scan_list_nonce').val(),
                action: '_ajax_fetch_kleinanzeigen_scan_history',
              },
              data
            ),
            success: function(response) {

              response = $.parseJSON(response);

              if (response.rows.length)
                $('.wp-list-scan-kleinanzeigen-ads tbody').html(response.rows);
              if (response.column_headers.length)
                $('.wp-list-scan-kleinanzeigen-ads thead tr, .wp-list-kleinanzeigen-ads tfoot tr').html(response.column_headers);
              if (response.pagination.top.length)
                $('#kleinanzeigen-scan-list-display .tablenav.top .tablenav-pages').html($(response.pagination.top).html());
              if (response.pagination.bottom.length)
                $('#kleinanzeigen-scan-list-display .tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());

              $('.wp-list-scan-kleinanzeigen-ads').removeClass('loading');

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

      // list.display();
      list.init();

    })(jQuery);
  </script>

<?php
}

add_action('admin_footer', 'fetch_ka_modal_script');
