# Site Builder

A local web app for creating, customizing, checking and building satellite sites of every type, with the engine's
decisions visible. Brief: [docs/briefs/10-site-builder.md](briefs/10-site-builder.md). Status: **B1 and B2 built.**

## Run it

From the `satellite-system` folder:

```bash
php -S 127.0.0.1:8800 builder/router.php
```

Open http://127.0.0.1:8800. It needs PHP 8.2+ and nothing else (no npm, no database). It only answers on this
machine and only writes `sites/<project>/<site>.json` and `dist/sites/<id>/`.

## What you can do

| Screen | What it does |
|---|---|
| **Projects** | Every site under `sites/` as a card (screenshot if the gallery made one, otherwise the style's colours). "Check diversity" shows each pair of same-type sites with overlap and whether they pass the rule |
| **New site** | Type → market → brand (or publisher, brands, studio, articles) → angle → style → project and id → start from a blank template or a demo. Blocked choices are greyed with the reason ("Australia does not allow casino", "Not licensed in Kazakhstan", "No promotion type is cleared in Brazil"). The style suggestion avoids styles sibling sites already use |
| **Editor · Structure** | Angle, language, seed and **Shuffle** (new seed, layouts and order re-picked, with Undo), tracked links, pages on/off (multi-page types), frame and section layouts, optional sections on/off, middle-zone order by drag or ↑ ↓ (order rules enforced) |
| **Editor · Content** | Page title and description with limits, then a form per section generated from the block's `fields.json`: required fields, character counters, repeaters with add, remove and reorder. Fixed blueprint props and the block's guardrails are shown |
| **Editor · Style** | Preset picker, colour overrides with live WCAG contrast results, fonts, corner radius, spacing density, reset |
| **Editor · X-ray** | Angle (pinned or seed), market rules applied, every warning with **Go to** the field, page set and section table (rule, reason, layout, pinned or seed, allowed layouts), middle order and its rules, page weight vs. the 60 KB budget with HTML and CSS per block, JSON-LD, sitemap and robots, the generated HTML |
| **Editor · JSON** | The exact file that is saved; edit and apply |
| **Preview** | The real page from the engine, refreshed about 0.4 s after each edit. Phone 375 (with the 667 px fold line), tablet 768, desktop 1280; page switcher; links inside the preview switch pages; optional age gate |
| **Top bar** | Warnings, page weight, diversity vs. the project, Undo/Redo (Ctrl+Z, Ctrl+Shift+Z), Export, Build, Save (Ctrl+S). Leaving with unsaved changes asks first (Stay, Discard, Save and leave) |
| **Diversity suggestions** | Click the diversity badge: the pairs, plus the single changes (style, angle, one section's layout) that lower overlap most, tried against the engine. **Apply** changes the site; Ctrl+Z undoes it |
| **Export** | Zip of the saved version: **Static site** (folder URLs, sitemap, robots, for hosting), **Preview package** (plain `.html` links, opens from disk or for review), **WordPress package** (ACF field groups, theme.json for the style, the site config, install steps). Warnings, if any, go into `BUILD-WARNINGS.txt` |
| **Data** | Forms for the shared data: brands, publishers, game studios, game catalogs (market → studio → games), promotions, facts, ratings (score + reason per criterion, strengths, weaknesses), reviews, articles, authors. Markets are shown read-only. New files (a brand's catalog or offers, a market's facts or ratings, a new article), add and remove items, raw JSON. **Every save keeps the previous version in `dist/backups/`** and rebuilds every site in memory, listing the ones that warn or no longer build, with a link to open them |

**Build** saves, writes `dist/sites/<id>/` with the same code as `build/build.php`, lists files and warnings, and opens the
built site at `/site/<id>/`.

## How it works

| Part | File |
|---|---|
| Local server, static files, built sites | [builder/router.php](../builder/router.php) |
| JSON API: meta, site, template, render, save, build, diversity, suggest, data (list, file, save, check), export | [builder/api.php](../builder/api.php) |
| Data collections and their forms | [builder/public/js/datasets.js](../builder/public/js/datasets.js) |
| Zip writer (no PHP zip extension needed) | [lib/zip.php](../lib/zip.php) |
| UI (plain ES modules, no build step) | [builder/public/](../builder/public/) |
| X-ray data: `SX::$trace` (why pages and sections are on, pinned vs seed), `SX::$weights` (bytes per block) | [lib/blueprint.php](../lib/blueprint.php), [lib/page.php](../lib/page.php) |
| Diversity, shared with `build/diversity.php` | [lib/diversity.php](../lib/diversity.php) |

The builder never renders on its own: `/api/render` calls `sx_build_site()`, the same function as the static build and
the WordPress template. A demo saved and built through the builder produces byte-identical HTML to the CLI.

## Data safety

- Markets (compliance rules) cannot be changed from the builder.
- A save overwrites a data file only after copying the previous version to `dist/backups/<path>.<time>.json`.
  To roll back, copy that file back.
- The builder cannot delete files. Remove an article by deleting its file, or unpublish it by giving it a future date.

## Planned (B3)

- Shared server with logins and roles, edit locking, publishing to WordPress over its API
