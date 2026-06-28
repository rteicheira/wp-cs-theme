<?php
/**
 * Projects / Portfolio section header.
 *
 * Create once in WP Admin → Pages → Add New:
 *   Slug:    portfolio-content
 *   Status:  Draft
 *   Excerpt: eyebrow  (e.g. "// portfolio")
 *   Title:   heading  (e.g. "Projects & Work")
 *   Content: sub-description (one sentence)
 */
$projects_header = rt_get_section_header( 'portfolio-content', array(
	'eyebrow' => '// lorem ipsum',
	'heading' => 'Lorem Ipsum & Amet',
	'sub'     => 'A lorem of things I\'ve ipsum, consectetur, or adipiscing.',
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
			'orderby' => 'menu_order date',
			'order'   => 'ASC',
		] );

		// Fallback: show any projects if none are marked featured
		if ( ! $projects_query->have_posts() ) {
			$projects_query = new WP_Query( [
				'post_type'      => 'project',
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
			] );
		}
		?>

		<?php if ( $projects_query->have_posts() ) : ?>
			<div class="projects__grid">
				<?php while ( $projects_query->have_posts() ) : $projects_query->the_post(); ?>
					<?php
					$live_url = get_post_meta( get_the_ID(), '_project_url',    true );
					$gh_url   = get_post_meta( get_the_ID(), '_project_github', true );
					$stack    = rt_get_stack_tags();
					$link     = $live_url ?: $gh_url ?: get_permalink();
					?>
					<article class="project-card">
						<div class="project-card__header">
							<h3 class="project-card__title">
								<a href="<?php echo esc_url( $link ); ?>"
								   <?php echo ( $live_url || $gh_url ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
									<?php echo esc_html( get_the_title() ); ?>
								</a>
							</h3>
							<span class="project-card__link" aria-hidden="true">↗</span>
						</div>

						<p class="project-card__desc">
							<?php echo wp_kses_post( get_the_excerpt() ); ?>
						</p>

						<?php if ( $stack ) : ?>
							<div class="project-card__stack" aria-label="<?php esc_attr_e( 'Tech stack', 'russteicheira' ); ?>">
								<?php foreach ( $stack as $term ) : ?>
									<span class="stack-tag"><?php echo esc_html( $term->name ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $gh_url ) : ?>
							<a href="<?php echo esc_url( $gh_url ); ?>"
							   class="project-card__gh"
							   target="_blank"
							   rel="noopener noreferrer"
							   aria-label="<?php esc_attr_e( 'View on GitHub', 'russteicheira' ); ?>">
								🐙 <?php _e( 'GitHub', 'russteicheira' ); ?>
							</a>
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
