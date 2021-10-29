<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// add_action( 'load-post-new.php', 'maicca_create_display_terms' );
// add_action( 'load-post.php', 'maicca_create_display_terms' );
/**
 * Creates default content type terms.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maicca_create_display_terms() {
	$screen = get_current_screen();

	if ( 'mai_template_part' !== $screen->post_type ) {
		return;
	}

	$post_types = maicca_get_post_type_choices();

	if ( ! $post_types ) {
		return;
	}

	foreach ( $post_types as $name => $label ) {
		if ( term_exists( $name, 'mai_cca_display' ) ) {
			continue;
		}

		$data = wp_insert_term( $label, 'mai_cca_display', [ 'slug' => $name ] );
	}
}
