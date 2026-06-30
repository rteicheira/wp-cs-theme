<?php
/**
 * About section — content managed via WP Admin → Sections.
 *
 * Fields: Eyebrow, Heading, Body (HTML), Skills (comma-separated).
 * Falls back to neutral placeholders until saved.
 */

$eyebrow = rt_section_opt( 'about', 'eyebrow', '// about me' );
$heading = rt_section_opt( 'about', 'heading', 'About Me' );

$body_raw = rt_section_opt( 'about', 'body', '' );
$content  = $body_raw ? wpautop( wp_kses_post( $body_raw ) ) : '';

$skills_raw = rt_section_opt( 'about', 'skills', '' );
if ( $skills_raw ) {
	$skill_ids = array_filter( array_map( 'intval', explode( ',', $skills_raw ) ) );
	$skills    = array();
	foreach ( $skill_ids as $id ) {
		$term = get_term( $id, 'skill' );
		if ( $term && ! is_wp_error( $term ) ) {
			$skills[] = $term; // store full object so we can link
		}
	}
} else {
	$skills = array( 'Network Security', 'Risk Assessment', 'PCI DSS', 'Cloud Security', 'Python', 'Automation', 'Compliance', 'Incident Response' );
}
?>

<section class="about" id="about">
	<div class="section-inner">
		<div class="about__grid">

			<div class="about__text">
				<p class="section-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>

				<h2 class="section-title"><?php echo esc_html( $heading ); ?></h2>

				<div class="gold-line" aria-hidden="true"></div>

				<div class="about__content">
					<?php echo $content; ?>
				</div>

				<?php if ( ! empty( $skills ) ) : ?>
					<div class="about__badges" aria-label="<?php esc_attr_e( 'Skills', 'russteicheira' ); ?>">
						<?php foreach ( $skills as $skill ) :
							if ( $skill instanceof WP_Term ) :
								$skill_link = get_term_link( $skill );
							?>
								<?php if ( ! is_wp_error( $skill_link ) ) : ?>
									<a class="badge" href="<?php echo esc_url( $skill_link ); ?>"><?php echo esc_html( $skill->name ); ?></a>
								<?php else : ?>
									<span class="badge"><?php echo esc_html( $skill->name ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span class="badge"><?php echo esc_html( $skill ); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="about__highlight" aria-label="<?php esc_attr_e( 'Core capabilities', 'russteicheira' ); ?>">
				<?php
				$cap_query = new WP_Query( array(
					'post_type'      => 'capability',
					'post_status'    => 'publish',
					'posts_per_page' => 5,
					'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
					'no_found_rows'  => true,
				) );

				if ( $cap_query->have_posts() ) :
					while ( $cap_query->have_posts() ) :
						$cap_query->the_post();
						$icon = get_post_meta( get_the_ID(), '_capability_icon', true );
						$icon = $icon ? $icon : '📄';
						?>
						<div class="highlight-item">
							<div class="highlight-item__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></div>
							<div class="highlight-item__text">
								<strong><?php echo esc_html( get_the_title() ); ?></strong>
								<span><?php echo esc_html( get_the_excerpt() ); ?></span>
							</div>
						</div>
					<?php endwhile;
					wp_reset_postdata();

				else :
					// Fallback until capabilities are created in WP Admin
					$fallback = array(
						array( '🔐', 'Security Architecture',   'Designing and implementing security controls across enterprise environments.' ),
						array( '📋', 'Compliance & Governance',  'PCI DSS, HIPAA, and SOC 2 compliance program management and gap assessments.' ),
						array( '⚙️', 'Security Automation',      'Building automated workflows and tooling to accelerate detection and response.' ),
						array( '🌐', 'Network Security',         'Firewall management, segmentation strategy, and perimeter defense.' ),
					);
					foreach ( $fallback as $item ) : ?>
						<div class="highlight-item">
							<div class="highlight-item__icon" aria-hidden="true"><?php echo esc_html( $item[0] ); ?></div>
							<div class="highlight-item__text">
								<strong><?php echo esc_html( $item[1] ); ?></strong>
								<span><?php echo esc_html( $item[2] ); ?></span>
							</div>
						</div>
					<?php endforeach;
				endif;
				?>
			</div>

		</div>
	</div>
</section>
