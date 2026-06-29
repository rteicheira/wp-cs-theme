<?php
$eyebrow = rt_section_opt( 'contact', 'eyebrow', '// get in touch' );
$heading = rt_section_opt( 'contact', 'heading', 'Get in Touch' );
$subtext = rt_section_opt( 'contact', 'sub',     '' );
$links   = rt_section_opt( 'contact', 'links', array() );
if ( ! is_array( $links ) ) {
	$links = array();
}
?>
<section class="contact" id="contact">
	<div class="section-inner">
		<div class="contact__inner">

			<div class="contact__info">
				<p class="section-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<h2 class="section-title section-title--light">
					<?php echo esc_html( $heading ); ?>
				</h2>
				<div class="gold-line" aria-hidden="true"></div>
				<p class="section-sub section-sub--light">
					<?php echo esc_html( $subtext ); ?>
				</p>

				<div class="contact__links">
					<?php foreach ( $links as $link ) :
						if ( empty( $link['url'] ) ) continue;
						$icon        = ! empty( $link['icon'] )    ? $link['icon']    : '📄';
						$label       = ! empty( $link['label'] )   ? $link['label']   : '';
						$display     = ! empty( $link['display'] ) ? $link['display'] : $link['url'];
						$is_external = (bool) preg_match( '#^https?://#i', $link['url'] );
					?>
					<a href="<?php echo esc_url( $link['url'] ); ?>" class="contact-link"
					   <?php if ( $is_external ) echo 'target="_blank" rel="noopener noreferrer"'; ?>>
						<div class="contact-link__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></div>
						<div>
							<?php if ( $label ) : ?>
							<div class="contact-link__label"><?php echo esc_html( $label ); ?></div>
							<?php endif; ?>
							<div class="contact-link__value"><?php echo esc_html( $display ); ?></div>
						</div>
					</a>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="contact__form-wrap">
				<form class="contact-form" id="contact-form" novalidate aria-label="<?php esc_attr_e( 'Contact form', 'russteicheira' ); ?>">
					<div class="form-group">
						<label for="contact-name"><?php _e( 'Your Name', 'russteicheira' ); ?></label>
						<input type="text" id="contact-name" name="name" placeholder="<?php esc_attr_e( 'Jane Smith', 'russteicheira' ); ?>" required autocomplete="name" />
					</div>

					<div class="form-group">
						<label for="contact-email"><?php _e( 'Email Address', 'russteicheira' ); ?></label>
						<input type="email" id="contact-email" name="email" placeholder="jane@company.com" required autocomplete="email" />
					</div>

					<div class="form-group">
						<label for="contact-subject"><?php _e( 'Subject', 'russteicheira' ); ?></label>
						<input type="text" id="contact-subject" name="subject" placeholder="<?php esc_attr_e( 'PCI compliance consultation', 'russteicheira' ); ?>" required />
					</div>

					<div class="form-group">
						<label for="contact-message"><?php _e( 'Message', 'russteicheira' ); ?></label>
						<textarea id="contact-message" name="message" rows="5" placeholder="<?php esc_attr_e( "Tell me a bit about your project or what you're working on\xe2\x80\xa6", 'russteicheira' ); ?>" required></textarea>
					</div>

					<div class="form-status" id="form-status" role="alert" aria-live="polite"></div>

					<button type="submit" class="btn btn--primary" id="contact-submit">
						<span class="btn__text"><?php _e( 'Send Message', 'russteicheira' ); ?> &rarr;</span>
						<span class="btn__loading" aria-hidden="true" hidden><?php _e( 'Sending&hellip;', 'russteicheira' ); ?></span>
					</button>
				</form>
			</div>

		</div>
	</div>
</section>
