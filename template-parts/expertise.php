<?php
/**
 * Core Expertise section.
 *
 * Section header — create once in WP Admin → Pages → Add New:
 *   Slug:    expertise-content
 *   Status:  Draft
 *   Excerpt: eyebrow  (e.g. "// what I do")
 *   Title:   heading  (e.g. "Core Expertise")
 *   Content: sub-description (one sentence)
 *
 * Cards — WP Admin → Expertise → Add New:
 *   Title:   card heading
 *   Excerpt: card description
 *   Skills:  tags (shared taxonomy with About page badges)
 *   Icon:    emoji in the Expertise Details meta box (default 📄)
 *   Order:   Page Attributes → Order controls display sequence
 */

$header = rt_get_section_header( 'expertise-content', array(
	'eyebrow' => '// lorem sit',
	'heading' => 'Lorem Expertum',
	'sub'     => 'A lorem of ipsum dolor sit amet and consectetur adipiscing elit.',
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
					array( '📄', 'Lorem Ipsum Dolor',               'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt labore, dolore magna aliqua, et elit readiness.',                   array( 'Lorem v1.0', 'Amet', 'Sit', 'Ipsum' ) ),
					array( '📄', 'Lorem & Consectetur Management',  'Lorem ipsum, consectetur, sed do eiusmod tempor incididunt ut labore dolore magna aliqua. Ut enim ad minim veniam quis nostrud exercitation ullamco.',        array( 'Ipsum Lorem', 'Amet Dolor', 'Elit Register' ) ),
					array( '📄', 'Lorem Sit & Consecutum',          'Lorem ipsum dolor amet consectetur tasks with adipiscing and labore. Duis aute irure, plus dolor magna aliqua signal.',                                       array( 'Lorem Ipsum', 'Duis', 'Elit', 'Amet Labore' ) ),
					array( '📄', 'Lorem & Consectetur Dolor',       'Lorem ipsum dolor sit amet, consectetur adipiscing, sed do eiusmod tempor. Duis aute irure stacks on magna aliqua et ipsum.',                                 array( 'Lorem', 'Ipsum', 'Dolor', 'Amet Sit' ) ),
					array( '📄', 'Lorem-Ipsum Consecteturs',        'Lorem ipsum dolor sit amet, consectetur elit sed do labore. Ut enim ad minim, from the magna, ex the adipiscing application layer.',                          array( 'Lorem', 'Ipsum', 'Dolor', 'Amet' ) ),
					array( '📄', 'Tempor & Consectetura',           'Lorem ipsum labore consectetur, sed do eiusmod packages that magna aliqua cillum and actually duis amet day-to-day.',                                         array( 'Lorem Ipsum', 'Dolor Amet', 'Elit Duis' ) ),
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
