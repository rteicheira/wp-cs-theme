<?php
/**
 * Webmention (W3C Recommendation) + IndieWeb support.
 *
 * Receiving : REST POST  /wp-json/webmention/1.0/endpoint
 * Sending   : fires on post publish / content update (async via WP-Cron)
 * Storage   : WordPress comments, comment_type = 'webmention'
 * Display   : helpers consumed by comments.php
 *
 * @link https://www.w3.org/TR/webmention/
 */

defined( 'ABSPATH' ) || exit;

// ── CONSTANTS ─────────────────────────────────────────────────────────────────
define( 'RT_WM_NS',    'webmention/1.0' );
define( 'RT_WM_ROUTE', '/endpoint' );

// ── ADVERTISE ENDPOINT ────────────────────────────────────────────────────────

add_action( 'wp_head', function () {
	if ( is_singular() ) {
		echo '<link rel="webmention" href="' . esc_url( rt_wm_endpoint_url() ) . '">' . "\n";
	}
}, 2 );

add_filter( 'wp_headers', function ( $headers ) {
	if ( is_singular() ) {
		$headers['Link'] = '<' . esc_url_raw( rt_wm_endpoint_url() ) . '>; rel="webmention"';
	}
	return $headers;
} );

function rt_wm_endpoint_url() {
	return rest_url( RT_WM_NS . RT_WM_ROUTE );
}

// ── REST ENDPOINT ─────────────────────────────────────────────────────────────

add_action( 'rest_api_init', function () {
	register_rest_route( RT_WM_NS, RT_WM_ROUTE, array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'rt_wm_receive',
		'permission_callback' => '__return_true',
		'args'                => array(
			'source' => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => 'rt_wm_validate_url',
				'sanitize_callback' => 'esc_url_raw',
			),
			'target' => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => 'rt_wm_validate_url',
				'sanitize_callback' => 'esc_url_raw',
			),
		),
	) );
} );

/**
 * Validate that a value is an absolute http(s) URL.
 */
function rt_wm_validate_url( $value ) {
	if ( ! is_string( $value ) ) return false;
	if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) return false;
	return in_array( strtolower( (string) parse_url( $value, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true );
}

/**
 * REST callback — validates the request and queues async processing.
 * Returns 202 Accepted immediately per the spec.
 */
function rt_wm_receive( WP_REST_Request $request ) {
	$source = $request->get_param( 'source' );
	$target = $request->get_param( 'target' );

	// target must be on this site
	$site_host   = strtolower( (string) parse_url( home_url(), PHP_URL_HOST ) );
	$target_host = strtolower( (string) parse_url( $target,   PHP_URL_HOST ) );
	if ( $target_host !== $site_host ) {
		return new WP_Error( 'invalid_target', 'Target URL is not on this site.', array( 'status' => 400 ) );
	}

	// source and target must differ
	if ( untrailingslashit( $source ) === untrailingslashit( $target ) ) {
		return new WP_Error( 'invalid_source', 'Source and target must be different.', array( 'status' => 400 ) );
	}

	// target must resolve to a real published post
	$post_id = rt_wm_url_to_post_id( $target );
	if ( ! $post_id ) {
		return new WP_Error( 'target_not_found', 'Target URL does not resolve to a post on this site.', array( 'status' => 400 ) );
	}

	// rate-limit: 10 webmentions per source domain per hour
	$rate_key = 'rt_wm_rate_' . md5( (string) parse_url( $source, PHP_URL_HOST ) );
	$attempts = (int) get_transient( $rate_key );
	if ( $attempts >= 10 ) {
		return new WP_Error( 'rate_limited', 'Too many webmentions from this domain.', array( 'status' => 429 ) );
	}
	set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

	// queue async processing
	wp_schedule_single_event( time(), 'rt_wm_process_event', array(
		array(
			'source'  => $source,
			'target'  => $target,
			'post_id' => $post_id,
		),
	) );
	spawn_cron();

	return new WP_REST_Response( 'Webmention received.', 202 );
}

// ── URL → POST ID ─────────────────────────────────────────────────────────────

/**
 * Resolve a target URL to a local post ID.
 * Strips query string / fragment; tries both trailing-slash variants.
 */
function rt_wm_url_to_post_id( $url ) {
	$clean = strtok( $url, '?' );
	$clean = strtok( $clean, '#' );

	$id = url_to_postid( $clean );
	if ( $id ) return $id;

	$toggled = trailingslashit( $clean ) === $clean
		? untrailingslashit( $clean )
		: trailingslashit( $clean );

	return url_to_postid( $toggled ) ?: 0;
}

// ── ASYNC PROCESSING ──────────────────────────────────────────────────────────

add_action( 'rt_wm_process_event', 'rt_wm_process' );

/**
 * Process an incoming webmention (runs via WP-Cron).
 * Fetches source, verifies backlink, parses metadata, upserts comment.
 */
function rt_wm_process( array $args ) {
	$source  = $args['source'];
	$target  = $args['target'];
	$post_id = (int) $args['post_id'];

	if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) return;

	// SSRF: reject private/loopback IPs
	if ( ! rt_wm_is_safe_url( $source ) ) {
		rt_wm_delete_mention( $source, $post_id );
		return;
	}

	$response = wp_remote_get( $source, array(
		'timeout'    => 10,
		'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . ' webmention (russteicheira.net)',
		'sslverify'  => true,
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		rt_wm_delete_mention( $source, $post_id );
		return;
	}

	// cap response body at 1 MB to prevent memory exhaustion
	$body = substr( wp_remote_retrieve_body( $response ), 0, 1048576 );

	if ( ! rt_wm_source_links_to_target( $body, $target ) ) {
		// Link was removed — delete any stored mention
		rt_wm_delete_mention( $source, $post_id );
		return;
	}

	$parsed = rt_wm_parse_source( $body, $source );
	rt_wm_upsert_mention( $post_id, $source, $target, $parsed );
}

// ── SSRF GUARD ────────────────────────────────────────────────────────────────

/**
 * Return true only when the URL's host resolves to a public, routable IPv4 address.
 */
function rt_wm_is_safe_url( $url ) {
	$host = (string) parse_url( $url, PHP_URL_HOST );
	if ( ! $host ) return false;

	// strip port suffix (e.g. example.com:8080)
	$host = preg_replace( '/:\d+$/', '', $host );

	$ip = gethostbyname( $host );
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) return false;

	return (bool) filter_var( $ip, FILTER_VALIDATE_IP,
		FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	);
}

// ── LINK VERIFICATION ─────────────────────────────────────────────────────────

/**
 * Return true if $html contains an <a href> that matches $target.
 * Does a cheap strpos short-circuit before full DOM parse.
 */
function rt_wm_source_links_to_target( $html, $target ) {
	$norm = untrailingslashit( $target );

	if ( strpos( $html, $norm ) === false && strpos( $html, trailingslashit( $target ) ) === false ) {
		return false;
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();

	foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
		if ( untrailingslashit( $a->getAttribute( 'href' ) ) === $norm ) {
			return true;
		}
	}
	return false;
}

// ── MICROFORMAT PARSER ────────────────────────────────────────────────────────

/**
 * Parse h-entry / h-card microformats and Open Graph fallbacks from source HTML.
 *
 * @return array {
 *   string author_name, string author_url, string author_avatar,
 *   string title, string excerpt, string type, string published
 * }
 */
function rt_wm_parse_source( $html, $source_url ) {
	$result = array(
		'author_name'   => '',
		'author_url'    => $source_url,
		'author_avatar' => '',
		'title'         => '',
		'excerpt'       => '',
		'type'          => 'mention',
		'published'     => '',
	);

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xp = new DOMXPath( $dom );

	// page <title> as baseline
	$t = $dom->getElementsByTagName( 'title' );
	if ( $t->length ) $result['title'] = trim( $t->item(0)->textContent );

	// h-entry
	$entry = rt_wm_xp_class( $xp, 'h-entry' );
	if ( $entry ) {
		$p_name = rt_wm_xp_class( $xp, 'p-name', $entry );
		if ( $p_name ) $result['title'] = trim( $p_name->textContent );

		$p_sum = rt_wm_xp_class( $xp, 'p-summary', $entry );
		if ( $p_sum ) $result['excerpt'] = rt_wm_truncate( trim( $p_sum->textContent ), 200 );

		$dt = rt_wm_xp_class( $xp, 'dt-published', $entry );
		if ( $dt ) {
			$result['published'] = $dt->getAttribute( 'datetime' ) ?: trim( $dt->textContent );
		}

		// webmention type via microformat property classes
		foreach ( array(
			'u-like-of'     => 'like',
			'u-repost-of'   => 'repost',
			'u-in-reply-to' => 'reply',
			'u-bookmark-of' => 'bookmark',
		) as $class => $type ) {
			if ( rt_wm_xp_class( $xp, $class, $entry ) ) {
				$result['type'] = $type;
				break;
			}
		}

		// h-card (try inside entry first, then page-level)
		$card = rt_wm_xp_class( $xp, 'h-card', $entry ) ?: rt_wm_xp_class( $xp, 'h-card' );
		if ( $card ) {
			$fn = rt_wm_xp_class( $xp, 'p-name', $card ) ?: rt_wm_xp_class( $xp, 'fn', $card );
			if ( $fn ) $result['author_name'] = trim( $fn->textContent );

			$u_url = rt_wm_xp_class( $xp, 'u-url', $card );
			if ( $u_url ) $result['author_url'] = $u_url->getAttribute( 'href' ) ?: $source_url;

			$photo = rt_wm_xp_class( $xp, 'u-photo', $card );
			if ( $photo ) {
				$result['author_avatar'] = $photo->getAttribute( 'src' ) ?: $photo->getAttribute( 'href' );
			}
		}
	}

	// Open Graph fallbacks
	if ( ! $result['author_name'] ) {
		foreach ( array( 'article:author', 'og:site_name' ) as $prop ) {
			$n = $xp->query( '//meta[@property="' . $prop . '"]/@content' );
			if ( $n && $n->length ) { $result['author_name'] = $n->item(0)->nodeValue; break; }
		}
	}
	if ( ! $result['title'] ) {
		$n = $xp->query( '//meta[@property="og:title"]/@content' );
		if ( $n && $n->length ) $result['title'] = $n->item(0)->nodeValue;
	}

	// final fallback: domain name
	if ( ! $result['author_name'] ) {
		$result['author_name'] = (string) parse_url( $source_url, PHP_URL_HOST );
	}

	return $result;
}

/**
 * XPath helper: first element with a given CSS class, optionally scoped to a context node.
 */
function rt_wm_xp_class( DOMXPath $xp, $class, DOMNode $ctx = null ) {
	$q = './/*[contains(concat(" ",normalize-space(@class)," ")," ' . $class . ' ")]';
	$r = $ctx ? $xp->query( $q, $ctx ) : $xp->query( '/' . $q );
	return ( $r && $r->length ) ? $r->item(0) : null;
}

/**
 * Truncate text to $max characters, breaking on a word boundary.
 */
function rt_wm_truncate( $text, $max ) {
	if ( mb_strlen( $text ) <= $max ) return $text;
	$cut = mb_substr( $text, 0, $max );
	$pos = mb_strrpos( $cut, ' ' );
	return rtrim( $pos ? mb_substr( $cut, 0, $pos ) : $cut ) . '…';
}

// ── UPSERT / DELETE ───────────────────────────────────────────────────────────

function rt_wm_upsert_mention( $post_id, $source, $target, array $parsed ) {
	$existing = get_comments( array(
		'post_id'    => $post_id,
		'type'       => 'webmention',
		'meta_key'   => 'webmention_source',
		'meta_value' => $source,
		'number'     => 1,
		'status'     => 'any',
		'fields'     => 'ids',
	) );

	$date = ( ! empty( $parsed['published'] ) && strtotime( $parsed['published'] ) )
		? date( 'Y-m-d H:i:s', strtotime( $parsed['published'] ) )
		: current_time( 'mysql' );

	$data = array(
		'comment_post_ID'      => $post_id,
		'comment_type'         => 'webmention',
		'comment_author'       => wp_strip_all_tags( $parsed['author_name'] ),
		'comment_author_url'   => esc_url_raw( $parsed['author_url'] ),
		'comment_author_email' => '',
		'comment_content'      => wp_strip_all_tags( $parsed['excerpt'] ?: $parsed['title'] ),
		'comment_date'         => $date,
		'comment_date_gmt'     => get_gmt_from_date( $date ),
		'comment_approved'     => 1,
		'comment_agent'        => 'Webmention',
	);

	if ( $existing ) {
		$data['comment_ID'] = (int) $existing[0];
		wp_update_comment( $data );
		$cid = (int) $existing[0];
	} else {
		$cid = wp_insert_comment( $data );
	}

	if ( $cid ) {
		update_comment_meta( $cid, 'webmention_source', $source );
		update_comment_meta( $cid, 'webmention_target', $target );
		update_comment_meta( $cid, 'webmention_type',   $parsed['type'] );
		update_comment_meta( $cid, 'webmention_avatar', esc_url_raw( $parsed['author_avatar'] ) );
		update_comment_meta( $cid, 'webmention_title',  sanitize_text_field( $parsed['title'] ) );
	}
}

function rt_wm_delete_mention( $source, $post_id ) {
	$existing = get_comments( array(
		'post_id'    => $post_id,
		'type'       => 'webmention',
		'meta_key'   => 'webmention_source',
		'meta_value' => $source,
		'number'     => 5,
		'status'     => 'any',
		'fields'     => 'ids',
	) );
	foreach ( $existing as $id ) {
		wp_delete_comment( (int) $id, true );
	}
}

// ── SENDING WEBMENTIONS ───────────────────────────────────────────────────────

add_action( 'publish_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
	rt_wm_queue_sends( $post_id, $post->post_content );
}, 10, 2 );

add_action( 'post_updated', function ( $post_id, $post_after, $post_before ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( $post_after->post_status !== 'publish' ) return;
	if ( $post_after->post_content === $post_before->post_content ) return;
	rt_wm_queue_sends( $post_id, $post_after->post_content );
}, 10, 3 );

function rt_wm_queue_sends( $post_id, $content ) {
	$source = get_permalink( $post_id );
	$urls   = rt_wm_extract_external_links( $content );
	foreach ( $urls as $url ) {
		wp_schedule_single_event( time(), 'rt_wm_send_event', array( array(
			'source' => $source,
			'target' => $url,
		) ) );
	}
	if ( $urls ) spawn_cron();
}

add_action( 'rt_wm_send_event', function ( array $args ) {
	$ep = rt_wm_discover_endpoint( $args['target'] );
	if ( $ep ) {
		wp_remote_post( $ep, array(
			'timeout' => 10,
			'body'    => array( 'source' => $args['source'], 'target' => $args['target'] ),
		) );
	}
} );

/**
 * Extract unique external http(s) <a href> URLs from post HTML.
 */
function rt_wm_extract_external_links( $content ) {
	if ( ! preg_match_all( '/<a\b[^>]*\bhref=["\']([^"\']+)["\'][^>]*>/i', $content, $m ) ) {
		return array();
	}
	$site = strtolower( (string) parse_url( home_url(), PHP_URL_HOST ) );
	$out  = array();
	foreach ( $m[1] as $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) continue;
		if ( ! in_array( strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) continue;
		if ( strtolower( (string) parse_url( $url, PHP_URL_HOST ) ) === $site ) continue;
		$out[] = $url;
	}
	return array_values( array_unique( $out ) );
}

/**
 * Discover a page's Webmention endpoint via HTTP Link header then HTML.
 * Returns the absolute endpoint URL, or null if none found.
 */
function rt_wm_discover_endpoint( $url ) {
	$head = wp_remote_head( $url, array( 'timeout' => 5, 'redirection' => 3 ) );
	if ( ! is_wp_error( $head ) ) {
		$ep = rt_wm_parse_link_header_rel( wp_remote_retrieve_header( $head, 'link' ), 'webmention' );
		if ( $ep ) return rt_wm_absolute_url( $ep, $url );
	}

	$get = wp_remote_get( $url, array( 'timeout' => 8, 'redirection' => 3 ) );
	if ( is_wp_error( $get ) ) return null;

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( wp_remote_retrieve_body( $get ) );
	libxml_clear_errors();

	foreach ( array( 'link', 'a' ) as $tag ) {
		foreach ( $dom->getElementsByTagName( $tag ) as $el ) {
			$href = $el->getAttribute( 'href' );
			if ( $href && strpos( $el->getAttribute( 'rel' ), 'webmention' ) !== false ) {
				return rt_wm_absolute_url( $href, $url );
			}
		}
	}
	return null;
}

/**
 * Parse an HTTP Link header value for a given rel type.
 * Handles multi-value headers (comma-separated).
 */
function rt_wm_parse_link_header_rel( $header, $rel ) {
	if ( ! $header ) return null;
	foreach ( explode( ',', $header ) as $part ) {
		if ( preg_match( '/<([^>]+)>/', $part, $um )
			&& preg_match( '/\brel=["\']?([^"\';\s]+)["\']?/', $part, $rm )
			&& strpos( $rm[1], $rel ) !== false ) {
			return trim( $um[1] );
		}
	}
	return null;
}

/**
 * Resolve a possibly-relative $href against an absolute $base URL.
 */
function rt_wm_absolute_url( $href, $base ) {
	if ( filter_var( $href, FILTER_VALIDATE_URL ) ) return $href;
	$p = parse_url( $base );
	if ( strpos( $href, '//' ) === 0 ) return $p['scheme'] . ':' . $href;
	if ( strpos( $href, '/' )  === 0 ) return $p['scheme'] . '://' . $p['host'] . $href;
	$dir = isset( $p['path'] ) ? rtrim( dirname( $p['path'] ), '/' ) . '/' : '/';
	return $p['scheme'] . '://' . $p['host'] . $dir . ltrim( $href, './' );
}

// ── MICROFORMATS: h-entry on single posts ─────────────────────────────────────

add_filter( 'post_class', function ( $classes ) {
	if ( is_singular( 'post' ) ) $classes[] = 'h-entry';
	return $classes;
} );

// ── DISPLAY HELPER ────────────────────────────────────────────────────────────

/**
 * Return approved webmentions for a post, grouped by type.
 *
 * @param  int   $post_id
 * @return array{ like: WP_Comment[], repost: WP_Comment[], bookmark: WP_Comment[], reply: WP_Comment[], mention: WP_Comment[] }
 */
function rt_get_webmentions( $post_id ) {
	$all = get_comments( array(
		'post_id' => $post_id,
		'type'    => 'webmention',
		'status'  => 'approve',
		'orderby' => 'comment_date',
		'order'   => 'ASC',
		'number'  => 200,
	) );

	$groups = array( 'like' => array(), 'repost' => array(), 'bookmark' => array(), 'reply' => array(), 'mention' => array() );
	foreach ( $all as $wm ) {
		$t = get_comment_meta( $wm->comment_ID, 'webmention_type', true ) ?: 'mention';
		if ( ! array_key_exists( $t, $groups ) ) $t = 'mention';
		$groups[ $t ][] = $wm;
	}
	return $groups;
}
