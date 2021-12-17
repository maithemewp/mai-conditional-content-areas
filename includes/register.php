<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_filter( 'display_post_states', 'maicca_content_areas_post_state', 10, 2 );
/**
 * Display active content areas.
 *
 * @since 2.0.0
 *
 * @param array   $states Array of post states.
 * @param WP_Post $post   Post object.
 *
 * @return array
 */
function maicca_content_areas_post_state( $states, $post ) {
	if ( 'mai_template_part' !== $post->post_type ) {
		return $states;
	}

	if ( ! maicca_is_custom_content_area( $post->ID ) ) {
		return $states;
	}

	if ( ! ( 'publish' === $post->post_status && $post->post_content ) ) {
		return $states;
	}

	if ( function_exists( 'get_field' ) ) {
		$single  = get_post_meta( $post->ID, 'maicca_single_location', true );
		$archive = get_post_meta( $post->ID, 'maicca_archive_location', true );

		if ( $single || $archive ) {
			$states[] = __( 'Active (Custom)', 'mai-custom-content-areas' );
		}
	}

	return $states;
}

add_filter( 'manage_mai_template_part_posts_columns', 'maicca_mai_cca_display_column' );
/**
 * Adds the display taxonomy column after the title.
 *
 * @since 0.1.0
 *
 * @param string[] $columns An associative array of column headings.
 *
 * @return array
 */
function maicca_mai_cca_display_column( $columns ) {
	$new = [ 'maicca_location' => __( 'Custom Location', 'mai-custom-content-areas' ) ];

	return maiam_array_insert_after( $columns, 'title', $new );
}

add_action( 'manage_mai_template_part_posts_custom_column' , 'maicca_maicca_display_column_location', 10, 2 );
/**
 * Adds the display taxonomy column after the title.
 *
 * @since 0.1.0
 *
 * @param string $column  The name of the column to display.
 * @param int    $post_id The current post ID.
 *
 * @return void
 */
function maicca_maicca_display_column_location( $column, $post_id ) {
	if ( 'maicca_location' !== $column ) {
		return;
	}

	if ( ! maicca_is_custom_content_area( $post_id ) ) {
		// echo __( 'Mai Theme', 'mai-custom-content-areas' );
		return;
	}

	// echo __( 'Custom', 'mai-custom-content-areas' );

	$html       = '';
	$singles    = get_post_meta( $post_id, 'maicca_single_types', true );
	$archives   = get_post_meta( $post_id, 'maicca_archive_types', true );
	$taxonomies = get_post_meta( $post_id, 'maicca_archive_taxonomies', true );
	$terms      = get_post_meta( $post_id, 'maicca_archive_terms', true );

	if ( ! ( $singles || $archives || $taxonomies || $terms ) ) {
		return;
	}

	if ( $singles ) {
		$array = [];

		foreach ( $singles as $single ) {
			$array[] = get_post_type_object( $single )->label;
		}

		$html .= 'Single -- ' . implode( ', ', $array ) . '<br>';
	}

	if ( $archives || $taxonomies ) {
		$array = [];

		if ( $archives ) {
			foreach ( $archives as $archive ) {
				$array[] = get_post_type_object( $single )->label;
			}
		}

		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$array[] = get_taxonomy( $taxonomy )->label;
			}
		}

		$html .= 'Archives -- ' . implode( ', ', $array ) . '<br>';
	}

	if ( $terms ) {
		$array = [];

		foreach ( $terms as $term ) {
			$array[] = get_term( $term )->name;
		}

		$html .= 'Terms -- ' . implode( ', ', $array ) . '<br>';
	}

	echo wptexturize( $html );
}

add_filter( 'plugin_action_links_mai-custom-content-areas/mai-custom-content-areas.php', 'maicca_add_settings_link', 10, 4 );
/**
 * Return the plugin action links.  This will only be called if the plugin is active.
 *
 * @since   TBD
 *
 * @param   array   $actions      Associative array of action names to anchor tags
 * @param   string  $plugin_file  Plugin file name, ie my-plugin/my-plugin.php
 * @param   array   $plugin_data  Associative array of plugin data from the plugin file headers
 * @param   string  $context      Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
 *
 * @return  array  associative array of plugin action links
 */
function maicca_add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
	$url                 = admin_url( 'edit.php?post_type=mai_template_part' );
	$link                = sprintf( '<a href="%s">%s</a>', $url, __( 'Content Areas', 'mai-table-of-contents' ) );
	$actions['settings'] = $link;

	return $actions;
}

add_action( 'acf/init', 'maicca_add_settings_metabox' );
/**
 * Add content type settings metabox.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maicca_add_settings_metabox() {
	// Bail if no engine.
	if ( ! class_exists( 'Mai_Engine' ) ) {
		return;
	}

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'maicca_field_group',
			'title'    => __( 'Locations Settings', 'mai-custom-content-areas' ),
			'fields'   => maicca_get_fields(),
			'location' => [
				[
					[
						'param'    => 'maicca_template_part',
						'operator' => '==',
						'value'    => 'custom',
					],
				],
			],
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		]
	);
}

/**
 * Gets content type settings fields.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$fields = [
		[
			'key'               => 'maicca_single_tab',
			'label'             => __( 'Single Content', 'mai-custom-content-areas' ),
			'type'              => 'tab',
			'placement'         => 'left',
		],
		[
			'label'             => '',
			'key'               => 'maicca_single_heading',
			'type'              => 'message',
			'message'           => sprintf( '<h2>%s</h2>', __( 'Single Content', 'mai-custom-content-areas' ) ),
		],
		[
			'label'        => __( 'Display location', 'mai-custom-content-areas' ),
			'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-custom-content-areas' ),
			'key'          => 'maicca_single_location',
			'name'         => 'maicca_single_location',
			'type'         => 'select',
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-custom-content-areas' ),
				'before_header'        => __( 'Before header', 'mai-custom-content-areas' ),
				'after_header'         => __( 'After header', 'mai-custom-content-areas' ),
				'before_entry'         => __( 'Before entry', 'mai-custom-content-areas' ),
				'before_entry_content' => __( 'Before entry content', 'mai-custom-content-areas' ),
				'content'              => __( 'In content', 'mai-custom-content-areas' ),
				'after_entry_content'  => __( 'After entry content', 'mai-custom-content-areas' ),
				'after_entry'          => __( 'After entry', 'mai-custom-content-areas' ),
				'before_footer'        => __( 'Before footer', 'mai-custom-content-areas' ),
			],
		],
		[
			'label'             => __( 'Content location', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_content_location',
			'name'              => 'maicca_single_content_location',
			'type'              => 'select',
			'default_value'     => 'before',
			'choices'           => [
				'before' => __( 'Before headings', 'mai-custom-content-areas' ) . ' (h2, h3)',
				'after'  => __( 'After elements', 'mai-custom-content-areas' ) . ' (div, p, ol, ul, blockquote, figure, iframe)',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_single_location',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'label'             => __( 'Element count', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Count this many elements before displaying content.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_content_count',
			'name'              => 'maicca_single_content_count',
			'type'              => 'number',
			'append'            => __( 'elements', 'mai-custom-content-areas' ),
			'required'          => 1,
			'default_value'     => 6,
			'min'               => 1,
			'step'              => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_single_location',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		// [
		// 	'label'             => __( 'Elements', 'mai-custom-content-areas' ),
		// 	'instructions'      => __( 'Count the following top level elements.', 'mai-custom-content-areas' ),
		// 	'key'               => 'maicca_single_content_elements',
		// 	'name'              => 'maicca_single_content_elements',
		// 	'type'              => 'checkbox',
		// 	'choices'           => [
		// 		'div'        => esc_html( __( '<div> Most top level elements', 'mai-custom-content-areas' ) ),
		// 		'p'          => esc_html( __( '<p> Paragraphs', 'mai-custom-content-areas' ) ),
		// 		'ul'         => esc_html( __( '<ul> Unordered lists', 'mai-custom-content-areas' ) ),
		// 		'ol'         => esc_html( __( '<ol> Ordered lists', 'mai-custom-content-areas' ) ),
		// 		'blockquote' => esc_html( __( '<blockquote> Blockquotes', 'mai-custom-content-areas' ) ),
		// 		'h2'         => esc_html( __( '<h2> headings', 'mai-custom-content-areas' ) ),
		// 		'h3'         => esc_html( __( '<h3> headings', 'mai-custom-content-areas' ) ),
		// 		'h4'         => esc_html( __( '<h4> headings', 'mai-custom-content-areas' ) ),
		// 		'h5'         => esc_html( __( '<h5> headings', 'mai-custom-content-areas' ) ),
		// 	],
		// 	'conditional_logic' => [
		// 		[
		// 			[
		// 				'field'    => 'maicca_single_location',
		// 				'operator' => '==',
		// 				'value'    => 'content',
		// 			],
		// 		],
		// 	],
		// ],
		[
			'label'             => __( 'Content types', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Show on entries of these content types.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_types',
			'name'              => 'maicca_single_types',
			'type'              => 'select',
			'ui'                => 1,
			'multiple'          => 1,
			'choices'           => [],
		],
		[
			'label'             => __( 'Keyword conditions', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Show on entries any of the following keyword strings. Comma-separate multiple keyword strings to check. Keyword search is case-insensitive.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_keywords',
			'name'              => 'maicca_single_keywords',
			'type'              => 'text',
		],
		[
			'label'             => __( 'Taxonomy conditions', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Show on entries with taxonomy conditions.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomies',
			'name'              => 'maicca_single_taxonomies',
			'type'              => 'repeater',
			'collapsed'         => 'maicca_single_taxonomy',
			'layout'            => 'block',
			'button_label'      => __( 'Add Taxonomy Condition', 'mai-custom-content-areas' ),
			'sub_fields'        => maicca_get_taxonomies_sub_fields(),
			'conditional_logic' => [
				[
					'field'    => 'maicca_single_types',
					'operator' => '!=empty',
				],
			],
		],
		[
			'label'             => __( 'Taxonomies relation', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomies_relation',
			'name'              => 'maicca_single_taxonomies_relation',
			'type'              => 'select',
			'default'           => 'AND',
			'choices'           => [
				'AND' => __( 'And', 'mai-custom-content-areas' ),
				'OR'  => __( 'Or', 'mai-custom-content-areas' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'maicca_single_types',
					'operator' => '!=empty',
				],
				[
					'field'    => 'maicca_single_taxonomies',
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		[
			'label'             => __( 'Include entries', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Show on specific entries regardless of content type and taxonomy conditions.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_entries',
			'name'              => 'maicca_single_entries',
			'type'              => 'relationship',
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'min'               => '',
			'max'               => '',
			'return_format'     => 'id',
			'filters'           => [
				'search',
				'post_type',
				'taxonomy',
			],
			'elements'          => [
				'featured_image',
			],
		],
		[
			'label'             => __( 'Exclude entries', 'mai-custom-content-areas' ),
			'instructions'      => __( 'Hide on specific entries regardless of content type and taxonomy conditions.', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_exclude_entries',
			'name'              => 'maicca_single_exclude_entries',
			'type'              => 'relationship',
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'min'               => '',
			'max'               => '',
			'return_format'     => 'id',
			'filters'           => [
				'search',
				'post_type',
				'taxonomy',
			],
			'elements'          => [
				'featured_image',
			],
		],
		[
			'label'             => __( 'Content Archives', 'mai-custom-content-areas' ),
			'key'               => 'maicca_archive_tab',
			'type'              => 'tab',
			'placement'         => 'left',
		],
		[
			'label'             => '',
			'key'               => 'maicca_archive_heading',
			'type'              => 'message',
			'message'           => sprintf( '<h2>%s</h2>', __( 'Content Archives', 'mai-custom-content-areas' ) ),
		],
		[
			'label'        => __( 'Display location', 'mai-custom-content-areas' ),
			'instructions' => __( 'Location of content area on archives.', 'mai-custom-content-areas' ),
			'key'          => 'maicca_archive_location',
			'name'         => 'maicca_archive_location',
			'type'         => 'select',
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-custom-content-areas' ),
				'before_header'        => __( 'Before header', 'mai-custom-content-areas' ),
				'after_header'         => __( 'After header', 'mai-custom-content-areas' ),
				'before_loop'          => __( 'Before entries', 'mai-custom-content-areas' ),
				// 'in_entries'           => __( 'Before entries', 'mai-custom-content-areas' ), // TODO: Is this doable without breaking columns, etc?
				'after_loop'           => __( 'After entries', 'mai-custom-content-areas' ),
				'before_footer'        => __( 'Before footer', 'mai-custom-content-areas' ),
			],
		],
		[
			'label'        => __( 'Post type archives', 'mai-custom-content-areas' ),
			'instructions' => __( 'Show on post type archives.', 'mai-custom-content-areas' ),
			'key'          => 'maicca_archive_types',
			'name'         => 'maicca_archive_types',
			'type'         => 'select',
			'ui'           => 1,
			'multiple'     => 1,
			'choices'      => [],
		],
		[
			'label'        => __( 'Taxonomy archives', 'mai-custom-content-areas' ),
			'instructions' => __( 'Show on taxonomy archives.', 'mai-custom-content-areas' ),
			'key'          => 'maicca_archive_taxonomies',
			'name'         => 'maicca_archive_taxonomies',
			'type'         => 'select',
			'ui'           => 1,
			'multiple'     => 1,
			'choices'      => [],
		],
		[
			'label'         => __( 'Term archives', 'mai-custom-content-areas' ),
			'instructions'  => __( 'Show on specific term archives.', 'mai-custom-content-areas' ),
			'key'           => 'maicca_archive_terms',
			'name'          => 'maicca_archive_terms',
			'type'         => 'select',
			'ui'           => 1,
			'multiple'     => 1,
			'choices'      => [],
		],
		[
			'label'         => __( 'Exclude term archives', 'mai-custom-content-areas' ),
			'instructions'  => __( 'Hide on specific term archives.', 'mai-custom-content-areas' ),
			'key'           => 'maicca_archive_exclude_terms',
			'name'          => 'maicca_archive_exclude_terms',
			'type'         => 'select',
			'ui'           => 1,
			'multiple'     => 1,
			'choices'      => [],
		],
	];

	return $fields;
}

/**
 * Gets taxonomies sub fields.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_taxonomies_sub_fields() {
	return [
		[
			'label'             => __( 'Taxonomy', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomy',
			'name'              => 'taxonomy',
			'type'              => 'select',
			'choices'           => [],
			'ui'                => 1,
			'ajax'              => 1,
		],
		[
			'label'             => __( 'Terms', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_terms',
			'name'              => 'terms',
			'type'              => 'select',
			'choices'           => [],
			'ui'                => 1,
			'ajax'              => 1,
			'multiple'          => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_single_taxonomy',
						'operator' => '!=empty',
					],
				],
			],
		],
		[
			'key'        => 'maicca_single_operator',
			'name'       => 'operator',
			'label'      => __( 'Operator', 'mai-custom-content-areas' ),
			'type'       => 'select',
			'default'    => 'IN',
			'choices'    => [
				'IN'     => __( 'In', 'mai-custom-content-areas' ),
				'NOT IN' => __( 'Not In', 'mai-custom-content-areas' ),
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_single_taxonomy',
						'operator' => '!=empty',
					],
				],
			],
		],
	];
}
