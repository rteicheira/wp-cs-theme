/**
 * Admin field character counters — capability and expertise edit screens.
 * Config injected via wp_localize_script as window.rtFieldCounters.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	var cfg = window.rtFieldCounters || {};

	/**
	 * Attach a live character counter beneath a text field.
	 *
	 * @param {HTMLElement|null} el    The input/textarea to watch.
	 * @param {number}           max   Hard character limit (also sets maxlength).
	 * @param {number}           warn  Length at which the counter turns amber.
	 * @param {string}           hint  Shown in the empty-state label (e.g. "50 chars = one line").
	 */
	function makeCounter( el, max, warn, hint ) {
		if ( ! el ) { return; }

		el.setAttribute( 'maxlength', max );

		var counter       = document.createElement( 'p' );
		counter.className = 'description';
		counter.style.marginTop = '4px';
		el.parentNode.insertBefore( counter, el.nextSibling );

		function update() {
			var len  = el.value.length;
			var left = max - len;

			if ( len === 0 ) {
				counter.style.color  = '';
				counter.textContent  = hint
					? '0 / ' + max + ' — ' + hint
					: '0 / ' + max + ' characters';
			} else if ( len <= warn ) {
				counter.style.color  = '#1e7e34';
				counter.textContent  = len + ' / ' + max;
			} else if ( len <= max ) {
				counter.style.color  = '#856404';
				counter.textContent  = len + ' / ' + max + ' — ' + left + ' remaining';
			} else {
				counter.style.color  = '#cc1818';
				counter.textContent  = len + ' / ' + max + ' — ' + Math.abs( left ) + ' over limit';
			}
		}

		el.addEventListener( 'input', update );
		update();
	}

	makeCounter(
		document.getElementById( 'title' ),
		parseInt( cfg.titleMax,    10 ) || 40,
		parseInt( cfg.titleWarn,   10 ) || 30,
		''
	);

	makeCounter(
		document.getElementById( 'excerpt' ),
		parseInt( cfg.excerptMax,  10 ) || 100,
		parseInt( cfg.excerptWarn, 10 ) || 50,
		cfg.excerptHint || ''
	);
} );
