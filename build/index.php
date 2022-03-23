<?php

add_action( 'init', 'maicca_gutenberg_examples_dynamic_block_block_init' );
function maicca_gutenberg_examples_dynamic_block_block_init() {
	register_block_type( MAI_CCA_PLUGIN_DIR . 'build',
		[
			'render_callback' => 'maicca_gutenberg_examples_dynamic_block_render_callback',
		]
	);
}

/**
* This function is called when the block is being rendered on the front end of the site
*
* @param array    $attributes     The array of attributes for this block.
* @param string   $content        Rendered block output. ie. <InnerBlocks.Content />.
* @param WP_Block $block_instance The instance of the WP_Block class that represents the block being rendered.
*/
function maicca_gutenberg_examples_dynamic_block_render_callback( $attributes, $content, $block_instance ) {
	$html = sprintf( '<p %s>', get_block_wrapper_attributes() );

		$html .= 'This better work.';

		if ( isset( $attributes['message'] ) ) {
			/**
			 * The wp_kses_post function is used to ensure any HTML that is not allowed in a post will be escaped.
			 * @see https://developer.wordpress.org/reference/functions/wp_kses_post/
			 * @see https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/#escaping-securing-output
			 */
			$html .= wp_kses_post( $attributes['message'] );
		}
	$html .= '</p>';

	return $html;
}
