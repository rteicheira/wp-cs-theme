<?php
/**
 * RussTeicheira Theme — functions.php
 * PHP 7.4+ compatible. No arrow functions, no nullish coalescing assignment.
 */

defined( 'ABSPATH' ) || exit;

// ── CONSTANTS ────────────────────────────────────────────────
define( 'RT_VERSION', '1.0.0' );
define( 'RT_DIR',     get_template_directory() );
define( 'RT_URI',     get_template_directory_uri() );

// ── INCLUDES ─────────────────────────────────────────────────
require_once RT_DIR . '/inc/fallback-nav.php';
require_once RT_DIR . '/inc/customizer.php';
require_once RT_DIR . '/inc/section-settings.php';


// ── THEME SETUP ──────────────────────────────────────────────
function rt_theme_setup() {
	load_theme_textdomain( 'russteicheira', RT_DIR . '/languages' );

	add_post_type_support( 'page', 'excerpt' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'style', 'script',
	) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'russteicheira' ),
		'footer'  => __( 'Footer Navigation',  'russteicheira' ),
	) );

	add_image_size( 'project-thumb', 800, 500, true );
	add_image_size( 'blog-card',     600, 400, true );
}
add_action( 'after_setup_theme', 'rt_theme_setup' );


// ── ENQUEUE ASSETS ───────────────────────────────────────────
function rt_enqueue_assets() {
	wp_enqueue_style(
		'rt-fonts',
		RT_URI . '/css/fonts.css',
		array(),
		RT_VERSION
	);

	wp_enqueue_style(
		'rt-main',
		RT_URI . '/css/main.css',
		array( 'rt-fonts' ),
		RT_VERSION
	);

	wp_enqueue_script(
		'rt-main',
		RT_URI . '/js/main.js',
		array(),
		RT_VERSION,
		true
	);

	$typing_default = implode( "\n", array(
		'> securing cardholder data environments',
		'> automating the boring stuff',
		'> docker run --rm compliance-check',
		'> grep -r "risk" /etc/security/',
		'> building things that hold up under audit',
	) );
	$typing_raw     = get_theme_mod( 'hero_typing_lines', $typing_default );
	$typing_phrases = array();
	foreach ( explode( "\n", $typing_raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$typing_phrases[] = $line;
		}
	}
	if ( empty( $typing_phrases ) ) {
		$typing_phrases = explode( "\n", $typing_default );
	}

	wp_localize_script( 'rt-main', 'RT', array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'nonce'         => wp_create_nonce( 'rt_nonce' ),
		'themeUri'      => RT_URI,
		'typingPhrases' => $typing_phrases,
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'rt_enqueue_assets' );


// ── CUSTOM POST TYPE: PROJECTS + CAPABILITIES ────────────────
function rt_register_post_types() {

	register_post_type( 'capability', array(
		'labels' => array(
			'name'          => __( 'Capabilities',           'russteicheira' ),
			'singular_name' => __( 'Capability',             'russteicheira' ),
			'add_new_item'  => __( 'Add New Capability',     'russteicheira' ),
			'edit_item'     => __( 'Edit Capability',        'russteicheira' ),
			'new_item'      => __( 'New Capability',         'russteicheira' ),
			'view_item'     => __( 'View Capability',        'russteicheira' ),
			'search_items'  => __( 'Search Capabilities',   'russteicheira' ),
			'not_found'     => __( 'No capabilities found', 'russteicheira' ),
			'menu_name'     => __( 'Capabilities',           'russteicheira' ),
		),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => array( 'rt_capability', 'rt_capabilities' ),
		'map_meta_cap'       => true,
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 6,
		'menu_icon'          => 'dashicons-awards',
		// title = capability name, excerpt = short description, page-attributes = Order field
		'supports'           => array( 'title', 'excerpt', 'page-attributes' ),
	) );

	register_post_type( 'expertise', array(
		'labels' => array(
			'name'          => __( 'Expertise',          'russteicheira' ),
			'singular_name' => __( 'Expertise',          'russteicheira' ),
			'add_new_item'  => __( 'Add New Expertise',  'russteicheira' ),
			'edit_item'     => __( 'Edit Expertise',     'russteicheira' ),
			'new_item'      => __( 'New Expertise',      'russteicheira' ),
			'search_items'  => __( 'Search Expertise',   'russteicheira' ),
			'not_found'     => __( 'No expertise found', 'russteicheira' ),
			'menu_name'     => __( 'Expertise',          'russteicheira' ),
		),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => array( 'rt_expertise', 'rt_expertises' ),
		'map_meta_cap'       => true,
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 7,
		'menu_icon'          => 'dashicons-admin-tools',
		// title = card title, excerpt = description, page-attributes = Order
		'supports'           => array( 'title', 'excerpt', 'page-attributes' ),
		'taxonomies'         => array( 'skill' ),
	) );

	register_post_type( 'project', array(
		'labels' => array(
			'name'          => __( 'Projects',        'russteicheira' ),
			'singular_name' => __( 'Project',         'russteicheira' ),
			'add_new_item'  => __( 'Add New Project', 'russteicheira' ),
			'edit_item'     => __( 'Edit Project',    'russteicheira' ),
			'new_item'      => __( 'New Project',     'russteicheira' ),
			'view_item'     => __( 'View Project',    'russteicheira' ),
			'search_items'  => __( 'Search Projects', 'russteicheira' ),
			'not_found'     => __( 'No projects found', 'russteicheira' ),
			'menu_name'     => __( 'Projects',        'russteicheira' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'projects' ),
		'capability_type'    => array( 'rt_project', 'rt_projects' ),
		'map_meta_cap'       => true,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-portfolio',
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
	) );

	register_taxonomy( 'stack', 'project', array(
		'labels' => array(
			'name'          => __( 'Stack Tags',  'russteicheira' ),
			'singular_name' => __( 'Stack Tag',   'russteicheira' ),
			'search_items'  => __( 'Search Tags', 'russteicheira' ),
			'all_items'     => __( 'All Tags',    'russteicheira' ),
			'edit_item'     => __( 'Edit Tag',    'russteicheira' ),
			'add_new_item'  => __( 'Add New Tag', 'russteicheira' ),
			'menu_name'     => __( 'Stack Tags',  'russteicheira' ),
		),
		'hierarchical'      => false,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'stack' ),
	) );
}
add_action( 'init', 'rt_register_post_types' );


// ── CPT CAPABILITIES ──────────────────────────────────────────
// Grant primitive caps for all three custom CPTs to the administrator role.
// Runs on theme activation and once on the first admin_init after activation.
if ( ! function_exists( 'rt_grant_cpt_caps' ) ) {
	function rt_grant_cpt_caps() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			return;
		}
		$sets = array(
			array( 'rt_capability', 'rt_capabilities' ),
			array( 'rt_expertise',  'rt_expertises'   ),
			array( 'rt_project',    'rt_projects'     ),
		);
		foreach ( $sets as $set ) {
			list( $singular, $plural ) = $set;
			foreach ( array(
				"edit_{$plural}",
				"edit_others_{$plural}",
				"edit_published_{$plural}",
				"edit_private_{$plural}",
				"create_{$plural}",
				"publish_{$plural}",
				"read_private_{$plural}",
				"delete_{$plural}",
				"delete_published_{$plural}",
				"delete_private_{$plural}",
				"delete_others_{$plural}",
			) as $cap ) {
				$administrator->add_cap( $cap );
			}
		}
	}
}
add_action( 'after_switch_theme', 'rt_grant_cpt_caps' );

if ( ! function_exists( 'rt_maybe_grant_cpt_caps' ) ) {
	function rt_maybe_grant_cpt_caps() {
		if ( get_option( 'rt_cpt_caps_v1' ) ) {
			return;
		}
		rt_grant_cpt_caps();
		update_option( 'rt_cpt_caps_v1', true, false );
	}
}
add_action( 'admin_init', 'rt_maybe_grant_cpt_caps' );



// ── PROJECT META BOXES ────────────────────────────────────────
function rt_add_project_meta_boxes() {
	add_meta_box(
		'rt_project_details',
		__( 'Project Details', 'russteicheira' ),
		'rt_project_meta_box_cb',
		'project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'rt_add_project_meta_boxes' );

function rt_project_meta_box_cb( $post ) {
	wp_nonce_field( 'rt_project_meta', 'rt_project_nonce' );
	$url      = get_post_meta( $post->ID, '_project_url',      true );
	$github   = get_post_meta( $post->ID, '_project_github',   true );
	$featured = get_post_meta( $post->ID, '_project_featured', true );
	?>
	<table class="form-table" style="width:100%">
		<tr>
			<th><label for="project_url"><?php _e( 'Live URL', 'russteicheira' ); ?></label></th>
			<td><input type="url" id="project_url" name="project_url" value="<?php echo esc_attr( $url ); ?>" class="widefat" placeholder="https://…" /></td>
		</tr>
		<tr>
			<th><label for="project_github"><?php _e( 'GitHub URL', 'russteicheira' ); ?></label></th>
			<td><input type="url" id="project_github" name="project_github" value="<?php echo esc_attr( $github ); ?>" class="widefat" placeholder="https://github.com/…" /></td>
		</tr>
		<tr>
			<th><label for="project_featured"><?php _e( 'Featured?', 'russteicheira' ); ?></label></th>
			<td>
				<input type="checkbox" id="project_featured" name="project_featured" value="1" <?php checked( $featured, '1' ); ?> />
				<label for="project_featured"><?php _e( 'Show on homepage', 'russteicheira' ); ?></label>
			</td>
		</tr>
	</table>
	<?php
}

function rt_save_project_meta( $post_id ) {
	if ( ! isset( $_POST['rt_project_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['rt_project_nonce'], 'rt_project_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url      = isset( $_POST['project_url'] )      ? esc_url_raw( $_POST['project_url'] )            : '';
	$github   = isset( $_POST['project_github'] )   ? esc_url_raw( $_POST['project_github'] )         : '';
	$featured = isset( $_POST['project_featured'] ) ? sanitize_text_field( $_POST['project_featured'] ) : '';
	$allowed_schemes = array( 'http', 'https' );
	if ( $url    && ! in_array( wp_parse_url( $url,    PHP_URL_SCHEME ), $allowed_schemes, true ) ) { $url    = ''; }
	if ( $github && ! in_array( wp_parse_url( $github, PHP_URL_SCHEME ), $allowed_schemes, true ) ) { $github = ''; }

	update_post_meta( $post_id, '_project_url',      $url );
	update_post_meta( $post_id, '_project_github',   $github );
	update_post_meta( $post_id, '_project_featured', $featured );
}
add_action( 'save_post_project', 'rt_save_project_meta' );


// ── CAPABILITY META BOX ───────────────────────────────────────
function rt_add_capability_meta_boxes() {
	add_meta_box(
		'rt_capability_details',
		__( 'Capability Details', 'russteicheira' ),
		'rt_capability_meta_box_cb',
		'capability',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'rt_add_capability_meta_boxes' );

function rt_capability_meta_box_cb( $post ) {
	wp_nonce_field( 'rt_capability_meta', 'rt_capability_nonce' );
	$icon = get_post_meta( $post->ID, '_capability_icon', true );
	$icon = $icon ? $icon : '📄';
	?>
	<p>
		<label for="capability_icon"><strong><?php _e( 'Icon (emoji)', 'russteicheira' ); ?></strong></label><br>
		<input type="text" id="capability_icon" name="capability_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			style="width:60px;font-size:1.5em;text-align:center;margin-top:4px;" />
	</p>
	<p class="description"><?php _e( 'Default: 📄. Title: 40 chars max. Excerpt (description): 50 chars per line, 100 max.', 'russteicheira' ); ?></p>
	<p class="description" style="margin-top:8px;color:#b00;"><?php _e( '<strong>Limit: 5 published.</strong> A 6th reverts to Draft. Use Order (Page Attributes) to control sequence.', 'russteicheira' ); ?></p>
	<?php
}

function rt_save_capability_meta( $post_id ) {
	if ( ! isset( $_POST['rt_capability_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['rt_capability_nonce'], 'rt_capability_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Enforce 5-item limit: if this post is being published and would exceed 5, revert to draft.
	if ( isset( $_POST['post_status'] ) && 'publish' === $_POST['post_status'] ) {
		$published = new WP_Query( array(
			'post_type'      => 'capability',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		// Count published, excluding the current post (already counted if previously published)
		$ids            = array_map( 'intval', $published->posts );
		$others_count   = count( array_diff( $ids, array( $post_id ) ) );
		if ( $others_count >= 5 ) {
			remove_action( 'save_post_capability', 'rt_save_capability_meta' );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			add_action( 'save_post_capability', 'rt_save_capability_meta' );
			set_transient( 'rt_cap_limit_' . get_current_user_id(), true, 60 );
			return;
		}
	}

	$icon = isset( $_POST['capability_icon'] ) ? sanitize_text_field( $_POST['capability_icon'] ) : '';
	if ( ! $icon ) {
		$icon = '📄';
	}
	update_post_meta( $post_id, '_capability_icon', $icon );
}
add_action( 'save_post_capability', 'rt_save_capability_meta' );

function rt_capability_limit_fields( $data ) {
	if ( 'capability' !== $data['post_type'] ) {
		return $data;
	}
	$uid = get_current_user_id();

	if ( mb_strlen( $data['post_title'] ) > 40 ) {
		$data['post_title'] = mb_substr( $data['post_title'], 0, 40 );
		set_transient( 'rt_cap_title_trimmed_' . $uid, true, 60 );
	}

	if ( mb_strlen( $data['post_excerpt'] ) > 100 ) {
		$data['post_excerpt'] = mb_substr( $data['post_excerpt'], 0, 100 );
		set_transient( 'rt_cap_excerpt_trimmed_' . $uid, true, 60 );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'rt_capability_limit_fields' );

function rt_capability_admin_notices() {
	$uid = get_current_user_id();

	if ( get_transient( 'rt_cap_limit_' . $uid ) ) {
		delete_transient( 'rt_cap_limit_' . $uid );
		echo '<div class="notice notice-error is-dismissible"><p><strong>'
			. esc_html__( 'Capability limit reached:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'Only 5 capabilities may be published at once. This item was saved as a Draft.', 'russteicheira' )
			. '</p></div>';
	}

	if ( get_transient( 'rt_cap_title_trimmed_' . $uid ) ) {
		delete_transient( 'rt_cap_title_trimmed_' . $uid );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>'
			. esc_html__( 'Title trimmed:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'The title exceeded 40 characters and was automatically cut to the limit.', 'russteicheira' )
			. '</p></div>';
	}

	if ( get_transient( 'rt_cap_excerpt_trimmed_' . $uid ) ) {
		delete_transient( 'rt_cap_excerpt_trimmed_' . $uid );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>'
			. esc_html__( 'Description trimmed:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'The excerpt exceeded 100 characters and was automatically cut to the limit.', 'russteicheira' )
			. '</p></div>';
	}
}
add_action( 'admin_notices', 'rt_capability_admin_notices' );

// Live counters for title and excerpt on the capability edit screen.
function rt_capability_excerpt_counter() {
	$screen = get_current_screen();
	if ( ! $screen || 'capability' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {

		// ── Title (40 chars) ──────────────────────────────────────
		var title = document.getElementById('title');
		if ( title ) {
			title.setAttribute( 'maxlength', '40' );
			var titleCounter = document.createElement('p');
			titleCounter.className = 'description';
			titleCounter.style.marginTop = '4px';
			title.parentNode.insertBefore( titleCounter, title.nextSibling );

			function updateTitle() {
				var len  = title.value.length;
				var left = 40 - len;
				if ( len === 0 ) {
					titleCounter.style.color = '';
					titleCounter.textContent = '0 / 40 characters';
				} else if ( len <= 30 ) {
					titleCounter.style.color = '#1e7e34';
					titleCounter.textContent = len + ' / 40';
				} else if ( len <= 40 ) {
					titleCounter.style.color = '#856404';
					titleCounter.textContent = len + ' / 40 — ' + left + ' remaining';
				} else {
					titleCounter.style.color = '#cc1818';
					titleCounter.textContent = len + ' / 40 — ' + Math.abs( left ) + ' over limit';
				}
			}
			title.addEventListener( 'input', updateTitle );
			updateTitle();
		}

		// ── Excerpt (50 per line, 100 max) ────────────────────────
		var excerpt = document.getElementById('excerpt');
		if ( ! excerpt ) { return; }

		var counter = document.createElement('p');
		counter.className = 'description';
		counter.style.marginTop = '4px';
		excerpt.parentNode.insertBefore( counter, excerpt.nextSibling );
		excerpt.setAttribute( 'maxlength', '100' );

		function update() {
			var len  = excerpt.value.length;
			var left = 100 - len;
			if ( len === 0 ) {
				counter.style.color = '';
				counter.textContent = '0 / 100 — 50 chars = one comfortable line';
			} else if ( len <= 50 ) {
				counter.style.color = '#1e7e34';
				counter.textContent = len + ' / 100';
			} else if ( len <= 100 ) {
				counter.style.color = '#856404';
				counter.textContent = len + ' / 100 — ' + left + ' remaining';
			} else {
				counter.style.color = '#cc1818';
				counter.textContent = len + ' / 100 — ' + Math.abs( left ) + ' over limit';
			}
		}

		excerpt.addEventListener( 'input', update );
		update();
	});
	</script>
	<?php
}
add_action( 'admin_footer', 'rt_capability_excerpt_counter' );


// ── EXPERTISE META BOX ───────────────────────────────────────
function rt_add_expertise_meta_boxes() {
	add_meta_box(
		'rt_expertise_details',
		__( 'Expertise Details', 'russteicheira' ),
		'rt_expertise_meta_box_cb',
		'expertise',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'rt_add_expertise_meta_boxes' );

function rt_expertise_meta_box_cb( $post ) {
	wp_nonce_field( 'rt_expertise_meta', 'rt_expertise_nonce' );
	$icon = get_post_meta( $post->ID, '_expertise_icon', true );
	$icon = $icon ? $icon : '📄';
	?>
	<p>
		<label for="expertise_icon"><strong><?php _e( 'Icon (emoji)', 'russteicheira' ); ?></strong></label><br>
		<input type="text" id="expertise_icon" name="expertise_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			style="width:60px;font-size:1.5em;text-align:center;margin-top:4px;" />
	</p>
	<p class="description"><?php _e( 'Default: 📄. Title: 32 chars max. Excerpt (description): 42 chars per line, 200 max. Skills panel for tags.', 'russteicheira' ); ?></p>
	<?php
}

function rt_save_expertise_meta( $post_id ) {
	if ( ! isset( $_POST['rt_expertise_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['rt_expertise_nonce'], 'rt_expertise_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$icon = isset( $_POST['expertise_icon'] ) ? sanitize_text_field( $_POST['expertise_icon'] ) : '';
	if ( ! $icon ) {
		$icon = '📄';
	}
	update_post_meta( $post_id, '_expertise_icon', $icon );
}
add_action( 'save_post_expertise', 'rt_save_expertise_meta' );

// Hard-limit title (32) and excerpt (200) before the row hits the database.
function rt_expertise_limit_fields( $data ) {
	if ( 'expertise' !== $data['post_type'] ) {
		return $data;
	}
	$uid = get_current_user_id();

	if ( mb_strlen( $data['post_title'] ) > 32 ) {
		$data['post_title'] = mb_substr( $data['post_title'], 0, 32 );
		set_transient( 'rt_exp_title_trimmed_' . $uid, true, 60 );
	}

	if ( mb_strlen( $data['post_excerpt'] ) > 200 ) {
		$data['post_excerpt'] = mb_substr( $data['post_excerpt'], 0, 200 );
		set_transient( 'rt_exp_excerpt_trimmed_' . $uid, true, 60 );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'rt_expertise_limit_fields' );

function rt_expertise_trim_notices() {
	$uid = get_current_user_id();

	if ( get_transient( 'rt_exp_title_trimmed_' . $uid ) ) {
		delete_transient( 'rt_exp_title_trimmed_' . $uid );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>'
			. esc_html__( 'Title trimmed:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'The title exceeded 32 characters and was automatically cut to the limit.', 'russteicheira' )
			. '</p></div>';
	}

	if ( get_transient( 'rt_exp_excerpt_trimmed_' . $uid ) ) {
		delete_transient( 'rt_exp_excerpt_trimmed_' . $uid );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>'
			. esc_html__( 'Description trimmed:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'The excerpt exceeded 200 characters and was automatically cut to the limit.', 'russteicheira' )
			. '</p></div>';
	}
}
add_action( 'admin_notices', 'rt_expertise_trim_notices' );

// Live counters for title and excerpt on the expertise edit screen.
function rt_expertise_field_counters() {
	$screen = get_current_screen();
	if ( ! $screen || 'expertise' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {

		// ── Title (32 chars) ──────────────────────────────────────
		var title = document.getElementById('title');
		if ( title ) {
			title.setAttribute( 'maxlength', '32' );
			var titleCounter = document.createElement('p');
			titleCounter.className = 'description';
			titleCounter.style.marginTop = '4px';
			title.parentNode.insertBefore( titleCounter, title.nextSibling );

			function updateTitle() {
				var len  = title.value.length;
				var left = 32 - len;
				if ( len === 0 ) {
					titleCounter.style.color = '';
					titleCounter.textContent = '0 / 32 characters';
				} else if ( len <= 24 ) {
					titleCounter.style.color = '#1e7e34';
					titleCounter.textContent = len + ' / 32';
				} else if ( len <= 32 ) {
					titleCounter.style.color = '#856404';
					titleCounter.textContent = len + ' / 32 — ' + left + ' remaining';
				} else {
					titleCounter.style.color = '#cc1818';
					titleCounter.textContent = len + ' / 32 — ' + Math.abs( left ) + ' over limit';
				}
			}
			title.addEventListener( 'input', updateTitle );
			updateTitle();
		}

		// ── Excerpt (76 per line, 200 max) ────────────────────────
		var excerpt = document.getElementById('excerpt');
		if ( excerpt ) {
			excerpt.setAttribute( 'maxlength', '200' );
			var excerptCounter = document.createElement('p');
			excerptCounter.className = 'description';
			excerptCounter.style.marginTop = '4px';
			excerpt.parentNode.insertBefore( excerptCounter, excerpt.nextSibling );

			function updateExcerpt() {
				var len  = excerpt.value.length;
				var left = 200 - len;
				if ( len === 0 ) {
					excerptCounter.style.color = '';
					excerptCounter.textContent = '0 / 200 — 42 chars = one comfortable line';
				} else if ( len <= 42 ) {
					excerptCounter.style.color = '#1e7e34';
					excerptCounter.textContent = len + ' / 200';
				} else if ( len <= 200 ) {
					excerptCounter.style.color = '#856404';
					excerptCounter.textContent = len + ' / 200 — ' + left + ' remaining';
				} else {
					excerptCounter.style.color = '#cc1818';
					excerptCounter.textContent = len + ' / 200 — ' + Math.abs( left ) + ' over limit';
				}
			}
			excerpt.addEventListener( 'input', updateExcerpt );
			updateExcerpt();
		}

	});
	</script>
	<?php
}
add_action( 'admin_footer', 'rt_expertise_field_counters' );


// ── CONTACT FORM AJAX ─────────────────────────────────────────
function rt_handle_contact() {
	check_ajax_referer( 'rt_nonce', 'nonce' );

	// Rate limit: 5 submissions per IP per hour.
	// Behind Cloudflare, CF-Connecting-IP carries the real visitor IP; fall back to REMOTE_ADDR for local dev.
	$cf_ip     = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '';
	$remote_ip = ( $cf_ip && filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) ? $cf_ip : $_SERVER['REMOTE_ADDR'];
	$ip_key    = 'rt_contact_rate_' . md5( $remote_ip );
	$attempts = (int) get_transient( $ip_key );
	if ( $attempts >= 5 ) {
		wp_send_json_error( array( 'message' => __( 'Too many messages sent. Please try again later.', 'russteicheira' ) ) );
		return;
	}

	$name    = isset( $_POST['name'] )    ? sanitize_text_field( $_POST['name'] )        : '';
	$email   = isset( $_POST['email'] )   ? sanitize_email( $_POST['email'] )            : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] )     : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

	// Strip CRLF sequences to prevent header injection via $name or $subject.
	$name    = str_replace( array( "\r", "\n" ), '', $name );
	$subject = str_replace( array( "\r", "\n" ), '', $subject );

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'russteicheira' ) ) );
		return;
	}

	$to      = get_option( 'admin_email' );
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);
	$body = sprintf(
		"Name: %s\nEmail: %s\nSubject: %s\n\nMessage:\n%s",
		$name, $email, $subject, $message
	);

	// Increment only on a valid, mail-bound submission so malformed requests don't burn quota.
	set_transient( $ip_key, $attempts + 1, HOUR_IN_SECONDS );

	$sent = wp_mail( $to, '[russteicheira.net] ' . $subject, $body, $headers );

	if ( $sent ) {
		wp_send_json_success( array( 'message' => __( "Message sent! I'll be in touch soon.", 'russteicheira' ) ) );
	} else {
		error_log( 'rt_handle_contact: wp_mail failed — recipient: ' . $to );
		wp_send_json_error( array( 'message' => __( 'Something went wrong. Please email me directly.', 'russteicheira' ) ) );
	}
}
add_action( 'wp_ajax_nopriv_rt_contact', 'rt_handle_contact' );
add_action( 'wp_ajax_rt_contact',        'rt_handle_contact' );


// ── WIDGETS ───────────────────────────────────────────────────
function rt_register_sidebars() {
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'russteicheira' ),
		'id'            => 'blog-sidebar',
		'description'   => __( 'Widgets for the blog sidebar.', 'russteicheira' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'rt_register_sidebars' );


// ── EXCERPT ───────────────────────────────────────────────────
function rt_excerpt_length( $length ) {
	return 25;
}
add_filter( 'excerpt_length', 'rt_excerpt_length' );

function rt_excerpt_more( $more ) {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'rt_excerpt_more' );


// ── BODY CLASSES ──────────────────────────────────────────────
function rt_body_classes( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'is-front-page';
	}
	if ( is_singular( 'project' ) ) {
		$classes[] = 'is-project';
	}
	return $classes;
}
add_filter( 'body_class', 'rt_body_classes' );


// ── WP_BODY_OPEN SHIM (WP < 5.2) ─────────────────────────────
if ( ! function_exists( 'wp_body_open' ) ) {
	function wp_body_open() {
		do_action( 'wp_body_open' );
	}
}


// ── SECURITY ──────────────────────────────────────────────────
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'the_generator', '__return_empty_string' );

if ( ! function_exists( 'rt_send_security_headers' ) ) {
	function rt_send_security_headers() {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
	}
}
add_action( 'send_headers', 'rt_send_security_headers' );


// ── TAXONOMY: Skills (for the About page badges) ─────────────
function rt_register_skill_taxonomy() {
	register_taxonomy( 'skill', array( 'page', 'expertise' ), array(
		'labels' => array(
			'name'                       => __( 'Skills',            'russteicheira' ),
			'singular_name'              => __( 'Skill',             'russteicheira' ),
			'search_items'               => __( 'Search Skills',     'russteicheira' ),
			'all_items'                  => __( 'All Skills',        'russteicheira' ),
			'edit_item'                  => __( 'Edit Skill',        'russteicheira' ),
			'update_item'                => __( 'Update Skill',      'russteicheira' ),
			'add_new_item'               => __( 'Add New Skill',     'russteicheira' ),
			'new_item_name'              => __( 'New Skill Name',    'russteicheira' ),
			'menu_name'                  => __( 'Skills',            'russteicheira' ),
			/* tag-style UI labels */
			'popular_items'              => __( 'Popular Skills',    'russteicheira' ),
			'separate_items_with_commas' => __( 'Separate skills with commas', 'russteicheira' ),
			'add_or_remove_items'        => __( 'Add or remove skills', 'russteicheira' ),
			'choose_from_most_used'      => __( 'Choose from the most used skills', 'russteicheira' ),
			'not_found'                  => __( 'No skills found.',  'russteicheira' ),
		),
		'hierarchical'          => false,   // false = tag UI (free-form), true = category UI
		'show_ui'               => true,
		'show_in_rest'          => true,    // enables the block editor tag panel
		'show_admin_column'     => true,
		'show_in_nav_menus'     => false,
		'query_var'             => false,
		'rewrite'               => false,   // no public archive needed
	) );
}
add_action( 'init', 'rt_register_skill_taxonomy' );


// ── HELPER: fetch the about-content page ─────────────────────
if ( ! function_exists( 'rt_get_about_page' ) ) {
	function rt_get_about_page() {
		$pages = get_posts( array(
			'post_type'      => 'page',
			'name'           => 'about-content',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		) );
		return ! empty( $pages ) ? $pages[0] : null;
	}
}


// ── HELPER: section header (eyebrow / heading / sub) ─────────
/**
 * Fetch the three header fields for a section from a Draft page.
 *
 * Page setup (WP Admin → Pages → Add New — do once per section):
 *   Slug:    <$slug>  (e.g. 'expertise-content', 'portfolio-content')
 *   Status:  Draft
 *   Excerpt: Eyebrow label  (e.g. "// what I do")
 *   Title:   Section heading (e.g. "Core Expertise")
 *   Content: One-sentence sub-description
 *
 * @param string $slug     Page slug to look up.
 * @param array  $defaults Fallback strings: keys 'eyebrow', 'heading', 'sub'.
 * @return array
 */
if ( ! function_exists( 'rt_get_section_header' ) ) {
	function rt_get_section_header( $slug, $defaults = array() ) {
		// Map page slugs to section settings keys
		$slug_map = array(
			'expertise-content' => 'expertise',
			'portfolio-content' => 'portfolio',
			'blog-content'      => 'blog',
		);

		$eyebrow = isset( $defaults['eyebrow'] ) ? $defaults['eyebrow'] : '';
		$heading = isset( $defaults['heading'] ) ? $defaults['heading'] : '';
		$sub     = isset( $defaults['sub'] )     ? $defaults['sub']     : '';

		if ( isset( $slug_map[ $slug ] ) ) {
			$section = $slug_map[ $slug ];
			$eyebrow = rt_section_opt( $section, 'eyebrow', $eyebrow );
			$heading = rt_section_opt( $section, 'heading', $heading );
			$sub     = rt_section_opt( $section, 'sub',     $sub );
		}

		return compact( 'eyebrow', 'heading', 'sub' );
	}
}


// ── HELPER: stack tags ────────────────────────────────────────
if ( ! function_exists( 'rt_get_stack_tags' ) ) {
	function rt_get_stack_tags( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}
		$terms = get_the_terms( $post_id, 'stack' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return $terms;
		}
		return array();
	}
}
