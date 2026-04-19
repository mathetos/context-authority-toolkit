jQuery( function( $ ) {
	var selector = '.cat-glossary-item-container';

	$( document ).on( 'mouseenter focusin', selector, function() {
		$( selector ).not( this ).removeClass( 'is-visible' );
		$( this ).addClass( 'is-visible' );
	} );

	$( document ).on( 'mouseleave focusout', selector, function() {
		var $item = $( this );
		window.setTimeout( function() {
			if ( ! $item.is( ':hover' ) && ! $item.is( ':focus-within' ) ) {
				$item.removeClass( 'is-visible' );
			}
		}, 10 );
	} );

	$( document ).on( 'click', selector, function( event ) {
		event.stopPropagation();
		$( selector ).not( this ).removeClass( 'is-visible' );
		$( this ).toggleClass( 'is-visible' );
	} );

	$( document ).on( 'click', function() {
		$( selector ).removeClass( 'is-visible' );
	} );

	$( document ).on( 'keydown', function( event ) {
		if ( 'Escape' === event.key ) {
			$( selector ).removeClass( 'is-visible' );
		}
	} );
} );
