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

      add_action('wp_ajax_nopriv__ajax_kleinanzeigen_task', array($this, '_ajax_kleinanzeigen_task'));
      add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_history', array($this, '_ajax_fetch_kleinanzeigen_history'));
      add_action('wp_ajax_nopriv__ajax_fetch_kleinanzeigen_display', array($this, '_ajax_fetch_kleinanzeigen_display'));

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

      check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce', true);

      if (!isset($_COOKIE['ka-paged'])) {
        setcookie('ka-paged', 1);
      }
      $paged = $_COOKIE['ka-paged'];
      $data = Utils::get_json_data(array('paged' => $paged));

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

      // Keep in mind Utils::get_json_data will alter the page_number cookie, so save it and reset it later if required
      $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : (isset($_COOKIE['ka-paged']) ? $_COOKIE['ka-paged'] : 1);
      $task_type = isset($_REQUEST['task_type']) ? $_REQUEST['task_type'] : null;
      $product_ids = isset($_REQUEST['product_ids']) ? $_REQUEST['product_ids'] : array();

      $wp_tasks_list_table->set_vars($task_type);
      $ads = wbp_fn()->get_transient_data();

      $args = array(
        'status' => 'publish',
        'include' => $product_ids,
        'limit' => -1
      );
      $products = wc_get_products($args);
      $items = wbp_fn()->get_task_list_items($products, $ads, $task_type);

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

          const nonce = <?php echo json_encode(wp_create_nonce('_ajax_custom_list_nonce')) ?>

          const list = {

            /**
             * method display:
             * get first sets of data
             **/

            display: function() {

              $('body').addClass('loading');

              $.ajax({

                url: ajaxurl,
                dataType: 'json',
                data: {
                  _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                  action: '_ajax_fetch_kleinanzeigen_display',
                },
                success: function(response) {
                  $("body").removeClass('loading');

                  $("#kleinanzeigen-head-wrap .summary-content").html(response.head);

                  $("#kleinanzeigen-list-display").html(response.display);

                  $(".wp-list-kleinanzeigen tbody").on("click", ".toggle-row", function(e) {
                    e.preventDefault();
                    $(this).closest("tr").toggleClass("is-expanded")
                  });

                  list.init();
                },
                error: function(ajax, error, message) {
                  $("#kleinanzeigen-head-wrap .summary-content").html(message);
                  $("body").removeClass('loading');
                }
              });

            },

            init: function() {
              let timeoutId;

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
                    _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                    action: '_ajax_kleinanzeigen_task',
                    task_type,
                    product_ids
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

              $('#kleinanzeigen-list-display form').on('submit', (e) => e.preventDefault);

            },

            /** AJAX call
             *
             * Send the call and replace table parts with updated version!
             *
             * @param    object    data The data to pass through AJAX
             */
            update: function(data) {

              $('body').addClass('loading');

              $.ajax({
                url: ajaxurl,
                data: $.extend({
                    _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
                    action: '_ajax_fetch_kleinanzeigen_history',
                  },
                  data
                ),
                success: function(response) {
                  const {
                    head,
                    rows,
                    pagination,
                    column_headers,
                    tasks
                  } = $.parseJSON(response);

                  if (head)
                    $("#kleinanzeigen-head-wrap .summary-content").html(head);

                  if (tasks)
                    list.render_tasks(tasks);

                  if (rows.length)
                    $('.wp-list-kleinanzeigen tbody').html(rows);
                  if (column_headers.length)
                    $('.wp-list-kleinanzeigen thead tr, tfoot tr').html(column_headers);
                  if (pagination.top.length)
                    $('#kleinanzeigen-list-display .tablenav.top .tablenav-pages').html($(pagination.top).html());
                  if (pagination.bottom.length)
                    $('#kleinanzeigen-list-display .tablenav.bottom .tablenav-pages').html($(pagination.bottom).html());

                  $('body').removeClass('loading');

                  setTimeout(list.init, 1000);
                }
              });
            },
            render_tasks: function(tasks) {

              const inconsistencies = Object.entries(tasks).filter((task) => {
                return task[1].priority === 1 && task[1]['items'].length
              })

              if (!inconsistencies.length) {
                $("#inconsistencies").addClass('hidden');
                return;
              }

              $("#inconsistencies").removeClass('hidden');

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

          list.display()

          KleinanzeigenAjax.init_head = list.init_head;
          KleinanzeigenAjax.render_tasks = list.render_tasks;
          KleinanzeigenAjax.display = list.display;

        })(jQuery);
      </script>
<?php
    }
  }
}
