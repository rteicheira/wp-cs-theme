<?php get_header(); ?>

<div class="search-page">

	<header class="search-page__header section-inner">
		<p class="section-eyebrow"><?php esc_html_e( '// search results', 'russteicheira' ); ?></p>
		<?php if ( get_search_query() ) : ?>
			<h1 class="section-title">&ldquo;<?php echo esc_html( get_search_query() ); ?>&rdquo;</h1>
		<?php else : ?>
			<h1 class="section-title"><?php esc_html_e( 'Search', 'russteicheira' ); ?></h1>
		<?php endif; ?>
		<?php if ( have_posts() ) : global $wp_query; ?>
			<p class="search-page__count">
				<?php printf(
					esc_html( _n( '%d result', '%d results', $wp_query->found_posts, 'russteicheira' ) ),
					(int) $wp_query->found_posts
				); ?>
			</p>
		<?php endif; ?>
	</header>

	<div class="search-page__results section-inner">

		<?php if ( have_posts() ) : ?>

			<div class="post-list">
				<?php while ( have_posts() ) : the_post();
					$_type       = get_post_type();
					$_type_label = $_type === 'project'
						? esc_html__( 'Project', 'russteicheira' )
						: esc_html__( 'Post', 'russteicheira' );
				?>
				<article id="post-<?php the_ID(); ?>" class="post-entry">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post-entry__thumb">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_post_thumbnail( 'blog-card', [ 'alt' => '' ] ); ?></a>
						</div>
					<?php endif; ?>
					<div class="post-entry__body">
						<div class="post-entry__meta">
							<span class="search-result-type search-result-type--<?php echo esc_attr( $_type ); ?>"><?php echo $_type_label; ?></span>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></time>
						</div>
						<h2 class="post-entry__title">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
						</h2>
						<p class="post-entry__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?></p>
						<?php
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
						<a href="<?php echo esc_url( get_permalink() ); ?>" class="post-entry__more"><?php esc_html_e( 'View', 'russteicheira' ); ?> →</a>
					</div>
				</article>
				<?php endwhile; ?>

				<div class="pagination">
					<?php
					echo paginate_links( [
						'prev_text' => '← ' . __( 'Previous', 'russteicheira' ),
						'next_text' => __( 'Next', 'russteicheira' ) . ' →',
					] );
					?>
				</div>
			</div>

		<?php else : ?>

			<div class="search-no-results">
				<p class="search-no-results__msg">
					<?php printf(
						esc_html__( 'No results found for &ldquo;%s&rdquo;.', 'russteicheira' ),
						esc_html( get_search_query() )
					); ?>
				</p>
				<p class="search-no-results__sub"><?php esc_html_e( 'Try different keywords, or browse:', 'russteicheira' ); ?></p>
				<div class="search-no-results__links">
					<?php
					$posts_page = get_option( 'page_for_posts' );
					$blog_url   = $posts_page ? get_permalink( $posts_page ) : home_url( '/blog/' );
					?>
					<a class="btn" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'All Posts', 'russteicheira' ); ?></a>
					<a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'All Projects', 'russteicheira' ); ?></a>
				</div>
				<form class="search-no-results__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
					<label class="screen-reader-text" for="search-again-input"><?php esc_html_e( 'Search again', 'russteicheira' ); ?></label>
					<input type="search" id="search-again-input" name="s" class="search-no-results__input" placeholder="<?php esc_attr_e( 'Try again…', 'russteicheira' ); ?>">
					<button type="submit" class="btn"><?php esc_html_e( 'Search', 'russteicheira' ); ?></button>
				</form>
			</div>

		<?php endif; ?>

	</div><!-- .search-page__results -->

</div><!-- .search-page -->

<?php get_footer(); ?>
