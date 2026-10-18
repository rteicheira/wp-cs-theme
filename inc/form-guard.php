<?php
/**
 * Anti-spam guard for every form the public can submit.
 *
 * Defenses, cheapest first:
 *  1. Honeypot field: hidden from people; bots fill it in.
 *  2. Signed timestamp: rejects forged, missing, or too-fast submissions.
 *     Unlike a nonce it never expires, so forms keep working on cached pages.
 *  3. Rate limit per visitor (IP), per form.
 *  4. Turnstile / reCAPTCHA when switched on (inc/spam-protection.php).
 *  5. Link check: messages stuffed with links are flagged, never dropped.
 *
 * Forms covered: the contact form (functions.php → rt_handle_contact), blog
 * comments, password-protected posts, password reset, and log in
 * (inc/spam-protection.php). Registration is switched off: this site has no
 * member accounts. Webmentions have their own limits (inc/webmention.php).
 *
 * With Wordfence active, the log-in side (page passwords, password reset,
 * and the log-in captcha) is left to Wordfence; the contact-form and comment
 * guards always stay on. See rt_defer_to_wordfence().
 *
 * ADDING A NEW PUBLIC FORM — every new form must use this:
 *  1. Inside the <form>, echo rt_form_guard_fields( 'my-form' ), and
 *     rt_captcha_field_html( 'contact' ) just before the submit button.
 *  2. In the handler, first call rt_form_guard_check( 'my-form' ) and stop
 *     unless its status is 'ok'.
 *  3. Once the submission is accepted, call rt_form_guard_record( 'my-form' ).
 *  4. Treat the 'unverified' and 'links' flags as "deliver, but mark as
 *     possibly spam" — a real person's message should never be lost.
 */

defined( 'ABSPATH' ) || exit;

const RT_FORM_MIN_SECONDS = 3;                 // Faster than this is a bot.
const RT_RATE_LIMIT       = 5;                 // Submissions per visitor...
const RT_RATE_WINDOW      = 600;               // ...per 10 minutes.
const RT_HONEYPOT_FIELD   = 'rt_extra_field';
const RT_MAX_LINKS        = 2;                 // More links than this: flagged as possible spam.


// ── SIGNED TIMESTAMP ──────────────────────────────────────────
/**
 * "<unix time>.<hmac>" — printed into each form when it's rendered.
 */
function rt_form_token() {
	$time = (string) time();
	return $time . '.' . hash_hmac( 'sha256', $time, wp_salt( 'nonce' ) );
}

/**
 * Seconds since a form token was rendered, or false if it's missing/forged.
 */
function rt_form_token_age( $token ) {
	$parts = explode( '.', (string) $token, 2 );
	if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
		return false;
	}
	if ( ! hash_equals( hash_hmac( 'sha256', $parts[0], wp_salt( 'nonce' ) ), $parts[1] ) ) {
		return false;
	}
	return time() - (int) $parts[0];
}


// ── RATE LIMIT ────────────────────────────────────────────────
function rt_rate_limit_key( $form_id ) {
	return 'rt_rl_' . md5( $form_id . '|' . rt_get_visitor_ip() . wp_salt( 'nonce' ) );
}

/**
 * Whether this visitor has used up the form's allowance.
 */
function rt_rate_limited( $form_id, $limit = RT_RATE_LIMIT ) {
	return (int) get_transient( rt_rate_limit_key( $form_id ) ) >= $limit;
}

/**
 * Count one submission (or attempt) against the visitor's allowance.
 */
function rt_form_guard_record( $form_id, $window = RT_RATE_WINDOW ) {
	$key = rt_rate_limit_key( $form_id );
	set_transient( $key, (int) get_transient( $key ) + 1, $window );
}


// ── FORM MARKUP ───────────────────────────────────────────────
/**
 * Hidden timestamp + honeypot, printed inside the form.
 *
 * @param string $form_id Unique per form on the page (used for the field id).
 */
function rt_form_guard_fields( $form_id ) {
	$id = sanitize_html_class( $form_id ) . '-rt-extra';
	return '<input type="hidden" name="rt_ts" value="' . esc_attr( rt_form_token() ) . '">'
		// Spam trap: hidden from people; deliberately not named like anything browsers autofill.
		. '<div class="rt-hp" aria-hidden="true">'
		. '<label for="' . esc_attr( $id ) . '">Leave this field empty</label>'
		. '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( RT_HONEYPOT_FIELD ) . '" value="" tabindex="-1" autocomplete="new-password">'
		. '</div>';
}


// ── CHECK ─────────────────────────────────────────────────────
/**
 * Number of links in a piece of text (URLs, www. addresses, BBCode/HTML links).
 */
function rt_count_links( $text ) {
	return (int) preg_match_all( '~(?:https?://|www\.|\[url|<a\s)~i', (string) $text );
}

/**
 * Run every check for a submitted form.
 *
 * @param string $form_id Form name, also the rate-limit bucket.
 * @param array  $args {
 *     @type string|false $captcha     Provider action to verify ('contact', 'comment', 'login'), or false.
 *     @type bool         $strict      Refuse a missing captcha token instead of flagging it (log-in style).
 *     @type int          $limit       Submissions allowed per window.
 *     @type int          $min_seconds Minimum time on the page.
 *     @type string       $text        Message text to scan for links.
 * }
 * @return array {
 *     @type string $status 'ok', 'honeypot', 'expired', 'too_fast', 'busy', or 'spam'.
 *     @type array  $flags  'unverified' (captcha couldn't run) and/or 'links' (link-stuffed text).
 * }
 */
function rt_form_guard_check( $form_id, $args = array() ) {
	$args   = wp_parse_args(
		$args,
		array(
			'captcha'     => 'contact',
			'strict'      => false,
			'limit'       => RT_RATE_LIMIT,
			'min_seconds' => RT_FORM_MIN_SECONDS,
			'text'        => '',
		)
	);
	$result = array(
		'status' => 'ok',
		'flags'  => array(),
	);

	// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- public forms; see file header. Values are only compared.
	$honeypot = isset( $_POST[ RT_HONEYPOT_FIELD ] ) ? (string) wp_unslash( $_POST[ RT_HONEYPOT_FIELD ] ) : '';
	$token    = isset( $_POST['rt_ts'] ) && is_string( $_POST['rt_ts'] ) ? wp_unslash( $_POST['rt_ts'] ) : '';
	// phpcs:enable

	if ( '' !== $honeypot ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'rt form guard: honeypot filled on ' . $form_id . ', submission discarded.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
		$result['status'] = 'honeypot';
		return $result;
	}

	$age = rt_form_token_age( $token );
	if ( false === $age ) {
		$result['status'] = 'expired';
		return $result;
	}
	if ( $age < $args['min_seconds'] ) {
		$result['status'] = 'too_fast';
		return $result;
	}

	if ( rt_rate_limited( $form_id, $args['limit'] ) ) {
		$result['status'] = 'busy';
		return $result;
	}

	if ( $args['captcha'] && rt_captcha_protects( $args['captcha'] ) ) {
		$captcha_token = rt_captcha_posted_token();
		if ( $args['strict'] && '' === $captcha_token ) {
			$result['status'] = 'spam';
			return $result;
		}
		$verdict = rt_captcha_verify( $captcha_token, $args['captcha'] );
		if ( 'fail' === $verdict ) {
			$result['status'] = 'spam';
			return $result;
		}
		if ( 'unverified' === $verdict ) {
			$result['flags'][] = 'unverified';
		}
	}

	if ( '' !== $args['text'] && rt_count_links( $args['text'] ) > (int) apply_filters( 'rt_max_links', RT_MAX_LINKS ) ) {
		$result['flags'][] = 'links';
	}

	return $result;
}


// ── BLOG COMMENTS ─────────────────────────────────────────────
// Honeypot + timestamp + 5 comments per visitor per 10 minutes, checked
// before the Turnstile/reCAPTCHA check (which keeps holding unverified
// comments for moderation). Logged-in users, pingbacks, and webmentions
// are never checked here. Link-heavy comments are already held by
// WordPress (Settings → Discussion → "Hold a comment… if it contains 2 or more links").
const RT_COMMENT_LIMIT = 5;

add_filter(
	'comment_form_submit_field',
	function ( $submit_field ) {
		return is_user_logged_in() ? $submit_field : rt_form_guard_fields( 'comment' ) . $submit_field;
	},
	5
);

function rt_guard_comment( $commentdata ) {
	if ( ! did_action( 'pre_comment_on_post' ) || is_user_logged_in() ) {
		return $commentdata;
	}
	$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
	if ( '' !== $type && 'comment' !== $type ) {
		return $commentdata;
	}

	$check = rt_form_guard_check(
		'comment',
		array(
			'captcha' => false, // rt_captcha_check_comment() handles it, holding unverified comments.
			'limit'   => RT_COMMENT_LIMIT,
		)
	);
	if ( 'ok' === $check['status'] ) {
		rt_form_guard_record( 'comment' );
		return $commentdata;
	}

	$messages = array(
		'busy'    => __( 'You\'ve posted several comments in a short time. Please wait a few minutes, then try again.', 'russteicheira' ),
		'expired' => __( 'This page was open too long or didn\'t load fully. Please go back, refresh the page, and post your comment again.', 'russteicheira' ),
	);
	wp_die(
		esc_html( isset( $messages[ $check['status'] ] ) ? $messages[ $check['status'] ] : __( 'Your comment couldn\'t be verified as coming from a person. Please go back and try again.', 'russteicheira' ) ),
		esc_html__( 'Comment not posted', 'russteicheira' ),
		array(
			'response'  => 'busy' === $check['status'] ? 429 : 403,
			'back_link' => true,
		)
	);
}
add_filter( 'preprocess_comment', 'rt_guard_comment', 1 );


// ── TEXT-ONLY COMMENTS, NO WEBSITE URL ───────────────────────
// The comment form has no Website field (functions.php removes it), but
// WordPress's comment handler (wp-comments-post.php) still accepts a "url"
// value from anything that posts to it directly — which is how spam bots
// attach their links. And core lets comments keep some HTML (<a>, <b>,
// <blockquote>…) and turns typed web addresses into links. So:
//  - a logged-out submission that sends a "url" is a bot (people can't fill a
//    field the form doesn't have) and is refused;
//  - no comment ever stores a website URL, and none is shown as a link;
//  - comment text is plain text only: all HTML is stripped when saved and
//    again when shown (covers comments saved before this);
//  - typed web addresses stay plain text, and a logged-out comment that
//    contains one waits for moderation instead of being published.
// Webmentions (inc/webmention.php) are a separate comment type and keep
// their source/author links.

function rt_is_regular_comment( $comment_type ) {
	return '' === (string) $comment_type || 'comment' === $comment_type;
}

function rt_comment_no_url( $commentdata ) {
	$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
	if ( ! rt_is_regular_comment( $type ) ) {
		return $commentdata;
	}
	// phpcs:ignore WordPress.Security.NonceVerification -- only checks whether a field the form doesn't have was sent.
	$sent_url = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
	if ( '' !== $sent_url && did_action( 'pre_comment_on_post' ) && ! is_user_logged_in() ) {
		wp_die(
			esc_html__( 'Your comment couldn\'t be verified as coming from a person. Please go back and try again.', 'russteicheira' ),
			esc_html__( 'Comment not posted', 'russteicheira' ),
			array(
				'response'  => 403,
				'back_link' => true,
			)
		);
	}
	$commentdata['comment_author_url'] = '';
	return $commentdata;
}
add_filter( 'preprocess_comment', 'rt_comment_no_url', 0 );

/**
 * Plain text only: strip every tag (links, formatting, scripts, styles) but
 * keep the line breaks people typed.
 */
function rt_comment_plain_text( $content ) {
	return trim( wp_strip_all_tags( (string) $content, false ) );
}
add_filter( 'pre_comment_content', 'rt_comment_plain_text', 1 );

// Never show a website for a regular comment (rt_comment_no_url() above stops
// new ones being stored; this also hides URLs saved before that).
add_filter(
	'get_comment_author_url',
	function ( $url, $comment_id, $comment ) {
		return ( $comment instanceof WP_Comment && ! rt_is_regular_comment( $comment->comment_type ) ) ? $url : '';
	},
	99,
	3
);

// Shown as plain text too, so comments saved before this can't render HTML.
add_filter(
	'comment_text',
	function ( $text, $comment = null ) {
		if ( $comment instanceof WP_Comment && ! rt_is_regular_comment( $comment->comment_type ) ) {
			return $text;
		}
		return wp_strip_all_tags( $text, false );
	},
	1,
	2
);
// Typed web addresses stay plain text (core would turn them into links).
remove_filter( 'comment_text', 'make_clickable', 9 );

/**
 * Logged-out comments that mention a web address wait for moderation.
 */
function rt_comment_links_need_approval( $approved, $commentdata ) {
	if ( is_user_logged_in() || 'spam' === $approved || 'trash' === $approved || is_wp_error( $approved ) ) {
		return $approved;
	}
	$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
	if ( rt_is_regular_comment( $type ) && rt_count_links( isset( $commentdata['comment_content'] ) ? $commentdata['comment_content'] : '' ) > 0 ) {
		return 0;
	}
	return $approved;
}
add_filter( 'pre_comment_approved', 'rt_comment_links_need_approval', 20, 2 );


// ── PASSWORD-PROTECTED POSTS ──────────────────────────────────
// The post-password form posts to wp-login.php?action=postpass, which
// WordPress doesn't rate-limit, so it could be used to guess post passwords.
const RT_POSTPASS_LIMIT = 10; // Attempts per visitor per 10 minutes.

function rt_guard_password_form( $form ) {
	if ( rt_defer_to_wordfence() ) {
		return $form;
	}
	$form = preg_replace( '~(<form\b[^>]*>)~i', '$1' . rt_form_guard_fields( 'postpass' ), $form, 1 );
	// Same Turnstile/reCAPTCHA setting as the log-in page.
	return str_ireplace( '</form>', rt_captcha_field_html( 'login' ) . '</form>', $form );
}
add_filter( 'the_password_form', 'rt_guard_password_form', 20 );

function rt_guard_postpass() {
	if ( rt_defer_to_wordfence() ) {
		return;
	}
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared to a literal.
		return;
	}
	$check = rt_form_guard_check(
		'postpass',
		array(
			'captcha'     => 'login',
			'strict'      => true,
			'limit'       => RT_POSTPASS_LIMIT,
			'min_seconds' => 1,
		)
	);
	// Every attempt counts, right or wrong: this limit is about guessing.
	rt_form_guard_record( 'postpass' );

	if ( 'ok' === $check['status'] ) {
		return;
	}
	$messages = array(
		'busy'    => __( 'Too many password attempts. Please wait 10 minutes, then try again.', 'russteicheira' ),
		'expired' => __( 'This page was open too long or didn\'t load fully. Please go back, refresh the page, and try again.', 'russteicheira' ),
		'spam'    => __( 'Please complete the security check, then try again.', 'russteicheira' ),
	);
	$status   = isset( $messages[ $check['status'] ] ) ? $check['status'] : 'spam';
	wp_die(
		esc_html( $messages[ $status ] ),
		esc_html__( 'Please try again', 'russteicheira' ),
		array(
			'response'  => 'busy' === $status ? 429 : 403,
			'back_link' => true,
		)
	);
}
add_action( 'login_form_postpass', 'rt_guard_postpass', 1 );


// ── PASSWORD RESET ────────────────────────────────────────────
// Stops the "Lost your password?" form being used to flood an inbox with
// reset emails, with or without Turnstile/reCAPTCHA switched on.
const RT_RESET_LIMIT = 5; // Requests per visitor per hour.

function rt_guard_lost_password( $errors ) {
	if ( rt_defer_to_wordfence() ) {
		return;
	}
	if ( rt_rate_limited( 'lostpassword', RT_RESET_LIMIT ) ) {
		$errors->add( 'rt_reset_busy', __( '<strong>Error:</strong> Too many password reset requests. Please wait an hour, then try again.', 'russteicheira' ) );
		return;
	}
	rt_form_guard_record( 'lostpassword', HOUR_IN_SECONDS );
}
add_action( 'lostpassword_post', 'rt_guard_lost_password', 5 );


// ── NO SIGN-UPS ───────────────────────────────────────────────
// No member accounts on this site, so "Anyone can register" is always off:
// no spam accounts, and wp-login.php?action=register just goes back to log in.
add_filter( 'pre_option_users_can_register', '__return_zero' );
