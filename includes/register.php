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

	if ( maicca_is_custom_content_area( $post->ID ) ) {
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
				$states[] = __( 'Active', 'mai-custom-content-areas' );
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

	if ( maicca_is_custom_content_area( $post_id ) ) {
		echo __( 'Mai Theme', 'mai-conditional-content-areas' );
	}

	$terms = strip_tags( get_the_term_list( $post_id , 'mai_cca_display' , '', ',' , '' ) );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}

	echo $terms;
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
			'title'    => __( 'Locations Settings', 'mai-conditional-content-areas' ),
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
					'button_label' => __( 'Add Another Display Location', 'mai-conditional-content-areas' ),
					'sub_fields'   => maicca_get_ccas_sub_fields(),
				],
			],
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
function maicca_get_ccas_sub_fields() {
	return [
		[
			'label'         => __( 'Status', 'mai-conditional-content-areas' ),
			// 'message'         => __( 'Status', 'mai-conditional-content-areas' ),
			'key'           => 'maicca_active',
			'name'          => 'active',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'ui_on_text'    => __( 'On', 'mai-conditional-content-areas' ),
			'ui_off_text'   => __( 'Off', 'mai-conditional-content-areas' ),
		],
		[
			'key'       => 'maicca_global_tab',
			'label'     => __( 'Global', 'mai-conditional-content-areas' ),
			'type'      => 'tab',
			'placement' => 'top',
		],
		[
			'label'        => __( 'Display location', 'mai-conditional-content-areas' ),
			'instructions' => __( 'Location of sitewide content area.', 'mai-conditional-content-areas' ),
			'key'          => 'maicca_global_location',
			'name'         => 'global_location',
			'type'         => 'select',
			'choices'      => [
				''              => __( 'None (inactive)', 'mai-conditional-content-areas' ),
				'before_header' => __( 'Before header', 'mai-conditional-content-areas' ),
				'before_footer' => __( 'Before footer', 'mai-conditional-content-areas' ),
			],
		],
		[
			'key'       => 'maicca_single_tab',
			'label'     => __( 'Single Content', 'mai-conditional-content-areas' ),
			'type'      => 'tab',
			'placement' => 'top',
		],
		[
			'label'        => __( 'Display location', 'mai-conditional-content-areas' ),
			'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-conditional-content-areas' ),
			'key'          => 'maicca_single_location',
			'name'         => 'single_location',
			'type'         => 'select',
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-conditional-content-areas' ),
				'before_header'        => __( 'Before header', 'mai-conditional-content-areas' ),
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
			'key'               => 'maicca_single_content_types',
			'name'              => 'single_types',
			'type'              => 'select',
			'ui'                => 1,
			'multiple'          => 1,
			'choices'           => [],
		],
		[
			'label'             => __( 'Elements', 'mai-conditional-content-areas' ),
			'instructions'      => __( 'Display after this many elements', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_single_skip',
			'name'              => 'single_skip',
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
						'field'    => 'maicca_single_location',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'label'             => __( 'Taxonomy Conditions', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomies',
			'name'              => 'single_taxonomies',
			'type'              => 'repeater',
			'collapsed'         => 'maicca_single_taxonomy',
			'layout'            => 'row',
			'button_label'      => __( 'Add Taxonomy Condition', 'mai-custom-content-areas' ),
			'sub_fields'        => maicca_get_taxonomies_sub_fields(),
			'conditional_logic' => [
				[
					'field'    => 'maicca_single_content_types',
					'operator' => '!=empty',
				],
			],
		],
		[
			'label'             => __( 'Taxonomies Relation', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomies_relation',
			'name'              => 'single_taxonomies_relation',
			'type'              => 'select',
			'default'           => 'AND',
			'choices'           => [
				'AND' => __( 'And', 'mai-custom-content-areas' ),
				'OR'  => __( 'Or', 'mai-custom-content-areas' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'maicca_single_content_types',
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
			'label'             => __( 'Include entries', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_single_include',
			'name'              => 'single_include',
			'type'              => 'post_object',
			'instructions'      => __( 'Show on specific entries regardless of content type and taxonomy settings', 'mai-conditional-content-areas' ),
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'allow_null'        => 0,
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			'ajax'              => 1,
		],
		[
			'label'             => __( 'Exclude entries', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_single_exclude',
			'name'              => 'single_exclude',
			'type'              => 'post_object',
			'instructions'      => __( 'Hide on specific entries regardless of content type and taxonomy settings', 'mai-conditional-content-areas' ),
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'allow_null'        => 0,
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			'ajax'              => 1,
		],
		[
			'label'             => __( 'Content Archives', 'mai-conditional-content-areas' ),
			'key'               => 'maicca_archive_tab',
			'type'              => 'tab',
			'placement'         => 'top',
		],
		[
			'label'        => __( 'Display location', 'mai-conditional-content-areas' ),
			'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-conditional-content-areas' ),
			'key'          => 'maicca_archive_location',
			'name'         => 'archive_location',
			'type'         => 'select',
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-conditional-content-areas' ),
				'before_header'        => __( 'Before header', 'mai-conditional-content-areas' ),
				'before_entry'         => __( 'Before entry', 'mai-conditional-content-areas' ),
				'before_entry_content' => __( 'Before entry content', 'mai-conditional-content-areas' ),
				'content'              => __( 'In content', 'mai-conditional-content-areas' ),
				'after_entry_content'  => __( 'After entry content', 'mai-conditional-content-areas' ),
				'after_entry'          => __( 'After entry', 'mai-conditional-content-areas' ),
				'before_footer'        => __( 'Before footer', 'mai-conditional-content-areas' ),
			],
		],
	];
}

function maicca_get_taxonomies_sub_fields() {
	return [
		[
			'label'             => __( 'Taxonomy', 'mai-custom-content-areas' ),
			'key'               => 'maicca_single_taxonomy',
			'name'              => 'taxonomy',
			'type'              => 'select',
			'choices'           => mai_get_post_types_taxonomy_choices(),
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

// add_action( 'acf/render_field/key=maicca_taxonomies_description', 'maicca_render_taxonomies_description_field' );
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
