<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_CCA_Block {
	/**
	 * Gets it started.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function __construct() {
		add_action( 'acf/init',                              [ $this, 'register_block' ], 10, 3 );
		add_action( 'acf/init',                              [ $this, 'register_field_group' ], 10, 3 );
		add_filter( 'acf/load_field/key=mai_cca_block_post', [ $this, 'load_ccas' ] );
	}

	/**
	 * Registers blocks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function register_block() {
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}

		acf_register_block_type(
			[
				'name'            => 'mai-cca',
				'title'           => __( 'Mai Custom Content Area', 'mai-custom-content-areas' ),
				'description'     => __( 'Display a custom content area anywhere blocks are allowed.', 'mai-custom-content-areas' ),
				'render_callback' => [ $this, 'do_cca' ],
				'category'        => 'widget',
				'keywords'        => [ 'custom', 'content', 'area' ],
				'icon'            => $this->get_block_icon(),
				'supports'        => [
					'align'           => false,
					'anchor'          => false,
					'mode'            => false,
					'jsx'             => false,
					'customClassName' => false,
				],
			]
		);
	}

	/**
	 * Renders the cca content.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function do_cca( $block, $content = '', $is_preview = false ) {
		$cca = get_field( 'cca' );

		if ( ! $cca ) {
			return;
		}

		if ( 'private' === get_post_status( $cca ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		echo mai_get_post_content( $cca, 'mai_template_part' );
	}

	/**
	 * Registers field groups.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'    => 'mai_cca_block_field_group',
				'title'  => __( 'Mai CCA', 'mai-custom-content-areas' ),
				'fields' => [
					[
						'key'           => 'mai_cca_block_post',
						'label'         => __( 'Content Area', 'mai-custom-content-areas'),
						'name'          => 'cca',
						'type'          => 'select',
						'choices'       => [],
						'allow_null'    => 1,
						'multiple'      => 0,
						'ui'            => 1,
						'ajax'          => 1,
						'return_format' => 'value',
					],
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-cca',
						],
					],
				],
				'active' => true,
			]
		);
	}

	/**
	 * Sets default icon.
	 *
	 * @since TBD
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function load_ccas( $field ) {

		$field['choices'] = [];
		$query            = new WP_Query(
			[
				'post_type'              => 'mai_template_part',
				'post_status'            => [ 'publish', 'private' ],
				'posts_per_page'         => 500,
				'post__not_in'           => mai_get_template_part_ids(),
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->the_post();
				$field['choices'][ get_the_ID() ] = get_the_title();
			endwhile;
		}

		wp_reset_postdata();

		return $field;
	}

	/**
	 * Gets block svg icon.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	function get_block_icon() {
		return '<svg role="img" aria-hidden="true" focusable="false" style="display;block;" width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M504 240h-56.81C439.48 146.76 365.24 72.52 272 64.81V8c0-4.42-3.58-8-8-8h-16c-4.42 0-8 3.58-8 8v56.81C146.76 72.52 72.52 146.76 64.81 240H8c-4.42 0-8 3.58-8 8v16c0 4.42 3.58 8 8 8h56.81c7.71 93.24 81.95 167.48 175.19 175.19V504c0 4.42 3.58 8 8 8h16c4.42 0 8-3.58 8-8v-56.81c93.24-7.71 167.48-81.95 175.19-175.19H504c4.42 0 8-3.58 8-8v-16c0-4.42-3.58-8-8-8zM256 416c-88.22 0-160-71.78-160-160S167.78 96 256 96s160 71.78 160 160-71.78 160-160 160zm0-256c-53.02 0-96 42.98-96 96s42.98 96 96 96 96-42.98 96-96-42.98-96-96-96zm0 160c-35.29 0-64-28.71-64-64s28.71-64 64-64 64 28.71 64 64-28.71 64-64 64z"/></svg>';
	}
}
