# Satellite System

A block library for building diverse satellite sites (static HTML or WordPress + ACF) from one source.

Status: **Every site type has Demos A, B and C.** One-page types `landing`, `app`, `promo`, `vendor` and multi-page `satellite`, `reviews`, `casinos`, `news` each have three demos that pass the diversity rule. Phase 6 (multi-page app) skipped.

**Site Builder (UI):** `php -S 127.0.0.1:8800 builder/router.php`, then open http://127.0.0.1:8800. Create, customize, preview and build any site type; the X-ray panel shows why the engine made each choice. Guide: [docs/builder.md](docs/builder.md).

**Demo gallery:** https://claude.ai/artifact/T2rtbidSjgTT9qsE42VY6z (rebuild: `powershell -File build/screenshots.ps1`, then `python build/gallery.py`, then republish `dist/gallery/gallery.html` with `dist/gallery/shots/*`).

**Test on WordPress quickly:** [docs/wordpress-quick-test.md](docs/wordpress-quick-test.md) (paste a site config into a page, no ACF needed).

## Site types

| Type | Status | Guide | Brief |
|------|--------|-------|-------|
| `landing` (one-page branded landing) | Ready | [docs/types/landing.md](docs/types/landing.md) | [brief 01](docs/briefs/01-one-page-landing.md) |
| `app` (one-page app download) | Ready | [docs/types/app.md](docs/types/app.md) | [brief 02](docs/briefs/02-app-download.md) |
| `promo` (one-page bonuses and promo codes) | Ready, Demos A–C (demo market only; KZ/AU/BR blocked pending compliance) | [docs/types/promo.md](docs/types/promo.md) | [brief 03](docs/briefs/03-bonuses-promo-codes.md) |
| `vendor` (one-page brand vendor overview) | Ready (casino markets only: BR, xx; KZ/AU fail by design) | [docs/types/vendor.md](docs/types/vendor.md) | [brief 04](docs/briefs/04-vendor-overview.md) |
| `satellite` (multi-page: home + service pages) | Demos A–C (AU, KZ, BR); its multi-page plumbing is shared | [docs/types/satellite.md](docs/types/satellite.md) | [brief 05](docs/briefs/05-multi-page-satellite.md) |
| `casinos` (multi-brand casino reviews and comparison) | Demos A–C (BR ×2, demo market); casino markets only, KZ/AU fail by design | [docs/types/casinos.md](docs/types/casinos.md) | [brief 08](docs/briefs/08-multi-brand-casino-reviews.md) |
| `news` (gambling news with labelled PR articles) | Demos A–C with sample articles (AU, BR, KZ; demo-only) | [docs/types/news.md](docs/types/news.md) | [brief 09](docs/briefs/09-gambling-news-pr.md) |
| `reviews` (small brand review site, user reviews) | Demos A–C with sample reviews (KZ, BR, AU; demo-only), paged lists; needs a review backend for production | [docs/types/reviews.md](docs/types/reviews.md) | [brief 07](docs/briefs/07-brand-review-ugc.md) |

## Tech stack

No framework, no npm, no bundler. Every demo is plain static HTML with inline CSS and a little JS, under a 64 KB page
budget (`SX_PAGE_BUDGET_KB`), so it can be hosted anywhere or dropped into WordPress.

| Layer | Technology | Used for |
|-------|------------|----------|
| Rendering engine | **PHP 8** | Blocks (`blocks/*/render.php`, `variants/*.php`), blueprints, site expansion (`lib/`), build and export scripts, settings page |
| Markup and styles | **HTML5, CSS** | Semantic page output; CSS shipped per block and per variant, so a page carries only what it uses |
| Design tokens | **CSS custom properties** | Presets and looks (colours, fonts, spacing, radius) compiled by `tokens/build.php`, per-site class prefix |
| Interactivity | **Vanilla JavaScript** (inline, no libraries) | Age gate, sticky CTA, promo-code copy, tabs and filters, switcher bar, phone preview, developer panel (Shadow DOM), settings page |
| Graphics | **Inline SVG** | Icon sprite, brand logos, generated cover art, QR codes (PHP encoder, `build/test-qr.php`) |
| Fonts | **Google Fonts** | One stylesheet link per page |
| SEO | **schema.org JSON-LD** | WebPage, WebSite, FAQPage, Review and more, generated per page; `sitemap.xml`, `robots.txt` |
| Content and config | **JSON** | Site configs (`sites/`), blueprints, markets, brands, promotions, block definitions and fields, UI strings (`i18n/`, RU/EN/PT/KK) |
| CMS | **WordPress + ACF PRO** | `block.json` per block, generated ACF field groups (`wp/generate-acf-json.php`), ACF blocks and page template (`wp/`) |
| Tooling | **Python 3** | Gallery generator (`build/gallery.py`), rebuild-all (`build/rebuild-reviews.py`), variation difference in % (`build/variation-diff.py`), QA scripts |
| Screenshots | **PowerShell + headless Chrome** | Desktop and phone gallery thumbnails (`build/screenshots.ps1`); first-screen captures for `build/variation-diff.py` |
| Docs and AI settings | **Markdown** | Type docs, briefs, AI brief settings (`ai/`), generated AI briefs (`dist/ai/`) |
| Local server | **PHP built-in server** (`php -S`) | Local gallery + settings (`build/local-router.php`), Site Builder (`builder/router.php`) |
| Container | **Docker + Docker Compose** | One-command local gallery (`start-gallery.bat` / `start-gallery.sh`, PHP 8.3 + Python 3 image) |
| Deploy proxy | **Traefik + Caddy 2** | VPS deploy behind the shared Traefik (`docker-compose.traefik.yml`, `Caddyfile.traefik`, `deploy/traefik/`) |
| Version control | **Git / GitHub** | Repository |
| Sharing | **claude.ai Artifacts** | Published gallery and review links |

## Requirements

- PHP **8.2.35+** (or 8.3.36+ / 8.4.27+ / 8.5.12+). Older builds are affected by PHP2Shell (CVE-2026-109618/109619).
- Python 3 for the gallery and rebuild scripts; PowerShell + Chrome only for regenerating screenshots.
- Or just Docker: `start-gallery.bat` runs the local gallery with everything included.
- WordPress side: the custom theme with ACF PRO 6+.

## Quick start

```bash
php tokens/build.php                              # compile presets + contrast check
php build/build.php sites/examples/vantro-br.json # build a static site into dist/sites/
php build/build.php sites/demos/a-vantro-au.json  # build a site from the "landing" blueprint
php build/diversity.php sites/demos/*.json        # check sites in one project differ enough
php build/config-schema.php landing               # JSON Schema for content pipelines -> dist/schemas/
php build/build.php sites/demos/sat-a-vantro-au.json  # multi-page: every page + sitemap.xml + robots.txt
php build/export-review-site.php sites/demos/sat-a-vantro-au.json  # clickable multi-page review link
php build/test-qr.php                             # QR encoder self-test (spec reference values)
php build/split-css.php <block> [...]             # move variant-only CSS rules into variants/<v>.css
php build/style-tiles.php                         # regenerate the Phase 0 review page
php wp/generate-acf-json.php                      # ACF field groups from fields.json
php build/export-review.php dist/sites/a-vantro-au/index.html dist/review/a.html "Title"  # shareable review copy
```

New site? Start from a template in [sites/templates/](sites/templates/). Promotion feeds: copy [brands/promotions/_template.json](brands/promotions/_template.json).

## Building from a blueprint (recommended)

A config with `"type"` is expanded from `blueprints/<type>.json`. You supply the angle, content per section and,
optionally, pinned variants or order. Everything else is chosen by the seed within the blueprint's rules:

```json
{
  "id": "a-vantro-au", "type": "landing", "angle": "matchday",
  "brand": "vantro", "market": "au", "preset": "arena", "seed": "vantro-au-a", "prefix": "auto",
  "links": { "go": "https://tracked.example/..." },
  "variants": { "hero": "scoreboard" },
  "content": { "hero": { "title": "…" }, "features": { "items": [ … ] }, "faq": { "items": [ … ] } }
}
```

The build prints the resolved page, e.g. `header:minimal → hero:scoreboard → trust:strip → …`, so you can see what
the seed chose. All CTAs share one label (`cta_label`) and one tracked URL (`links.go`) and carry `data-cta=<position>`.

## How it fits together

```
tokens/presets/*.json ─┐
markets/*.json ────────┤
brands/*.json ─────────┼─> lib/render.php ─┬─> build/build.php   -> static HTML
i18n/strings.json ─────┤   (sx_block)      └─> wp/sx-blocks.php  -> ACF blocks in the WP theme
blocks/<name>/ ────────┘
```

- **Tokens:** 10 presets. Each sets type, colour, shape, surface style, density and hero background. All fonts cover Kazakh Cyrillic.
- **Blocks:** PHP templates that read `$props` only, so the same file renders in WP and in the static build. See [docs/block-contract.md](docs/block-contract.md).
- **Markets:** KZ, AU and BR rules (age, verticals, promotions, payments, compliance copy). Blocks read them, so templates contain no market logic.
- **Site config:** brand, market, preset, seed and class prefix, plus a list of pages and blocks. Same config means the same site every build.

## Site config

```json
{
  "id": "vantro-br",
  "brand": "vantro",
  "market": "br",
  "preset": "sunburst",
  "seed": "vantro-br-01",
  "prefix": "auto",
  "tokens": { "color": { "accent": "#FF5A1F" } },
  "pages": [
    { "slug": "index", "title": "…", "blocks": [
      { "block": "header", "props": { "variant": "minimal" } },
      { "block": "hero", "props": { "title": "…" } },
      { "block": "footer" }
    ] }
  ]
}
```

Omit `variant` (or use `"auto"`) to let the seed choose. On blueprint sites, the seed also picks, and these keys pin:

| Key | Values | What it changes |
|---|---|---|
| `"icons"` | `outline`, `filled`, `rounded` | Icon family on every page (19 icons, each family drawn separately) |
| `"variants": {"age-gate": …}` | `modal`, `fullscreen`, `sheet`, `split` | Age gate layout |
| `"variants": {"compliance-bar": …}` | `strip`, `ribbon` | Compliance bar at the top of the page (where the market shows one) |
| `"variants": {"compliance": …}` | `panel`, `cards` | Compliance block inside the footer |
| `"variants": {"disclosure": …}` | `line`, `box`, `chip` | Affiliate disclosure (review, comparison and news sites) |
| `"variants": {"footer": …}` | `columns`, `compact`, `centered` | Footer layout (within what the site type allows) |
| `"dialect"` | see Code dialects below | How the code is written |

The builder warns, and the command exits 1, when:

- the brand is not licensed in the market, or none of its verticals are allowed there
- a page asks for an offer in a market that restricts offers (it renders a feature card instead)
- a required block (age gate, compliance panel) is missing (it is added automatically)
- content breaks a field limit (required, max length, item count) on a blueprint site
- a Brazilian site's brand link is not on a `.bet.br` domain

## WordPress

1. Copy this folder into the theme as `/sx`.
2. In `functions.php`: `require get_template_directory() . '/sx/wp/sx-blocks.php';`
3. Run `php wp/generate-acf-json.php`. ACF loads `dist/wp/acf-json` automatically.
4. Save site settings in the `sx_site` option (JSON: market, locale, brand, preset, seed, prefix) or use the `sx_site_config` filter.

The WP adapter has not been tested in a live WordPress install yet. Test it on a staging copy of the theme first.

## Folder map

| Path | What it is |
|------|------------|
| `tokens/presets/` | Design presets (edit these) |
| `tokens/base.css` | Reset and primitives (buttons, cards, logo, age badge) |
| `blocks/` | Block library |
| `markets/` | Market rules and compliance copy (placeholders until approved) |
| `brands/` | Fictional operators, providers, logos |
| `i18n/strings.json` | UI strings in en / ru / kk / pt |
| `lib/` | Runtime: tokens compiler, renderer, site context, blueprint engine |
| `blueprints/` | One file per site type: sections, rules, angles |
| `build/` | Static builder, diversity check, schema generator, style tiles, review export |
| `wp/` | ACF adapter and field-group generator |
| `sites/demos/` | Reference demos per site type |
| `sites/templates/` | Starter configs to copy |
| `sites/examples/` | Low-level example configs (one positive, one negative test) |
| `dist/` | Generated output. Do not edit. |
| `docs/` | Block contract, QA checklist, briefs, site-type guides |

## Developer skeleton view (review links)

Every review link (`build/export-review-site.php`, `build/variations.php`) has a "Developer skeleton" button above "Mobile view".
It outlines each section on the page with its block and variant; in Wireframe mode the page turns grey and each section shows
its short name (from `lib/devspec-names.json`). The panel has: Page sections (WordPress block `sx/<name>`, variant, role, rules,
the field values used on this page), Site sections (every page with its sections; a section opens that page scrolled to it, and
"Go to page" opens it with the panel), Styles (CSS custom properties, fonts, class prefix) and WordPress (ACF block markup for the
page editor plus the page spec as JSON). It needs a build with `"_devspec": true` (set by both exporters), which adds `<!--sx:N-->` markers
before blocks; normal builds and WordPress output are unchanged. Code: `lib/devspec.php`, `lib/devspec.js`, `lib/devspec.css`.
`?sxdev=1` opens a page with the panel already open; `&sxs=N` also selects its N-th section (0-based).

### AI brief

The skeleton panel's last tab ("AI brief") holds a Markdown prompt for rebuilding the page with an AI coding tool: goal and target
stack, site and market context, design tokens, the rules that must not break (market compliance plus each block's guardrails, only
for variants on the page), every section in order with its field values, and an acceptance checklist. Multi-page review links also
publish `<prefix>ai-brief.txt` with the brief for every page of that switcher combination; the tab loads it into a second box.
Text is shortened in the review copy; `php build/ai-brief.php sites/demos/<id>.json` writes the full-text brief to `dist/ai/<id>.md`.
Code: `lib/aibrief.php`, `build/ai-brief.php`.

## Code dialects (`lib/dialect.php`)

Every site writes the same design in its own code dialect, picked from its seed, so sites built from one template
share almost no CSS or markup text while looking exactly the same. It is a final transform of each rendered page:

| Choice | Options |
|---|---|
| `classes` | `bem` (`hero__title--x`), `camel` (`heroTitle_x`), `flat` (`hero-title-x`), `short` (hashed, `qkzvt`) |
| `vars` | `keep`, `short` (CSS custom properties renamed, `--c-bg` → `--qvd`) |
| `colors` | `hex`, `HEX`, `rgb` (`rgb(31 122 224)`), `rgb-comma` |
| `units` | `px`, `rem` (spacing, type and radius; 1–3px stay px) |
| `numbers` | `lead` (`0.5`), `bare` (`.5`) |
| `breakpoints` | 0, 8, 16 or 24px added to every `@media` width |
| `order` | `keep`, `reverse`, `sorted` (declarations, only where order cannot matter) |
| `attrs` | `keep`, `sorted`, `reverse` (attribute order in tags) |
| `wrappers` | `none`, `some`, `many`: neutral `<div>` wrappers (`display: contents`) inside containers where no CSS rule depends on the exact parent/child structure, so the DOM tree changes and the layout does not |
| `lists` | `keep`, `aria`: component lists as `<div role="list">` / `<div role="listitem">`; `ul`/`li` selectors are rewritten at the same specificity |

Pin any choice in the site config, e.g. `"dialect": {"classes": "short"}`, or turn it off with `"dialect": false`.
The class prefix stays in front of renamed classes and ids; text, URLs, `data-*` values and JSON-LD are never touched;
class-substring selectors in looks (`[class*="__card"]`) are expanded to the classes they matched. The developer
panel's Styles tab shows each site's dialect, and `php build/diversity.php` hints when two sites of a type share a
class style. Block authors: find elements by `data-*` or id, never by class name (docs/block-contract.md).

Effect, measured with `build/variation-diff.py` (separate demo sites of the same type, originally → with dialects,
frame layouts, icon families and the structural dialect):

| Type | Code | DOM skeleton | Structure | Visual |
|---|---|---|---|---|
| landing | 53% → 97% | 47% → 77% | 69% → 80% | 60% (same) |
| app | 54% → 96% | 39% → 67% | 69% → 73% | 78% → 77% |
| app-multi | 55% → 96% | 46% → 64% | 73% → 76% | 79% (same) |
| promo | 49% → 96% | 43% → 72% | 64% → 75% | 83% → 82% |
| vendor | 53% → 94% | 44% → 72% | 77% → 83% | 94% (same) |
| satellite | 59% → 96% | 53% → 79% | 82% → 88% | 90% → 91% |
| casinos | 59% → 96% | 54% → 70% | 66% → 74% | 76% → 77% |
| news | 52% → 94% | 45% → 71% | 60% → 72% | 87% (same) |
| reviews | 50% → 96% | 37% → 67% | 71% → 78% | 82% → 84% |

The code and structural dialects change no pixel: all nine types render identically with them on and off (dialects
covering every option, full page at 1280 and 520px, app-store looks included); the accessibility tree (roles, names,
order) is the same node for node; age gate, FAQ, code copy and review filters work. Near the 64 KB page budget the
structural dialect uses fewer wrappers. The visual and structure changes come from the frame layouts and icon families.

## How different are the variations? (`build/variation-diff.py`)

Measures, in %, how much the home-page versions of each switcher demo differ from each other, visually and in code,
next to the separate demo sites A, B and C of the same type as the yardstick for "a different site".

```bash
python build/variation-diff.py                  # every site type (a few minutes the first time)
python build/variation-diff.py casinos landing  # some types, or demo ids such as casinos-b-xx
python build/variation-diff.py --threshold 30   # what "distinct" means (default 20%)
```

| Measure | What it counts |
|---|---|
| Visual | Share of the desktop first screen (1280×800) that visibly changes: 8×8 px cells whose average colour moves |
| Code | Mean of CSS (share of CSS rules that differ) and markup (share of 6-tag HTML sequences that differ). Trivial rewrites do not count: the class prefix, whitespace, declaration order and attribute order are normalised |
| Skeleton | Share of 6-tag sequences of tag names only: the DOM tree. Code dialects do not change it; different block layouts do |
| Structure, text (in the JSON) | Share of blocks (block:layout) and of 3-word text sequences that differ |
| Distinct versions | Largest set of versions in which every pair differs by at least the threshold |

Review-only parts (switcher bar, developer panel, phone toggle) and the age gate are left out. Rebuild the review pages
first (`python build/rebuild-reviews.py`). Needs Google Chrome or Chromium (`CHROME=/path` to override) and no Python
packages. Output: `dist/qa/variation-diff.md` (table) and `dist/qa/variation-diff.json`; screenshots are cached in
`dist/qa/variation-diff/` and re-taken only for pages that changed. Rough guide: separate sites differ by 60–90% visually
and about 50–60% in code; 20% or more on either is clearly noticeable. Visual covers the first screen only, so a switcher
that changes content further down shows 0% visual and still shows up in code.

## Local gallery (no publishing)

`python build/gallery.py --local` writes `dist/index.html`: the same gallery, with every demo linked to its local review pages
(`dist/review/<id>/index.html`, switchers, phone view and developer panel included). Serve the `dist` folder and open it:
`build/serve-local.bat` (Windows) or `sh build/serve-local.sh`, both run `php -S localhost:8080 -t dist` and open the browser.
Rebuild the review pages first when something changed (`php build/export-review-site.php …` / `php build/variations.php …`).

### Docker (one command, nothing else to install)

Double-click `start-gallery.bat` (Windows) or run `sh start-gallery.sh`. Both do `docker compose up -d --build` and open
http://localhost:8090/ (settings: http://localhost:8090/settings/). The container restarts with Docker, so after the first
start the gallery is simply there. Stop it with `docker compose down`; pick another port with `SX_PORT=9000`.

- Image: PHP 8.3 CLI (Debian, for glibc `iconv` slugs) + Python 3 + rsync. It holds no project files.
- The project folder is mounted at `/src`. `build/docker-start.sh` copies the code to `/app` on the container's own disk
  (`build/docker-sync.sh`), because PHP builds read thousands of small files and that is ~20× slower over a Windows bind
  mount. `ai/` and `dist/` are symlinks back to the host, so settings edits and rebuilt pages land in your folder.
- Settings → "Rebuild review pages" re-syncs the code first, so edits to blocks, blueprints or configs are picked up
  (27 demos in ~40 s). After editing code without rebuilding, `docker compose restart` (about 3 s) picks it up too.
- Output is identical to a native build (files are written with LF line endings).

**Behind the shared Traefik proxy (VPS):** `docker compose -f docker-compose.yml -f docker-compose.traefik.yml up -d --build`.
No host ports; Traefik (TLS + `allow-vpn-only`) → Caddy (basic auth + headers, `website-gallery-caddy:443`) → gallery.
Same pattern as Drop Sherlock. Steps, cert script and router snippet: [deploy/traefik/README.md](deploy/traefik/README.md).

### AI brief settings (local)

The AI brief is assembled from editable files in `ai/`: `system.md` (system prompt: role, target stack, acceptance
checklist; placeholders {scope}, {site}, {type}, {angle}, {language}), `types/<type>.md` (guidance per site type) and
`markets/<market>.md` (market context and the rules the AI must not break). The developer panel's AI brief tab shows the
system prompt in its own box; the site brief files and `build/ai-brief.php` put it first.

Edit them in the local settings page: start the local gallery (`start-gallery.bat` for Docker on :8090, `build/serve-local.bat`, or the `local-gallery` preview,
both route `/settings/` through `build/local-router.php` to `build/settings.php`) and open http://localhost:8080/settings/.
It saves the files, previews the assembled brief for any demo, and "Rebuild review pages" runs
`build/rebuild-reviews.py` in the background (log: `dist/qa/rebuild.log`). Local use only: it writes files.
