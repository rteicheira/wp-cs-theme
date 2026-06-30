<?php get_header(); ?>

<div class="blog-layout">

	<div class="post-list">

		<p class="section-eyebrow"><?php esc_html_e( '// skill', 'russteicheira' ); ?></p>
		<h1 class="section-title">
			<?php echo esc_html( single_term_title( '', false ) ); ?>
		</h1>
		<?php
		$term = get_queried_object();
		if ( $term instanceof WP_Term && $term->description ) : ?>
			<p class="section-sub"><?php echo esc_html( $term->description ); ?></p>
		<?php endif; ?>

		<?php
		$_show_date     = rt_section_opt( 'blog', 'show_date',     '1' );
		$_show_author   = rt_section_opt( 'blog', 'show_author',   '1' );
		$_show_category = rt_section_opt( 'blog', 'show_category', '1' );
		$_show_skills   = rt_section_opt( 'blog', 'show_skills',   '1' );
		?>

		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post();
				$_pt = get_post_type();
			?>

				<?php if ( 'project' === $_pt ) :
					$_live  = get_post_meta( get_the_ID(), '_project_url',    true );
					$_gh    = get_post_meta( get_the_ID(), '_project_github', true );
					$_ptags = get_the_terms( get_the_ID(), 'skill' );
				?>
				<article id="post-<?php the_ID(); ?>" class="project-card">
					<div class="project-card__header">
						<h2 class="project-card__title">
							<a href="<?php echo esc_url( get_permalink() ); ?>">
								<?php echo esc_html( get_the_title() ); ?>
							</a>
						</h2>
					</div>
					<p class="project-card__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?></p>
					<?php if ( $_ptags && ! is_wp_error( $_ptags ) ) : ?>
						<div class="project-card__skills">
							<?php foreach ( $_ptags as $_pt_tag ) :
								$_ptl = get_term_link( $_pt_tag );
							?>
								<?php if ( ! is_wp_error( $_ptl ) ) : ?>
									<a class="card-tag" href="<?php echo esc_url( $_ptl ); ?>"><?php echo esc_html( $_pt_tag->name ); ?></a>
								<?php else : ?>
									<span class="card-tag"><?php echo esc_html( $_pt_tag->name ); ?></span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $_live || $_gh ) : ?>
						<div class="project-card__links">
							<?php if ( $_live ) : ?>
								<a href="<?php echo esc_url( $_live ); ?>" class="project-card__gh" target="_blank" rel="noopener noreferrer">↗ <?php _e( 'Live Site', 'russteicheira' ); ?></a>
							<?php endif; ?>
							<?php if ( $_gh ) : ?>
								<a href="<?php echo esc_url( $_gh ); ?>" class="project-card__gh" target="_blank" rel="noopener noreferrer">🐙 <?php _e( 'GitHub', 'russteicheira' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</article>

				<?php else : ?>
				<article id="post-<?php the_ID(); ?>" class="post-entry">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post-entry__thumb">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_post_thumbnail( 'blog-card', array( 'alt' => '' ) ); ?></a>
						</div>
					<?php endif; ?>
					<div class="post-entry__body">
						<?php if ( $_show_date || $_show_author || $_show_category ) : ?>
						<div class="post-entry__meta">
							<?php $__sep = false; ?>
							<?php if ( $_show_date ) : ?>
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></time>
								<?php $__sep = true; ?>
							<?php endif; ?>
							<?php if ( $_show_author ) : ?>
								<?php if ( $__sep ) echo ' &middot; '; ?>
								<?php echo esc_html( get_the_author() ); ?>
								<?php $__sep = true; ?>
							<?php endif; ?>
							<?php if ( $_show_category ) :
								$cats = get_the_category();
								if ( $cats ) :
									if ( $__sep ) echo ' &middot; ';
									echo esc_html( $cats[0]->name );
								endif;
							endif; ?>
						</div>
						<?php endif; ?>
						<h2 class="post-entry__title">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
						</h2>
						<p class="post-entry__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?></p>
						<?php if ( $_show_skills ) :
							$_skills = get_the_terms( get_the_ID(), 'skill' );
							if ( $_skills && ! is_wp_error( $_skills ) ) : ?>
								<div class="post-entry__skills">
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
						<a href="<?php echo esc_url( get_permalink() ); ?>" class="post-entry__more">
							<?php esc_html_e( 'Read more', 'russteicheira' ); ?> →
						</a>
					</div>
				</article>
				<?php endif; ?>

			<?php endwhile; ?>

			<div class="pagination">
				<?php
				echo paginate_links( array(
					'prev_text' => '← ' . __( 'Newer', 'russteicheira' ),
					'next_text' => __( 'Older', 'russteicheira' ) . ' →',
				) );
				?>
			</div>

		<?php else : ?>
			<p><?php esc_html_e( 'No content found for this skill yet.', 'russteicheira' ); ?></p>
		<?php endif; ?>

	</div>

	<aside class="blog-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Blog Sidebar', 'russteicheira' ); ?>">
		<?php dynamic_sidebar( 'blog-sidebar' ); ?>
	</aside>

</div>

<?php get_footer(); ?>
