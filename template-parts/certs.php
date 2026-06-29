<?php
/**
 * Certifications section — content managed via WP Admin → Certifications.
 *
 * Section header (eyebrow/heading/sub) managed via WP Admin → Sections.
 * Each certification: title (cert name), excerpt (optional description),
 * and meta fields: _cert_icon, _cert_issuer, _cert_date, _cert_url.
 * Display order is set via Page Attributes → Order.
 */

$eyebrow = rt_section_opt( 'certs', 'eyebrow', '// credentials' );
$heading = rt_section_opt( 'certs', 'heading', 'Certifications' );
$sub     = rt_section_opt( 'certs', 'sub',     'Professional certifications and industry credentials.' );

$certs_query = new WP_Query( array(
	'post_type'      => 'certification',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	'no_found_rows'  => true,
) );
?>

<section class="certs" id="certs">
	<div class="section-inner">

		<p class="section-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<h2 class="section-title">
			<?php echo esc_html( $heading ); ?>
		</h2>
		<p class="section-sub">
			<?php echo esc_html( $sub ); ?>
		</p>

		<div class="certs__grid">
			<?php if ( $certs_query->have_posts() ) :
				while ( $certs_query->have_posts() ) :
					$certs_query->the_post();
					$icon    = get_post_meta( get_the_ID(), '_cert_icon',    true );
					$icon    = $icon ? $icon : '🏅';
					$issuer  = get_post_meta( get_the_ID(), '_cert_issuer',  true );
					$date    = get_post_meta( get_the_ID(), '_cert_date',    true );
					$expires = get_post_meta( get_the_ID(), '_cert_expires', true );
					$cert_id = get_post_meta( get_the_ID(), '_cert_id',      true );
					$url     = get_post_meta( get_the_ID(), '_cert_url',     true );
					$desc    = get_the_excerpt();
					?>
					<article class="cert-card">
						<div class="cert-card__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></div>
						<h3 class="cert-card__name"><?php echo esc_html( get_the_title() ); ?></h3>
						<?php if ( $issuer ) : ?>
							<span class="cert-card__issuer"><?php echo esc_html( $issuer ); ?></span>
						<?php endif; ?>
						<?php if ( $desc ) : ?>
							<p class="cert-card__desc"><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
						<?php if ( $cert_id ) : ?>
							<p class="cert-card__cert-id">
								<span class="cert-card__cert-id-label"><?php _e( 'ID:', 'russteicheira' ); ?></span>
								<?php echo esc_html( $cert_id ); ?>
							</p>
						<?php endif; ?>
						<?php if ( $date || $expires || $url ) : ?>
							<div class="cert-card__footer">
								<span class="cert-card__dates">
									<?php if ( $date ) : ?>
										<span class="cert-card__date"><?php echo esc_html( $date ); ?></span>
									<?php endif; ?>
									<?php if ( $date && $expires ) : ?>
										<span class="cert-card__date-sep" aria-hidden="true">–</span>
									<?php endif; ?>
									<?php if ( $expires ) : ?>
										<span class="cert-card__expires"><?php echo esc_html( $expires ); ?></span>
									<?php endif; ?>
								</span>
								<?php if ( $url ) : ?>
									<a href="<?php echo esc_url( $url ); ?>" class="cert-card__link"
									   target="_blank" rel="noopener noreferrer">
										<?php _e( 'Verify →', 'russteicheira' ); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endwhile;
				wp_reset_postdata();

			else :
				// Fallback displayed until certifications are created in WP Admin → Certifications
				$fallback = array(
					array( '🏅', 'Lorem Ipsum Certified Professional', 'Ipsum Institute',  '2024' ),
					array( '🎓', 'Dolor Sit Amet Security Analyst',    'Lorem & Co',       '2023' ),
					array( '🔐', 'Consectetur Adipiscing Expert',      'Adipiscing Group', '2023' ),
					array( '📜', 'Sed Do Eiusmod Tempor Associate',    'Tempor Inc',       '2022' ),
					array( '✅', 'Ut Labore Et Dolore Professional',   'Labore Corp',      '2022' ),
					array( '🛡️', 'Quis Nostrud Risk Practitioner',     'Nostrud Ltd',      '2021' ),
				);
				foreach ( $fallback as $cert ) : ?>
					<article class="cert-card">
						<div class="cert-card__icon" aria-hidden="true"><?php echo esc_html( $cert[0] ); ?></div>
						<h3 class="cert-card__name"><?php echo esc_html( $cert[1] ); ?></h3>
						<span class="cert-card__issuer"><?php echo esc_html( $cert[2] ); ?></span>
						<div class="cert-card__footer">
							<span class="cert-card__date"><?php echo esc_html( $cert[3] ); ?></span>
						</div>
					</article>
				<?php endforeach;
			endif; ?>
		</div>

	</div>
</section>
