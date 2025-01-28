jQuery(document).ready(function ($) {
  const { admin_ajax } = KleinanzeigenAjaxFront;

  const loginEl = $("#wbp-login");

  async function wbp_submit_login_form() {
    wbp_load();

    const formdata = $("#loginform").serializeJSON();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_submit_form",
        formdata,
        formaction: "login",
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_submit_register_form() {
    wbp_load();

    const formdata = $("#registerform").serializeJSON();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_submit_form",
        formdata,
        formaction: 'register'
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_submit_forgot_form() {
    wbp_load();

    const formdata = $("#lostpasswordform").serializeJSON();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_submit_form",
        formdata,
        formaction: 'lostpassword'
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_get_login_form_login() {
    wbp_load();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_get_login_form",
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_get_login_form_logout() {
    wbp_load();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_get_login_form",
        type: "logout",
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_get_login_form_register() {
    wbp_load();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_get_login_form",
        type: "register",
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  async function wbp_get_login_form_forgot() {
    wbp_load();

    return await $.post({
      url: admin_ajax,
      data: {
        action: "_ajax_get_login_form",
        type: "lostpassword",
      },
      success: wbp_parseResponse,
      error: (err) => console.log(err),
    });
  }

  function parseErrors(user, response) {
    const errors = [];
    Object.entries(user.errors).forEach((val, key) => {
      errors.push(`<p>${val[1][0]}</p>`);
    });

    const parser = new DOMParser();
    const doc = parser.parseFromString(response, "text/html");

    const errorEl = doc.getElementById("login_error");

    $(errorEl).html(errors.join());
    return doc.documentElement;
  }

  function wbp_parseResponse(data) {
    const json = JSON.parse(data);

    const { success, response, action, user } = json;

    let content = response;
    if ("errors" in user) {
      content = parseErrors(user, response);
    }

    switch (action) {
      case "login":
      case "submit":
      case "register":
      case "lostpassword":
        $(".login-body", loginEl).html(content);
        $(".login-form", loginEl).removeClass("loading");
        break;
      case "logout":
        window.location.reload();
    }
  }

  function wbp_hide() {
    loginEl.addClass("hidden");
    $(".login-body", loginEl).empty();
  }

  function wbp_load() {
    loginEl.removeClass("hidden");
    $(".login-form", loginEl).addClass("loading");
  }

  KleinanzeigenAjaxFront = {
    ...KleinanzeigenAjaxFront,
    wbp_submit_login_form,
    wbp_submit_forgot_form,
    wbp_submit_register_form,
    wbp_get_login_form_login,
    wbp_get_login_form_logout,
    wbp_get_login_form_register,
    wbp_get_login_form_forgot,
    wbp_hide,
  };
});
