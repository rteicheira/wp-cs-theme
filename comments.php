<?php
if ( post_password_required() ) {
	return;
}

$_post_id    = get_the_ID();
$_webm       = rt_get_webmentions( $_post_id );
$_has_webm   = array_sum( array_map( 'count', $_webm ) ) > 0;
$_reg_count  = get_comments( array(
	'post_id' => $_post_id,
	'type'    => 'comment',
	'status'  => 'approve',
	'count'   => true,
) );
?>
<div id="comments" class="comments-area">

	<?php
	// ── WEBMENTIONS ───────────────────────────────────────────
	if ( $_has_webm ) : ?>
	<div class="webmentions">
		<h2 class="webmentions__title"><?php esc_html_e( 'Mentions from the Web', 'russteicheira' ); ?></h2>

		<?php
		// Facepile: likes and reposts as avatars only
		foreach ( array(
			'like'     => array( '❤️', 'Liked by', 'likes' ),
			'repost'   => array( '🔁', 'Reposted by', 'reposts' ),
			'bookmark' => array( '🔖', 'Bookmarked by', 'bookmarks' ),
		) as $type => $meta ) {
			if ( empty( $_webm[ $type ] ) ) continue;
			list( $icon, $label_one, $label_many ) = $meta;
			?>
			<div class="webmentions__facepile">
				<span class="webmentions__type-label" aria-hidden="true"><?php echo $icon; ?></span>
				<span class="screen-reader-text"><?php echo esc_html( count( $_webm[ $type ] ) === 1 ? $label_one : $label_many ) . ':'; ?></span>
				<?php foreach ( $_webm[ $type ] as $wm ) :
					$avatar = get_comment_meta( $wm->comment_ID, 'webmention_avatar', true );
					$name   = $wm->comment_author ?: __( 'Someone', 'russteicheira' );
					$url    = esc_url( $wm->comment_author_url ?: get_comment_meta( $wm->comment_ID, 'webmention_source', true ) );
				?>
					<a class="webmentions__face" href="<?php echo $url; ?>" title="<?php echo esc_attr( $name ); ?>" target="_blank" rel="noopener noreferrer">
						<?php if ( $avatar ) : ?>
							<img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="36" height="36" loading="lazy">
						<?php else : ?>
							<span class="webmentions__face-initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php } ?>

		<?php
		// Replies and mentions as comment-style items
		$_threads = array_merge( $_webm['reply'], $_webm['mention'] );
		if ( $_threads ) : ?>
			<div class="webmentions__threads">
				<h3 class="webmentions__threads-title"><?php
					$n = count( $_threads );
					printf( esc_html( _n( '%d reply', '%d replies & mentions', $n, 'russteicheira' ) ), $n );
				?></h3>
				<?php foreach ( $_threads as $wm ) :
					$source  = get_comment_meta( $wm->comment_ID, 'webmention_source', true );
					$avatar  = get_comment_meta( $wm->comment_ID, 'webmention_avatar', true );
					$title   = get_comment_meta( $wm->comment_ID, 'webmention_title', true );
					$type    = get_comment_meta( $wm->comment_ID, 'webmention_type', true );
					$name    = $wm->comment_author ?: __( 'Someone', 'russteicheira' );
					$excerpt = $wm->comment_content;
					$type_icon = $type === 'reply' ? '💬' : '🔗';
				?>
				<article class="webmention-item" id="webmention-<?php echo absint( $wm->comment_ID ); ?>">
					<div class="webmention-item__meta">
						<?php if ( $avatar ) : ?>
							<img class="webmention-item__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="36" height="36" loading="lazy">
						<?php else : ?>
							<span class="webmention-item__initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></span>
						<?php endif; ?>
						<div class="webmention-item__byline">
							<a class="webmention-item__author" href="<?php echo esc_url( $wm->comment_author_url ?: $source ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $name ); ?></a>
							<span class="webmention-item__type" aria-hidden="true"><?php echo $type_icon; ?></span>
							<a class="webmention-item__source" href="<?php echo esc_url( $source ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $title ?: parse_url( $source, PHP_URL_HOST ) ); ?>
							</a>
							<time class="webmention-item__date" datetime="<?php echo esc_attr( $wm->comment_date_gmt . 'Z' ); ?>">
								<?php echo esc_html( date_i18n( 'M j, Y', strtotime( $wm->comment_date ) ) ); ?>
							</time>
						</div>
					</div>
					<?php if ( $excerpt ) : ?>
						<p class="webmention-item__content"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div><!-- .webmentions -->
	<?php endif; // $_has_webm ?>

	<?php
	// ── REGULAR COMMENTS ────────────────────────────────────────
	if ( $_reg_count > 0 ) : ?>
		<h2 class="comments-title">
			<?php printf(
				esc_html( _n( '%1$s comment', '%1$s comments', $_reg_count, 'russteicheira' ) ),
				number_format_i18n( $_reg_count )
			); ?>
		</h2>

		<ol class="comment-list">
			<?php wp_list_comments( array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 36,
				'type'        => 'comment',
			) ); ?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && ( $_reg_count || $_has_webm ) && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'russteicheira' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>

</div>
