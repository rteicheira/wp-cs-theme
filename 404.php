<?php get_header(); ?>

<div class="error-404">
	<div style="text-align:center;">
		<div class="error-404__code" aria-hidden="true">404</div>
		<h1 class="error-404__title"><?php esc_html_e( 'Page Not Found', 'russteicheira' ); ?></h1>
		<p class="error-404__desc">
			<?php esc_html_e( "That path doesn't resolve. It may have moved, been removed, or never existed.", 'russteicheira' ); ?>
		</p>
		<form class="error-404__search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<label class="screen-reader-text" for="error-404-search"><?php esc_html_e( 'Search', 'russteicheira' ); ?></label>
			<input
				type="search"
				id="error-404-search"
				name="s"
				class="error-404__search-input"
				placeholder="<?php esc_attr_e( 'Search…', 'russteicheira' ); ?>"
				autocomplete="off"
			>
			<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Search', 'russteicheira' ); ?></button>
		</form>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="error-404__home">
			← <?php esc_html_e( 'Back to Home', 'russteicheira' ); ?>
		</a>
	</div>
</div>

<?php get_footer(); ?>
