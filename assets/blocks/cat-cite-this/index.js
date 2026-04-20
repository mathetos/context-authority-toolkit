/* global wp */
( function( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.blockEditor || ! wp.components || ! wp.data || ! wp.element || ! wp.i18n ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var __ = wp.i18n.__;

	function normalizeString( value ) {
		return 'string' === typeof value ? value.trim() : '';
	}

	function ensureSentence( value ) {
		var text = normalizeString( value );
		if ( ! text ) {
			return '';
		}

		if ( /[.!?]$/.test( text ) ) {
			return text;
		}

		return text + '.';
	}

	function slugify( value ) {
		return normalizeString( value )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '_' )
			.replace( /^_+|_+$/g, '' );
	}

	function formatDate( value ) {
		var match = normalizeString( value ).match( /^\d{4}-\d{2}-\d{2}/ );
		return match ? match[ 0 ] : '';
	}

	function getEditorContext() {
		return useSelect( function( select ) {
			var editorStore = select( 'core/editor' );
			var coreStore = select( 'core' );
			if ( ! editorStore || ! coreStore ) {
				return {};
			}

			var authorId = editorStore.getEditedPostAttribute( 'author' );
			var author = authorId ? coreStore.getUser( authorId ) : null;
			var site = coreStore.getEntityRecord( 'root', 'site' );
			var permalink = editorStore.getPermalink ? editorStore.getPermalink() : editorStore.getEditedPostAttribute( 'link' );

			return {
				title: normalizeString( editorStore.getEditedPostAttribute( 'title' ) ),
				author: author && author.name ? normalizeString( author.name ) : '',
				lastVerified: formatDate( editorStore.getEditedPostAttribute( 'modified' ) ),
				excerpt: normalizeString( editorStore.getEditedPostAttribute( 'excerpt' ) ),
				url: normalizeString( permalink ),
				publisher: site && site.name ? normalizeString( site.name ) : ''
			};
		}, [] );
	}

	function buildCitationPreview( context, attributes ) {
		var parts = [];
		if ( attributes.includeAuthor && context.author ) {
			parts.push( ensureSentence( context.author ) );
		}
		if ( attributes.includeLastVerified && context.lastVerified ) {
			parts.push( '(' + context.lastVerified + ').' );
		}
		if ( attributes.includeTitle && context.title ) {
			parts.push( ensureSentence( context.title ) );
		}
		if ( attributes.includePublisher && context.publisher ) {
			parts.push( ensureSentence( context.publisher ) );
		}
		if ( attributes.includeUrl && context.url ) {
			parts.push( ensureSentence( context.url ) );
		}
		if ( attributes.includeExcerpt && context.excerpt ) {
			parts.push( ensureSentence( context.excerpt ) );
		}
		return parts.join( ' ' ).trim();
	}

	function buildBibtexPreview( context ) {
		var year = context.lastVerified ? context.lastVerified.slice( 0, 4 ) : String( new Date().getUTCFullYear() );
		var keyBase = slugify( context.title ) || 'term';
		return '@misc{cat_' + keyBase + ',\n' +
			'  author = {' + normalizeString( context.author ) + '},\n' +
			'  title = {' + normalizeString( context.title ) + '},\n' +
			'  year = {' + year + '},\n' +
			'  url = {' + normalizeString( context.url ) + '},\n' +
			'  urldate = {' + normalizeString( context.lastVerified ) + '},\n' +
			'  note = {Last verified: ' + normalizeString( context.lastVerified ) + '}\n' +
			'}';
	}

	function edit( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var blockProps = useBlockProps( { className: 'cat-cite-this' } );
		var context = getEditorContext();
		var citationPreview = buildCitationPreview( context, attributes );
		var bibtexPreview = buildBibtexPreview( context );

		return createElement(
			Fragment,
			null,
			createElement(
				InspectorControls,
				null,
				createElement(
					PanelBody,
					{ title: __( 'Citation options', 'context-authority-toolkit' ), initialOpen: true },
					createElement( ToggleControl, {
						label: __( 'Include author', 'context-authority-toolkit' ),
						checked: !! attributes.includeAuthor,
						onChange: function( value ) { setAttributes( { includeAuthor: !! value } ); }
					} ),
					createElement( ToggleControl, {
						label: __( 'Include last verified date', 'context-authority-toolkit' ),
						checked: !! attributes.includeLastVerified,
						onChange: function( value ) { setAttributes( { includeLastVerified: !! value } ); }
					} ),
					createElement( ToggleControl, {
						label: __( 'Include title', 'context-authority-toolkit' ),
						checked: !! attributes.includeTitle,
						onChange: function( value ) { setAttributes( { includeTitle: !! value } ); }
					} ),
					createElement( ToggleControl, {
						label: __( 'Include publisher', 'context-authority-toolkit' ),
						checked: !! attributes.includePublisher,
						onChange: function( value ) { setAttributes( { includePublisher: !! value } ); }
					} ),
					createElement( ToggleControl, {
						label: __( 'Include URL', 'context-authority-toolkit' ),
						checked: !! attributes.includeUrl,
						onChange: function( value ) { setAttributes( { includeUrl: !! value } ); }
					} ),
					createElement( ToggleControl, {
						label: __( 'Include excerpt', 'context-authority-toolkit' ),
						checked: !! attributes.includeExcerpt,
						onChange: function( value ) { setAttributes( { includeExcerpt: !! value } ); }
					} ),
					createElement( TextControl, {
						label: __( 'Button text', 'context-authority-toolkit' ),
						value: attributes.buttonText || '',
						onChange: function( value ) { setAttributes( { buttonText: value || '' } ); }
					} ),
					createElement( TextControl, {
						label: __( 'Copied text', 'context-authority-toolkit' ),
						value: attributes.copiedText || '',
						onChange: function( value ) { setAttributes( { copiedText: value || '' } ); }
					} )
				),
				createElement(
					PanelBody,
					{ title: __( 'Preview (editor context)', 'context-authority-toolkit' ), initialOpen: false },
					createElement( TextareaControl, {
						label: __( 'Citation preview', 'context-authority-toolkit' ),
						value: citationPreview || __( 'No term context available in this editor.', 'context-authority-toolkit' ),
						readOnly: true,
						rows: 4
					} ),
					createElement( TextareaControl, {
						label: __( 'BibTeX preview', 'context-authority-toolkit' ),
						value: bibtexPreview,
						readOnly: true,
						rows: 8
					} )
				)
			),
			createElement(
				'div',
				blockProps,
				createElement(
					'button',
					{ type: 'button', className: 'cat-cite-this__button', disabled: true },
					createElement(
						'span',
						{ className: 'cat-cite-this__icon cat-cite-this__icon--default', 'aria-hidden': 'true' },
						'✓'
					),
					createElement(
						'span',
						{ className: 'cat-cite-this__label' },
						attributes.buttonText || __( 'Copy citation', 'context-authority-toolkit' )
					)
				)
			)
		);
	}

	registerBlockType( 'cat-toolkit/cat-cite-this', { edit: edit, save: function() { return null; } } );
}( window.wp ) );
