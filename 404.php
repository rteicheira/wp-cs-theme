<?php get_header(); ?>

<main class="error-404" role="main">
	<div style="text-align:center;">
		<div class="error-404__code" aria-hidden="true">404</div>
		<h1 class="error-404__title"><?php _e( 'Page Not Found', 'russteicheira' ); ?></h1>
		<p class="error-404__desc">
			<?php _e( "That path doesn't resolve. It may have moved, been removed, or never existed.", 'russteicheira' ); ?>
		</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
			← <?php _e( 'Back to Home', 'russteicheira' ); ?>
		</a>
	</div>
</main>

<?php get_footer(); ?>
