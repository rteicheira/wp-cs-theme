<?php
/**
 * About section — content managed via WP Admin → Sections.
 *
 * Fields: Eyebrow, Heading, Body (HTML), Skills (comma-separated).
 * Falls back to Lorem Ipsum placeholders until saved.
 */

$eyebrow = rt_section_opt( 'about', 'eyebrow', '// lorem me' );
$heading = rt_section_opt( 'about', 'heading', 'Lorem ipsum dolor, ex aliquam honestatis' );

$body_raw = rt_section_opt( 'about', 'body', '' );
if ( $body_raw ) {
	$content = wpautop( wp_kses_post( $body_raw ) );
} else {
	$content = '<p>Lorem ipsum <strong>consectetur adipiscing</strong> elit, sed do eiusmod — ut labore et dolore magna aliqua, enim ad minim veniam quis nostrud exercitation ullamco et consequat.</p>'
	         . '<p>Duis aute irure dolor in <strong>reprehenderit voluptate</strong>, velit esse cillum dolore eu fugiat nulla pariatur, excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit.</p>'
	         . '<p>Ut labore et dolore magnam quaerat voluptatem — nemo enim ipsam laudantium totam aperiam eaque ipsa quae ab illo inventore veritatis.</p>';
}

$skills_raw = rt_section_opt( 'about', 'skills', '' );
if ( $skills_raw ) {
	$skill_ids = array_filter( array_map( 'intval', explode( ',', $skills_raw ) ) );
	$skills    = array();
	foreach ( $skill_ids as $id ) {
		$term = get_term( $id, 'skill' );
		if ( $term && ! is_wp_error( $term ) ) {
			$skills[] = $term->name;
		}
	}
} else {
	$skills = array( 'Labore', 'Consectetur Elit', 'Ipsum', 'Adipiscing', 'Duis', 'Reprehenderit', 'Voluptate', 'Velit' );
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
						<?php foreach ( $skills as $skill ) : ?>
							<span class="badge"><?php echo esc_html( $skill ); ?></span>
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
						array( '📄', 'Lorem Ipsum Amet Labore',   'Lorem ipsum dolor sit amet, consectetur adipiscing elit, incididunt ut labore et dolore magna.' ),
						array( '📄', 'Dolor Sit Amet Elit',       'Ipsum & dolor lorem scripts that labore, consectetur amet, et dolore incididunt magna aliqua.' ),
						array( '📄', 'Lorem & Ipsum Dolor Sit',   'Consectetur adipiscing, dolor ipsum, sed do eiusmod tempor labore et dolore magna aliqua.' ),
						array( '📄', 'Tempor & Consectetura',     'Excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim.' ),
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
