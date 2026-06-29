/**
 * RussTeicheira — main.js
 * Nav toggle, submenus, typewriter, contact form AJAX, smooth scroll.
 */
( function () {
  'use strict';

  var nav    = document.getElementById( 'site-nav' );
  var toggle = document.getElementById( 'nav-toggle' );
  var menu   = document.getElementById( 'primary-menu' );

  // ── MOBILE NAV OPEN/CLOSE ──────────────────────────────────
  function closeNav() {
    if ( ! toggle ) return;
    toggle.setAttribute( 'aria-expanded', 'false' );
    if ( menu ) menu.classList.remove( 'is-open' );
    document.body.style.overflow = '';
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
      btn.setAttribute( 'aria-label', 'Toggle submenu' );
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
          li.querySelector( 'a' ).focus();
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

    var cursorEl = termEl.querySelector( '.cursor' );
    if ( ! cursorEl ) {
      cursorEl = document.createElement( 'span' );
      cursorEl.className = 'cursor';
      cursorEl.setAttribute( 'aria-hidden', 'true' );
    }
    var textNode = document.createTextNode( '' );
    termEl.textContent = '';
    termEl.appendChild( textNode );
    termEl.appendChild( cursorEl );

    function type() {
      var phrase = phrases[ phraseIdx ];
      if ( ! deleting ) {
        charIdx++;
        textNode.nodeValue = phrase.slice( 0, charIdx );
        if ( charIdx === phrase.length ) {
          deleting = true;
          setTimeout( type, 2200 );
          return;
        }
      } else {
        charIdx--;
        textNode.nodeValue = phrase.slice( 0, charIdx );
        if ( charIdx === 0 ) {
          deleting  = false;
          phraseIdx = ( phraseIdx + 1 ) % phrases.length;
        }
      }
      setTimeout( type, deleting ? 32 : 62 );
    }
    setTimeout( type, 900 );

  } else if ( termEl ) {
    var fallbackPhrase = ( typeof RT !== 'undefined' && Array.isArray( RT.typingPhrases ) && RT.typingPhrases.length )
      ? RT.typingPhrases[0]
      : '> securing cardholder data environments';
    var cursorEl = termEl.querySelector( '.cursor' );
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

  if ( form && typeof RT !== 'undefined' ) {
    form.addEventListener( 'submit', function ( e ) {
      e.preventDefault();

      // Client-side validation
      var valid = true;
      var required = form.querySelectorAll( '[required]' );
      required.forEach( function ( field ) {
        field.classList.remove( 'is-invalid' );
        var empty   = ! field.value.trim();
        var badMail = field.type === 'email' && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( field.value );
        if ( empty || badMail ) {
          field.classList.add( 'is-invalid' );
          valid = false;
        }
      } );
      if ( ! valid ) return;

      submit.classList.add( 'is-loading' );
      submit.disabled = true;
      status.className = 'form-status';
      status.textContent = '';

      var data = new FormData( form );
      data.append( 'action', 'rt_contact' );
      data.append( 'nonce',  RT.nonce );

      fetch( RT.ajaxUrl, { method: 'POST', body: data } )
        .then( function ( res ) { return res.json(); } )
        .then( function ( json ) {
          status.className   = 'form-status ' + ( json.success ? 'success' : 'error' );
          status.textContent = json.data.message;
          if ( json.success ) form.reset();
        } )
        .catch( function () {
          status.className   = 'form-status error';
          status.textContent = RT.contactError;
        } )
        .finally( function () {
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
      } );
    } );
  }

  // ── SMOOTH SCROLL ─────────────────────────────────────────
  document.querySelectorAll( 'a[href^="#"]' ).forEach( function ( anchor ) {
    anchor.addEventListener( 'click', function ( e ) {
      var hash   = anchor.getAttribute( 'href' );
      var target = hash && hash.length > 1 ? document.getElementById( hash.slice( 1 ) ) : null;
      if ( ! target ) return;
      e.preventDefault();
      var top = target.getBoundingClientRect().top + window.pageYOffset - 72;
      window.scrollTo( { top: top, behavior: 'smooth' } );
    } );
  } );

} )();
