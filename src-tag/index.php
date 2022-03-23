<?php
/**
 * Server-side rendering of the `mai/tag-cloud` block.
 *
 * @package WordPress
 */

/**
 * Renders the `mai/tag-cloud` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the tag cloud for selected taxonomy.
 */
function maicca_render_block_core_tag_cloud( $attributes ) {
	$smallest_font_size = $attributes['smallestFontSize'];
	$unit               = ( preg_match( '/^[0-9.]+(?P<unit>[a-z%]+)$/i', $smallest_font_size, $m ) ? $m['unit'] : 'pt' );

	$args      = array(
		'echo'       => false,
		'unit'       => $unit,
		'taxonomy'   => $attributes['taxonomy'],
		'show_count' => $attributes['showTagCounts'],
		'number'     => $attributes['numberOfTags'],
		'smallest'   => floatVal( $attributes['smallestFontSize'] ),
		'largest'    => floatVal( $attributes['largestFontSize'] ),
	);
	$tag_cloud = wp_tag_cloud( $args );

	if ( ! $tag_cloud ) {
		$labels    = get_taxonomy_labels( get_taxonomy( $attributes['taxonomy'] ) );
		$tag_cloud = esc_html(
			sprintf(
				/* translators: %s: taxonomy name */
				__( 'Your site doesn&#8217;t have any %s, so there&#8217;s nothing to display here at the moment.' ),
				strtolower( $labels->name )
			)
		);
	}

	$wrapper_attributes = get_block_wrapper_attributes();

	return sprintf(
		'<p %1$s>%2$s</p>',
		$wrapper_attributes,
		$tag_cloud
	);
}

add_action( 'init', 'maicca_register_block_core_tag_cloud' );
/**
 * Registers the `mai/tag-cloud` block on server.
 */
function maicca_register_block_core_tag_cloud() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'maicca_render_block_core_tag_cloud',
		)
	);
}
