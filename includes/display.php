<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'get_header', 'mai_do_ccas' );
/**
 * Displays content areas on a single entry.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_ccas() {
	if ( ! is_singular() ) {
		return;
	}

	$post_type = get_post_type();
	$ccas      = maicca_get_ccas( $post_type );

	if ( ! $ccas ) {
		return;
	}

	foreach ( $ccas as $cca ) {
		if ( apply_filters( 'maicca_hide_cca', false, $cca ) ) {
			continue;
		}

		maicca_do_cca( $cca );
	}
}

