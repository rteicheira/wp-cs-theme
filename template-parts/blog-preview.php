<?php
/**
 * Blog preview section.
 *
 * Section header — managed via WP Admin → Sections → Blog.
 */
$blog_header = rt_get_section_header( 'blog-content', array(
	'eyebrow' => '// writing',
	'heading' => 'From the Blog',
	'sub'     => 'Thoughts on security, compliance, and automation.',
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
			'no_found_rows'  => true,
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
							<?php
							$_show_date     = rt_section_opt( 'blog', 'show_date',     '1' );
							$_show_author   = rt_section_opt( 'blog', 'show_author',   '1' );
							$_show_category = rt_section_opt( 'blog', 'show_category', '1' );
							$_show_skills   = rt_section_opt( 'blog', 'show_skills',   '1' );
							$_has_meta      = $_show_date || $_show_author || $_show_category;
							?>
							<?php if ( $_has_meta ) : ?>
							<div class="blog-card__meta">
								<?php $__sep = false; ?>
								<?php if ( $_show_date ) : ?>
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></time>
									<?php $__sep = true; ?>
								<?php endif; ?>
								<?php if ( $_show_author ) : ?>
									<?php if ( $__sep ) echo ' &middot; '; ?>
									<span class="blog-card__author"><?php echo esc_html( get_the_author() ); ?></span>
									<?php $__sep = true; ?>
								<?php endif; ?>
								<?php if ( $_show_category ) :
									$cats = get_the_category();
									if ( $cats ) :
										if ( $__sep ) echo ' &middot; ';
										echo '<span class="blog-card__cat">' . esc_html( $cats[0]->name ) . '</span>';
									endif;
								endif; ?>
							</div>
							<?php endif; ?>
							<h3 class="blog-card__title">
								<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
							</h3>
							<p class="blog-card__excerpt">
								<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?>
							</p>
							<?php if ( $_show_skills ) :
								$_skills = get_the_terms( get_the_ID(), 'skill' );
								if ( $_skills && ! is_wp_error( $_skills ) ) : ?>
									<div class="blog-card__skills">
										<?php foreach ( $_skills as $_skill ) :
											$_slink = get_term_link( $_skill );
										?>
											<?php if ( ! is_wp_error( $_slink ) ) : ?>
												<a class="card-tag" href="<?php echo esc_url( $_slink ); ?>"><?php echo esc_html( $_skill->name ); ?></a>
											<?php else : ?>
												<span class="card-tag"><?php echo esc_html( $_skill->name ); ?></span>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							<?php endif; ?>
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
