<?php
/**
 * Site hardening.
 *
 * - XML-RPC is switched off entirely. Its login methods would let bots guess
 *   passwords around the Turnstile/reCAPTCHA login check (Settings → Spam
 *   Protection), and system.multicall lets them try hundreds per request.
 *   Webmentions use the REST API (inc/webmention.php) and keep working;
 *   incoming pingbacks, which arrive over XML-RPC, stop.
 * - Usernames aren't published: no REST user list for visitors, no author
 *   archives or ?author= redirects, no author links in oEmbed data, no users
 *   sitemap, and neither the login page nor the lost-password form says
 *   whether a username or email address exists.
 *   Posts still show the author's display name.
 */

defined( 'ABSPATH' ) || exit;


// ── XML-RPC: OFF ──────────────────────────────────────────────
add_filter( 'xmlrpc_enabled', '__return_false' );       // Login-based methods refuse to run.
add_filter( 'xmlrpc_methods', '__return_empty_array' ); // No methods at all (incl. via system.multicall).
remove_action( 'wp_head', 'rsd_link' );                 // Don't advertise the endpoint.

/**
 * Answer any request to xmlrpc.php with 403 before it's processed.
 */
function rt_block_xmlrpc_requests() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'XML-RPC is disabled on this site.';
		exit;
	}
}
add_action( 'init', 'rt_block_xmlrpc_requests', 0 );

// Pingbacks can't be received any more, so don't advertise an endpoint for them.
function rt_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'rt_remove_pingback_header' );


// ── USERNAMES STAY PRIVATE ────────────────────────────────────
/**
 * No REST user routes for logged-out visitors. Logged-in editors keep them
 * (the block editor uses them for the author picker).
 */
function rt_hide_rest_users( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'rt_hide_rest_users' );

// oEmbed data would otherwise include the author's name and /author/<username>/ link.
function rt_oembed_without_author( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
}
add_filter( 'oembed_response_data', 'rt_oembed_without_author' );

// No users sitemap (it lists /author/<username>/ URLs).
function rt_no_users_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'rt_no_users_sitemap', 10, 2 );

/**
 * Same message whether the username or the password was wrong, so the login
 * form can't be used to test which usernames exist. Other errors (empty
 * fields, the spam check) are left as they are.
 */
function rt_generic_login_errors( $errors ) {
	if ( ! is_wp_error( $errors ) ) {
		return $errors;
	}
	$revealing = array( 'invalid_username', 'invalid_email', 'incorrect_password' );
	if ( ! array_intersect( $revealing, $errors->get_error_codes() ) ) {
		return $errors;
	}
	foreach ( $revealing as $code ) {
		$errors->remove( $code );
	}
	$errors->add( 'rt_login_failed', __( '<strong>Error:</strong> The username, email address, or password is incorrect.', 'russteicheira' ) );
	return $errors;
}
add_filter( 'wp_login_errors', 'rt_generic_login_errors' );

/**
 * The "Lost your password?" form shouldn't reveal accounts either. Core says
 * "There is no account with that username or email address" for an unknown
 * one but sends a real one to "Check your email", and it never runs the
 * login-errors filter above on that screen. So an unknown account gets the
 * same "Check your email" page, with no email sent. Other errors (empty
 * field, too many requests, the spam check) still show, without the "no
 * account" line. Only on wp-login.php: plugins that call retrieve_password()
 * themselves get the error back as usual.
 */
function rt_generic_lost_password( $errors, $user_data ) {
	if ( $user_data || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
		return $errors;
	}
	$errors->remove( 'invalid_email' ); // Core adds it for an unknown email before this runs.
	if ( $errors->has_errors() ) {
		return $errors;
	}
	// Same destination core uses after a real reset request.
	$redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : 'wp-login.php?checkemail=confirm'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- core's lost-password form has no nonce; wp_safe_redirect() validates it.
	wp_safe_redirect( $redirect_to );
	exit;
}
add_filter( 'lostpassword_errors', 'rt_generic_lost_password', 99, 2 );

/**
 * Author archives (/author/<username>/, ?author=1) return a real 404. It's a
 * single-author blog and the theme never links to them. Runs before
 * WordPress's canonical redirect (priority 10), which would otherwise turn
 * ?author=1 into a redirect that reveals the username.
 */
function rt_no_author_archives() {
	if ( is_author() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'rt_no_author_archives', 1 );

// Belt and braces: never let the canonical redirect turn ?author=1 into
// /author/<username>/. Checks the query vars, since set_404() above clears is_author().
function rt_no_author_canonical( $redirect_url ) {
	return get_query_var( 'author' ) || get_query_var( 'author_name' ) ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'rt_no_author_canonical' );
