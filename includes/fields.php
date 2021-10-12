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

add_filter( 'acf/load_field/key=mai_cca_display', 'maicca_load_display' );
/**
 * Loads display terms as choices.
 *
 * @since 0.1.0
 *
 * @param array $field The field data.
 *
 * @return array
 */
function maicca_load_display( $field ) {
	$field['choices'] = [];
	$terms            = get_terms(
		[
			'taxonomy'   => 'mai_cca_display',
			'hide_empty' => false,
		]
	);

	if ( $terms && ! is_wp_error( $terms ) ) {
		$field['choices'] = wp_list_pluck( $terms, 'name', 'slug' );
	}

	return $field;
}

add_filter( 'acf/fields/post_object/query/key=maicca_include', 'maicca_acf_get_posts', 10, 3 );
add_filter( 'acf/fields/post_object/query/key=maicca_exclude', 'maicca_acf_get_posts', 10, 3 );
/**
 * Gets chosen post type for use in other field filters.
 * Taken from `mai_acf_get_posts()` and `mai_get_acf_request()` in Mai Engine.
 *
 * @since 0.1.0
 *
 * @param array      $args    The query args. See WP_Query for available args.
 * @param array      $field   The field array containing all settings.
 * @param int|string $post_id The current post ID being edited.
 *
 * @return array
 */
function maicca_acf_get_posts( $args, $field, $post_id ) {
	$post_types = [];

	if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST[ 'post_type' ] ) && ! empty( $_REQUEST[ 'post_type' ] ) ) {
		$post_types = $_REQUEST[ 'post_type' ];
	}

	if ( ! $post_types ) {
		return $args;
	}

	foreach ( (array) $post_types as $post_type ) {
		$args['post_type'][] = sanitize_text_field( wp_unslash( $post_type ) );
	}

	return $args;
}

add_action( 'acf/save_post', 'maicca_save_display_terms', 5 );
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
		if ( ! isset( $cca['mai_cca_display'] ) ) {
			continue;
		}

		$terms = array_merge( $terms, (array) $cca['mai_cca_display'] );
	}

	$terms = array_filter( $terms );
	$terms = array_unique( $terms );

	wp_set_object_terms( $post_id, $terms, 'mai_cca_display', false );
}

add_action( 'acf/save_post', 'maicca_delete_transients', 99 );
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
