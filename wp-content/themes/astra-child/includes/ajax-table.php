<?php
require_once get_stylesheet_directory() . '/includes/class-admin-ebay-list-table.php';
define('EBAY_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/ebay/');

/**
 * Action wp_ajax for fetching ajax_response
 */

function _ajax_fetch_sts_history()
{
  setcookie('ebay-table-page', $_REQUEST['pageNum']);
  $wp_list_table = new Ebay_List_Table();
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

  $wp_list_table = new Ebay_List_Table();

  $pageNum = !empty($_REQUEST['pageNum']) ? $_REQUEST['pageNum'] : 1;
  $data = wbp_get_json_data($pageNum);

  if (!isset($data)) {
    echo __('Error:', 'wbp') . ' ' . __('Es wurden keine Daten empfangen', 'wbp');
  }
  $wp_list_table->setData($data);

  ob_start();
  $wp_list_table->display();
  $display = ob_get_clean();

  ob_start();
  $wp_list_table->display_head();
  $head = ob_get_clean();

  die(json_encode(array(

    "display" => $display,
    "head" => $head

  )));
}
add_action('wp_ajax__ajax_sts_display', '_ajax_sts_display');
add_action('wp_ajax_nopriv__ajax_sts_display', '_ajax_sts_display');

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

  <script type="text/javascript">
    console.log("<?php echo $screen->id; ?>")
  </script>

  <?php

  if ($screen->id != "toplevel_page_ebay")
    return;

  ?>

  <script type="text/javascript">
    (function($) {

      list = {

        /** added method display
         * for getting first sets of data
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

              $("#ts-history-table").html(response.display);

              $("#head-wrap").html(response.head);

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

          $('.pagination a').on('click', function(e) {
            e.preventDefault();

            const query = this.search.substring(1);

            const data = {
              pageNum: list.__query(query, 'pageNum') || '1',
            };
            list.update(data);
          })

          $('#ebay-list').on('submit', function(e) {

            e.preventDefault();

          });

          $('.wp-list-table').removeClass('loading');

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
              if (response.pagination.bottom.length)
                $('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
              if (response.pagination.top.length)
                $('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());
              if (response.row)
                $('#the-list tr').html($(response.pagination.bottom).html());

              $('.wp-list-table').removeClass('loading');

              list.init();
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

    })(jQuery);
  </script>
<?php
}

add_action('admin_footer', 'fetch_ts_script');