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

    // Helper: write/update a rule inside a dedicated <style> tag keyed by id.
    function liveStyle( id, css ) {
        var el = document.getElementById( id );
        if ( ! el ) {
            el = document.createElement( 'style' );
            el.id = id;
            document.head.appendChild( el );
        }
        el.textContent = css;
    }

    // site_tagline_color — hero eyebrow text + dash + footer tagline
    wp.customize( 'site_tagline_color', function ( value ) {
        value.bind( function ( to ) {
            var hex = safeHex( to );
            if ( hex ) {
                liveStyle( 'rt-tagline-color',
                    '.hero__eyebrow,.footer-tagline{color:' + hex + '}' +
                    '.hero__eyebrow::before{background:' + hex + '}'
                );
            } else {
                liveStyle( 'rt-tagline-color', '' );
            }
        } );
    } );

    // hero_desc_color — hero description paragraph
    wp.customize( 'hero_desc_color', function ( value ) {
        value.bind( function ( to ) {
            var hex = safeHex( to );
            liveStyle( 'rt-desc-color', hex ? '.hero__desc{color:' + hex + '}' : '' );
        } );
    } );

    // Returns the hex value only if it is a valid 3- or 6-digit CSS hex color.
    function safeHex( hex ) {
        return /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test( hex ) ? hex : null;
    }

    // Converts a validated hex + numeric opacity into an rgba() string.
    function hexToRgba( hex, opacity ) {
        var h = hex.replace( '#', '' );
        if ( 3 === h.length ) { h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2]; }
        return 'rgba(' +
            parseInt( h.substring( 0, 2 ), 16 ) + ',' +
            parseInt( h.substring( 2, 4 ), 16 ) + ',' +
            parseInt( h.substring( 4, 6 ), 16 ) + ',' +
            parseFloat( opacity ) + ')';
    }

    // Hero section colors
    wp.customize( 'hero_bg_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-hero-bg-color', c ? '.hero{background-color:' + c + '}' : '' );
        } );
    } );
    wp.customize( 'hero_name_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-hero-name', c ? '.hero__name{color:' + c + '}' : '' );
        } );
    } );
    wp.customize( 'hero_terminal_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-hero-terminal', c ? '.hero__terminal{color:' + c + '}' : '' );
        } );
    } );
    wp.customize( 'hero_stat_num_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-hero-stat-num', c ? '.hero .stat__num{color:' + c + '}' : '' );
        } );
    } );
    wp.customize( 'hero_stat_label_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-hero-stat-label', c ? '.hero .stat__label{color:' + c + '}' : '' );
        } );
    } );

    // Footer colors
    function updateFooterBg() {
        var hex     = safeHex( wp.customize( 'footer_bg_color' ).get() );
        var opacity = parseFloat( wp.customize( 'footer_bg_opacity' ).get() );
        if ( ! hex ) { return; }
        var css = ( opacity < 1 ) ? hexToRgba( hex, opacity ) : hex;
        liveStyle( 'rt-footer-bg', '.site-footer{background:' + css + '}' );
    }
    wp.customize( 'footer_bg_color',   function ( value ) { value.bind( updateFooterBg ); } );
    wp.customize( 'footer_bg_opacity', function ( value ) { value.bind( updateFooterBg ); } );

    wp.customize( 'footer_text_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-footer-text', c ? '.footer-tagline,.footer-bottom p{color:' + c + '}' : '' );
        } );
    } );

    // Navigation colors
    function updateNavBg() {
        var hex     = safeHex( wp.customize( 'nav_bg_color' ).get() );
        var opacity = parseFloat( wp.customize( 'nav_bg_opacity' ).get() );
        if ( ! hex ) { return; }
        liveStyle( 'rt-nav-bg', '.site-nav{background:' + hexToRgba( hex, opacity ) + '}' );
    }
    wp.customize( 'nav_bg_color',   function ( value ) { value.bind( updateNavBg ); } );
    wp.customize( 'nav_bg_opacity', function ( value ) { value.bind( updateNavBg ); } );

    wp.customize( 'nav_link_color', function ( value ) {
        value.bind( function ( to ) {
            var c = safeHex( to );
            liveStyle( 'rt-nav-link', c ? '.nav-links li a{color:' + c + '}' : '' );
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
