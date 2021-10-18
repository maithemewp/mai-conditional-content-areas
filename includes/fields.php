<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'admin_enqueue_scripts', 'maicca_enqueue_admin_scripts' );
/**
 * Enqueue admin JS file to dynamically change post type value
 * in include/exclude post object query.
 *
 * @since 0.1.0
 *
 * @param string $hook The current screen hook.
 *
 * @return void
 */
function maicca_enqueue_admin_scripts( $hook ) {
	if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ] ) ) {
		return;
	}

	if ( 'mai_template_part' !== get_post_type() ) {
		return;
	}

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_script( 'mai-cca', MAI_CCA_PLUGIN_URL . "assets/js/mai-cca{$suffix}.js", [ 'jquery' ], MAI_CCA_VERSION, true );
}

add_action( 'acf/render_field/key=maicca_single_tab', 'maicca_admin_css' );
/**
 * Adds custom CSS in the first field.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_admin_css( $field ) {
	echo '<style>
	#acf-maicca_field_group {
		padding-bottom: 5vh;
	}
	.acf-field-maicca-single-taxonomies .acf-repeater .acf-actions {
		text-align: start;
	}
	</style>';

	$old = '<style>.
	.acf-field-mai-ccas > .acf-input > .acf-repeater > .acf-actions {
		text-align: center;
	}
	.acf-field-mai-ccas > .acf-input > .acf-repeater > .acf-actions > .button-primary {
		display: inline-flex;
		width: auto;
		margin: 16px auto;
		padding: 8px 16px;
	}
	</style>';
}

add_filter( 'acf/load_field/key=maicca_single_content_types', 'maicca_load_content_types' );
/**
 * Loads singular content types.
 *
 * @since 0.1.0
 *
 * @param array $field The field data.
 *
 * @return array
 */
function maicca_load_content_types( $field ) {
	$field['choices'] = maicca_get_post_type_choices();

	return $field;
}

add_filter( 'acf/load_field/key=maicca_single_taxonomy', 'maicca_load_single_taxonomy' );
/**
 * Loads display terms as choices.
 *
 * @since 0.1.0
 *
 * @param array $field The field data.
 *
 * @return array
 */
function maicca_load_single_taxonomy( $field ) {
	$field['choices'] = maicca_get_taxonomy_choices();
	// if ( function_exists( 'mai_get_post_types_taxonomy_choices' ) ) {
	// 	$field['choices'] = mai_get_post_types_taxonomy_choices( false );
	// }

	return $field;
}

/**
 * Get terms from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
add_filter( 'acf/load_field/key=maicca_single_terms', 'maicca_acf_load_single_terms', 10, 1 );
function maicca_acf_load_single_terms( $field ) {
	if ( function_exists( 'mai_acf_load_terms' ) ) {
		$field = mai_acf_load_terms( $field );
	}

	return $field;
}

add_filter( 'acf/prepare_field/key=maicca_single_terms', 'maicca_acf_prepare_single_terms', 10, 1 );
/**
 * Get terms from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
function maicca_acf_prepare_single_terms( $field ) {
	if ( function_exists( 'mai_acf_prepare_terms' ) ) {
		$field = mai_acf_prepare_terms( $field );
	}

	return $field;
}

/**
 * Gets chosen post type for use in other field filters.
 *
 * @since 0.1.0
 *
 * @param array      $args    The query args. See WP_Query for available args.
 * @param array      $field   The field array containing all settings.
 * @param int|string $post_id The current post ID being edited.
 *
 * @return array
 */
add_filter( 'acf/fields/post_object/query/key=maicca_single_entries', 'maicca_acf_get_posts', 10, 1 );
add_filter( 'acf/fields/post_object/query/key=maicca_exclude_entries', 'maicca_acf_get_posts', 10, 1 );
function maicca_acf_get_posts( $args ) {
	if ( function_exists( 'mai_acf_get_posts' ) ) {
		$args = mai_acf_get_posts( $args );
	}

	return $args;
}

/**
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
add_filter( 'acf/load_field/key=maicca_archive_post_types', 'maicca_acf_load_archive_post_types', 10, 1 );
function maicca_acf_load_archive_post_types( $field ) {
	$post_types = maicca_get_post_type_choices();

	foreach ( $post_types as $index => $post_type ) {
		if ( is_post_type_hierarchical( $post_type ) ) {
			continue;
		}

		unset( $post_types[ $index ] );
	}

	$field['choices'] = $post_types;

	return $field;
}


/**
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
add_filter( 'acf/load_field/key=maicca_archive_taxonomies', 'maicca_acf_load_all_taxonomies', 10, 1 );
function maicca_acf_load_all_taxonomies( $field ) {
	$field['choices'] = maicca_get_taxonomy_choices();

	return $field;
}

add_filter( 'acf/load_field/key=maicca_archive_terms', 'maicca_acf_load_all_terms', 10, 1 );
add_filter( 'acf/load_field/key=maicca_exclude_terms', 'maicca_acf_load_all_terms', 10, 1 );
/**
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
function maicca_acf_load_all_terms( $field ) {
	$field['choices'] = acf_get_taxonomy_terms( maicca_get_taxonomies() );

	return $field;
}

// add_action( 'acf/save_post', 'maicca_save_display_terms', 5 );
/**
 * Saves row display values to taxonomy terms.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maicca_save_display_terms( $post_id ) {
	if ( 'mai_template_part' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! ( isset( $_POST['acf'] ) && $_POST['acf'] ) ) {
		return;
	}

	if ( ! ( isset( $_POST['acf']['mai_ccas' ] ) && $_POST['acf']['mai_ccas' ] ) ) {
		return;
	}

	$terms = [];

	foreach ( $_POST['acf']['mai_ccas' ] as $cca ) {
		if ( ! isset( $cca['maicca_display'] ) ) {
			continue;
		}

		$terms = array_merge( $terms, (array) $cca['maicca_display'] );
	}

	$terms = array_filter( $terms );
	$terms = array_unique( $terms );

	wp_set_object_terms( $post_id, $terms, 'mai_cca_display', false );

	$count++;
}

// add_action( 'acf/save_post', 'maicca_delete_transients', 99 );
/**
 * Clears the transients on post type save/update.
 *
 * @since 0.2.1
 *
 * @return void
 */
function maicca_delete_transients( $post_id ) {
	if ( 'mai_template_part' !== get_post_type( $post_id ) ) {
		return;
	}

	$ccas = get_field( 'mai_ccas' , $post_id );

	if ( ! $ccas ) {
		return;
	}

	foreach ( $ccas as $cca ) {
		$types = isset( $cca['display'] ) ? $cca['display'] : [];

		if ( ! $types ) {
			continue;
		}

		foreach ( $types as $type ) {
			delete_transient( sprintf( 'mai_cca_%s', $type ) );
			$prime_cache = maicca_get_ccas( $type, false );
		}
	}
}
