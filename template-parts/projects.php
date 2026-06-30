<?php
/**
 * Projects / Portfolio section.
 *
 * Section header — managed via WP Admin → Sections → Projects.
 */
$projects_header = rt_get_section_header( 'portfolio-content', array(
	'eyebrow' => '// portfolio',
	'heading' => 'Projects & Work',
	'sub'     => 'Things I\'ve built, automated, or shipped.',
) );
?>
<section class="projects" id="projects">
	<div class="section-inner">

		<p class="section-eyebrow"><?php echo esc_html( $projects_header['eyebrow'] ); ?></p>
		<h2 class="section-title"><?php echo esc_html( $projects_header['heading'] ); ?></h2>
		<p class="section-sub">
			<?php echo esc_html( $projects_header['sub'] ); ?>
		</p>

		<?php
		$projects_query = new WP_Query( [
			'post_type'      => 'project',
			'posts_per_page' => 6,
			'meta_query'     => [
				[
					'key'     => '_project_featured',
					'value'   => '1',
					'compare' => '=',
				],
			],
			'orderby'       => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'no_found_rows' => true,
		] );

		// Fallback: show any projects if none are marked featured
		if ( ! $projects_query->have_posts() ) {
			$projects_query = new WP_Query( [
				'post_type'      => 'project',
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			] );
		}
		?>

		<?php if ( $projects_query->have_posts() ) : ?>
			<div class="projects__grid">
				<?php while ( $projects_query->have_posts() ) : $projects_query->the_post(); ?>
					<?php
					$live_url = get_post_meta( get_the_ID(), '_project_url',    true );
					$gh_url   = get_post_meta( get_the_ID(), '_project_github', true );
					$skills   = get_the_terms( get_the_ID(), 'skill' );
					?>
					<article class="project-card">
						<div class="project-card__header">
							<h3 class="project-card__title">
								<a href="<?php echo esc_url( get_permalink() ); ?>">
									<?php echo esc_html( get_the_title() ); ?>
								</a>
							</h3>
						</div>

						<p class="project-card__desc">
							<?php echo wp_kses_post( get_the_excerpt() ); ?>
						</p>

						<?php if ( $skills && ! is_wp_error( $skills ) ) : ?>
							<div class="project-card__skills" aria-label="<?php esc_attr_e( 'Skills', 'russteicheira' ); ?>">
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
				<?php endwhile; wp_reset_postdata(); ?>
			</div>

		<?php else : ?>
			<div class="projects__empty">
				<p><?php _e( 'Projects coming soon. Check back shortly.', 'russteicheira' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="projects__cta">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>" class="btn btn--outline">
				<?php _e( 'View All Projects', 'russteicheira' ); ?> →
			</a>
		</div>

	</div>
</section>
