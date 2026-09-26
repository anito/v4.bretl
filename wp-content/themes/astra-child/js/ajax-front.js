jQuery(document).ready(function ($) {
	const { admin_ajax, nonce } = KleinanzeigenAjaxFront;

	const loginEl = $("#astra-child-login");

	async function astra_child_submit_login_form() {
		astra_child_login_show();

		const formdata = $("#loginform").serializeJSON();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_submit_login_form",
				nonce,
				formdata,
				formaction: "login",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_submit_register_form() {
		astra_child_login_show();

		const formdata = $("#registerform").serializeJSON();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_submit_login_form",
				nonce,
				formdata,
				formaction: "register",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_submit_forgot_form() {
		astra_child_login_show();

		const formdata = $("#lostpasswordform").serializeJSON();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_submit_login_form",
				nonce,
				formdata,
				formaction: "lostpassword",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_get_login_form_login() {
		astra_child_login_show();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_get_login_form",
				nonce,
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_get_login_form_logout() {
		astra_child_login_show();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_get_login_form",
				nonce,
				type: "logout",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_get_login_form_register() {
		astra_child_login_show();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_get_login_form",
				nonce,
				type: "register",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	async function astra_child_get_login_form_forgot() {
		astra_child_login_show();

		return await $.post({
			url: admin_ajax,
			data: {
				action: "_ajax_get_login_form",
				nonce,
				type: "lostpassword",
			},
			success: astra_child_parseResponse,
			error: astra_child_onError,
		});
	}

	// Renders errors into the form that is already shown, like wp-login.php does.
	function showErrors(errors) {
		const body = $(".login-body", loginEl);
		let errorEl = $("#login_error", body);
		if (!errorEl.length) {
			errorEl = $('<div id="login_error" class="notice notice-error"></div>');
			$("form", body).first().before(errorEl);
		}
		errorEl.html(errors.map((error) => `<p>${error}</p>`).join(""));
	}

	function astra_child_parseResponse(data) {
		let json;
		if('string' === typeof data ) {
			try{
				json = JSON.parse(data);
			} catch(e) {
				console.log(e);
				return;
			}
		} else {
			json = data;
		}

		const { response, action, errors } = json;

		switch (action) {
			case "login":
			case "submit":
			case "register":
			case "lostpassword":
				if (errors && errors.length) {
					showErrors(errors);
				} else if (response) {
					$(".login-body", loginEl).html(response);
				}
				$(".login-form", loginEl).removeClass("loading");
				break;
			case "logout":
				window.location.reload();
		}
	}

	function astra_child_login_hide() {
		loginEl.addClass("hidden");
		$(".login-body", loginEl).empty();
	}

	// Stop the spinner when a request is rejected (e.g. an expired nonce).
	function astra_child_onError(err) {
		console.log(err);
		$(".login-form", loginEl).removeClass("loading");
	}

	function astra_child_login_show() {
		loginEl.removeClass("hidden");
		$(".login-form", loginEl).addClass("loading");
	}

	KleinanzeigenAjaxFront = {
		...KleinanzeigenAjaxFront,
		astra_child_submit_login_form,
		astra_child_submit_forgot_form,
		astra_child_submit_register_form,
		astra_child_get_login_form_login,
		astra_child_get_login_form_logout,
		astra_child_get_login_form_register,
		astra_child_get_login_form_forgot,
		astra_child_login_hide,
	};
});
