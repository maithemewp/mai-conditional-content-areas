<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'simple_page_ordering_ordered_posts', 'maicca_simple_page_ordering_delete_transients', 10, 2 );
/**
 * Delete all transients after simple page reordering.
 *
 * @param WP_Post $post    The current post being reordered.
 * @param array   $new_pos The post ID => page attributes values.
 *
 * @return void
 */
function maicca_simple_page_ordering_delete_transients( $post, $new_pos ) {
	if ( ! isset( $post->post_type ) || 'mai_template_part' !== $post->post_type ) {
		return;
	}

	maicca_delete_transients( $post->ID );
}
