( function() {
	var itemSelector = '.cat-glossary-item-container';
	var triggerSelector = '.cat-glossary-item-trigger';
	var panelSelector = '.cat-glossary-item-hidden-content';
	var showDelay = 150;
	var hideDelay = 100;
	var timers = new WeakMap();

	function getItemParts( item ) {
		if ( ! item ) {
			return null;
		}

		var trigger = item.querySelector( triggerSelector );
		var panel = item.querySelector( panelSelector );
		if ( ! trigger || ! panel ) {
			return null;
		}

		return {
			trigger: trigger,
			panel: panel
		};
	}

	function isOpen( item ) {
		return item.classList.contains( 'is-visible' );
	}

	function isPinned( item ) {
		return item.classList.contains( 'is-pinned' );
	}

	function ensureTimerRecord( item ) {
		if ( ! timers.has( item ) ) {
			timers.set(
				item,
				{
					show: 0,
					hide: 0
				}
			);
		}
		return timers.get( item );
	}

	function clearTimers( item ) {
		var record = ensureTimerRecord( item );
		window.clearTimeout( record.show );
		window.clearTimeout( record.hide );
		record.show = 0;
		record.hide = 0;
	}

	function closeItem( item ) {
		var parts = getItemParts( item );
		if ( ! parts ) {
			return;
		}

		clearTimers( item );
		item.classList.remove( 'is-visible' );
		item.classList.remove( 'is-pinned' );
		parts.trigger.setAttribute( 'aria-expanded', 'false' );
		parts.panel.hidden = true;
	}

	function closeAllExcept( activeItem ) {
		document.querySelectorAll( itemSelector ).forEach( function( item ) {
			if ( item !== activeItem ) {
				closeItem( item );
			}
		} );
	}

	function openItem( item ) {
		var parts = getItemParts( item );
		if ( ! parts ) {
			return;
		}

		clearTimers( item );
		closeAllExcept( item );
		item.classList.add( 'is-visible' );
		parts.trigger.setAttribute( 'aria-expanded', 'true' );
		parts.panel.hidden = false;
	}

	function scheduleOpen( item ) {
		var record = ensureTimerRecord( item );
		window.clearTimeout( record.hide );
		window.clearTimeout( record.show );
		record.show = window.setTimeout( function() {
			openItem( item );
		}, showDelay );
	}

	function scheduleClose( item ) {
		if ( isPinned( item ) ) {
			return;
		}

		var record = ensureTimerRecord( item );
		window.clearTimeout( record.show );
		window.clearTimeout( record.hide );
		record.hide = window.setTimeout( function() {
			if ( isPinned( item ) ) {
				return;
			}
			closeItem( item );
		}, hideDelay );
	}

	document.addEventListener(
		'mouseenter',
		function( event ) {
			var item = event.target.closest( itemSelector );
			if ( item ) {
				scheduleOpen( item );
			}
		},
		true
	);

	document.addEventListener(
		'mouseleave',
		function( event ) {
			var item = event.target.closest( itemSelector );
			if ( ! item ) {
				return;
			}

			if ( event.relatedTarget && item.contains( event.relatedTarget ) ) {
				return;
			}

			scheduleClose( item );
		},
		true
	);

	document.addEventListener( 'focusin', function( event ) {
		var item = event.target.closest( itemSelector );
		if ( item ) {
			openItem( item );
		}
	} );

	document.addEventListener( 'focusout', function( event ) {
		var item = event.target.closest( itemSelector );
		if ( ! item ) {
			return;
		}

		if ( event.relatedTarget && item.contains( event.relatedTarget ) ) {
			return;
		}

		scheduleClose( item );
	} );

	document.addEventListener( 'click', function( event ) {
		var trigger = event.target.closest( triggerSelector );
		if ( trigger ) {
			var item = trigger.closest( itemSelector );
			if ( ! item ) {
				return;
			}

			event.preventDefault();
			if ( isPinned( item ) ) {
				closeItem( item );
			} else {
				openItem( item );
				item.classList.add( 'is-pinned' );
			}
			return;
		}

		if ( ! event.target.closest( itemSelector ) ) {
			closeAllExcept( null );
		}
	} );

	document.addEventListener( 'keydown', function( event ) {
		var isEscapeKey = 'Escape' === event.key || 'Esc' === event.key || 27 === event.keyCode;
		if ( ! isEscapeKey ) {
			return;
		}

		var activeItem = document.querySelector( itemSelector + '.is-visible' );
		closeAllExcept( null );
		if ( activeItem ) {
			var parts = getItemParts( activeItem );
			if ( parts ) {
				parts.trigger.focus();
			}
		}
	} );
}() );
