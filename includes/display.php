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
	$ccas = maicca_get_ccas();

	if ( ! $ccas ) {
		return;
	}

	foreach ( $ccas as $type => $type_ccas ) {
		foreach ( $type_ccas as $cca ) {
			if ( apply_filters( 'maicca_hide_cca', false, $cca ) ) {
				continue;
			}

			maicca_do_cca( $type, $cca );
		}
	}
}
