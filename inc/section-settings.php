<?php
/**
 * Homepage Section Settings — dedicated admin page.
 *
 * WP Admin → Sections (top-level menu)
 *
 * Covers: About, Core Expertise, Portfolio, Blog.
 * Expertise, Portfolio, and Blog each have an Enabled toggle.
 * About is always visible.
 *
 * Individual cards (Capabilities, Expertise items) are managed
 * through their own CPT screens, not here.
 */

defined( 'ABSPATH' ) || exit;


// ── ADMIN MENU ────────────────────────────────────────────────
function rt_sections_admin_menu() {
	add_menu_page(
		__( 'Homepage Sections', 'russteicheira' ),
		__( 'Sections',          'russteicheira' ),
		'manage_options',
		'rt-sections',
		'rt_sections_page',
		'dashicons-layout',
		3
	);
}
add_action( 'admin_menu', 'rt_sections_admin_menu' );


// ── REGISTER SETTING ──────────────────────────────────────────
function rt_sections_settings_init() {
	register_setting( 'rt_sections_group', 'rt_sections', array(
		'sanitize_callback' => 'rt_sections_sanitize',
		'capability'        => 'manage_options',
	) );
}
add_action( 'admin_init', 'rt_sections_settings_init' );


// ── ADMIN STYLES (skills tag input) ───────────────────────────
function rt_sections_admin_enqueue( $hook ) {
	if ( 'toplevel_page_rt-sections' !== $hook ) {
		return;
	}
	wp_add_inline_style( 'wp-admin', '
.rt-tag-wrap {
    border: 1px solid #8c8f94;
    border-radius: 4px;
    padding: 5px 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
    cursor: text;
    background: #fff;
    min-height: 38px;
    max-width: 600px;
    position: relative;
}
.rt-tag-wrap:focus-within {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}
.rt-tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #2271b1;
    color: #fff;
    font-size: 12px;
    line-height: 1;
    padding: 4px 10px 4px 11px;
    border-radius: 12px;
    white-space: nowrap;
}
.rt-tag-pill__remove {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0 0 0 2px;
    font-size: 15px;
    line-height: 1;
    opacity: .75;
}
.rt-tag-pill__remove:hover { opacity: 1; }
#rt-skills-input {
    border: none;
    outline: none;
    padding: 3px 4px;
    min-width: 160px;
    flex: 1;
    font-size: 13px;
    background: transparent;
}
.rt-tag-dropdown {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #8c8f94;
    border-radius: 0 0 4px 4px;
    list-style: none;
    margin: 0;
    padding: 0;
    z-index: 9999;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 2px 6px rgba(0,0,0,.1);
}
.rt-tag-dropdown li {
    padding: 7px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #1d2327;
}
.rt-tag-dropdown li:hover,
.rt-tag-dropdown li.rt-focused { background: #f0f6fc; color: #2271b1; }
.rt-tag-dropdown li.rt-add-new { color: #2271b1; font-style: italic; }
' );
}
add_action( 'admin_enqueue_scripts', 'rt_sections_admin_enqueue' );


// ── SANITIZE ──────────────────────────────────────────────────

/**
 * Convert the skills array from the settings form into a comma-separated
 * string of term IDs. New terms (prefixed 'new:') are created here only
 * during an admin settings save — not during REST, cron, or import.
 */
function rt_sections_sanitize_skills( array $raw ) {
	$ids = array();
	foreach ( $raw as $val ) {
		$val = sanitize_text_field( $val );
		if ( is_numeric( $val ) ) {
			$ids[] = intval( $val );
		} elseif ( 0 === strpos( $val, 'new:' ) ) {
			// Term creation is intentionally skipped in non-admin contexts (WP-CLI, REST, cron).
			if ( ! is_admin() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				continue;
			}
			$name = sanitize_text_field( trim( substr( $val, 4 ) ) );
			if ( '' === $name ) {
				continue;
			}
			$existing = get_term_by( 'name', $name, 'skill' );
			if ( $existing ) {
				$ids[] = $existing->term_id;
			} else {
				$result = wp_insert_term( $name, 'skill' );
				if ( ! is_wp_error( $result ) ) {
					$ids[] = $result['term_id'];
				}
			}
		}
	}
	return implode( ',', $ids );
}

function rt_sections_sanitize( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$out = array();

	// About — always enabled, has body + skills
	$out['about'] = array(
		'eyebrow' => isset( $input['about']['eyebrow'] ) ? sanitize_text_field( $input['about']['eyebrow'] ) : '',
		'heading' => isset( $input['about']['heading'] ) ? sanitize_text_field( $input['about']['heading'] ) : '',
		'body'    => isset( $input['about']['body'] )    ? wp_kses_post( $input['about']['body'] ) : '',
		'skills'  => rt_sections_sanitize_skills(
			isset( $input['about']['skills'] ) && is_array( $input['about']['skills'] )
				? $input['about']['skills'] : array()
		),
	);

	// Expertise, Portfolio, Blog — enabled toggle + header text
	foreach ( array( 'expertise', 'portfolio', 'blog' ) as $s ) {
		$out[ $s ] = array(
			'enabled' => ! empty( $input[ $s ]['enabled'] ) ? '1' : '0',
			'eyebrow' => isset( $input[ $s ]['eyebrow'] ) ? sanitize_text_field( $input[ $s ]['eyebrow'] ) : '',
			'heading' => isset( $input[ $s ]['heading'] ) ? sanitize_text_field( $input[ $s ]['heading'] ) : '',
			'sub'     => isset( $input[ $s ]['sub'] )     ? sanitize_text_field( $input[ $s ]['sub'] )     : '',
		);
	}

	// Contact — always visible, heading + subtext + 5 configurable link rows
	$raw_links = ( isset( $input['contact']['links'] ) && is_array( $input['contact']['links'] ) )
	             ? $input['contact']['links'] : array();
	$contact_links = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$l = isset( $raw_links[ $i ] ) ? (array) $raw_links[ $i ] : array();
		$contact_links[] = array(
			'icon'    => isset( $l['icon'] )    ? sanitize_text_field( $l['icon'] )    : '',
			'label'   => isset( $l['label'] )   ? sanitize_text_field( $l['label'] )   : '',
			'url'     => isset( $l['url'] )     ? esc_url_raw( $l['url'] )             : '',
			'display' => isset( $l['display'] ) ? sanitize_text_field( $l['display'] ) : '',
		);
	}
	$out['contact'] = array(
		'eyebrow' => isset( $input['contact']['eyebrow'] ) ? sanitize_text_field( $input['contact']['eyebrow'] ) : '',
		'heading' => isset( $input['contact']['heading'] ) ? sanitize_text_field( $input['contact']['heading'] ) : '',
		'sub'     => isset( $input['contact']['sub'] )     ? sanitize_textarea_field( $input['contact']['sub'] ) : '',
		'links'   => $contact_links,
	);

	return $out;
}


// ── HELPERS ───────────────────────────────────────────────────

/**
 * Get one field from section settings, falling back to $default.
 *
 * @param string $section  'about' | 'expertise' | 'portfolio' | 'blog'
 * @param string $key      Field name
 * @param string $default  Fallback value
 * @return string
 */
if ( ! function_exists( 'rt_section_opt' ) ) {
	function rt_section_opt( $section, $key, $default = '' ) {
		$opts = get_option( 'rt_sections', array() );
		if ( isset( $opts[ $section ][ $key ] ) ) {
			return $opts[ $section ][ $key ];
		}
		return $default;
	}
}

/**
 * Return whether a section should be rendered on the front page.
 * About is always on. Others default to enabled until explicitly disabled.
 *
 * @param string $section
 * @return bool
 */
if ( ! function_exists( 'rt_section_enabled' ) ) {
	function rt_section_enabled( $section ) {
		if ( 'about' === $section ) {
			return true;
		}
		$opts = get_option( 'rt_sections', array() );
		if ( ! isset( $opts[ $section ]['enabled'] ) ) {
			return true; // default on before first save
		}
		return '1' === $opts[ $section ]['enabled'];
	}
}


// ── SETTINGS PAGE ─────────────────────────────────────────────
function rt_sections_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$opts = get_option( 'rt_sections', array() );

	$v = function ( $section, $key ) use ( $opts ) {
		return isset( $opts[ $section ][ $key ] ) ? $opts[ $section ][ $key ] : '';
	};

	$is_enabled = function ( $section ) use ( $opts ) {
		if ( ! isset( $opts[ $section ]['enabled'] ) ) {
			return true; // default on
		}
		return '1' === $opts[ $section ]['enabled'];
	};

	$sections = array(
		'about' => array(
			'label'  => __( 'About', 'russteicheira' ),
			'toggle' => false,
			'extra'  => 'about',
		),
		'expertise' => array(
			'label'  => __( 'Core Expertise', 'russteicheira' ),
			'toggle' => true,
			'extra'  => 'sub',
		),
		'portfolio' => array(
			'label'  => __( 'Portfolio / Projects', 'russteicheira' ),
			'toggle' => true,
			'extra'  => 'sub',
		),
		'blog' => array(
			'label'  => __( 'Blog', 'russteicheira' ),
			'toggle' => true,
			'extra'  => 'sub',
		),
		'contact' => array(
			'label'  => __( 'Get in Touch', 'russteicheira' ),
			'toggle' => false,
			'extra'  => 'contact',
		),
	);
	?>
	<div class="wrap">
		<h1><?php _e( 'Homepage Sections', 'russteicheira' ); ?></h1>
		<p class="description" style="margin-bottom:24px;">
			<?php _e( 'Edit section header text and toggle sections on or off. Individual cards (Capabilities, Expertise items) are managed through their own post-type screens.', 'russteicheira' ); ?>
		</p>

		<?php settings_errors( 'rt_sections_group' ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'rt_sections_group' ); ?>

			<?php foreach ( $sections as $key => $meta ) :
				$enabled = $is_enabled( $key );
			?>
			<div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px 24px;margin-bottom:20px;">

				<div style="display:flex;align-items:center;gap:16px;padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid #f0f0f1;">
					<h2 style="margin:0;font-size:1.05rem;"><?php echo esc_html( $meta['label'] ); ?></h2>

					<?php if ( $meta['toggle'] ) : ?>
						<label style="display:flex;align-items:center;gap:6px;font-weight:600;cursor:pointer;margin:0;">
							<input type="checkbox"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][enabled]"
								value="1"
								<?php checked( $enabled ); ?> />
							<?php _e( 'Enabled', 'russteicheira' ); ?>
						</label>
					<?php else : ?>
						<span style="color:#646970;font-size:0.825rem;font-style:italic;">
							<?php _e( 'Always visible', 'russteicheira' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<table class="form-table" style="margin-top:0;">
					<tr>
						<th style="width:160px;">
							<label for="<?php echo esc_attr( $key ); ?>_eyebrow">
								<?php _e( 'Eyebrow', 'russteicheira' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								id="<?php echo esc_attr( $key ); ?>_eyebrow"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][eyebrow]"
								value="<?php echo esc_attr( $v( $key, 'eyebrow' ) ); ?>"
								class="regular-text"
								placeholder="<?php echo esc_attr( '// ' . strtolower( $meta['label'] ) ); ?>" />
							<p class="description"><?php _e( 'Small label shown above the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo esc_attr( $key ); ?>_heading">
								<?php _e( 'Heading', 'russteicheira' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								id="<?php echo esc_attr( $key ); ?>_heading"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][heading]"
								value="<?php echo esc_attr( $v( $key, 'heading' ) ); ?>"
								class="regular-text" />
						</td>
					</tr>

					<?php if ( 'about' === $key ) : ?>
					<tr>
						<th>
							<label><?php _e( 'Body', 'russteicheira' ); ?></label>
						</th>
						<td>
							<?php
							wp_editor( $v( 'about', 'body' ), 'rt_about_body', array(
								'textarea_name' => 'rt_sections[about][body]',
								'textarea_rows' => 10,
								'media_buttons' => false,
								'teeny'         => false,
								'quicktags'     => true,
							) );
							?>
						</td>
					</tr>
					<tr>
						<th style="padding-top:14px;">
							<label><?php _e( 'Skills', 'russteicheira' ); ?></label>
						</th>
						<td>
							<?php
							$selected_ids = array_filter( array_map( 'intval', explode( ',', $v( 'about', 'skills' ) ) ) );
							$skill_terms  = get_terms( array(
								'taxonomy'   => 'skill',
								'hide_empty' => false,
								'orderby'    => 'name',
								'order'      => 'ASC',
							) );
							if ( is_wp_error( $skill_terms ) ) {
								$skill_terms = array();
							}

							// Build JS data arrays
							$selected_data = array();
							foreach ( $selected_ids as $sid ) {
								$st = get_term( $sid, 'skill' );
								if ( $st && ! is_wp_error( $st ) ) {
									$selected_data[] = array( 'id' => $st->term_id, 'name' => $st->name );
								}
							}
							$all_terms_data = array();
							foreach ( $skill_terms as $st ) {
								$all_terms_data[] = array( 'id' => $st->term_id, 'name' => $st->name );
							}
							?>
							<div class="rt-tag-wrap" id="rt-skills-wrap">
								<div id="rt-skills-pills"></div>
								<input type="text" id="rt-skills-input" autocomplete="off"
									placeholder="<?php esc_attr_e( 'Type to search or add a skill…', 'russteicheira' ); ?>">
								<ul class="rt-tag-dropdown" id="rt-skills-dropdown"></ul>
								<div id="rt-skills-hidden"></div>
							</div>
							<p class="description" style="max-width:600px;">
								<?php _e( 'Select existing skills or type a new one and press Enter to create it. Backspace removes the last tag.', 'russteicheira' ); ?>
							</p>
							<script>
							(function() {
								var ALL      = <?php echo wp_json_encode( $all_terms_data ); ?>;
								var selected = <?php echo wp_json_encode( $selected_data ); ?>;
								var wrap     = document.getElementById('rt-skills-wrap');
								var input    = document.getElementById('rt-skills-input');
								var dropdown = document.getElementById('rt-skills-dropdown');
								var pillsEl  = document.getElementById('rt-skills-pills');
								var hiddenEl = document.getElementById('rt-skills-hidden');

								function focusedIndex() {
									var items = dropdown.querySelectorAll('li');
									for (var i = 0; i < items.length; i++) {
										if (items[i].classList.contains('rt-focused')) return i;
									}
									return -1;
								}

								function render() {
									pillsEl.innerHTML = '';
									hiddenEl.innerHTML = '';
									selected.forEach(function(t) {
										var pill = document.createElement('span');
										pill.className = 'rt-tag-pill';
										var label = document.createTextNode(t.name);
										var btn = document.createElement('button');
										btn.type = 'button';
										btn.className = 'rt-tag-pill__remove';
										btn.setAttribute('aria-label', 'Remove ' + t.name);
										btn.textContent = '×';
										(function(term) {
											btn.addEventListener('click', function() {
												selected = selected.filter(function(s) { return s.id !== term.id; });
												render();
											});
										})(t);
										pill.appendChild(label);
										pill.appendChild(btn);
										pillsEl.appendChild(pill);
										var inp = document.createElement('input');
										inp.type  = 'hidden';
										inp.name  = 'rt_sections[about][skills][]';
										inp.value = t.id;
										hiddenEl.appendChild(inp);
									});
								}

								function buildDropdown(q) {
									dropdown.innerHTML = '';
									var lcq = q.toLowerCase();
									var usedIds = selected.map(function(s) { return s.id; });
									var matches = ALL.filter(function(t) {
										return t.name.toLowerCase().indexOf(lcq) !== -1
											&& usedIds.indexOf(t.id) === -1;
									}).slice(0, 8);
									var exactMatch = ALL.some(function(t) {
										return t.name.toLowerCase() === lcq;
									});
									if (q && !exactMatch) {
										var li = document.createElement('li');
										li.className = 'rt-add-new';
										li.textContent = 'Add “' + q + '”';
										(function(name) {
											li.addEventListener('mousedown', function(e) {
												e.preventDefault(); addNew(name);
											});
										})(q);
										dropdown.appendChild(li);
									}
									matches.forEach(function(t) {
										var li = document.createElement('li');
										li.textContent = t.name;
										(function(term) {
											li.addEventListener('mousedown', function(e) {
												e.preventDefault(); addExisting(term);
											});
										})(t);
										dropdown.appendChild(li);
									});
									dropdown.style.display = dropdown.children.length > 0 ? 'block' : 'none';
								}

								function hideDropdown() {
									dropdown.style.display = 'none';
									dropdown.innerHTML = '';
								}

								function addExisting(term) {
									if (!selected.some(function(s) { return s.id === term.id; })) {
										selected.push(term);
									}
									input.value = '';
									hideDropdown();
									render();
								}

								function addNew(name) {
									name = name.trim();
									if (!name) return;
									var id = 'new:' + name;
									if (!selected.some(function(s) { return s.id === id || s.name.toLowerCase() === name.toLowerCase(); })) {
										selected.push({ id: id, name: name });
									}
									input.value = '';
									hideDropdown();
									render();
								}

								input.addEventListener('input', function() {
									var q = this.value.trim();
									if (q.length > 0) { buildDropdown(q); } else { hideDropdown(); }
								});

								input.addEventListener('keydown', function(e) {
									var items = dropdown.querySelectorAll('li');
									var idx   = focusedIndex();
									if (e.key === 'ArrowDown') {
										e.preventDefault();
										if (dropdown.style.display === 'none' && input.value.trim()) {
											buildDropdown(input.value.trim()); return;
										}
										items.forEach(function(i) { i.classList.remove('rt-focused'); });
										var next = idx < items.length - 1 ? idx + 1 : 0;
										if (items[next]) items[next].classList.add('rt-focused');
									} else if (e.key === 'ArrowUp') {
										e.preventDefault();
										items.forEach(function(i) { i.classList.remove('rt-focused'); });
										var prev = idx > 0 ? idx - 1 : items.length - 1;
										if (items[prev]) items[prev].classList.add('rt-focused');
									} else if (e.key === 'Enter') {
										e.preventDefault();
										if (idx >= 0 && items[idx]) {
											items[idx].dispatchEvent(new MouseEvent('mousedown'));
										} else {
											var q = input.value.trim().replace(/,\s*$/, '');
											if (q) {
												var exact = null;
												for (var i = 0; i < ALL.length; i++) {
													if (ALL[i].name.toLowerCase() === q.toLowerCase()) { exact = ALL[i]; break; }
												}
												if (exact) { addExisting(exact); } else { addNew(q); }
											}
										}
									} else if (e.key === 'Backspace' && !input.value && selected.length) {
										selected = selected.slice(0, -1);
										render();
									} else if (e.key === 'Escape') {
										hideDropdown();
									}
								});

								input.addEventListener('blur', function() {
									setTimeout(hideDropdown, 150);
								});

								wrap.addEventListener('click', function(e) {
									if (e.target !== input) input.focus();
								});

								render();
							})();
							</script>
						</td>
					</tr>

					<?php elseif ( 'contact' === $key ) : ?>
					<tr>
						<th>
							<label for="contact_sub"><?php _e( 'Subtext', 'russteicheira' ); ?></label>
						</th>
						<td>
							<textarea id="contact_sub" name="rt_sections[contact][sub]"
								class="large-text" rows="3"><?php echo esc_textarea( $v( 'contact', 'sub' ) ); ?></textarea>
							<p class="description"><?php _e( 'Paragraph shown beneath the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<tr>
						<th style="padding-top:14px;">
							<label><?php _e( 'Links', 'russteicheira' ); ?></label>
						</th>
						<td>
							<?php
							$saved_links = isset( $opts['contact']['links'] ) && is_array( $opts['contact']['links'] )
							              ? $opts['contact']['links'] : array();
							?>
							<table style="border-collapse:collapse;width:100%;max-width:680px;">
								<thead>
									<tr>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;width:52px;"><?php _e( 'Icon', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;width:120px;"><?php _e( 'Label', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 10px 6px 0;font-size:12px;font-weight:600;color:#646970;"><?php _e( 'URL', 'russteicheira' ); ?></th>
										<th style="text-align:left;padding:0 0 6px 0;font-size:12px;font-weight:600;color:#646970;width:150px;"><?php _e( 'Display Text', 'russteicheira' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php for ( $i = 0; $i < 5; $i++ ) :
										$lnk         = isset( $saved_links[ $i ] ) ? $saved_links[ $i ] : array();
										$lnk_icon    = isset( $lnk['icon'] )    ? $lnk['icon']    : '';
										$lnk_label   = isset( $lnk['label'] )   ? $lnk['label']   : '';
										$lnk_url     = isset( $lnk['url'] )     ? $lnk['url']     : '';
										$lnk_display = isset( $lnk['display'] ) ? $lnk['display'] : '';
									?>
									<tr style="<?php echo $i > 0 ? 'border-top:1px solid #f0f0f1;' : ''; ?>">
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][icon]"
												value="<?php echo esc_attr( $lnk_icon ); ?>"
												placeholder="📄"
												style="width:46px;text-align:center;font-size:16px;" />
										</td>
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][label]"
												value="<?php echo esc_attr( $lnk_label ); ?>"
												placeholder="<?php esc_attr_e( 'Label', 'russteicheira' ); ?>"
												style="width:110px;" />
										</td>
										<td style="padding:5px 10px 5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][url]"
												value="<?php echo esc_attr( $lnk_url ); ?>"
												placeholder="https://"
												class="regular-text" />
										</td>
										<td style="padding:5px 0;">
											<input type="text"
												name="rt_sections[contact][links][<?php echo (int) $i; ?>][display]"
												value="<?php echo esc_attr( $lnk_display ); ?>"
												placeholder="<?php esc_attr_e( 'Optional', 'russteicheira' ); ?>"
												style="width:140px;" />
										</td>
									</tr>
									<?php endfor; ?>
								</tbody>
							</table>
							<p class="description" style="margin-top:8px;max-width:680px;">
								<?php _e( 'Rows with a URL appear in the contact section. Use emoji for icons (e.g. ✉️ 💼 🐙). For email, use <code>mailto:you@example.com</code> as the URL. Display text falls back to the URL if left blank.', 'russteicheira' ); ?>
							</p>
						</td>
					</tr>

					<?php else : ?>
					<tr>
						<th>
							<label for="<?php echo esc_attr( $key ); ?>_sub">
								<?php _e( 'Sub-description', 'russteicheira' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								id="<?php echo esc_attr( $key ); ?>_sub"
								name="rt_sections[<?php echo esc_attr( $key ); ?>][sub]"
								value="<?php echo esc_attr( $v( $key, 'sub' ) ); ?>"
								class="large-text" />
							<p class="description"><?php _e( 'One-sentence description shown below the heading.', 'russteicheira' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</div>
			<?php endforeach; ?>

			<?php submit_button( __( 'Save Section Settings', 'russteicheira' ) ); ?>
		</form>
	</div>
	<?php
}
