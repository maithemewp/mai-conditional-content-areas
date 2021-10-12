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

	if ( maicca_is_config_content_area( $post->ID ) ) {
		return $states;
	}

	if ( ! ( 'publish' === $post->post_status && $post->post_content ) ) {
		return $states;
	}

	if ( function_exists( 'get_field' ) ) {
		$ccas = get_field( 'mai_ccas' );

		if ( $ccas ) {
			$locations = wp_list_pluck( $ccas, 'location' );

			if ( $locations ) {
				$states[] = __( 'Active', 'mai-engine' );
			}
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
	$new = [ 'mai_cca_display' => __( 'Display', 'mai-conditional-content-areas' ) ];

	return maiam_array_insert_after( $columns, 'title', $new );
}

add_action( 'manage_mai_template_part_posts_custom_column' , 'maicca_mai_cca_display_column_content', 10, 2 );
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
function maicca_mai_cca_display_column_content( $column, $post_id ) {
	if ( 'mai_cca_display' !== $column ) {
		return;
	}

	if ( maicca_is_config_content_area( $post_id ) ) {
		echo __( 'Mai Theme', 'mai-conditional-content-areas' );
	}

	$terms = strip_tags( get_the_term_list( $post_id , 'mai_cca_display' , '', ',' , '' ) );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}

	echo $terms;
}

add_action( 'init', 'maicca_add_settings_metabox', 99 );
/**
 * Add content type settings metabox.
 * Can't be on acf/init because we need to get registered content types.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maicca_add_settings_metabox() {
	// Bail if no engine and no ACF Pro.
	if ( ! ( class_exists( 'Mai_Engine' ) && class_exists( 'acf_pro' ) ) ) {
		return;
	}

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'maicca_field_group',
			'title'    => __( 'Content Area Display Settings', 'mai-conditional-content-areas' ),
			'fields'   => [
				[
					'key'          => 'mai_ccas' ,
					'label'        => __( 'Locations', 'mai-conditional-content-areas' ),
					'name'         => 'mai_ccas' ,
					'type'         => 'repeater',
					'collapsed'    => 'maicca_location',
					'min'          => 1,
					'max'          => 0,
					'layout'       => 'block',
					'button_label' => __( 'Add Display Location', 'mai-conditional-content-areas' ),
					'sub_fields'   => maicca_get_settings_metabox_sub_fields(),
				],
			],
			'location' => [
				[
					[
						'param'    => 'maicca_conditional_content',
						'operator' => '==', // Currently unused.
						'value'    => true, // Currently unused.
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
function maicca_get_settings_metabox_sub_fields() {
	$fields = [
		[
			'key'       => 'maicca_location_tab',
			'label'     => __( 'Location', 'mai-conditional-content-areas' ),
			'type'      => 'tab',
			'placement' => 'top',
		],
		[
			'label'        => __( 'Display location', 'mai-conditional-content-areas' ),
			'instructions' => __( 'Location of content area', 'mai-conditional-content-areas' ),
			'key'          => 'maicca_location',
			'name'         => 'location',
			'type'         => 'select',
			// 'required'     => 1,
			// 'allow_null'   => 0,
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-conditional-content-areas' ),
				'before_entry'         => __( 'Before entry', 'mai-conditional-content-areas' ),
				'before_entry_content' => __( 'Before entry content', 'mai-conditional-content-areas' ),
				'content'              => __( 'In content', 'mai-conditional-content-areas' ),
				'after_entry_content'  => __( 'After entry content', 'mai-conditional-content-areas' ),
				'after_entry'          => __( 'After entry', 'mai-conditional-content-areas' ),
				'before_footer'        => __( 'Before footer', 'mai-conditional-content-areas' ),
			],
		],
		[
			'label'             => __( 'Content types', 'mai-conditional-content-areas' ),
			'instructions'      => __( 'Display on these content types', 'mai-conditional-content-areas' ),
			'key'               => 'mai_cca_display',
			'name'              => 'display',
			'type'              => 'select',
			'required'          => 1,
			'ui'                => 1,
			'multiple'          => 1,
			'choices'           => [],
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
				],
			],
		],
		[
			'label'             => __( 'Elements', 'mai-conditional-content-areas' ),
			'instructions'      => __( 'Display after this many elements', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_skip',
			'name'              => 'skip',
			'type'              => 'number',
			'append'            => __( 'elements', 'mai-conditional-content-areas' ),
			'required'          => 1,
			'default_value'     => 6,
			'min'               => 1,
			'max'               => '',
			'step'              => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'key'               => 'maicca_taxonomies_tab',
			'label'             => __( 'Taxonomies', 'mai-conditional-content-areas' ),
			'type'              => 'tab',
			'placement'         => 'top',
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '!=empty',
					],
				],
			],
		],
	];

	$taxonomies = get_taxonomies( [ 'public' => 'true' ], 'objects' );
	unset( $taxonomies['post_format'] );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$conditions = [];

			foreach ( $taxonomy->object_type as $type ) {
				$term = get_term_by( 'slug', $type, 'mai_cca_display' );

				if ( ! $term ) {
					continue;
				}

				$conditions[] = [
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '==',
						'value'    => $term->slug,
					],
				];
			}

			if ( ! $conditions ) {
				continue;
			}

			$fields = array_merge( $fields, [
				[
					'label'             => $taxonomy->label,
					'key'               => sprintf( 'maicca_terms_%s', $taxonomy->name ),
					'name'              => $taxonomy->name,
					'type'              => 'taxonomy',
					'instructions'      => sprintf( __( 'Limit to entries with any of these %s', 'mai-conditional-content-areas' ), strtolower( $taxonomy->label ) ),
					'required'          => 0,
					'taxonomy'          => $taxonomy->name,
					'field_type'        => 'multi_select',
					'allow_null'        => 0,
					'add_term'          => 0,
					'save_terms'        => 0,
					'load_terms'        => 0,
					'return_format'     => 'id',
					'multiple'          => 1,
					'conditional_logic' => $conditions,
					'wrapper'           => [
						'width' => '75%',
					],
				],
				[
					'label'             => __( 'Operator', 'mai-conditional-content-areas' ),
					'key'               => sprintf( 'maicca_operator_%s', $taxonomy->name ),
					'name'              => $taxonomy->name . '_operator',
					'type'              => 'select',
					'instructions'      => __( 'Include or exclude these entries', 'mai-conditional-content-areas' ),
					'default_value'     => 'IN',
					'choices'           => [
						'IN'     => __( 'In', 'mai-conditional-content-areas' ),
						'NOT IN' => __( 'Not In', 'mai-conditional-content-areas' ),
					],
					'conditional_logic' => $conditions,
					'wrapper'           => [
						'width' => '25%',
					],
				]
			] );
		}

	}

	$fields = array_merge( $fields, [
		[
			'key'               => 'maicca_taxonomies_description',
			'name'              => '',
			'label'             => '',
			'type'              => 'message',
			'message'           => __( 'No taxonomies available for the selected content types', 'mai-conditional-content-areas' ),
			'new_lines'         => 'wpautop',
			'esc_html'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '!=empty',
					],
				],
			],
		],
		[
			'key'               => 'maicca_entries_tab',
			'label'             => __( 'Entries', 'mai-conditional-content-areas' ),
			'type'              => 'tab',
			'placement'         => 'top',
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '!=empty',
					],
				],
			],
		],
		[
			'label'             => __( 'Include entries', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_include',
			'name'              => 'include',
			'type'              => 'post_object',
			'instructions'      => __( 'Show on specific entries regardless of content type and taxonomy settings', 'mai-conditional-content-areas' ),
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'allow_null'        => 0,
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '!=empty',
					],
				],
			],
		],
		[
			'label'             => __( 'Exclude entries', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_exclude',
			'name'              => 'exclude',
			'type'              => 'post_object',
			'instructions'      => __( 'Hide on specific entries regardless of content type and taxonomy settings', 'mai-conditional-content-areas' ),
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'allow_null'        => 0,
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'maicca_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cca_display',
						'operator' => '!=empty',
					],
				],
			],
		],
	] );

	return $fields;
}

add_filter( 'acf/location/rule_match/maicca_conditional_content', 'maicca_acf_conditional_content_rule_match', 10, 4 );
/**
 * Shows content area display settings on all non-config content areas.
 *
 * @since 0.1.0
 *
 * @param bool      $result Whether the rule matches.
 * @param array     $rule   Current rule to match (param, operator, value).
 * @param WP_Screen $screen The current screen.
 *
 * @return bool
 */
function maicca_acf_conditional_content_rule_match( $result, $rule, $screen ) {
	if ( 'mai_template_part' !== $screen['post_type'] ) {
		return false;
	}

	return isset( $screen['post_id'] ) && ! maicca_is_config_content_area( $screen['post_id'] );
}

add_action( 'acf/render_field/key=maicca_taxonomies_description', 'maicca_render_taxonomies_description_field' );
/**
 * Adds custom CSS to taxonomy description field.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_render_taxonomies_description_field( $field ) {
	echo '<style>
	.acf-field-taxonomy:not(.acf-hidden) + .acf-field-select:not(.acf-hidden) + .acf-field-maicca-taxonomies-description {
		display: none;
	}
	</style>';
}
