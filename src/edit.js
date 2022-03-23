/**
 * External dependencies
 */
import { map, filter } from 'lodash';

/**
 * useBlockProps is a React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import { SelectControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	PanelBody,
	useBlockProps,
	useSetting,
} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   param0
 * @param {Object}   param0.attributes
 * @param {Function} param0.setAttributes
 * @param {string}   param0.attributes.taxonomy
 *
 * @return {WPElement} Element to render.
 */
// export default function Edit( { attributes, setAttributes } ) {
function maiCCAEdit( { attributes, setAttributes, taxonomies } ) {
// const maiCCAEdit = ( { attributes, setAttributes } ) => {
	const {
		taxonomy,
	} = attributes;

	// const taxonomies = wp.data.select( 'core' ).getEntityRecords( 'postType', 'mai_template_part', { per_page: 500 } );

	const getTaxonomyOptions = () => {
		const selectOption = {
			label: __( '- Select -' ),
			value: '',
			disabled: true,
		};
		const taxonomyOptions = map(
			taxonomies,
			( item ) => {
				return {
					value: item.slug,
					label: item.name,
				};
			}
		);

		return [ selectOption, ...taxonomyOptions ];
	};

	const inspectorControls = (
		<InspectorControls>
			<PanelBody title={ __( 'Tag Cloud settings' ) }>
				<SelectControl
					label={ __( 'Taxonomy' ) }
					options={ getTaxonomyOptions() }
					value={ taxonomy }
					onChange={ ( selectedTaxonomy ) =>
						setAttributes( { taxonomy: selectedTaxonomy } )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);

	return (
		<>
			{ inspectorControls }
			<div { ...useBlockProps() }>
				<ServerSideRender
					key="dynamic-block"
					block="maicca/dynamic-block"
					attributes={ attributes }
				/>
			</div>
		</>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tag Cloud settings' ) }>
					<SelectControl
						label={ __( 'Taxonomy' ) }
						options={ getTaxonomyOptions() }
						value={ taxonomy }
						onChange={ ( selectedTaxonomy ) =>
							setAttributes( { taxonomy: selectedTaxonomy } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			{ inspectorControls }
			<div { ...useBlockProps() }>
				<ServerSideRender
					key="dynamic-block"
					block="maicca/dynamic-block"
					attributes={ attributes }
				/>
			</div>
		</>
	);

	// return (
	// 	<RichText
	// 		{ ...useBlockProps() }
	// 		tagName="p"
	// 		value={ message }
	// 		onChange={ ( newMessage ) =>
	// 			setAttributes( { message: newMessage } )
	// 		}
	// 	/>
	// );
}

export default maiCCAEdit( ( select ) => {
	return {
		taxonomies: wp.data.select( 'core' ).getEntityRecords( 'postType', 'mai_template_part', { per_page: 500 } ),
	};
} )( maiCCAEdit );

// export default maiCCAEdit();

// export default withSelect( ( select ) => {
// 	return {
// 		taxonomies: select( coreStore ).getTaxonomies( { per_page: -1 } ),
// 	};
// } )( maiCCAEdit );
