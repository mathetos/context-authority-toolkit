/* global wp */
( function( wp ) {
	var store = wp && wp.interactivity && wp.interactivity.store ? wp.interactivity.store : null;
	var getContext = wp && wp.interactivity && wp.interactivity.getContext ? wp.interactivity.getContext : null;

	function copyWithFallback( text ) {
		var textarea = document.createElement( 'textarea' );
		textarea.value = text;
		textarea.setAttribute( 'readonly', 'readonly' );
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild( textarea );
		textarea.select();
		document.execCommand( 'copy' );
		document.body.removeChild( textarea );
	}

	function closestFromTarget( target, selector ) {
		var element = target;
		if ( ! element ) {
			return null;
		}

		if ( Node.TEXT_NODE === element.nodeType ) {
			element = element.parentElement;
		}

		if ( ! element || ! element.closest ) {
			return null;
		}

		return element.closest( selector );
	}

	function buildClipboardPayload( citationText, bibtexText ) {
		var citation = 'string' === typeof citationText ? citationText.trim() : '';
		var bibtex = 'string' === typeof bibtexText ? bibtexText.trim() : '';
		if ( ! citation && ! bibtex ) {
			return '';
		}
		if ( ! bibtex ) {
			return citation;
		}
		if ( ! citation ) {
			return bibtex;
		}
		return citation + '\n\n' + bibtex;
	}

	function setCopiedUiState( button, copied ) {
		var defaultIcon = button.querySelector( '.cat-cite-this__icon--default' );
		var copiedIcon = button.querySelector( '.cat-cite-this__icon--copied' );
		var defaultLabel = button.querySelector( '.cat-cite-this__label--default' );
		var copiedLabel = button.querySelector( '.cat-cite-this__label--copied' );
		if ( defaultIcon ) {
			defaultIcon.hidden = !! copied;
		}
		if ( copiedIcon ) {
			copiedIcon.hidden = ! copied;
		}
		if ( defaultLabel ) {
			defaultLabel.hidden = !! copied;
		}
		if ( copiedLabel ) {
			copiedLabel.hidden = ! copied;
		}
	}

	function handleCopy( button, context ) {
		var payload = buildClipboardPayload(
			button ? button.getAttribute( 'data-citation' ) : '',
			button ? button.getAttribute( 'data-bibtex' ) : ''
		);
		if ( ! button || ! payload ) {
			return;
		}

		var markCopied = function() {
			if ( context ) {
				context.copied = true;
			}
			setCopiedUiState( button, true );
			window.setTimeout( function() {
				if ( context ) {
					context.copied = false;
				}
				setCopiedUiState( button, false );
			}, 2000 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( payload ).then( markCopied ).catch( function() {
				copyWithFallback( payload );
				markCopied();
			} );
			return;
		}

		copyWithFallback( payload );
		markCopied();
	}

	if ( store && getContext ) {
		store( 'cat-cite-this', {
			actions: {
				copyCitation: function( event ) {
					var context = getContext();
					var button = closestFromTarget( event ? event.target : null, '.cat-cite-this__button' );
					if ( ! button ) {
						return;
					}
					button.setAttribute( 'data-cat-cite-handled', '1' );
					window.setTimeout( function() {
						button.removeAttribute( 'data-cat-cite-handled' );
					}, 0 );
					handleCopy( button, context );
				}
			}
		} );
	}

	document.addEventListener( 'click', function( event ) {
		var button = closestFromTarget( event.target, '.cat-cite-this__button' );
		if ( ! button || button.getAttribute( 'data-cat-cite-handled' ) ) {
			return;
		}
		event.preventDefault();
		handleCopy( button, null );
	} );

	document.querySelectorAll( '.cat-cite-this__button' ).forEach( function( button ) {
		setCopiedUiState( button, false );
	} );
}( window.wp ) );
