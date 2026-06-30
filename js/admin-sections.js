/**
 * Sections admin page — color picker, media uploader, tab nav, reset button.
 * Depends on: wp-dom-ready, wp-element, wp-components, jquery
 * Strings injected via wp_localize_script as window.rtSectionsL10n
 */
wp.domReady( function () {
	var el       = wp.element.createElement;
	var useState = wp.element.useState;
	var Popover  = wp.components.Popover;
	var Picker   = wp.components.ColorPicker;

	// ── Color picker swatch + popover ─────────────────────────────
	function ColorField( props ) {
		var s1     = useState( false );
		var isOpen = s1[0], setOpen = s1[1];
		var s2     = useState( props.value || props.defaultColor || '' );
		var color  = s2[0], setColor = s2[1];

		var swatchBg = color || props.defaultColor || 'repeating-conic-gradient(#ccc 0% 25%,#fff 0% 50%) 0 0/8px 8px';

		return el( 'span', { style: { display: 'inline-flex', alignItems: 'center', gap: '8px', position: 'relative' } },
			el( 'button', {
				type:       'button',
				onClick:    function() { setOpen( !isOpen ); },
				style: {
					width:        '28px',
					height:       '28px',
					background:   swatchBg,
					border:       '1px solid #8c8f94',
					borderRadius: '3px',
					cursor:       'pointer',
					padding:      0,
					flexShrink:   0,
				},
				'aria-label': rtSectionsL10n.openColorPicker,
			} ),
			el( 'span', { style: { fontFamily: 'monospace', fontSize: '12px', color: '#50575e' } },
				color || '— theme default'
			),
			isOpen && el( Popover, {
				onClose:      function() { setOpen( false ); },
				placement:    'bottom-start',
				focusOnMount: false,
			},
				el( 'div', { style: { padding: '8px 8px 0' } },
					el( Picker, {
						color:       color || props.defaultColor || '#000000',
						onChange:    function( c ) { setColor( c ); props.onChange( c ); },
						enableAlpha: true,
						copyFormat:  'hex',
					} )
				),
				el( 'div', { style: { padding: '4px 8px 8px', borderTop: '1px solid #e2e4e7', marginTop: '4px', textAlign: 'right' } },
					el( 'button', {
						type:      'button',
						className: 'button button-small',
						onClick:   function() { setColor( '' ); props.onChange( '' ); setOpen( false ); },
					}, rtSectionsL10n.resetToDefault )
				)
			)
		);
	}

	document.querySelectorAll( '.rt-color-field' ).forEach( function ( wrap ) {
		var input    = wrap.querySelector( '.rt-color-input' );
		var mount    = wrap.querySelector( '.rt-color-picker-mount' );
		var defColor = wrap.dataset.defaultColor || '';
		var comp     = el( ColorField, {
			value:        input.value,
			defaultColor: defColor,
			onChange:     function( c ) {
				input.value = ( typeof c === 'string' ) ? c : ( c && c.hex ? c.hex : '' );
			},
		} );
		if ( wp.element.createRoot ) {
			wp.element.createRoot( mount ).render( comp );
		} else {
			wp.element.render( comp, mount );
		}
	} );

	// ── Background image uploader ─────────────────────────────────
	( function ( $ ) {
		$( document ).on( 'click', '.rt-bg-upload', function ( e ) {
			e.preventDefault();
			var $wrap  = $( this ).closest( '.rt-bg-image' );
			var $table = $( this ).closest( 'table' );
			var frame  = wp.media( {
				title:    rtSectionsL10n.selectBgImage,
				button:   { text: rtSectionsL10n.useAsBg },
				multiple: false,
				library:  { type: 'image' },
			} );
			frame.on( 'select', function () {
				var att   = frame.state().get( 'selection' ).first().toJSON();
				var thumb = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
				$wrap.find( '.rt-bg-id' ).val( att.id );
				$wrap.find( '.rt-bg-preview img' ).attr( 'src', thumb );
				$wrap.find( '.rt-bg-preview' ).show();
				$wrap.find( '.rt-bg-upload' ).text( rtSectionsL10n.changeImage );
				$wrap.find( '.rt-bg-remove' ).show();
				$table.find( '.rt-bg-fixed-row' ).show();
			} );
			frame.open();
		} );

		$( document ).on( 'click', '.rt-bg-remove', function () {
			var $wrap  = $( this ).closest( '.rt-bg-image' );
			var $table = $( this ).closest( 'table' );
			$wrap.find( '.rt-bg-id' ).val( '' );
			$wrap.find( '.rt-bg-preview' ).hide();
			$wrap.find( '.rt-bg-upload' ).text( rtSectionsL10n.uploadSelectImage );
			$( this ).hide();
			$table.find( '.rt-bg-fixed-row' ).hide();
		} );
	} )( jQuery );

	// ── Tab navigation ────────────────────────────────────────────
	document.querySelectorAll( '.rt-tab-nav .nav-tab' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var nav     = tab.closest( '.rt-tab-nav' );
			var wrapper = tab.closest( '.rt-tab-wrapper' );
			nav.querySelectorAll( '.nav-tab' ).forEach( function ( t ) {
				t.classList.remove( 'nav-tab-active' );
			} );
			tab.classList.add( 'nav-tab-active' );
			wrapper.querySelectorAll( '.rt-tab-panel' ).forEach( function ( panel ) {
				panel.style.display = 'none';
			} );
			var target = document.getElementById( tab.dataset.tab );
			if ( target ) { target.style.display = 'block'; }
		} );
	} );

	// ── Reset colors button ───────────────────────────────────────
	var resetBtn = document.getElementById( 'rt-reset-colors' );
	if ( resetBtn ) {
		resetBtn.addEventListener( 'click', function () {
			if ( ! confirm( rtSectionsL10n.resetSectionColorsConfirm ) ) { return; }
			document.querySelectorAll( '.rt-color-input' ).forEach( function ( input ) {
				input.value = '';
			} );
			document.getElementById( 'submit' ).click();
		} );
	}
} );
