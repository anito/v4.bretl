jQuery(document).ready(function ($) {
  const { admin_ajax, plugin_name, screen, edit_link } = KleinanzeigenAjax;

  // hide controls if sku is not available
  const skuInputEl = document.getElementById('_kleinanzeigen_id');

  if (skuInputEl?.getAttribute('value')) {
    const sku_metabox = document
      .getElementById('_kleinanzeigen_id')
      ?.closest('.postbox');
    sku_metabox?.classList.add('sku');
  }

  const delayed_item_click = async (arr, selector, eventType) => {
    await arr
      .reduce(async (a, item) => {
        await a;
        await new Promise((resolve) => {
          const el = $(`tr#${item.post_ID} ${selector}`);
          $(el).on('data:parsed', function (e) {
            return resolve(e.detail);
          });
          $(el).click();
        });
      }, Promise.resolve())
      .then(() => {
        document.dispatchEvent(new CustomEvent(eventType));
      });
  };

  document.addEventListener('toggle-publish:item', function (e) {
    publishPost(e.detail.e);
  });
  document.addEventListener('deactivate:item', function (e) {
    publishPost(e.detail.e);
  });
  document.addEventListener('disconnect:item', function (e) {
    disconnect(e.detail.e);
  });
  document.addEventListener('save:item', function (e) {
    const { e: event, action = '' } = e.detail;
    savePost(event, action);
  });
  document.addEventListener('fixprice:item', function (e) {
    fixPrice(e.detail.e);
  });
  document.addEventListener('deactivate:all', async function (e) {
    const { products } = e.detail.data;
    delayed_item_click(products, '.deactivate', 'deactivated:all');
  });
  document.addEventListener('disconnect:all', async function (e) {
    const { products } = e.detail.data;
    delayed_item_click(products, '.disconnect', 'disconnected:all');
  });
  document.addEventListener('fixprice:all', async function (e) {
    const { products } = e.detail.data;
    delayed_item_click(products, '.fix-price', 'fixed-price:all');
  });

  // Set Status
  $('#change-post-status').on('click', function () {
    let post_ID = $(this).attr('id');
    let post_status = $(this).data('post-status');
    let that = this;
    $.ajax({
      method: 'POST',
      url: admin_ajax,
      data: {
        action: '_ajax_update_post',
        post_ID,
        post_status,
      },
      success: (response, status, options) => {
        if (status === 'success') ajax_ad_callback(status, that);
      },
      error: (response, status, message) => {
        if (status === 'error') ajax_ad_callback(status, that);
      },
    });
    return false;
  });

  // Get Status
  $('#get-status').on('click', function () {
    let post_ID = $(this).attr('id');
    let that = this;
    $.ajax({
      type: 'POST',
      url: admin_ajax,
      data: {
        action: '_ajax_get_post',
        post_ID,
      },
      success: (response, status, options) => {
        if (status === 'success') ajax_ad_callback(status, that);
      },
      error: defaultErrorHandler,
    });
    return false;
  });

  function start() {
    document.body.classList.add('kleinanzeigen-sync-active');
  }

  function finish() {
    document.body.classList.remove('kleinanzeigen-sync-active');
  }

  function getAd(e) {
    e.preventDefault();

    const formdata = $(form).serializeJSON();

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    const addSpinner = () => {
      spinner?.classList.add('is-active');
    };
    const removeSpinner = () => {
      spinner?.classList.remove('is-active');
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_get_remote',
        formdata,
      },
      success: (response) =>
        ajax_ad_callback(response, () => {
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
    const post_ID = action.replace('connect-', '');

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    const addSpinner = () => {
      spinner?.classList.add('is-active');
    };
    const removeSpinner = () => {
      spinner?.classList.remove('is-active');
    };

    addSpinner();

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_connect',
        post_ID,
        kleinanzeigen_id,
        screen,
      },
      beforeSend: () => {
        $(el).html('Verknüpfe...');
      },
      success: (response) => {
        $(el).html('Fertig');

        removeSpinner();

        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: (response, status, message) => {
        $(el).parents('td').removeClass('busy');
        $(el).html('Fehler');

        el.dispatchEvent(new CustomEvent('kleinanzeigen:data-import'), {
          detail: { success: false, message },
        });

        removeSpinner();
        console.log(message);
      },
    });
  }

  function disconnect(e) {
    e.preventDefault();

    const el = e.target;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const post_ID = el.dataset.postId;
    const task_type = el.dataset.taskType;
    const _screen = el.dataset.screen || screen;

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    const addSpinner = () => {
      spinner?.classList.add('is-active');
    };
    const removeSpinner = () => {
      spinner?.classList.remove('is-active');
    };

    addSpinner();

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_disconnect',
        post_ID,
        kleinanzeigen_id,
        task_type,
        screen: _screen,
      },
      beforeSend: () => {
        $(el).html('Verknüpfung lösen...');
      },
      success: (response) => {
        $(el).html('Fertig');

        removeSpinner();

        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: (response, status, message) => {
        $(el).parents('td').removeClass('busy');
        $(el).html('Fehler');

        el.dispatchEvent(new CustomEvent('kleinanzeigen:data-import'), {
          detail: { success: false, message },
        });

        removeSpinner();
        console.log(message);
      },
    });
  }

  function createPost(e) {
    importData(e);
  }

  function importData(e) {
    e.preventDefault();

    let formdata;
    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const target = e.target;
      const post_ID = target.dataset.postId || '';
      const kleinanzeigen_id = target.dataset.kleinanzeigenId || '';
      formdata = { post_ID, kleinanzeigen_id };
    }

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');

    const addSpinner = () => {
      spinner?.classList.add('is-active');
    };
    const removeSpinner = () => {
      spinner?.classList.remove('is-active');
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_get_remote',
        formdata,
        screen,
      },
      beforeSend: () => {
        $(el).parents('td').addClass('busy');
        $(el).html('Hole Daten...');
      },
      success: (response) => {
        const json = JSON.parse(response);
        if (json.content.response?.code === 200) {
          $(el).html('Verarbeite...');
          setTimeout(() => {
            processDataImport(json, el, removeSpinner);
          }, 500);
        } else {
          $(el).parents('td').removeClass('busy');
          $(el).html('Fehler');
          removeSpinner();
        }
      },
      error: (response, status, message) => {
        $(el).parents('td').removeClass('busy');
        $(el).html('Fehler');

        el.dispatchEvent(new CustomEvent('kleinanzeigen:data-import'), {
          detail: { success: false, message },
        });

        removeSpinner();

        console.log(response.responseText);
      },
    });
  }

  function importImages(e) {
    e.preventDefault();

    if (form) {
      formdata = $(form).serializeJSON();
    } else {
      const el = e.target;
      const post_ID = el.dataset.postId || '';
      const kleinanzeigen_id = el.dataset.kleinanzeigenId || '';
      formdata = { post_ID, kleinanzeigen_id };
    }

    if (!formdata.kleinanzeigen_id) {
      alert(MSG_MISSING_KLEINANZEIGEN_ID);
      return;
    }

    const el = e.target;
    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');

    const addSpinner = () => {
      spinner?.classList.add('is-active');
    };
    const removeSpinner = () => {
      spinner?.classList.remove('is-active');
    };

    start();
    addSpinner();

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_get_remote',
        formdata,
        screen,
      },
      beforeSend: () => {
        $(el).parents('td').addClass('busy');
        $(el).html('Hole Fotos...');
      },
      success: (response) => {
        const json = JSON.parse(response);
        if (json.content.response?.code === 200) {
          $(el).html('Verarbeite...');

          setTimeout(() => {
            processImageImport(json, el, removeSpinner);
          }, 500);
        } else {
          $(el).parents('td').removeClass('busy');
          $(el).html('Fehler');
          removeSpinner();
        }
      },
      error: (response, status, message) => {
        spinner?.classList.remove('is-active');
        console.log(message);
      },
    });
  }

  function deleteImages(e) {
    e.preventDefault();
    start();

    const el = e.target;
    const _screen = el.dataset.screen || screen;

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

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    spinner?.classList.add('is-active');

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_delete_images',
        screen: _screen,
        post_ID,
      },
      success: (response) => parseResponse(response, el),
      error: (response, status, message) => {
        el.innerHTML = 'Fehler';
        $(el).parents('td').removeClass('busy');
        spinner?.classList.remove('is-active');
      },
    });
  }

  function publishPost(e) {
    e.preventDefault();
    e.stopPropagation();
    start();

    const el = e.target;
    const post_ID = el.dataset.postId;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const task_type = el.dataset.taskType;
    const _screen = el.dataset.screen || screen;

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    spinner?.classList.add('is-active');

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_toggle_publish_post',
        post_ID,
        kleinanzeigen_id,
        task_type,
        screen: _screen,
      },
      beforeSend: () => {
        $(el).parents('td').addClass('busy');
        $(el).html('Einen Moment...');
      },
      success: (response) => {
        $(el).html('Fertig');

        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: defaultErrorHandler,
    });
  }

  function featurePost(e) {
    e.preventDefault();
    e.stopPropagation();

    const el = e.target.closest('a');
    const post_ID = el.dataset.postId;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const task_type = el.dataset.taskType;
    const _screen = el.dataset.screen || screen;

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_feature_post',
        post_ID,
        kleinanzeigen_id,
        task_type,
        screen: _screen,
      },
      success: (response) => {
        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: defaultErrorHandler,
    });
  }

  function savePost(e, action = '') {
    e.preventDefault();
    e.stopPropagation();
    start();

    const el = e.target;
    const post_ID = el.dataset.postId;
    const args = action || el.dataset.args;
    const task_type = el.dataset.taskType;
    const _screen = el.dataset.screen || screen;

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    spinner?.classList.add('is-active');

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_save_post',
        post_ID,
        args,
        task_type,
        screen: _screen,
      },
      beforeSend: () => {
        $(el).parents('td').addClass('busy');
        $(el).html('Einen Moment...');
      },
      success: (response) => {
        $(el).html('Fertig');

        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: defaultErrorHandler,
    });
  }

  function fixPrice(e) {
    e.preventDefault();
    e.stopPropagation();
    start();

    const el = e.target;
    const post_ID = el.dataset.postId;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const task_type = el.dataset.taskType;
    const price = el.dataset.price;
    const _screen = el.dataset.screen || screen;

    const spinner = el.closest('[id*=-action]')?.querySelector('.spinner');
    spinner?.classList.add('is-active');

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_fix_price',
        post_ID,
        kleinanzeigen_id,
        price,
        task_type,
        screen: _screen,
      },
      beforeSend: () => {
        $(el).parents('td').addClass('busy');
        $(el).html('Einen Moment...');
      },
      success: (response) => {
        $(el).html('Fertig');

        setTimeout(() => {
          parseResponse(response, el);
        }, 500);
      },
      error: defaultErrorHandler,
    });
  }

  function deletePost(e) {
    const el = e.target;
    const kleinanzeigen_id = el.dataset.kleinanzeigenId;
    const post_ID = el.dataset.postId;

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_delete_post',
        post_ID,
        kleinanzeigen_id,
      },
      success: (response) => parseResponse(response, el),
      error: defaultErrorHandler,
    });
  }

  function parseResponse(response, el, callback) {
    const {
      data: { row, modal_row, head, post_ID, kleinanzeigen_id },
    } = JSON.parse(response);

    const _screen = el.dataset.screen || screen;
    let rowEl;
    let modalRowEl;
    switch (_screen) {
      case 'product':
        location = `${edit_link}${post_ID}`;
        break;

      case 'edit-product':
        rowEl = el.closest(`#post-${post_ID}`);
        $(rowEl)?.replaceWith(row);
        break;

      case 'toplevel_page_kleinanzeigen':
      case 'modal':
        if (head) {
          $('#kleinanzeigen-head-wrap .summary-content').html(head);
          document.dispatchEvent(new CustomEvent('init:head'));
        }

        let newEl, data;
        if (row) {
          rowEl = $(`.wp-list-kleinanzeigen tr#ad-id-${kleinanzeigen_id}`);
          $(rowEl)?.replaceWith(row);
          newEl = $(`.wp-list-kleinanzeigen tr#ad-id-${kleinanzeigen_id}`);
          data = { row: newEl, id: kleinanzeigen_id };
        }

        if (modal_row) {
          modalRowEl = $(`.wp-list-kleinanzeigen-tasks tr#post-id-${post_ID}`);
          $(modalRowEl)?.replaceWith(modal_row);
          newEl = $(`.wp-list-kleinanzeigen-tasks tr#post-id-${post_ID}`);
          data = { row: newEl, id: post_ID };
        }

        if (data) {
          document.dispatchEvent(
            new CustomEvent('data:parsed', {
              detail: data,
            })
          );
        }

        if ('create' === el.dataset.action) {
          rowEl = document.querySelector(`tr#ad-id-${kleinanzeigen_id}`);

          setTimeout(() => {
            $(rowEl).trigger(
              new CustomEvent('data:parsed', {
                detail: { action: el.dataset.action },
              })
            );
          }, 200);
        }
    }
    callback?.();
    finish();
  }

  const statusReport = async (e, nonce, temp_text = 'loading...') => {
    const el = e.target;
    const text = el.innerHTML;

    return $.ajax({
      url: admin_ajax,
      data: {
        action: '_ajax_status_mail',
        _ajax_nonce: nonce,
      },
      beforeSend: () => {
        $(el).html(temp_text);
      },
      success: (response) => {
        $(el).html(text);
        return response;
      },
      error: (response, status, message) => {
        return {
          data: {
            success: false,
            message,
          },
        };
      },
    });
  };

  const poll = (nonce, args = '') =>
    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_poll',
        _ajax_nonce: nonce,
        _poll_action: args,
      },
      success: (response) => response,
      error: defaultErrorHandler,
    });

  const cron = (nonce) =>
    $.ajax({
      url: admin_ajax,
      data: {
        action: '_ajax_cron',
        _ajax_nonce: nonce,
      },
      success: (response) => response,
      error: (response, status, message) => {
        return {
          data: {
            success: false,
            message,
          },
        };
      },
    });

  const ping = (post_ID = '', nonce) =>
    $.ajax({
      url: admin_ajax,
      data: {
        action: '_ajax_ping',
        _ajax_nonce: nonce,
        post_ID
      },
      success: (response) => response,
      error: (response, status, message) => {
        return {
         success: false,
         message,
        };
      },
    });

  const getNonce = (action) =>
    $.ajax({
      url: admin_ajax,
      data: {
        action: '_ajax_get_nonce',
        _ajax_action_name: action,
      },
      success: (response) => response,
      error: defaultErrorHandler,
    });

  const defaultErrorHandler = (response, status, message) =>
    console.error(message);

  const MSG_MISSING_KLEINANZEIGEN_ID = 'Keine Kleinanzeigen ID gefunden.';
  const MSG_MISSING_POST_ID = 'Keine Post ID gefunden.';
  const MSG_ERROR =
    'Arrgh🥶, etwas scheint schiefgegangen zu sein. Bitte noch einmal versuchen.';

  let form;
  switch (screen) {
    case 'kleinanzeigen_page_kleinanzeigen-settings':
    case 'toplevel_page_kleinanzeigen':
    case 'edit-product':
      KleinanzeigenAjax = {
        ...KleinanzeigenAjax,
        createPost,
        deletePost,
        publishPost,
        savePost,
        featurePost,
        importData,
        connect,
        disconnect,
        importImages,
        deleteImages,
        processDataImport,
        statusReport,
        poll,
        cron,
        ping,
        getNonce,
      };
      break;
    case 'product':
      form = document.getElementById('post');

      const getAdButton = document.getElementById('get-kleinanzeigen-ad');
      const importDataButton = document.getElementById(
        'import-kleinanzeigen-data'
      );
      const importImagesButton = document.getElementById(
        'import-kleinanzeigen-images'
      );
      const delImagesButton = document.getElementById('del-images');

      importDataButton?.addEventListener('click', importData);
      importImagesButton?.addEventListener('click', importImages);
      getAdButton?.addEventListener('click', getAd);
      delImagesButton?.addEventListener('click', deleteImages);

      getAdButton?.removeAttribute('disabled');
      importDataButton?.removeAttribute('disabled');
      importImagesButton?.removeAttribute('disabled');
      delImagesButton?.removeAttribute('disabled');
      break;
  }

  function ajax_ad_callback(data, callback) {
    const response = JSON.parse(data);

    const wrapper = document.getElementById('kleinanzeigen-ad-wrapper');
    if (wrapper) {
      const iframe =
        wrapper.querySelector('iframe') || document.createElement('iframe');
      iframe.src = '';
      wrapper.innerHTML = '';
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
      wrapper.setAttribute('style', 'height:400px;');
      iframe.setAttribute('style', 'height:100%; width:100%;');
      callback?.();
    }
  }

  function processDataImport(data, el, callback = () => {}) {
    const {
      post_ID,
      kleinanzeigen_id,
      post_status,
      content: description,
      screen,
      record,
    } = data;

    let doc, content;
    try {
      const parser = new DOMParser();
      doc = parser.parseFromString(description.body, 'text/html');
      content = doc.getElementById('viewad-description-text')?.outerHTML;
    } catch (err) {
      content = null;
    }

    // const raw_title = doc.getElementById("viewad-title")?.innerText;
    // const raw_price = doc.getElementById("viewad-price")?.innerText;

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_import_kleinanzeigen_data',
        post_ID,
        kleinanzeigen_id,
        post_status,
        content,
        record,
        screen,
      },
      success: (response) => {
        el.dispatchEvent(
          new CustomEvent('kleinanzeigen:data-import', {
            detail: { success: true, response },
          })
        );
        $(el).html('Fertig');
        setTimeout(() => parseResponse(response, el, callback), 2000);
      },
      error: (response, status, message) => {
        el.dispatchEvent(
          new CustomEvent('kleinanzeigen:data-import', {
            detail: { success: false, message },
          })
        );
        callback?.();
      },
    });
  }

  function processImageImport(json, el, callback) {
    const { post_ID, kleinanzeigen_id, post_status, content, screen } = json;

    let doc;
    try {
      const parser = new DOMParser();
      doc = parser.parseFromString(content.body, 'text/html');
    } catch (err) {
      console.error(err);
      return;
    }

    let images = [];
    doc.documentElement
      .querySelectorAll('#viewad-product .galleryimage-large img[data-imgsrc]')
      .forEach((image) => {
        images.push(image.dataset.imgsrc);
      });

    const message = (count, errors) => {
      if (!images.length) {
        msg = `Es konnten keine Fotos gefunden werden`;
      } else {
        if (!errors) {
          msg = `${count} Foto${1 === count ? '' : 's'} wurde${
            1 === count ? '' : 'n'
          } importiert`;
        } else {
          msg = `${errors} von ${images.length} Foto${
            1 === count ? '' : 's'
          } konnten nicht importiert werden!`;
        }
      }
      alert(msg);
    };

    $.post({
      url: admin_ajax,
      data: {
        action: '_ajax_import_kleinanzeigen_images',
        post_ID,
        kleinanzeigen_id,
        post_status,
        images,
        screen,
      },
      success: (response) => {
        $(el).html('Fertig');
        const json = JSON.parse(response);
        const { count, errors } = json.data;
        message(count, errors);
        setTimeout(() => parseResponse(response, el, callback), 2000);
      },
      error: defaultErrorHandler,
    });
  }
});
