jQuery(document).ready(function ($) {
  const { local_url, remote_url, home_url, relocate_url, screen } = ajax_object;

  // Set Status
  $("#change-post-status").on("click", function () {
    let post_id = $(this).attr("id");
    let post_status = $(this).data("post-status");
    let that = this;
    $.ajax({
      method: "POST",
      url: local_url,
      data: {
        action: "wbp_update_post",
        post_id,
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
    let post_id = $(this).attr("id");
    let that = this;
    $.ajax({
      type: "POST",
      url: local_url,
      data: {
        action: "wbp_get_post",
        post_id,
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

  function getEbayAd(e) {
    e.preventDefault();
    const formdata = $(form).serializeJSON();

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }
    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");
    const remove_spinner = () => spinner.classList.remove("is-active");
    $.post({
      url: remote_url,
      data: {
        action: "wbp_ebay_ad",
        formdata,
      },
      success: (data) => ajax_ad_callback(data, remove_spinner),
      error: (a, b, c) => {
        remove_spinner();
        console.log(a, b, c);
      },
      complete: remove_spinner,
    });
  }

  function importEbayData(e) {
    e.preventDefault();

    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const ondone = () => {
      spinner?.classList.remove("is-active");
    };

    const success = (data) => ajax_import_data_callback(data, ondone);
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

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }

    $.post({
      url: remote_url,
      data: {
        action: "wbp_ebay_ad",
        formdata,
      },
      success,
      error,
    });
  }

  function importEbayImages(e) {
    e.preventDefault();

    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const ondone = () => {
      spinner?.classList.remove("is-active");
    };

    const success = (data) => ajax_import_images_callback(data, ondone);
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

    if (!formdata.ebay_id) {
      alert(MSG_MISSING_EBAY_ID);
      return;
    }

    $.post({
      url: remote_url,
      data: {
        action: "wbp_ebay_ad",
        formdata,
      },
      success,
      error,
    });
  }

  function publishPost(e) {
    e.preventDefault();
    e.stopPropagation();

    const postId = e.target.dataset.postId;
    const spinner = e.target.parentElement.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const success = (data) => {
      if (relocate_url) {
        console.log(data);
        location = relocate_url;
      } else {
        alert(MSG_ERROR);
      }
    };
    const error = (error) => {
      console.log(error);
    };

    $.post({
      url: local_url,
      data: {
        action: "wbp_publish",
        postId,
      },
      success,
      error,
    });
  }

  function delImages(e) {
    e.preventDefault();
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
      url: local_url,
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
  const MSG_ERROR =
    "Arrgh🥶, etwas scheint schiefgegangen zu sein.\n\nBitte nochnmal versuchen.";

  let form;
  switch (screen) {
    case "edit-product":
      const importEbayDataButtons = document.querySelectorAll(
        "input.import-ebay-data"
      );
      const importEbayImagesButtons = document.querySelectorAll(
        "input.import-ebay-images"
      );
      const publishButtons = document.querySelectorAll("input.publish-post");

      importEbayDataButtons.forEach((button) => {
        const tablerowElement = button.closest("[id^=post-]");
        const ebay_id = tablerowElement?.querySelector(".sku")?.innerText;

        if (ebay_id) {
          ajax_object.importEbayData = importEbayData;
        }
      });
      importEbayImagesButtons.forEach((button) => {
        const tablerowElement = button.closest("[id^=post-]");
        const ebay_id = tablerowElement?.querySelector(".sku")?.innerText;

        if (ebay_id) {
          ajax_object.importEbayImages = importEbayImages;
        }
      });
      publishButtons.forEach((button) => {
        const post_status = button.dataset.postStatus;

        if (post_status == "draft") {
          ajax_object.publishPost = publishPost;
        }
      });
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
      delImagesButton?.addEventListener("click", delImages);

      getEbayAdButton?.removeAttribute("disabled");
      importEbayDataButton?.removeAttribute("disabled");
      importEbayImagesButton?.removeAttribute("disabled");
      delImagesButton?.removeAttribute("disabled");
      break;
  }

  function ajax_ad_callback(data, callback) {
    const response = JSON.parse(data);
    console.log(response);
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

  function ajax_import_data_callback(data, callback) {
    const handle_success = (data) => {
      const response = JSON.parse(data);
      console.log(response);

      if (success) {
        if (relocate_url) {
          location = relocate_url;
        }
      } else {
        alert(MSG_ERROR);
      }
      callback?.();
    };

    const handle_error = (data) => {
      console.log(data);
      callback?.();
    };

    const response = JSON.parse(data);
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
      url: local_url,
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
      const { relocate_url } = ajax_object;
      console.log(response);

      if (success) {
        location = relocate_url;
      } else {
        alert(MSG_ERROR);
      }
      callback?.();
    };
    const handle_error = (data) => {
      console.log(data);
      callback?.();
    };
    const response = JSON.parse(data);
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
      url: local_url,
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
    const { success } = JSON.parse(data);
    if (success) {
      location = relocate_url;
    }
    callback?.();
  }
});
