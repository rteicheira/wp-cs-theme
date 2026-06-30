# Cybersecurity & Compliance Portfolio WordPress Theme

[![CodeQL](https://github.com/rteicheira/wp-cs-theme/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/rteicheira/wp-cs-theme/actions/workflows/github-code-scanning/codeql) [![PHPMD](https://github.com/rteicheira/wp-cs-theme/actions/workflows/phpmd.yml/badge.svg)](https://github.com/rteicheira/wp-cs-theme/actions/workflows/phpmd.yml)

A custom WordPress theme for [russteicheira.net](https://russteicheira.net). While I designed this for my own use, I did try to add enough flexibility for others to use it as well.

The theme is built for a cybersecurity and compliance professional. The theme combines a personal blog with a structured portfolio using three custom post types for: Projects, Expertise, and Certifications, with a shared Skill taxonomy that links related content across the entire site.

The homepage is assembled from independently controlled sections, each with configurable visibility, color schemes, and background images managed through a dedicated admin page.

> [!important]
> This is a work in progress - use at your own risk.
>
> **Minimum Requirements:** ![Static Badge](https://img.shields.io/badge/WordPress-v._6.2%2B-green) ![Static Badge](https://img.shields.io/badge/PHP-v._7.4-blue) ![Static Badge](https://img.shields.io/badge/MySQL_/_MariaDB-v._5.7_/_10.3_-blue)
>
> **Recommended Requirements:** ![Static Badge](https://img.shields.io/badge/WordPress-v._7.0%2B-green) ![Static Badge](https://img.shields.io/badge/PHP-v._8.0-blue) ![Static Badge](https://img.shields.io/badge/MySQL_/_MariaDB-v._8.0_/_10.6_-blue)

![Screenshot](screenshot.png)

## Structure

```text
russteicheira/
├── style.css                  ← Required WP theme header
├── functions.php              ← Setup, enqueue, CPTs, taxonomies, AJAX, helpers
├── theme.json                 ← Block editor color/font palette
├── front-page.php             ← Homepage (all sections)
├── index.php                  ← Blog archive / fallback archive
├── single.php                 ← Single blog post
├── single-project.php         ← Single project page
├── page.php                   ← Static page template
├── archive-project.php        ← Project archive
├── taxonomy-skill.php         ← Skill taxonomy archive (posts + projects)
├── 404.php                    ← Not found
├── header.php                 ← <head>, nav
├── footer.php                 ← footer, wp_footer()
├── screenshot.png             ← WP admin theme preview (1200×900)
├── css/
│   ├── main.css               ← All styles (tokens → responsive)
│   ├── admin-sections.css     ← Sections admin page styles
│   ├── fonts.css              ← @font-face declarations
│   └── fonts/                 ← Self-hosted woff2 files
│       ├── inter-v20-latin.woff2
│       ├── jetbrains-mono-v24-latin.woff2
│       └── space-grotesk-v22-latin.woff2
├── js/
│   ├── main.js                ← Nav, typewriter, smooth scroll, contact AJAX
│   ├── admin-sections.js      ← Sections admin page: color picker, media uploader, tab nav
│   ├── admin-sections-skills.js ← Sections admin page: About section skills tag widget
│   ├── admin-field-counters.js ← Capability/expertise title+excerpt character counters
│   └── customizer-preview.js  ← Customizer live preview bindings
├── template-parts/
│   ├── hero.php               ← Hero / above-the-fold section
│   ├── about.php              ← About Me + capabilities panel
│   ├── expertise.php          ← Core Expertise cards
│   ├── projects.php           ← Portfolio preview (featured Projects CPT)
│   ├── blog-preview.php       ← Latest posts preview
│   ├── certs.php              ← Certifications section
│   └── contact.php            ← AJAX contact form
├── inc/
│   ├── section-settings.php   ← Sections admin page (visibility, colors, bg images)
│   ├── customizer.php         ← Customizer panels, settings, controls
│   └── fallback-nav.php       ← Hardcoded nav if no WP menu assigned
└── .audits/
    ├── todo.md                ← Development task tracker
    └── theme-*-audit-*.md     ← Periodic theme audit reports
```

## Custom Post Types

### Projects

Each project supports:

- **Title** — project name
- **Excerpt** — card description (keep under 40 words)
- **Skills** (taxonomy) — gold skill badges linking to skill archives (Docker, PowerShell, etc.)
- **Tags** — teal keyword tags shown at the bottom of the single project page
- **Featured thumbnail** — optional
- **Live URL** — meta field; shown as "↗ Live Site" link on cards and in single page meta
- **GitHub URL** — meta field; shown as "🐙 GitHub" link on cards and in single page meta
- **Featured checkbox** — controls visibility on the homepage projects section

Project cards link to the WordPress project page. Live Site and GitHub links appear as a separate row at the bottom of each card.

### Capabilities

Displayed in the capabilities panel on the right side of the About section. Maximum of 5 items enforced.

- **Title** — capability name
- **Excerpt** — short description shown below the title
- **Icon** — emoji displayed alongside the title (meta field)

### Expertise

Displayed as cards in the Core Expertise section.

- **Title** — expertise area name
- **Excerpt** — card description
- **Icon** — emoji displayed on the card (meta field)
- **Skills** (taxonomy) — shared with Projects and blog posts; skill tags link to skill archives

### Certifications

Displayed in the Certifications section. Admin-only — no public archive or single post URL.

- **Title** — certification name
- **Excerpt** — issuing body or short description
- **Expiry date** — displayed on the card; leave blank for non-expiring certs
- **Credential ID** — badge/certificate ID number; leave blank to hide
- **Credential URL** — links the cert card to a verification page
- **Order** — controls display order via page-attributes

## Taxonomies

### Skill

A shared taxonomy registered on **Posts**, **Projects**, and **Expertise**.

- Tags display in gold (`card-tag`) everywhere and link to `/skill/<slug>/`
- The skill archive (`taxonomy-skill.php`) lists both blog posts and projects in a unified view
- Skill tags appear under the post/project title on single pages

## Sections Admin Page

**WP Admin → Sections** controls per-section visibility, colors, and background images. Saving this page automatically clears Jetpack Boost and W3 Total Cache page caches.

The **Blog** section has additional card meta toggles:

| Toggle | Controls |
| --- | --- |
| Post date | Date shown on blog cards |
| Author | Author name shown on blog cards |
| Category | Primary category shown on blog cards |
| Skills | Skill tag row shown on blog cards |

## Footer Settings

Footer content is configurable in **WP Admin → Sections → (bottom of page)**:

| Field | Default | Notes |
| --- | --- | --- |
| Site tagline | `Cybersecurity & Compliance Professional` | Shown under the logo |
| Copyright name | Site title | Name shown in the copyright line |
| Credit text | _(blank)_ | Optional "Built by …" line |
| Legal links | _(blank)_ | HTML links rendered above the copyright bar |

Social/contact icons in the footer are pulled from the **Contact** section link list.

## Contact Form

Submits via WordPress AJAX to `wp_mail()`. No plugin required.
Destination email = WP admin email (`Settings → General → Administration Email Address`).

## Site Logo

The nav and footer display a text logo derived from the first letter of each of the first two words in **Settings → General → Site Title** (e.g. "Russ Teicheira" → `RT.`). If a site icon image is set in the Customizer, that image is used instead.

## Responsive Breakpoints

| Breakpoint | Behavior |
| --- | --- |
| > 1024px | Full desktop layout |
| ≤ 1024px | Expertise grid → 2 col, footer adjusts |
| ≤ 900px | About/Contact stack to 1 col, blog sidebar hides |
| ≤ 768px | Hamburger nav activates |
| ≤ 640px | All grids → 1 col |

## Limitations

There are a few known limitations:

1. The title structure is geared around one first name and one last name. Additional names should work just fine, but it might look off.
2. I did not program anything around WooCommerce.
3. Currently no way to easily change color overlays (like the grid in the hero section).
