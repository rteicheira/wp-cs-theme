/**
 * About section skills tag widget: search, add, remove, keyboard nav.
 * Reads initial state from data-all / data-selected JSON attributes on #rt-skills-wrap.
 */
wp.domReady( function () {
	var wrap = document.getElementById( 'rt-skills-wrap' );
	if ( ! wrap ) {
		return;
	}

	var ALL      = JSON.parse( wrap.dataset.all || '[]' );
	var selected = JSON.parse( wrap.dataset.selected || '[]' );
	var input    = document.getElementById( 'rt-skills-input' );
	var dropdown = document.getElementById( 'rt-skills-dropdown' );
	var pillsEl  = document.getElementById( 'rt-skills-pills' );
	var hiddenEl = document.getElementById( 'rt-skills-hidden' );

	function focusedIndex() {
		var items = dropdown.querySelectorAll( 'li' );
		for ( var i = 0; i < items.length; i++ ) {
			if ( items[ i ].classList.contains( 'rt-focused' ) ) {
				return i;
			}
		}
		return -1;
	}

	function render() {
		pillsEl.innerHTML = '';
		hiddenEl.innerHTML = '';
		selected.forEach( function ( t ) {
			var pill = document.createElement( 'span' );
			pill.className = 'rt-tag-pill';
			var label = document.createTextNode( t.name );
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'rt-tag-pill__remove';
			btn.setAttribute( 'aria-label', 'Remove ' + t.name );
			btn.textContent = '×';
			( function ( term ) {
				btn.addEventListener( 'click', function () {
					selected = selected.filter( function ( s ) { return s.id !== term.id; } );
					render();
				} );
			} )( t );
			pill.appendChild( label );
			pill.appendChild( btn );
			pillsEl.appendChild( pill );
			var inp = document.createElement( 'input' );
			inp.type  = 'hidden';
			inp.name  = 'rt_sections[about][skills][]';
			inp.value = t.id;
			hiddenEl.appendChild( inp );
		} );
	}

	function buildDropdown( q ) {
		dropdown.innerHTML = '';
		var lcq = q.toLowerCase();
		var usedIds = selected.map( function ( s ) { return s.id; } );
		var matches = ALL.filter( function ( t ) {
			return t.name.toLowerCase().indexOf( lcq ) !== -1
				&& usedIds.indexOf( t.id ) === -1;
		} ).slice( 0, 8 );
		var exactMatch = ALL.some( function ( t ) {
			return t.name.toLowerCase() === lcq;
		} );
		if ( q && ! exactMatch ) {
			var li = document.createElement( 'li' );
			li.className = 'rt-add-new';
			li.textContent = 'Add “' + q + '”';
			( function ( name ) {
				li.addEventListener( 'mousedown', function ( e ) {
					e.preventDefault(); addNew( name );
				} );
			} )( q );
			dropdown.appendChild( li );
		}
		matches.forEach( function ( t ) {
			var li = document.createElement( 'li' );
			li.textContent = t.name;
			( function ( term ) {
				li.addEventListener( 'mousedown', function ( e ) {
					e.preventDefault(); addExisting( term );
				} );
			} )( t );
			dropdown.appendChild( li );
		} );
		dropdown.style.display = dropdown.children.length > 0 ? 'block' : 'none';
	}

	function hideDropdown() {
		dropdown.style.display = 'none';
		dropdown.innerHTML = '';
	}

	function addExisting( term ) {
		if ( ! selected.some( function ( s ) { return s.id === term.id; } ) ) {
			selected.push( term );
		}
		input.value = '';
		hideDropdown();
		render();
	}

	function addNew( name ) {
		name = name.trim();
		if ( ! name ) {
			return;
		}
		var id = 'new:' + name;
		if ( ! selected.some( function ( s ) { return s.id === id || s.name.toLowerCase() === name.toLowerCase(); } ) ) {
			selected.push( { id: id, name: name } );
		}
		input.value = '';
		hideDropdown();
		render();
	}

	input.addEventListener( 'input', function () {
		var q = this.value.trim();
		if ( q.length > 0 ) {
			buildDropdown( q );
		} else {
			hideDropdown();
		}
	} );

	input.addEventListener( 'keydown', function ( e ) {
		var items = dropdown.querySelectorAll( 'li' );
		var idx   = focusedIndex();
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			if ( dropdown.style.display === 'none' && input.value.trim() ) {
				buildDropdown( input.value.trim() ); return;
			}
			items.forEach( function ( i ) { i.classList.remove( 'rt-focused' ); } );
			var next = idx < items.length - 1 ? idx + 1 : 0;
			if ( items[ next ] ) {
				items[ next ].classList.add( 'rt-focused' );
			}
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			items.forEach( function ( i ) { i.classList.remove( 'rt-focused' ); } );
			var prev = idx > 0 ? idx - 1 : items.length - 1;
			if ( items[ prev ] ) {
				items[ prev ].classList.add( 'rt-focused' );
			}
		} else if ( e.key === 'Enter' ) {
			e.preventDefault();
			if ( idx >= 0 && items[ idx ] ) {
				items[ idx ].dispatchEvent( new MouseEvent( 'mousedown' ) );
			} else {
				var q = input.value.trim().replace( /,\s*$/, '' );
				if ( q ) {
					var exact = null;
					for ( var i = 0; i < ALL.length; i++ ) {
						if ( ALL[ i ].name.toLowerCase() === q.toLowerCase() ) {
							exact = ALL[ i ]; break;
						}
					}
					if ( exact ) {
						addExisting( exact );
					} else {
						addNew( q );
					}
				}
			}
		} else if ( e.key === 'Backspace' && ! input.value && selected.length ) {
			selected = selected.slice( 0, -1 );
			render();
		} else if ( e.key === 'Escape' ) {
			hideDropdown();
		}
	} );

	input.addEventListener( 'blur', function () {
		setTimeout( hideDropdown, 150 );
	} );

	wrap.addEventListener( 'click', function ( e ) {
		if ( e.target !== input ) {
			input.focus();
		}
	} );

	render();
} );
