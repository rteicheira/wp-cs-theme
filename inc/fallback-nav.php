<?php
/**
 * Fallback nav rendered when no menu is assigned in WP admin.
 * Called by the fallback_cb in wp_nav_menu().
 */
function rt_fallback_nav() {
	echo '<ul id="primary-menu" class="nav-links" role="menubar">';
	echo '<li><a href="' . esc_url( home_url( '/#about' ) )     . '">' . esc_html__( 'About',     'russteicheira' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/#expertise' ) ) . '">' . esc_html__( 'Expertise', 'russteicheira' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/#projects' ) )  . '">' . esc_html__( 'Projects',  'russteicheira' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/#blog' ) )      . '">' . esc_html__( 'Blog',      'russteicheira' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/#contact' ) )   . '">' . esc_html__( 'Get in Touch', 'russteicheira' ) . '</a></li>';
	echo '</ul>';
}
