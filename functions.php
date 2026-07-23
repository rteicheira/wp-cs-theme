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
	add_theme_support( 'align-wide' );
	add_editor_style( 'css/main.css' );

	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'russteicheira' ),
		'footer'  => __( 'Footer Navigation',  'russteicheira' ),
	) );

	add_image_size( 'project-thumb', 800, 500, true );
	add_image_size( 'blog-card',     600, 400, true );
}
add_action( 'after_setup_theme', 'rt_theme_setup' );


// ── ENQUEUE ASSETS ───────────────────────────────────────────
function rt_get_typing_phrases() {
	$default = implode( "\n", array(
		'> securing cardholder data environments',
		'> automating the boring stuff',
		'> docker run --rm compliance-check',
		'> grep -r "risk" /etc/security/',
		'> building things that hold up under audit',
	) );
	$raw     = get_theme_mod( 'hero_typing_lines', $default );
	$phrases = array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
	return $phrases ?: explode( "\n", $default );
}

function rt_enqueue_assets() {
	wp_enqueue_style(
		'rt-fonts',
		RT_URI . '/css/fonts.css',
		array(),
		filemtime( RT_DIR . '/css/fonts.css' )
	);

	wp_enqueue_style(
		'rt-main',
		RT_URI . '/css/main.css',
		array( 'rt-fonts' ),
		filemtime( RT_DIR . '/css/main.css' )
	);

	wp_enqueue_script(
		'rt-main',
		RT_URI . '/js/main.js',
		array(),
		filemtime( RT_DIR . '/js/main.js' ),
		true
	);

	wp_localize_script( 'rt-main', 'RT', array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'nonce'         => wp_create_nonce( 'rt_nonce' ),
		'themeUri'      => RT_URI,
		'typingPhrases' => rt_get_typing_phrases(),
		'contactError'  => __( 'Something went wrong. Please try again or email me directly.', 'russteicheira' ),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'rt_enqueue_assets' );


// ── READING TIME ─────────────────────────────────────────────
function rt_reading_time() {
    $content    = get_post_field( 'post_content', get_the_ID() );
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    $minutes    = max( 1, (int) round( $word_count / 200 ) );
    /* translators: %d: estimated minutes to read the post */
    return sprintf( _n( '%d min read', '%d min read', $minutes, 'russteicheira' ), $minutes );
}


// ── TABLE OF CONTENTS ─────────────────────────────────────────
// Parses h2/h3 elements that carry an id attribute (Gutenberg adds these
// automatically to heading blocks at save time). Returns '' if fewer than
// three headings are found — not worth a TOC for short posts.
function rt_get_toc() {
    $content = get_post_field( 'post_content', get_the_ID() );
    if ( ! preg_match_all(
        '/<h([23])\b[^>]*\bid=["\']([^"\']+)["\'][^>]*>(.*?)<\/h\1>/is',
        $content,
        $matches
    ) ) {
        return '';
    }
    if ( count( $matches[0] ) < 3 ) {
        return '';
    }
    $items = '';
    foreach ( $matches[1] as $i => $level ) {
        $id    = esc_attr( $matches[2][ $i ] );
        $text  = wp_strip_all_tags( $matches[3][ $i ] );
        $items .= '<li class="toc-item toc-item--h' . (int) $level . '"><a href="#' . $id . '">' . esc_html( $text ) . '</a></li>';
    }
    return '<nav class="toc" aria-label="' . esc_attr__( 'Table of contents', 'russteicheira' ) . '">'
        . '<p class="toc__title">' . esc_html__( 'On this page', 'russteicheira' ) . '</p>'
        . '<ol class="toc__list">' . $items . '</ol>'
        . '</nav>';
}


// ── RELATED POSTS ─────────────────────────────────────────────
// Finds up to 3 published posts sharing a skill tag with the current post.
// Falls back to posts in the same category if the post has no skill tags,
// and returns '' entirely when neither produces results.
function rt_related_posts() {
    $current_id = get_the_ID();
    $skills     = get_the_terms( $current_id, 'skill' );
    $args       = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'post__not_in'   => array( $current_id ),
        'no_found_rows'  => true,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $skills && ! is_wp_error( $skills ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'skill',
                'field'    => 'term_id',
                'terms'    => wp_list_pluck( $skills, 'term_id' ),
            ),
        );
    } else {
        $cats = get_the_category( $current_id );
        if ( ! $cats ) {
            return '';
        }
        $args['category__in'] = wp_list_pluck( $cats, 'term_id' );
    }

    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) {
        return '';
    }

    $html = '<section class="related-posts">'
        . '<h2 class="related-posts__heading">' . esc_html__( 'Related Posts', 'russteicheira' ) . '</h2>'
        . '<div class="related-posts__grid">';

    while ( $query->have_posts() ) {
        $query->the_post();
        $html .= '<article class="related-post">';
        if ( has_post_thumbnail() ) {
            $html .= '<a class="related-post__thumb" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">'
                . get_the_post_thumbnail( null, 'blog-card' )
                . '</a>';
        }
        $html .= '<div class="related-post__body">'
            . '<a class="related-post__title" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>'
            . '<p class="related-post__meta">' . esc_html( get_the_date( 'M j, Y' ) ) . ' &middot; ' . esc_html( rt_reading_time() ) . '</p>'
            . '</div>'
            . '</article>';
    }
    wp_reset_postdata();

    $html .= '</div></section>';
    return $html;
}


// ── PRISM SYNTAX HIGHLIGHTING ─────────────────────────────────
// Load PrismJS only on singular posts/pages that contain a Code block.
function rt_enqueue_prism() {
    if ( ! is_singular() || ! has_block( 'core/code' ) ) {
        return;
    }
    wp_enqueue_style(
        'rt-prism',
        RT_URI . '/css/prism.css',
        array(),
        filemtime( RT_DIR . '/css/prism.css' )
    );
    wp_enqueue_script(
        'rt-prism',
        RT_URI . '/js/prism.js',
        array(),
        filemtime( RT_DIR . '/js/prism.js' ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'rt_enqueue_prism' );

// Add line-numbers class to the <pre> in every rendered Code block so the
// Prism line-numbers plugin activates without needing a JS class injection step.
add_filter( 'render_block_core/code', function ( $block_content ) {
    return preg_replace(
        '/(<pre\b[^>]*class=["\'])/',
        '$1line-numbers ',
        $block_content,
        1
    );
} );


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
			'not_found'          => __( 'No capabilities found',          'russteicheira' ),
			'not_found_in_trash' => __( 'No capabilities found in Trash', 'russteicheira' ),
			'menu_name'          => __( 'Capabilities',                   'russteicheira' ),
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
			'not_found'          => __( 'No expertise found',          'russteicheira' ),
			'not_found_in_trash' => __( 'No expertise found in Trash', 'russteicheira' ),
			'view_item'          => __( 'View Expertise',               'russteicheira' ),
			'menu_name'          => __( 'Expertise',                    'russteicheira' ),
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
			'not_found'          => __( 'No projects found',          'russteicheira' ),
			'not_found_in_trash' => __( 'No projects found in Trash', 'russteicheira' ),
			'menu_name'          => __( 'Projects',                   'russteicheira' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'rest_base'          => 'projects',
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

	register_post_type( 'certification', array(
		'labels' => array(
			'name'          => __( 'Certifications',         'russteicheira' ),
			'singular_name' => __( 'Certification',          'russteicheira' ),
			'add_new_item'  => __( 'Add New Certification',  'russteicheira' ),
			'edit_item'     => __( 'Edit Certification',     'russteicheira' ),
			'new_item'      => __( 'New Certification',      'russteicheira' ),
			'search_items'  => __( 'Search Certifications',  'russteicheira' ),
			'not_found'          => __( 'No certifications found',          'russteicheira' ),
			'not_found_in_trash' => __( 'No certifications found in Trash', 'russteicheira' ),
			'menu_name'          => __( 'Certifications',                   'russteicheira' ),
		),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => array( 'rt_certification', 'rt_certifications' ),
		'map_meta_cap'       => true,
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 8,
		'menu_icon'          => 'dashicons-welcome-learn-more',
		// title = cert name, excerpt = optional description, page-attributes = Order
		'supports'           => array( 'title', 'excerpt', 'page-attributes' ),
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
			array( 'rt_capability',    'rt_capabilities'    ),
			array( 'rt_expertise',     'rt_expertises'      ),
			array( 'rt_project',       'rt_projects'        ),
			array( 'rt_certification', 'rt_certifications'  ),
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
	add_action( 'after_switch_theme', 'rt_grant_cpt_caps' );
}

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

// One-time grant for certification caps on existing installs (v1 already ran).
if ( ! function_exists( 'rt_maybe_grant_cpt_caps_v2' ) ) {
	function rt_maybe_grant_cpt_caps_v2() {
		if ( get_option( 'rt_cpt_caps_v2' ) ) {
			return;
		}
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( array(
				'edit_rt_certifications',
				'edit_others_rt_certifications',
				'edit_published_rt_certifications',
				'edit_private_rt_certifications',
				'create_rt_certifications',
				'publish_rt_certifications',
				'read_private_rt_certifications',
				'delete_rt_certifications',
				'delete_published_rt_certifications',
				'delete_private_rt_certifications',
				'delete_others_rt_certifications',
			) as $cap ) {
				$administrator->add_cap( $cap );
			}
		}
		update_option( 'rt_cpt_caps_v2', true, false );
	}
}
add_action( 'admin_init', 'rt_maybe_grant_cpt_caps_v2' );



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
			<th><label for="project_url"><?php esc_html_e( 'Live URL', 'russteicheira' ); ?></label></th>
			<td><input type="url" id="project_url" name="project_url" value="<?php echo esc_attr( $url ); ?>" class="widefat" placeholder="https://…" /></td>
		</tr>
		<tr>
			<th><label for="project_github"><?php esc_html_e( 'GitHub URL', 'russteicheira' ); ?></label></th>
			<td><input type="url" id="project_github" name="project_github" value="<?php echo esc_attr( $github ); ?>" class="widefat" placeholder="https://github.com/…" /></td>
		</tr>
		<tr>
			<th><label for="project_featured"><?php esc_html_e( 'Featured', 'russteicheira' ); ?></label></th>
			<td>
				<input type="checkbox" id="project_featured" name="project_featured" value="1" <?php checked( $featured, '1' ); ?> />
				<label for="project_featured"><?php esc_html_e( 'Show on homepage', 'russteicheira' ); ?></label>
			</td>
		</tr>
	</table>
	<?php
}

function rt_save_project_meta( $post_id ) {
	$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;
	if ( $is_rest ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
		if ( ! isset( $_POST['rt_project_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['rt_project_nonce'], 'rt_project_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Meta box fields are only present on classic-style saves (incl. block editor meta box iframe).
	if ( ! isset( $_POST['project_url'] ) && ! isset( $_POST['project_github'] ) && ! isset( $_POST['project_featured'] ) ) {
		return;
	}

	$url      = isset( $_POST['project_url'] )      ? esc_url_raw( wp_unslash( $_POST['project_url'] ) )            : '';
	$github   = isset( $_POST['project_github'] )   ? esc_url_raw( wp_unslash( $_POST['project_github'] ) )         : '';
	$featured = isset( $_POST['project_featured'] ) ? sanitize_text_field( wp_unslash( $_POST['project_featured'] ) ) : '';
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
		<label for="capability_icon"><strong><?php esc_html_e( 'Icon (emoji)', 'russteicheira' ); ?></strong></label><br>
		<input type="text" id="capability_icon" name="capability_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			style="width:60px;font-size:1.5em;text-align:center;margin-top:4px;" />
	</p>
	<p class="description"><?php esc_html_e( 'Default: 📄. Title: 40 chars max. Excerpt (description): 50 chars per line, 100 max.', 'russteicheira' ); ?></p>
	<p class="description" style="margin-top:8px;color:#b00;"><strong><?php esc_html_e( 'Limit: 5 published.', 'russteicheira' ); ?></strong> <?php esc_html_e( 'A 6th reverts to Draft. Use Order (Page Attributes) to control sequence.', 'russteicheira' ); ?></p>
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

	$icon = isset( $_POST['capability_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['capability_icon'] ) ) : '';
	if ( ! $icon ) {
		$icon = '📄';
	}
	update_post_meta( $post_id, '_capability_icon', $icon );
}
add_action( 'save_post_capability', 'rt_save_capability_meta' );

function rt_capability_limit_fields( $data, $postarr ) {
	if ( 'capability' !== $data['post_type'] ) {
		return $data;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $data;
	}
	$uid     = get_current_user_id();
	$post_id = isset( $postarr['ID'] ) ? intval( $postarr['ID'] ) : 0;

	// Enforce 5-item publish limit — runs before the row is written, covers all save paths.
	if ( 'publish' === $data['post_status'] ) {
		$published    = new WP_Query( array(
			'post_type'      => 'capability',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		$ids          = array_map( 'intval', $published->posts );
		$others_count = count( array_diff( $ids, array( $post_id ) ) );
		if ( $others_count >= 5 ) {
			$data['post_status'] = 'draft';
			set_transient( 'rt_cap_limit_' . $uid, true, 60 );
		}
	}

	if ( 'publish' === $data['post_status'] && '' === trim( $data['post_title'] ) ) {
		$data['post_status'] = 'draft';
		set_transient( 'rt_cap_empty_' . $uid, true, 60 );
	}

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
add_filter( 'wp_insert_post_data', 'rt_capability_limit_fields', 10, 2 );

function rt_capability_admin_notices() {
	$uid = get_current_user_id();

	if ( get_transient( 'rt_cap_empty_' . $uid ) ) {
		delete_transient( 'rt_cap_empty_' . $uid );
		echo '<div class="notice notice-error is-dismissible"><p><strong>'
			. esc_html__( 'Title required:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'A capability must have a title to be published. This item was saved as a Draft.', 'russteicheira' )
			. '</p></div>';
	}

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
// Note: shares handle + object name with rt_expertise_field_counters below.
// The post_type guards are mutually exclusive, so only one ever runs per page.
function rt_capability_excerpt_counter() {
	$screen = get_current_screen();
	if ( ! $screen || 'capability' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	wp_enqueue_script( 'rt-admin-field-counters', RT_URI . '/js/admin-field-counters.js', array(), RT_VERSION, true );
	wp_localize_script( 'rt-admin-field-counters', 'rtFieldCounters', array(
		'titleMax'    => 40,
		'titleWarn'   => 30,
		'excerptMax'  => 100,
		'excerptWarn' => 50,
		'excerptHint' => '50 chars = one comfortable line',
	) );
}
add_action( 'admin_enqueue_scripts', 'rt_capability_excerpt_counter' );


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
		<label for="expertise_icon"><strong><?php esc_html_e( 'Icon (emoji)', 'russteicheira' ); ?></strong></label><br>
		<input type="text" id="expertise_icon" name="expertise_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			style="width:60px;font-size:1.5em;text-align:center;margin-top:4px;" />
	</p>
	<p class="description"><?php esc_html_e( 'Default: 📄. Title: 32 chars max. Excerpt (description): 42 chars per line, 200 max. Skills panel for tags.', 'russteicheira' ); ?></p>
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

	$icon = isset( $_POST['expertise_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['expertise_icon'] ) ) : '';
	if ( ! $icon ) {
		$icon = '📄';
	}
	update_post_meta( $post_id, '_expertise_icon', $icon );
}
add_action( 'save_post_expertise', 'rt_save_expertise_meta' );


// ── CERTIFICATION META BOXES ──────────────────────────────────
function rt_add_cert_meta_boxes() {
	add_meta_box(
		'rt_cert_details',
		__( 'Certification Details', 'russteicheira' ),
		'rt_cert_meta_box_cb',
		'certification',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'rt_add_cert_meta_boxes' );

function rt_cert_meta_box_cb( $post ) {
	wp_nonce_field( 'rt_cert_meta', 'rt_cert_nonce' );
	$icon    = get_post_meta( $post->ID, '_cert_icon',    true );
	$issuer  = get_post_meta( $post->ID, '_cert_issuer',  true );
	$date    = get_post_meta( $post->ID, '_cert_date',    true );
	$expires = get_post_meta( $post->ID, '_cert_expires', true );
	$cert_id = get_post_meta( $post->ID, '_cert_id',      true );
	$url     = get_post_meta( $post->ID, '_cert_url',     true );
	?>
	<table class="form-table" style="margin-top:0;">
		<tr>
			<th style="width:180px;">
				<label for="cert_icon"><?php esc_html_e( 'Icon (emoji)', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="text" id="cert_icon" name="cert_icon"
					value="<?php echo esc_attr( $icon ); ?>"
					style="width:60px;font-size:1.4rem;text-align:center;" />
				<p class="description"><?php esc_html_e( 'Emoji shown on the card (e.g. 🏅 🎓 🔐 📜). Defaults to 🏅 if blank.', 'russteicheira' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="cert_issuer"><?php esc_html_e( 'Issuing Organization', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="text" id="cert_issuer" name="cert_issuer"
					value="<?php echo esc_attr( $issuer ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g. ISC2, CompTIA, ISACA', 'russteicheira' ); ?>" />
			</td>
		</tr>
		<tr>
			<th>
				<label for="cert_date"><?php esc_html_e( 'Issue Date', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="text" id="cert_date" name="cert_date"
					value="<?php echo esc_attr( $date ); ?>"
					style="width:160px;"
					placeholder="<?php esc_attr_e( 'e.g. 2023 or Jan 2023', 'russteicheira' ); ?>" />
			</td>
		</tr>
		<tr>
			<th>
				<label for="cert_expires"><?php esc_html_e( 'Expiry Date', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="text" id="cert_expires" name="cert_expires"
					value="<?php echo esc_attr( $expires ); ?>"
					style="width:160px;"
					placeholder="<?php esc_attr_e( 'e.g. 2026 or Jan 2026', 'russteicheira' ); ?>" />
				<p class="description"><?php esc_html_e( 'Leave blank if the certification does not expire.', 'russteicheira' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="cert_id"><?php esc_html_e( 'Credential ID', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="text" id="cert_id" name="cert_id"
					value="<?php echo esc_attr( $cert_id ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g. ABC-123456', 'russteicheira' ); ?>" />
				<p class="description"><?php esc_html_e( 'Credential or certificate ID number. Leave blank to hide.', 'russteicheira' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="cert_url"><?php esc_html_e( 'Credential URL', 'russteicheira' ); ?></label>
			</th>
			<td>
				<input type="url" id="cert_url" name="cert_url"
					value="<?php echo esc_attr( $url ); ?>"
					class="large-text"
					placeholder="https://" />
				<p class="description"><?php esc_html_e( 'Link to the digital credential or verification page. Leave blank to hide the "Verify →" link.', 'russteicheira' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

function rt_save_cert_meta( $post_id ) {
	if ( ! isset( $_POST['rt_cert_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['rt_cert_nonce'], 'rt_cert_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = array( 'cert_icon' => '_cert_icon', 'cert_issuer' => '_cert_issuer', 'cert_date' => '_cert_date', 'cert_expires' => '_cert_expires', 'cert_id' => '_cert_id' );
	foreach ( $text_fields as $post_key => $meta_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
		}
	}
	$url = isset( $_POST['cert_url'] ) ? esc_url_raw( wp_unslash( $_POST['cert_url'] ) ) : '';
	update_post_meta( $post_id, '_cert_url', $url );
}
add_action( 'save_post_certification', 'rt_save_cert_meta' );

function rt_certification_limit_fields( $data ) {
	if ( 'certification' !== $data['post_type'] ) {
		return $data;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $data;
	}
	if ( 'publish' === $data['post_status'] && '' === trim( $data['post_title'] ) ) {
		$data['post_status'] = 'draft';
		set_transient( 'rt_cert_empty_' . get_current_user_id(), true, 60 );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'rt_certification_limit_fields' );

function rt_certification_admin_notices() {
	$uid = get_current_user_id();
	if ( get_transient( 'rt_cert_empty_' . $uid ) ) {
		delete_transient( 'rt_cert_empty_' . $uid );
		echo '<div class="notice notice-error is-dismissible"><p><strong>'
			. esc_html__( 'Title required:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'A certification must have a name (title) to be published. This item was saved as a Draft.', 'russteicheira' )
			. '</p></div>';
	}
}
add_action( 'admin_notices', 'rt_certification_admin_notices' );

// Hard-limit title (32) and excerpt (200) before the row hits the database.
function rt_expertise_limit_fields( $data ) {
	if ( 'expertise' !== $data['post_type'] ) {
		return $data;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $data;
	}
	$uid = get_current_user_id();

	if ( 'publish' === $data['post_status'] && '' === trim( $data['post_title'] ) ) {
		$data['post_status'] = 'draft';
		set_transient( 'rt_exp_empty_' . $uid, true, 60 );
	}

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

	if ( get_transient( 'rt_exp_empty_' . $uid ) ) {
		delete_transient( 'rt_exp_empty_' . $uid );
		echo '<div class="notice notice-error is-dismissible"><p><strong>'
			. esc_html__( 'Title required:', 'russteicheira' )
			. '</strong> '
			. esc_html__( 'An expertise item must have a title to be published. This item was saved as a Draft.', 'russteicheira' )
			. '</p></div>';
	}

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
	wp_enqueue_script( 'rt-admin-field-counters', RT_URI . '/js/admin-field-counters.js', array(), RT_VERSION, true );
	wp_localize_script( 'rt-admin-field-counters', 'rtFieldCounters', array(
		'titleMax'    => 32,
		'titleWarn'   => 24,
		'excerptMax'  => 200,
		'excerptWarn' => 42,
		'excerptHint' => '42 chars = one comfortable line',
	) );
}
add_action( 'admin_enqueue_scripts', 'rt_expertise_field_counters' );


// ── CONTACT FORM AJAX ─────────────────────────────────────────
function rt_get_visitor_ip() {
	$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '';
	if ( $cf && filter_var( $cf, FILTER_VALIDATE_IP ) ) {
		return sanitize_text_field( wp_unslash( $cf ) );
	}
	return sanitize_text_field( wp_unslash( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ) );
}

function rt_handle_contact() {
	check_ajax_referer( 'rt_nonce', 'nonce' );

	// Rate limit: 5 submissions per IP per hour.
	$ip_key    = 'rt_contact_rate_' . md5( rt_get_visitor_ip() );
	$attempts = (int) get_transient( $ip_key );
	if ( $attempts >= 5 ) {
		wp_send_json_error( array( 'message' => __( 'Too many messages sent. Please try again later.', 'russteicheira' ) ) );
		return;
	}

	$name    = isset( $_POST['name'] )    ? sanitize_text_field( wp_unslash( $_POST['name'] ) )        : '';
	$email   = isset( $_POST['email'] )   ? sanitize_email( wp_unslash( $_POST['email'] ) )            : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )     : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	// Strip CRLF and angle brackets to prevent header injection via $name/$subject.
	$name    = str_replace( array( "\r", "\n", '<', '>' ), '', $name );
	$subject = str_replace( array( "\r", "\n" ), '', $subject );
	$subject = $subject ? mb_substr( $subject, 0, 200 ) : 'Contact Form Submission';

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

	// Count every attempt regardless of delivery outcome — counting only on success
	// would let a broken SMTP connection bypass the rate limit entirely.
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
	register_taxonomy( 'skill', array( 'expertise', 'post' ), array(
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
		'publicly_queryable'    => true,
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'skill' ),
	) );
	// Explicit association ensures the admin meta box appears regardless of init order.
	register_taxonomy_for_object_type( 'skill',    'expertise' );
	register_taxonomy_for_object_type( 'skill',    'post' );
	register_taxonomy_for_object_type( 'skill',    'project' );
	register_taxonomy_for_object_type( 'post_tag', 'project' );
}
add_action( 'init', 'rt_register_skill_taxonomy' );

// Skill taxonomy archives show blog posts and projects, not expertise CPT entries.
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_tax( 'skill' ) ) {
		$query->set( 'post_type', array( 'post', 'project' ) );
	}
} );


// ── PAGE CACHE FLUSH ──────────────────────────────────────────
// Clear Jetpack Boost and W3TC page caches when Section settings are saved,
// so changes to meta toggles and header text appear immediately on the frontend.
function rt_flush_page_cache() {
	// W3 Total Cache
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	// Jetpack Boost — action-based clear
	do_action( 'jetpack_boost_clear_cache' );
	// Jetpack Boost — filesystem fallback (runs as www-data, so has write access)
	$cache_dir = WP_CONTENT_DIR . '/boost-cache/cache/';
	if ( is_dir( $cache_dir ) ) {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( $file->isFile() && 'html' === $file->getExtension() && 'index.html' !== $file->getFilename() ) {
				@unlink( $file->getPathname() );
			}
		}
	}
}
add_action( 'update_option_rt_sections', 'rt_flush_page_cache' );
add_action( 'add_option_rt_sections',    'rt_flush_page_cache' );


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


