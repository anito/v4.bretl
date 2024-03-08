<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die();
}

// If class `Kleinanzeigen_Ajax_Table` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Ajax_Table')) {

  class Kleinanzeigen_Ajax_Table extends Kleinanzeigen
  {

    public function __construct()
    {

      $this->register();
      $this->loadFiles();
      $this->createTables();
    }

    public function loadFiles()
    {
      require_once $this->plugin_path('includes/class-utils.php');
      require_once $this->plugin_path('includes/class-kleinanzeigen-list-table.php');
      require_once $this->plugin_path('includes/class-kleinanzeigen-list-table-tasks.php');
    }

    public function register()
    {
      add_action('wp_ajax__ajax_kleinanzeigen_task', array($this, '_ajax_kleinanzeigen_task'));
      add_action('wp_ajax__ajax_fetch_kleinanzeigen_history', array($this, '_ajax_fetch_kleinanzeigen_history'));
      add_action('wp_ajax__ajax_fetch_kleinanzeigen_display', array($this, '_ajax_fetch_kleinanzeigen_display'));

      add_action('admin_footer', array($this, 'fetch_script'));
    }

    public function createTables()
    {
      global $wp_list_table;
      global $wp_tasks_list_table;

      $wp_list_table = new Kleinanzeigen_List_Table();
      $wp_tasks_list_table = new Kleinanzeigen_Tasks_List_Table();
    }

    /**
     * Action wp_ajax for fetching ajax_response
     */
    public function _ajax_fetch_kleinanzeigen_history()
    {
      global $wp_list_table;

      check_ajax_referer('ajax-nonce-custom-list', '_ajax_nonce_custom_list');

      if (isset($_REQUEST['paged'])) {
        setcookie('ka-paged', $_REQUEST['paged']);
      }
      $wp_list_table->ajax_response();
    }

    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function _ajax_fetch_kleinanzeigen_display()
    {
      global $wp_list_table;

      check_ajax_referer('ajax-nonce-custom-list', '_ajax_nonce_custom_list');

      $paged = 1;
      if (!isset($_COOKIE['ka-paged'])) {
        setcookie('ka-paged', $paged);
      }
      $paged = isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : $paged;
      $data = Utils::get_page_data(array('paged' => $paged));

      if (is_wp_error($data)) {
        die(json_encode(array(

          "head" => wbp_ka()->include_template('error-message.php', true, array('message' => $data->get_error_message()))

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

    public function _ajax_kleinanzeigen_task()
    {
      global $wp_tasks_list_table;
      global $ajax_modal_handler;

      check_ajax_referer('ajax-nonce-custom-list', '_ajax_nonce_custom_list');

      // Keep in mind Utils::get_remote_page_data will alter the page_number cookie, so save it and reset it later if required
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $product_ids = isset($_REQUEST['product_ids']) ? $_REQUEST['product_ids'] : array();

      $wp_tasks_list_table->set_vars($task_type);

      $args = array(
        'status' => array('publish'),
        'include' => $product_ids,
        'limit' => -1
      );
      $items = wbp_fn()->build_tasks($task_type, $args)['items'];

      setcookie('kleinanzeigen-task-type', $task_type);

      ob_start();
      $wp_tasks_list_table->setData($items);
      $wp_tasks_list_table->display();
      $body = ob_get_clean();

      ob_start();
      $wp_tasks_list_table->render_header();
      $header = ob_get_clean();

      ob_start();
      $wp_tasks_list_table->render_footer();
      $footer = ob_get_clean();

      ob_start();
      $ajax_modal_handler->fetch_script();
      $script = ob_get_clean();

      die(json_encode(compact('header', 'body', 'footer', 'script')));
    }

    /**
     * fetch_script function based from Charlie's original function
     */

    public function fetch_script()
    {
      $screen_id = wbp_fn()->get_screen_id();
      $plugin_name = parent::$plugin_name;
      if ("toplevel_page_{$plugin_name}" !== $screen_id) return;

?>

      <script type="text/javascript">
        (function($) {

          let nonce = <?php echo json_encode(wp_create_nonce('ajax-nonce-custom-list')) ?>;
          let prevented = false;

          const list = {

            prepare: () => {
              $('body').addClass('loading');
              $('#kleinanzeigen-list-display').removeClass('pointer-ready');
            },
            /**
             * method create:
             * get nonce
             */
            createNonce: async () => await KleinanzeigenAjax.getNonce?.('custom-list')
              .then(res => JSON.parse(res))
              .then(res => nonce = res),

            /**
             * method display:
             * get first sets of data
             */
            display: async function(cb) {

              if (prevented) return;
              prevented = true;

              if (cb && 'function' === typeof cb) cb();

              const _nonce = nonce || await list.createNonce()

              $.ajax({

                  url: ajaxurl,
                  dataType: 'json',
                  data: {
                    _ajax_nonce_custom_list: _nonce,
                    action: '_ajax_fetch_kleinanzeigen_display',
                  },
                  beforeSend: () => list.prepare()
                })
                .done((response) => {

                  $("#kleinanzeigen-head-wrap .summary-content").html(response.head);

                  $("#kleinanzeigen-list-display").html(response.display);

                  $(".wp-list-kleinanzeigen tbody").on("click", ".toggle-row", function(e) {
                    e.preventDefault();
                    $(this).closest("tr").toggleClass("is-expanded")
                  });

                  list.init();

                })
                .fail((response, status, message) => {

                  nonce = null; // Force display to refetch nonce
                  $("#kleinanzeigen-head-wrap .summary-content").html(`<div class="notice notice-error is-dismissible"><p>${response.status}: ${message}</p></div>`);
                  $("#kleinanzeigen-list-display").empty();

                })
                .always(() => {

                  $("body").removeClass('loading');
                  setTimeout(() => prevented = false, 5000)

                });

            },

            init: function() {

              $('#kleinanzeigen-head-wrap .pagination a, #kleinanzeigen-list-display .tablenav-pages a, #kleinanzeigen-list-display .manage-column.sortable a, #kleinanzeigen-list-display .manage-column.sorted a').on('click', function(e) {
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

                list.update(data);
              });

              $('#kleinanzeigen-head-wrap .task a.start-task').on('click', function(e) {
                e.preventDefault();
                const el = e.target;
                const data = $(el).data();
                const restored_text = $(el).html();
                const {
                  productIds: product_ids,
                  taskType: task_type
                } = data;

                $.ajax({

                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                      _ajax_nonce_custom_list: nonce,
                      action: '_ajax_kleinanzeigen_task',
                      task_type,
                      product_ids
                    },
                    beforeSend: function(data) {
                      $(el).html('Einen Moment...');
                    },
                  })
                  .done((response) => {

                    $('#ka-list-modal-content .header').html(response['header']);
                    $('#ka-list-modal-content .body').html(response['body']);
                    $('#ka-list-modal-content .footer').html(response['footer']);
                    $('#ka-list-modal-content .script').html(response['script']);
                    $('body').addClass('show-modal');
                    $(el).html(restored_text);

                  })
                  .fail((response, status, message) => {})
                  .always(() => {

                    $(el).html(restored_text);

                  })

              })

              $('#kleinanzeigen-list-display form').on('submit', (e) => e.preventDefault);
              $('#kleinanzeigen-list-display').addClass('pointer-ready');
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
                      _ajax_nonce_custom_list: nonce,
                      action: '_ajax_fetch_kleinanzeigen_history',
                    },
                    data
                  ),
                  beforeSend: list.prepare()
                })
                .done((response) => {
                  const {
                    head,
                    rows,
                    pagination,
                    column_headers,
                    tasks
                  } = $.parseJSON(response);

                  if (head) {
                    $("#kleinanzeigen-head-wrap .summary-content").html(head);
                  }
                  if (tasks) {
                    list.render_tasks(tasks);
                  }
                  if (rows.length) {
                    $('.wp-list-kleinanzeigen tbody').html(rows);
                  }
                  if (column_headers.length) {
                    $('.wp-list-kleinanzeigen thead tr, tfoot tr').html(column_headers);
                  }
                  if (pagination.top.length) {
                    $('#kleinanzeigen-list-display .tablenav.top .tablenav-pages').html($(pagination.top).html());
                  }
                  if (pagination.bottom.length) {
                    $('#kleinanzeigen-list-display .tablenav.bottom .tablenav-pages').html($(pagination.bottom).html());
                  }

                  $('body').removeClass('loading');

                  list.init();

                })
                .fail((response, status, message) => list.display())
            },
            render_tasks: (tasks) => {

              const drafts = Object.entries(tasks).filter((task) => {
                return 'drafts' === task[0] && task[1]['items'].length;
              })

              $("#drafts").toggleClass('hidden', drafts.length === 0);

              const inconsistencies = Object.entries(tasks).filter((task) => {
                return 1 === task[1].priority && task[1]['items'].length;
              })

              $("#inconsistencies").toggleClass('hidden', inconsistencies.length === 0);

              if (inconsistencies.length) {

                Object.entries(tasks).forEach((task) => {
                  const name = task[0];
                  const items = task[1]['items'];
                  const product_ids = items.map(item => item.product_id)

                  const count = items.length;
                  if (count) {
                    $(`#kleinanzeigen-head-wrap .task.${name} .task-value`).html(count);
                    $(`#kleinanzeigen-head-wrap .task.${name} a`).data('product-ids', product_ids);
                  } else {
                    $(`#kleinanzeigen-head-wrap .task.${name}`).addClass('hidden');
                    $(`#kleinanzeigen-head-wrap .task.${name} a`).addClass('disabled');
                  }

                })
              }


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
            __query: (query, variable) => {

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

          KleinanzeigenAjax.init = list.init;
          KleinanzeigenAjax.render_tasks = list.render_tasks;
          KleinanzeigenAjax.display = list.display;

        })(jQuery);
      </script>
<?php
    }
  }
}
