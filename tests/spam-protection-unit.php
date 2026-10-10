<?php
/**
 * Unit tests for inc/spam-protection.php (Turnstile / reCAPTCHA).
 * No WordPress required — WP functions are stubbed below.
 *
 * Run:  php tests/spam-protection-unit.php
 *       php tests/spam-protection-unit.php --live   (also calls the real
 *       siteverify endpoints with Cloudflare's / Google's public test keys)
 */

// ── WP STUBS ─────────────────────────────────────────────────────────────────
define( 'ABSPATH', __DIR__ . '/' );
define( 'RT_DIR', dirname( __DIR__ ) );
define( 'RT_URI', 'https://russteicheira.net/wp-content/themes/russteicheira' );

$GLOBALS['opt']        = array();   // the rt_spam_protection option
$GLOBALS['http']       = null;      // canned wp_remote_post response, or 'live'
$GLOBALS['http_calls'] = array();
$GLOBALS['filters']    = array();
$GLOBALS['actions']    = array();
$GLOBALS['logged_in']  = false;
$GLOBALS['host']       = 'russteicheira.net';

class WP_Error {
	private $m;
	public function __construct( $c = '', $m = '' ) { $this->m = $m; }
	public function get_error_message() { return $this->m; }
}
class WPDie extends Exception {}

function get_option( $k, $d = false )   { return 'rt_spam_protection' === $k ? $GLOBALS['opt'] : $d; }
function wp_parse_args( $a, $d )        { return array_merge( $d, $a ); }
function sanitize_text_field( $s )      { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s )               { return $s; }
function home_url( $p = '' )            { return 'https://' . $GLOBALS['host'] . $p; }
function wp_parse_url( $u, $c = -1 )    { return parse_url( $u, $c ); }
function esc_attr( $s )                 { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html( $s )                 { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html__( $s )               { return $s; }
function esc_attr__( $s )               { return $s; }
function __( $s )                       { return $s; }
function is_user_logged_in()            { return $GLOBALS['logged_in']; }
function did_action( $a )               { return in_array( $a, $GLOBALS['actions'], true ) ? 1 : 0; }
function add_action()                   {}
function add_filter( $h, $cb )          { $GLOBALS['filters'][ $h ][] = $cb; }
function apply_filters( $h, $v )        { return $v; }
function wp_enqueue_script()            {}
function wp_localize_script()           {}
function error_log_stub()               {}
function wp_die( $m )                   { throw new WPDie( $m ); }
function rt_get_visitor_ip()            { return '203.0.113.7'; }

function is_wp_error( $t )                    { return $t instanceof WP_Error; }
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r )          { return $r['body']; }
function wp_remote_post( $url, $args ) {
	$GLOBALS['http_calls'][] = array( 'url' => $url, 'body' => $args['body'] );
	if ( 'live' !== $GLOBALS['http'] ) {
		return $GLOBALS['http'];
	}
	$ctx  = stream_context_create( array( 'http' => array(
		'method'        => 'POST',
		'header'        => 'Content-Type: application/x-www-form-urlencoded',
		'content'       => http_build_query( $args['body'] ),
		'timeout'       => $args['timeout'],
		'ignore_errors' => true,
	) ) );
	$body = @file_get_contents( $url, false, $ctx ); // phpcs:ignore
	$code = isset( $http_response_header[0] ) && preg_match( '#\s(\d{3})\s#', $http_response_header[0], $m ) ? (int) $m[1] : 0;
	return false === $body ? new WP_Error( 'http', 'curl failed' ) : array( 'code' => $code, 'body' => $body );
}

// Silence the module's error_log() calls during tests.
ini_set( 'error_log', '/dev/null' );

// ── LOAD ─────────────────────────────────────────────────────────────────────
require __DIR__ . '/../inc/spam-protection.php';

// ── TEST HARNESS ─────────────────────────────────────────────────────────────
$pass = 0; $fail = 0; $errors = array();

function ok( $description, $condition ) {
	global $pass, $fail, $errors;
	if ( $condition ) {
		echo "  \033[32m✓\033[0m  $description\n";
		$pass++;
	} else {
		echo "  \033[31m✗\033[0m  $description\n";
		$errors[] = $description;
		$fail++;
	}
}

function json_ok( $data ) { return array( 'code' => 200, 'body' => json_encode( $data ) ); }

$TS = array( 'enabled' => true, 'provider' => 'turnstile', 'ts_site_key' => 'site-ts', 'ts_secret_key' => 'secret-ts' );
$V3 = array( 'enabled' => true, 'provider' => 'recaptcha', 'rc_version' => 'v3', 'rc_site_key' => 'site-rc', 'rc_secret_key' => 'secret-rc', 'rc_threshold' => 0.5 );
$V2 = array_merge( $V3, array( 'rc_version' => 'v2' ) );

// ── rt_captcha_sanitize ──────────────────────────────────────────────────────
echo "\nrt_captcha_sanitize\n";
$GLOBALS['opt'] = array( 'ts_secret_key' => 'old-ts', 'rc_secret_key' => 'old-rc' );
$out = rt_captcha_sanitize( array( 'provider' => 'evil', 'rc_version' => 'v9', 'rc_threshold' => '5' ) );
ok( 'unknown provider falls back to turnstile',      'turnstile' === $out['provider'] );
ok( 'unknown reCAPTCHA version falls back to v3',    'v3' === $out['rc_version'] );
ok( 'threshold clamped to 0.9',                      0.9 === $out['rc_threshold'] );
ok( 'unchecked boxes save as false',                 false === $out['enabled'] && false === $out['protect_contact'] && false === $out['protect_comments'] );
ok( 'blank secret keeps saved Turnstile secret',     'old-ts' === $out['ts_secret_key'] );
ok( 'blank secret keeps saved reCAPTCHA secret',     'old-rc' === $out['rc_secret_key'] );
$out = rt_captcha_sanitize( array( 'ts_clear_secret' => '1', 'rc_secret_key' => ' new-rc ', 'rc_threshold' => '0.04', 'provider' => 'recaptcha' ) );
ok( '"remove" box clears only that secret',          '' === $out['ts_secret_key'] && 'new-rc' === $out['rc_secret_key'] );
ok( 'threshold clamped to 0.1',                      0.1 === $out['rc_threshold'] );
ok( 'recaptcha provider accepted',                   'recaptcha' === $out['provider'] );
ok( 'non-array input handled',                       is_array( rt_captcha_sanitize( 'junk' ) ) );

// ── rt_captcha_active / rt_captcha_protects ──────────────────────────────────
echo "\nrt_captcha_active / rt_captcha_protects\n";
$GLOBALS['opt'] = array();
ok( 'off by default',                                ! rt_captcha_active() );
$GLOBALS['opt'] = array_merge( $TS, array( 'enabled' => false ) );
ok( 'off when not enabled even with keys',           ! rt_captcha_active() );
$GLOBALS['opt'] = array_merge( $TS, array( 'ts_secret_key' => '' ) );
ok( 'off when the chosen provider lacks a secret',   ! rt_captcha_active() );
$GLOBALS['opt'] = array_merge( $V3, array( 'ts_site_key' => 'x', 'ts_secret_key' => 'y', 'rc_site_key' => '' ) );
ok( "other provider's keys don't count",             ! rt_captcha_active() );
$GLOBALS['opt'] = $TS;
ok( 'on with Turnstile keys',                        rt_captcha_active() );
ok( 'protects contact and comments by default',      rt_captcha_protects( 'contact' ) && rt_captcha_protects( 'comment' ) );
$GLOBALS['opt'] = array_merge( $TS, array( 'protect_comments' => false ) );
ok( 'comments toggle respected',                     rt_captcha_protects( 'contact' ) && ! rt_captcha_protects( 'comment' ) );

// ── rt_captcha_field_html / rt_captcha_csp_sources ───────────────────────────
echo "\nrt_captcha_field_html / rt_captcha_csp_sources\n";
$GLOBALS['opt'] = array();
ok( 'no markup when off',                            '' === rt_captcha_field_html( 'contact' ) );
ok( 'no CSP additions when off',                     array() === rt_captcha_csp_sources()['script'] );
$GLOBALS['opt'] = array_merge( $TS, array( 'ts_site_key' => 'k"><script>' ) );
$html = rt_captcha_field_html( 'contact' );
ok( 'turnstile mode + action',                       false !== strpos( $html, 'data-mode="turnstile"' ) && false !== strpos( $html, 'data-action="contact"' ) );
ok( 'site key escaped',                              false === strpos( $html, '<script>' ) );
ok( 'token field present',                           false !== strpos( $html, 'name="rt_captcha_token"' ) );
ok( 'contact form is not native-submit',             false === strpos( $html, 'data-native-submit' ) );
ok( 'no Google attribution for Turnstile',           false === strpos( $html, 'reCAPTCHA' ) );
ok( 'comment form is native-submit',                 false !== strpos( rt_captcha_field_html( 'comment' ), 'data-native-submit="1"' ) );
ok( 'Turnstile CSP origin',                          array( 'https://challenges.cloudflare.com' ) === rt_captcha_csp_sources()['frame'] );
$GLOBALS['opt'] = $V3;
$html = rt_captcha_field_html( 'contact' );
ok( 'reCAPTCHA v3 mode + Google attribution',        false !== strpos( $html, 'data-mode="recaptcha-v3"' ) && false !== strpos( $html, 'policies.google.com/terms' ) );
ok( 'reCAPTCHA CSP origins',                         in_array( 'https://www.gstatic.com', rt_captcha_csp_sources()['script'], true ) );
$GLOBALS['opt'] = $V2;
ok( 'reCAPTCHA v2 has no v3 attribution',            false === strpos( rt_captcha_field_html( 'contact' ), 'policies.google.com' ) );

// ── rt_captcha_posted_token ──────────────────────────────────────────────────
echo "\nrt_captcha_posted_token\n";
$_POST = array();
ok( 'missing token is empty',                        '' === rt_captcha_posted_token() );
$_POST = array( 'rt_captcha_token' => str_repeat( 'a', 5000 ) );
ok( 'oversized token discarded',                     '' === rt_captcha_posted_token() );
$_POST = array( 'rt_captcha_token' => 'abc.def' );
ok( 'normal token returned',                         'abc.def' === rt_captcha_posted_token() );

// ── rt_captcha_verify ────────────────────────────────────────────────────────
echo "\nrt_captcha_verify\n";
$GLOBALS['opt'] = array();
ok( 'pass when off (no HTTP call)',                  'pass' === rt_captcha_verify( 'x', 'contact' ) && ! $GLOBALS['http_calls'] );

$GLOBALS['opt'] = $TS;
ok( 'empty token is unverified',                     'unverified' === rt_captcha_verify( '', 'contact' ) );
$GLOBALS['http'] = new WP_Error( 'http', 'timeout' );
ok( 'unreachable is unverified',                     'unverified' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http'] = array( 'code' => 503, 'body' => '' );
ok( 'HTTP 503 is unverified',                        'unverified' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http'] = array( 'code' => 200, 'body' => '<html>' );
ok( 'garbled response is unverified',                'unverified' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http_calls'] = array();
$GLOBALS['http'] = json_ok( array( 'success' => true, 'hostname' => 'RussTeicheira.net', 'action' => 'contact' ) );
ok( 'Turnstile success passes',                      'pass' === rt_captcha_verify( 'tok', 'contact' ) );
$call = $GLOBALS['http_calls'][0];
ok( 'posts to Turnstile siteverify',                 'https://challenges.cloudflare.com/turnstile/v0/siteverify' === $call['url'] );
ok( 'sends Turnstile secret, token, remoteip',       'secret-ts' === $call['body']['secret'] && 'tok' === $call['body']['response'] && '203.0.113.7' === $call['body']['remoteip'] );
$GLOBALS['http'] = json_ok( array( 'success' => false, 'error-codes' => array( 'timeout-or-duplicate' ) ) );
ok( 'expired/reused token fails',                    'fail' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http'] = json_ok( array( 'success' => false, 'error-codes' => array( 'internal-error' ) ) );
ok( 'Cloudflare internal-error is unverified',       'unverified' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http'] = json_ok( array( 'success' => true, 'hostname' => 'evil.example', 'action' => 'contact' ) );
ok( 'token from another hostname fails',             'fail' === rt_captcha_verify( 'tok', 'contact' ) );
$GLOBALS['http'] = json_ok( array( 'success' => true, 'hostname' => 'russteicheira.net', 'action' => 'comment' ) );
ok( 'comment token replayed on contact fails',       'fail' === rt_captcha_verify( 'tok', 'contact' ) );

$GLOBALS['opt'] = $V3;
$GLOBALS['http_calls'] = array();
$GLOBALS['http'] = json_ok( array( 'success' => true, 'score' => 0.9, 'action' => 'comment', 'hostname' => 'russteicheira.net' ) );
ok( 'reCAPTCHA v3 good score passes',                'pass' === rt_captcha_verify( 'tok', 'comment' ) );
$call = $GLOBALS['http_calls'][0];
ok( 'posts to Google siteverify with rc secret',     'https://www.google.com/recaptcha/api/siteverify' === $call['url'] && 'secret-rc' === $call['body']['secret'] );
ok( 'no remoteip sent to Google',                    ! isset( $call['body']['remoteip'] ) );
$GLOBALS['http'] = json_ok( array( 'success' => true, 'score' => 0.3, 'action' => 'comment' ) );
ok( 'reCAPTCHA v3 low score fails',                  'fail' === rt_captcha_verify( 'tok', 'comment' ) );
$GLOBALS['http'] = json_ok( array( 'success' => true, 'score' => 0.9 ) );
ok( 'reCAPTCHA v3 missing action fails',             'fail' === rt_captcha_verify( 'tok', 'comment' ) );
$GLOBALS['opt'] = $V2;
$GLOBALS['http'] = json_ok( array( 'success' => true, 'hostname' => 'russteicheira.net' ) );
ok( 'reCAPTCHA v2 success passes (no score/action)', 'pass' === rt_captcha_verify( 'tok', 'comment' ) );

// ── rt_captcha_check_comment ─────────────────────────────────────────────────
echo "\nrt_captcha_check_comment\n";
$GLOBALS['opt']  = $TS;
$GLOBALS['http'] = json_ok( array( 'success' => false ) );
$comment         = array( 'comment_type' => 'comment', 'comment_content' => 'hi' );
$_POST           = array( 'rt_captcha_token' => 'tok' );

$GLOBALS['actions'] = array();
ok( 'ignored outside wp-comments-post.php',          $comment === rt_captcha_check_comment( $comment ) );
$GLOBALS['actions'] = array( 'pre_comment_on_post' );
$GLOBALS['logged_in'] = true;
ok( 'logged-in users skip the check',                $comment === rt_captcha_check_comment( $comment ) );
$GLOBALS['logged_in'] = false;
ok( 'pingbacks skip the check',                      'pingback' === rt_captcha_check_comment( array( 'comment_type' => 'pingback' ) )['comment_type'] );
$died = false;
try { rt_captcha_check_comment( $comment ); } catch ( WPDie $e ) { $died = true; }
ok( 'failed check stops the comment',                $died );

$GLOBALS['opt'] = array_merge( $TS, array( 'protect_comments' => false ) );
ok( 'comments toggle off skips the check',           $comment === rt_captcha_check_comment( $comment ) );

$GLOBALS['opt']     = $TS;
$GLOBALS['filters'] = array();
$_POST              = array();
$plain = array( 'comment_type' => '', 'comment_content' => 'hi' );
ok( 'unverified comment is accepted',                $plain === rt_captcha_check_comment( $plain ) );
$hold = isset( $GLOBALS['filters']['pre_comment_approved'][0] ) ? $GLOBALS['filters']['pre_comment_approved'][0] : null;
ok( '...but held for moderation',                    $hold && 0 === $hold( 1 ) && 0 === $hold( '1' ) );
ok( '...without un-spamming spam',                   $hold && 'spam' === $hold( 'spam' ) && 0 === $hold( 0 ) );

// ── LIVE (optional) ──────────────────────────────────────────────────────────
if ( in_array( '--live', $argv, true ) ) {
	echo "\nlive siteverify (public test keys)\n";
	$GLOBALS['http'] = 'live';
	// Test keys don't report this site's hostname or the form action.
	$GLOBALS['host'] = '';
	$dummy           = 'XXXX.DUMMY.TOKEN.XXXX';

	$GLOBALS['opt'] = array_merge( $TS, array( 'ts_secret_key' => '1x0000000000000000000000000000000AA' ) );
	ok( 'Turnstile "always passes" secret → pass',       'pass' === rt_captcha_verify( $dummy, 'contact' ) );
	$GLOBALS['opt'] = array_merge( $TS, array( 'ts_secret_key' => '2x0000000000000000000000000000000AA' ) );
	ok( 'Turnstile "always fails" secret → fail',        'fail' === rt_captcha_verify( $dummy, 'contact' ) );
	$GLOBALS['opt'] = array_merge( $TS, array( 'ts_secret_key' => '3x0000000000000000000000000000000AA' ) );
	ok( 'Turnstile "token already spent" secret → fail', 'fail' === rt_captcha_verify( $dummy, 'contact' ) );
	$GLOBALS['opt'] = array_merge( $V2, array( 'rc_secret_key' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe' ) );
	ok( 'reCAPTCHA v2 test secret → pass',               'pass' === rt_captcha_verify( 'any-token', 'contact' ) );
	$GLOBALS['opt'] = array_merge( $V2, array( 'rc_secret_key' => 'not-a-real-secret' ) );
	ok( 'reCAPTCHA bad secret → fail',                   'fail' === rt_captcha_verify( 'any-token', 'contact' ) );
}

// ── SUMMARY ──────────────────────────────────────────────────────────────────
echo "\n" . ( $fail ? "\033[31m" : "\033[32m" ) . "$pass passed, $fail failed\033[0m\n";
if ( $errors ) {
	echo "Failed:\n  - " . implode( "\n  - ", $errors ) . "\n";
}
exit( $fail ? 1 : 0 );
