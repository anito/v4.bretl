<div class="login hidden" id="astra-child-login" style="display: none;">
	<div class="login-bg"></div>
	<div class="login-form">
	<div class="login-close"></div>
	<div class="login-body"></div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {

	// Login
	const loginEl = $("#astra-child-login");
	const astra_child_loginLinks = [
		".ast-header-account-link",
		".wp-login-log-in",
		".login a",
		"#login-message a",
		"#login_error a",
	];
	const astra_child_logoutLinks = [
		// ".customer-logout a", // Loggedin menu item
	];
	const astra_child_registerLinks = [
		".wp-login-register",
		".register a",
	];
	const astra_child_forgotLinks = [
		".wp-login-lost-password",
		".lost-password",
		".lost-password a",
		".jet-login-lost-password-link",
		"#login_error a",
	];
	const astra_child_backtoblogLinks = [
		"#backtoblog a",
		".login-close",
	];

	// Prevents flicker
	loginEl.addClass("block");

	// WordPress adds `logged-in` to <body> for authenticated users.
	function astra_child_isLoggedIn() {
		return $("body").hasClass("logged-in");
	}

	function astra_child_init() {
		astra_child_initLogout();

		// Logged-in users keep the links' default behaviour, e.g. the header
		// account link (.ast-header-account-link) goes to the account page.
		if (astra_child_isLoggedIn()) {
			return;
		}

		astra_child_initLogin();
		astra_child_initForgot();
		astra_child_initRegister();
		astra_child_initBacktoblog();
		astra_child_initSubmitForgot();
		astra_child_initSubmitRegister();
	}

	const astra_child_hide = function(e) {
		e.preventDefault();

		KleinanzeigenAjaxFront.astra_child_login_hide();
		astra_child_initLogin('off');
		astra_child_initLogout('off');
		astra_child_initRegister('off');
		astra_child_initForgot('off');
		astra_child_initBacktoblog('off');
		astra_child_initSubmitLogin('off');
		astra_child_initSubmitRegister('off');
		astra_child_initSubmitForgot('off');

		// Start over
		astra_child_init();
	}

	function astra_child_initLogin(off) {
		$(astra_child_loginLinks).each((i, link) => {
			$(link)[off || 'on']("click", async function(e) {
				e.preventDefault();
				e.stopPropagation();

				await KleinanzeigenAjaxFront?.astra_child_get_login_form_login()
				astra_child_initSubmitLogin();
				astra_child_initSubmitForgot();
				astra_child_initForgot();
				astra_child_initRegister();
				astra_child_initBacktoblog();
			});
		});
	}

	function astra_child_initLogout(off) {
		$(astra_child_logoutLinks).each((i, link) => {
			$(link)[off || 'on']("click", async function(e) {
				e.preventDefault();

				await KleinanzeigenAjaxFront?.astra_child_get_login_form_logout()
				astra_child_initSubmitLogin();
				astra_child_initSubmitForgot();
				astra_child_initForgot();
				astra_child_initRegister();
				astra_child_initBacktoblog();
			});
		});
	}

	function astra_child_initRegister(off) {
		$(astra_child_registerLinks).each((i, link) => {
			$(link)[off || 'on']("click", async function(e) {
				e.preventDefault();

				await KleinanzeigenAjaxFront?.astra_child_get_login_form_register();
				astra_child_initLogin();
				astra_child_initForgot();
				astra_child_initSubmitRegister();
				astra_child_initBacktoblog();
			});
		});
	}

	function astra_child_initForgot(off) {
		$(astra_child_forgotLinks).each((i, link) => {
			$(link)[off || 'on']("click", async function(e) {
				e.preventDefault();

				await KleinanzeigenAjaxFront?.astra_child_get_login_form_forgot();
				astra_child_initLogin();
				astra_child_initRegister();
				astra_child_initBacktoblog();
				astra_child_initSubmitForgot();
			});
		});
	}

	function astra_child_initBacktoblog(off) {
		$(astra_child_backtoblogLinks).each((i, link) => {
			$(link)[off || 'on']("click", astra_child_hide);
		});
	}

	function astra_child_initSubmitLogin(off) {
		$("#loginform")[off || 'on']("submit", async function(e) {
			e.preventDefault();

			// wp_send_json() replies with application/json, so jQuery has already parsed it.
			let res;
			try {
				res = await KleinanzeigenAjaxFront?.astra_child_submit_login_form();
			} catch (err) {
				$(".login-form", loginEl).removeClass("loading");
				return;
			}

			// On failure the errors are rendered into the form in place, so the
			// existing handlers stay bound and nothing needs re-initialising.
			if (res?.success) {
				KleinanzeigenAjaxFront.astra_child_login_hide();
				1 !== location.pathname.indexOf("/login") ?
					(window.location = "/") :
					window.location.reload();
			}
		});
	}

	function astra_child_initSubmitRegister(off) {
		$("#registerform")[off || 'on']("submit", async function(e) {
			e.preventDefault();

			await KleinanzeigenAjaxFront.astra_child_submit_register_form();
			astra_child_init();
		});
	}

	function astra_child_initSubmitForgot(off) {
		$("#lostpasswordform")[off || 'on']("submit", async function(e) {
			e.preventDefault();

			await KleinanzeigenAjaxFront.astra_child_submit_forgot_form();
			astra_child_init();
		});
	}

	astra_child_init();
})
</script>
<style>
	#astra-child-login.block {
		display: block !important;
	}

	#astra-child-login .login-form {
		visibility: visible;
		opacity: 1;
		transition: opacity .8s ease-in-out .3s;
	}

	#astra-child-login.hidden .login-bg,
	#astra-child-login.hidden .login-form {
		opacity: 0;
		transition: opacity 0.3s;
		transition-delay: 0;
		z-index: -1000000;
	}

	#astra-child-login .login-bg {
		position: fixed;
		top: 0;
		bottom: 0;
		left: 0;
		right: 0;
		background: #000;
		opacity: .7;
		z-index: 1000010;
	}

	#astra-child-login .login-form {
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

	#astra-child-login .login-close {
		position: absolute;
		top: 5px;
		right: 5px;
		height: 22px;
		width: 22px;
		cursor: pointer;
		text-decoration: none;
		text-align: center;
	}

	#astra-child-login .login-close::before {
		content: "\f158";
		font: normal 20px/22px dashicons;
		speak: never;
		-webkit-font-smoothing: antialiased !important;
		-moz-osx-font-smoothing: grayscale
	}

	#astra-child-login .login-body,
	#astra-child-login iframe {
		height: 790px;
		width: 100%;
	}

	#astra-child-login .button-link {
		top: 5px;
		right: 5px;
		height: 22px;
		width: 22px;
		color: #787c82;
		text-decoration: none;
		text-align: center;
	}

	#astra-child-login .login-form.loading {
	color: var(--ast-global-color-5);
	}

	#astra-child-login .login-form.loading::before {
		content: '';
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background-color: rgb(255 255 255 / 65%);
		z-index: 1;
	}

	#astra-child-login .login-form.loading::after {
		content: '';
		position: absolute;
		top: 50%;
		left: 50%;
		width: 100px;
		height: 100px;
		margin-left: -50px;
		margin-top: -50px;
		z-index: 2;
		background-image: url("<?php echo get_stylesheet_directory_uri(); ?>/images/pulse-2.svg");
		background-size: 100px 100px;
	}
</style>