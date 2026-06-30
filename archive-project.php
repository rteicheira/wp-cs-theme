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
				$skills   = get_the_terms( get_the_ID(), 'skill' );
				?>
				<article class="project-card">
					<div class="project-card__header">
						<h2 class="project-card__title">
							<a href="<?php echo esc_url( get_permalink() ); ?>">
								<?php echo esc_html( get_the_title() ); ?>
							</a>
						</h2>
					</div>
					<p class="project-card__desc"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
					<?php if ( $skills && ! is_wp_error( $skills ) ) : ?>
						<div class="project-card__skills">
							<?php foreach ( $skills as $skill ) :
								$slink = get_term_link( $skill );
							?>
								<?php if ( ! is_wp_error( $slink ) ) : ?>
									<a class="card-tag" href="<?php echo esc_url( $slink ); ?>"><?php echo esc_html( $skill->name ); ?></a>
								<?php else : ?>
									<span class="card-tag"><?php echo esc_html( $skill->name ); ?></span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $live_url || $gh_url ) : ?>
						<div class="project-card__links">
							<?php if ( $live_url ) : ?>
								<a href="<?php echo esc_url( $live_url ); ?>" class="project-card__gh" target="_blank" rel="noopener noreferrer">↗ <?php _e( 'Live Site', 'russteicheira' ); ?></a>
							<?php endif; ?>
							<?php if ( $gh_url ) : ?>
								<a href="<?php echo esc_url( $gh_url ); ?>" class="project-card__gh" target="_blank" rel="noopener noreferrer">🐙 <?php _e( 'GitHub', 'russteicheira' ); ?></a>
							<?php endif; ?>
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
