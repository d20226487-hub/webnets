# WebNets

A block library for building diverse satellite sites (static HTML or WordPress + ACF) from one source.

Status: **Every site type has Demos A, B and C.** One-page types `landing`, `app`, `promo`, `vendor` and multi-page `satellite`, `reviews`, `casinos`, `news` each have three demos that pass the diversity rule. Phase 6 (multi-page app) skipped.

**Site Builder (UI):** `php -S 127.0.0.1:8800 builder/router.php`, then open http://127.0.0.1:8800. Create, customize, preview and build any site type; the X-ray panel shows why the engine made each choice. Guide: [docs/builder.md](docs/builder.md).

**Demo gallery:** https://claude.ai/artifact/T2rtbidSjgTT9qsE42VY6z (rebuild: `powershell -File build/screenshots.ps1`, then `python build/gallery.py`, then republish `dist/gallery/gallery.html` with `dist/gallery/shots/*`).
**Local gallery (Docker):** `http://localhost:8090/` (the generator's "Gallery" link). Rebuild everything in the container:
`php build/build.php sites/demos/<id>.json` for each demo listed in `docs/types/*.md`, `sh build/screenshots.sh` (the
Linux twin of the PowerShell script), then `python3 build/rebuild-reviews.py` (every review page with its switchers,
then `dist/index.html`). The local gallery lists every demo linked from the type guides that has screenshots and local
review pages; it needs no published link. Its footer date is the date of the newest screenshot.

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
| `"impl"` | `a`, `b`, or per block `{"hero": "b", "faq": "a"}` | Which implementation of a block (see Block implementations below) |

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

In the generator, every built site's card has an **AI brief ↓** button that downloads `<project>_<job name>_<date>_<site>-ai-brief.txt` (system prompt and
the brief for all its pages; one-page sites get the file too). **AI briefs ↓** on the job page downloads a zip of the chosen
site types (all by default), named `<project>_<job name>_<date>.zip` (`_<type>` added for one type) and unzipping into a folder of that name, with a
folder per type inside when several are chosen; the date is the download's: `GET /generator/api/sites/<id>/brief`,
`GET /generator/api/jobs/<id>/briefs?types=a,b` (`generator/briefs.php`). One-page sites built before this get their brief
from the page's developer data.

## Website network generator (`generator/`)

Open http://localhost:8090/generator/ (Docker) or `/generator/` on the local server. **Projects** hold **jobs**; a job
generates N sites per site type in one market, and every job stays in the database (`data/generator.sqlite`) so you can
come back to it and compare sites inside the project and against all earlier jobs.

**The job form:** name, market, your own brands, site types with how many sites
each, the meaningful variations per type (tick one or more; sites are spread over the ticks), and colours (primary =
buttons and links, secondary = second highlight, accent = hero background; leave them automatic for the most different
sites). Types and options the market's rules block (e.g. no casino vertical, no allowed bonus types) are shown with a
warning, never disabled: the user decides. Generated sites build with `"override_blocks": true`, which turns those
legal and policy blocks into warnings; the job page shows them on each site as "Market rule overridden". Without the
flag (every other build) the blocks still stop the build.

The warnings come from probe builds of every type and option in the market (`generator/probe.php`, one process per
type and option, run in parallel), cached in `data/availability-<market>.json` until brand data, the engine, a
blueprint or the market changes. Opening the form starts the checks for every out-of-date market in the background;
the table is drawn at once and the warnings are added when the market's check is done (about 3 s when nothing is cached).

**Brands:** each brand row has a name, an optional domain (tracked links use it; otherwise `<name>` on the market's
required suffix, e.g. `.bet.br`, or `.example`), an optional colour and casino / sport / both. On job creation each
brand is written to the brand data overlay `data/overlay/brands/` (same layout as `brands/`, gitignored; the engine
reads it through `sx_brand_data()` / `sx_brand_file()`) as `u<job>-<slug>`: operator entry, a letter-mark logo, and
placeholder promotions, game catalog, reviews, market facts and rating cloned from a demo brand, with the numbers
varied per brand. Single-brand sites are spread over the brands; casino reviews and news sites list all of them (news
articles are copied per site with placeholder text and the job's brands as sponsors). **Rebuild all** re-creates the
brand data with the same ids.

**Copy:** "Demo copy" (default) keeps the source demo's text with the demo brand names swapped for the job's brands,
when the demo is written in the site's language (the switcher demos are Russian: demo market and Kazakhstan sites);
otherwise, and with "Placeholder text", copy is placeholder text of the same length.

**Page order and section designs (generated sites):** below the first screen the engine picks the order and each section's design itself, by three rules (`lib/blueprint.php`): no two conversion blocks next to each other (`SX_CONVERSION_BLOCKS`), no two tinted or coloured bands next to each other (`SX_TONED_DESIGNS`, kept in step with the blocks' CSS), and one pattern per site (a section keeps one design on all of a site's pages, and pages follow one seeded ranking of section kinds within their order rules). The demo's hand-picked order and section designs are not copied into generated sites; header, footer and frame designs are. Article pages keep their reading order and vary in design per site (`article-body`: standard, toc-side, banner, magazine).

**Gallery, by section:** the local gallery switches between "By site" and "By section". `python3 build/sections.py` finds one example of every block design in the built pages (review pages first, then generated sites, newest first), screenshots it alone on desktop and phone (`dist/gallery/sections/`) and writes `dist/gallery/sections.json`; `build/gallery.py --local` shows them grouped (first screens, conversion, trust, product, guidance, lists, articles), with where each section is used and a link to its page. `build/rebuild-reviews.py` runs it before the gallery when rebuilding every demo.

**Colours and Design:** every type has a Colours option: "Site style" (the preset and its per-site variation), "Neutral", or "Brand colours", a palette around the brand's colour (the job brand's on single-brand sites, the site's own on multi-brand ones: casino reviews, news). Game-provider sites keep their own Colours list, which adds "Provider colours". App sites and casino reviews also have a Design option for the first screen; each design is named after the site that inspired its structure (for example "Offer cards (like liga.net)"). The names only appear in the job form: the sites are built in their own visual language and never name or imitate those sites. Jobs from before Colours and Design were split keep their colour picks.

**Affiliate disclosure:** review, ranking and news sites say on every page that they earn a commission through their links (the `disclosure` block). The job form (and the editor, per type or site) chooses where: a strip at the top of every page (default) or a line in the footer above the responsible-gambling panel (`"disclosure": "footer"`). It cannot be switched off: gambling advertising rules and the type briefs require it.

**Sample brands:** casino review sites list the job's brands plus a fixed set of made-up operators (`GEN_SAMPLE_NAMES` in `generator/brands.php`, the first N, 7 by default, set in the job form), so rankings show a full set of cards; the featured (paid) slot is always one of the job's brands. The code fingerprint ignores the job number in brand ids (`u40-…`), so the same brand does not make two jobs' sites differ.

**Rows of cards:** "Swipe" keeps rows of cards in one row you scroll sideways (bonus banners above a satellite's hero,
game strips, screenshot carousels, App Store-style reviews and fact strip); "Stacked" wraps them into a grid on that site
(`<body data-rows="stack">`); "Both" (default for new jobs) alternates per site. Every swipe row shares one look
(`.sx-swipe` in tokens/base.css): it runs to the screen edges on phones, the next card peeks in, cards snap, and a thin
accent track shows it scrolls. Wide tables (comparison, payments) keep their sideways scroll either way. The editor can
set it per type or site.

**Edit job** reopens the form filled in; saving rebuilds the job. Sites that stay (same type and number) keep their
seed and style, extra sites are added, sites over the new count are deleted with their files. Jobs made before user
brands still rebuild with their demo brands until edited.

**First screens:** the generator drops the demo's pin on the first-screen section (the hero, or on review and news
sites the rating summary and the lead stories) and gives the sites of a type every layout of that block in turn, times
implementation A / B where a B exists: landing and satellite 8, casino reviews 6, promo and reviews 4, app, app site,
vendor and news 3. A layout outside the angle's list is kept for generated sites (`"pins_over_angle": true`); the angle
still steers automatic picks. Offers above the hero show at most one row (3 on landings, 2 on satellites).

| Type | Variations in the form | Picked by the generator per site |
|---|---|---|
| landing | casino / sport / casino + sport | full or compact length (bonus banners on top come with the full length) |
| app, app-multi | iOS / Android / both · site style / neutral / brand / Google Play / App Store design | |
| promo | bonus banners / promo codes / both | |
| vendor | one provider / several · site style / neutral / brand / provider colours | |
| satellite | casino / sport / casino + sport | lite / standard / full size, navigation (top, sidebar, hub) |
| casinos | casino / sport / casino + sport | ranking focus |
| casinos-one | casino / sport / casino + sport | ranking focus (one page: ranking, comparison, criteria, FAQ) |
| news | general iGaming news / brand news · paid articles as a block or in the feed | images on or off |
| reviews | (none) | images on or off |

Each site gets its own seed (layouts, order, code dialect, implementations A/B, structure, icon family) and a style:
one of 120 (10 presets × light / dark × 6 colour rotations, see **Styles** below), the one its type uses least across
the compared jobs. Copy is placeholder text sized like the real copy, brand data too. Sites are exported to `dist/generated/<site>/` with every page, the phone view, the developer panel and the AI
brief, without the variations bar, and with no comments in the code.

**The job page:** every site as a live thumbnail with its variations, style, page count, build notes and its
difference checks (Code %, Visual %, how many times it was rebuilt to meet them); **Open site**; a phone check run in your browser (sideways scroll at
375 px, hero CTA within 667 px), stored with the site; and the difference table per type: each site's most similar site
in the job, in the project's other jobs and in every other job, by code (with the DOM-tree difference) and visually.


**Editing (phase 3):** the job page opens in View mode; **Edit mode** shows **Edit all** next to each type and
**Edit** on each site card. The editor (`#/j/<job>/type/<type>` for all sites of a type in the job, `#/s/<site>` for
one site) sets, each on Automatic unless changed: the style (a preset, light / dark), the three colours, the desktop
navigation (top menu, sidebar left / right, hub on satellites), the sidebar look, the phone menu (side, how it opens,
none on one-page sites), which pages a multi-page site has (optional pages added or removed, required pages removed;
the home page, the per-brand / per-article pages, the review policy and market-mandated pages stay), and per page every
section: shown (optional sections added or removed; required sections removed, except the first screen and compliance
blocks: disclosure, offer terms, reviews), its layout, and the order of the middle sections (checked against the
blueprint's order rules before saving). Sections outside the type's blueprint cannot be added (there is no copy for
them). The site editor also shows what the built site has now. Buttons and links to a page the site does not have are
left out (links in text keep their words), and `page:<id>` links in block props resolve to the page.
Type edits are stored in the job (`spec.edits.<type>`), site edits in the site's plan (`edits`); a site's own edits win
key by key, and both go on top of what the generator picks on every build (`generator/edits.php`). Saving rebuilds the
affected sites and checks their differences again; settings survive Rebuild all and New looks. Fixed choices make
sites more alike, so the editor says so.

**Where the data is:** in Docker the generator's database and caches (difference tables, market checks) are on the
`generator-db` volume (`/db`, Docker's own disk; `SX_GEN_DB`, `SX_GEN_CACHE` in `docker-compose.yml`), in WAL mode.
Reading them through the Windows project folder made every query slow: the 450-site job page went from 2.2 s to
0.26 s. Site files, configs and logs stay in the project folder. On the first start the old `data/generator.sqlite` is
copied to the volume and kept as `data/generator.sqlite.before-volume`. Back up with
`docker compose cp gallery:/db/generator.sqlite ./generator-backup.sqlite`. Without Docker the database stays in
`data/generator.sqlite`. `docker compose down -v` would delete the volume, and with it the database.

**One job at a time:** a job started while another one builds is queued ("Waiting for another job") and starts on its
own when that one is done (`gen_spawn()` and the worker lock in `generator/lib.php`; the lock is on the container's own
disk). The worker runs at a lower CPU priority. A job left "running" by a worker that was killed (or a container
restart) shows as "stopped" with **Continue the build**, which builds the sites still missing.

**Big jobs:** the job page shows 10 sites per type with page buttons, and only the shown sites get the phone check.
Thumbnails are the desktop screenshot the visual check takes (`dist/generated/<site>/_thumb.png`), not live frames; a
site without one falls back to a live frame. The site list reads no code fingerprints or screenshot prints (they are
the last two columns of `sites`, so reading the others does not read through them), and the differences table is
cached in `data/diff/job-<id>.json` until a compared site changes. Each pair is measured once.

**Style variants** can be switched off on the Thresholds page (the 10 presets as they are), e.g. to build a baseline.

**Interface:** English or Russian and an Auto / Light / Dark theme, picked in the header and remembered in the
browser (`gen-lang`, `gen-theme`). The texts are in the `RU` table in `generator/index.php`; build notes and market
rule messages from the engine stay in English. The site-by-site difference tables are collapsed under the overview;
clicking a type in the overview opens them.

**Difference tables at scale:** a job's tables compare each of its sites with every compared same-type site (thousands
on a big network), so they are worked out in the background (`generator/diffjob.php`, started when a job finishes and
when its page finds no up-to-date result; the page says so and fills in when ready) and cached until a compared site
changes. The worker runs one site type at a time with PHP's JIT on (`gen_php_cmd()`), on prepared fingerprints and
decoded prints: about 12 s for 200 sites against 3,000 (it was about 60 s, and over the web server's 128 MB and 30 s).

**Difference thresholds** (Thresholds page, `#/settings`; stored in the `settings` table):

| Setting | Default | Meaning |
|---|---|---|
| Minimum code difference | 50% | share of CSS rules and HTML 6-tag sequences that differ (class prefix, whitespace and order do not count) |
| Minimum visual difference | 25% | share of the first screen that visibly changes, desktop 1280×800 and phone 375×667 averaged: 8×8 px cells at half scale whose average colour moves by more than 40 |
| Compare with | every job | sites of the same type in every job, the project, or the job only |
| Rebuilds per check | 2 | how many times a site under a threshold is re-planned and rebuilt, for the code check and again for the visual check; 0 = measure only |

How the worker (`generator/run.php`) meets them:
1. **Code, right after each build.** The site is compared with the sites before it (same type, in scope; inside its own
   job only the earlier sites, so of two alike sites the later one changes). Under the threshold it is re-planned and
   rebuilt at once.
2. **Visual, once the job is built** (job status "checking"). Headless Chromium takes first-screen screenshots of the
   job's sites, and of earlier sites in scope that have none yet (`generator/visual.py`; prints are stored per site, so
   comparing with hundreds of sites stays fast). Then, site by site in build order, code and visual are checked again
   against the final versions of the sites before it; a site under either threshold is re-planned, rebuilt and shot
   again before the next site is checked, so no site changes after a later one was compared with it.
3. **Re-planning** escalates. A site that only looks too alike (its code passes) first swaps light and dark, which
   changes 81–99% of the first screen and keeps the code. Otherwise, and after that: new seed, the least-used style
   that is neither its own nor the closest site's light / dark version of a preset, the next hero layout; from the 2nd
   attempt the section order is left to the seed, from the 3rd every layout the demo pins too (layouts the chosen
   options need stay). The angle stays, since the copy is written for it.
4. **Each site keeps its best attempt** (the largest margin over both thresholds). One that still misses a threshold
   is flagged red on its card ("Code 41% · under 50%") instead of being retried forever.

**What is compared:** only the current version of every existing site. A rebuild overwrites the site (same id, files,
fingerprint and visual print), so earlier versions are never kept or compared; deleted jobs and sites, sites removed
by an edit, and failed sites drop out. While a job is rebuilding, its own sites only count once rebuilt.
**Count this job in comparisons** (a switch on the job page, `jobs.compare`): untick it for a draft or test job. Other
jobs are then not compared with its sites, in their checks and in their tables, so nothing is rebuilt to differ from
it; the project list marks it "left out of comparisons". Its own sites are still checked against the counted jobs and
each other.

**On the job page** the differences start with an overview per type: sites, how many are under each threshold, how
many are somewhat alike (code between the threshold and 74%), and the lowest code and visual difference. While the job
builds, a notice says what is happening (building n of N, or checking the differences) and the previous table is
dimmed until the new one is ready.

**Styles:** a site's look is a preset plus a variant (`"style": {"hue": 120, "flip": true}` in the site config,
`sx_style_variant()` in `lib/tokens.php`): every colour turned around the colour wheel by `hue` (0, 60 … 300), and with
`flip` the theme swapped by role (backgrounds, surfaces, borders and text trade light for dark; accents keep theirs).
Contrast is restored afterwards, so all 120 variants pass the presets' contrast checks. Visual difference is decided
mostly by the style: in a 450-site job, sites with the same preset were 37% different on average and 19% of those
pairs were under 25%, against 76% and 0.4% for different presets; a light / dark swap changes 81–99% of the first
screen. The planner takes, per type, the light / dark version of a preset used least across the compared jobs, then
the colour rotation used least within it. Cards show the style as "arena · light · +120°".

**Palettes:** with "Site style" each site uses its own style's colours. With "Brand colours" or "Provider colours" the
brand's (or studio's) colour stays the accent, and the site gets one of five palettes around it (`look_variant`, in
`lib/tokens.php`): the original deep hero, night (dark, hero towards a hue 60° away), pastel (light tinted hero, dark
text), contrast (complementary hues) and dusk (dark, a glow 120° away). The sites of a type take them in turn, and a
re-plan picks one the type's other sites do not use. Google Play, App Store and Neutral are fixed looks by design.
Colours fixed in the job form override all of this.

**Navigation:** the sites of every type take turns through three navigations (plan key `nav`):

| Navigation | Multi-page sites | One-page sites |
|---|---|---|
| top | menu in the header, links in the footer | the header (no menu) |
| left sidebar | the page list in a sticky sidebar; the header has no menu | the brand's sections (sport, live, casino, promotions, app, payments) as tracked affiliate links to the brand (`links.go`, `data-cta="sidenav"`, `rel="nofollow sponsored noopener"`), with the CTA |
| right sidebar | the same on the right | the same on the right |

**Bonuses first on satellites:** every third satellite (plan key `offers`) opens with the brand's offers before the
hero, as the full-length landings do: two banners without code boxes ("BoostWin offers"), a swipeable strip on phones so
the hero button stays on the first screen. It is the optional `offers` section of the satellite home page, so the
editor can turn it on or off; in a market whose rules allow no bonus offers it does not appear.

**Phone menu and sidebar looks** (plan key `menu`, site config `"menu"` and `"sidebar_look"`; spread over each type's
sites with different strides, moved on by a re-plan and by New looks):

| | Options |
|---|---|
| ☰ side | left or right (the logo keeps the free space; the header button keeps its label, a long brand name shortens) |
| How it opens | dropdown under the ☰, a drawer sliding in from its side, a full-width panel right under the header, the full screen |
| No menu | one-page sidebar sites, every third: the sidebar then shows under the content on phones; multi-page sites always have a menu |
| Desktop sidebar | panel (solid box, accent title band, large items with arrows), rail (no box, display-type items, accent bar), cards (an item per card) |

The menu is a `<details>` (works without scripts); a small script closes it on the backdrop, a link or Escape and
stops the page scrolling behind a drawer, sheet or full screen.

Satellites keep their own top / sidebar / hub variation; the plan picks the sidebar's side. On phones (860 px and
narrower) the sidebar's content (its title and every link) moves into the header's ☰ menu, in every header layout,
and the content gets the full width; the header keeps its CTA. Phones and desktop carry the same links and labels,
only laid out differently (`sx_side_menu()` in `lib/page.php` builds both). In the engine: `"nav_style": "sidebar"` and `"nav_side": "left" | "right"`,
for one-page and multi-page sites. A re-plan moves to the navigation the type's other sites use least; New looks moves
every site on by one.

**Rebuild all** keeps the stored plans (style, palette, seed, hero) and checks them again with the current thresholds;
**New looks** gives every site a new seed, the least-used style for its type (not its current one), the next palette and hero
layout, keeps the variations, and rebuilds. With the defaults, a 13-site job
built in about 3.5 minutes the first time (screenshots of every earlier site in scope included) and needed no
re-plans; at 97% code / 70% visual every site was re-planned 2–5 times and none failed to build.

Files: `generator/lib.php` (catalog, planning, placeholders, build, fingerprints, thresholds), `generator/api.php`,
`generator/run.php` (background worker), `generator/visual.py` (screenshots and visual prints), `generator/probe.php`
(market checks), `generator/index.php` (the app). `lib/nocomments.php` strips comments from every build. The Docker
image includes Chromium for the visual check (`docker compose build` after pulling this change).

## Block implementations (A and B)

The nine most-used blocks also exist as a second, independent implementation: the same design and content, built with
another element structure and another CSS technique. Each site picks A or B per block by seed (`"impl"` pins it), so two
sites with the same layouts still ship different HTML and CSS.

| Block | Layouts with a B | How B differs from A |
|---|---|---|
| hero | split, centered, stat-led | flex `<header>` copy, CTAs in their own group, trust facts as inline notes, offer card in an `<aside>`; the big number is a `<figure>` with `<data>` |
| toplist | cards, list | each casino an `<article>` with its name as a heading, placed with grid areas; score as `<data>` (table and podium stay A: a real table is the right element) |
| faq | accordion, columns | questions as `<h3>` in a grid summary with a CSS-drawn plus/minus; columns via CSS multi-column instead of grid |
| cta-band | solid, split | flexbox rows, proof points as inline notes |
| trust-bar | strip, badges | a `<dl>` of label/value cells; the strip flows as grid columns, the badges are inline pills |
| features | grid, rows, bento | flex cards; rows in an auto-fit grid; bento as a lead tile beside a nested grid |
| steps | row, timeline | `<article>` steps numbered by a CSS counter; one continuous rail on the timeline |
| page-links | cards, list | a labelled `<nav>` of plain links in flex rows or cards |
| page-hero | compact, answer | a flex-column `<header>`, picture as its own column |

B lives in `blocks/<name>/b/` (`variants/<layout>.php`, `style.css`, `variants/<layout>.css`) with its own class roots,
and reuses A's data preparation (for example `blocks/toplist/_prep.php`), so content logic exists once. Checked: A and
B pages differ only by spacing details (0–4% of the screen on most pages; more where one block is a little taller and
shifts the rest), no sideways scrolling at 375px, the hero CTA sits at the same height (within the first 667px) in A and
B. The developer panel shows "B" next to the layout. Adding a B: copy the variant's fields and data, change the markup
and CSS, never the content or the rules (docs/block-contract.md).

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
