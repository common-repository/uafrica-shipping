import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import uafricaIcon from './icon';

registerBlockType( 'uafrica/shipping', {
	title: __( 'Bob Go Tracking', 'uafrica-shipping' ),
	description: __( 'Display Bob Go Shipping tracking info.', 'uafrica-shipping' ),
	category: 'widgets',
	icon: uafricaIcon,
	supports: { html: false },
	attributes: {
		bg_color: { type: 'string', default: '#000000' },
		text_color: { type: 'string', default: '#ffffff' },
	},
	/**
	 * @see ./edit.js
	 */
	edit,
	save: () => {
		return null; // we have a dynamic block, no custom save needed.
	},
} );
