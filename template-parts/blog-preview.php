<?php
/**
 * Blog preview section header.
 *
 * Create once in WP Admin → Pages → Add New:
 *   Slug:    blog-content
 *   Status:  Draft
 *   Excerpt: eyebrow  (e.g. "// writing")
 *   Title:   heading  (e.g. "From the Blog")
 *   Content: sub-description (one sentence)
 */
$blog_header = rt_get_section_header( 'blog-content', array(
	'eyebrow' => '// lorem sit',
	'heading' => 'Lorem the Ipsum',
	'sub'     => 'Lorem on consectetur, dolor, adipiscing elit, sed do eiusmod.',
) );
?>
<section class="blog-preview" id="blog">
	<div class="section-inner">

		<p class="section-eyebrow"><?php echo esc_html( $blog_header['eyebrow'] ); ?></p>
		<h2 class="section-title"><?php echo esc_html( $blog_header['heading'] ); ?></h2>
		<p class="section-sub">
			<?php echo esc_html( $blog_header['sub'] ); ?>
		</p>

		<?php
		$blog_query = new WP_Query( [
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );
		?>

		<?php if ( $blog_query->have_posts() ) : ?>
			<div class="blog-preview__grid">
				<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); ?>
					<article class="blog-card" id="post-<?php the_ID(); ?>">

						<div class="blog-card__top" aria-hidden="true">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'blog-card', [ 'alt' => '' ] ); ?>
							<?php else : ?>
								<span class="blog-card__icon">
									<?php
									$cats = get_the_category();
									$icons = [
										'pci'           => '🔐',
										'compliance'    => '📋',
										'docker'        => '🐋',
										'automation'    => '⚡',
										'linux'         => '🖥️',
										'security'      => '🛡️',
										'homelab'       => '🏠',
									];
									$icon = '📝';
									if ( $cats ) {
										foreach ( $cats as $cat ) {
											$slug = strtolower( $cat->slug );
											foreach ( $icons as $key => $val ) {
												if ( strpos( $slug, $key ) !== false ) {
													$icon = $val;
													break 2;
												}
											}
										}
									}
									echo esc_html( $icon );
									?>
								</span>
							<?php endif; ?>
						</div>

						<div class="blog-card__body">
							<div class="blog-card__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date( 'M j, Y' ) ); ?>
								</time>
								<?php
								$cats = get_the_category();
								if ( $cats ) {
									echo ' &middot; <span class="blog-card__cat">' . esc_html( $cats[0]->name ) . '</span>';
								}
								?>
							</div>
							<h3 class="blog-card__title">
								<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
							</h3>
							<p class="blog-card__excerpt">
								<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?>
							</p>
						</div>

					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>

		<?php else : ?>
			<div class="blog-preview__placeholder">
				<div class="blog-preview__placeholder-icon" aria-hidden="true">📝</div>
				<p><?php _e( 'Blog posts are on their way. Check back soon.', 'russteicheira' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="blog-preview__cta">
			<?php
			$blog_pid = (int) get_option( 'page_for_posts' );
			$blog_url = $blog_pid ? get_permalink( $blog_pid ) : home_url( '/blog/' );
			?>
			<a href="<?php echo esc_url( $blog_url ); ?>"
			   class="btn btn--outline">
				<?php _e( 'All Posts', 'russteicheira' ); ?> →
			</a>
		</div>

	</div>
</section>
