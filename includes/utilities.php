<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Checks if current page is an singular.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function maicca_is_singular() {
	return is_singular();
}

/**
 * Checks if current page is an archive.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function maicca_is_archive() {
	return is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax();
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
 * Gets post type choices with name => label.
 * If two share the same label, the name is appended in parenthesis.
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

	$choices = maicca_get_post_types();
	$choices = function_exists( 'acf_get_pretty_post_types' ) ? acf_get_pretty_post_types( $choices ) : $choices;

	return $choices;
}

/**
 * Gets taxonomies
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_taxonomies() {
	static $taxonomies = null;

	if ( ! is_null( $taxonomies ) ) {
		return $taxonomies;
	}

	$taxonomies = get_taxonomies( [ 'public' => 'true' ], 'names' );

	$taxonomies = apply_filters( 'maicca_taxonomies', array_values( $taxonomies ) );

	$taxonomies = array_unique( array_filter( (array) $taxonomies ) );

	foreach ( $taxonomies as $index => $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		unset( $taxonomy[ $index ] );
	}

	return array_values( $taxonomies );
}

/**
 * Gets taxonomy choices with name => label.
 * If two share the same label, the name is appended in parenthesis.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maicca_get_taxonomy_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$choices = maicca_get_taxonomies();
	$choices = function_exists( 'acf_get_pretty_taxonomies' ) ? acf_get_pretty_taxonomies( $choices ) : $choices;

	return $choices;
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
 * Adds content area to existing content/HTML.
 *
 * @access private
 *
 * @since 0.1.0
 *
 * @uses DOMDocument
 *
 * @param string $content     The existing html.
 * @param string $cca_content The content area html.
 * @param array  $args        The cca args.
 *
 * @return string.
 */
function maicca_add_cca( $content, $cca_content, $args ) {
	$cca_content  = trim( $cca_content );
	$args         = wp_parse_args( $args,
		[
			'location' => 'after', // The location of the cca in relation to the elements.
			'count'    => 6,       // The amount of elements to count before showing the content area.
		]
	);

	// Sanitize.
	$location = esc_html( $args['location'] );
	$after    = 'after' === $location;
	$count    = absint( $args['count'] );

	if ( ! ( trim( $content ) && $cca_content && $count ) ) {
		return $content;
	}

	$dom      = maicca_get_dom_document( $content );
	$xpath    = new DOMXPath( $dom );
	$elements = $after ? [ 'div', 'p', 'ol', 'ul', 'blockquote', 'figure', 'iframe' ] : [ 'h2', 'h3' ];
	$elements = apply_filters( 'maicca_content_elements', $elements, $location );
	$query    = [];

	foreach ( $elements as $element ) {
		$query[] = $element;
	}

	// self::div | self::p | self::ol | etc.
	$query = 'self::' . implode( ' | self::', $query );

	$elements = $xpath->query( sprintf( '/html/body/*[%s][string-length() > 0]', $query ) );

	if ( ! $elements->length ) {
		return $content;
	}

	// Build the HTML node.
	$fragment = $dom->createDocumentFragment();
	$fragment->appendXml( $cca_content );

	$item = 0;

	foreach ( $elements as $element ) {
		$item++;

		if ( $count !== $item ) {
			continue;
		}

		// After elements.
		if ( $after ) {
			/**
			 * Bail if this is the last element.
			 * This avoids duplicates since this location would technically be "after entry content" at this point.
			 */
			if ( null === $element->nextSibling ) {
				break;
			}

			$element->parentNode->insertBefore( $fragment, $element->nextSibling );

		}
		// Before headings.
		else {
			$element->parentNode->insertBefore( $fragment, $element );
		}

		/**
		 * Add cca after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
		 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
		 */
		// if ( null === $element->nextSibling ) {
		// 	$element->parentNode->appendChild( $fragment );
		// } else {
		// 	$element->parentNode->insertBefore( $fragment, $element->nextSibling );
		// }

		// No need to keep looping.
		break;
	}

	$content = $dom->saveHTML();

	return maicca_get_processed_content( $content );
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
 * Sanitizes keyword strings to array.
 *
 * @param string $keywords Comma-separated keyword strings.
 *
 * @return array
 */
function maicca_sanitize_keywords( $keywords ) {
	$sanitized = [];
	$keywords  = trim( (string) $keywords );

	if ( ! $keywords ) {
		return $sanitized;
	}

	$sanitized = explode( ',', $keywords );
	$sanitized = array_map( 'trim', $sanitized );
	$sanitized = array_filter( $sanitized );
	$sanitized = array_map( 'maicca_strtolower', $sanitized );

	return $sanitized;
}

/**
 * Sanitized a string to lowercase, keeping character encoding.
 *
 * @param string $string The string to make lowercase.
 *
 * @return string
 */
function maicca_strtolower( $string ) {
	return mb_strtolower( (string) $string, 'UTF-8' );
}

/**
 * Sanitizes taxonomy data for CCA.
 *
 * @param array $taxonomies The taxonomy data.
 *
 * @return array
 */
function maicca_sanitize_taxonomies( $taxonomies ) {
	if ( ! $taxonomies ) {
		return $taxonomies;
	}

	$sanitized = [];

	foreach ( $taxonomies as $data ) {
		$args = wp_parse_args( $data,
			[
				'taxonomy' => '',
				'terms'    => [],
				'operator' => 'IN',
			]
		);

		// Skip if we don't have all of the data.
		if ( ! ( $args['taxonomy'] && $args['terms'] && $args['operator'] ) ) {
			continue;
		}

		$sanitized[] = [
			'taxonomy' => esc_html( $args['taxonomy'] ),
			'terms'    => array_map( 'absint', (array) $args['terms'] ),
			'operator' => esc_html( $args['operator'] ),
		];
	}

	return $sanitized;
}
