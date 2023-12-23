<div class="login hidden" id="wbp-login" style="display: none;">
  <div class="login-bg"></div>
  <div class="login-form">
    <div class="login-close"></div>
    <div class="login-body"></div>
  </div>
</div>

<script>
  jQuery(document).ready(function($) {

    // Login
    const loginEl = $("#wbp-login");
    const wbp_loginLinks = [
      ".ast-header-account-link",
      ".wp-login-log-in",
      ".login a",
      "#login-message a",
      "#login_error a",
    ];
    const wbp_logoutLinks = [
      // ".customer-logout a", // Loggedin menu item
    ];
    const wbp_registerLinks = [
      ".wp-login-register",
      ".register a",
    ];
    const wbp_forgotLinks = [
      ".wp-login-lost-password",
      ".lost-password",
      ".lost-password a",
      ".jet-login-lost-password-link",
      "#login_error a",
    ];
    const wbp_backtoblogLinks = [
      "#backtoblog a",
      ".login-close",
    ];

    // Prevents flicker
    loginEl.addClass("block");

    function wbp_init() {
      wbp_initLogin();
      wbp_initLogout();
      wbp_initForgot();
      wbp_initRegister();
      wbp_initBacktoblog();
      wbp_initSubmitForgot();
      wbp_initSubmitRegister();
    }

    const wbp_get_login_form_login = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront?.wbp_get_login_form_login()
      wbp_initSubmitLogin();
      wbp_initSubmitForgot();
      wbp_initForgot();
      wbp_initRegister();
      wbp_initBacktoblog();
    }
    const wbp_get_login_form_logout = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront?.wbp_get_login_form_logout()
      wbp_initSubmitLogin();
      wbp_initSubmitForgot();
      wbp_initForgot();
      wbp_initRegister();
      wbp_initBacktoblog();
    }
    const wbp_get_login_form_register = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront?.wbp_get_login_form_register();
      wbp_initLogin();
      wbp_initForgot();
      wbp_initSubmitRegister();
      wbp_initBacktoblog();
    }
    const wbp_get_login_form_forgot = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront?.wbp_get_login_form_forgot();
      wbp_initLogin();
      wbp_initRegister();
      wbp_initBacktoblog();
      wbp_initSubmitForgot();
    }
    const wbp_submit_login_form = async function(e) {
      e.preventDefault();

      const res = await KleinanzeigenAjaxFront.wbp_submit_login_form();
      const {
        success
      } = JSON.parse(res);

      if (success) {
        KleinanzeigenAjaxFront.wbp_hide();
          -
          1 !== location.pathname.indexOf("/login") ?
          (window.location = "/") :
          window.location.reload();
      } else {
        wbp_init();
      }
    }
    const wbp_submit_register_form = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront.wbp_submit_register_form();
      wbp_init();
    }
    const wbp_submit_forgot_form = async function(e) {
      e.preventDefault();

      await KleinanzeigenAjaxFront.wbp_submit_forgot_form();
      wbp_init();
    }
    const wbp_hide = function(e) {
      e.preventDefault();

      KleinanzeigenAjaxFront.wbp_hide();
      wbp_initLogin('off');
      wbp_initLogout('off');
      wbp_initRegister('off');
      wbp_initForgot('off');
      wbp_initBacktoblog('off');
      wbp_initSubmitLogin('off');
      wbp_initSubmitRegister('off');
      wbp_initSubmitForgot('off');

      // Start over
      wbp_init();
    }

    function wbp_initLogin(off) {
      $(wbp_loginLinks).each((i, link) => {
        $(link)[off || 'on']("click", wbp_get_login_form_login);
      });
    }

    function wbp_initLogout(off) {
      $(wbp_logoutLinks).each((i, link) => {
        $(link)[off || 'on']("click", wbp_get_login_form_logout);
      });
    }

    function wbp_initRegister(off) {
      $(wbp_registerLinks).each((i, link) => {
        $(link)[off || 'on']("click", wbp_get_login_form_register);
      });
    }

    function wbp_initForgot(off) {
      $(wbp_forgotLinks).each((i, link) => {
        $(link)[off || 'on']("click", wbp_get_login_form_forgot);
      });
    }

    function wbp_initBacktoblog(off) {
      $(wbp_backtoblogLinks).each((i, link) => {
        $(link)[off || 'on']("click", wbp_hide);
      });
    }

    function wbp_initSubmitLogin(off) {
      $("#loginform")[off || 'on']("submit", wbp_submit_login_form);
    }

    function wbp_initSubmitRegister(off) {
      $("#registerform")[off || 'on']("submit", wbp_submit_register_form);
    }

    function wbp_initSubmitForgot(off) {
      $("#lostpasswordform")[off || 'on']("submit", wbp_submit_forgot_form);
    }

    wbp_init();
  })
</script>
<style>
  #wbp-login.block {
    display: block !important;
  }

  #wbp-login .login-form {
    visibility: visible;
    opacity: 1;
    transition: opacity .8s ease-in-out .3s;
  }

  #wbp-login.hidden .login-bg,
  #wbp-login.hidden .login-form {
    opacity: 0;
    transition: opacity 0.3s;
    transition-delay: 0;
    z-index: -1000000;
  }

  #wbp-login .login-bg {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background: #000;
    opacity: .7;
    z-index: 1000010;
  }

  #wbp-login .login-form {
    position: fixed;
    left: 50%;
    overflow: hidden;
    top: 10%;
    bottom: 20px;
    max-height: 835px;
    width: 490px;
    margin: 0 0 0 -245px;
    padding: 30px 0 30px;
    background-color: #f0f0f1;
    z-index: 1000011;
    box-shadow: 0 3px 6px rgba(0, 0, 0, .3);
  }

  #wbp-login .login-close {
    position: absolute;
    top: 5px;
    right: 5px;
    height: 22px;
    width: 22px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
  }

  #wbp-login .login-close::before {
    content: "\f158";
    font: normal 20px/22px dashicons;
    speak: never;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale
  }

  #wbp-login .login-body,
  #wbp-login iframe {
    height: 790px;
    width: 100%;
  }

  #wbp-login .button-link {
    top: 5px;
    right: 5px;
    height: 22px;
    width: 22px;
    color: #787c82;
    text-decoration: none;
    text-align: center;
  }

  #wbp-login .login-form.loading::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background-color: rgb(255 255 255 / 65%);
    z-index: 1;
  }

  #wbp-login .login-form.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100px;
    height: 100px;
    margin-left: -50px;
    margin-top: -50px;
    z-index: 2;
  }
</style>