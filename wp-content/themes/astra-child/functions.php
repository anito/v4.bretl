<?php
defined( 'ABSPATH' ) || exit;
defined( 'WC_ABSPATH' ) || exit;

require_once __DIR__ . '/includes/sender-email.php';

if ( is_admin() ) {
	require_once __DIR__ . '/includes/duplicate-content.php';
	require_once __DIR__ . '/includes/custom-avatar.php';
}

function astra_child_define() {
	$theme = wp_get_theme();
	/**
	 * Define Constants
	 */
	define( 'CHILD_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
	define(
		'AJAX_FRONT_VARS',
		array(
			'admin_ajax' => admin_url( 'admin-ajax.php' ),
			'is_login'   => is_login(),
			'user'       => json_encode( astra_child_get_current_user() ),
			'home_url'   => home_url(),
			'nonce'      => wp_create_nonce( 'ajax-nonce-action' ),
		)
	);
	// IUBENDA
	define(
		'IUBENDA_VARS',
		array(
			'siteId'         => defined( 'IUBENDA_SITE_ID' ) ? IUBENDA_SITE_ID : '',
			'cookiePolicyId' => defined( 'IUBENDA_COOKIE_POLICY_ID' ) ? IUBENDA_COOKIE_POLICY_ID : '',
		)
	);
}

function astra_child_register_ajax() {
	require_once __DIR__ . '/includes/ajax-handler.php';

	// Ajax actions
	add_action( 'wp_ajax__ajax_get_login_form', 'astra_child_ajax_get_login_form' );
	add_action( 'wp_ajax__ajax_submit_login_form', 'astra_child_ajax_submit_login_form' );

	add_action( 'wp_ajax_nopriv__ajax_get_login_form', 'astra_child_ajax_get_login_form' );
	add_action( 'wp_ajax_nopriv__ajax_submit_login_form', 'astra_child_ajax_submit_login_form' );
}

function astra_child_hooks() {
	/**
	 * Change the breakpoint for Astra
	 *
	 * @return int Screen width when the header should change to the mobile header.
	 */
	add_filter(
		'astra_mobile_breakpoint',
		function () {
			return 544;
		}
	);
	add_filter(
		'astra_tablet_breakpoint',
		function () {
			return 921;
		}
	);
	add_filter( 'astra_theme_author', 'astra_child_return_theme_author' );

	add_filter( 'allowed_http_origins', 'astra_child_add_allowed_origins' );
	add_action( 'wp_enqueue_scripts', 'astra_child_add_scripts' );
	add_action( 'wp_enqueue_scripts', 'astra_child_detectTrident' );
	add_action( 'login_enqueue_scripts', 'astra_child_add_login_scripts' );

	add_filter(
		'logout_redirect',
		function ( $redirect_url ) {
			return esc_url( home_url() );
		}
	);

	add_filter(
		'logout_url',
		function ( $logout_url ) {
			return $logout_url . '&amp;redirect_to=' . get_permalink();
		},
		9999
	);

	add_action( 'wp_footer', 'astra_child_footer', 99 );
	add_filter( 'login_headerurl', 'astra_child_login_logo_url' );
	add_filter( 'login_form_defaults', 'astra_child_login_form_defaults' );
	add_filter( 'login_form_top', 'astra_child_login_form_top' );
	add_filter( 'login_form_middle', 'astra_child_login_form_middle' );
	add_filter( 'login_form_bottom', 'astra_child_login_form_bottom' );

	add_filter( 'jet-woo-builder/template-functions/product-thumbnail-placeholder', 'astra_child_get_wc_placeholder_image' );
	add_filter( 'elementor/utils/get_placeholder_image_src', 'custom_elementor_placeholder_image' );

	add_filter( 'woocommerce_default_catalog_orderby', 'astra_child_custom_default_orderby' );
	add_filter( 'woocommerce_product_tabs', 'astra_child_woo_custom_tabs' );
	add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

	add_filter( 'woocommerce_default_address_fields', 'astra_child_filter_default_address_fields', 20, 1 );
	add_filter( 'woocommerce_coupons_enabled', 'astra_child_wc_coupons_frontend_enabled' );
	// add_filter('woocommerce_short_description', 'astra_child_short_description', 10, 1);
	add_filter( 'woocommerce_format_price_range', 'astra_child_format_variation_price_range', 10, 3 );
	add_filter( 'woocommerce_cart_product_subtotal', 'astra_child_modify_cart_product_subtotal_label', 10, 4 );
}

function astra_child_init() {
	astra_child_define();
	astra_child_register_ajax();
	astra_child_hooks();
}

add_filter( 'init', 'astra_child_init' );

/**
 * @return mixed
 */
function astra_child_get_current_user() {
	$cur_user = wp_get_current_user();
	unset( $cur_user->user_pass );
	return $cur_user;
}

function astra_child_ajax_get_login_form() {
	astra_child_check_ajax_referer();

	astra_child_get_login_form();
}

function astra_child_ajax_submit_login_form() {
	astra_child_check_ajax_referer();

	astra_child_submit_login_form();
}

function astra_child_doing_login_ajax() {
	return isset( $_REQUEST['doing_login_ajax'] ) ? true : false;
}

/**
 * CSRF allowed domains
 */
function astra_child_add_allowed_origins( $origins ) {
	return array_merge(
		$origins,
		array(
			'http://localhost:5173',
			'https://dev.bretl.webpremiere.de',
			'https://dev.auto-traktor-bretschneider.de',
			'https://dev.auto-traktor-bretschneider.mbp',
			'https://dev.auto-traktor-bretschneider.test',
		)
	);
}

/**
 * App asset names (e.g. *.js. *.css files) changing per app distribution
 * This method takes care of it automatically using glob
 */
function astra_child_get_themes_file( $file_path ) {
	$regex = '/^([\w\-\/.]*)(\/wp-content\/themes[\w\-\.\/]+)/';
	return preg_replace( $regex, '\2', glob( $file_path )[0] );
}

/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */
function astra_child_add_scripts() {
	include_once __DIR__ . '/includes/admin-login-styles.php';

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	// Theme styles
	wp_enqueue_style( 'parent-style', get_parent_theme_file_uri( '/style.css' ), array(), ASTRA_THEME_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-theme', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), CHILD_THEME_VERSION, 'all' );

	wp_enqueue_script( 'ajax-front', get_stylesheet_directory_uri() . '/js/ajax-front.js', array( 'jquery' ), CHILD_THEME_VERSION, true );
	wp_enqueue_script( 'main', get_stylesheet_directory_uri() . '/js/main.js', array( 'jquery' ), CHILD_THEME_VERSION, true );

	wp_localize_script( 'ajax-front', 'KleinanzeigenAjaxFront', AJAX_FRONT_VARS );

	// Iubenda
	if ( defined( 'IUBENDA_VARS' ) && isset( IUBENDA_VARS['siteId'] ) && IUBENDA_VARS['siteId'] ) {
		wp_enqueue_script( 'iubenda', get_stylesheet_directory_uri() . '/js/iubenda.js', array(), CHILD_THEME_VERSION, true );
		wp_enqueue_script( 'iubenda-autoblocking', 'https://cs.iubenda.com/autoblocking/' . IUBENDA_VARS['siteId'] . '.js', array( 'iubenda' ), CHILD_THEME_VERSION, true );
		wp_enqueue_script( 'iubenda-cs', '//cdn.iubenda.com/cs/iubenda_cs.js', array( 'iubenda' ), CHILD_THEME_VERSION, true );
		wp_localize_script( 'iubenda', 'Iubenda', IUBENDA_VARS );
	}

	if ( astra_child_doing_login_ajax() ) {
		wp_dequeue_script( 'zxcvbn-async' );
		wp_dequeue_script( 'regenerator-runtime' );
		wp_dequeue_script( 'wp-polyfill-inert' );
		wp_dequeue_script( 'wp-polyfill' );
		wp_dequeue_script( 'wp-hooks' );
		wp_dequeue_script( 'user-profile' );
	}
}

function astra_child_add_login_scripts() {
	if ( ! astra_child_doing_login_ajax() ) {
		return;
	}

	wp_dequeue_style( 'login' );
	// The modal's host page already loads jQuery.
	wp_deregister_script( 'jquery' );

	// wp-login.php enqueues `user-profile` (which needs jQuery) after this hook,
	// so drop it right before the footer scripts print. js/login.js provides
	// the show-password toggle instead.
	add_action(
		'login_footer',
		function () {
			wp_dequeue_script( 'user-profile' );
		},
		19
	);

	wp_enqueue_script( 'login', get_stylesheet_directory_uri() . '/js/login.js', array(), CHILD_THEME_VERSION, true );
	wp_enqueue_style( 'dashicons', ABSPATH . WPINC . '/css/dashicons.min.css', array() );
	wp_enqueue_style( 'astra-child-login-base', get_stylesheet_directory_uri() . '/css/login.css', array(), CHILD_THEME_VERSION, 'all' );

	wp_enqueue_script( 'ajax-front', get_stylesheet_directory_uri() . '/js/ajax-front.js', array(), CHILD_THEME_VERSION, true );
	wp_enqueue_script( 'jquery-serializejson', get_stylesheet_directory_uri() . '/js/jquery.serializejson.js', array(), CHILD_THEME_VERSION, true );

	wp_localize_script( 'ajax-front', 'KleinanzeigenAjaxFront', AJAX_FRONT_VARS );
}

// Logo link url
function astra_child_login_logo_url() {
	return home_url();
}

function astra_child_footer() {
	get_template_part( 'templates/footer/login' );
}

function astra_child_login_form_defaults() {
	return array(
		'echo'           => true,
		// Default 'redirect' value takes the user back to the request URI.
		'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'form_id'        => 'astra-child-loginform',
		'label_username' => __( 'Username or Email Address', 'astra-child' ),
		'label_password' => __( 'Password', 'astra-child' ),
		'label_remember' => __( 'Remember Me', 'astra-child' ),
		'label_log_in'   => __( 'Log In', 'astra-child' ),
		'id_username'    => 'astra-child-user_login',
		'id_password'    => 'astra-child-user_pass',
		'id_remember'    => 'astra-child-rememberme',
		'id_submit'      => 'astra-child-submit',
		'remember'       => true,
		'value_username' => '',
		// Set 'value_remember' to true to default the "Remember me" checkbox to checked.
		'value_remember' => false,
	);
}

// Templates for `wp_login_form` function
/**
 * @param array $args
 */
function astra_child_login_form_top( $args ) {
	ob_start();
	get_template_part( 'templates/login/top', 'login', $args );
	return ob_get_clean();
}

/**
 * @param array $args
 */
function astra_child_login_form_middle( $args ) {
	ob_start();
	get_template_part( 'templates/login/middle', 'login', $args );
	return ob_get_clean();
}

/**
 * @param array $args
 */
function astra_child_login_form_bottom( $args ) {
	ob_start();
	get_template_part( 'templates/login/bottom', 'login', $args );
	return ob_get_clean();
}

/**
 * Default sort for shop and specific categories
 *
 * @param string $sortby
 */
function astra_child_custom_default_orderby( $sortby ) {

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'date';
	}

	global $wp_query;

	// categories sorting table
	$orderby = array(
		'slug'     => array(
			'aktionspreise' => 'date',
			'aktionswochen' => 'date',
			'empfehlungen'  => 'date',
			'aktionen'      => 'date',
		),
		'taxonomy' => array(
			'product_cat'   => 'date',
			'product_brand' => 'date',
			'pa_merkmale'   => 'date',
		),
	);

	$obj = (array) $wp_query->get_queried_object();

	foreach ( $orderby as $key => $val ) {
		$prop = $obj[ $key ];

		if ( array_key_exists( $prop, $val ) ) {
			$sortby = $val[ $prop ];
			break;
		}
	}

	return $sortby;
}

/**
 * Unsupprted Browsers IE 11 and lower
 */
function astra_child_detectTrident( $current_theme ) {

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( $ua = $_SERVER['HTTP_USER_AGENT'] ) ) {
		$browser = array(
			'name'     => '',
			'version'  => '',
			'platform' => '',
		);

		if ( preg_match( '/Trident\/([0-9.]*)/u', $ua, $match ) ) {
			$match = (int) array_pop( $match ) + 4;
		} elseif ( preg_match( '/MSIE\s{1}([0-9.]*)/u', $ua, $match ) ) {
			$match = (int) array_pop( $match );
		}

		if ( ! empty( $match ) && ( $match <= 11 ) ) {
			$browser['name']    = 'ie';
			$browser['version'] = $match;
			add_action( 'wp_footer', 'astra_child_unsupported_browsers_template', 100 );

			wp_register_script( 'browser_sniffer', get_stylesheet_directory_uri() . '/js/browser_support.js', array( 'jquery' ), CHILD_THEME_VERSION, true );
			wp_localize_script(
				'browser_sniffer',
				'__browser',
				array(
					'name'     => $browser['name'],
					'version'  => $browser['version'],
					'platform' => $browser['platform'],
				)
			);
			wp_enqueue_script( 'browser_sniffer' );
		}
	}
}

function astra_child_unsupported_browsers_template() {
	get_template_part(
		'templates/misc/unsupported',
		'browser',
		array(
			'blogname' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'email'    => get_bloginfo( 'admin_email' ),
		)
	);
}

function get_certificate() {
	$g = stream_context_create( array( 'ssl' => array( 'capture_peer_cert' => true ) ) );
	$r = stream_socket_client(
		'ssl://' . $_SERVER['SERVER_NAME'] . ':443',
		$errno,
		$errstr,
		30,
		STREAM_CLIENT_CONNECT,
		$g
	);

	// returns ressource|false
	if ( false === $r ) {
		return false;
	}

	$cont = stream_context_get_params( $r );
	return ( $cont['options']['ssl']['peer_certificate'] );
}

/**
 * Replace Elementors with Woos Placeholder Image (can be defined in woo product settings)
 */
function astra_child_get_wc_placeholder_image( $default_placeholder ) {
	return wc_placeholder_img_src( 'woocommerce_image' );
}

/**
 * Replace default Elementor image placeholdder
 */
function custom_elementor_placeholder_image() {
	return get_stylesheet_directory_uri() . '/images/placeholder.jpg';
}

/**
 * In order to improve SEO,
 * display the product title again in product description
 */
function astra_child_woo_custom_tabs( $tabs ) {
	global $product;

	$tabs['description'] = array(
		'title'    => __( 'Description', 'woocommerce' ),
		'priority' => 10,
		'callback' => 'astra_child_woo_tab_content',
	);

	$datasheets = get_post_meta( $product->get_id(), '_datasheet', true );
	if ( class_exists( 'DG_Gallery' ) && ! empty( $datasheets ) ) {
		$tabs['datasheets'] = array(
			'title'    => __( 'Datasheet', 'astra-child' ),
			'priority' => 20,
			'callback' => 'astra_child_woo_tab_datasheets',
		);
	}

	$tabs['quote_request_form'] = array(
		'title'    => __( 'Quote Request', 'astra-child' ),
		'priority' => 30,
		'callback' => 'astra_child_woo_tab_quote_request',
	);

	unset( $tabs['reviews'] );
	unset( $tabs['additional_information'] );

	return $tabs;
}

/**
 * @param $tab_name
 * @param $tab
 */
function astra_child_woo_tab_content( $tab_name, $tab ) {
	global $product;

	$title   = $product->get_title();
	$content = wpautop( $product->get_description() );                                                                                                                     // prevent to strip out all \n !!!
	echo '<h6 style="font-weight:600; opacity: 0.5; margin-bottom: 10px;">Highlights</h6><h5 style="margin-bottom: 30px;">' . $title . '</h5>' . do_shortcode( $content ); // keep possible shortcode
}

function astra_child_woo_tab_quote_request() {

	if ( defined( 'REQUEST_FORM_SHORTCODE_ID' ) ) {
		echo do_shortcode( '[elementor-template id="' . REQUEST_FORM_SHORTCODE_ID . '"]' );
	} else {
		echo '<p>Um den Inhalt dieses Tabs anzuzeigen, muss eine Shortcode ID in der wp-config.php hinterlegt werden.</p>';
	}
}

function astra_child_woo_tab_technical() {
	echo '<p>Der Tab <strong>' . __( 'Technical Details', 'astra-child' ) . '</strong> kann auf Wunsch implementiert werden.</p>';
}

function astra_child_woo_tab_datasheets() {
	global $product;

	$meta = get_post_meta( $product->id );
	$dg   = $meta['_datasheet'][0];

	echo do_shortcode( $dg );
}

	// add_filter('woocommerce_cart_hide_zero_taxes', '__return_false');

	// add_filter('woocommerce_order_hide_zero_taxes', '__return_false');

/**
 * Quote Plugin
 */
function astra_child_get_price_visibility( $show ) {
	return array(
		'show'  => ( isset( $show['to_admin'] ) ? $show['to_admin'] : false ) || ( isset( $show['to_customer'] ) ? $show['to_customer'] : false ),
		'class' => ( isset( $show['to_customer'] ) && ! $show['to_customer'] ) ? 'price-hidden' : '',
	);
}

add_filter(
	'astra_child_show_prices',
	function () {
		if ( ! defined( 'SHOW_CUSTOMER_EMAIL_PRICE' ) ) {
			return false;
		}

		return SHOW_CUSTOMER_EMAIL_PRICE;
	}
);

/**
 * Change required billing & shipping address fields
 */
function astra_child_filter_default_address_fields( $address_fields ) {

	// Only on checkout page
	if ( function_exists( 'is_checkout' ) && ! is_checkout() ) {
		return $address_fields;
	}

	// list all non required field
	$key_fields = array( 'country', 'first_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode' );

	foreach ( $key_fields as $key_field ) {
		$address_fields[ $key_field ]['required'] = false;
	}

	return $address_fields;
}

/**
 * @param $is_enabled
 */
function astra_child_wc_coupons_frontend_enabled( $is_enabled ) {

	if ( ! is_admin() && function_exists( 'astra_get_option' ) ) {
		return astra_get_option( 'checkout-coupon-display' );
	}

	return true;
}

/**
 * @param $author
 * @return mixed
 */
function astra_child_return_theme_author( $author ) {
	$author = array(
		'theme_name'       => __( 'Axel Nitzschner', 'astra-child' ),
		'theme_author_url' => 'https://webpremiere.de/',
	);
	return $author;
}

/**
 * Use Kleinanzeigen's short description method `sanitize_excerpt`.
 * By disabling this filter we just use (the default excerpt) the ad records `description` property.
 *
 * @param string  @excerpt
 *
 * @return string
 */
function astra_child_short_description( $excerpt ) {

	if ( class_exists( '\Klassifieds\Utils\Utils' ) ) {
		return \Klassifieds\Utils\Utils::sanitize_excerpt( $excerpt, 150 );
	}

	return $excerpt;
}

/**
 * Change Variable Price Range Html
 */
function astra_child_format_variation_price_range( $price, $from, $to ) {
	return '<small>' . _x( 'from ', 'Price range: from', 'astra-child' ) . '</small>' . wc_price( $from );
}

/**
 * @param string      $product_subtotal
 * @param \WC_Product $product
 * @param int         $quantity
 * @param \WC_Cart    $cart
 * @return mixed
 */
function astra_child_modify_cart_product_subtotal_label( $product_subtotal, $product, $quantity, $cart ) {
	$tax_class = $product->get_tax_class();
	$rates     = WC_Tax::get_rates_for_tax_class( $tax_class );
	foreach ( $rates as $rate ) {
		$label = $rate->tax_rate_name;
		if ( str_contains( strtolower( $tax_class ), 'differenzbesteuert' ) ) {
			$product_subtotal .= ' <small class="tax-label">' . $label . '</small>';
		}
	}

	return $product_subtotal;
}

/**
 * Nonce check for the login modal endpoints. No capability check: these
 * endpoints exist for logged-out visitors.
 */
function astra_child_check_ajax_referer() {
	add_filter( 'wp_die_ajax_handler', 'astra_child_ajax_die_handler_name' );

	return check_ajax_referer( 'ajax-nonce-action', 'nonce' );
}

function astra_child_ajax_die_handler_name() {
	return 'astra_child_ajax_die_handler';
}

function astra_child_ajax_die_handler( $message = '', $title = '', $args = array() ) {
	if ( '' === $message || null === $message ) {
		die;
	}

	// Keep the error status (e.g. 403 from check_ajax_referer) so the JS error path runs.
	$status = is_array( $args ) && ! empty( $args['response'] ) ? (int) $args['response'] : 500;

	wp_send_json(
		array(
			'success' => false,
			'message' => __( 'An error occurred while processing your request.', 'astra-child' ),
		),
		$status
	);
}