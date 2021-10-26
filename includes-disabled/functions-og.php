<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Displays a content area.
 *
 * @since 0.1.0
 *
 * @param array $args The content area args.
 *
 * @return void
 */
function maicca_do_cca( $args ) {
	$args = wp_parse_args( $args,
		[
			'location'   => '',
			'include'    => '',
			'exclude'    => '',
			'content'    => '',
			'taxonomies' => '',
			'skip'       => 6,
		]
	);

	$args['content'] = trim( $args['content'] );

	// Bail if no cca content.
	if ( ! $args['content'] ) {
		return;
	}

	$locations = maicca_get_locations();

	// Bail if no location and no content. Only check isset for location since 'content' has no hook.
	if ( ! isset( $locations[ $args['location'] ] ) ) {
		return;
	}

	// Sanitize.
	$args['exclude'] = is_array( $args['exclude'] ) ? array_map( 'absint', $args['exclude'] ) : $args['exclude'];
	$args['include'] = is_array( $args['include'] ) ? array_map( 'absint', $args['include'] ) : $args['include'];

	// Bail if excluding this entry.
	if ( $args['exclude'] && in_array( get_the_ID(), (array) $args['exclude'] ) ) {
		return;
	}

	// If including this entry.
	$include = $args['include'] && in_array( get_the_ID(), (array) $args['include'] );

	// If not already including, check taxonomies.
	if ( ! $include && $args['taxonomies'] ) {

		// Loop through all taxonomies to give a chance to bail if NOT IN.
		foreach ( $args['taxonomies'] as $taxonomy => $data ) {
			$term_ids = isset( $data['terms'] ) ? $data['terms'] : [];
			$operator = isset( $data['operator'] ) ? $data['operator'] : 'IN';

			// Skip this taxonomy if we don't have the data we need.
			if ( ! ( $term_ids && $operator ) ) {
				continue;
			}

			$has_term = has_term( $term_ids, $taxonomy );

			// Bail if we have a term and we aren't displaying here.
			if ( $has_term && 'NOT IN' === $operator ) {
				return;
			}

			// Bail if we have don't a term and we are dislaying here.
			if ( ! $has_term && 'IN' === $operator ) {
				return;
			}
		}
	}

	if ( 'content' === $args['location'] ) {

		add_filter( 'the_content', function( $content ) use ( $args ) {
			if ( ! is_main_query() ) {
				return $content;
			}

			return maicca_add_cca( $content, $args['content'], $args['skip'] );
		});

	} else {

		$priority = isset( $locations[ $args['location'] ]['priority'] ) && $locations[ $args['location'] ]['priority'] ? $locations[ $args['location'] ]['priority'] : 10;

		add_action( $locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
			echo maicca_get_processed_content( $args['content'] );
		}, $priority );
	}
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
function maicca_get_ccas( $type, $use_cache = true ) {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	static $ccas = null;

	if ( isset( $ccas[ $type ] ) && $use_cache ) {
		return $ccas[ $type ];
	}

	if ( ! is_array( $ccas ) ) {
		$ccas = [];
	}

	$transient = sprintf( 'mai_cca_%s', $type );

	if ( ! $use_cache || ( false === ( $queried_ccas = get_transient( $transient ) ) ) ) {

		$queried_ccas = [];
		$query        = new WP_Query(
			[
				'post_type'              => 'mai_template_part',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false, // https: //github.com/10up/Engineering-Best-Practices/issues/116
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'tax_query'              => [
					[
						'taxonomy' => 'mai_cca_display',
						'field'    => 'slug',
						'terms'    => $type,
					],
				],
			]
		);

		if ( $query->have_posts() ) {
			$taxonomies = get_object_taxonomies( $type );

			while ( $query->have_posts() ) : $query->the_post();
				$mai_ccas = get_field( 'mai_ccas' );

				if ( ! $mai_ccas ) {
					continue;
				}

				foreach ( $mai_ccas as $maicca ) {
					if ( isset( $maicca['display'] ) && ! in_array( $type, (array) $maicca['display'] ) ) {
						continue;
					}

					$cca = [
						'id'         => get_the_ID(),
						'location'   => isset( $maicca['location'] ) ? $maicca['location'] : '',
						'skip'       => isset( $maicca['skip'] ) ? $maicca['skip'] : '',
						'include'    => isset( $maicca['include'] ) ? $maicca['include'] : '',
						'exclude'    => isset( $maicca['exclude'] ) ? $maicca['exclude'] : '',
						'content'    => get_post()->post_content,
						'taxonomies' => [],
					];

					if ( $taxonomies ) {
						foreach ( $taxonomies as $taxonomy ) {
							if ( ! ( isset( $maicca[ $taxonomy ] ) && $maicca[ $taxonomy ] ) ) {
								continue;
							}

							$cca['taxonomies'][ $taxonomy ]['terms']     = $maicca[ $taxonomy ];
							$cca['taxonomies'][ $taxonomy ]['operator' ] = isset( $maicca[ $taxonomy . '_operator' ] ) ? $maicca[ $taxonomy . '_operator' ] : 'IN';
						}
					}

					$queried_ccas[] = $cca;
				}

			endwhile;
		}

		wp_reset_postdata();

		// Set transient, and expire after 1 hour.
		set_transient( $transient, $queried_ccas, 1 * HOUR_IN_SECONDS );
	}

	$ccas[ $type ] = $queried_ccas;

	return $ccas[ $type ];
}

/**
 * Adds content area to existing content/HTML.
 *
 * @since 0.1.0
 *
 * @uses DOMDocument
 *
 * @param string $content The existing html.
 * @param string $cca     The content area html.
 * @param int    $skip    The amount of elements to skip before showing the content area.
 *
 * @return string.
 */
function maicca_add_cca( $content, $cca, $skip ) {
	$cca  = trim( $cca );
	$skip = absint( $skip );

	if ( ! ( trim( $content ) && $cca && $skip ) ) {
		return $content;
	}

	$dom      = maicca_get_dom_document( $content );
	$xpath    = new DOMXPath( $dom );
	$elements = [ 'div', 'p', 'ul', 'blockquote' ];
	$elements = apply_filters( 'maicca_content_elements', $elements );
	$query    = [];

	foreach ( $elements as $element ) {
		$query[] = $element;
	}

	// self::p | self::div | self::ul | self::blockquote
	$query = 'self::' . implode( ' | self::', $query );

	$elements = $xpath->query( sprintf( '/html/body/*[%s][string-length() > 0]', $query ) );

	if ( ! $elements->length ) {
		return $content;
	}

	// Build the HTML node.
	$fragment = $dom->createDocumentFragment();
	$fragment->appendXml( $cca );

	$item = 0;

	foreach ( $elements as $element ) {
		$item++;

		if ( $skip !== $item ) {
			continue;
		}

		/**
		 * Add cca after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
		 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
		 */
		if ( null === $element->nextSibling ) {
			$element->parentNode->appendChild( $fragment );
		} else {
			$element->parentNode->insertBefore( $fragment, $element->nextSibling );
		}

		// No need to keep looping.
		break;
	}

	$content = $dom->saveHTML();

	return maicca_get_processed_content( $content );
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
			'priority' => 6,
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
		'before_loop'         => [
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

/**
 * Checks if a post is a theme content area,
 * registered via config.php.
 *
 * @since 0.1.0
 *
 * @param int $post_id The post ID to check.
 *
 * @return bool
 */
function maicca_is_custom_content_area( $post_id ) {
	if ( 'mai_template_part' !== get_post_type( $post_id ) ) {
		return false;
	}

	$slugs = function_exists( 'mai_get_config' ) ? mai_get_config( 'template-parts' ) : [];

	if ( ! $slugs ) {
		return false;
	}

	$slug   = get_post_field( 'post_name', $post_id );
	$config = $slug && isset( $slugs[ $slug ] );

	return ! $config;
}
