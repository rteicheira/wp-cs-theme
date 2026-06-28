<section class="hero" id="hero" aria-label="<?php esc_attr_e( 'Introduction', 'russteicheira' ); ?>">

	<div class="hero__bg-grid" aria-hidden="true"></div>

	<div class="hero__inner section-inner">

		<p class="hero__eyebrow">
			<?php echo esc_html( rt_get( 'site_tagline', 'Cybersecurity & Compliance Professional' ) ); ?>
		</p>

		<h1 class="hero__name">
			<?php
			$name_parts = explode( ' ', get_bloginfo( 'name' ), 2 );
			echo esc_html( $name_parts[0] );
			if ( isset( $name_parts[1] ) ) {
				echo '<br /><span class="accent">' . esc_html( $name_parts[1] ) . '</span>';
			}
			?>
		</h1>

		<p class="hero__terminal" id="hero-terminal" aria-live="polite">
			<span class="cursor" aria-hidden="true"></span>
		</p>

		<p class="hero__desc">
			<?php
			$tagline = get_bloginfo( 'description' );
			echo $tagline
				? esc_html( $tagline )
				: esc_html__( 'PCI compliance specialist, automation builder, and self-hosted infrastructure enthusiast. Helping organizations navigate complex security landscapes and build resilient systems.', 'russteicheira' );
			?>
		</p>

		<div class="hero__actions">
			<a href="#projects" class="btn btn--primary">
				<?php _e( 'View My Work', 'russteicheira' ); ?>
				<span aria-hidden="true">↓</span>
			</a>
			<a href="#contact" class="btn btn--secondary">
				<?php _e( "Let's Connect", 'russteicheira' ); ?>
			</a>
		</div>

		<div class="hero__stats" aria-label="<?php esc_attr_e( 'Quick stats', 'russteicheira' ); ?>">
			<div class="stat">
				<div class="stat__num"><?php echo esc_html( rt_get( 'hero_stat1_num', 'PCI DSS' ) ); ?></div>
				<div class="stat__label"><?php echo esc_html( rt_get( 'hero_stat1_label', 'Compliance Focus' ) ); ?></div>
			</div>
			<div class="stat">
				<div class="stat__num"><?php echo esc_html( rt_get( 'hero_stat2_num', '10+' ) ); ?></div>
				<div class="stat__label"><?php echo esc_html( rt_get( 'hero_stat2_label', 'Years in Security' ) ); ?></div>
			</div>
			<div class="stat">
				<div class="stat__num"><?php echo esc_html( rt_get( 'hero_stat3_num', '∞' ) ); ?></div>
				<div class="stat__label"><?php echo esc_html( rt_get( 'hero_stat3_label', 'Scripts Automated' ) ); ?></div>
			</div>
		</div>

	</div><!-- .hero__inner -->
</section>
