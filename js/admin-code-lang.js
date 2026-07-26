/**
 * Adds a "Syntax Highlighting" panel to the Core Code block inspector.
 * Stores the selected language as a language-* CSS class on the block's
 * className attribute, which the PHP render_block filter then copies to
 * the inner <code> element where Prism expects it.
 */
( function () {
	'use strict';

	var el               = wp.element.createElement;
	var Fragment         = wp.element.Fragment;
	var addFilter        = wp.hooks.addFilter;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody        = wp.components.PanelBody;
	var SelectControl    = wp.components.SelectControl;
	var __               = wp.i18n.__;

	var LANGS = [
		{ value: '',                      label: '— None (plain text) —' },
		{ value: 'language-bash',         label: 'Bash / Shell' },
		{ value: 'language-python',       label: 'Python' },
		{ value: 'language-javascript',   label: 'JavaScript' },
		{ value: 'language-typescript',   label: 'TypeScript' },
		{ value: 'language-php',          label: 'PHP' },
		{ value: 'language-css',          label: 'CSS' },
		{ value: 'language-markup',       label: 'HTML / XML' },
		{ value: 'language-json',         label: 'JSON' },
		{ value: 'language-sql',          label: 'SQL' },
		{ value: 'language-yaml',         label: 'YAML' },
		{ value: 'language-docker',       label: 'Dockerfile' },
		{ value: 'language-nginx',        label: 'Nginx config' },
		{ value: 'language-apacheconf',   label: 'Apache config' },
		{ value: 'language-powershell',   label: 'PowerShell' },
		{ value: 'language-go',           label: 'Go' },
		{ value: 'language-rust',         label: 'Rust' },
		{ value: 'language-diff',         label: 'Diff / Patch' },
		{ value: 'language-ini',          label: 'INI / Config' },
		{ value: 'language-regex',        label: 'Regex' },
	];

	function withLanguageSelector( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== 'core/code' ) {
				return el( BlockEdit, props );
			}

			var className = props.attributes.className || '';

			// Determine which language is currently active
			var active = '';
			for ( var i = 0; i < LANGS.length; i++ ) {
				if ( LANGS[ i ].value && className.indexOf( LANGS[ i ].value ) !== -1 ) {
					active = LANGS[ i ].value;
					break;
				}
			}

			function onChange( selected ) {
				// Strip any existing language-* class, then append the new one
				var stripped = className
					.replace( /\blanguage-\S+/g, '' )
					.replace( /\s+/g, ' ' )
					.trim();
				var next = selected
					? ( stripped ? stripped + ' ' + selected : selected )
					: stripped;
				props.setAttributes( { className: next || undefined } );
			}

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Syntax Highlighting', 'russteicheira' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Language', 'russteicheira' ),
							value: active,
							options: LANGS,
							onChange: onChange,
							__nextHasNoMarginBottom: true,
						} )
					)
				)
			);
		};
	}

	addFilter(
		'editor.BlockEdit',
		'russteicheira/code-language-selector',
		withLanguageSelector
	);
} )();
