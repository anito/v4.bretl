jQuery(document).ready(function ($) {
  const { screen, poll, display, displayModal } = {
    ...KleinanzeigenAjax,
    ...KleinanzeigenUtils,
  };

  const getCookie = (key) => {
    const regex = new RegExp(`(?<=${key}=)([\\w\\S-])+(?=;)`);
    const matches = document.cookie.match(regex);

    return matches?.length ? matches[0] : null;
  };

  const setCookie = (name, value, days) => {
    // const oneYearFromNow = new Date(new Date().setFullYear(new Date().getFullYear() + 1))

    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      var expires = '; expires=' + date.toGMTString();
    } else {
      var expires = '';
    }
    const cookie = name + '=' + value + expires + ';path=/wp-admin';
    document.cookie = cookie;
  };

  const createOrientation = (data, prefix = '') => {
    const { cookie_key, cookie_val } = data;
    const _prefix = prefix ? `${prefix}__` : '';
    const horizontal = `${_prefix}horizontal`;
    const vertical = `${_prefix}vertical`;
    const defaultOrientation = cookie_val;

    const settings = {
      set: (val, cb) => {
        const regex = new RegExp(_prefix);
        const cookie_val = settings.getValues()[val.replace(regex, '')];
        localStorage.setItem(cookie_key, cookie_val);

        setCookie(cookie_key, cookie_val, 365);
        poll();
        if (typeof cb === 'function') cb(cookie_val);
        return cookie_val;
      },
      get: () => {
        let orientation = localStorage.getItem(cookie_key);
        if (!orientation) {
          settings.set(defaultOrientation);
          orientation = defaultOrientation;
        }
        return orientation;
      },
      toggle: (cb) => {
        const curVal = settings.get();
        const newVal =
          horizontal === curVal
            ? settings.set(vertical)
            : settings.set(horizontal);
        if (typeof cb === 'function') cb(newVal);
        return newVal;
      },
      getValues: () => {
        return {
          horizontal,
          vertical,
        };
      },
      remove: () => localStorage.removeIte(cookie_key),
    };
    return settings;
  };

  const displayTime = (ms) => {
    return new Intl.DateTimeFormat('de-DE', {
      minute: '2-digit',
      second: '2-digit',
    }).format(ms);
  };

  class CreateScheduledJobs {
    static instance;
    scheduled = [];
    callback;

    get _scheduled() {
      return this.scheduled;
    }
    set _callback(cb) {
      return (this.callback = cb);
    }

    getIds = () => this.scheduled.map((job) => job.intervalId);
    add = (job, remaining) => {
      job.intervalId = setInterval(this.callback, remaining, job);
      this.scheduled.push(job);
      return this.scheduled;
    };
    remove = (id) => {
      clearInterval(id);

      const index = this.scheduled.findIndex((job) => job.intervalId === id);
      this.scheduled.splice(index, 1);
      return this.scheduled;
    };
    clear = () => {
      this.scheduled.forEach((job) => {
        job.intervalId && this.remove(job.intervalId);
      });
      return !this.scheduled.length;
    };

    static getInstance = () =>
      CreateScheduledJobs.instance ||
      (CreateScheduledJobs.instance = new CreateScheduledJobs());
  }

  function focus_after_edit_post() {
    display();
    window.removeEventListener('focus', focus_after_edit_post);
  }

  function handle_visibility_change(e) {
    if (!e.target.hidden) {
      display();
      displayModal();
    }
  }

  /**
   * IMAGE UPLOADER
   */
  function uploader_init(image_id, is_empty) {
    $(`.image-uploader-${image_id}`).each((id, item) => {
      console.log($(item));
      $('.upload_image_trigger', $(item)).click(function (e) {
        e.preventDefault();
        let image_uploader;

        //If the uploader object has already been created, reopen the dialog
        if (image_uploader) {
          image_uploader.open();
          return;
        }

        //Extend the wp.media object
        image_uploader = wp.media.frames.file_frame = wp.media({
          title: 'Upload Image',
          button: {
            text: 'Choose Image',
          },
          multiple: false,
        });

        //When a file is selected, grab the URL and set it as the text field's value
        image_uploader.on('select', function () {
          const image_id = $('img', $(item)).attr('id');

          attachment = image_uploader.state().get('selection').first().toJSON();
          console.log(attachment)
          const {id, url} = attachment;
          $('.image_post_id', $(item)).attr('value', id);
          $(document).trigger(`selected:image-${image_id}`, { id, url });
        });

        //Open the uploader dialog
        image_uploader.open();
      });

      const { placeholder } = TaxonomyUtils;

      const image = $(`#${image_id}`, $(item));
      const cancel_button = $(`img#${image_id} ~ .cancel-button`, $(item));

      const input = $('input[type=hidden]', $(item));
      if (image.length) {
        '1' !== is_empty && cancel_button.show();
        cancel_button.on('click', function (e) {
          image.attr('src', placeholder);
          input.attr('value', '');
          cancel_button.hide();
        });

        $(document).on(`selected:image-${image_id}`, function (e, data) {
          cancel_button.show();
          const {id, url} = data;
          image.attr('src', url);
        });
      }
    });
  }

  switch (screen) {
    case 'kleinanzeigen_page_kleinanzeigen-settings':
    case 'toplevel_page_kleinanzeigen':
      KleinanzeigenUtils = {
        ...KleinanzeigenUtils,
        getCookie,
        setCookie,
        displayTime,
        CreateScheduledJobs,
        createOrientation,
        focus_after_edit_post,
        handle_visibility_change,
      };
      break;
    case 'edit-product_brand':
    case 'edit-product_label':
      TaxonomyUtils = {
        uploader_init,
      };
  }
});
