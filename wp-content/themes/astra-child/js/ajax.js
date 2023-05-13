jQuery(document).ready(function ($) {
  const {
    admin_ajax_local,
    admin_ajax_remote,
    relocate_url,
    screen,
    edit_link,
  } = ajax_object;

  // Get eBay JSON
  $("#get_ebay_json_data").on("click", function () {
    let post_ID = $(this).attr("id");
    let post_status = $(this).data("post-status");
    let that = this;
    $.ajax({
      method: "POST",
      url: admin_ajax_local,
      data: {
        action: "_ajax_ebay_json_data",
      },
      success: function (response, status, options) {
        if (status === "success") ajax_json_data_callback(status, that);
      },
      error: function (response, status, text) {
        if (status === "error") ajax_json_data_callback(status, that);
      },
    });
    return false;
  });

  // Set Status
  $("#change-post-status").on("click", function () {
    let post_ID = $(this).attr("id");
    let post_status = $(this).data("post-status");
    let that = this;
    $.ajax({
      method: "POST",
      url: admin_ajax_local,
      data: {
        action: "_ajax_update_post",
        post_ID,
        post_status,
      },
      success: function (response, status, options) {
        if (status === "success") ajax_ad_callback(status, that);
      },
      error: function (response, status, text) {
        if (status === "error") ajax_ad_callback(status, that);
      },
    });
    return false;
  });

  // Get Status
  $("#get-status").on("click", function () {
    let post_ID = $(this).attr("id");
    let that = this;
    $.ajax({
      type: "POST",
      url: admin_ajax_local,
      data: {
        action: "_ajax_get_post",
        post_ID,
      },
      success: function (response, status, options) {
        if (status === "success") ajax_ad_callback(status, that);
      },
      error: function (response, status, text) {
        if (status === "error") ajax_ad_callback(status, that);
      },
    });
    return false;
  });

  function start() {
    document.body.classList.add("ebay-sync-active");
  }

  function finish() {
    document.body.classList.remove("ebay-sync-active");
  }

  function getEbayAd(e) {
    e.preventDefault();
    start();

    const formdata = $(form).serializeJSON();

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }
    const spinner = e.target
      .closest("[id*=-action]")
      ?.querySelector(".spinner");
    const remove_spinner = () => spinner?.classList.remove("is-active");
    spinner?.classList.add("is-active");

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
      },
      success: (data) =>
        ajax_ad_callback(data, () => {
          remove_spinner();
          finish();
        }),
      error: remove_spinner,
    });
  }

  function connectEbayData(e) {
    e.preventDefault();

    const el = e.target;
    const action = el.dataset.action;
    const ebay_id = el.dataset.ebayId;
    const post_ID = action.replace("connect-", "");

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_connect",
        post_ID,
        ebay_id
      },
      beforeSend: () => {
        $(el).html("Verknüpfe");
      },
      success: (data) => {
        $(el).html("Fertig");

        removeSpinner();

        setTimeout(() => {
          parseResponse(data, el);
        }, 500);
      },
      error: (error) => {
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("ebay:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();
        console.log(error);
      },
    })
  }

  function disconnectEbayData(e) {
    e.preventDefault();

    const el = e.target;
    const action = el.dataset.action;
    const ebay_id = el.dataset.ebayId;
    const post_ID = action.replace("disconnect-", "");

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_disconnect",
        post_ID,
        ebay_id
      },
      beforeSend: () => {
        $(el).html("Entknüpfe");
      },
      success: (data) => {
        $(el).html("Fertig");

        removeSpinner();

        setTimeout(() => {
          parseResponse(data, el);
        }, 500);
      },
      error: (error) => {
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("ebay:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();
        console.log(error);
      },
    })
  }

  function importEbayData(e) {
    e.preventDefault();
    start();

    const el = e.target;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const target = e.target;
      const post_ID = target.dataset.postId;
      const ebay_id = target.dataset.ebayId;
      formdata = { post_ID, ebay_id };
    }

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
      },
      beforeSend: () => {
        $(el).html("Hole Daten...");
      },
      success: (ebaydata) => {
        $(el).html("Verarbeite...");

        removeSpinner();

        setTimeout(() => {
          processDataImport(ebaydata, el);
        }, 500);
      },
      error: (error) => {
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("ebay:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();
        console.log(error);
      },
    });
  }

  function importEbayImages(e) {
    e.preventDefault();
    start();

    const el = e.target;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const ondone = () => {
      spinner?.classList.remove("is-active");
    };

    const success = (data) => {
      processImageImport(data, el);
    };

    const error = (error) => {
      ondone();
      console.log(error);
    };

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const button = e.target;
      const post_ID = button.dataset.postId;
      const ebay_id = button.dataset.ebayId;
      formdata = { post_ID, ebay_id };
    }
    const { post_ID } = formdata;

    console.log(formdata);

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
      },
      success,
      error,
    });
  }

  function deleteEbayImages(e) {
    e.preventDefault();
    start();

    const el = e.target;

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const post_ID = el?.dataset.postId;
      const ebay_id = el?.dataset.ebayId;
      formdata = { post_ID, ebay_id };
    }

    if (!formdata.post_ID) {
      alert(MSG_MISSING_POST_ID);
      return;
    }
    const post_ID = formdata.post_ID;

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    const remove_spinner = () => spinner?.classList.remove("is-active");
    spinner?.classList.add("is-active");

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_delete_images",
        post_ID,
      },
      success: (data) => parseResponse(data, el),
      error: remove_spinner,
    });
  }

  function publishPost(e) {
    e.preventDefault();
    e.stopPropagation();
    start();

    const el = e.target;
    const post_ID = el.dataset.postId;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_publish_post",
        post_ID,
      },
      success: (data) => parseResponse(data, el),
      error: (error) => console.log(error),
    });
  }

  function deletePost(e) {
    const el = e.target;
    const ebay_id = el.dataset.ebayId;
    const post_ID = el.dataset.postId;

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_delete_post",
        post_ID,
        ebay_id,
      },
      success: (data) => parseResponse(data, el),
      error: (error) => console.log(error),
    });
  }

  function parseResponse(data, el) {
    
    const {
      data: { row, post_ID, ebay_id },
    } = JSON.parse(data);
    
    let rowEl;
    switch (screen) {
      case "product":
        location = `${edit_link}${post_ID}`;
        break;
      case "edit-product":
        rowEl = el.closest(`#post-${post_ID}`);
        $(rowEl)?.replaceWith(row);
        break;
      case "toplevel_page_ebay":
        rowEl = el.closest(`tr#ad-id-${ebay_id}`);
        $(rowEl)?.replaceWith(row);
        break;
    }
    finish();
  }

  const MSG_MISSING_EBAY_ID = "Keine eBay-Kleinanzeigen ID gefunden.";
  const MSG_MISSING_POST_ID = "Keine Post ID gefunden.";
  const MSG_ERROR =
    "Arrgh🥶, etwas scheint schiefgegangen zu sein. Bitte noch einmal versuchen.";

  let form;
  switch (screen) {
    case "toplevel_page_ebay":
    case "edit-product":
      ajax_object = {
        ...ajax_object,
        deletePost,
        publishPost,
        importEbayData,
        connectEbayData,
        disconnectEbayData,
        importEbayImages,
        deleteEbayImages,
        processDataImport,
      };
      break;
    case "product":
      form = document.getElementById("post");

      const getEbayAdButton = document.getElementById("get-ebay-ad");
      const importEbayDataButton = document.getElementById("import-ebay-data");
      const importEbayImagesButton =
        document.getElementById("import-ebay-images");
      const delImagesButton = document.getElementById("del-images");

      importEbayDataButton?.addEventListener("click", importEbayData);
      importEbayImagesButton?.addEventListener("click", importEbayImages);
      getEbayAdButton?.addEventListener("click", getEbayAd);
      delImagesButton?.addEventListener("click", deleteEbayImages);

      getEbayAdButton?.removeAttribute("disabled");
      importEbayDataButton?.removeAttribute("disabled");
      importEbayImagesButton?.removeAttribute("disabled");
      delImagesButton?.removeAttribute("disabled");
      break;
  }

  function relocate() {
    if (-1 !== relocate_url?.indexOf("post-new.php")) {
      location = relocate_url;
    }
  }

  function ajax_ad_callback(data, callback) {
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
            response.content?.body || `<h1>${MSG_ERROR}</h1>`
          );
        } catch (err) {}
      }
      iframe.contentWindow.document.close();
      wrapper.setAttribute("style", "height:400px;");
      iframe.setAttribute("style", "height:100%; width:100%;");
      callback?.();
    }
  }

  function processDataImport(data, el, callback = () => {}) {
    const handle_success = (data) => {
      parseResponse(data, el);
      el.dispatchEvent(
        new CustomEvent("ebay:data-import", { detail: { success: true, data } })
      );
    };

    const handle_error = (data) => {
      console.log(data);
      el.dispatchEvent(
        new CustomEvent("ebay:data-import", { detail: { success: false } })
      );
      callback?.();
    };

    const response = JSON.parse(data);

    const { post_ID, ebay_id, post_status, content } = response;
    const postdata = { post_ID, ebay_id, post_status };
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
      url: admin_ajax_local,
      data: {
        action: "_ajax_import_ebay_data",
        postdata,
        ebaydata,
      },
      success: handle_success,
      error: handle_error,
    });
  }

  function processImageImport(data, el) {
    const handle_success = (data) => {
      alert(msg);
      parseResponse(data, el);
    };
    const handle_error = (error) => {
      console.log(error);
    };
    const response = JSON.parse(data);
    const { post_ID, ebay_id, post_status, content } = response;
    const postdata = { post_ID, ebay_id, post_status };

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

    if (images.length) {
      msg = `${images.length} Fotos wurden importiert.`;
    } else {
      msg = "Es konnten keine Fotos importiert werden.";
    }

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_import_ebay_images",
        postdata,
        ebaydata,
      },
      success: handle_success,
      error: handle_error,
    });
  }
});
