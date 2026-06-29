<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" class="single-post" <?php post_class(); ?>>

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
			?>
		</div>
		<h1 class="single-post__title"><?php echo esc_html( get_the_title() ); ?></h1>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="single-post__thumb">
			<?php the_post_thumbnail( 'full', [ 'alt' => get_the_title() ] ); ?>
		</div>
	<?php endif; ?>

	<div class="post-content">
		<?php the_content(); ?>
	</div>

	<?php
	$tags = get_the_tags();
	if ( $tags ) : ?>
		<div class="post-tags" style="margin-top:2rem; display:flex; gap:.5rem; flex-wrap:wrap;">
			<?php foreach ( $tags as $tag ) : ?>
				<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="badge">
					#<?php echo esc_html( $tag->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

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
