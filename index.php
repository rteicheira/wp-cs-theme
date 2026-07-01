<?php get_header(); ?>

<div class="blog-layout">

	<div class="post-list">
		<h1 class="section-title">
			<?php
			if ( is_category() )      single_cat_title( '', true );
			elseif ( is_tag() )       single_tag_title( '', true );
			elseif ( is_author() )    echo esc_html( get_the_author() );
			elseif ( is_search() )    printf( esc_html__( 'Search: %s', 'russteicheira' ), esc_html( get_search_query() ) );
			elseif ( is_archive() )   the_archive_title( '', true );
			else                      esc_html_e( 'Latest Posts', 'russteicheira' );
			?>
		</h1>

		<?php
		$_show_date     = rt_section_opt( 'blog', 'show_date',     '1' );
		$_show_author   = rt_section_opt( 'blog', 'show_author',   '1' );
		$_show_category = rt_section_opt( 'blog', 'show_category', '1' );
		$_show_skills   = rt_section_opt( 'blog', 'show_skills',   '1' );
		?>

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" class="post-entry">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post-entry__thumb">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_post_thumbnail( 'blog-card', [ 'alt' => '' ] ); ?></a>
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
			<?php endwhile; wp_reset_postdata(); ?>

			<div class="pagination">
				<?php
				echo paginate_links( [
					'prev_text' => '← ' . __( 'Newer', 'russteicheira' ),
					'next_text' => __( 'Older', 'russteicheira' ) . ' →',
				] );
				?>
			</div>

		<?php else : ?>
			<p><?php _e( 'No posts found.', 'russteicheira' ); ?></p>
		<?php endif; ?>
	</div>

	<aside class="blog-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Blog Sidebar', 'russteicheira' ); ?>">
		<?php dynamic_sidebar( 'blog-sidebar' ); ?>
	</aside>

</div>

<?php get_footer(); ?>
