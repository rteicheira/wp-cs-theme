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
	<?php esc_html_e( 'Skip to content', 'russteicheira' ); ?>
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

		<div class="nav-right">
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

			<button
				class="search-toggle"
				id="search-toggle"
				aria-expanded="false"
				aria-controls="search-overlay"
				aria-label="<?php esc_attr_e( 'Open search', 'russteicheira' ); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</button>

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
		</div><!-- .nav-right -->

	</div><!-- .nav-inner -->
</nav><!-- .site-nav -->

<div id="search-overlay" class="search-overlay" role="dialog" aria-label="<?php esc_attr_e( 'Search', 'russteicheira' ); ?>" aria-modal="true">
	<button class="search-overlay__close" id="search-overlay-close" aria-label="<?php esc_attr_e( 'Close search', 'russteicheira' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
	</button>
	<form class="search-overlay__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
		<label class="screen-reader-text" for="search-overlay-input"><?php esc_html_e( 'Search', 'russteicheira' ); ?></label>
		<input
			type="search"
			id="search-overlay-input"
			class="search-overlay__input"
			name="s"
			autocomplete="off"
			spellcheck="false"
			placeholder="<?php esc_attr_e( 'Search posts, projects…', 'russteicheira' ); ?>"
			value="<?php echo esc_attr( get_search_query() ); ?>"
		>
		<button type="submit" class="search-overlay__submit" aria-label="<?php esc_attr_e( 'Submit search', 'russteicheira' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
		</button>
	</form>
	<p class="search-overlay__hint" aria-hidden="true"><?php esc_html_e( 'Press Enter to search · Esc to close', 'russteicheira' ); ?></p>
</div>

<main id="main-content">
