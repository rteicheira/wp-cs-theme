<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>

	<header class="single-post__header">
		<div class="single-post__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></time>
			<?php
			$cats = get_the_category();
			if ( $cats ) {
				echo ' &middot; ';
				foreach ( $cats as $i => $cat ) {
					echo '<a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
					if ( $i < count( $cats ) - 1 ) echo ', ';
				}
			}
			echo ' &middot; ' . esc_html( get_the_author() );
			echo ' &middot; ' . esc_html( rt_reading_time() );
			?>
		</div>
		<h1 class="single-post__title"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php
		$post_skills = get_the_terms( get_the_ID(), 'skill' );
		if ( $post_skills && ! is_wp_error( $post_skills ) ) : ?>
			<div class="single-post__skills">
				<?php foreach ( $post_skills as $ps ) :
					$ps_link = get_term_link( $ps );
				?>
					<?php if ( ! is_wp_error( $ps_link ) ) : ?>
						<a class="card-tag" href="<?php echo esc_url( $ps_link ); ?>"><?php echo esc_html( $ps->name ); ?></a>
					<?php else : ?>
						<span class="card-tag"><?php echo esc_html( $ps->name ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="single-post__thumb">
			<?php the_post_thumbnail( 'full', [ 'alt' => get_the_title() ] ); ?>
		</div>
	<?php endif; ?>

	<?php $toc = rt_get_toc(); if ( $toc ) : ?>
		<?php echo $toc; ?>
	<?php endif; ?>

	<div class="post-content">
		<?php the_content(); ?>
	</div>

	<?php
	$tags = get_the_tags();
	if ( $tags ) : ?>
		<div class="post-tags">
			<?php foreach ( $tags as $tag ) : ?>
				<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="post-tag">
					#<?php echo esc_html( $tag->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php echo rt_related_posts(); ?>

	<nav class="post-navigation" aria-label="<?php esc_attr_e( 'Post navigation', 'russteicheira' ); ?>" style="margin-top:3rem; display:flex; justify-content:space-between; gap:1rem;">
		<div><?php previous_post_link( '%link', '← ' . __( 'Previous', 'russteicheira' ) ); ?></div>
		<div><?php next_post_link( '%link', __( 'Next', 'russteicheira' ) . ' →' ); ?></div>
	</nav>

	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
	?>

</article>

<?php endwhile; ?>

<?php get_footer(); ?>
