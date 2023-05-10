jQuery(document).ready(function ($) {
  const {
    admin_ajax_local,
    admin_ajax_remote,
    home_url,
    relocate_url,
    screen,
  } = ajax_object;

  return;
  // Get eBay JSON
  let post_ID = $(this).attr("id");
  let post_status = $(this).data("post-status");
  let that = this;
  $.ajax({
    method: "POST",
    url: admin_ajax_local,
    data: {
      action: "wbp_ebay_json_data",
    },
    success: function (response, status, options) {
      if (status === "success") console.log(response);
    },
    error: function (response, status, text) {
      if (status === "error") console.log(response, text);
    },
  });
  
  function ajax_json_data_callback(data, callback) {
    const response = JSON.parse(data);

    const wrapper = document.getElementById("ebay-ad-wrapper");
    if (wrapper) {
      const iframe =
        wrapper.querySelector("iframe") || document.createElement("iframe");
      iframe.src = "";
      wrapper.innerHTML = "";
      wrapper.appendChild(iframe);
      iframe.contentWindow.document.open();
      if (iframe.contentWindow.document) {
        try {
          iframe.contentWindow.document.write(
            response.content?.body ||
              "<h1>Arrgh🥶, etwas scheint schiefgegangen zu sein...</h1>"
          );
        } catch (err) {}
      }
      iframe.contentWindow.document.close();
      wrapper.setAttribute("style", "height:400px;");
      iframe.setAttribute("style", "height:100%; width:100%;");
      callback?.();
    }
  }
});
