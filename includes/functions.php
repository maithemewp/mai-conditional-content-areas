<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Displays a content area.
 *
 * @since 0.1.0
 *
 * @param string $type The cca type. Accepts 'single' or 'archive'.
 * @param array  $args The content area args.
 *
 * @return void
 */
function maicca_do_cca( $type, $args ) {
	switch ( $type ) {
		case 'single':
			maicca_do_single_cca( $args );
		break;
		case 'archive':
			maicca_do_archive_cca( $args );
		break;
	}
}

/**
 * Displays a content area on singular.
 *
 * @since 0.1.0
 *
 * @param array $args The content area args.
 *
 * @return void
 */
function maicca_do_single_cca( $args ) {
	if ( ! maicca_is_singular() ) {
		return;
	}

	$args = wp_parse_args( $args,
		[
			'id'                  => '',
			'location'            => '',
			'content'             => '',
			'content_location'    => 'after',
			'content_count'       => 6,
			'types'               => [],
			'keywords'            => '',
			'taxonomies'          => [],
			'taxonomies_relation' => 'AND',
			'include'             => [],
			'exclude'             => [],
		]
	);

	// Sanitize.
	$args = [
		'id'                  => absint( $args['id'] ),
		'location'            => esc_html( $args['location'] ),
		'content'             => trim( wp_kses_post( $args['content'] ) ),
		'content_location'    => esc_html( $args['content_location'] ),
		'content_count'       => absint( $args['content_count'] ),
		'types'               => array_map( 'esc_html', (array) $args['types'] ),
		'keywords'            => maicca_sanitize_keywords( $args['keywords'] ),
		'taxonomies'          => maicca_sanitize_taxonomies( $args['taxonomies'] ),
		'taxonomies_relation' => esc_html( $args['taxonomies_relation'] ),
		'include'             => array_map( 'absint', (array) $args['include'] ),
		'exclude'             => array_map( 'absint', (array) $args['exclude'] ),
	];

	// Bail if user can't view.
	if ( ! maicca_can_view( $args ) ) {
		return;
	}

	// Set variables.
	$post_id   = get_the_ID();
	$post_type = get_post_type();
	$locations = maicca_get_locations();

	// Bail if excluding this entry.
	if ( $args['exclude'] && in_array( $post_id, $args['exclude'] ) ) {
		return;
	}

	// If including this entry.
	$include = $args['include'] && in_array( $post_id, $args['include'] );

	// If not already including, check post types.
	if ( ! $include && ! in_array( $post_type, $args['types'] ) ) {
		return;
	}

	// If not already including, and have keywords, check for them.
	if ( ! $include && $args['keywords'] ) {
		$post         = get_post( $post_id );
		$post_content = maicca_strtolower( strip_tags( do_shortcode( trim( $post->post_content ) ) ) );

		if ( ! ( function_exists( 'mai_has_string' ) && mai_has_string( $args['keywords'], $post_content ) ) ) {
			return;
		}
	}

	// If not already including, check taxonomies.
	if ( ! $include && $args['taxonomies'] ) {

		if ( 'AND' === $args['taxonomies_relation'] ) {

			// Loop through all taxonomies to give a chance to bail if NOT IN.
			foreach ( $args['taxonomies'] as $data ) {
				$has_term = has_term( $data['terms'], $data['taxonomy'] );

				// Bail if we have a term and we aren't displaying here.
				if ( $has_term && 'NOT IN' === $data['operator'] ) {
					return;
				}

				// Bail if we have don't a term and we are dislaying here.
				if ( ! $has_term && 'IN' === $data['operator'] ) {
					return;
				}
			}

		} elseif ( 'OR' === $args['taxonomies_relation'] ) {

			$meets_any = false;

			foreach ( $args['taxonomies'] as $data ) {
				$has_term = has_term( $data['terms'], $data['taxonomy'] );

				if ( $has_term && 'IN' === $data['operator'] ) {
					$meets_any = true;
					break;
				}

				if ( ! $has_term && 'NOT IN' === $data['operator'] ) {
					$meets_any = true;
					break;
				}
			}

			if ( ! $meets_any ) {
				return;
			}
		}
	}

	if ( 'content' === $args['location'] ) {

		add_filter( 'the_content', function( $content ) use ( $args ) {
			if ( ! is_main_query() ) {
				return $content;
			}

			return maicca_add_cca( $content, $args['content'],
				[
					'location' => $args['content_location'],
					'count'    => $args['content_count'],
				]
			);
		});

	} else {

		$priority = isset( $locations[ $args['location'] ]['priority'] ) && $locations[ $args['location'] ]['priority'] ? $locations[ $args['location'] ]['priority'] : 10;

		add_action( $locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
			echo maicca_get_processed_content( $args['content'] );
		}, $priority );
	}
}

/**
 * Displays a content area on archives.
 *
 * @since 0.1.0
 *
 * @param array $args The content area args.
 *
 * @return void
 */
function maicca_do_archive_cca( $args ) {
	if ( ! maicca_is_archive() ) {
		return;
	}

	$args = wp_parse_args( $args,
		[
			'id'         => '',
			'location'   => '',
			'content'    => '',
			'types'      => [],
			'taxonomies' => [],
			'terms'      => [],
			'exclude'    => [],
		]
	);

	// Sanitize.
	$args = [
		'id'         => absint( $args['id'] ),
		'location'   => esc_html( $args['location'] ),
		'content'    => trim( wp_kses_post( $args['content'] ) ),
		'types'      => array_map( 'esc_html', (array) $args['types'] ),
		'taxonomies' => array_map( 'esc_html', (array) $args['taxonomies'] ),
		'terms'      => array_map( 'absint', (array) $args['types'] ),
		'exclude'    => array_map( 'absint', (array) $args['exclude'] ),
	];

	// Bail if user can't view.
	if ( ! maicca_can_view( $args ) ) {
		return;
	}

	// Set variables.
	$locations = maicca_get_locations();

	// Blog.
	if ( is_home() ) {
		// Bail if not showing on post archive.
		if ( ! in_array( 'post', $args['types'] ) ) {
			return;
		}
	}

	// CPT archive.
	elseif ( is_post_type_archive() ) {
		// Bail if not showing on this cpt archive.
		if ( ! is_post_type_archive( $args['types'] ) ) {
			return;
		}
	}

	// Term archive.
	elseif ( is_tax() || is_category() || is_tag() ) {
		$object = get_queried_object();

		// Bail if excluding this term archive.
		if ( $args['exclude'] && in_array( $object->term_id, $args['exclude'] ) ) {
			return;
		}

		// If including this entry.
		$include = $args['terms'] && in_array( $object->term_id, $args['terms'] );

		// If not already including, check taxonomies if we're restricting to specific taxonomies.
		if ( ! $include && ! in_array( $object->taxonomy, $args['taxonomies'] ) ) {
			return;
		}
	}

	$priority = isset( $locations[ $args['location'] ]['priority'] ) && $locations[ $args['location'] ]['priority'] ? $locations[ $args['location'] ]['priority'] : 10;

	add_action( $locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
		echo maicca_get_processed_content( $args['content'] );
	}, $priority );
}

/**
 * If user can view content area.
 *
 * @since 0.1.0
 *
 * @param array $args The cca args.
 *
 * @return bool
 */
function maicca_can_view( $args ) {
	// Bail if no id, content, and location.
	if ( ! ( $args['id'] && $args['location'] && $args['content'] ) ) {
		return false;
	}

	// Set variables.
	$locations = maicca_get_locations();
	$status    = get_post_status( $args['id'] );

	// Bail if no location hook. Only check isset for location since 'content' has no hook.
	if ( ! isset( $locations[ $args['location'] ] ) ) {
		return false;
	}

	// Bail if not a status we want.
	if ( ! in_array( $status, [ 'publish', 'private' ] ) ) {
		return false;
	}

	// Bail if user can't view private cca.
	if ( 'private' === $status && ! ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ) {
		return false;
	}

	return true;
}

/**
 * Gets content areas by type.
 *
 * @since 0.1.0
 *
 * @param string $type
 * @param bool   $use_cache
 *
 * @return array
 */
function maicca_get_ccas( $use_cache = true ) {
	if ( ! ( function_exists( 'get_field' ) && function_exists( 'mai_get_template_part_ids' ) ) ) {
		return [];
	}

	static $ccas = null;

	if ( $ccas && $use_cache ) {
		return $ccas;
	}

	if ( ! is_array( $ccas ) ) {
		$ccas = [];
	}

	$transient = 'mai_ccas';

	if ( ! $use_cache || ( false === ( $queried_ccas = get_transient( $transient ) ) ) ) {

		$queried_ccas = [];
		$query        = new WP_Query(
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

				$content          = get_post()->post_content;
				$single_location  = get_field( 'maicca_single_location' );
				$archive_location = get_field( 'maicca_archive_location' );

				if ( $single_location ) {
					$single_data = [
						'id'                  => get_the_ID(),
						'status'              => get_post_status(),
						'location'            => $single_location,
						'content'             => $content,
						'content_location'    => get_field( 'maicca_single_content_location' ),
						'content_count'       => get_field( 'maicca_single_content_count' ),
						'types'               => get_field( 'maicca_single_types' ),
						'keywords'            => get_field( 'maicca_single_keywords' ),
						'taxonomies'          => get_field( 'maicca_single_taxonomies' ),
						'taxonomies_relation' => get_field( 'maicca_single_taxonomies_relation' ),
						'include'             => get_field( 'maicca_single_entries' ),
						'exclude'             => get_field( 'maicca_single_exclude_entries' ),
					];

					$queried_ccas['single'][] = maicca_filter_associative_array( $single_data );
				}

				if ( $archive_location ) {

					$archive_data = [
						'id'         => get_the_ID(),
						'status'     => get_post_status(),
						'location'   => $archive_location,
						'content'    => $content,
						'types'      => get_field( 'maicca_archive_types' ),
						'taxonomies' => get_field( 'maicca_archive_taxonomies' ),
						'terms'      => get_field( 'maicca_archive_terms' ),
						'exclude'    => get_field( 'maicca_archive_exclude_terms' ),
					];

					$queried_ccas['archive'][] = maicca_filter_associative_array( $archive_data );
				}

			endwhile;
		}

		wp_reset_postdata();

		// Set transient, and expire after 1 hour.
		set_transient( $transient, $queried_ccas, 1 * HOUR_IN_SECONDS );
	}

	$ccas = $queried_ccas;

	return $ccas;
}

/**
 * Get content area hook locations.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_locations() {
	static $locations = null;

	if ( ! is_null( $locations ) ) {
		return $locations;
	}

	$locations = [
		'before_header'        => [
			'hook'     => 'genesis_header',
			'priority' => 5,
		],
		'after_header'        => [
			'hook'     => 'genesis_after_header',
			'priority' => 15,
		],
		'before_loop'         => [
			'hook'     => 'genesis_loop',
			'priority' => 5,
		],
		'before_entry'         => [
			'hook'     => 'genesis_before_entry',
			'priority' => 10,
		],
		'before_entry_content' => [
			'hook'     => 'genesis_before_entry_content',
			'priority' => 10,
		],
		'content'              => [
			'hook'     => '', // No hooks, counted in content.
			'priority' => null,
		],
		'after_entry_content'  => [
			'hook'     => 'genesis_after_entry_content',
			'priority' => 10,
		],
		'after_entry'          => [
			'hook'     => 'genesis_after_entry',
			'priority' => 8, // 10 was after comments.
		],
		'after_loop'           => [
			'hook'     => 'genesis_loop',
			'priority' => 15,
		],
		'before_footer'        => [
			'hook'     => 'genesis_after_content_sidebar_wrap',
			'priority' => 10,
		],
	];

	$locations = apply_filters( 'maicca_locations', $locations );

	if ( $locations ) {
		foreach ( $locations as $name => $location ) {
			$locations[ $name ] = wp_parse_args( (array) $location,
				[
					'hook'     => '',
					'priority' => null,
				]
			);
		}
	}

	return $locations;
}
