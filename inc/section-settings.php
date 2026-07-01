<?php
/**
 * Homepage Section Settings — dedicated admin page.
 *
 * WP Admin → Sections (top-level menu)
 *
 * Covers: About, Core Expertise, Portfolio, Blog.
 * Expertise, Portfolio, and Blog each have an Enabled toggle.
 * About is always visible.
 *
 * Individual cards (Capabilities, Expertise items) are managed
 * through their own CPT screens, not here.
 */

defined( 'ABSPATH' ) || exit;


// ── ADMIN MENU ────────────────────────────────────────────────
function rt_sections_admin_menu() {
	add_menu_page(
		__( 'Homepage Sections', 'russteicheira' ),
		__( 'Sections',          'russteicheira' ),
		'manage_options',
		'rt-sections',
		'rt_sections_page',
		'dashicons-layout',
		3
	);
}
add_action( 'admin_menu', 'rt_sections_admin_menu' );


// ── REGISTER SETTING ──────────────────────────────────────────
function rt_sections_settings_init() {
	register_setting( 'rt_sections_group', 'rt_sections', array(
		'sanitize_callback' => 'rt_sections_sanitize',
		'capability'        => 'manage_options',
	) );
}
add_action( 'admin_init', 'rt_sections_settings_init' );


// ── ADMIN STYLES + SCRIPTS ────────────────────────────────────
function rt_sections_admin_enqueue( $hook ) {
	if ( 'toplevel_page_rt-sections' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wp-components' );
	wp_enqueue_style(
		'rt-admin-sections',
		RT_URI . '/css/admin-sections.css',
		array( 'wp-components' ),
		RT_VERSION
	);
	wp_enqueue_script( 'wp-components' );
	wp_enqueue_script( 'wp-element' );
	wp_enqueue_script( 'wp-dom-ready' );
	wp_enqueue_media();
	wp_enqueue_script(
		'rt-admin-sections',
		RT_URI . '/js/admin-sections.js',
		array( 'wp-dom-ready', 'wp-element', 'wp-components', 'jquery' ),
		RT_VERSION,
		true
	);

	wp_localize_script( 'rt-admin-sections', 'rtSectionsL10n', array(
		'selectBgImage'             => __( 'Select Background Image',                                                  'russteicheira' ),
		'useAsBg'                   => __( 'Use as Background',                                                        'russteicheira' ),
		'changeImage'               => __( 'Change Image',                                                             'russteicheira' ),
		'uploadSelectImage'         => __( 'Upload / Select Image',                                                    'russteicheira' ),
		'openColorPicker'           => __( 'Open color picker',                                                        'russteicheira' ),
		'resetToDefault'            => __( 'Reset to default',                                                         'russteicheira' ),
		'resetSectionColorsConfirm' => __( 'Reset all section colors to theme defaults? This cannot be undone.',       'russteicheira' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'rt_sections_admin_enqueue' );


// ── COLOR SANITIZER ───────────────────────────────────────────
// Accepts hex3/6/8, rgb(), rgba(), hsl(), hsla() — all formats
// the WP block editor ColorPicker can emit. Values are rebuilt
// from validated numeric parts so CSS injection is impossible.
function rt_sanitize_color( $color ) {
	$color = trim( (string) $color );
	if ( '' === $color ) {
		return '';
	}

	// #rgb or #rrggbb
	$hex = sanitize_hex_color( $color );
	if ( $hex ) {
		return $hex;
	}

	// #rrggbbaa (8-digit hex with alpha)
	if ( preg_match( '/^#[0-9a-fA-F]{8}$/', $color ) ) {
		return strtolower( $color );
	}

	// rgb(r, g, b) or rgba(r, g, b, a)
	if ( preg_match(
		'/^rgba?\(\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(?:\s*,\s*(1|0|0?\.\d+))?\s*\)$/i',
		$color, $m
	) ) {
		$r = intval( $m[1] );
		$g = intval( $m[2] );
		$b = intval( $m[3] );
		if ( isset( $m[4] ) && '' !== $m[4] ) {
			$a = round( min( 1.0, max( 0.0, (float) $m[4] ) ), 4 );
			return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $a );
		}
		return sprintf( 'rgb(%d, %d, %d)', $r, $g, $b );
	}

	// hsl(h, s%, l%) or hsla(h, s%, l%, a)
	if ( preg_match(
		'/^hsla?\(\s*(360|3[0-5]\d|[12]\d{2}|[1-9]\d|\d)\s*,\s*(100|[1-9]?\d)%\s*,\s*(100|[1-9]?\d)%(?:\s*,\s*(1|0|0?\.\d+))?\s*\)$/i',
		$color, $m
	) ) {
		$h = intval( $m[1] );
		$s = intval( $m[2] );
		$l = intval( $m[3] );
		if ( isset( $m[4] ) && '' !== $m[4] ) {
			$a = round( min( 1.0, max( 0.0, (float) $m[4] ) ), 4 );
			return sprintf( 'hsla(%d, %d%%, %d%%, %s)', $h, $s, $l, $a );
		}
		return sprintf( 'hsl(%d, %d%%, %d%%)', $h, $s, $l );
	}

	return '';
}


// ── SANITIZE ──────────────────────────────────────────────────

/**
 * Convert the skills array from the settings form into a comma-separated
 * string of term IDs. New terms (prefixed 'new:') are created here only
 * during an admin settings save — not during REST, cron, or import.
 */
function rt_sections_sanitize_skills( array $raw ) {
	$ids = array();
	foreach ( $raw as $val ) {
		$val = sanitize_text_field( $val );
		if ( is_numeric( $val ) ) {
			$ids[] = intval( $val );
		} elseif ( 0 === strpos( $val, 'new:' ) ) {
			// Term creation is intentionally skipped in non-admin contexts (WP-CLI, REST, cron).
			if ( ! is_admin() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				continue;
			}
			$name = sanitize_text_field( trim( substr( $val, 4 ) ) );
			if ( '' === $name ) {
				continue;
			}
			$existing = get_term_by( 'name', $name, 'skill' );
			if ( $existing ) {
				$ids[] = $existing->term_id;
			} else {
				$result = wp_insert_term( $name, 'skill' );
				if ( ! is_wp_error( $result ) ) {
					$ids[] = $result['term_id'];
				}
			}
		}
	}
	return implode( ',', $ids );
}

function rt_sections_sanitize_about( array $raw ) {
	return array(
		'eyebrow'          => isset( $raw['eyebrow'] )          ? sanitize_text_field( $raw['eyebrow'] )          : '',
		'heading'          => isset( $raw['heading'] )          ? sanitize_text_field( $raw['heading'] )          : '',
		'body'             => isset( $raw['body'] )             ? wp_kses_post( $raw['body'] )                    : '',
		'skills'           => rt_sections_sanitize_skills(
			isset( $raw['skills'] ) && is_array( $raw['skills'] ) ? $raw['skills'] : array()
		),
		'bg_color'         => isset( $raw['bg_color'] )         ? rt_sanitize_color( $raw['bg_color'] )         : '',
		'accent_color'     => isset( $raw['accent_color'] )     ? rt_sanitize_color( $raw['accent_color'] )     : '',
		'bg_image_id'      => isset( $raw['bg_image_id'] )      ? absint( $raw['bg_image_id'] )                  : 0,
		'bg_fixed'         => isset( $raw['bg_fixed'] ) && '1' === $raw['bg_fixed'] ? '1' : '0',
		'badge_bg'         => isset( $raw['badge_bg'] )         ? rt_sanitize_color( $raw['badge_bg'] )         : '',
		'badge_color'      => isset( $raw['badge_color'] )      ? rt_sanitize_color( $raw['badge_color'] )      : '',
		'eyebrow_color'    => isset( $raw['eyebrow_color'] )    ? rt_sanitize_color( $raw['eyebrow_color'] )    : '',
		'heading_color'    => isset( $raw['heading_color'] )    ? rt_sanitize_color( $raw['heading_color'] )    : '',
		'body_color'       => isset( $raw['body_color'] )       ? rt_sanitize_color( $raw['body_color'] )       : '',
		'card_title_color' => isset( $raw['card_title_color'] ) ? rt_sanitize_color( $raw['card_title_color'] ) : '',
		'card_body_color'  => isset( $raw['card_body_color'] )  ? rt_sanitize_color( $raw['card_body_color'] )  : '',
		'portrait_id'      => isset( $raw['portrait_id'] )      ? absint( $raw['portrait_id'] )                  : 0,
	);
}

function rt_sections_sanitize_section( $key, array $raw ) {
	$out = array(
		'enabled'          => ! empty( $raw['enabled'] ) ? '1' : '0',
		'eyebrow'          => isset( $raw['eyebrow'] )          ? sanitize_text_field( $raw['eyebrow'] )        : '',
		'heading'          => isset( $raw['heading'] )          ? sanitize_text_field( $raw['heading'] )        : '',
		'sub'              => isset( $raw['sub'] )              ? sanitize_text_field( $raw['sub'] )            : '',
		'bg_color'         => isset( $raw['bg_color'] )         ? rt_sanitize_color( $raw['bg_color'] )         : '',
		'accent_color'     => isset( $raw['accent_color'] )     ? rt_sanitize_color( $raw['accent_color'] )     : '',
		'bg_image_id'      => isset( $raw['bg_image_id'] )      ? absint( $raw['bg_image_id'] )                 : 0,
		'bg_fixed'         => isset( $raw['bg_fixed'] ) && '1' === $raw['bg_fixed'] ? '1' : '0',
		'eyebrow_color'    => isset( $raw['eyebrow_color'] )    ? rt_sanitize_color( $raw['eyebrow_color'] )    : '',
		'heading_color'    => isset( $raw['heading_color'] )    ? rt_sanitize_color( $raw['heading_color'] )    : '',
		'body_color'       => isset( $raw['body_color'] )       ? rt_sanitize_color( $raw['body_color'] )       : '',
		'card_title_color' => isset( $raw['card_title_color'] ) ? rt_sanitize_color( $raw['card_title_color'] ) : '',
		'card_body_color'  => isset( $raw['card_body_color'] )  ? rt_sanitize_color( $raw['card_body_color'] )  : '',
	);
	if ( 'blog' !== $key ) {
		$out['card_tag_bg']    = isset( $raw['card_tag_bg'] )    ? rt_sanitize_color( $raw['card_tag_bg'] )    : '';
		$out['card_tag_color'] = isset( $raw['card_tag_color'] ) ? rt_sanitize_color( $raw['card_tag_color'] ) : '';
	} else {
		$out['show_date']     = ! empty( $raw['show_date'] )     ? '1' : '0';
		$out['show_author']   = ! empty( $raw['show_author'] )   ? '1' : '0';
		$out['show_category'] = ! empty( $raw['show_category'] ) ? '1' : '0';
		$out['show_skills']   = ! empty( $raw['show_skills'] )   ? '1' : '0';
	}
	return $out;
}

function rt_sections_sanitize_contact( array $raw ) {
	$raw_links = ( isset( $raw['links'] ) && is_array( $raw['links'] ) ) ? $raw['links'] : array();
	$links     = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$l       = isset( $raw_links[ $i ] ) ? (array) $raw_links[ $i ] : array();
		$links[] = array(
			'icon'    => isset( $l['icon'] )    ? sanitize_text_field( $l['icon'] )    : '',
			'label'   => isset( $l['label'] )   ? sanitize_text_field( $l['label'] )   : '',
			'url'     => isset( $l['url'] )     ? esc_url_raw( $l['url'] )             : '',
			'display' => isset( $l['display'] ) ? sanitize_text_field( $l['display'] ) : '',
		);
	}
	return array(
		'eyebrow'          => isset( $raw['eyebrow'] )          ? sanitize_text_field( $raw['eyebrow'] )        : '',
		'heading'          => isset( $raw['heading'] )          ? sanitize_text_field( $raw['heading'] )        : '',
		'sub'              => isset( $raw['sub'] )              ? sanitize_textarea_field( $raw['sub'] )        : '',
		'links'            => $links,
		'bg_color'         => isset( $raw['bg_color'] )         ? rt_sanitize_color( $raw['bg_color'] )         : '',
		'accent_color'     => isset( $raw['accent_color'] )     ? rt_sanitize_color( $raw['accent_color'] )     : '',
		'bg_image_id'      => isset( $raw['bg_image_id'] )      ? absint( $raw['bg_image_id'] )                 : 0,
		'bg_fixed'         => isset( $raw['bg_fixed'] ) && '1' === $raw['bg_fixed'] ? '1' : '0',
		'eyebrow_color'    => isset( $raw['eyebrow_color'] )    ? rt_sanitize_color( $raw['eyebrow_color'] )    : '',
		'heading_color'    => isset( $raw['heading_color'] )    ? rt_sanitize_color( $raw['heading_color'] )    : '',
		'body_color'       => isset( $raw['body_color'] )       ? rt_sanitize_color( $raw['body_color'] )       : '',
		'card_title_color' => isset( $raw['card_title_color'] ) ? rt_sanitize_color( $raw['card_title_color'] ) : '',
		'card_body_color'  => isset( $raw['card_body_color'] )  ? rt_sanitize_color( $raw['card_body_color'] )  : '',
	);
}

function rt_sections_sanitize( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}
	$out            = array();
	$out['about']   = rt_sections_sanitize_about(   isset( $input['about'] )   ? (array) $input['about']   : array() );
	$out['contact'] = rt_sections_sanitize_contact( isset( $input['contact'] ) ? (array) $input['contact'] : array() );
	foreach ( array( 'certs', 'expertise', 'portfolio', 'blog' ) as $s ) {
		$out[ $s ] = rt_sections_sanitize_section( $s, isset( $input[ $s ] ) ? (array) $input[ $s ] : array() );
	}
	return $out;
}


// ── HELPERS ───────────────────────────────────────────────────

/**
 * Get one field from section settings, falling back to $default.
 *
 * @param string $section  'about' | 'expertise' | 'portfolio' | 'blog'
 * @param string $key      Field name
 * @param string $default  Fallback value
 * @return string
 */
if ( ! function_exists( 'rt_section_opt' ) ) {
	function rt_section_opt( $section, $key, $default = '' ) {
		$opts = get_option( 'rt_sections', array() );
		if ( isset( $opts[ $section ][ $key ] ) && '' !== $opts[ $section ][ $key ] ) {
			return $opts[ $section ][ $key ];
		}
		return $default;
	}
}

/**
 * Return whether a section should be rendered on the front page.
 * About is always on. Others default to enabled until explicitly disabled.
 *
 * @param string $section
 * @return bool
 */
if ( ! function_exists( 'rt_section_enabled' ) ) {
	function rt_section_enabled( $section ) {
		if ( 'about' === $section ) {
			return true;
		}
		$opts = get_option( 'rt_sections', array() );
		if ( ! isset( $opts[ $section ]['enabled'] ) ) {
			return true; // default on before first save
		}
		return '1' === $opts[ $section ]['enabled'];
	}
}


// ── HEX → RGBA HELPER ────────────────────────────────────────
function rt_hex_to_rgba( $hex, $alpha ) {
	if ( ! $hex ) {
		return 'transparent';
	}
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return sprintf( 'rgba(%d, %d, %d, %s)',
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
		$alpha
	);
}


// ── SETTINGS PAGE ─────────────────────────────────────────────
/**
 * Compute color field values and theme-aware defaults for one section.
 *
 * @param string $key  Section key (about, certs, expertise, portfolio, blog, contact).
 * @param array  $opts Saved rt_sections option value.
 * @return array Associative array of def_* defaults and current field values.
 */
function rt_sections_color_context( $key, array $opts ) {
	// Read live Customizer palette so defaults stay in sync when site colors change.
	$c_navy     = sanitize_hex_color( get_theme_mod( 'color_navy',     '#0D1B2A' ) );
	$c_navy_mid = sanitize_hex_color( get_theme_mod( 'color_navy_mid', '#122336' ) );
	$c_teal     = sanitize_hex_color( get_theme_mod( 'color_teal',     '#1A7A6E' ) );
	$c_gold     = sanitize_hex_color( get_theme_mod( 'color_gold',     '#C9A84C' ) );
	$c_offwhite = sanitize_hex_color( get_theme_mod( 'color_offwhite', '#F0F4F8' ) );

	$section_color_defaults = array(
		'about'     => array( 'bg' => '#FFFFFF', 'accent' => $c_navy,     'eyebrow' => $c_teal,     'heading' => $c_navy,     'body' => '#4A5A6A', 'card_title' => '#FFFFFF', 'card_body' => '#8899AA' ),
		'certs'     => array( 'bg' => $c_offwhite, 'accent' => '#FFFFFF',   'eyebrow' => $c_teal,     'heading' => $c_navy,     'body' => '#5A6A7A', 'card_title' => $c_navy,   'card_body' => '#4A5A6A', 'card_tag' => $c_teal,  'card_tag_bg' => rt_hex_to_rgba( $c_teal, '0.07' ) ),
		'expertise' => array( 'bg' => $c_navy,   'accent' => $c_navy_mid, 'eyebrow' => $c_teal,     'heading' => '#FFFFFF',   'body' => '#99AABB', 'card_title' => '#FFFFFF', 'card_body' => '#7A8EA0', 'card_tag' => $c_gold,  'card_tag_bg' => rt_hex_to_rgba( $c_gold, '0.1' ) ),
		'portfolio' => array( 'bg' => $c_offwhite, 'accent' => '#FFFFFF', 'eyebrow' => $c_teal,     'heading' => $c_navy,     'body' => '#5A6A7A', 'card_title' => $c_navy,   'card_body' => '#4A5A6A', 'card_tag' => $c_teal,  'card_tag_bg' => rt_hex_to_rgba( $c_teal, '0.07' ) ),
		'blog'      => array( 'bg' => '#FFFFFF', 'accent' => $c_navy,     'eyebrow' => $c_teal,     'heading' => $c_navy,     'body' => '#5A6A7A', 'card_title' => $c_navy,   'card_body' => '#6A7A8A' ),
		'contact'   => array( 'bg' => $c_navy,   'accent' => $c_teal,     'eyebrow' => $c_teal,     'heading' => '#FFFFFF',   'body' => '#99AABB', 'card_title' => '#FFFFFF', 'card_body' => '#8899AA' ),
	);

	$def = isset( $section_color_defaults[ $key ] ) ? $section_color_defaults[ $key ] : array();
	$get = function ( $field ) use ( $opts, $key ) {
		return isset( $opts[ $key ][ $field ] ) ? $opts[ $key ][ $field ] : '';
	};

	$img_id = absint( $get( 'bg_image_id' ) );

	return array(
		'def_bg'               => isset( $def['bg'] )          ? $def['bg']          : '',
		'def_accent'           => isset( $def['accent'] )       ? $def['accent']       : '',
		'def_eyebrow_color'    => isset( $def['eyebrow'] )     ? $def['eyebrow']     : $c_teal,
		'def_heading_color'    => isset( $def['heading'] )     ? $def['heading']     : $c_navy,
		'def_body_color'       => isset( $def['body'] )        ? $def['body']        : '#4A5A6A',
		'def_card_title_color' => isset( $def['card_title'] )  ? $def['card_title']  : '#FFFFFF',
		'def_card_body_color'  => isset( $def['card_body'] )   ? $def['card_body']   : '#8899AA',
		'def_card_tag_color'   => isset( $def['card_tag'] )    ? $def['card_tag']    : $c_gold,
		'def_card_tag_bg'      => isset( $def['card_tag_bg'] ) ? $def['card_tag_bg'] : rt_hex_to_rgba( $c_gold, '0.1' ),
		'bg_color'        => $get( 'bg_color' ),
		'accent_col'      => $get( 'accent_color' ),
		'eyebrow_col'     => $get( 'eyebrow_color' ),
		'heading_col'     => $get( 'heading_color' ),
		'body_col'        => $get( 'body_color' ),
		'card_title_col'  => $get( 'card_title_color' ),
		'card_body_col'   => $get( 'card_body_color' ),
		'card_tag_col'    => $get( 'card_tag_color' ),
		'card_tag_bg_col' => $get( 'card_tag_bg' ),
		'img_id'    => $img_id,
		'bg_fixed'  => $get( 'bg_fixed' ),
		'img_thumb' => $img_id ? wp_get_attachment_image_url( $img_id, 'thumbnail' ) : '',
	);
}

/**
 * Render the Eyebrow + Heading table rows common to every section.
 */
function rt_render_eyebrow_heading_fields( $key, array $meta, $v, array $ctx ) {
	?>
	<tr>
		<th style="width:160px;">
			<label for="<?php echo esc_attr( $key ); ?>_eyebrow">
				<?php _e( 'Eyebrow', 'russteicheira' ); ?>
			</label>
		</th>
		<td>
			<div style="display:flex;align-items:center;gap:10px;">
				<input type="text"
					id="<?php echo esc_attr( $key ); ?>_eyebrow"
					name="rt_sections[<?php echo esc_attr( $key ); ?>][eyebrow]"
					value="<?php echo esc_attr( $v( $key, 'eyebrow' ) ); ?>"
					class="regular-text"
					placeholder="<?php echo esc_attr( '// ' . strtolower( $meta['label'] ) ); ?>" />
				<div class="rt-color-field"
					data-default-color="<?php echo esc_attr( $ctx['def_eyebrow_color'] ); ?>"
					data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][eyebrow_color]">
					<input type="hidden"
						name="rt_sections[<?php echo esc_attr( $key ); ?>][eyebrow_color]"
						class="rt-color-input"
						value="<?php echo esc_attr( $ctx['eyebrow_col'] ); ?>" />
					<div class="rt-color-picker-mount"></div>
				</div>
			</div>
			<p class="description"><?php _e( 'Small label shown above the heading.', 'russteicheira' ); ?></p>
		</td>
	</tr>
	<tr>
		<th>
			<label for="<?php echo esc_attr( $key ); ?>_heading">
				<?php _e( 'Heading', 'russteicheira' ); ?>
			</label>
		</th>
		<td>
			<div style="display:flex;align-items:center;gap:10px;">
				<input type="text"
					id="<?php echo esc_attr( $key ); ?>_heading"
					name="rt_sections[<?php echo esc_attr( $key ); ?>][heading]"
					value="<?php echo esc_attr( $v( $key, 'heading' ) ); ?>"
					class="regular-text" />
				<div class="rt-color-field"
					data-default-color="<?php echo esc_attr( $ctx['def_heading_color'] ); ?>"
					data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][heading_color]">
					<input type="hidden"
						name="rt_sections[<?php echo esc_attr( $key ); ?>][heading_color]"
						class="rt-color-input"
						value="<?php echo esc_attr( $ctx['heading_col'] ); ?>" />
					<div class="rt-color-picker-mount"></div>
				</div>
			</div>
		</td>
	</tr>
	<?php
}

function rt_sections_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$opts = get_option( 'rt_sections', array() );

	$v = function ( $section, $field ) use ( $opts ) {
		return isset( $opts[ $section ][ $field ] ) ? $opts[ $section ][ $field ] : '';
	};

	$is_enabled = function ( $section ) use ( $opts ) {
		if ( ! isset( $opts[ $section ]['enabled'] ) ) {
			return true;
		}
		return '1' === $opts[ $section ]['enabled'];
	};

	$sections = array(
		'about'     => array( 'label' => __( 'About',                  'russteicheira' ), 'toggle' => false ),
		'certs'     => array( 'label' => __( 'Certifications',          'russteicheira' ), 'toggle' => true  ),
		'expertise' => array( 'label' => __( 'Core Expertise',          'russteicheira' ), 'toggle' => true  ),
		'portfolio' => array( 'label' => __( 'Portfolio / Projects',    'russteicheira' ), 'toggle' => true  ),
		'blog'      => array( 'label' => __( 'Blog',                    'russteicheira' ), 'toggle' => true  ),
		'contact'   => array( 'label' => __( 'Get in Touch',            'russteicheira' ), 'toggle' => false ),
	);
	?>
	<div class="wrap">
		<h1><?php _e( 'Homepage Sections', 'russteicheira' ); ?></h1>
		<p class="description" style="margin-bottom:24px;">
			<?php _e( 'Edit section header text, colors, and background images. Individual cards (Capabilities, Expertise items) are managed through their own post-type screens.', 'russteicheira' ); ?>
		</p>

		<?php settings_errors( 'rt_sections_group' ); ?>

		<form id="rt-sections-form" method="post" action="options.php">
			<?php settings_fields( 'rt_sections_group' ); ?>

			<?php foreach ( $sections as $key => $meta ) :
				$enabled = $is_enabled( $key );
				$ctx     = rt_sections_color_context( $key, $opts );
				extract( $ctx );
			?>
			<div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px 24px;margin-bottom:20px;">

				<div style="display:flex;align-items:center;gap:16px;padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid #f0f0f1;">
					<h2 style="margin:0;font-size:1.05rem;"><?php echo esc_html( $meta['label'] ); ?></h2>

					<?php if ( $meta['toggle'] ) : ?>
						<label style="display:flex;align-items:center;gap:6px;font-weight:600;cursor:pointer;margin:0;">
							<input type="checkbox"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][enabled]"
								value="1"
								<?php checked( $enabled ); ?> />
							<?php _e( 'Enabled', 'russteicheira' ); ?>
						</label>
					<?php else : ?>
						<span style="color:#646970;font-size:0.825rem;font-style:italic;">
							<?php _e( 'Always visible', 'russteicheira' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<table class="form-table" style="margin-top:0;">
					<?php rt_render_eyebrow_heading_fields( $key, $meta, $v, $ctx ); ?>

					<?php if ( 'about' === $key ) : ?>
					<tr>
		<th>
			<label><?php _e( 'Body', 'russteicheira' ); ?></label>
		</th>
		<td>
			<?php
			wp_editor( $v( 'about', 'body' ), 'rt_about_body', array(
				'textarea_name' => 'rt_sections[about][body]',
				'textarea_rows' => 10,
				'media_buttons' => false,
				'teeny'         => false,
				'quicktags'     => true,
			) );
			?>
		</td>
	</tr>
	<tr>
		<th style="padding-top:14px;">
			<label><?php _e( 'Skills', 'russteicheira' ); ?></label>
		</th>
		<td>
			<?php
			$selected_ids = array_filter( array_map( 'intval', explode( ',', $v( 'about', 'skills' ) ) ) );
			$skill_terms  = get_terms( array(
				'taxonomy'   => 'skill',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );
			if ( is_wp_error( $skill_terms ) ) {
				$skill_terms = array();
			}

							// Build JS data arrays
							$selected_data = array();
							foreach ( $selected_ids as $sid ) {
								$st = get_term( $sid, 'skill' );
								if ( $st && ! is_wp_error( $st ) ) {
									$selected_data[] = array( 'id' => $st->term_id, 'name' => $st->name );
								}
							}
							$all_terms_data = array();
							foreach ( $skill_terms as $st ) {
								$all_terms_data[] = array( 'id' => $st->term_id, 'name' => $st->name );
							}
							?>
							<div class="rt-tag-wrap" id="rt-skills-wrap">
								<div id="rt-skills-pills"></div>
								<input type="text" id="rt-skills-input" autocomplete="off"
									placeholder="<?php esc_attr_e( 'Type to search or add a skill…', 'russteicheira' ); ?>">
								<ul class="rt-tag-dropdown" id="rt-skills-dropdown"></ul>
								<div id="rt-skills-hidden"></div>
							</div>
							<p class="description" style="max-width:600px;">
								<?php _e( 'Select existing skills or type a new one and press Enter to create it. Backspace removes the last tag.', 'russteicheira' ); ?>
							</p>
							<script>
							(function() {
								var ALL      = <?php echo wp_json_encode( $all_terms_data ); ?>;
								var selected = <?php echo wp_json_encode( $selected_data ); ?>;
								var wrap     = document.getElementById('rt-skills-wrap');
								var input    = document.getElementById('rt-skills-input');
								var dropdown = document.getElementById('rt-skills-dropdown');
								var pillsEl  = document.getElementById('rt-skills-pills');
								var hiddenEl = document.getElementById('rt-skills-hidden');

								function focusedIndex() {
									var items = dropdown.querySelectorAll('li');
									for (var i = 0; i < items.length; i++) {
										if (items[i].classList.contains('rt-focused')) return i;
									}
									return -1;
								}

								function render() {
									pillsEl.innerHTML = '';
									hiddenEl.innerHTML = '';
									selected.forEach(function(t) {
										var pill = document.createElement('span');
										pill.className = 'rt-tag-pill';
										var label = document.createTextNode(t.name);
										var btn = document.createElement('button');
										btn.type = 'button';
										btn.className = 'rt-tag-pill__remove';
										btn.setAttribute('aria-label', 'Remove ' + t.name);
										btn.textContent = '×';
										(function(term) {
											btn.addEventListener('click', function() {
												selected = selected.filter(function(s) { return s.id !== term.id; });
												render();
											});
										})(t);
										pill.appendChild(label);
										pill.appendChild(btn);
										pillsEl.appendChild(pill);
										var inp = document.createElement('input');
										inp.type  = 'hidden';
										inp.name  = 'rt_sections[about][skills][]';
										inp.value = t.id;
										hiddenEl.appendChild(inp);
									});
								}

								function buildDropdown(q) {
									dropdown.innerHTML = '';
									var lcq = q.toLowerCase();
									var usedIds = selected.map(function(s) { return s.id; });
									var matches = ALL.filter(function(t) {
										return t.name.toLowerCase().indexOf(lcq) !== -1
											&& usedIds.indexOf(t.id) === -1;
									}).slice(0, 8);
									var exactMatch = ALL.some(function(t) {
										return t.name.toLowerCase() === lcq;
									});
									if (q && !exactMatch) {
										var li = document.createElement('li');
										li.className = 'rt-add-new';
										li.textContent = 'Add “' + q + '”';
										(function(name) {
											li.addEventListener('mousedown', function(e) {
												e.preventDefault(); addNew(name);
											});
										})(q);
										dropdown.appendChild(li);
									}
									matches.forEach(function(t) {
										var li = document.createElement('li');
										li.textContent = t.name;
										(function(term) {
											li.addEventListener('mousedown', function(e) {
												e.preventDefault(); addExisting(term);
											});
										})(t);
										dropdown.appendChild(li);
									});
									dropdown.style.display = dropdown.children.length > 0 ? 'block' : 'none';
								}

								function hideDropdown() {
									dropdown.style.display = 'none';
									dropdown.innerHTML = '';
								}

								function addExisting(term) {
									if (!selected.some(function(s) { return s.id === term.id; })) {
										selected.push(term);
									}
									input.value = '';
									hideDropdown();
									render();
								}

								function addNew(name) {
									name = name.trim();
									if (!name) return;
									var id = 'new:' + name;
									if (!selected.some(function(s) { return s.id === id || s.name.toLowerCase() === name.toLowerCase(); })) {
										selected.push({ id: id, name: name });
									}
									input.value = '';
									hideDropdown();
									render();
								}

								input.addEventListener('input', function() {
									var q = this.value.trim();
									if (q.length > 0) { buildDropdown(q); } else { hideDropdown(); }
								});

								input.addEventListener('keydown', function(e) {
									var items = dropdown.querySelectorAll('li');
									var idx   = focusedIndex();
									if (e.key === 'ArrowDown') {
										e.preventDefault();
										if (dropdown.style.display === 'none' && input.value.trim()) {
											buildDropdown(input.value.trim()); return;
										}
										items.forEach(function(i) { i.classList.remove('rt-focused'); });
										var next = idx < items.length - 1 ? idx + 1 : 0;
										if (items[next]) items[next].classList.add('rt-focused');
									} else if (e.key === 'ArrowUp') {
										e.preventDefault();
										items.forEach(function(i) { i.classList.remove('rt-focused'); });
										var prev = idx > 0 ? idx - 1 : items.length - 1;
										if (items[prev]) items[prev].classList.add('rt-focused');
									} else if (e.key === 'Enter') {
										e.preventDefault();
										if (idx >= 0 && items[idx]) {
											items[idx].dispatchEvent(new MouseEvent('mousedown'));
										} else {
											var q = input.value.trim().replace(/,\s*$/, '');
											if (q) {
												var exact = null;
												for (var i = 0; i < ALL.length; i++) {
													if (ALL[i].name.toLowerCase() === q.toLowerCase()) { exact = ALL[i]; break; }
												}
												if (exact) { addExisting(exact); } else { addNew(q); }
											}
										}
									} else if (e.key === 'Backspace' && !input.value && selected.length) {
										selected = selected.slice(0, -1);
										render();
									} else if (e.key === 'Escape') {
										hideDropdown();
									}
								});

								input.addEventListener('blur', function() {
									setTimeout(hideDropdown, 150);
								});

								wrap.addEventListener('click', function(e) {
									if (e.target !== input) input.focus();
								});

								render();
							})();
							</script>
						</td>
					</tr>
					<tr>
						<th>
							<label><?php _e( 'Portrait Photo', 'russteicheira' ); ?></label>
						</th>
						<td>
							<?php
							$portrait_id  = absint( $v( 'about', 'portrait_id' ) );
							$portrait_url = $portrait_id ? wp_get_attachment_image_url( $portrait_id, 'large' ) : '';
							?>
							<div class="rt-bg-image">
								<input type="hidden" class="rt-bg-id" name="rt_sections[about][portrait_id]"
									value="<?php echo esc_attr( $portrait_id ?: '' ); ?>">
								<div class="rt-bg-preview" style="<?php echo $portrait_url ? '' : 'display:none;'; ?>">
									<img src="<?php echo esc_url( $portrait_url ); ?>" style="max-width:120px;max-height:120px;border-radius:50%;object-fit:cover;">
								</div>
								<p>
									<button type="button" class="button rt-bg-upload">
										<?php echo $portrait_url ? esc_html__( 'Change Image', 'russteicheira' ) : esc_html__( 'Upload / Select Image', 'russteicheira' ); ?>
									</button>
									<button type="button" class="button rt-bg-remove"
										style="<?php echo $portrait_url ? '' : 'display:none;'; ?>">
										<?php _e( 'Remove', 'russteicheira' ); ?>
									</button>
								</p>
							</div>
							<p class="description"><?php _e( 'Square or portrait crop works best. Displays as a circular photo above the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>

					<?php elseif ( 'contact' === $key ) : ?>
					<tr>
						<th>
							<label for="contact_sub"><?php _e( 'Subtext', 'russteicheira' ); ?></label>
						</th>
						<td>
							<textarea id="contact_sub" name="rt_sections[contact][sub]"
								class="large-text" rows="3"><?php echo esc_textarea( $v( 'contact', 'sub' ) ); ?></textarea>
							<p class="description"><?php _e( 'Paragraph shown beneath the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<tr>
						<th style="padding-top:14px;">
							<label><?php _e( 'Links', 'russteicheira' ); ?></label>
						</th>
						<td>
							<?php
							$saved_links = isset( $opts['contact']['links'] ) && is_array( $opts['contact']['links'] )
							              ? $opts['contact']['links'] : array();
							?>
							<table style="border-collapse:collapse;width:100%;max-width:680px;">
								<thead>
									<tr>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;width:52px;"><?php _e( 'Icon', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;width:120px;"><?php _e( 'Label', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;"><?php _e( 'URL', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 0 6px 0;font-size:12px;font-weight:600;color:#646970;width:150px;"><?php _e( 'Display Text', 'russteicheira' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php for ( $i = 0; $i < 5; $i++ ) :
										$lnk         = isset( $saved_links[ $i ] ) ? $saved_links[ $i ] : array();
										$lnk_icon    = isset( $lnk['icon'] )    ? $lnk['icon']    : '';
										$lnk_label   = isset( $lnk['label'] )   ? $lnk['label']   : '';
										$lnk_url     = isset( $lnk['url'] )     ? $lnk['url']     : '';
										$lnk_display = isset( $lnk['display'] ) ? $lnk['display'] : '';
									?>
									<tr style="<?php echo $i > 0 ? 'border-top:1px solid #f0f0f1;' : ''; ?>">
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][icon]"
												value="<?php echo esc_attr( $lnk_icon ); ?>"
												placeholder="📄"
												style="width:46px;text-align:center;font-size:16px;" />
										</td>
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][label]"
												value="<?php echo esc_attr( $lnk_label ); ?>"
												placeholder="<?php esc_attr_e( 'Label', 'russteicheira' ); ?>"
												style="width:110px;" />
										</td>
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][url]"
												value="<?php echo esc_attr( $lnk_url ); ?>"
												placeholder="https://"
												class="regular-text" />
										</td>
										<td style="padding:5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][display]"
												value="<?php echo esc_attr( $lnk_display ); ?>"
												placeholder="<?php esc_attr_e( 'Optional', 'russteicheira' ); ?>"
												style="width:140px;" />
										</td>
									</tr>
									<?php endfor; ?>
								</tbody>
							</table>
							<p class="description" style="margin-top:8px;max-width:680px;">
								<?php _e( 'Rows with a URL appear in the contact section. Use emoji for icons (e.g. ✉️ 💼 🐙). For email, use <code>mailto:you@example.com</code> as the URL. Display text falls back to the URL if left blank.', 'russteicheira' ); ?>
							</p>
						</td>
					</tr>

					<?php elseif ( 'blog' === $key ) : ?>
					<tr>
						<th>
							<label for="blog_sub">
								<?php _e( 'Sub-description', 'russteicheira' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								id="blog_sub"
								name="rt_sections[blog][sub]"
								value="<?php echo esc_attr( $v( 'blog', 'sub' ) ); ?>"
								class="large-text" />
							<p class="description"><?php _e( 'One-sentence description shown below the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<tr>
						<th style="padding-top:14px;">
							<?php _e( 'Card Meta', 'russteicheira' ); ?>
						</th>
						<td style="padding-top:14px;">
							<?php
							$blog_meta_items = array(
								'show_date'     => __( 'Post date',  'russteicheira' ),
								'show_author'   => __( 'Author',     'russteicheira' ),
								'show_category' => __( 'Category',   'russteicheira' ),
								'show_skills'   => __( 'Skills',     'russteicheira' ),
							);
							foreach ( $blog_meta_items as $bfield => $blabel ) :
								$bval     = $v( 'blog', $bfield );
								$bchecked = ( '' === $bval || '1' === $bval );
							?>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox"
										name="rt_sections[blog][<?php echo esc_attr( $bfield ); ?>]"
										value="1"
										<?php checked( $bchecked ); ?> />
									<?php echo esc_html( $blabel ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php _e( 'Choose which meta elements appear on blog preview cards and skill archive cards.', 'russteicheira' ); ?></p>
						</td>
					</tr>

					<?php else : ?>
					<tr>
						<th>
							<label for="<?php echo esc_attr( $key ); ?>_sub">
								<?php _e( 'Sub-description', 'russteicheira' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								id="<?php echo esc_attr( $key ); ?>_sub"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][sub]"
								value="<?php echo esc_attr( $v( $key, 'sub' ) ); ?>"
								class="large-text" />
							<p class="description"><?php _e( 'One-sentence description shown below the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>

				</table>

				<div class="rt-tab-wrapper" style="margin-top:16px;border-top:1px solid #f0f0f1;">
					<nav class="nav-tab-wrapper rt-tab-nav" style="padding:12px 0 0;margin-bottom:0;">
						<a href="#" class="nav-tab nav-tab-active"
							data-tab="rt-tab-section-<?php echo esc_attr( $key ); ?>">
							<?php _e( 'Section Colors', 'russteicheira' ); ?>
						</a>
						<a href="#" class="nav-tab"
							data-tab="rt-tab-cards-<?php echo esc_attr( $key ); ?>">
							<?php _e( 'Card Colors', 'russteicheira' ); ?>
						</a>
					</nav>

					<div id="rt-tab-section-<?php echo esc_attr( $key ); ?>" class="rt-tab-panel">
						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:160px; padding-top:12px;">
									<?php _e( 'Background Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_bg ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][bg_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][bg_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $bg_color ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Overrides the section\'s default background. Leave blank to use the theme default.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Body Text Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_body_color ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][body_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][body_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $body_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Color for body/description text within this section. Leave blank to use the theme default.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;"><?php _e( 'Background Image', 'russteicheira' ); ?></th>
								<td>
									<div class="rt-bg-image">
										<div class="rt-bg-preview" style="<?php echo $img_thumb ? '' : 'display:none;'; ?>margin-bottom:8px;">
											<img src="<?php echo esc_url( $img_thumb ); ?>" />
										</div>
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][bg_image_id]"
											class="rt-bg-id"
											value="<?php echo esc_attr( (int) $img_id ?: '' ); ?>" />
										<button type="button" class="button rt-bg-upload">
											<?php echo $img_id ? esc_html__( 'Change Image', 'russteicheira' ) : esc_html__( 'Upload / Select Image', 'russteicheira' ); ?>
										</button>
										<button type="button" class="button rt-bg-remove" style="<?php echo $img_id ? '' : 'display:none;'; ?>margin-left:6px;">
											<?php _e( 'Remove', 'russteicheira' ); ?>
										</button>
									</div>
									<p class="description" style="margin-top:6px;">
										<?php _e( 'Optional. Gradient overlays and grid patterns remain on top of the image.', 'russteicheira' ); ?>
									</p>
								</td>
							</tr>
							<tr class="rt-bg-fixed-row" <?php echo $img_id ? '' : 'style="display:none;"'; ?>>
								<th><?php _e( 'Image Behavior', 'russteicheira' ); ?></th>
								<td>
									<label style="margin-right:1.5rem;">
										<input type="radio"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][bg_fixed]"
											value="0"
											<?php checked( '1' !== $bg_fixed ); ?> />
										<?php _e( 'Scroll with page', 'russteicheira' ); ?>
									</label>
									<label>
										<input type="radio"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][bg_fixed]"
											value="1"
											<?php checked( $bg_fixed, '1' ); ?> />
										<?php _e( 'Fixed (parallax)', 'russteicheira' ); ?>
									</label>
									<p class="description"><?php _e( 'Fixed: the image stays in place as the page scrolls through the section. Not supported on iOS Safari.', 'russteicheira' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<div id="rt-tab-cards-<?php echo esc_attr( $key ); ?>" class="rt-tab-panel" style="display:none;">
						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:160px; padding-top:12px;">
									<?php _e( 'Card / Object Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_accent ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][accent_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][accent_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $accent_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php echo 'about' === $key ? esc_html__( 'Background color for the capabilities panel on the right side of the About section.', 'russteicheira' ) : esc_html__( 'Background color for the primary cards or info boxes within this section.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Card Title Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_card_title_color ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][card_title_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][card_title_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $card_title_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Color for the primary heading/title text inside cards.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Card Body Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_card_body_color ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][card_body_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][card_body_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $card_body_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Color for the description/body text inside cards.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<?php if ( in_array( $key, array( 'certs', 'expertise', 'portfolio' ) ) ) : ?>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Card Tag Background', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_card_tag_bg ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][card_tag_bg]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][card_tag_bg]"
											class="rt-color-input"
											value="<?php echo esc_attr( $card_tag_bg_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Background fill for skill tag badges on cards.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Card Tag Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="<?php echo esc_attr( $def_card_tag_color ); ?>"
										data-input-name="rt_sections[<?php echo esc_attr( $key ); ?>][card_tag_color]">
										<input type="hidden"
											name="rt_sections[<?php echo esc_attr( $key ); ?>][card_tag_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $card_tag_col ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Text and border color for skill tag badges on cards.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<?php endif; ?>
							<?php if ( 'about' === $key ) :
								$badge_bg_val    = $v( 'about', 'badge_bg' );
								$badge_color_val = $v( 'about', 'badge_color' );
							?>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Skill Tag Background', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="rgba(26, 122, 110, 0.05)"
										data-input-name="rt_sections[about][badge_bg]">
										<input type="hidden"
											name="rt_sections[about][badge_bg]"
											class="rt-color-input"
											value="<?php echo esc_attr( $badge_bg_val ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Background fill for the skill/badge tags.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<tr>
								<th style="padding-top:12px;">
									<?php _e( 'Skill Tag Color', 'russteicheira' ); ?>
								</th>
								<td>
									<div class="rt-color-field"
										data-default-color="#1A7A6E"
										data-input-name="rt_sections[about][badge_color]">
										<input type="hidden"
											name="rt_sections[about][badge_color]"
											class="rt-color-input"
											value="<?php echo esc_attr( $badge_color_val ); ?>" />
										<div class="rt-color-picker-mount"></div>
									</div>
									<p class="description"><?php _e( 'Text and border color for the skill/badge tags.', 'russteicheira' ); ?></p>
								</td>
							</tr>
							<?php endif; ?>
						</table>
					</div>
				</div>
			</div>
			<?php endforeach; ?>

			<p class="submit" style="display:flex;align-items:center;justify-content:space-between;">
				<button type="button" id="rt-reset-colors" class="button"
					style="color:#fff;border-color:#dc3232;background:#dc3232;box-shadow:0 0 0 1px #dc3232;">
					<?php _e( 'Reset All Section Colors', 'russteicheira' ); ?>
				</button>
				<input type="submit" name="submit" id="submit" class="button button-primary"
					value="<?php esc_attr_e( 'Save Section Settings', 'russteicheira' ); ?>" />
			</p>
		</form>

	</div>
	<?php
}


// ── SECTION BACKGROUND CSS OUTPUT ────────────────────────────
// Outputs scoped background/card overrides only for values that have been set.
// Runs at priority 25, after rt-main loads, so inline styles win specificity.
function rt_output_section_css() {
	$map = array(
		'about'     => array(
			'id'         => 'about',
			'card'       => '.about__highlight',
			'card_title' => '.highlight-item__text strong',
			'card_body'  => '.highlight-item__text span',
			'body'       => '.about__content p',
		),
		'certs'     => array(
			'id'         => 'certs',
			'card'       => '.cert-card',
			'card_title' => '.cert-card__name',
			'card_body'  => '.cert-card__desc',
			'card_tag'   => '.cert-card__issuer',
			'body'       => '.section-sub',
		),
		'expertise' => array(
			'id'         => 'expertise',
			'card'       => '.expertise-card',
			'card_title' => '.expertise-card__title',
			'card_body'  => '.expertise-card__desc',
			'card_tag'   => '.card-tag',
			'body'       => '.section-sub',
		),
		'portfolio' => array(
			'id'         => 'projects',
			'card'       => '.project-card',
			'card_title' => '.project-card__title',
			'card_body'  => '.project-card__desc',
			'card_tag'   => '.card-tag',
			'body'       => '.section-sub',
		),
		'blog'      => array(
			'id'         => 'blog',
			'card'       => '.blog-card__top',
			'card_title' => '.blog-card__title',
			'card_body'  => '.blog-card__excerpt',
			'body'       => '.section-sub',
		),
		'contact'   => array(
			'id'         => 'contact',
			'card'       => '.contact-link__icon',
			'card_title' => '.contact-link__value',
			'card_body'  => '.contact-link__label',
			'body'       => '.section-sub',
		),
	);
	$css = '';
	foreach ( $map as $key => $sel ) {
		$bg_color = rt_sanitize_color( rt_section_opt( $key, 'bg_color' ) );
		$accent   = rt_sanitize_color( rt_section_opt( $key, 'accent_color' ) );
		$img_id   = absint( rt_section_opt( $key, 'bg_image_id' ) );
		$fixed    = '1' === rt_section_opt( $key, 'bg_fixed', '0' );

		$sec  = '';
		$card = '';

		if ( $bg_color ) {
			$sec .= 'background-color:' . $bg_color . ';';
		}
		if ( $img_id ) {
			$img_url = wp_get_attachment_image_url( $img_id, 'full' );
			if ( $img_url ) {
				$sec .= 'background-image:url(' . esc_url( $img_url ) . ');'
				      . 'background-size:cover;'
				      . 'background-position:center;'
				      . 'background-repeat:no-repeat;'
				      . 'background-attachment:' . ( $fixed ? 'fixed' : 'scroll' ) . ';';
			}
		}
		if ( $accent ) {
			$card .= 'background:' . $accent . ';';
		}

		if ( $sec ) {
			$css .= '#' . $sel['id'] . '{' . $sec . '}';
		}
		if ( $card ) {
			$css .= '#' . $sel['id'] . ' ' . $sel['card'] . '{' . $card . '}';
		}

		if ( 'about' === $key ) {
			$badge_bg    = rt_sanitize_color( rt_section_opt( 'about', 'badge_bg' ) );
			$badge_color = rt_sanitize_color( rt_section_opt( 'about', 'badge_color' ) );
			$badge_css = '';
			if ( $badge_bg )    { $badge_css .= 'background:' . $badge_bg . ';'; }
			if ( $badge_color ) { $badge_css .= 'color:' . $badge_color . ';border-color:' . $badge_color . ';'; }
			if ( $badge_css )   { $css .= '#about .badge{' . $badge_css . '}'; }
		}

		$eyebrow_color   = rt_sanitize_color( rt_section_opt( $key, 'eyebrow_color' ) );
		$heading_color   = rt_sanitize_color( rt_section_opt( $key, 'heading_color' ) );
		$body_color      = rt_sanitize_color( rt_section_opt( $key, 'body_color' ) );
		$card_title_color = rt_sanitize_color( rt_section_opt( $key, 'card_title_color' ) );
		$card_body_color  = rt_sanitize_color( rt_section_opt( $key, 'card_body_color' ) );
		if ( $eyebrow_color )   { $css .= '#' . $sel['id'] . ' .section-eyebrow{color:' . $eyebrow_color . ';}'; }
		if ( $heading_color )   { $css .= '#' . $sel['id'] . ' .section-title{color:' . $heading_color . ';}'; }
		if ( $body_color ) {
			$css .= '#' . $sel['id'] . ' ' . $sel['body'] . '{color:' . $body_color . ';}';
			if ( 'about' === $key ) {
				$css .= '#about .about__content strong{color:' . $body_color . ';}';
			}
		}
		if ( $card_title_color ) { $css .= '#' . $sel['id'] . ' ' . $sel['card_title'] . '{color:' . $card_title_color . ';}'; }
		if ( $card_body_color )  { $css .= '#' . $sel['id'] . ' ' . $sel['card_body']  . '{color:' . $card_body_color  . ';}'; }
		if ( isset( $sel['card_tag'] ) ) {
			$card_tag_bg    = rt_sanitize_color( rt_section_opt( $key, 'card_tag_bg' ) );
			$card_tag_color = rt_sanitize_color( rt_section_opt( $key, 'card_tag_color' ) );
			$tag_sel = '#' . $sel['id'] . ' ' . $sel['card_tag'];
			if ( $card_tag_bg )    { $css .= $tag_sel . '{background:' . $card_tag_bg . ';}'; }
			if ( $card_tag_color ) { $css .= $tag_sel . '{color:' . $card_tag_color . ';border-color:' . $card_tag_color . ';}'; }
		}
	}
	if ( $css ) {
		wp_add_inline_style( 'rt-main', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'rt_output_section_css', 25 );
