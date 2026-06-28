# RussTeicheira WordPress Theme

Custom WordPress theme for russteicheira.net.

> [!CAUTION]
> This is a work in progress - use at your own risk. The below readme is **VERY** out-of-date!

![Screenshot](screenshot.png)

## Structure

```
russteicheira/
├── style.css                  ← Required WP theme header
├── functions.php              ← Setup, enqueue, CPT, AJAX, helpers
├── front-page.php             ← Homepage (all sections)
├── index.php                  ← Blog archive
├── single.php                 ← Single blog post
├── archive-project.php        ← Project archive
├── 404.php                    ← Not found
├── header.php                 ← <head>, nav
├── footer.php                 ← footer, wp_footer()
├── css/
│   └── main.css               ← All styles (tokens → responsive)
├── js/
│   └── main.js                ← Nav, typewriter, contact AJAX
├── template-parts/
│   ├── hero.php
│   ├── about.php
│   ├── expertise.php
│   ├── projects.php           ← Pulls from Projects CPT
│   ├── blog-preview.php       ← Latest 3 posts
│   └── contact.php            ← AJAX contact form
└── inc/
    └── fallback-nav.php       ← Hardcoded nav if no WP menu assigned
```

## WordPress Setup Checklist

1. Upload the `russteicheira/` folder to `wp-content/themes/`
2. Activate in **Appearance → Themes**
3. Set a static front page: **Settings → Reading → Your homepage displays: A static page** → select any blank page
4. Create navigation menus at **Appearance → Menus**:
   - Primary: About | Expertise | Projects | Blog | Get in Touch (link to `/#contact`)
   - Footer: About | Blog | Projects | Contact
5. Go to **Projects** in the admin sidebar to add projects
   - Fill in the excerpt (used as the card description)
   - Add Stack Tags for the tech badges
   - Check "Featured" to show on homepage
6. Set your admin email in **Settings → General** — the contact form sends there
7. Add a `screenshot.png` (1200×900px) to the theme folder for the WP admin preview

## Custom Post Type: Projects

Each project supports:
- **Title** — project name
- **Excerpt** — card description (keep under 40 words)
- **Stack Tags** (taxonomy) — tech badges (Docker, PowerShell, etc.)
- **Featured thumbnail** — optional
- **Live URL** — meta field
- **GitHub URL** — meta field
- **Featured checkbox** — shows on homepage

## Contact Form

Submits via WordPress AJAX to `wp_mail()`. No plugin required.
Destination email = WP admin email (`Settings → General → Administration Email Address`).

## Responsive Breakpoints

| Breakpoint | Behavior |
|---|---|
| > 1024px | Full desktop layout |
| ≤ 1024px | Expertise grid → 2 col, footer adjusts |
| ≤ 900px | About/Contact stack to 1 col, blog sidebar hides |
| ≤ 768px | Hamburger nav activates |
| ≤ 640px | All grids → 1 col |
