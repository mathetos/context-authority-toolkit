/* global wp */
( function( wp ) {
	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.data || ! wp.components || ! wp.element || ! wp.i18n ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var PanelRow = wp.components.PanelRow;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var createElement = wp.element.createElement;
	var useDispatch = wp.data.useDispatch;
	var useSelect = wp.data.useSelect;
	var __ = wp.i18n.__;

	var ALTERNATIVES_META_KEY = 'cat_alternatives';
	var TOOLTIP_META_KEY = 'cat_tooltip_content';
	var PUBLIC_DISABLE_AUTOLINK_META_KEY = window.catToolkitEditor && window.catToolkitEditor.disableAutolinkMeta ? window.catToolkitEditor.disableAutolinkMeta : 'cat_disable_autolinking';
	var PUBLIC_POST_TYPES = window.catToolkitEditor && Array.isArray( window.catToolkitEditor.publicPostTypes ) ? window.catToolkitEditor.publicPostTypes : [ 'post', 'page', 'term' ];

	function parseAlternatives( value ) {
		if ( 'string' !== typeof value ) {
			return [];
		}

		var names = value.split( /\s*,\s*/ ).map( function( name ) {
			return name.trim();
		} );

		names = names.filter( function( name ) {
			return name.length > 0;
		} );

		return names.filter( function( name, index ) {
			return names.indexOf( name ) === index;
		} );
	}

	function TermSidebarFields() {
		var postType = useSelect( function( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var meta = useSelect( function( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		var setMetaValue = function( key, value ) {
			editPost(
				{
					meta: Object.assign(
						{},
						meta,
						{
							[ key ]: value
						}
					)
				}
			);
		};

		if ( 'term' !== postType ) {
			if ( PUBLIC_POST_TYPES.indexOf( postType ) === -1 ) {
				return null;
			}

			return createElement(
				PluginDocumentSettingPanel,
				{
					name: 'cat-public-autolink-settings',
					title: __( 'Context & Authority Toolkit', 'context-authority-toolkit' )
				},
				createElement(
					PanelRow,
					null,
					createElement( ToggleControl, {
						label: __( 'Disable glossary auto-linking', 'context-authority-toolkit' ),
						help: __( 'Turn off glossary auto-linking for this content.', 'context-authority-toolkit' ),
						checked: !! meta[ PUBLIC_DISABLE_AUTOLINK_META_KEY ],
						onChange: function( value ) {
							setMetaValue( PUBLIC_DISABLE_AUTOLINK_META_KEY, !! value );
						}
					} )
				)
			);
		}

		var alternatives = Array.isArray( meta[ ALTERNATIVES_META_KEY ] ) ? meta[ ALTERNATIVES_META_KEY ].join( ', ' ) : '';
		var tooltipContent = 'string' === typeof meta[ TOOLTIP_META_KEY ] ? meta[ TOOLTIP_META_KEY ] : '';

		return createElement(
			wp.element.Fragment,
			null,
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'cat-public-autolink-settings',
					title: __( 'Context & Authority Toolkit', 'context-authority-toolkit' )
				},
				createElement(
					PanelRow,
					null,
					createElement( ToggleControl, {
						label: __( 'Disable glossary auto-linking', 'context-authority-toolkit' ),
						help: __( 'Turn off glossary auto-linking for this content.', 'context-authority-toolkit' ),
						checked: !! meta[ PUBLIC_DISABLE_AUTOLINK_META_KEY ],
						onChange: function( value ) {
							setMetaValue( PUBLIC_DISABLE_AUTOLINK_META_KEY, !! value );
						}
					} )
				)
			),
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'cat-term-sidebar-fields',
					title: __( 'Term Settings', 'context-authority-toolkit' )
				},
				createElement(
					PanelRow,
					null,
					createElement( TextControl, {
						label: __( 'Alternate Names', 'context-authority-toolkit' ),
						help: __( 'Comma-separated alternative names or abbreviations for this term.', 'context-authority-toolkit' ),
						value: alternatives,
						onChange: function( value ) {
							setMetaValue( ALTERNATIVES_META_KEY, parseAlternatives( value ) );
						}
					} )
				),
				createElement(
					PanelRow,
					null,
					createElement( TextareaControl, {
						label: __( 'Tooltip content', 'context-authority-toolkit' ),
						help: __( 'Plain text only. Line breaks are supported.', 'context-authority-toolkit' ),
						value: tooltipContent,
						rows: 6,
						onChange: function( value ) {
							setMetaValue( TOOLTIP_META_KEY, value || '' );
						}
					} )
				)
			)
		);
	}

	registerPlugin(
		'cat-term-sidebar-fields',
		{
			render: TermSidebarFields
		}
	);
} )( window.wp );
