<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<article id="page-<?php the_ID(); ?>" class="single-post" <?php post_class(); ?>>

	<header class="single-post__header">
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

</article>

<?php endwhile; ?>

<?php get_footer(); ?>
