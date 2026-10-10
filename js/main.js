/**
 * RussTeicheira — main.js
 * Nav toggle, submenus, typewriter, contact form AJAX, smooth scroll.
 */
// Disable scroll restoration so Chrome won't replay a horizontal scroll
// position into the html scroll container created by overflow-x:hidden.
if ( 'scrollRestoration' in history ) {
  history.scrollRestoration = 'manual';
}

( function () {
  'use strict';

  var nav    = document.getElementById( 'site-nav' );
  var toggle = document.getElementById( 'nav-toggle' );
  var menu   = document.getElementById( 'primary-menu' );

  // ── SEARCH OVERLAY ─────────────────────────────────────────
  var searchToggle  = document.getElementById( 'search-toggle' );
  var searchOverlay = document.getElementById( 'search-overlay' );
  var searchClose   = document.getElementById( 'search-overlay-close' );
  var searchInput   = document.getElementById( 'search-overlay-input' );

  function openSearch() {
    if ( ! searchOverlay ) return;
    closeNav();
    searchOverlay.classList.add( 'is-open' );
    searchOverlay.removeAttribute( 'aria-hidden' );
    if ( searchToggle ) searchToggle.setAttribute( 'aria-expanded', 'true' );
    document.body.style.overflow = 'hidden';
    // rAF ensures element is visible before focusing (avoids scroll jump)
    requestAnimationFrame( function () {
      if ( searchInput ) searchInput.focus();
    } );
  }

  function closeSearch() {
    if ( ! searchOverlay ) return;
    searchOverlay.classList.remove( 'is-open' );
    searchOverlay.setAttribute( 'aria-hidden', 'true' );
    if ( searchToggle ) {
      searchToggle.setAttribute( 'aria-expanded', 'false' );
      searchToggle.focus();
    }
    document.body.style.overflow = '';
  }

  if ( searchToggle ) {
    searchToggle.addEventListener( 'click', function () {
      searchOverlay && searchOverlay.classList.contains( 'is-open' ) ? closeSearch() : openSearch();
    } );
  }
  if ( searchClose ) {
    searchClose.addEventListener( 'click', closeSearch );
  }
  if ( searchOverlay ) {
    searchOverlay.addEventListener( 'click', function ( e ) {
      if ( e.target === searchOverlay ) closeSearch();
    } );
    // Focus trap: keep Tab inside overlay when open
    searchOverlay.addEventListener( 'keydown', function ( e ) {
      if ( e.key !== 'Tab' || ! searchOverlay.classList.contains( 'is-open' ) ) return;
      var focusable = searchOverlay.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      var first = focusable[0];
      var last  = focusable[ focusable.length - 1 ];
      if ( e.shiftKey ) {
        if ( document.activeElement === first ) { e.preventDefault(); last.focus(); }
      } else {
        if ( document.activeElement === last ) { e.preventDefault(); first.focus(); }
      }
    } );
  }

  // Keyboard shortcut: / or Cmd+K opens search; Escape closes it
  document.addEventListener( 'keydown', function ( e ) {
    if ( searchOverlay && searchOverlay.classList.contains( 'is-open' ) ) {
      if ( e.key === 'Escape' ) closeSearch();
      return;
    }
    var ae  = document.activeElement;
    var tag = ae ? ae.tagName : '';
    if ( ( e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA' && ! ( ae && ae.isContentEditable ) ) ||
         ( ( e.metaKey || e.ctrlKey ) && e.key === 'k' ) ) {
      e.preventDefault();
      openSearch();
    }
  } );

  // ── MOBILE NAV OPEN/CLOSE ──────────────────────────────────
  function closeNav() {
    if ( ! toggle ) return;
    toggle.setAttribute( 'aria-expanded', 'false' );
    if ( menu ) menu.classList.remove( 'is-open' );
    if ( ! searchOverlay || ! searchOverlay.classList.contains( 'is-open' ) ) {
      document.body.style.overflow = '';
    }
  }

  if ( toggle && menu ) {
    toggle.addEventListener( 'click', function () {
      var open = toggle.getAttribute( 'aria-expanded' ) === 'true';
      toggle.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
      menu.classList.toggle( 'is-open', ! open );
      document.body.style.overflow = open ? '' : 'hidden';
    } );

    document.addEventListener( 'click', function ( e ) {
      if ( nav && ! nav.contains( e.target ) ) {
        closeNav();
      }
    } );

    document.addEventListener( 'keydown', function ( e ) {
      if ( e.key === 'Escape' ) {
        if ( searchOverlay && searchOverlay.classList.contains( 'is-open' ) ) return;
        closeNav();
        if ( toggle ) toggle.focus();
      }
    } );
  }

  // ── SUBMENU: inject toggle buttons + handle mobile expand ──
  if ( menu ) {
    var parents = menu.querySelectorAll( '.menu-item-has-children' );

    parents.forEach( function ( li ) {
      var subMenu = li.querySelector( '.sub-menu' );
      if ( ! subMenu ) return;

      // Give the sub-menu an ID for aria-controls
      var subId = 'submenu-' + Math.random().toString(36).slice(2,7);
      subMenu.setAttribute( 'id', subId );

      // Create a toggle button (visible only on mobile via CSS)
      var btn = document.createElement( 'button' );
      btn.className        = 'sub-menu-toggle';
      btn.setAttribute( 'aria-expanded', 'false' );
      btn.setAttribute( 'aria-controls', subId );
      var parentLink = li.querySelector( 'a' );
      var parentText = parentLink ? parentLink.textContent.trim() : '';
      btn.setAttribute( 'aria-label', parentText ? 'Toggle ' + parentText + ' submenu' : 'Toggle submenu' );
      btn.innerHTML        = '▾';
      li.style.position    = 'relative';
      li.appendChild( btn );

      btn.addEventListener( 'click', function ( e ) {
        e.stopPropagation();
        var expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
        btn.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
        li.classList.toggle( 'sub-open', ! expanded );
      } );

      // Desktop: close submenu on Escape
      li.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' ) {
          li.classList.remove( 'sub-open' );
          btn.setAttribute( 'aria-expanded', 'false' );
          var link = li.querySelector( 'a' );
          if ( link ) link.focus();
        }
      } );
    } );

    // Close all submenus when clicking outside nav
    document.addEventListener( 'click', function ( e ) {
      if ( nav && ! nav.contains( e.target ) ) {
        menu.querySelectorAll( '.menu-item-has-children' ).forEach( function ( li ) {
          li.classList.remove( 'sub-open' );
          var btn = li.querySelector( '.sub-menu-toggle' );
          if ( btn ) btn.setAttribute( 'aria-expanded', 'false' );
        } );
      }
    } );

    // Close nav on any menu link click (mobile)
    menu.querySelectorAll( 'a' ).forEach( function ( a ) {
      a.addEventListener( 'click', function () {
        closeNav();
      } );
    } );
  }

  // ── NAV ACTIVE SECTION HIGHLIGHT ──────────────────────────
  var sections = document.querySelectorAll( 'section[id]' );
  var navLinks = menu ? menu.querySelectorAll( 'a[href*="#"]' ) : [];

  // Strip WP's static active classes from anchor links so only the
  // scrollspy below controls which item is highlighted.
  navLinks.forEach( function ( a ) {
    a.parentElement.classList.remove(
      'current-menu-item', 'current_page_item', 'current-menu-ancestor', 'current_page_ancestor'
    );
  } );

  if ( sections.length && navLinks.length && 'IntersectionObserver' in window ) {
    var obs = new IntersectionObserver( function ( entries ) {
      entries.forEach( function ( entry ) {
        if ( ! entry.isIntersecting ) return;
        navLinks.forEach( function ( link ) {
          var href    = link.getAttribute( 'href' ) || '';
          var isMatch = href.indexOf( '#' + entry.target.id ) !== -1;
          var parent  = link.parentElement;
          if ( isMatch ) {
            parent.classList.add( 'current-menu-item' );
          } else {
            parent.classList.remove( 'current-menu-item' );
          }
        } );
      } );
    }, { threshold: 0.35, rootMargin: '-64px 0px 0px 0px' } );

    sections.forEach( function ( s ) { obs.observe( s ); } );
  }

  // ── TYPEWRITER HERO ────────────────────────────────────────
  var termEl = document.getElementById( 'hero-terminal' );
  var prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
  var cursorEl;

  if ( termEl && ! prefersReducedMotion ) {
    var phrases = ( typeof RT !== 'undefined' && Array.isArray( RT.typingPhrases ) && RT.typingPhrases.length )
      ? RT.typingPhrases
      : [
          '> securing cardholder data environments',
          '> automating the boring stuff',
          '> docker run --rm compliance-check',
          '> grep -r "risk" /etc/security/',
          '> building things that hold up under audit',
        ];
    var phraseIdx = 0;
    var charIdx   = 0;
    var deleting  = false;

    cursorEl = termEl.querySelector( '.cursor' );
    if ( ! cursorEl ) {
      cursorEl = document.createElement( 'span' );
      cursorEl.className = 'cursor';
      cursorEl.setAttribute( 'aria-hidden', 'true' );
    }
    var textNode = document.createTextNode( '' );
    termEl.textContent = '';
    termEl.appendChild( textNode );
    termEl.appendChild( cursorEl );

    var typeTimer = null;

    function type() {
      var phrase = phrases[ phraseIdx ];
      var delay;
      if ( ! deleting ) {
        charIdx++;
        textNode.nodeValue = phrase.slice( 0, charIdx );
        if ( charIdx === phrase.length ) {
          deleting = true;
          delay    = 2200;
        } else {
          delay = 62;
        }
      } else {
        charIdx--;
        textNode.nodeValue = phrase.slice( 0, charIdx );
        if ( charIdx === 0 ) {
          deleting  = false;
          phraseIdx = ( phraseIdx + 1 ) % phrases.length;
        }
        delay = deleting ? 32 : 62;
      }
      typeTimer = setTimeout( type, delay );
    }

    // Pause the timer while the tab is hidden so it can't die from
    // aggressive browser throttling; resume cleanly on return.
    document.addEventListener( 'visibilitychange', function () {
      if ( document.hidden ) {
        clearTimeout( typeTimer );
      } else {
        clearTimeout( typeTimer );
        typeTimer = setTimeout( type, 62 );
      }
    } );

    typeTimer = setTimeout( type, 900 );

  } else if ( termEl ) {
    var fallbackPhrase = ( typeof RT !== 'undefined' && Array.isArray( RT.typingPhrases ) && RT.typingPhrases.length )
      ? RT.typingPhrases[0]
      : '> securing cardholder data environments';
    cursorEl = termEl.querySelector( '.cursor' );
    if ( ! cursorEl ) {
      cursorEl = document.createElement( 'span' );
      cursorEl.className = 'cursor';
      cursorEl.setAttribute( 'aria-hidden', 'true' );
    }
    termEl.textContent = fallbackPhrase;
    termEl.appendChild( cursorEl );
  }

  // ── CONTACT FORM AJAX ──────────────────────────────────────
  var form   = document.getElementById( 'contact-form' );
  var status = document.getElementById( 'form-status' );
  var submit = document.getElementById( 'contact-submit' );

  if ( form && submit && status && typeof RT !== 'undefined' ) {
    form.addEventListener( 'submit', function ( e ) {
      e.preventDefault();

      // Client-side validation
      var valid = true;
      var required = form.querySelectorAll( '[required]' );
      required.forEach( function ( field ) {
        field.classList.remove( 'is-invalid' );
        field.removeAttribute( 'aria-invalid' );
        var empty   = ! field.value.trim();
        var badMail = field.type === 'email' && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( field.value );
        if ( empty || badMail ) {
          field.classList.add( 'is-invalid' );
          field.setAttribute( 'aria-invalid', 'true' );
          valid = false;
        }
      } );
      if ( ! valid ) return;

      submit.classList.add( 'is-loading' );
      submit.disabled = true;
      status.className = 'form-status';
      status.textContent = '';

      // Spam protection (spam-protection.js), when enabled, fills its token field first.
      var captcha = window.RTCaptcha ? window.RTCaptcha.token( form ) : Promise.resolve();

      captcha
        .then( function () {
          var data = new FormData( form );
          data.append( 'action', 'rt_contact' );
          data.append( 'nonce',  RT.nonce );
          return fetch( RT.ajaxUrl, { method: 'POST', body: data } );
        } )
        .then( function ( res ) { return res.json(); } )
        .then( function ( json ) {
          status.className   = 'form-status ' + ( json.success ? 'success' : 'error' );
          status.textContent = ( json.data && json.data.message ) ? json.data.message : RT.contactError;
          if ( json.success ) form.reset();
        } )
        .catch( function ( err ) {
          status.className   = 'form-status error';
          status.textContent = ( err && err.rtCaptcha ) ? err.message : RT.contactError;
        } )
        .finally( function () {
          if ( window.RTCaptcha ) window.RTCaptcha.reset( form );
          submit.classList.remove( 'is-loading' );
          submit.disabled = false;
          if ( status.scrollIntoView ) {
            status.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
          }
        } );
    } );

    form.querySelectorAll( 'input, textarea' ).forEach( function ( field ) {
      field.addEventListener( 'input', function () {
        field.classList.remove( 'is-invalid' );
        field.removeAttribute( 'aria-invalid' );
      } );
    } );
  }

  // ── BACK TO TOP ────────────────────────────────────────────
  // pageshow fires after scroll restoration; reset horizontal scroll here
  // so the browser can't replay it after history.scrollRestoration='manual'.
  window.addEventListener( 'pageshow', function () {
    document.documentElement.scrollLeft = 0;
  }, { passive: true } );

  var btt = document.querySelector( '.back-to-top' );
  if ( btt ) {
    window.addEventListener( 'scroll', function () {
      btt.classList.toggle( 'is-visible', window.scrollY > 400 );
    }, { passive: true } );

    btt.addEventListener( 'click', function () {
      window.scrollTo( { top: 0, left: 0, behavior: 'smooth' } );
    } );
  }

  // ── SMOOTH SCROLL ─────────────────────────────────────────
  document.querySelectorAll( 'a[href^="#"]' ).forEach( function ( anchor ) {
    anchor.addEventListener( 'click', function ( e ) {
      var hash   = anchor.getAttribute( 'href' );
      var target = hash && hash.length > 1 ? document.getElementById( hash.slice( 1 ) ) : null;
      if ( ! target ) return;
      e.preventDefault();
      var top = target.getBoundingClientRect().top + window.pageYOffset - 85;
      window.scrollTo( { top: top, behavior: 'smooth' } );
    } );
  } );

} )();
