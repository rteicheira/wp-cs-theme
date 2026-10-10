/**
 * RussTeicheira Theme — spam-protection.js
 * Cloudflare Turnstile / Google reCAPTCHA for the contact and comment forms
 * (Settings → Spam Protection; markup from rt_captcha_field_html()).
 *
 * The provider script loads the first time a visitor focuses a protected form
 * (reCAPTCHA v2: when the form nears the viewport, since its checkbox must be
 * visible). Before sending, RTCaptcha.token( form ) puts a fresh token in the
 * hidden rt_captcha_token field. main.js calls it for the AJAX contact form;
 * native-submit forms (comments) are intercepted here.
 *
 * If the provider can't load (blocked or offline) the form is sent without a
 * token; the server lets it through flagged rather than losing it.
 */
( function () {
  'use strict';

  var boxes = document.querySelectorAll( '.rt-captcha' );
  if ( ! boxes.length ) return;

  var mode    = boxes[ 0 ].dataset.mode;    // turnstile | recaptcha-v3 | recaptcha-v2
  var siteKey = boxes[ 0 ].dataset.sitekey;
  var l10n    = window.RTCaptchaL10n || {};
  var loading = null;

  // How long to wait for Turnstile to finish its background check on submit.
  var TURNSTILE_WAIT_MS = 15000;

  var SCRIPTS = {
    'turnstile':    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
    'recaptcha-v3': 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent( siteKey ),
    'recaptcha-v2': 'https://www.google.com/recaptcha/api.js?render=explicit'
  };

  function load() {
    if ( ! loading ) {
      loading = new Promise( function ( resolve, reject ) {
        var script   = document.createElement( 'script' );
        script.src   = SCRIPTS[ mode ];
        script.async = true;
        script.onload = function () {
          if ( mode === 'turnstile' ) {
            window.turnstile ? resolve() : reject();
          } else {
            window.grecaptcha ? window.grecaptcha.ready( resolve ) : reject();
          }
        };
        script.onerror = reject;
        document.head.appendChild( script );
      } );
    }
    return loading;
  }

  // Error flagged so the caller shows its message instead of a generic one.
  function needsVisitor() {
    var err = new Error( l10n.incomplete || 'Please complete the security check.' );
    err.rtCaptcha = true;
    return err;
  }

  // ── PER-FORM STATE ─────────────────────────────────────────
  var states = [];

  function stateFor( form ) {
    for ( var i = 0; i < states.length; i++ ) {
      if ( states[ i ].form === form ) return states[ i ];
    }
    return null;
  }

  function settle( state, token ) {
    state.token = token;
    var waiters = state.waiters;
    state.waiters = [];
    waiters.forEach( function ( fn ) { fn(); } );
  }

  // Draw the widget (Turnstile, reCAPTCHA v2) once the script is ready.
  function render( state ) {
    if ( state.widget !== null || mode === 'recaptcha-v3' ) return;
    var slot = state.box.querySelector( '.rt-captcha__widget' );

    if ( mode === 'turnstile' ) {
      state.widget = window.turnstile.render( slot, {
        sitekey:          siteKey,
        action:           state.box.dataset.action,
        appearance:       'interaction-only',
        'response-field': false,
        callback:         function ( token ) { state.interactive = state.failed = false; settle( state, token ); },
        'expired-callback': function () { state.token = ''; },
        'before-interactive-callback': function () { state.interactive = true; },
        'error-callback': function () { state.failed = true; settle( state, '' ); return true; }
      } );
    } else {
      state.widget = window.grecaptcha.render( slot, {
        sitekey:            siteKey,
        callback:           function ( token ) { state.token = token; },
        'expired-callback': function () { state.token = ''; }
      } );
    }
  }

  function start( state ) {
    return load().then( function () { render( state ); } );
  }

  // Resolve once Turnstile has a token (or gave up), or after a timeout.
  function waitForTurnstile( state ) {
    return new Promise( function ( resolve, reject ) {
      if ( state.token ) return resolve( state.token );
      if ( state.failed ) return resolve( '' );
      if ( state.interactive ) return reject( needsVisitor() );
      var timer = setTimeout( function () {
        state.waiters = state.waiters.filter( function ( fn ) { return fn !== done; } );
        // Waiting on the visitor to click the challenge vs. a stuck check.
        state.interactive ? reject( needsVisitor() ) : resolve( '' );
      }, TURNSTILE_WAIT_MS );
      function done() {
        clearTimeout( timer );
        resolve( state.token );
      }
      state.waiters.push( done );
    } );
  }

  function fetchToken( state ) {
    return start( state ).then( function () {
      if ( mode === 'recaptcha-v3' ) {
        return window.grecaptcha.execute( siteKey, { action: state.box.dataset.action } );
      }
      if ( mode === 'recaptcha-v2' ) {
        var token = window.grecaptcha.getResponse( state.widget );
        if ( ! token ) throw needsVisitor();
        return token;
      }
      return waitForTurnstile( state );
    }, function () {
      return ''; // Script blocked: send without a token.
    } );
  }

  // ── PUBLIC API (used by main.js) ───────────────────────────
  window.RTCaptcha = {
    /**
     * Fill the form's token field. Rejects (err.rtCaptcha) only when the
     * visitor has to do something first; otherwise always resolves.
     */
    token: function ( form ) {
      var state = stateFor( form );
      if ( ! state ) return Promise.resolve();
      state.msg.hidden = true;
      return fetchToken( state ).then( function ( token ) {
        state.input.value = token || '';
      }, function ( err ) {
        if ( err && err.rtCaptcha ) throw err;
        state.input.value = '';
      } );
    },

    // Tokens are single-use: clear after an AJAX send so the next one gets a fresh one.
    reset: function ( form ) {
      var state = stateFor( form );
      // Nothing was sent (e.g. still waiting on the visitor): keep the widget as is.
      if ( ! state || ! state.input.value ) return;
      state.input.value = '';
      state.token = '';
      state.failed = false;
      if ( state.widget === null ) return;
      if ( mode === 'turnstile' && window.turnstile ) window.turnstile.reset( state.widget );
      if ( mode === 'recaptcha-v2' && window.grecaptcha ) window.grecaptcha.reset( state.widget );
    }
  };

  // ── WIRE UP FORMS ──────────────────────────────────────────
  boxes.forEach( function ( box ) {
    var form = box.closest( 'form' );
    if ( ! form ) return;

    var state = {
      box:         box,
      form:        form,
      input:       box.querySelector( 'input[name="rt_captcha_token"]' ),
      msg:         box.querySelector( '.rt-captcha__msg' ),
      widget:      null,
      token:       '',
      interactive: false,
      failed:      false,
      waiters:     []
    };
    states.push( state );

    if ( mode === 'recaptcha-v2' && 'IntersectionObserver' in window ) {
      var io = new IntersectionObserver( function ( entries ) {
        if ( entries[ 0 ].isIntersecting ) {
          io.disconnect();
          start( state ).catch( function () {} );
        }
      }, { rootMargin: '300px' } );
      io.observe( box );
    } else if ( mode === 'recaptcha-v2' ) {
      start( state ).catch( function () {} );
    } else {
      form.addEventListener( 'focusin', function () {
        start( state ).catch( function () {} );
      }, { once: true } );
    }

    if ( ! box.dataset.nativeSubmit ) return;

    // Native-submit form (comments): get a token, then submit for real.
    var button = form.querySelector( '[type="submit"]' );

    form.addEventListener( 'submit', function ( e ) {
      if ( form.dataset.rtVerified === '1' ) return;
      e.preventDefault();
      if ( button ) button.disabled = true;

      window.RTCaptcha.token( form ).then( function () {
        form.dataset.rtVerified = '1';
        // The comment form's button is named "submit", which shadows form.submit().
        HTMLFormElement.prototype.submit.call( form );
      }, function ( err ) {
        if ( button ) button.disabled = false;
        state.msg.textContent = err.message;
        state.msg.hidden = false;
      } );
    } );

    // Back/forward cache can restore a submitted form with its used token
    // and disabled button; reset so the next submit gets a fresh token.
    window.addEventListener( 'pageshow', function ( event ) {
      if ( ! event.persisted ) return;
      delete form.dataset.rtVerified;
      if ( button ) button.disabled = false;
      window.RTCaptcha.reset( form );
    } );
  } );
} )();
