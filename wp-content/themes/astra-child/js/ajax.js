jQuery(document).ready(function ($) {
  // Set Status
  $("#change-post-status").on("click", function () {
    let post_id = $(this).attr("id");
    let post_status = $(this).data("post-status");
    let that = this;
    $.ajax({
      method: "POST",
      url: ajax_object.url,
      data: {
        action: "wbp_update_post",
        post_id,
        post_status,
      },
      success: function (response, status, options) {
        if (status === "success") ajax_preview_callback(status, that);
      },
      error: function (response, status, text) {
        if (status === "error") ajax_preview_callback(status, that);
      },
    });
    return false;
  });

  // Get Status
  $("#get-status").on("click", function () {
    let post_id = $(this).attr("id");
    let that = this;
    $.ajax({
      type: "POST",
      url: ajax_object.url,
      data: {
        action: "wbp_get_post",
        post_id,
      },
      success: function (response, status, options) {
        if (status === "success") ajax_preview_callback(status, that);
      },
      error: function (response, status, text) {
        if (status === "error") ajax_preview_callback(status, that);
      },
    });
    return false;
  });

  function getPreview(e) {
    e.preventDefault();
    const { url, nonce } = ajax_object;
    const formdata = $(form).serializeJSON();

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }
    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");
    const remove_spinner = () => spinner.classList.remove("is-active");
    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_ebay_preview",
        formdata,
      },
      success: (data) => ajax_preview_callback(data, remove_spinner),
      error: remove_spinner,
      complete: remove_spinner,
    });
  }

  function getData(e) {
    e.preventDefault();
    const { url, nonce } = ajax_object;
    const formdata = $(form).serializeJSON();

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }

    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");
    const remove_spinner = () => spinner.classList.remove("is-active");
    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_ebay_preview",
        formdata,
      },
      success: (data) => ajax_import_data_callback(data, remove_spinner),
      error: remove_spinner,
    });
  }

  function getImages(e) {
    e.preventDefault();
    const { url, nonce } = ajax_object;
    const formdata = $(form).serializeJSON();

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }
    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");
    const remove_spinner = () => spinner.classList.remove("is-active");
    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_ebay_preview",
        formdata,
      },
      success: (data) => ajax_import_images_callback(data, remove_spinner),
      error: remove_spinner,
    });
  }

  function delImages(e) {
    e.preventDefault();
    const { url, nonce } = ajax_object;
    const formdata = $(form).serializeJSON();

    if (!formdata.post_ID) {
      alert(MSG_MISSING_POST_ID);
      return;
    }
    const post_id = formdata.post_ID;

    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");
    const remove_spinner = () => spinner.classList.remove("is-active");
    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_del_images",
        post_id,
      },
      success: (data) => ajax_del_images_callback(data, remove_spinner),
      error: remove_spinner,
    });
  }

  const MSG_MISSING_EBAY_ID = "Keine eBay-Kleinanzeigen ID gefunden.";
  const MSG_MISSING_POST_ID = "Keine Post ID gefunden.";
  const form = document.getElementById("post");
  const getdataButton = document.getElementById("get-ebay-data");
  const getimagesButton = document.getElementById("get-ebay-images");
  const delimagesButton = document.getElementById("del-images");
  const getpreviewButton = document.getElementById("get-ebay-preview");

  getpreviewButton?.addEventListener("click", getPreview);
  getimagesButton?.addEventListener("click", getImages);
  delimagesButton?.addEventListener("click", delImages);
  getdataButton?.addEventListener("click", getData);

  function ajax_preview_callback(data, callback) {
    const response = JSON.parse(data);
    const wrapper = document.getElementById("ebay-preview-wrapper");

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

  function ajax_import_data_callback(data, callback) {
    const handle_success = (data) => {
      const response = JSON.parse(data);
      console.log(response);

      if (response.success) {
        location = response.data.redirect;
      } else {
        alert(
          "Sorry, etwas scheint schiefgegangen zu sein.. Versuche es bitte nochnmal."
        );
      }
      callback?.();
    };
    const handle_error = (data) => {
      console.log(data);
      callback?.();
    };
    const response = JSON.parse(data);
    const { url, nonce } = ajax_object;
    const { post_id, ebay_id, post_status, content } = response;
    const postdata = { post_id, ebay_id, post_status };
    let doc;
    try {
      const parser = new DOMParser();
      doc = parser.parseFromString(content.body, "text/html");
    } catch (err) {
      console.error(err);
      return;
    }

    const title_raw = doc.getElementById("viewad-title")?.innerText;
    const price_raw = doc.getElementById("viewad-price")?.innerText;
    const description = doc.getElementById(
      "viewad-description-text"
    )?.outerHTML;
    const title = title_raw?.replace(/\s*/, "");
    const price = price_raw?.replace(/[\s\.€]*/g, "");
    const ebaydata = { title, price, description };

    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_ebay_data",
        postdata,
        ebaydata,
      },
      success: handle_success,
      error: handle_error,
    });
  }

  function ajax_import_images_callback(data, callback) {
    const handle_success = (data) => {
      const response = JSON.parse(data);
      console.log(response);

      if (response.success) {
        location = response.data.redirect;
      } else {
        alert("ERROR");
      }
      callback?.();
    };
    const handle_error = (data) => {
      console.log(data);
      callback?.();
    };
    const response = JSON.parse(data);
    const { url, nonce } = ajax_object;
    const { post_id, ebay_id, post_status, content } = response;
    const postdata = { post_id, ebay_id, post_status };

    let doc;
    try {
      const parser = new DOMParser();
      doc = parser.parseFromString(content.body, "text/html");
    } catch (err) {
      console.error(err);
      return;
    }

    let images = [];
    doc.documentElement
      .querySelectorAll("#viewad-product .galleryimage-large img[data-imgsrc]")
      .forEach((image) => {
        images.push(image.dataset.imgsrc);
      });

    const ebaydata = { images };

    $.post({
      url: `${url}?_nonce=${nonce}`,
      data: {
        action: "wbp_ebay_images",
        postdata,
        ebaydata,
      },
      success: handle_success,
      error: handle_error,
    });
  }

  function ajax_del_images_callback(data, callback) {
    const {
      success,
      data: { redirect },
    } = JSON.parse(data);
    if (success) {
      location = redirect;
    }
    callback?.();
  }
});
