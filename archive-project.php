<?php get_header(); ?>

<div class="projects-archive">

	<p class="section-eyebrow"><?php _e( '// portfolio', 'russteicheira' ); ?></p>
	<h1 class="section-title"><?php _e( 'All Projects', 'russteicheira' ); ?></h1>
	<p class="section-sub"><?php _e( 'Everything I\'ve built, automated, or shipped.', 'russteicheira' ); ?></p>

	<?php if ( have_posts() ) : ?>
		<div class="projects-archive__grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$live_url = get_post_meta( get_the_ID(), '_project_url',    true );
				$gh_url   = get_post_meta( get_the_ID(), '_project_github', true );
				$stack    = rt_get_stack_tags();
				$link     = $live_url ?: $gh_url ?: get_permalink();
				?>
				<article class="project-card">
					<div class="project-card__header">
						<h2 class="project-card__title">
							<a href="<?php echo esc_url( $link ); ?>"
							   <?php echo ( $live_url || $gh_url ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php echo esc_html( get_the_title() ); ?>
							</a>
						</h2>
						<span class="project-card__link" aria-hidden="true">↗</span>
					</div>
					<p class="project-card__desc"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
					<?php if ( $stack ) : ?>
						<div class="project-card__stack">
							<?php foreach ( $stack as $term ) : ?>
								<span class="stack-tag"><?php echo esc_html( $term->name ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="pagination" style="margin-top:3rem;">
			<?php echo paginate_links(); ?>
		</div>

	<?php else : ?>
		<p><?php _e( 'No projects yet. Check back soon.', 'russteicheira' ); ?></p>
	<?php endif; ?>

</div>

<?php get_footer(); ?>
