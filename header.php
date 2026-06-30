<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content">
	<?php _e( 'Skip to content', 'russteicheira' ); ?>
</a>

<nav class="site-nav" id="site-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'russteicheira' ); ?>">
	<div class="nav-inner">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php
			$site_icon_url = get_site_icon_url( 64 );
			if ( $site_icon_url ) {
				echo '<img src="' . esc_url( $site_icon_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="40" height="40">';
			} else {
				$_parts = explode( ' ', trim( get_bloginfo( 'name' ) ) );
				$_ini   = strtoupper( substr( $_parts[0], 0, 1 ) );
				$_ini  .= isset( $_parts[1] ) ? strtoupper( substr( $_parts[1], 0, 1 ) ) : '';
				echo esc_html( $_ini ) . '<span aria-hidden="true">.</span>';
			}
			?>
		</a>

		<button
			class="nav-toggle"
			id="nav-toggle"
			aria-expanded="false"
			aria-controls="primary-menu"
			aria-label="<?php esc_attr_e( 'Toggle navigation', 'russteicheira' ); ?>"
		>
			<span class="nav-toggle__bar"></span>
			<span class="nav-toggle__bar"></span>
			<span class="nav-toggle__bar"></span>
		</button>

		<?php
		wp_nav_menu( [
			'theme_location' => 'primary',
			'menu_id'        => 'primary-menu',
			'container'      => false,
			'menu_class'     => 'nav-links',
			'fallback_cb'    => 'rt_fallback_nav',
			'items_wrap'     => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
		] );
		?>

	</div><!-- .nav-inner -->
</nav><!-- .site-nav -->

<main id="main-content">
