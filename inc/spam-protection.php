<?php
/**
 * Spam protection for the contact form, blog comments, and the login page.
 * WP Admin → Settings → Spam Protection
 *
 * Providers: Cloudflare Turnstile, or Google reCAPTCHA (v3 invisible / v2
 * checkbox). Off until enabled with keys for the chosen provider; when off,
 * nothing is printed or loaded. The provider's script only loads on pages
 * with a protected form, and (except reCAPTCHA v2) not until the visitor
 * starts filling it in.
 *
 * Front end: rt_captcha_field_html() prints a .rt-captcha box inside the
 * form; js/spam-protection.js fills its hidden rt_captcha_token field.
 * Server: rt_captcha_verify() checks the token with the provider.
 *
 * Emergency switch: define( 'RT_SPAM_PROTECTION_OFF', true ); in
 * wp-config.php turns every check off (e.g. if broken keys block logging in).
 */

defined( 'ABSPATH' ) || exit;

const RT_CAPTCHA_OPTION = 'rt_spam_protection';


// ── SETTINGS ──────────────────────────────────────────────────
function rt_captcha_settings() {
	$saved = get_option( RT_CAPTCHA_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	// Login protection was added later: settings saved before then keep the
	// login unchecked until it's switched on here, so an update can't
	// suddenly put a check in front of the login.
	if ( $saved && ! isset( $saved['protect_login'] ) ) {
		$saved['protect_login'] = false;
	}
	return wp_parse_args(
		$saved,
		array(
			'enabled'          => false,
			'provider'         => 'turnstile',
			'protect_contact'  => true,
			'protect_comments' => true,
			'protect_login'    => true,
			'ts_site_key'      => '',
			'ts_secret_key'    => '',
			'rc_version'       => 'v3',
			'rc_site_key'      => '',
			'rc_secret_key'    => '',
			'rc_threshold'     => 0.5,
		)
	);
}

/**
 * Enabled with both keys for the chosen provider.
 */
function rt_captcha_active() {
	if ( defined( 'RT_SPAM_PROTECTION_OFF' ) && RT_SPAM_PROTECTION_OFF ) {
		return false;
	}
	$s      = rt_captcha_settings();
	$prefix = 'turnstile' === $s['provider'] ? 'ts_' : 'rc_';
	return $s['enabled'] && '' !== $s[ $prefix . 'site_key' ] && '' !== $s[ $prefix . 'secret_key' ];
}

/**
 * Whether Wordfence is active and the theme should leave log-in security to it.
 *
 * Wordfence covers log in, password reset, and registration (brute-force
 * limits, its own captcha, 2FA), so the theme's own checks for those step
 * aside instead of doubling up. It doesn't protect theme forms or comments
 * the way the theme does, so those checks always stay on. Active (not just
 * installed) counts: an installed-but-inactive plugin protects nothing.
 *
 * Override: add_filter( 'rt_defer_to_wordfence', '__return_false' );
 * keeps the theme's own log-in checks even with Wordfence active.
 */
function rt_defer_to_wordfence() {
	$active = defined( 'WORDFENCE_VERSION' ) || class_exists( 'wordfence', false );
	return (bool) apply_filters( 'rt_defer_to_wordfence', $active );
}

/**
 * Whether a form is protected right now.
 *
 * @param string $form 'contact', 'comment', or 'login'.
 */
function rt_captcha_protects( $form ) {
	if ( ! rt_captcha_active() ) {
		return false;
	}
	// Log in / reset / registration / post passwords: Wordfence's job when it's active.
	if ( 'login' === $form && rt_defer_to_wordfence() ) {
		return false;
	}
	$keys = array(
		'contact' => 'protect_contact',
		'comment' => 'protect_comments',
		'login'   => 'protect_login',
	);
	$s = rt_captcha_settings();
	return isset( $keys[ $form ] ) && (bool) $s[ $keys[ $form ] ];
}

function rt_captcha_sanitize( $input ) {
	$current = rt_captcha_settings();
	$input   = is_array( $input ) ? $input : array();

	$text = function ( $key ) use ( $input ) {
		return isset( $input[ $key ] ) ? trim( sanitize_text_field( $input[ $key ] ) ) : '';
	};
	// A blank secret field means "keep the saved one" — it's never echoed back.
	$secret = function ( $key, $clear_key ) use ( $input, $current, $text ) {
		$value = $text( $key );
		return ( '' === $value && empty( $input[ $clear_key ] ) ) ? $current[ $key ] : $value;
	};

	return array(
		'enabled'          => ! empty( $input['enabled'] ),
		'provider'         => ( isset( $input['provider'] ) && 'recaptcha' === $input['provider'] ) ? 'recaptcha' : 'turnstile',
		'protect_contact'  => ! empty( $input['protect_contact'] ),
		'protect_comments' => ! empty( $input['protect_comments'] ),
		'protect_login'    => ! empty( $input['protect_login'] ),
		'ts_site_key'      => $text( 'ts_site_key' ),
		'ts_secret_key'    => $secret( 'ts_secret_key', 'ts_clear_secret' ),
		'rc_version'       => ( isset( $input['rc_version'] ) && 'v2' === $input['rc_version'] ) ? 'v2' : 'v3',
		'rc_site_key'      => $text( 'rc_site_key' ),
		'rc_secret_key'    => $secret( 'rc_secret_key', 'rc_clear_secret' ),
		'rc_threshold'     => isset( $input['rc_threshold'] ) ? min( 0.9, max( 0.1, round( (float) $input['rc_threshold'], 1 ) ) ) : 0.5,
	);
}

function rt_captcha_register_settings() {
	register_setting(
		'rt_spam_protection',
		RT_CAPTCHA_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'rt_captcha_sanitize',
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'rt_captcha_register_settings' );


// ── ADMIN SCREEN ──────────────────────────────────────────────
function rt_captcha_admin_menu() {
	add_options_page(
		__( 'Spam Protection', 'russteicheira' ),
		__( 'Spam Protection', 'russteicheira' ),
		'manage_options',
		'rt-spam-protection',
		'rt_captcha_render_settings'
	);
}
add_action( 'admin_menu', 'rt_captcha_admin_menu' );

/**
 * Password field for a secret key: never pre-filled, with a "remove" box.
 */
function rt_captcha_secret_field( $s, $key, $clear_key, $id ) {
	$name = RT_CAPTCHA_OPTION;
	?>
	<input type="password" id="<?php echo esc_attr( $id ); ?>" class="regular-text code" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="" autocomplete="new-password"
		placeholder="<?php echo $s[ $key ] ? esc_attr__( 'Saved — leave blank to keep it', 'russteicheira' ) : ''; ?>">
	<?php if ( $s[ $key ] ) : ?>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $name . '[' . $clear_key . ']' ); ?>" value="1"> <?php esc_html_e( 'Remove the saved secret key', 'russteicheira' ); ?></label></p>
	<?php endif; ?>
	<p class="description"><?php esc_html_e( 'Kept private: it\'s never shown again after saving.', 'russteicheira' ); ?></p>
	<?php
}

function rt_captcha_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = rt_captcha_settings();
	$name   = RT_CAPTCHA_OPTION;
	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Spam Protection', 'russteicheira' ); ?></h1>
		<p><?php esc_html_e( 'Checks that the contact form, blog comments, and the login page are used by a person, not a bot. Choose Cloudflare Turnstile or Google reCAPTCHA; each keeps its own keys, so you can switch back and forth.', 'russteicheira' ); ?></p>

		<?php if ( defined( 'RT_SPAM_PROTECTION_OFF' ) && RT_SPAM_PROTECTION_OFF ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Spam protection is switched off in wp-config.php (RT_SPAM_PROTECTION_OFF), so these settings have no effect until that line is removed.', 'russteicheira' ); ?></p></div>
		<?php elseif ( $s['enabled'] && ! rt_captcha_active() ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Spam protection is turned on but isn\'t running yet — add both the site key and the secret key for the provider you chose.', 'russteicheira' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'rt_spam_protection' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'russteicheira' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?>> <?php esc_html_e( 'Turn on spam protection', 'russteicheira' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Protect', 'russteicheira' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Forms to protect', 'russteicheira' ); ?></legend>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[protect_comments]" value="1" <?php checked( $s['protect_comments'] ); ?>> <?php esc_html_e( 'Comment boxes', 'russteicheira' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[protect_contact]" value="1" <?php checked( $s['protect_contact'] ); ?>> <?php esc_html_e( 'Contact forms', 'russteicheira' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[protect_login]" value="1" <?php checked( $s['protect_login'] ); ?>> <?php esc_html_e( 'Log in, password reset, and registration', 'russteicheira' ); ?></label>
							<p class="description"><?php esc_html_e( 'Logged-in users are never checked on comments. Webmentions are unaffected. Password-protected posts use the log in setting.', 'russteicheira' ); ?></p>
							<?php if ( rt_defer_to_wordfence() ) : ?>
								<div class="notice notice-info inline"><p><?php esc_html_e( 'Wordfence is active, so it handles log in, password reset, registration, and password-protected posts. The log in setting above is ignored while Wordfence is active. The contact form and comments are still protected here.', 'russteicheira' ); ?></p></div>
							<?php endif; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Provider', 'russteicheira' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Provider', 'russteicheira' ); ?></legend>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[provider]" value="turnstile" <?php checked( $s['provider'], 'turnstile' ); ?>> <strong><?php esc_html_e( 'Cloudflare Turnstile (recommended)', 'russteicheira' ); ?></strong></label>
							<p class="description"><?php esc_html_e( 'Usually invisible; no image puzzles. Doesn\'t use visitor data for advertising.', 'russteicheira' ); ?></p>
							<br>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[provider]" value="recaptcha" <?php checked( $s['provider'], 'recaptcha' ); ?>> <strong><?php esc_html_e( 'Google reCAPTCHA', 'russteicheira' ); ?></strong></label>
							<p class="description"><?php esc_html_e( 'v3 is invisible and score-based; v2 shows an "I\'m not a robot" checkbox.', 'russteicheira' ); ?></p>
						</fieldset>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Cloudflare Turnstile', 'russteicheira' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="rt-ts-site"><?php esc_html_e( 'Site key', 'russteicheira' ); ?></label></th>
					<td><input type="text" id="rt-ts-site" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[ts_site_key]" value="<?php echo esc_attr( $s['ts_site_key'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="rt-ts-secret"><?php esc_html_e( 'Secret key', 'russteicheira' ); ?></label></th>
					<td><?php rt_captcha_secret_field( $s, 'ts_secret_key', 'ts_clear_secret', 'rt-ts-secret' ); ?></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Google reCAPTCHA', 'russteicheira' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Type', 'russteicheira' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'reCAPTCHA type', 'russteicheira' ); ?></legend>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[rc_version]" value="v3" <?php checked( $s['rc_version'], 'v3' ); ?>> <?php esc_html_e( 'v3 — invisible (recommended)', 'russteicheira' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[rc_version]" value="v2" <?php checked( $s['rc_version'], 'v2' ); ?>> <?php esc_html_e( 'v2 — "I\'m not a robot" checkbox', 'russteicheira' ); ?></label>
							<p class="description"><?php esc_html_e( 'Your keys must be created for the same type you choose here.', 'russteicheira' ); ?></p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="rt-rc-site"><?php esc_html_e( 'Site key', 'russteicheira' ); ?></label></th>
					<td><input type="text" id="rt-rc-site" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[rc_site_key]" value="<?php echo esc_attr( $s['rc_site_key'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="rt-rc-secret"><?php esc_html_e( 'Secret key', 'russteicheira' ); ?></label></th>
					<td><?php rt_captcha_secret_field( $s, 'rc_secret_key', 'rc_clear_secret', 'rt-rc-secret' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="rt-rc-threshold"><?php esc_html_e( 'Strictness (v3 only)', 'russteicheira' ); ?></label></th>
					<td>
						<input type="number" id="rt-rc-threshold" name="<?php echo esc_attr( $name ); ?>[rc_threshold]" value="<?php echo esc_attr( $s['rc_threshold'] ); ?>" min="0.1" max="0.9" step="0.1" class="small-text">
						<p class="description"><?php esc_html_e( 'Google scores each submission from 0.0 (likely a bot) to 1.0 (likely a person). Submissions below this score are blocked. 0.5 is a good default; lower it if real people are being blocked.', 'russteicheira' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Getting Turnstile keys', 'russteicheira' ); ?></h2>
		<ol>
			<li>
				<?php
				printf(
					/* translators: %s: link to the Cloudflare Turnstile dashboard */
					esc_html__( 'In the %s, choose Add widget.', 'russteicheira' ),
					'<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">' . esc_html__( 'Cloudflare Turnstile dashboard', 'russteicheira' ) . '</a>'
				);
				?>
			</li>
			<?php /* translators: %s: this site's domain */ ?>
			<li><?php printf( esc_html__( 'Add the hostname %s.', 'russteicheira' ), '<code>' . esc_html( $domain ) . '</code>' ); ?></li>
			<li><?php esc_html_e( 'Choose the Managed widget mode. It stays hidden for almost everyone and only asks for a click when a visitor looks suspicious.', 'russteicheira' ); ?></li>
			<li><?php esc_html_e( 'Copy the site key and secret key into the Turnstile fields above and save.', 'russteicheira' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Getting reCAPTCHA keys', 'russteicheira' ); ?></h2>
		<ol>
			<li>
				<?php
				printf(
					/* translators: %s: link to the Google reCAPTCHA admin console */
					esc_html__( 'Sign in to the %s with a Google account.', 'russteicheira' ),
					'<a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">' . esc_html__( 'Google reCAPTCHA admin console', 'russteicheira' ) . '</a>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Choose the same type you picked above (score-based v3, or v2 "I\'m not a robot" checkbox).', 'russteicheira' ); ?></li>
			<?php /* translators: %s: this site's domain */ ?>
			<li><?php printf( esc_html__( 'Add the domain %s.', 'russteicheira' ), '<code>' . esc_html( $domain ) . '</code>' ); ?></li>
			<li><?php esc_html_e( 'Copy the site key and secret key into the reCAPTCHA fields above and save.', 'russteicheira' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Before you turn it on', 'russteicheira' ); ?></h2>
		<ul class="ul-disc">
			<li><?php esc_html_e( 'Test first: after saving, open your login page in a private window and make sure you can still log in.', 'russteicheira' ); ?></li>
			<li>
				<?php
				printf(
					/* translators: %s: wp-config.php line */
					esc_html__( 'If you\'re ever locked out (for example, the keys were deleted at the provider), add %s to wp-config.php, log in, fix the settings here, then remove that line.', 'russteicheira' ),
					'<code>define( \'RT_SPAM_PROTECTION_OFF\', true );</code>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Content Security Policy: if your host or a Cloudflare Transform Rule sets the CSP header, allow the provider there too. Turnstile needs https://challenges.cloudflare.com in script-src and frame-src. reCAPTCHA needs https://www.google.com and https://www.gstatic.com in script-src, and https://www.google.com and https://recaptcha.google.com in frame-src.', 'russteicheira' ); ?></li>
			<li><?php esc_html_e( 'Privacy: both services receive visitor and device information. Mention the one you use in your Privacy Policy.', 'russteicheira' ); ?></li>
		</ul>
	</div>
	<?php
}


// ── FRONT END ─────────────────────────────────────────────────
/**
 * Origins the provider's script and iframe load from, for the CSP header.
 *
 * @return array{script: string[], frame: string[]}
 */
function rt_captcha_csp_sources() {
	if ( ! rt_captcha_active() ) {
		return array( 'script' => array(), 'frame' => array() );
	}
	$s = rt_captcha_settings();
	if ( 'turnstile' === $s['provider'] ) {
		return array(
			'script' => array( 'https://challenges.cloudflare.com' ),
			'frame'  => array( 'https://challenges.cloudflare.com' ),
		);
	}
	return array(
		'script' => array( 'https://www.google.com', 'https://www.gstatic.com' ),
		'frame'  => array( 'https://www.google.com', 'https://recaptcha.google.com' ),
	);
}

/**
 * Markup placed inside a protected form, just before its submit button:
 * the widget slot, the hidden token field, and (reCAPTCHA v3) the
 * attribution Google requires when its badge is hidden. Also enqueues the
 * script. Empty when the form isn't protected.
 *
 * @param string $form 'contact', 'comment', or 'login' (also the provider "action").
 */
function rt_captcha_field_html( $form ) {
	if ( ! rt_captcha_protects( $form ) ) {
		return '';
	}
	$s = rt_captcha_settings();

	wp_enqueue_script(
		'rt-spam-protection',
		RT_URI . '/js/spam-protection.js',
		array(),
		filemtime( RT_DIR . '/js/spam-protection.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
	wp_localize_script( 'rt-spam-protection', 'RTCaptchaL10n', array(
		'incomplete' => __( 'Please complete the security check, then send again.', 'russteicheira' ),
	) );

	if ( 'turnstile' === $s['provider'] ) {
		$mode = 'turnstile';
		$key  = $s['ts_site_key'];
	} else {
		$mode = 'recaptcha-' . $s['rc_version'];
		$key  = $s['rc_site_key'];
	}

	$html = sprintf(
		'<div class="rt-captcha" data-mode="%s" data-sitekey="%s" data-action="%s"%s>'
			. '<div class="rt-captcha__widget"></div>'
			. '<input type="hidden" name="rt_captcha_token" value="">'
			. '<p class="rt-captcha__msg" role="alert" hidden></p>',
		esc_attr( $mode ),
		esc_attr( $key ),
		esc_attr( $form ),
		// Comments and wp-login.php forms post normally; js/spam-protection.js
		// gets the token first. The contact form is AJAX (main.js calls RTCaptcha).
		in_array( $form, array( 'comment', 'login' ), true ) ? ' data-native-submit="1"' : ''
	);

	if ( 'recaptcha-v3' === $mode ) {
		$html .= '<p class="rt-captcha__note">' . sprintf(
			/* translators: 1: Google Privacy Policy link, 2: Google Terms of Service link */
			esc_html__( 'This form is protected by reCAPTCHA and the Google %1$s and %2$s apply.', 'russteicheira' ),
			'<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'russteicheira' ) . '</a>',
			'<a href="https://policies.google.com/terms" target="_blank" rel="noopener">' . esc_html__( 'Terms of Service', 'russteicheira' ) . '</a>'
		) . '</p>';
	}

	return $html . '</div>';
}

// Comment form: put the check just above the submit button (logged-out visitors only).
add_filter( 'comment_form_submit_field', function ( $submit_field ) {
	if ( is_user_logged_in() ) {
		return $submit_field;
	}
	return rt_captcha_field_html( 'comment' ) . $submit_field;
} );

// Login, lost-password, and registration forms on wp-login.php.
function rt_captcha_login_field() {
	echo rt_captcha_field_html( 'login' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
}
add_action( 'login_form', 'rt_captcha_login_field' );
add_action( 'lostpassword_form', 'rt_captcha_login_field' );
add_action( 'register_form', 'rt_captcha_login_field' );

// wp-login.php doesn't load the theme stylesheet: a little layout for the widget there.
function rt_captcha_login_styles() {
	if ( ! rt_captcha_protects( 'login' ) ) {
		return;
	}
	wp_add_inline_style(
		'login',
		'.rt-captcha__widget:not(:empty){margin:0 0 16px}'
		. '#loginform .rt-captcha__widget iframe[title*="reCAPTCHA"]{transform:scale(.88);transform-origin:0 0}' // v2 checkbox is 304px; the login box is narrower.
		. '.rt-captcha__msg{margin:0 0 16px;color:#d63638}'
		. '.rt-captcha__note{margin:0 0 16px;font-size:12px;color:#50575e}'
		. '.grecaptcha-badge{visibility:hidden}'
	);
}
add_action( 'login_enqueue_scripts', 'rt_captcha_login_styles' );


// ── VERIFICATION ──────────────────────────────────────────────
/**
 * Token posted by js/spam-protection.js.
 */
function rt_captcha_posted_token() {
	// phpcs:ignore WordPress.Security.NonceVerification -- read inside handlers that do their own checks.
	$token = isset( $_POST['rt_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['rt_captcha_token'] ) ) : '';
	// Both providers' tokens are well under this; anything longer is junk.
	return strlen( $token ) > 4096 ? '' : $token;
}

/**
 * Verify a submission with the provider. Returns:
 *  - 'pass'       protection is off, or the provider confirmed a person.
 *  - 'fail'       invalid / expired / reused / low-score / wrong-site token: reject.
 *  - 'unverified' no token (the provider's script was blocked in the visitor's
 *                 browser) or the provider is unreachable: callers decide —
 *                 the contact form and comments let it through flagged so a
 *                 real message is never lost; the login only tolerates an
 *                 unreachable provider.
 *
 * @param string $token  Token from the form.
 * @param string $action 'contact', 'comment', or 'login' — must match the token's action.
 */
function rt_captcha_verify( $token, $action ) {
	if ( ! rt_captcha_active() ) {
		return 'pass';
	}
	if ( '' === $token ) {
		return 'unverified';
	}

	$s            = rt_captcha_settings();
	$is_turnstile = 'turnstile' === $s['provider'];
	$body         = array(
		'secret'   => $is_turnstile ? $s['ts_secret_key'] : $s['rc_secret_key'],
		'response' => $token,
	);
	if ( $is_turnstile && function_exists( 'rt_get_visitor_ip' ) ) {
		$body['remoteip'] = rt_get_visitor_ip();
	}

	$response = wp_remote_post(
		$is_turnstile
			? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
			: 'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 8,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		error_log( 'rt spam protection: ' . $s['provider'] . ' unreachable, submission allowed through: ' . ( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return 'unverified';
	}

	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $result ) ) {
		return 'unverified';
	}
	if ( empty( $result['success'] ) ) {
		// Cloudflare's own fault, not the visitor's.
		$codes = isset( $result['error-codes'] ) ? (array) $result['error-codes'] : array();
		if ( in_array( 'internal-error', $codes, true ) ) {
			error_log( 'rt spam protection: turnstile internal-error, submission allowed through.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return 'unverified';
		}
		return 'fail';
	}

	// Token must have been issued on this site (matters if the provider's
	// own domain check is ever turned off for the key).
	$expected_host = apply_filters( 'rt_captcha_hostname', wp_parse_url( home_url(), PHP_URL_HOST ) );
	if ( ! empty( $result['hostname'] ) && $expected_host && strtolower( $result['hostname'] ) !== strtolower( $expected_host ) ) {
		return 'fail';
	}

	// A token minted for the comment form can't be replayed on the contact form.
	if ( isset( $result['action'] ) && '' !== $result['action'] && $action !== $result['action'] ) {
		return 'fail';
	}

	if ( ! $is_turnstile && 'v3' === $s['rc_version'] ) {
		$score_ok  = isset( $result['score'] ) && (float) $result['score'] >= (float) $s['rc_threshold'];
		$action_ok = isset( $result['action'] ) && $action === $result['action'];
		return ( $score_ok && $action_ok ) ? 'pass' : 'fail';
	}
	return 'pass';
}

/**
 * Login-page check, stricter than the contact form: a missing token is
 * refused (otherwise a bot could skip the check by leaving it out). Only an
 * unreachable provider lets a login through, so an outage can't lock you out.
 *
 * @return true|WP_Error
 */
function rt_captcha_check_login_request() {
	$token = rt_captcha_posted_token();
	if ( '' === $token ) {
		return new WP_Error( 'rt_captcha_missing', __( '<strong>Error:</strong> Please complete the security check, then try again.', 'russteicheira' ) );
	}
	if ( 'fail' === rt_captcha_verify( $token, 'login' ) ) {
		return new WP_Error( 'rt_captcha_failed', __( '<strong>Error:</strong> The security check didn\'t pass. Please try again.', 'russteicheira' ) );
	}
	return true;
}

/**
 * Only real form posts to wp-login.php are checked — not XML-RPC, REST
 * application passwords, or cookie logins, which also run 'authenticate'.
 */
function rt_captcha_is_login_post() {
	global $pagenow;
	return 'wp-login.php' === $pagenow && 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared to a literal.
}

function rt_captcha_check_login( $user ) {
	// phpcs:ignore WordPress.Security.NonceVerification -- core login form has no nonce.
	if ( ! rt_captcha_protects( 'login' ) || ! rt_captcha_is_login_post() || ! isset( $_POST['log'] ) ) {
		return $user;
	}
	$check = rt_captcha_check_login_request();
	return is_wp_error( $check ) ? $check : $user;
}
add_filter( 'authenticate', 'rt_captcha_check_login', 30 );

function rt_captcha_check_lost_password( $errors ) {
	if ( ! rt_captcha_protects( 'login' ) || ! rt_captcha_is_login_post() ) {
		return;
	}
	$check = rt_captcha_check_login_request();
	if ( is_wp_error( $check ) ) {
		$errors->add( $check->get_error_code(), $check->get_error_message() );
	}
}
add_action( 'lostpassword_post', 'rt_captcha_check_lost_password' );

function rt_captcha_check_registration( $errors ) {
	if ( ! rt_captcha_protects( 'login' ) || ! rt_captcha_is_login_post() ) {
		return $errors;
	}
	$check = rt_captcha_check_login_request();
	if ( is_wp_error( $check ) ) {
		$errors->add( $check->get_error_code(), $check->get_error_message() );
	}
	return $errors;
}
add_filter( 'registration_errors', 'rt_captcha_check_registration' );

/**
 * Check comments posted through wp-comments-post.php. Failures are rejected;
 * unverified comments are held for moderation instead of published.
 */
function rt_captcha_check_comment( $commentdata ) {
	if ( ! did_action( 'pre_comment_on_post' ) || is_user_logged_in() || ! rt_captcha_protects( 'comment' ) ) {
		return $commentdata;
	}
	$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
	if ( '' !== $type && 'comment' !== $type ) {
		return $commentdata;
	}

	$result = rt_captcha_verify( rt_captcha_posted_token(), 'comment' );

	if ( 'fail' === $result ) {
		wp_die(
			esc_html__( 'Your comment couldn\'t be verified as coming from a person. Please go back and try again.', 'russteicheira' ),
			esc_html__( 'Comment not posted', 'russteicheira' ),
			array( 'response' => 403, 'back_link' => true )
		);
	}

	if ( 'unverified' === $result ) {
		add_filter( 'pre_comment_approved', function ( $approved ) {
			return ( 1 === $approved || '1' === $approved ) ? 0 : $approved;
		}, 99 );
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'rt_captcha_check_comment' );
