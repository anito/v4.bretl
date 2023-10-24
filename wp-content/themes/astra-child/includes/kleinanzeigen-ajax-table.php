<?php
require_once get_stylesheet_directory() . '/includes/class-admin-kleinanzeigen-list-table.php';
if (!defined('KLEINANZEIGEN_TEMPLATE_PATH')) {
  define('KLEINANZEIGEN_TEMPLATE_PATH', get_stylesheet_directory() . '/templates/kleinanzeigen/');
}

/**
 * Action wp_ajax for fetching ajax_response
 */
function _ajax_fetch_kleinanzeigen_history()
{
  if(isset($_REQUEST['paged'])) {
    setcookie('ka-paged', $_REQUEST['paged']);
  }
  $wp_list_table = new Kleinanzeigen_List_Table();
  $wp_list_table->ajax_response();
}
add_action('wp_ajax__ajax_fetch_kleinanzeigen_history', '_ajax_fetch_kleinanzeigen_history');
add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_history', '_ajax_fetch_kleinanzeigen_history');

/**
 * Action wp_ajax for fetching the first time table structure
 */
function _ajax_fetch_kleinanzeigen_display()
{

  check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce', true);
  $wp_list_table = new Kleinanzeigen_List_Table();

  if (!isset($_COOKIE['ka-paged'])) 
  {
    setcookie('ka-paged', 1);
  }
  $paged = $_COOKIE['ka-paged'];
  $data = wbp_get_json_data(array('paged' => $paged));
  
  if (is_wp_error($data)) {
    die(json_encode(array(

      "head" => wbp_include_kleinanzeigen_template('error-message.php', true, array('message' => $data->get_error_message()))

    )));
  }
  // $categories = $data->categoriesSearchData;
  // $totalAds = array_column($categories, 'totalAds');

  $wp_list_table->setData($data);

  ob_start();
  $wp_list_table->display();
  $display = ob_get_clean();

  ob_start();
  $wp_list_table->render_head();
  $head = ob_get_clean();

  die(json_encode(compact('head', 'display')));
}
add_action('wp_ajax__ajax_fetch_kleinanzeigen_display', '_ajax_fetch_kleinanzeigen_display');
add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_display', '_ajax_fetch_kleinanzeigen_display');

function _ajax_kleinanzeigen_scan()
{
  // require_once get_stylesheet_directory() . '/includes/kleinanzeigen-ajax-table-modal.php';

  // Keep in mind wbp_get_json_data will alter the page_number cookie, so save it and reset it later if required
  $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
  $scan_type = isset($_REQUEST['scan_type']) ? $_REQUEST['scan_type'] : null;
  $wp_list_table = new Kleinanzeigen_Scan_List_Table();

  $subheader = '';
  switch ($scan_type) {
    case 'invalid-ad':
      $subheader = 'Liste von Produkten deren Anzeige nicht mehr auffindbar ist.';
      $footer_template = 'footer-invalid-ad';
      break;
    case 'invalid-price':
      $subheader = 'Auflistung von Produkten mit Preisdifferenz zwischen Shop und Kleinanzeige.';
      $footer_template = 'blank';
      $footer = '';
      break;
  }

  $ads = wbp_get_all_ads();

  $args = array(
    'status' => 'publish',
    'limit' => -1
  );
  $products = wc_get_products($args);
  $items = wbp_get_scan_data($products, $ads, $scan_type);

  setcookie('kleinanzeigen-scan-type', $scan_type);

  ob_start();
  $wp_list_table->setData($items);
  $wp_list_table->display();
  $body = ob_get_clean();

  ob_start();
  $wp_list_table->render_header(array(
    'template' => 'modal-header',
    'subheader' => $subheader,
  ));
  $header = ob_get_clean();

  ob_start();
  $wp_list_table->render_footer(array('template' => $footer_template));
  $footer = ob_get_clean();

  ob_start();
  fetch_ka_modal_script();
  $script = ob_get_clean();

  die(json_encode(compact('header', 'body', 'footer', 'script')));
}
add_action('wp_ajax__ajax_kleinanzeigen_scan', '_ajax_kleinanzeigen_scan');
add_action('wp_ajax_nopriv__ajax_kleinanzeigen_scan', '_ajax_kleinanzeigen_scan');

/**
 * fetch_ka_script function based from Charlie's original function
 */

function fetch_ka_script()
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

          $('.wp-list-kleinanzeigen-ads').addClass('loading');

          $.ajax({

            url: ajaxurl,
            dataType: 'json',
            data: {
              _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
              action: '_ajax_fetch_kleinanzeigen_display'
            },
            success: function(response) {

              $('.wp-list-kleinanzeigen-ads').removeClass('loading');

              $("#kleinanzeigen-head-wrap").html(response.head);

              $("#kleinanzeigen-list-display").html(response.display);

              $(".wp-list-kleinanzeigen-ads tbody").on("click", ".toggle-row", function(e) {
                e.preventDefault();
                $(this).closest("tr").toggleClass("is-expanded")
              });

              $('#kleinanzeigen-head-wrap .pagination a').on('click', function(e) {
                e.preventDefault();

                const query = this.search.substring(1);

                const data = {
                  paged: list.__query(query, 'paged') || '1',
                };
                list.update(data);
              })

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

          $('#kleinanzeigen-list-display .tablenav-pages a, #kleinanzeigen-list-display .manage-column.sortable a, #kleinanzeigen-list-display .manage-column.sorted a').on('click', function(e) {
            e.preventDefault();
            const query = this.search.substring(1);

            const data = {
              paged: list.__query(query, 'paged') || '1',
              order: list.__query(query, 'order') || 'asc',
              orderby: list.__query(query, 'orderby') || 'title'
            };
            list.update(data);
          });

          $('#kleinanzeigen-list-display input[name=paged]').on('keyup', function(e) {

            if (13 == e.which)
              e.preventDefault();

            const data = {
              paged: parseInt($('#kleinanzeigen-list-display input[name=paged]').val()) || '1',
              order: $('#kleinanzeigen-list-display input[name=order]').val() || 'asc',
              orderby: $('#kleinanzeigen-list-display input[name=orderby]').val() || 'title'
            };

            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(function() {
              list.update(data);
            }, delay);
          });

          $('#kleinanzeigen-list-display form').on('submit', function(e) {

            e.preventDefault();

          });

          $('.wp-list-kleinanzeigen-ads').removeClass('loading');

        },

        init_head: function() {

          $('#kleinanzeigen-head-wrap .pagination a').on('click', function(e) {
            e.preventDefault();

            const query = this.search.substring(1);

            const data = {
              paged: list.__query(query, 'paged') || '1',
            };
            list.update(data);
          })

          $('.scan-pages a.start-scan').on('click', function(e) {
            e.preventDefault();
            const el = e.target;
            const parent = $(el).parents('.scan-pages');
            const scan_type = $(el).data('scan-type');
            const restored_text = $(el).html();

            $.ajax({

              url: ajaxurl,
              dataType: 'json',
              data: {
                _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                action: '_ajax_kleinanzeigen_scan',
                scan_type
              },
              beforeSend: function(data) {
                $(el).html('Einen Moment...');
              },
              success: function(response) {
                $('#ka-list-modal-content .header').html(response['header']);
                $('#ka-list-modal-content .body').html(response['body']);
                $('#ka-list-modal-content .footer').html(response['footer']);
                $('#ka-list-modal-content .script').html(response['script']);
                $('body').addClass('show-modal');
                $(el).html(restored_text);
              },
              error: function(ajax, error, message) {
                console.log(message)
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

          $('.wp-list-kleinanzeigen-ads').addClass('loading');

          $.ajax({

            url: ajaxurl,
            data: $.extend({
                _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                action: '_ajax_fetch_kleinanzeigen_history',
              },
              data
            ),
            success: function(response) {

              response = $.parseJSON(response);

              if (response.head)
                $("#kleinanzeigen-head-wrap").html(response.head);
              if (response.rows.length)
                $('.wp-list-kleinanzeigen-ads tbody').html(response.rows);
              if (response.column_headers.length)
                $('.wp-list-kleinanzeigen-ads thead tr, tfoot tr').html(response.column_headers);
              if (response.pagination.top.length)
                $('#kleinanzeigen-list-display .tablenav.top .tablenav-pages').html($(response.pagination.top).html());
              if (response.pagination.bottom.length)
                $('#kleinanzeigen-list-display .tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());
              // if (response.row)
              //   $('#kleinanzeigen-list-display #the-list tr').html($(response.pagination.bottom).html());

              $('.wp-list-kleinanzeigen-ads').removeClass('loading');

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

add_action('admin_footer', 'fetch_ka_script');
