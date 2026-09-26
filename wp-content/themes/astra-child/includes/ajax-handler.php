<?php

function astra_child_get_login_form() {
	$user   = array();
	$action = ! empty( $_POST['type'] ) ? $_POST['type'] : 'login';
	$url    = home_url( '/wp-login.php' ) . "?action={$action}&doing_login_ajax=true";

	$response = wp_remote_get( $url, array( 'sslverify' => false ) );

	if ( 'logout' === $action && ! empty( get_current_user() ) ) {
		wp_destroy_current_session();
		wp_clear_auth_cookie();
		wp_set_current_user( 0 );
		$success = true;
	}

	if ( ! is_wp_error( $response ) ) {
		$success  = true;
		$response = $response['body'];
	} else {
		$success = false;
	}

	wp_send_json( compact( 'success', 'user', 'action', 'response' ) );
}

function astra_child_submit_login_form() {
	$action = ! empty( $_POST['formaction'] ) ? sanitize_key( $_POST['formaction'] ) : 'login';
	if ( ! isset( $_POST['formdata'] ) || ! is_array( $_POST['formdata'] ) ) {
		wp_send_json_error( null, 400 );
	}
	$formdata = $_POST['formdata'];

	$response = null;
	$errors   = array();
	$success  = false;

	switch ( $action ) {
		case 'login':
			/**
			 * Authenticate in this request only. Never forward the credentials to
			 * wp-login.php via a loopback request: that runs a second wp_signon()
			 * (a second failed attempt, originating from the server's own IP) and
			 * re-sends the plaintext password with SSL verification disabled.
			 *
			 * The password is left slashed on purpose, matching wp_signon()'s own
			 * handling of $_POST['pwd'].
			 */
			$credentials = array(
				'user_login'    => isset( $formdata['log'] ) ? wp_unslash( $formdata['log'] ) : '',
				'user_password' => isset( $formdata['pwd'] ) ? $formdata['pwd'] : '',
				'remember'      => ! empty( $formdata['rememberme'] ),
			);
			// wp_signon() already sets the auth cookie (honouring "remember me").
			$user = wp_signon( $credentials, is_ssl() );

			if ( is_wp_error( $user ) ) {
				$errors = $user->get_error_messages();
			} else {
				$success = true;
				wp_set_current_user( $user->ID );
			}
			break;
		case 'register':
		case 'lostpassword':
			$result = wp_remote_post(
				home_url( "/wp-login.php?action={$action}&doing_login_ajax=true" ),
				array(
					'body'      => wp_unslash( $formdata ),
					'sslverify' => false,
				)
			);

			if ( is_wp_error( $result ) ) {
				$errors = $result->get_error_messages();
			} else {
				$success  = true;
				$response = wp_remote_retrieve_body( $result );
			}
			break;
	}

	wp_send_json( compact( 'success', 'errors', 'response', 'action' ) );
}
