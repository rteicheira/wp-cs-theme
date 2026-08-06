<?php
/**
 * Unit tests for pure webmention functions.
 * No WordPress required — WP globals/functions are stubbed below.
 *
 * Run: php tests/webmention-unit.php
 */

// ── WP STUBS ─────────────────────────────────────────────────────────────────
define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

// Constants only needed at call-time inside closures that never run
// (WP_REST_Server class is never constructed in tests)

function trailingslashit( $s )   { return rtrim( $s, '/' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function home_url( $path = '' )  { return 'https://russteicheira.net' . $path; }
function rest_url( $path = '' )  { return 'https://russteicheira.net/wp-json/' . ltrim( $path, '/' ); }
function esc_url_raw( $u )       { return $u; }
function sanitize_text_field( $s ) { return $s; }
function wp_strip_all_tags( $s )   { return strip_tags( $s ); }
function get_bloginfo( $k = '' )   { return 'russteicheira.net'; }

// Hooks — stubs that accept any args and do nothing
function add_action()  {}
function add_filter()  {}

// WP functions called inside closures that will never run during test
function register_rest_route()            {}
function url_to_postid( $u )              { return 0; }
function get_transient( $k )              { return false; }
function set_transient( $k, $v, $ttl )   {}
function wp_schedule_single_event()       {}
function spawn_cron()                     {}
function get_post_status( $id )           { return 'publish'; }
function wp_remote_get()                  { return array(); }
function wp_remote_head()                 { return array(); }
function wp_remote_post()                 {}
function is_wp_error( $t )               { return false; }
function wp_remote_retrieve_response_code() { return 200; }
function wp_remote_retrieve_body()          { return ''; }
function wp_remote_retrieve_header()        { return ''; }
function get_comments()                  { return array(); }
function wp_update_comment()             {}
function wp_insert_comment()             { return 0; }
function wp_delete_comment()             {}
function update_comment_meta()           {}
function get_comment_meta()              { return ''; }
function wp_is_post_revision( $id )      { return false; }
function wp_is_post_autosave( $id )      { return false; }
function get_permalink( $id = 0 )        { return 'https://russteicheira.net/?p=' . $id; }
function current_time( $t )             { return date( 'Y-m-d H:i:s' ); }
function get_gmt_from_date( $d )        { return $d; }
function is_singular( $t = '' )         { return false; }

// ── LOAD FUNCTIONS ────────────────────────────────────────────────────────────
require __DIR__ . '/../inc/webmention.php';

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

// ── rt_wm_validate_url ────────────────────────────────────────────────────────
echo "\nrt_wm_validate_url\n";
ok( 'accepts https URL',                  rt_wm_validate_url( 'https://example.com/page' ) );
ok( 'accepts http URL',                   rt_wm_validate_url( 'http://example.com/' ) );
ok( 'accepts URL with path and query',    rt_wm_validate_url( 'https://example.com/a?b=c' ) );
ok( 'rejects empty string',             ! rt_wm_validate_url( '' ) );
ok( 'rejects bare domain',              ! rt_wm_validate_url( 'example.com' ) );
ok( 'rejects mailto',                   ! rt_wm_validate_url( 'mailto:user@example.com' ) );
ok( 'rejects ftp',                      ! rt_wm_validate_url( 'ftp://example.com/' ) );
ok( 'rejects non-string integer',       ! rt_wm_validate_url( 42 ) );
ok( 'rejects javascript: URI',          ! rt_wm_validate_url( 'javascript:alert(1)' ) );
ok( 'accepts URL with port',             rt_wm_validate_url( 'https://example.com:8443/path' ) );
ok( 'accepts URL with fragment',         rt_wm_validate_url( 'https://example.com/page#section' ) );
ok( 'rejects null',                     ! rt_wm_validate_url( null ) );
ok( 'rejects array',                    ! rt_wm_validate_url( array() ) );

// ── rt_wm_source_links_to_target ─────────────────────────────────────────────
echo "\nrt_wm_source_links_to_target\n";
$target = 'https://russteicheira.net/my-post/';

ok( 'exact href match',
    rt_wm_source_links_to_target(
        '<html><body><a href="https://russteicheira.net/my-post/">link</a></body></html>',
        $target
    )
);
ok( 'trailing-slash variant matches',
    rt_wm_source_links_to_target(
        '<html><body><a href="https://russteicheira.net/my-post">link</a></body></html>',
        $target
    )
);
ok( 'link in paragraph',
    rt_wm_source_links_to_target(
        '<html><body><p>Some text <a href="https://russteicheira.net/my-post/">here</a>.</p></body></html>',
        $target
    )
);
ok( 'URL in text only (not href) returns false',
    ! rt_wm_source_links_to_target(
        '<html><body><p>Visit https://russteicheira.net/my-post/ for details</p></body></html>',
        $target
    )
);
ok( 'completely different URL returns false',
    ! rt_wm_source_links_to_target(
        '<html><body><a href="https://other.example.com/page">link</a></body></html>',
        $target
    )
);
ok( 'empty HTML returns false',
    ! rt_wm_source_links_to_target( '', $target )
);
ok( 'link in img src returns false',
    ! rt_wm_source_links_to_target(
        '<html><body><img src="https://russteicheira.net/my-post/"></body></html>',
        $target
    )
);
ok( 'multiple links, only second matches',
    rt_wm_source_links_to_target(
        '<html><body><a href="https://other.com/">other</a> <a href="https://russteicheira.net/my-post/">target</a></body></html>',
        $target
    )
);

// ── rt_wm_parse_link_header_rel ──────────────────────────────────────────────
echo "\nrt_wm_parse_link_header_rel\n";
ok( 'parses simple Link header',
    rt_wm_parse_link_header_rel( '<https://endpoint.example/wm>; rel="webmention"', 'webmention' )
    === 'https://endpoint.example/wm'
);
ok( 'parses multi-value Link header',
    rt_wm_parse_link_header_rel(
        '<https://other.example/>; rel="canonical", <https://endpoint.example/wm>; rel="webmention"',
        'webmention'
    ) === 'https://endpoint.example/wm'
);
ok( 'returns null when rel not present',
    rt_wm_parse_link_header_rel( '<https://other.example/>; rel="canonical"', 'webmention' ) === null
);
ok( 'returns null for empty header',
    rt_wm_parse_link_header_rel( '', 'webmention' ) === null
);
ok( 'returns null for null header',
    rt_wm_parse_link_header_rel( null, 'webmention' ) === null
);
ok( 'handles rel without quotes',
    rt_wm_parse_link_header_rel( '<https://ep.example/wm>; rel=webmention', 'webmention' )
    === 'https://ep.example/wm'
);
ok( 'trims whitespace from URL',
    rt_wm_parse_link_header_rel( '< https://ep.example/wm >; rel="webmention"', 'webmention' )
    === 'https://ep.example/wm'
);

// ── rt_wm_absolute_url ────────────────────────────────────────────────────────
echo "\nrt_wm_absolute_url\n";
$base = 'https://example.com/blog/post/';
ok( 'absolute URL is unchanged',
    rt_wm_absolute_url( 'https://endpoint.example/wm', $base ) === 'https://endpoint.example/wm'
);
ok( 'protocol-relative resolved',
    rt_wm_absolute_url( '//endpoint.example/wm', $base ) === 'https://endpoint.example/wm'
);
ok( 'root-relative resolved',
    rt_wm_absolute_url( '/webmention', $base ) === 'https://example.com/webmention'
);
ok( 'relative path resolved against directory',
    rt_wm_absolute_url( 'wm', 'https://example.com/dir/page' ) === 'https://example.com/dir/wm'
);
ok( 'http base preserved for protocol-relative',
    rt_wm_absolute_url( '//endpoint.example/wm', 'http://example.com/page' ) === 'http://endpoint.example/wm'
);

// ── rt_wm_truncate ────────────────────────────────────────────────────────────
echo "\nrt_wm_truncate\n";
ok( 'short text returned unchanged',
    rt_wm_truncate( 'Hello world', 200 ) === 'Hello world'
);
ok( 'long text truncated with ellipsis', ( function () {
    $out = rt_wm_truncate( str_repeat( 'word ', 60 ), 50 );
    return substr( $out, -3 ) === '…'; // mb ellipsis is 3 bytes in UTF-8
} )() );
ok( 'truncation respects word boundary', ( function () {
    $out = rt_wm_truncate( 'The quick brown fox jumps over the lazy dog', 20 );
    return substr( $out, -3 ) === '…' && strpos( $out, ' ' ) !== false;
} )() );
ok( 'exact-length text returned unchanged',
    rt_wm_truncate( 'Hello', 5 ) === 'Hello'
);
ok( 'empty string returned unchanged',
    rt_wm_truncate( '', 50 ) === ''
);

// ── rt_wm_extract_external_links ─────────────────────────────────────────────
echo "\nrt_wm_extract_external_links\n";
ok( 'finds external https link',
    in_array( 'https://external.example/page', rt_wm_extract_external_links(
        '<a href="https://external.example/page">link</a>'
    ), true )
);
ok( 'skips internal links',
    ! in_array( 'https://russteicheira.net/about/', rt_wm_extract_external_links(
        '<a href="https://russteicheira.net/about/">about</a>'
    ), true )
);
ok( 'skips mailto links',
    empty( rt_wm_extract_external_links( '<a href="mailto:user@example.com">email</a>' ) )
);
ok( 'deduplicates repeated external URLs', ( function () {
    $result = rt_wm_extract_external_links(
        '<a href="https://ext.example/p">one</a> <a href="https://ext.example/p">two</a>'
    );
    return count( $result ) === 1;
} )() );
ok( 'returns empty array for no links',
    rt_wm_extract_external_links( '<p>No links here.</p>' ) === array()
);
ok( 'skips ftp links', ( function () {
    $result = rt_wm_extract_external_links( '<a href="ftp://files.example.com/file">ftp</a>' );
    return empty( $result );
} )() );
ok( 'handles multiple different external links', ( function () {
    $result = rt_wm_extract_external_links(
        '<a href="https://one.example/">one</a> <a href="https://two.example/">two</a>'
    );
    return count( $result ) === 2;
} )() );

// ── rt_wm_parse_source: h-entry microformats ─────────────────────────────────
echo "\nrt_wm_parse_source (h-entry)\n";
$hentry_html = <<<HTML
<!DOCTYPE html><html><head><title>Page Title</title></head><body>
<div class="h-entry">
  <h1 class="p-name">My Great Article</h1>
  <p class="p-author h-card"><span class="p-name">Jane Doe</span>
    <a class="u-url" href="https://jane.example/">janedoe.example</a>
    <img class="u-photo" src="https://jane.example/avatar.jpg" alt="">
  </p>
  <time class="dt-published" datetime="2026-07-01T12:00:00Z">July 1, 2026</time>
  <p class="p-summary">A summary of my great article about things.</p>
  <a class="u-in-reply-to" href="https://russteicheira.net/my-post/">Replying to this post</a>
  <div class="e-content"><p>Full content here.</p></div>
</div>
</body></html>
HTML;

$parsed = rt_wm_parse_source( $hentry_html, 'https://jane.example/post/' );
ok( 'extracts h-entry title (p-name)',           $parsed['title'] === 'My Great Article' );
ok( 'extracts author name from h-card',          $parsed['author_name'] === 'Jane Doe' );
ok( 'extracts author URL from h-card u-url',     $parsed['author_url'] === 'https://jane.example/' );
ok( 'extracts avatar from h-card u-photo',       $parsed['author_avatar'] === 'https://jane.example/avatar.jpg' );
ok( 'extracts published datetime',               $parsed['published'] === '2026-07-01T12:00:00Z' );
ok( 'extracts p-summary as excerpt',             strpos( $parsed['excerpt'], 'summary' ) !== false );
ok( 'detects reply type from u-in-reply-to',     $parsed['type'] === 'reply' );

// like
$like_html = '<html><body><div class="h-entry"><a class="u-like-of" href="https://russteicheira.net/post/"></a></div></body></html>';
$p2 = rt_wm_parse_source( $like_html, 'https://mastodon.social/@user/123' );
ok( 'detects like type from u-like-of',          $p2['type'] === 'like' );

// repost
$repost_html = '<html><body><div class="h-entry"><a class="u-repost-of" href="https://russteicheira.net/post/"></a></div></body></html>';
$p3 = rt_wm_parse_source( $repost_html, 'https://mastodon.social/@user/124' );
ok( 'detects repost type from u-repost-of',      $p3['type'] === 'repost' );

// bookmark
$bm_html = '<html><body><div class="h-entry"><a class="u-bookmark-of" href="https://russteicheira.net/post/"></a></div></body></html>';
$p_bm = rt_wm_parse_source( $bm_html, 'https://pinboard.in/u:user/123' );
ok( 'detects bookmark type from u-bookmark-of',  $p_bm['type'] === 'bookmark' );

// ── rt_wm_parse_source: OG fallbacks ─────────────────────────────────────────
echo "\nrt_wm_parse_source (Open Graph fallbacks)\n";
// No <title> so og:title fallback fires (code uses <title> as baseline first)
$og_html = <<<HTML
<!DOCTYPE html><html><head>
  <meta property="og:title" content="OG Title Override">
  <meta property="og:site_name" content="Example Blog">
  <meta property="article:author" content="John Smith">
</head><body><a href="https://russteicheira.net/my-post/">link</a></body></html>
HTML;

$p4 = rt_wm_parse_source( $og_html, 'https://example-blog.com/post/' );
ok( 'uses article:author when no h-card',        $p4['author_name'] === 'John Smith' );
ok( 'uses og:title when no h-entry and no title', $p4['title'] === 'OG Title Override' );

$og_html2 = '<html><head><meta property="og:site_name" content="My Blog"></head><body></body></html>';
$p5 = rt_wm_parse_source( $og_html2, 'https://myblog.com/post/' );
ok( 'falls back to og:site_name for author',     $p5['author_name'] === 'My Blog' );

$bare_html = '<html><head></head><body><a href="https://russteicheira.net/my-post/">link</a></body></html>';
$p6 = rt_wm_parse_source( $bare_html, 'https://bare.example/post/' );
ok( 'falls back to domain name as author',       $p6['author_name'] === 'bare.example' );

// ── rt_wm_is_safe_url ─────────────────────────────────────────────────────────
echo "\nrt_wm_is_safe_url\n";
ok( 'rejects loopback 127.0.0.1',               ! rt_wm_is_safe_url( 'http://127.0.0.1/page' ) );
ok( 'rejects private 192.168.x.x',              ! rt_wm_is_safe_url( 'http://192.168.1.1/page' ) );
ok( 'rejects private 10.x.x.x',                 ! rt_wm_is_safe_url( 'http://10.0.0.1/page' ) );
ok( 'rejects private 172.16.x.x',               ! rt_wm_is_safe_url( 'http://172.16.0.1/page' ) );
ok( 'rejects missing host',                      ! rt_wm_is_safe_url( 'not-a-url' ) );
ok( 'rejects 0.0.0.0',                           ! rt_wm_is_safe_url( 'http://0.0.0.0/page' ) );
ok( 'accepts public IP literal',                  rt_wm_is_safe_url( 'http://8.8.8.8/page' ) );

// ── SUMMARY ──────────────────────────────────────────────────────────────────
$total = $pass + $fail;
echo "\n" . str_repeat( '─', 50 ) . "\n";
if ( $fail === 0 ) {
    echo "\033[32mAll $total tests passed.\033[0m\n";
} else {
    echo "\033[31m$fail of $total tests FAILED:\033[0m\n";
    foreach ( $errors as $e ) echo "  • $e\n";
}
echo str_repeat( '─', 50 ) . "\n\n";
exit( $fail > 0 ? 1 : 0 );
