jQuery(document).ready(function ($) {
  const { screen, heartbeat } = {
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
      var expires = "; expires=" + date.toGMTString();
    } else {
      var expires = "";
    }
    const cookie = name + "=" + value + expires + ";path=/wp-admin";
    document.cookie = cookie;
  };

  const createOrientation = (data, prefix = "") => {
    const { cookie_key, cookie_val } = data;
    const _prefix = prefix ? `${prefix}__` : "";
    const horizontal = `${_prefix}horizontal`;
    const vertical = `${_prefix}vertical`;
    const defaultOrientation = cookie_val;

    const settings = {
      set: (val, cb) => {
        const regex = new RegExp(_prefix);
        const cookie_val = settings.getValues()[val.replace(regex, "")];
        localStorage.setItem(cookie_key, cookie_val);

        setCookie(cookie_key, cookie_val, 365);
        heartbeat({
          cookie_key,
          cookie_val,
        });
        if (typeof cb === "function") cb(cookie_val);
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
        if (typeof cb === "function") cb(newVal);
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

  function focus_after_edit_post() {
    KleinanzeigenAjax.display();
    window.removeEventListener('focus', focus_after_edit_post)
  }

  switch (screen) {
    case "toplevel_page_kleinanzeigen":
      KleinanzeigenUtils = {
        ...KleinanzeigenUtils,
        getCookie,
        setCookie,
        createOrientation,
        focus_after_edit_post,
      };
      break;
  }
});
