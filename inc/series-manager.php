<?php
/**
 * Series Manager admin page — reorder posts within a series.
 * WP Admin → Posts → Manage Series
 *
 * Order is stored as term meta `_rt_series_order` (array of post IDs) on the
 * series term. rt_series_nav() reads this meta to render the ordered list.
 */

defined( 'ABSPATH' ) || exit;


// ── ADMIN MENU ────────────────────────────────────────────────
function rt_series_manager_menu() {
	add_submenu_page(
		'edit.php',
		__( 'Manage Series', 'russteicheira' ),
		__( 'Manage Series', 'russteicheira' ),
		'manage_options',
		'rt-series-manager',
		'rt_series_manager_page'
	);
}
add_action( 'admin_menu', 'rt_series_manager_menu' );


// ── ENQUEUE ───────────────────────────────────────────────────
function rt_series_manager_enqueue( $hook ) {
	if ( 'posts_page_rt-series-manager' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style(
		'rt-admin-series',
		RT_URI . '/css/admin-series.css',
		array(),
		RT_VERSION
	);
	wp_enqueue_script(
		'rt-admin-series',
		RT_URI . '/js/admin-series.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		RT_VERSION,
		true
	);
	wp_localize_script( 'rt-admin-series', 'rtSeriesL10n', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'rt_series_order' ),
		'saving'    => __( 'Saving\xe2\x80\xa6',              'russteicheira' ),
		'saved'     => __( 'Order saved.',                    'russteicheira' ),
		'saveError' => __( 'Save failed. Please try again.',  'russteicheira' ),
		'saveOrder' => __( 'Save Order',                      'russteicheira' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'rt_series_manager_enqueue' );


// ── AJAX: SAVE ORDER ──────────────────────────────────────────
function rt_save_series_order() {
	check_ajax_referer( 'rt_series_order', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'russteicheira' ) ) );
	}

	$series_id = isset( $_POST['series_id'] ) ? absint( $_POST['series_id'] ) : 0;
	$raw_order = isset( $_POST['order'] )     ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';

	if ( ! $series_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid series.', 'russteicheira' ) ) );
	}

	$term = get_term( $series_id, 'series' );
	if ( ! $term || is_wp_error( $term ) ) {
		wp_send_json_error( array( 'message' => __( 'Series not found.', 'russteicheira' ) ) );
	}

	// Build a clean array of positive post IDs; empty string → empty array (clear order).
	$post_ids = array();
	if ( '' !== $raw_order ) {
		$post_ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw_order ) ) ) );
	}

	update_term_meta( $series_id, '_rt_series_order', $post_ids );
	wp_send_json_success( array( 'message' => __( 'Order saved.', 'russteicheira' ) ) );
}
add_action( 'wp_ajax_rt_save_series_order', 'rt_save_series_order' );


// ── PAGE RENDERER ─────────────────────────────────────────────
function rt_series_manager_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'russteicheira' ) );
	}

	$all_series = get_terms( array(
		'taxonomy'   => 'series',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	echo '<div class="wrap rt-series-manager">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Manage Series', 'russteicheira' ) . '</h1>';
	echo '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=series' ) )
		. '" class="page-title-action">' . esc_html__( 'All Series', 'russteicheira' ) . '</a>';
	echo '<hr class="wp-header-end">';
	echo '<p class="description">'
		. esc_html__( 'Drag posts to set their display order within each series. Click Save Order to apply.', 'russteicheira' )
		. '</p>';

	if ( empty( $all_series ) || is_wp_error( $all_series ) ) {
		echo '<p>' . esc_html__( 'No series found. Create series terms and assign them to posts first.', 'russteicheira' ) . '</p>';
		echo '</div>';
		return;
	}

	foreach ( $all_series as $series ) {
		// Admins should see draft/scheduled posts so they can sequence unpublished parts.
		$posts = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'draft', 'future' ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'series',
					'field'    => 'term_id',
					'terms'    => $series->term_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		) );

		if ( empty( $posts ) ) continue;

		// Apply stored order, appending posts added since the last save.
		$post_map = array_column( $posts, null, 'ID' );
		$ids      = rt_apply_series_order( array_keys( $post_map ), $series->term_id );
		$posts    = array_values( array_map( fn( $id ) => $post_map[ $id ], $ids ) );

		$count    = count( $posts );
		$edit_url = admin_url( 'edit-tags.php?action=edit&taxonomy=series&tag_ID=' . $series->term_id );

		echo '<div class="rt-series-card" data-series-id="' . esc_attr( $series->term_id ) . '">';

		echo '<div class="rt-series-card__header">';
		echo '<h2 class="rt-series-card__name">'
			. '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $series->name ) . '</a>'
			. '</h2>';
		echo '<span class="rt-series-card__count">'
			. esc_html( sprintf(
				/* translators: %d: post count */
				_n( '%d post', '%d posts', $count, 'russteicheira' ),
				$count
			) )
			. '</span>';
		echo '</div>';

		echo '<ol class="rt-series-list" data-series-id="' . esc_attr( $series->term_id ) . '">';

		foreach ( $posts as $p ) {
			$status_html = '';
			if ( 'draft' === $p->post_status ) {
				$status_html = '<span class="rt-post-status rt-post-status--draft">'
					. esc_html__( 'Draft', 'russteicheira' ) . '</span>';
			} elseif ( 'future' === $p->post_status ) {
				$status_html = '<span class="rt-post-status rt-post-status--future">'
					. esc_html__( 'Scheduled', 'russteicheira' ) . '</span>';
			}

			echo '<li class="rt-series-item" data-post-id="' . esc_attr( $p->ID ) . '">';
			echo '<span class="rt-series-item__handle" aria-hidden="true">&#10307;</span>';
			echo '<span class="rt-series-item__title">' . esc_html( $p->post_title ) . '</span>';
			echo $status_html; // escaped above
			$edit_link = get_edit_post_link( $p->ID );
			if ( $edit_link ) {
				echo '<a class="rt-series-item__edit" href="' . esc_url( $edit_link )
					. '">' . esc_html__( 'Edit', 'russteicheira' ) . '</a>';
			}
			echo '</li>';
		}

		echo '</ol>';

		echo '<div class="rt-series-card__footer">';
		echo '<button type="button" class="button button-primary rt-save-order">'
			. esc_html__( 'Save Order', 'russteicheira' ) . '</button>';
		echo '<span class="rt-save-status" aria-live="polite"></span>';
		echo '</div>';

		echo '</div>'; // .rt-series-card
	}

	echo '</div>'; // .wrap
}
