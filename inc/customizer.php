<?php
/**
 * Theme Customizer — registers all editable homepage fields.
 *
 * Sections: Hero · About · Expertise · Contact · Social Links
 *
 * Access via Appearance → Customize in the WP admin.
 * All settings use get_theme_mod() with sensible defaults so the
 * site looks correct even before the user edits anything.
 */

defined( 'ABSPATH' ) || exit;

function rt_customizer_register( $wp_customize ) {

	// ── SITE IDENTITY tweaks ──────────────────────────────────────
	// Rename WP's built-in "Tagline" to "Description"
	$ctrl = $wp_customize->get_control( 'blogdescription' );
	if ( $ctrl ) {
		$ctrl->label = __( 'Description', 'russteicheira' );
	}

	// Site Tagline — drives hero eyebrow + footer brand line
	$wp_customize->add_setting( 'site_tagline', array(
		'default'           => 'Cybersecurity & Compliance Professional',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'site_tagline', array(
		'label'       => __( 'Site Tagline', 'russteicheira' ),
		'description' => __( 'Shown above your name in the hero and in the footer brand area.', 'russteicheira' ),
		'section'     => 'title_tagline',
		'type'        => 'text',
		'priority'    => 40,
	) );

	// ════════════════════════════════════════════════════════════
	// SECTION: Site Colors
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_section( 'rt_colors', array(
		'title'       => __( 'Site Colors', 'russteicheira' ),
		'description' => __( 'Adjust the core color palette. Changes take effect site-wide.', 'russteicheira' ),
		'priority'    => 25,
	) );

	$rt_color_settings = array(
		'color_navy'     => array( 'label' => __( 'Background',       'russteicheira' ), 'default' => '#0D1B2A' ),
		'color_navy_mid' => array( 'label' => __( 'Background Mid',   'russteicheira' ), 'default' => '#122336' ),
		'color_teal'     => array( 'label' => __( 'Accent',           'russteicheira' ), 'default' => '#1A7A6E' ),
		'color_teal_lt'  => array( 'label' => __( 'Accent Light',     'russteicheira' ), 'default' => '#22A090' ),
		'color_gold'     => array( 'label' => __( 'Highlight',        'russteicheira' ), 'default' => '#C9A84C' ),
		'color_gold_lt'  => array( 'label' => __( 'Highlight Light',  'russteicheira' ), 'default' => '#E0BF6B' ),
		'color_offwhite' => array( 'label' => __( 'Light Background', 'russteicheira' ), 'default' => '#F0F4F8' ),
	);

	foreach ( $rt_color_settings as $id => $args ) {
		$wp_customize->add_setting( $id, array(
			'default'           => $args['default'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
			'label'   => $args['label'],
			'section' => 'rt_colors',
		) ) );
	}

	// ── PANEL: Homepage ──────────────────────────────────────────
	$wp_customize->add_panel( 'rt_homepage', array(
		'title'       => __( 'Homepage Content', 'russteicheira' ),
		'description' => __( 'Edit the text and content shown on the homepage.', 'russteicheira' ),
		'priority'    => 30,
	) );

	// ════════════════════════════════════════════════════════════
	// SECTION: Hero
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_section( 'rt_hero', array(
		'title'    => __( 'Hero Section', 'russteicheira' ),
		'panel'    => 'rt_homepage',
		'priority' => 10,
	) );

	// Stat 1
	$wp_customize->add_setting( 'hero_stat1_num', array(
		'default'           => 'PCI DSS',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat1_num', array(
		'label'   => __( 'Stat 1 — Number/Label', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'hero_stat1_label', array(
		'default'           => 'Compliance Focus',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat1_label', array(
		'label'   => __( 'Stat 1 — Description', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );

	// Stat 2
	$wp_customize->add_setting( 'hero_stat2_num', array(
		'default'           => '10+',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat2_num', array(
		'label'   => __( 'Stat 2 — Number', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'hero_stat2_label', array(
		'default'           => 'Years in Security',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat2_label', array(
		'label'   => __( 'Stat 2 — Description', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );

	// Stat 3
	$wp_customize->add_setting( 'hero_stat3_num', array(
		'default'           => '∞',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat3_num', array(
		'label'   => __( 'Stat 3 — Number/Symbol', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'hero_stat3_label', array(
		'default'           => 'Scripts Automated',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'hero_stat3_label', array(
		'label'   => __( 'Stat 3 — Description', 'russteicheira' ),
		'section' => 'rt_hero',
		'type'    => 'text',
	) );

	// ════════════════════════════════════════════════════════════
	// SECTION: Homepage Sections (redirect link to admin page)
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_section( 'rt_about', array(
		'title'    => __( 'Homepage Sections', 'russteicheira' ),
		'panel'    => 'rt_homepage',
		'priority' => 20,
	) );

	// Dummy read-only control so WP doesn't hide the empty section
	$wp_customize->add_setting( 'about_note', array(
		'sanitize_callback' => '__return_empty_string',
	) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'about_note', array(
		'label'       => __( 'Manage Homepage Sections', 'russteicheira' ),
		'description' => sprintf(
			__( 'Homepage section content is managed in the dedicated admin page. <a href="%s" target="_blank">Open Homepage Sections →</a>', 'russteicheira' ),
			admin_url( 'admin.php?page=rt-sections' )
		),
		'section'     => 'rt_about',
		'type'        => 'hidden',
	) ) );

	// ════════════════════════════════════════════════════════════
	// SECTION: Social Links (redirect — managed via Sections)
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_section( 'rt_social', array(
		'title'    => __( 'Social Links', 'russteicheira' ),
		'priority' => 35,
	) );

	$wp_customize->add_setting( 'social_note', array(
		'sanitize_callback' => '__return_empty_string',
	) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'social_note', array(
		'label'       => __( 'Managed in Homepage Sections', 'russteicheira' ),
		'description' => sprintf(
			__( 'Social and contact links are managed in the <strong>Get in Touch</strong> section. <a href="%s" target="_blank">Open Homepage Sections →</a>', 'russteicheira' ),
			admin_url( 'admin.php?page=rt-sections' )
		),
		'section'     => 'rt_social',
		'type'        => 'hidden',
	) ) );

	// ════════════════════════════════════════════════════════════
	// SECTION: Footer
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_section( 'rt_footer', array(
		'title'    => __( 'Footer', 'russteicheira' ),
		'priority' => 36,
	) );

	$wp_customize->add_setting( 'footer_copyright_name', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'footer_copyright_name', array(
		'label'       => __( 'Copyright Name', 'russteicheira' ),
		'description' => __( 'Name shown after © and the year. Defaults to the site name if blank.', 'russteicheira' ),
		'section'     => 'rt_footer',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'footer_credit', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'footer_credit', array(
		'label'       => __( 'Credit Line', 'russteicheira' ),
		'description' => __( 'Text to the right of the copyright (e.g. "Built on Linux"). Leave blank to hide.', 'russteicheira' ),
		'section'     => 'rt_footer',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'footer_legal_links', array(
		'default'           => '',
		'sanitize_callback' => 'rt_sanitize_legal_links',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'footer_legal_links', array(
		'label'       => __( 'Document Links', 'russteicheira' ),
		'description' => __( 'Here you can link your privacy policy, terms of use or other important documents.', 'russteicheira' ),
		'section'     => 'rt_footer',
		'type'        => 'textarea',
	) );
	// ════════════════════════════════════════════════════════════
	// SECTION: Site Identity — typing lines
	// ════════════════════════════════════════════════════════════
	$wp_customize->add_setting( 'hero_typing_lines', array(
		'default'           => implode( "\n", array(
			'> securing cardholder data environments',
			'> automating the boring stuff',
			'> docker run --rm compliance-check',
			'> grep -r "risk" /etc/security/',
			'> building things that hold up under audit',
		) ),
		'sanitize_callback' => 'rt_sanitize_typing_lines',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'hero_typing_lines', array(
		'label'       => __( 'Hero Typing Lines', 'russteicheira' ),
		'description' => __( 'One phrase per line — max 66 characters each. These cycle through the typewriter animation on the homepage.', 'russteicheira' ),
		'section'     => 'title_tagline',
		'type'        => 'textarea',
		'priority'     => 50,
	) );
}
add_action( 'customize_register', 'rt_customizer_register' );


// ── COLOR CSS OUTPUT ──────────────────────────────────────────
// Appended inline to rt-main so overrides fire after the stylesheet's :root block.
// Only changed values are emitted — unchanged colors inherit from main.css.
function rt_output_color_css() {
	$map = array(
		'color_navy'     => array( '--navy',     '#0D1B2A' ),
		'color_navy_mid' => array( '--navy-mid', '#122336' ),
		'color_teal'     => array( '--teal',     '#1A7A6E' ),
		'color_teal_lt'  => array( '--teal-lt',  '#22A090' ),
		'color_gold'     => array( '--gold',     '#C9A84C' ),
		'color_gold_lt'  => array( '--gold-lt',  '#E0BF6B' ),
		'color_offwhite' => array( '--offwhite', '#F0F4F8' ),
	);
	$decls = array();
	foreach ( $map as $mod => $pair ) {
		$val = sanitize_hex_color( get_theme_mod( $mod, $pair[1] ) );
		if ( $val && strtolower( $val ) !== strtolower( $pair[1] ) ) {
			$decls[] = $pair[0] . ':' . $val;
		}
	}
	if ( ! empty( $decls ) ) {
		wp_add_inline_style( 'rt-main', ':root{' . implode( ';', $decls ) . '}' );
	}
}
add_action( 'wp_enqueue_scripts', 'rt_output_color_css', 20 );


// ── SANITIZE: footer legal links (links + inline text only) ──
function rt_legal_links_allowed_html() {
	return array(
		'a'    => array( 'href' => array(), 'target' => array(), 'rel' => array(), 'class' => array() ),
		'span' => array( 'class' => array() ),
		'br'   => array(),
	);
}
function rt_sanitize_legal_links( $value ) {
	return wp_kses( $value, rt_legal_links_allowed_html() );
}


// ── SANITIZE: typing lines (one per line, 66-char max each) ──
function rt_sanitize_typing_lines( $value ) {
	$lines = explode( "\n", $value );
	$clean = array();
	foreach ( $lines as $line ) {
		$line = sanitize_text_field( $line );
		if ( '' !== $line ) {
			$clean[] = mb_substr( $line, 0, 66 );
		}
	}
	return implode( "\n", $clean );
}


// ── CUSTOMIZER PREVIEW SCRIPT ─────────────────────────────────
function rt_customizer_preview_enqueue() {
	wp_enqueue_script(
		'rt-customizer-preview',
		get_template_directory_uri() . '/js/customizer-preview.js',
		array( 'customize-preview' ),
		filemtime( get_template_directory() . '/js/customizer-preview.js' ),
		true
	);
}
add_action( 'customize_preview_init', 'rt_customizer_preview_enqueue' );


// ── HELPER: get theme mod with default ───────────────────────
if ( ! function_exists( 'rt_get' ) ) {
	function rt_get( $key, $default = '' ) {
		return get_theme_mod( $key, $default );
	}
}
