<?php get_header(); ?>

<main class="blog-layout">

	<div class="post-list">
		<h1 class="section-title" style="margin-bottom:2rem;">
			<?php
			if ( is_category() )      single_cat_title( '', true );
			elseif ( is_tag() )       single_tag_title( '', true );
			elseif ( is_author() )    the_author();
			elseif ( is_search() )    printf( esc_html__( 'Search: %s', 'russteicheira' ), get_search_query() );
			elseif ( is_archive() )   the_archive_title( '', true );
			else                      esc_html_e( 'Latest Posts', 'russteicheira' );
			?>
		</h1>

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" class="post-entry">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post-entry__thumb">
							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'blog-card', [ 'alt' => '' ] ); ?></a>
						</div>
					<?php endif; ?>
					<div class="post-entry__body">
						<div class="post-entry__meta">
							<time datetime="<?php echo get_the_date( 'c' ); ?>"><?php echo get_the_date( 'F j, Y' ); ?></time>
							<?php
							$cats = get_the_category();
							if ( $cats ) echo ' &middot; ' . esc_html( $cats[0]->name );
							?>
						</div>
						<h2 class="post-entry__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="post-entry__excerpt"><?php echo wp_trim_words( get_the_excerpt(), 30, '…' ); ?></p>
						<a href="<?php the_permalink(); ?>" class="post-entry__more">
							<?php _e( 'Read more', 'russteicheira' ); ?> →
						</a>
					</div>
				</article>
			<?php endwhile; ?>

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

	<aside class="blog-sidebar" role="complementary" aria-label="<?php _e( 'Blog Sidebar', 'russteicheira' ); ?>">
		<?php dynamic_sidebar( 'blog-sidebar' ); ?>
	</aside>

</main>

<?php get_footer(); ?>
