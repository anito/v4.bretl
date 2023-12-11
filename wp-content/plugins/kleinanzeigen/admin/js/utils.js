jQuery(document).ready(function ($) {
  const { screen, poll, cron, display } = {
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
        poll({
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

  const displayTime = (ms) => {
    return new Intl.DateTimeFormat("de-DE", {
      minute: "2-digit",
      second: "2-digit",
    }).format(ms);
  };

  const getCronJobs = () =>
    cron().then((res) => res);

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
      job.intervalId = setInterval(this.callback, remaining + 0, job); // add some delay to give cronjob time to execute before next check
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
      CreateScheduledJobs.instance || (CreateScheduledJobs.instance = new CreateScheduledJobs());
  }

  function focus_after_edit_post() {
    display();
    window.removeEventListener("focus", focus_after_edit_post);
  }

  function handle_visibility_change(e) {
    if (!e.target.hidden) {
      display();
    }
  }

  switch (screen) {
    case "kleinanzeigen_page_kleinanzeigen-settings":
    case "toplevel_page_kleinanzeigen":
      KleinanzeigenUtils = {
        ...KleinanzeigenUtils,
        getCookie,
        setCookie,
        getCronJobs,
        displayTime,
        CreateScheduledJobs,
        createOrientation,
        focus_after_edit_post,
        handle_visibility_change,
      };
      break;
  }
});
