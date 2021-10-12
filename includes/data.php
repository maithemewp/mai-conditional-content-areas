<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'load-post-new.php', 'maicca_create_display_terms' );
add_action( 'load-post.php', 'maicca_create_display_terms' );
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

	$create     = [];
	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );

	foreach ( $post_types as $slug => $post_type ) {
		$create[ $slug ] = $post_type->label;
	}

	if ( ! $create ) {
		return;
	}

	foreach ( $create as $slug => $label ) {
		if ( term_exists( $slug, 'mai_cca_display' ) ) {
			continue;
		}

		$data = wp_insert_term( $label, 'mai_cca_display', [ 'slug' => $slug ] );
	}
}
