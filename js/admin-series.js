/**
 * Series Manager — sortable post ordering + AJAX save.
 * Depends on: jquery, jquery-ui-sortable
 * Strings injected via wp_localize_script as window.rtSeriesL10n
 */
( function ( $ ) {
	'use strict';

	if ( typeof rtSeriesL10n === 'undefined' ) { return; }

	$( function () {

		// Initialise drag-and-drop on every series list on the page.
		$( '.rt-series-list' ).sortable( {
			handle:      '.rt-series-item__handle',
			placeholder: 'rt-series-item rt-series-item--placeholder',
			axis:        'y',
			tolerance:   'pointer',
			cursor:      'grabbing',
		} );

		// Save order for a single series card.
		$( document ).on( 'click', '.rt-save-order', function () {
			var $btn    = $( this );
			var $card   = $btn.closest( '.rt-series-card' );
			var $list   = $card.find( '.rt-series-list' );
			var $status = $card.find( '.rt-save-status' );
			var seriesId = $card.data( 'series-id' );

			var ids = $list.find( '.rt-series-item' ).map( function () {
				return $( this ).data( 'post-id' );
			} ).get().join( ',' );

			$btn.prop( 'disabled', true ).text( rtSeriesL10n.saving );
			$status.text( '' ).removeClass( 'is-success is-error' );

			$.post( rtSeriesL10n.ajaxUrl, {
				action:    'rt_save_series_order',
				nonce:     rtSeriesL10n.nonce,
				series_id: seriesId,
				order:     ids,
			} )
			.done( function ( res ) {
				if ( res.success ) {
					$status.text( rtSeriesL10n.saved ).addClass( 'is-success' );
				} else {
					$status.text( rtSeriesL10n.saveError ).addClass( 'is-error' );
				}
			} )
			.fail( function () {
				$status.text( rtSeriesL10n.saveError ).addClass( 'is-error' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false ).text( rtSeriesL10n.saveOrder );
				setTimeout( function () {
					$status.text( '' ).removeClass( 'is-success is-error' );
				}, 4000 );
			} );
		} );

	} );
} )( jQuery );
