jQuery(document).ready(function ($) {
  const {
    admin_ajax_local,
    admin_ajax_remote,
    relocate_url,
    screen,
    edit_link,
  } = ajax_object;

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
    document.body.classList.add("kleinanzeigen-sync-active");
  }

  function finish() {
    document.body.classList.remove("kleinanzeigen-sync-active");
  }

  function getAd(e) {
    e.preventDefault();

    const formdata = $(form).serializeJSON();

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    const addSpinner = () => {
      spinner?.classList.add("is-active");
    };
    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
      },
      success: (data) =>
        ajax_ad_callback(data, () => {
          removeSpinner();
          finish();
        }),
      error: removeSpinner,
    });
  }

  function connect(e) {
    e.preventDefault();

    const el = e.target;
    const action = el.dataset.action;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const post_ID = action.replace("connect-", "");

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    const addSpinner = () => {
      spinner?.classList.add("is-active");
    };
    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    addSpinner();

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_connect",
        post_ID,
        kleinanzeigen_id,
        screen
      },
      beforeSend: () => {
        $(el).html("Verknüpfe...");
      },
      success: (data) => {
        $(el).html("Fertig");

        removeSpinner();

        setTimeout(() => {
          parseResponse(data, el);
        }, 500);
      },
      error: (error) => {
        $(el).parents("td").removeClass("busy");
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("kleinanzeigen:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();
        console.log(error);
      },
    });
  }

  function disconnect(e) {
    e.preventDefault();

    const el = e.target;
    const action = el.dataset.action;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const post_ID = action.replace("disconnect-", "");

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    const addSpinner = () => {
      spinner?.classList.add("is-active");
    };
    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    addSpinner();

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_disconnect",
        post_ID,
        kleinanzeigen_id,
        screen
      },
      beforeSend: () => {
        $(el).html("Verknüpfung lösen...");
      },
      success: (data) => {
        $(el).html("Fertig");

        removeSpinner();

        setTimeout(() => {
          parseResponse(data, el);
        }, 500);
      },
      error: (error) => {
        $(el).parents("td").removeClass("busy");
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("kleinanzeigen:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();
        console.log(error);
      },
    });
  }

  function createPost(e) {
    importData(e);
  }

  function importData(e) {
    e.preventDefault();

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const target = e.target;
      const post_ID = target.dataset.postId || "";
      const kleinanzeigen_id = target.dataset.kleinanzeigenId || "";
      formdata = { post_ID, kleinanzeigen_id };
    }

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");

    const addSpinner = () => {
      spinner?.classList.add("is-active");
    };
    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
        screen,
      },
      beforeSend: () => {
        $(el).parents("td").addClass("busy");
        $(el).html("Hole Daten...");
      },
      success: (data) => {
        const json = JSON.parse(data);
        if (json.content.response?.code === 200) {
          $(el).html("Verarbeite...");
          setTimeout(() => {
            processDataImport(json, el, removeSpinner);
          }, 500);
        } else {
          $(el).parents("td").removeClass("busy");
          $(el).html("Fehler");
          removeSpinner();
        }
      },
      error: (error) => {
        $(el).parents("td").removeClass("busy");
        $(el).html("Fehler");

        el.dispatchEvent(new CustomEvent("kleinanzeigen:data-import"), {
          detail: { success: false, error },
        });

        removeSpinner();

        const { responseText } = error;
        console.log(responseText);
      },
    });
  }

  function importImages(e) {
    e.preventDefault();

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const el = e.target;
      const post_ID = el.dataset.postId || "";
      const kleinanzeigen_id = el.dataset.kleinanzeigenId || "";
      formdata = { post_ID, kleinanzeigen_id };
    }

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");

    const addSpinner = () => {
      spinner?.classList.add("is-active");
    };
    const removeSpinner = () => {
      spinner?.classList.remove("is-active");
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax_remote,
      data: {
        action: "_ajax_get_remote",
        formdata,
        screen,
      },
      beforeSend: () => {
        $(el).parents("td").addClass("busy");
        $(el).html("Hole Fotos...");
      },
      success: (data) => {
        const json = JSON.parse(data);
        if (json.content.response?.code === 200) {
          $(el).html("Verarbeite...");

          setTimeout(() => {
            processImageImport(json, el, removeSpinner);
          }, 500);
        } else {
          $(el).parents("td").removeClass("busy");
          $(el).html("Fehler");
          removeSpinner();
        }
      },
      error: (error) => {
        spinner?.classList.remove("is-active");
        console.log(error);
      },
    });
  }

  function deleteImages(e) {
    e.preventDefault();
    start();

    const el = e.target;

    let post_ID;
    if (form) {
      formdata = $(form).serializeJSON();
      post_ID = formdata.post_ID;
    } else {
      post_ID = el?.dataset.postId;
    }

    if (!post_ID) {
      alert(MSG_MISSING_POST_ID);
      return;
    }

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    const error_callback = () => {
      el.innerHTML = "Fehler";
      $(el).parents("td").removeClass("busy");
      spinner?.classList.remove("is-active");
    };

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_delete_images",
        post_ID,
      },
      success: (data) => parseResponse(data, el),
      error: error_callback,
    });
  }

  function publishPost(e) {
    e.preventDefault();
    e.stopPropagation();
    start();

    const el = e.target;
    const post_ID = el.dataset.postId;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;

    const spinner = el.closest("[id*=-action]")?.querySelector(".spinner");
    spinner?.classList.add("is-active");

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_publish_post",
        post_ID,
        kleinanzeigen_id,
        screen,
      },
      beforeSend: () => {
        $(el).parents("td").addClass("busy");
        $(el).html("Einen Moment...");
      },
      success: (data) => parseResponse(data, el),
      error: (error) => console.log(error),
    });
  }

  function deletePost(e) {
    const el = e.target;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const post_ID = el.dataset.postId;

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_delete_post",
        post_ID,
        kleinanzeigen_id,
      },
      success: (data) => parseResponse(data, el),
      error: (error) => console.log(error),
    });
  }

  function parseResponse(data, el, callback) {
    const {
      data: { row, head, post_ID, kleinanzeigen_id },
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

      case "toplevel_page_kleinanzeigen":
        rowEl = el.closest(`tr#ad-id-${kleinanzeigen_id}`);
        if (head) $("#head-wrap").html(head);
        el.dispatchEvent(
          new CustomEvent("data:action", {
            detail: { data: $(el).data() },
          })
        );
        $(rowEl)?.replaceWith(row);

        if ("create" === el.dataset.action) {
          rowEl = document.querySelector(`tr#ad-id-${kleinanzeigen_id}`);

          setTimeout(() => {
            rowEl.dispatchEvent(
              new CustomEvent("data:action", {
                detail: { action: el.dataset.action },
              })
            );
          }, 200);
        }
        break;
    }
    callback?.();
    finish();
  }

  const MSG_MISSING_KLEINANZEIGEN_ID = "Keine Kleinanzeigen ID gefunden.";
  const MSG_MISSING_POST_ID = "Keine Post ID gefunden.";
  const MSG_ERROR =
    "Arrgh🥶, etwas scheint schiefgegangen zu sein. Bitte noch einmal versuchen.";

  let form;
  switch (screen) {
    case "toplevel_page_kleinanzeigen":
    case "edit-product":
      ajax_object = {
        ...ajax_object,
        createPost,
        deletePost,
        publishPost,
        importData,
        connect,
        disconnect,
        importImages,
        deleteImages,
        processDataImport,
      };
      break;
    case "product":
      form = document.getElementById("post");

      const getAdButton = document.getElementById("get-kleinanzeigen-ad");
      const importDataButton = document.getElementById(
        "import-kleinanzeigen-data"
      );
      const importImagesButton = document.getElementById(
        "import-kleinanzeigen-images"
      );
      const delImagesButton = document.getElementById("del-images");

      importDataButton?.addEventListener("click", importData);
      importImagesButton?.addEventListener("click", importImages);
      getAdButton?.addEventListener("click", getAd);
      delImagesButton?.addEventListener("click", deleteImages);

      getAdButton?.removeAttribute("disabled");
      importDataButton?.removeAttribute("disabled");
      importImagesButton?.removeAttribute("disabled");
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

    const wrapper = document.getElementById("kleinanzeigen-ad-wrapper");
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

  function processDataImport(json, el, callback = () => {}) {
    const { title, price: raw_price, tags: raw_tags, excerpt } = $(el).data();
    // const title = raw_title?.replace(/\s*/, "");
    const tags = raw_tags?.split(",");
    const price = raw_price?.replace(/[\s\.€]*/g, "");
    const { post_ID, kleinanzeigen_id, post_status, content, screen } = json;
    const postdata = { post_ID, kleinanzeigen_id, post_status };

    let doc, description;
    try {
      const parser = new DOMParser();
      doc = parser.parseFromString(content.body, "text/html");
      description = doc.getElementById(
        "viewad-description-text"
      )?.outerHTML;
    } catch (err) {
      description = `Document parse error: ${err}`;
    }

    // const raw_title = doc.getElementById("viewad-title")?.innerText;
    // const raw_price = doc.getElementById("viewad-price")?.innerText;
    
    const kleinanzeigendata = {
      title,
      price,
      description,
      excerpt,
      tags,
    };

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_import_kleinanzeigen_data",
        postdata,
        kleinanzeigendata,
        screen,
      },
      success: (data) => {
        el.dispatchEvent(
          new CustomEvent("kleinanzeigen:data-import", {
            detail: { success: true, data },
          })
        );
        $(el).html("Fertig");
        setTimeout(() => parseResponse(data, el, callback), 2000);
      },
      error: (data) => {
        console.log(data);
        el.dispatchEvent(
          new CustomEvent("kleinanzeigen:data-import", {
            detail: { success: false },
          })
        );
        callback?.();
      },
    });
  }

  function processImageImport(json, el, callback) {
    const { post_ID, kleinanzeigen_id, post_status, content, screen } = json;
    const postdata = { post_ID, kleinanzeigen_id, post_status };

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

    const kleinanzeigendata = { images };

    if (images.length) {
      msg = `${images.length} Fotos wurden importiert.`;
    } else {
      msg = "Es wurden keine Fotos importiert.";
    }

    $.post({
      url: admin_ajax_local,
      data: {
        action: "_ajax_import_kleinanzeigen_images",
        postdata,
        kleinanzeigendata,
        screen,
      },
      success: (data) => {
        $(el).html("Fertig");
        if (!$(el).data("bulk-action")) {
          alert(msg);
        } else {
          $(el).data("image-count", images.length);
        }
        setTimeout(() => parseResponse(data, el, callback), 2000);
      },
      error: (error) => {
        console.log(error);
      },
    });
  }
});
