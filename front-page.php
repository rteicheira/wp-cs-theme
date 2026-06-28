<?php
/**
 * Front Page Template
 *
 * Displays the full homepage: Hero, About, Expertise, Projects, Blog, Contact.
 * Section visibility is controlled via WP Admin → Sections.
 */

get_header();
?>

<?php get_template_part( 'template-parts/hero' ); ?>
<?php get_template_part( 'template-parts/about' ); ?>

<?php if ( rt_section_enabled( 'expertise' ) ) : ?>
	<?php get_template_part( 'template-parts/expertise' ); ?>
<?php endif; ?>

<?php if ( rt_section_enabled( 'portfolio' ) ) : ?>
	<?php get_template_part( 'template-parts/projects' ); ?>
<?php endif; ?>

<?php if ( rt_section_enabled( 'blog' ) ) : ?>
	<?php get_template_part( 'template-parts/blog-preview' ); ?>
<?php endif; ?>

<?php get_template_part( 'template-parts/contact' ); ?>

<?php get_footer(); ?>
