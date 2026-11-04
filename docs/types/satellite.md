# Site type: multi-page brand satellite (`satellite`)

Status: **Phase 5: plumbing + Demos A, B, C.** Brief: [docs/briefs/05-multi-page-satellite.md](../briefs/05-multi-page-satellite.md) ·
Blueprint: [blueprints/satellite.json](../../blueprints/satellite.json) · Schema: `dist/schemas/satellite.schema.json`

Reference demos (fictional brands):

| Demo | Config | Review link (all pages clickable) |
|---|---|---|
| A · Vantro · AU · starter-guide · 5 pages | [sites/demos/sat-a-vantro-au.json](../../sites/demos/sat-a-vantro-au.json) | https://claude.ai/artifact/SZGsU5EGXFFUzJghR2xyJD |
| B · Nyxa · KZ · RU · fast-payouts · split hero · 5 pages | [sites/demos/sat-b-nyxa-kz.json](../../sites/demos/sat-b-nyxa-kz.json) | https://claude.ai/artifact/YCWaxhwnCRRwHCWWoCCXnP |
| C · Vantro · BR · PT · sports-depth · scoreboard hero · 5 pages | [sites/demos/sat-c-vantro-br.json](../../sites/demos/sat-c-vantro-br.json) | https://claude.ai/artifact/Nz2eVJGiVJbapgQJCx6mzC |

The three demos pass the diversity rule (overlap 3–52%). The multi-page plumbing below is shared with the review-site
and news-site types. Wide uppercase presets (arena, terminal) read best with a short `cta_label` such as "Criar conta";
longer labels still wrap on phones instead of overflowing.

## 1. What the site is for

A home page plus 3–5 service pages, each answering one brand + modifier search ("vantro sign up", "vantro withdrawal").
Every page converts on its own: its answer and the CTA sit in the first screen on phones.

## 2. Pages

| Page id | Slug | Rule | Main sections |
|---|---|---|---|
| home | `/` | required | hero, trust, page links, features, steps (opt.), faq, final |
| signup | `/sign-up/` | required | page-hero (answer), steps, requirements, prose, faq, related, final |
| payments | `/payments/` | required | page-hero, payments table, prose, faq, related, final |
| app | `/app/` | when the brand has an app in the market | page-hero, download panel, features, prose (opt.), faq, related |
| betting | `/betting/` | when the market allows sports | page-hero, markets showcase, features, prose (opt.), faq, related, final |
| casino | `/casino/` | when the market allows casino | page-hero, features, prose (opt.), faq, related, final |
| bonus | `/bonus/` | when the market allows a promotion type (none of KZ/AU/BR today) | page-hero, promo terms, prose (opt.), faq, related, final |
| responsible | `/responsible-gambling/` | required in AU, optional elsewhere | page-hero (no CTA), prose, faq, related; no sticky CTA |

| Angle | Pages on | Home hero |
|---|---|---|
| starter-guide | signup, payments, app | stat-led, split |
| fast-payouts | payments, signup, app | stat-led, split |
| sports-depth (sports markets) | betting, signup, payments | scoreboard, split |

## 3. Build a satellite

1. Copy [sites/templates/satellite.json](../../sites/templates/satellite.json); set brand, market, preset, angle, seed.
2. Set `site.url` (needed for canonical links and the sitemap) and `urls`: `pretty` (default, `/payments/`) or `files` (`payments.html`).
   For a site in a subfolder, set `base` (e.g. `"/guide"`).
3. Fill `meta.<page>` (unique title + description per page) and `content.<page>.<section>`.
   Shared parts go at the top level: `content.header`, `variants.header`, `variants.footer`, `variants.sticky-cta`.
4. Prose text: HTML with H2/H3, p, lists, simple tables. Link other pages with `href="page:payments"`, never a hard-coded path.
5. `php build/build.php sites/<project>/<id>.json` writes every page, `sitemap.xml` and `robots.txt`.
   **A build with warnings or errors is not shippable.**
6. Review link: `php build/export-review-site.php sites/<project>/<id>.json "Title"`, then publish
   `dist/review/<id>.html` with the other pages from `dist/review/<id>/` (not `index.html`) as Artifact files.

## 4. Plumbing (reused by later types)

| Piece | Where |
|---|---|
| Page list, page rules, per-page expansion | `sx_expand_site()` in [lib/blueprint.php](../../lib/blueprint.php) |
| Paging (`"paginate": {"section", "per_page"}` on a page; `paging` override per site) | `sx_expand_site()`, `sx_paginate_count()`; nav via `sx_pagination()` in [lib/reviews.php](../../lib/reviews.php) |
| URLs, output files, nav items, absolute URLs | `sx_page_url()`, `sx_page_file()`, `sx_page_nav()`, `sx_abs_url()` in [lib/pages.php](../../lib/pages.php) |
| Site checks, sitemap, robots | `sx_site_checks()`, `sx_sitemap()` in [lib/pages.php](../../lib/pages.php) |
| Header nav + no-JS phone menu, current page marked | `header` variants `inline`, `centered` (nav comes from the page list) |
| Footer site nav | `footer` variants `compact`, `columns` |
| Breadcrumbs + BreadcrumbList JSON-LD | `page-hero` block |
| Page text with table of contents | `prose` block (`article`, `toc-side`) |
| Links to other pages | `page-links` block (`cards`, `list`) |
| Canonical, WebSite JSON-LD on home, `data-page` on `<body>` | `sx_render_page()` in [lib/page.php](../../lib/page.php) |
| WordPress | one WP page per site page, same config + "Site page id" ([wp/sx-page-template.php](../../wp/sx-page-template.php)) |

Analytics: CTA clicks carry `data-cta` (position) and the page is on `<body data-page="payments">`.

## 5. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Broken internal link, or a page no other page links to | **Build fails** |
| Page switched on whose market rule fails (casino in AU, bonus without allowed promotions, app without an app) | Warning, page left out |
| Required page switched off (e.g. responsible gambling in AU) | Warning, page kept |
| Link to a page the site does not have (`page:bonus`) | Warning |
| Missing or duplicate page title/description; page without CTA; page over 60 KB | Warning |
| No `site.url` | Warning (no canonical, no sitemap) |
| Prose HTML | Only basic tags survive; attributes other than `href` are removed; links must be `page:`, `https://` or `#` |
| One CTA label site-wide (`Join {brand}`); responsible-gambling page has no hero CTA and no sticky bar | Blueprint |

## 6. Diversity

`build/diversity.php` compares every page's sections. The same rule applies: two satellites in a project must differ in
preset, angle and home hero. With two home hero layouts per angle, pin `variants.home.hero` when the seed picks the same one.

## 7. Known gaps

- Demos B and C; `bonus` and `casino` pages are untested (no market in the demos allows them).
- Languages: RU/KK page pairs with `hreflang` for KZ (brief 05, question 4).
- WordPress menus instead of config navigation (brief 05, question 2).
- Pretty URLs on WordPress need matching WP page slugs and `base`; nothing checks that automatically.

**Variation demo (Russian, KZ):** `php build/export-review-site.php sites/demos/sat-b-nyxa-kz.json` builds size (standard / lite = no app and responsible pages / full = adds betting) × nav (`nav_style`: top / sidebar = sticky guide aside / hub = guide cards first). Review link: https://claude.ai/artifact/YCWaxhwnCRRwHCWWoCCXnP.
