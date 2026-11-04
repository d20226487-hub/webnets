# Brief 10: Site Builder (UI for the whole system)

Status: **B1 and B2 built** · Phase 10 · Tool, not a site type · Guide: [docs/builder.md](../builder.md)

## 1. Job of the tool

One place to create, customize, check and ship satellite sites of every type, without editing JSON by hand, and
with the system's inner workings visible: why a section is on, why a variant was picked, which market rule applied,
what a page weighs.

| | |
|---|---|
| Users | Developers (full control, raw data) and content or SEO managers (guided, no code) |
| Main jobs | Start a site in under 2 minutes; make it look unlike the other sites in the project; fill and check content; build and export |
| Success | Every saved site builds without warnings, passes the diversity check, and is under 60 KB per page |
| Principle | The builder never renders anything itself: it calls the same PHP engine as the static build and WordPress, so what you see is what ships |

## 2. Where it runs and where data lives

| Decision | Default | Why |
|---|---|---|
| Runs | Locally: `php -S 127.0.0.1:8800 builder/router.php`, opened in a browser | No server or accounts to set up; works today |
| Storage | The existing JSON files: `sites/<project>/<site>.json`, `brands/`, `content/` | Stays git-friendly; CLI, builder and WordPress share one source |
| Front end | Plain HTML, CSS and JavaScript modules; no npm, no build step | Nothing to install; easy for any dev to change |
| Safety | Listens on 127.0.0.1 only; writes only inside `sites/`, `brands/`, `content/`; every save validated first | A local tool must not become a file-writing server |

A shared internal server (logins, roles, two people editing) is a later step (open question 2).

## 3. Screens

### 3.1 Projects
Projects (folders under `sites/`) with their sites as cards: thumbnail, type, market, language, last build, warnings,
largest page. A **diversity matrix** per project shows every pair of sites with their overlap percentage, and flags
pairs that break the rule (same preset, angle or hero).

### 3.2 New site wizard
Type → market → brand (or publisher) → angle → style → done. Only valid choices are offered, and each blocked option
says why: "Casino not allowed in Australia", "Promotions not cleared in Brazil", "Nyxa has no catalog for Tidewell
in KZ". The wizard picks a style and seed that keep the new site different from the project's other sites, and you
can override both.

### 3.3 Editor (the main screen)

| Area | What it does |
|---|---|
| Left: structure | Pages (multi-page types) and their sections, in order. Toggle optional sections, reorder the middle zone by drag (order rules are enforced as you drag), pick a variant per section from small previews |
| Left: content | A form per section, generated from the block's `fields.json`: text limits shown as you type, required fields marked, repeaters you can add to or remove from |
| Center: live preview | The real page from the engine, refreshed as you edit. Phone 375 / tablet 768 / desktop widths; page switcher for multi-page sites; fold line at 667px on phone |
| Right: style | Preset picker with swatches; token overrides (colours with live WCAG contrast, fonts, radius, density); "Shuffle" for a new seed (different variants and order) |
| Top bar | Save, Build, Export, warnings count, page weight, diversity vs. project |

### 3.4 X-ray (inner workings)
A panel beside the preview that explains the current page:

- **Blueprint trace:** each section with its rule (required, optional, angle, market, APK) and the reason it is on or off
- **Decisions:** for every variant and the section order, whether it was pinned by you or picked by the seed
- **Market rules applied:** age, compliance strip, mandated tagline, blocked verticals, promotion policy, domain check
- **Guardrails:** every warning and error with the rule behind it and a link to the field to fix
- **Weight:** HTML and CSS per block, sprite, fonts; budget bar against 60 KB
- **Output:** class prefix, JSON-LD, canonical, sitemap and robots, the generated HTML source

### 3.5 Data
Forms for the shared data, with the same validation the build uses: brands, providers and catalogs, promotions,
facts and ratings, review feeds, articles and authors. Markets and their compliance copy are read-only here (they
belong to compliance).

### 3.6 Build and export
Build one site or a whole project; the output of the existing builder, with its checks. Export as a folder of static
files, as a review link package, or for WordPress (ACF JSON and the config to paste into the page template).

## 4. Making sites diverse and fast by default

- New sites start from the seed and style the diversity check prefers for the project
- The editor shows overlap with the project's other sites live, and suggests the single change that lowers it most
- Budget, fold and contrast are visible while editing, not discovered at build time
- Nothing the guardrails forbid can be chosen in the UI (blocked options are shown greyed with the reason)

## 5. Phases

| Step | Contents |
|---|---|
| B1 | Projects, wizard, editor and live preview for all one-page and multi-page types, style panel, X-ray, save and build |
| B2 | Data editors (brands, catalogs, promotions, facts and ratings, reviews, articles), export packages, diversity suggestions |
| B3 | Shared server: logins and roles, edit locking, publishing to WordPress over its API (needs answers 2 and 4) |

## 6. Acceptance criteria for B1

- [x] Recreate any existing demo in the UI, and its saved JSON builds identically with the CLI
- [x] Market gating visible in the wizard (casino types blocked in KZ/AU with the reason)
- [x] Editing any field updates the preview within about a second; limits and required fields validated inline
- [x] Shuffle changes variants and order; the diversity matrix updates
- [x] X-ray shows rule, decision and weight for every section of the current page
- [x] Works at laptop width; usable on a tablet; keyboard accessible

## 7. Open questions (defaults in brackets)

1. **Users:** developers only, or content managers too? [both: a guided mode and a developer mode with raw JSON]
2. **Where:** each person's machine, or a shared internal server with logins? [local first; server in B3]
3. **Storage:** JSON files in git, or a database? [JSON files in git]
4. **WordPress:** export only, or push pages to WordPress sites through their API? [export in B1–B2]
5. **Scope of B1:** all site types at once, or one-page types first? [all types; multi-page uses the same editor with a page switcher]
