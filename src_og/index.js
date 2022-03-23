/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
// import { registerBlockType } from '@wordpress/blocks';
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
// import Edit from './edit';
// import save from './save';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( 'mai/custom-content-area', {
	// attributes: {
	// 	cca: {
	// 		type: 'number',
	// 		default: 0
	// 	}
	// },
	edit: () => {
		const blockProps = useBlockProps();
		const posts = useSelect( ( select ) => {
			return select( 'core' ).getEntityRecords( 'postType', 'post' );
		}, [] );

		return (
			<div { ...blockProps }>
				{ ! posts && 'Loading' }
				{ posts && posts.length === 0 && 'No Posts' }
				{ posts && posts.length > 0 && (
					<a href={ posts[ 0 ].link }>
						{ posts[ 0 ].title.rendered }
					</a>
				) }
			</div>
		);
	},
} );
