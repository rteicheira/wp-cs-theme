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

	$wp_customize->add_setting( 'hero_name_color', array(
		'default'           => '#FFFFFF',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_name_color', array(
		'label'       => __( 'Site Title Color', 'russteicheira' ),
		'description' => __( 'Color for your name in the hero section.', 'russteicheira' ),
		'section'     => 'title_tagline',
		'priority'    => 41,
	) ) );

	$wp_customize->add_setting( 'site_tagline_color', array(
		'default'           => '#22A090',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'site_tagline_color', array(
		'label'       => __( 'Site Tagline Color', 'russteicheira' ),
		'description' => __( 'Color for the tagline text in the hero and footer. Defaults to the theme accent color.', 'russteicheira' ),
		'section'     => 'title_tagline',
		'priority'    => 42,
	) ) );

	$wp_customize->add_setting( 'hero_desc_color', array(
		'default'           => '#99AABB',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_desc_color', array(
		'label'       => __( 'Description Color', 'russteicheira' ),
		'description' => __( 'Color for the description paragraph in the hero section. Defaults to the theme body text color.', 'russteicheira' ),
		'section'     => 'title_tagline',
		'priority'    => 43,
	) ) );

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

	// ── Navigation Colors ────────────────────────────────────────
	$wp_customize->add_setting( 'nav_bg_color', array(
		'default'           => '#0D1B2A',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'nav_bg_color', array(
		'label'   => __( 'Navigation Background', 'russteicheira' ),
		'section' => 'rt_colors',
	) ) );

	$wp_customize->add_setting( 'nav_bg_opacity', array(
		'default'           => '0.95',
		'sanitize_callback' => 'rt_sanitize_opacity',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'nav_bg_opacity', array(
		'label'       => __( 'Navigation Background Opacity', 'russteicheira' ),
		'description' => __( '0 = fully transparent · 1 = fully opaque · Default: 0.95', 'russteicheira' ),
		'section'     => 'rt_colors',
		'type'        => 'range',
		'input_attrs' => array( 'min' => '0', 'max' => '1', 'step' => '0.05' ),
	) );

	$wp_customize->add_setting( 'nav_link_color', array(
		'default'           => '#8899AA',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'nav_link_color', array(
		'label'   => __( 'Navigation Link Color', 'russteicheira' ),
		'section' => 'rt_colors',
	) ) );

	// ── Reset All Colors button ──────────────────────────────────
	$wp_customize->add_setting( 'rt_color_reset', array(
		'type'              => 'option',
		'sanitize_callback' => '__return_empty_string',
	) );
	$wp_customize->add_control( new RT_Reset_Control( $wp_customize, 'rt_color_reset', array(
		'section' => 'rt_colors',
	) ) );

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

	// ── Hero Background ───────────────────────────────────────
	$wp_customize->add_setting( 'hero_bg_color', array(
		'default'           => '#0D1B2A',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_bg_color', array(
		'label'   => __( 'Background Color', 'russteicheira' ),
		'section' => 'rt_hero',
	) ) );

	$wp_customize->add_setting( 'hero_bg_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'hero_bg_image', array(
		'label'       => __( 'Background Image', 'russteicheira' ),
		'description' => __( 'Overlays on the background color. Leave blank for no image.', 'russteicheira' ),
		'section'     => 'rt_hero',
	) ) );

	$wp_customize->add_setting( 'hero_bg_fixed', array(
		'default'           => '0',
		'sanitize_callback' => 'rt_sanitize_checkbox',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'hero_bg_fixed', array(
		'label'       => __( 'Fixed Background Image', 'russteicheira' ),
		'description' => __( 'Enable parallax-style fixed attachment for the background image.', 'russteicheira' ),
		'section'     => 'rt_hero',
		'type'        => 'checkbox',
	) );

	// ── Typing Lines ──────────────────────────────────────────
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
		'section'     => 'rt_hero',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'hero_terminal_color', array(
		'default'           => '#22A090',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_terminal_color', array(
		'label'   => __( 'Typewriter Color', 'russteicheira' ),
		'section' => 'rt_hero',
	) ) );

	// ── Stats ─────────────────────────────────────────────────
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

	// ── Hero Colors ───────────────────────────────────────────
	$wp_customize->add_setting( 'hero_stat_num_color', array(
		'default'           => '#FFFFFF',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_stat_num_color', array(
		'label'   => __( 'Stat Numbers Color', 'russteicheira' ),
		'section' => 'rt_hero',
	) ) );

	$wp_customize->add_setting( 'hero_stat_label_color', array(
		'default'           => '#8899AA',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_stat_label_color', array(
		'label'   => __( 'Stat Labels Color', 'russteicheira' ),
		'section' => 'rt_hero',
	) ) );

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

	// ── Footer Colors ────────────────────────────────────────────
	$wp_customize->add_setting( 'footer_bg_color', array(
		'default'           => '#08111C',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_bg_color', array(
		'label'   => __( 'Footer Background', 'russteicheira' ),
		'section' => 'rt_footer',
	) ) );

	$wp_customize->add_setting( 'footer_bg_opacity', array(
		'default'           => '1',
		'sanitize_callback' => 'rt_sanitize_opacity',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'footer_bg_opacity', array(
		'label'       => __( 'Footer Background Opacity', 'russteicheira' ),
		'description' => __( '0 = fully transparent · 1 = fully opaque · Default: 1 (fully opaque)', 'russteicheira' ),
		'section'     => 'rt_footer',
		'type'        => 'range',
		'input_attrs' => array( 'min' => '0', 'max' => '1', 'step' => '0.05' ),
	) );

	$wp_customize->add_setting( 'footer_text_color', array(
		'default'           => '#8899AA',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_text_color', array(
		'label'       => __( 'Footer Text Color', 'russteicheira' ),
		'description' => __( 'Tagline and copyright text. Footer navigation links are not affected.', 'russteicheira' ),
		'section'     => 'rt_footer',
	) ) );
}
// Red reset-all-colors button rendered as a real Customizer control.
// Guarded so the extends clause never runs on non-Customizer page loads.
if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'RT_Reset_Control' ) ) {
	class RT_Reset_Control extends WP_Customize_Control {
		public $type = 'rt_reset';
		public function render_content() {
			?>
			<button type="button" id="rt-reset-colors-btn"
				style="display:block;width:100%;padding:8px 12px;background:#dc3232;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px;font-weight:600;"
				onmouseover="this.style.background='#b32929'"
				onmouseout="this.style.background='#dc3232'"
			><?php esc_html_e( 'Reset All Site Colors', 'russteicheira' ); ?></button>
			<?php
		}
	}
}

add_action( 'customize_register', 'rt_customizer_register' );

// Inject a synced number input next to each opacity range slider.
add_action( 'customize_controls_print_footer_scripts', function () {
	?>
	<script>
	( function () {
		var opIds = [ 'nav_bg_opacity', 'footer_bg_opacity' ];

		function enhance( id ) {
			var wrapper = document.getElementById( 'customize-control-' + id );
			if ( ! wrapper ) { return; }
			var range = wrapper.querySelector( 'input[type="range"]' );
			if ( ! range || wrapper.querySelector( '.rt-op-num' ) ) { return; }

			var num       = document.createElement( 'input' );
			num.type      = 'number';
			num.min       = '0';
			num.max       = '1';
			num.step      = '0.05';
			num.value     = range.value;
			num.className = 'rt-op-num';
			num.style.cssText = 'width:4.5rem;margin-left:8px;vertical-align:middle;';
			num.setAttribute( 'aria-label', <?php echo wp_json_encode( __( 'Opacity value', 'russteicheira' ) ); ?> );

			range.parentNode.insertBefore( num, range.nextSibling );

			range.addEventListener( 'input', function () {
				num.value = parseFloat( range.value ).toFixed( 2 );
			} );

			num.addEventListener( 'change', function () {
				var v = Math.min( 1, Math.max( 0, parseFloat( num.value ) || 0 ) );
				v = Math.round( v / 0.05 ) * 0.05;
				num.value   = v.toFixed( 2 );
				range.value = v;
				range.dispatchEvent( new Event( 'input',  { bubbles: true } ) );
				range.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		}

		document.addEventListener( 'DOMContentLoaded', function () {
			opIds.forEach( enhance );
		} );
	} )();
	</script>
	<?php
} );

// Click handler for the PHP-rendered Reset All Colors button.
// Uses event delegation so the listener survives any WP Customizer DOM re-renders.
// Resets via direct admin-ajax call (remove_theme_mod) to avoid wp.customize.save()
// leaking a persistent 'saved' listener onto subsequent Publish clicks.
add_action( 'customize_controls_print_footer_scripts', function () {
	$home_url = wp_json_encode( home_url( '/' ) );
	$nonce    = wp_create_nonce( 'rt_reset_colors' );
	$i18n     = wp_json_encode( array(
		'confirmMsg' => __( "Reset ALL site colors to their defaults?\n\nThis will clear every color customization across the entire site — the color palette, hero, navigation, and footer.\n\nThis cannot be undone.", 'russteicheira' ),
		'resetting'  => __( 'Resetting…', 'russteicheira' ),
		'resetBtn'   => __( 'Reset All Site Colors', 'russteicheira' ),
		'resetFailed' => __( 'Reset failed. Please try again.', 'russteicheira' ),
	) );
	?>
	<script>
	( function () {
		var i18n = <?php echo $i18n; ?>;
		document.addEventListener( 'click', function ( e ) {
			if ( ! e.target || e.target.id !== 'rt-reset-colors-btn' ) { return; }

			if ( ! window.confirm( i18n.confirmMsg ) ) { return; }

			var btn          = e.target;
			btn.disabled     = true;
			btn.textContent  = i18n.resetting;

			jQuery.post(
				ajaxurl,
				{ action: 'rt_reset_colors', nonce: <?php echo wp_json_encode( $nonce ); ?> },
				function ( response ) {
					if ( response.success ) {
						window.location.href = <?php echo $home_url; ?>;
					} else {
						btn.disabled    = false;
						btn.textContent = i18n.resetBtn;
						window.alert( i18n.resetFailed );
					}
				}
			).fail( function () {
				btn.disabled    = false;
				btn.textContent = i18n.resetBtn;
				window.alert( i18n.resetFailed );
			} );
		} );
	} )();
	</script>
	<?php
} );

// AJAX handler — removes all color theme mods directly from the database.
add_action( 'wp_ajax_rt_reset_colors', function () {
	check_ajax_referer( 'rt_reset_colors', 'nonce' );
	if ( ! current_user_can( 'customize' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}
	foreach ( array(
		'color_navy', 'color_navy_mid', 'color_teal', 'color_teal_lt',
		'color_gold', 'color_gold_lt', 'color_offwhite',
		'site_tagline_color', 'hero_desc_color',
		'hero_bg_color', 'hero_name_color', 'hero_terminal_color',
		'hero_stat_num_color', 'hero_stat_label_color',
		'nav_bg_color', 'nav_bg_opacity', 'nav_link_color',
		'footer_bg_color', 'footer_bg_opacity', 'footer_text_color',
	) as $key ) {
		remove_theme_mod( $key );
	}
	wp_send_json_success();
} );

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

	$tagline_color = sanitize_hex_color( get_theme_mod( 'site_tagline_color', '#22A090' ) );
	if ( $tagline_color && strtolower( $tagline_color ) !== '#22a090' ) {
		wp_add_inline_style( 'rt-main', '.hero__eyebrow,.footer-tagline{color:' . $tagline_color . ';}.hero__eyebrow::before{background:' . $tagline_color . ';}' );
	}

	$desc_color = sanitize_hex_color( get_theme_mod( 'hero_desc_color', '#99AABB' ) );
	if ( $desc_color && strtolower( $desc_color ) !== '#99aabb' ) {
		wp_add_inline_style( 'rt-main', '.hero__desc{color:' . $desc_color . ';}' );
	}

	// ── Hero section colors ───────────────────────────────────────
	$hero_bg = sanitize_hex_color( get_theme_mod( 'hero_bg_color', '#0D1B2A' ) );
	if ( $hero_bg && strtolower( $hero_bg ) !== '#0d1b2a' ) {
		wp_add_inline_style( 'rt-main', '.hero{background-color:' . $hero_bg . ';}' );
	}

	$hero_img = esc_url_raw( get_theme_mod( 'hero_bg_image', '' ) );
	if ( $hero_img ) {
		$hero_fixed = '1' === get_theme_mod( 'hero_bg_fixed', '0' ) ? 'fixed' : 'scroll';
		wp_add_inline_style( 'rt-main', '.hero{background-image:url("' . $hero_img . '");background-size:cover;background-position:center;background-attachment:' . $hero_fixed . ';}' );
	}

	$hero_name = sanitize_hex_color( get_theme_mod( 'hero_name_color', '#FFFFFF' ) );
	if ( $hero_name && strtolower( $hero_name ) !== '#ffffff' ) {
		wp_add_inline_style( 'rt-main', '.hero__name{color:' . $hero_name . ';}' );
	}

	$hero_terminal = sanitize_hex_color( get_theme_mod( 'hero_terminal_color', '#22A090' ) );
	if ( $hero_terminal && strtolower( $hero_terminal ) !== '#22a090' ) {
		wp_add_inline_style( 'rt-main', '.hero__terminal{color:' . $hero_terminal . ';}' );
	}

	$hero_stat_num = sanitize_hex_color( get_theme_mod( 'hero_stat_num_color', '#FFFFFF' ) );
	if ( $hero_stat_num && strtolower( $hero_stat_num ) !== '#ffffff' ) {
		wp_add_inline_style( 'rt-main', '.hero .stat__num{color:' . $hero_stat_num . ';}' );
	}

	$hero_stat_label = sanitize_hex_color( get_theme_mod( 'hero_stat_label_color', '#8899AA' ) );
	if ( $hero_stat_label && strtolower( $hero_stat_label ) !== '#8899aa' ) {
		wp_add_inline_style( 'rt-main', '.hero .stat__label{color:' . $hero_stat_label . ';}' );
	}

	// ── Footer colors ─────────────────────────────────────────────
	$footer_bg_hex     = sanitize_hex_color( get_theme_mod( 'footer_bg_color', '#08111C' ) );
	$footer_bg_opacity = max( 0.0, min( 1.0, (float) get_theme_mod( 'footer_bg_opacity', '1' ) ) );
	if ( $footer_bg_hex ) {
		$at_default = ( 1.0 === $footer_bg_opacity && strtolower( $footer_bg_hex ) === '#08111c' );
		if ( ! $at_default ) {
			$footer_bg_css = $footer_bg_opacity < 1.0
				? rt_hex_to_rgba( $footer_bg_hex, $footer_bg_opacity )
				: $footer_bg_hex;
			wp_add_inline_style( 'rt-main', '.site-footer{background:' . $footer_bg_css . ';}' );
		}
	}

	$footer_text = sanitize_hex_color( get_theme_mod( 'footer_text_color', '#8899AA' ) );
	if ( $footer_text && strtolower( $footer_text ) !== '#8899aa' ) {
		wp_add_inline_style( 'rt-main', '.footer-tagline,.footer-bottom p{color:' . $footer_text . ';}' );
	}

	// ── Navigation colors ─────────────────────────────────────────
	$nav_bg_hex     = sanitize_hex_color( get_theme_mod( 'nav_bg_color', '#0D1B2A' ) );
	$nav_bg_opacity = max( 0.0, min( 1.0, (float) get_theme_mod( 'nav_bg_opacity', '0.95' ) ) );
	if ( $nav_bg_hex ) {
		$at_default = ( abs( $nav_bg_opacity - 0.95 ) < 0.001 && strtolower( $nav_bg_hex ) === '#0d1b2a' );
		if ( ! $at_default ) {
			wp_add_inline_style( 'rt-main', '.site-nav{background:' . rt_hex_to_rgba( $nav_bg_hex, $nav_bg_opacity ) . ';}' );
		}
	}

	$nav_link = sanitize_hex_color( get_theme_mod( 'nav_link_color', '#8899AA' ) );
	if ( $nav_link && strtolower( $nav_link ) !== '#8899aa' ) {
		wp_add_inline_style( 'rt-main', '.nav-links li a{color:' . $nav_link . ';}' );
	}
}
add_action( 'wp_enqueue_scripts', 'rt_output_color_css', 20 );


// ── SANITIZE: checkbox (WP sends '1' when checked, '' when not) ──
function rt_sanitize_checkbox( $value ) {
	return ( '1' === $value || true === $value ) ? '1' : '0';
}

// ── SANITIZE: opacity float clamped to 0–1 ───────────────────
function rt_sanitize_opacity( $value ) {
	return (string) max( 0.0, min( 1.0, (float) $value ) );
}

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


// ── ONE-TIME CLEANUP: remove empty-string stored values so color pickers
//    show their PHP defaults instead of a gray/blank swatch.
//    get_theme_mods() is autoloaded — no extra DB hit per page load.
add_action( 'after_setup_theme', function() {
	$mods = get_theme_mods();
	foreach ( array(
		'site_tagline_color', 'hero_desc_color',
		'hero_bg_color', 'hero_name_color', 'hero_terminal_color',
		'hero_stat_num_color', 'hero_stat_label_color',
		'nav_bg_color', 'nav_link_color',
		'footer_bg_color', 'footer_text_color',
	) as $key ) {
		if ( isset( $mods[ $key ] ) && '' === $mods[ $key ] ) {
			remove_theme_mod( $key );
		}
	}
} );
