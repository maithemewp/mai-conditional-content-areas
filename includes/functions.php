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
 * Gets available post types for content areas.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_post_types() {
	static $post_types = null;

	if ( ! is_null( $post_types ) ) {
		return $post_types;
	}

	$post_types = get_post_types( [ 'public' => true ], 'names' );
	unset( $post_types['attachment'] );

	$post_types = apply_filters( 'maicca_post_types', array_values( $post_types ) );

	$post_types = array_unique( array_filter( (array) $post_types ) );

	foreach ( $post_types as $index => $post_type ) {
		if ( post_type_exists( $post_type ) ) {
			continue;
		}

		unset( $post_types[ $index ] );
	}

	return array_values( $post_types );
}

/**
 * Gets available post types with labels.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_post_type_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$choices    = [];
	$post_types = maicca_get_post_types();

	foreach ( $post_types as $post_type ) {

		$choices[ $post_type ] = get_post_type_object( $post_type )->label;
	}

	return $choices;
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
 * Gets DOMDocument object.
 * Copies mai_get_dom_document() in Mai Engine, but without dom->replaceChild().
 *
 * @since 0.1.0
 *
 * @param string $html Any given HTML string.
 *
 * @return DOMDocument
 */
function maicca_get_dom_document( $html ) {

	// Create the new document.
	$dom = new DOMDocument();

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );

	// Remove <!DOCTYPE.
	$dom->removeChild( $dom->doctype );

	// Remove <html><body></body></html>.
	// $dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}

/**
 * Get processed content.
 * Take from mai_get_processed_content() in Mai Engine.
 *
 * @since 0.1.0
 *
 * @return string
 */
function maicca_get_processed_content( $content ) {
	if ( function_exists( 'mai_get_processed_content' ) ) {
		return mai_get_processed_content( $content );
	}

	/**
	 * Embed.
	 *
	 * @var WP_Embed $wp_embed Embed object.
	 */
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );     // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content ); // WP runs priority 8.
	$content = do_blocks( $content );                // WP runs priority 9.
	$content = wptexturize( $content );              // WP runs priority 10.
	$content = wpautop( $content );                  // WP runs priority 10.
	$content = shortcode_unautop( $content );        // WP runs priority 10.
	$content = function_exists( 'wp_filter_content_tags' ) ? wp_filter_content_tags( $content ) : wp_make_content_images_responsive( $content ); // WP runs priority 10. WP 5.5 with fallback.
	$content = do_shortcode( $content );             // WP runs priority 11.
	$content = convert_smilies( $content );          // WP runs priority 20.

	return $content;
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

/**
 * Insert a value or key/value pair after a specific key in an array.
 * If key doesn't exist, value is appended to the end of the array.
 *
 * @since 0.1.0
 *
 * @param array  $array
 * @param string $key
 * @param array  $new
 *
 * @return array
 */
function maiam_array_insert_after( array $array, $key, array $new ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys, true );
	$pos   = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}
