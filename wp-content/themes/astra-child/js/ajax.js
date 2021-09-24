jQuery(document).ready(function ($) {
  // Set Status
  $(".change-post-status").on("click", function () {
    let post_id = $(this).attr("id");
    let post_status = $(this).data("post-status");
    let that = this;
    $.ajax({
      type: "POST",
      url: ajax_object.ajaxurl,
      data: {
        action: "wbp_update_post",
        post_id,
        post_status,
      },
      success: function (response, status, options) {
        status === "success" && ajax_callback(status, that);
      },
      error: function (response, status, text) {
        status === "error" && ajax_callback(status, that);
      },
    });
    return false;
  });

  // Get Status
  $(".get-status").on("click", function () {
    let post_id = $(this).attr("id");
    let that = this;
    $.ajax({
      type: "POST",
      url: ajax_object.ajaxurl,
      data: {
        action: "wbp_get_post",
        post_id,
      },
      success: function (response, status, options) {
        status === "success" && ajax_callback(status, that);
      },
      error: function (response, status, text) {
        status === "error" && ajax_callback(status, that);
      },
    });
    return false;
  });
});

function ajax_callback(status, el) {
  let col = el.getElementsByTagName("a");
  col.item(0) && col.item(0).classList.add("ajax-message", status);
  setTimeout((_) => col.item(0).classList.remove("ajax-message", status), 2500);
}
