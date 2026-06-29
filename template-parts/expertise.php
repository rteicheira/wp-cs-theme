<?php
/**
 * Core Expertise section.
 *
 * Section header — managed via WP Admin → Sections → Expertise.
 *
 * Cards — WP Admin → Expertise → Add New:
 *   Title:   card heading
 *   Excerpt: card description
 *   Skills:  tags (shared taxonomy with About page badges)
 *   Icon:    emoji in the Expertise Details meta box (default 📄)
 *   Order:   Page Attributes → Order controls display sequence
 */

$header = rt_get_section_header( 'expertise-content', array(
	'eyebrow' => '// what I do',
	'heading' => 'Core Expertise',
	'sub'     => 'A selection of the skills and disciplines I apply day-to-day.',
) );

$expertise_query = new WP_Query( array(
	'post_type'      => 'expertise',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
	'no_found_rows'  => true,
) );
?>

<section class="expertise" id="expertise">
	<div class="section-inner">

		<p class="section-eyebrow"><?php echo esc_html( $header['eyebrow'] ); ?></p>
		<h2 class="section-title section-title--light">
			<?php echo esc_html( $header['heading'] ); ?>
		</h2>
		<p class="section-sub section-sub--light">
			<?php echo esc_html( $header['sub'] ); ?>
		</p>

		<div class="expertise__grid">
			<?php if ( $expertise_query->have_posts() ) :
				while ( $expertise_query->have_posts() ) :
					$expertise_query->the_post();
					$icon  = get_post_meta( get_the_ID(), '_expertise_icon', true );
					$icon  = $icon ? $icon : '📄';
					$tags  = get_the_terms( get_the_ID(), 'skill' );
					?>
					<article class="expertise-card">
						<div class="expertise-card__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></div>
						<h3 class="expertise-card__title"><?php echo esc_html( get_the_title() ); ?></h3>
						<p class="expertise-card__desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
							<div class="expertise-card__tags" aria-label="<?php esc_attr_e( 'Related skills', 'russteicheira' ); ?>">
								<?php foreach ( $tags as $tag ) : ?>
									<span class="card-tag"><?php echo esc_html( $tag->name ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endwhile;
				wp_reset_postdata();

			else :
				// Fallback cards shown until expertise posts are created in WP Admin
				$fallback = array(
					array( '🔐', 'Security Engineering',       'Designing layered security controls, hardening systems, and reducing attack surface across on-prem and cloud environments.',  array( 'Architecture', 'Hardening', 'Zero Trust' ) ),
					array( '📋', 'Compliance & Audit',          'Leading PCI DSS, SOC 2, and HIPAA assessments, managing remediation backlogs, and preparing for external audits.',                 array( 'PCI DSS', 'SOC 2', 'HIPAA' ) ),
					array( '⚙️', 'Automation & Tooling',        'Building Python-based automation for security workflows, log analysis, alerting, and evidence collection.',                       array( 'Python', 'APIs', 'Scripting' ) ),
					array( '🌐', 'Network Security',            'Firewall policy management, network segmentation, IDS/IPS tuning, and traffic analysis.',                                        array( 'Firewalls', 'IDS/IPS', 'Segmentation' ) ),
					array( '☁️', 'Cloud Security',              'Securing AWS and Azure environments — IAM policies, security group hygiene, cloud-native logging, and CSPM tooling.',             array( 'AWS', 'Azure', 'IAM', 'CSPM' ) ),
					array( '🛡️', 'Incident Response',           'Developing IR playbooks, leading tabletop exercises, and managing containment and forensics for security incidents.',              array( 'IR', 'Forensics', 'Playbooks' ) ),
				);
				foreach ( $fallback as $card ) : ?>
					<article class="expertise-card">
						<div class="expertise-card__icon" aria-hidden="true"><?php echo esc_html( $card[0] ); ?></div>
						<h3 class="expertise-card__title"><?php echo esc_html( $card[1] ); ?></h3>
						<p class="expertise-card__desc"><?php echo esc_html( $card[2] ); ?></p>
						<div class="expertise-card__tags" aria-label="<?php esc_attr_e( 'Related skills', 'russteicheira' ); ?>">
							<?php foreach ( $card[3] as $tag ) : ?>
								<span class="card-tag"><?php echo esc_html( $tag ); ?></span>
							<?php endforeach; ?>
						</div>
					</article>
				<?php endforeach;
			endif; ?>
		</div>

	</div>
</section>
