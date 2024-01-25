<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Ajax_Table_Modal` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Ajax_Table_Modal')) {

  class Kleinanzeigen_Ajax_Table_Modal
  {

    public function __construct()
    {
      $this->register();

      global $ajax_modal_handler;
      $ajax_modal_handler = $this;
    }

    public function register()
    {

      add_action('wp_ajax__ajax_fetch_kleinanzeigen_task_display', array($this, '_ajax_fetch_kleinanzeigen_task_display'));
      add_action('wp_ajax__ajax_fetch_kleinanzeigen_task_history', array($this, '_ajax_fetch_kleinanzeigen_task_history'));

      add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_task_display', array($this, '_ajax_fetch_kleinanzeigen_task_display'));
      add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_task_history', array($this, '_ajax_fetch_kleinanzeigen_task_history'));

      add_action('admin_footer', array($this, 'fetch_script'));
    }
    /**
     * Action wp_ajax for fetching ajax_response
     */
    function _ajax_fetch_kleinanzeigen_task_history()
    {
      global $wp_tasks_list_table;

      $wp_tasks_list_table->ajax_response();
    }

    /**
     * Action wp_ajax for fetching the first time table structure
     */
    function _ajax_fetch_kleinanzeigen_task_display()
    {
      global $wp_tasks_list_table;

      check_ajax_referer('ajax-custom-task-list-nonce', '_ajax_custom_task_list_nonce', true);
      $task_type = isset($_COOKIE['kleinanzeigen-task-type']) ? $_COOKIE['kleinanzeigen-task-type'] : '';

      $args = array(
        'status' => array('publish'),
        'limit' => -1
      );
      $items = wbp_fn()->build_tasks($task_type, $args)['items'];
      $wp_tasks_list_table->setData($items);

      ob_start();
      $wp_tasks_list_table->display();
      $display = ob_get_clean();

      ob_start();
      $wp_tasks_list_table->render_head();
      $head = ob_get_clean();

      die(json_encode(compact('head', 'display')));
    }

    /**
     * fetch_script function based from Charlie's original function
     */
    public function fetch_script()
    {

?>

      <script type="text/javascript">
        (function($) {

          const list = {

            prepare: () => {
              $('body').addClass('loading');
              $('#kleinanzeigen-list-display').removeClass('pointer-ready');
            },
            /**
             * method display:
             * get first sets of data
             **/

            display: function() {

              // No need to fetch fresh data if modal isn't visible
              if (!$('body').hasClass('show-modal')) return;

              $.ajax({

                  url: ajaxurl,
                  dataType: 'json',
                  data: {
                    _ajax_custom_task_list_nonce: $('#_ajax_custom_task_list_nonce').val(),
                    action: '_ajax_fetch_kleinanzeigen_task_display'
                  },

                  beforeSend: () => list.prepare()
                })
                .done((response) => {

                  $("#kleinanzeigen-task-list-display").html(response.display);

                  $(".wp-list-kleinanzeigen-tasks tbody").on("click", ".toggle-row", function(e) {
                    e.preventDefault();
                    $(this).closest("tr").toggleClass("is-expanded")
                  });

                })
                .fail((response, status, message) => {

                  $("#kleinanzeigen-head-wrap .summary-content").html(message);

                }).always(() => {

                  $('body').removeClass('loading');

                });

            },

            init: function() {

              let timeoutId;

              $('#kleinanzeigen-task-list-display .tablenav-pages a, #kleinanzeigen-task-list-display .manage-column.sortable a, #kleinanzeigen-task-list-display .manage-column.sorted a').on('click', function(e) {
                e.preventDefault();
                const query = this.search.substring(1);

                const data = {
                  paged: list.__query(query, 'paged') || '1',
                  order: list.__query(query, 'order') || 'asc',
                  orderby: list.__query(query, 'orderby') || 'title',
                  task_type: list.__query(query, 'task_type') || '',
                };
                list.update(data);
              });

              $('#kleinanzeigen-task-list-display input[name=paged]').on('keyup', function(e) {

                if (13 == e.which)
                  e.preventDefault();

                const data = {
                  paged: parseInt($('#kleinanzeigen-task-list-display input[name=paged]').val()) || '1',
                  order: $('#kleinanzeigen-task-list-display input[name=order]').val() || 'asc',
                  orderby: $('#kleinanzeigen-task-list-display input[name=orderby]').val() || 'title'
                };

                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(function() {
                  list.update(data);
                }, 500);
              });

              $('#kleinanzeigen-task-list').on('submit', (e) => e.preventDefault);

            },

            /** AJAX call
             *
             * Send the call and replace table parts with updated version!
             *
             * @param    object    data The data to pass through AJAX
             */
            update: function(data) {

              $.ajax({

                  url: ajaxurl,
                  data: $.extend({
                      _ajax_custom_task_list_nonce: $('#_ajax_custom_task_list_nonce').val(),
                      action: '_ajax_fetch_kleinanzeigen_task_history',
                    },
                    data
                  ),
                  beforeSend: () => list.prepare()
                })
                .done((response) => {

                  response = $.parseJSON(response);

                  if (response.rows.length) {
                    $('.wp-list-kleinanzeigen-tasks tbody').html(response.rows);
                  }
                  if (response.column_headers.length) {
                    $('.wp-list-kleinanzeigen-tasks thead tr, .wp-list-kleinanzeigen tfoot tr').html(response.column_headers);
                  }
                  if (response.pagination.top.length) {
                    $('#kleinanzeigen-task-list-display .tablenav.top .tablenav-pages').html($(response.pagination.top).html());
                  }
                  if (response.pagination.bottom.length) {
                    $('#kleinanzeigen-task-list-display .tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());
                  }

                  list.init();
                })
                .always(() => {

                  $('body').removeClass('loading');

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

          list.init();

          KleinanzeigenAjax.displayModal = list.display;

        })(jQuery);
      </script>

<?php
    }
  }
}
