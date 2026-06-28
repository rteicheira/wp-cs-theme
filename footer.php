</div><!-- #main-content -->

<footer class="site-footer" role="contentinfo">
	<div class="footer-inner">

		<div class="footer-brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php
				$site_icon_url = get_site_icon_url( 64 );
				if ( $site_icon_url ) {
					echo '<img src="' . esc_url( $site_icon_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="40" height="40">';
				} else {
					echo 'RT<span aria-hidden="true">.</span>';
				}
				?>
			</a>
			<p class="footer-tagline">
				<?php echo esc_html( rt_get( 'site_tagline', 'Cybersecurity & Compliance Professional' ) ); ?>
			</p>
		</div>

		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer Navigation', 'russteicheira' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'footer-links',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</nav>

		<div class="footer-social">
			<?php
			$footer_links = rt_section_opt( 'contact', 'links', array() );
			if ( is_array( $footer_links ) ) :
				foreach ( $footer_links as $fl ) :
					if ( empty( $fl['url'] ) ) continue;
					$icon        = ! empty( $fl['icon'] )  ? $fl['icon']  : '📄';
					$aria        = ! empty( $fl['label'] ) ? $fl['label'] : $icon;
					$is_external = (bool) preg_match( '#^https?://#i', $fl['url'] );
					?>
					<a href="<?php echo esc_url( $fl['url'] ); ?>"
					   <?php if ( $is_external ) echo 'target="_blank" rel="noopener noreferrer"'; ?>
					   aria-label="<?php echo esc_attr( $aria ); ?>">
						<span aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
					</a>
				<?php endforeach;
			endif; ?>
		</div>

	</div><!-- .footer-inner -->

	<?php $legal = wp_kses( rt_get( 'footer_legal_links', '' ), rt_legal_links_allowed_html() ); if ( $legal ) : ?>
	<div class="footer-legal">
		<?php echo $legal; ?>
	</div>
	<?php endif; ?>

	<div class="footer-bottom">
		<p>
			<span class="footer-copy">
				&copy; <?php echo date( 'Y' ); ?>
				<?php
				$copyright_name = rt_get( 'footer_copyright_name', '' );
				echo esc_html( $copyright_name ?: get_bloginfo( 'name' ) );
				?>.
			</span>
			<?php $credit = rt_get( 'footer_credit', '' ); if ( $credit ) : ?>
			<span class="footer-built">
				<?php echo esc_html( $credit ); ?>
			</span>
			<?php endif; ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
