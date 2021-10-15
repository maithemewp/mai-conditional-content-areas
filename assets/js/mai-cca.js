( function( $ ) {

	if ( 'object' !== typeof acf ) {
		return;
	}

	var postKeys = [ 'maicca_single_taxonomy', 'maicca_single_include', 'maicca_single_exclude' ];
	var taxoKeys = [ 'maicca_single_terms' ];

	/**
	 * Uses current post types for use in include/exclude post object query.
	 *
	 * @since 0.1.0
	 *
	 * @return object
	 */
	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		if ( postKeys.includes( data.field_key ) ) {

			var postField = acf.getFields(
				{
					key: 'maicca_single_content_types',
					parent: $input.parents( '.acf-row' ),
				}
			);

			if ( postField ) {
				data.post_type = postField.shift().val();
			}
		}

		if ( taxoKeys.includes( data.field_key ) ) {

			var taxoField = acf.getFields(
				{
					key: 'maicca_single_taxonomy',
					parent: $input.parents( '.acf-row' ),
				}
			);

			console.log( taxoField );

			if ( taxoField ) {
				data.taxonomy = taxoField.shift().val();
			}
		}

		return data;
	} );

} )( jQuery );
