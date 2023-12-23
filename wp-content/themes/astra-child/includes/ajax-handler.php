<?php

function wbp_get_login_form()
{

  $user = array();
  $action = !empty($_POST['type']) ? $_POST['type'] : 'login';
  $url = home_url('/wp-login.php') . "?action={$action}&doing_login_ajax=true";

  $response = wp_remote_get($url, array('sslverify' => false));

  if ('logout' === $action && !empty(get_current_user())) {
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    wp_set_current_user(0);
    $success = true;
  }

  if (!is_wp_error($response)) {
    $success = true;
    $response = $response['body'];
  } else {
    $success = false;
  }


  die(json_encode(compact('success', 'user', 'action', 'response')));
}

function wbp_submit_form()
{

  $action = !empty($_POST['formaction']) ? $_POST['formaction'] : 'login';
  if (!isset($_REQUEST['formdata'])) die();
  $formdata = $_REQUEST['formdata'];

  $post_vars = array_combine(array_keys($formdata), $formdata);

  $user = array();

  $post = function() use($action, $post_vars) {
    $response = wp_remote_post(home_url("/wp-login.php?action={$action}&doing_login_ajax=true"), array('body' => $post_vars, 'sslverify' => false));
    if(is_wp_error($response)) {
      return $response;
    } else {
      return $response['body'];
    }
  };

  switch ($action) {
    case 'login':
      $credentials = array(
        'user_login'    => $formdata['log'],
        'user_password' => $formdata['pwd'],
        'remember'      => $formdata['rememberme'],
        'remember'      => $formdata['wp-submit'],
        'testcookie'    => $formdata['testcookie'],
      );
      $user = wp_signon($credentials, false);

      if (is_wp_error($user)) {
        $success = false;

        /**
         * Template:
         * The following will return 'Cookies are blocked or not supported by your browser'
         * Needed as template where this error will be replaced by our own ones
         */
        $response = $post();
      } else {
        $success = true;
        $response = null;

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
      }
      break;
    case 'register':
    case 'lostpassword':
      $response = $post();

      if (is_wp_error($response)) {
        $success = false;
      } else {
        $success = true;
      }
      break;
  }

  die(json_encode(compact('success', 'user', 'response', 'action')));
}