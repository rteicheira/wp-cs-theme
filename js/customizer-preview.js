( function () {
    'use strict';

    // site_tagline → hero eyebrow text + footer brand tagline
    wp.customize( 'site_tagline', function ( value ) {
        value.bind( function ( to ) {
            [ '.hero__eyebrow', '.footer-tagline' ].forEach( function ( sel ) {
                var el = document.querySelector( sel );
                if ( el ) el.textContent = to;
            } );
        } );
    } );

    // Hero stats — pairs of [num selector, label selector] per stat block
    var stats = document.querySelectorAll( '.hero__stats .stat' );
    var statSettings = [
        [ 'hero_stat1_num',   0, '.stat__num'   ],
        [ 'hero_stat1_label', 0, '.stat__label' ],
        [ 'hero_stat2_num',   1, '.stat__num'   ],
        [ 'hero_stat2_label', 1, '.stat__label' ],
        [ 'hero_stat3_num',   2, '.stat__num'   ],
        [ 'hero_stat3_label', 2, '.stat__label' ],
    ];
    statSettings.forEach( function ( row ) {
        var settingId = row[0], statIdx = row[1], cls = row[2];
        wp.customize( settingId, function ( value ) {
            value.bind( function ( to ) {
                var stat = stats[ statIdx ];
                if ( stat ) {
                    var el = stat.querySelector( cls );
                    if ( el ) el.textContent = to;
                }
            } );
        } );
    } );

    // footer_copyright_name — rebuilds the © line; falls back to site title
    wp.customize( 'footer_copyright_name', function ( value ) {
        value.bind( function ( to ) {
            var el = document.querySelector( '.footer-copy' );
            if ( ! el ) return;
            var year = new Date().getFullYear();
            var name = to || wp.customize( 'blogname' ).get();
            el.textContent = '© ' + year + ' ' + name + '.';
        } );
    } );

    // footer_credit — span is conditionally rendered; create or remove as needed
    wp.customize( 'footer_credit', function ( value ) {
        value.bind( function ( to ) {
            var copy = document.querySelector( '.footer-copy' );
            if ( ! copy ) return;
            var built = document.querySelector( '.footer-built' );
            if ( to ) {
                if ( ! built ) {
                    built = document.createElement( 'span' );
                    built.className = 'footer-built';
                    copy.parentNode.appendChild( built );
                }
                built.textContent = to;
            } else if ( built ) {
                built.parentNode.removeChild( built );
            }
        } );
    } );

    // Site Colors — update CSS custom properties live on the <html> element,
    // which overrides the :root block in main.css without a page reload.
    var colorVarMap = {
        'color_navy':     '--navy',
        'color_navy_mid': '--navy-mid',
        'color_teal':     '--teal',
        'color_teal_lt':  '--teal-lt',
        'color_gold':     '--gold',
        'color_gold_lt':  '--gold-lt',
        'color_offwhite': '--offwhite',
    };
    Object.keys( colorVarMap ).forEach( function ( id ) {
        var cssVar = colorVarMap[ id ];
        wp.customize( id, function ( value ) {
            value.bind( function ( newVal ) {
                document.documentElement.style.setProperty( cssVar, newVal );
            } );
        } );
    } );

} )();
