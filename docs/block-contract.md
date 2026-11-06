# Block contract

Every block in `blocks/` follows this contract. If a block follows it, it works in both the static
builder and the WordPress (ACF) theme without extra code.

## 1. Folder layout

```
blocks/<name>/
  block.json        WP block registration + "sx" metadata (variants, role, guardrails)
  fields.json       Props schema. Source of truth for ACF field groups and defaults.
  _shared.php       Optional. Prep shared by all variants (defaults, derived values).
  variants/<v>.php  One file per structural variant. Markup only.
  variants/<v>.css  Optional. CSS only that variant needs; shipped only on pages that render it.
  render.php        Fallback when a block has no variants/ folder (receives $variant).
  style.css         Shared block CSS. Tokens only, authored with the .sx- prefix.
```

Keep layout-specific CSS in `variants/<v>.css` (`php build/split-css.php <block>` moves rules that only target one variant). It keeps pages small (the landing budget is 64 KB) and means
sites using different variants share fewer identical CSS rules.

## 2. Templates

A template receives exactly two variables:

| Variable   | Type   | Notes |
|------------|--------|-------|
| `$props`   | array  | Already merged with `fields.json` defaults. |
| `$variant` | string | Resolved variant name. |

Rules:

- Never call `get_field()`, `the_content()` or other WP functions. The WP adapter fills `$props` for you.
- Never print user input without `e()`. URLs go through `sx_url()`.
- Every class goes through `cls()` / `cx()`, never written literally. This lets the build rename classes per site.
- UI strings use `t('key')` (from `i18n/strings.json`). Market text uses `tm($map)`. Money uses `money()`.
- A block may render another block with `sx_block('name', $props)` (the footer renders the compliance panel this way).
- Brand CTAs are always `sx_cta('<position>', $classes)`. Never put a CTA label or URL in `fields.json`:
  the site has one label (`cta_label`) and one tracked URL (`links.go`).
- Brand facts (licence, payout time, founding year, rating) come from `sx_proof_points()`, never from props.

```php
<a class="<?= cx('btn', ['btn', null, 'primary']) ?>" href="<?= sx_url($cta['url']) ?>"><?= e($cta['label']) ?></a>
```

## 3. Variants

- A variant is a **structural** difference (different markup or layout), not a colour change. Colour and shape come from tokens.
- List variants in `block.json` → `sx.variants`. Aim for 3–5 per block by the end of each phase.
- `variant: "auto"` (the default) picks one with the site seed: `sx_pick($variants, "variant:<name>:<n>")`.
  Same seed means the same site every build. Change the seed to reshuffle.
- Each variant must meet every guardrail in `sx.guardrails`.

## 4. fields.json

```json
{ "fields": [
  { "name": "title", "type": "text", "label": "Headline", "required": true, "maxlength": 60, "default": "" },
  { "name": "cta", "type": "link", "label": "Primary button", "default": null },
  { "name": "items", "type": "repeater", "max": 4, "default": [], "fields": [ { "name": "value", "type": "text" } ] }
] }
```

Supported types: `text`, `textarea`, `url`, `select` (with `choices`, optional `multiple`), `true_false`, `link`, `repeater`, `group`.

Validation keys the builder enforces on blueprint sites: `required`, `maxlength` (strings), `min` / `max` (repeaters).
The same keys drive the generated JSON Schema (`php build/config-schema.php <type>`), so set them on every field
a content pipeline will fill.
`link` props are always `{label, url}`; the adapter converts ACF's `{title, url}`.

`php wp/generate-acf-json.php` turns these into ACF local-JSON groups. Never edit the generated JSON by hand.

## 5. CSS

- Use only token variables (`--c-*`, `--f-*`, `--fs-*`, `--sp-*`, `--r*`, `--card-*`, `--hero-*`). No literal colours,
  except neutral overlays such as `rgba(0,0,0,.7)` behind modals.
- Use logical properties (`margin-inline`, `inset-inline-start`, `border-block-end`) so RTL works later.
- Author selectors with `.sx-`. The build rewrites `.sx-` to the site prefix and strips comments.
- Code dialects (`lib/dialect.php`) then rewrite class names (BEM, camelCase, flat or hashed), custom property
  names, colour formats, units and declaration order per site. So: scripts find elements by `data-*` attributes or
  ids, never by class name; never build a class name from pieces in CSS or JS (`'sx-' + name`); class-substring
  selectors (`[class*="__card"]`) are allowed only in looks, where the dialect expands them to the matching classes.
- Mobile first in spirit: every layout must work at 360px with no horizontal scroll.

## 6. Roles and required blocks

`block.json` → `sx.role` tells the builder where a block goes: `header` before `<main>`, `footer` after,
`age-gate` and `sticky` as overlays after the footer, `compliance` strip at the top. Everything else goes inside `<main>`.

Each market's `compliance.required_blocks` (currently `age-gate` and `compliance-bar`) is enforced:
the builder adds a missing block and prints a warning.

## 7. Market gating

- `sx.requires.vertical` (for example `"casino"`) means the block is skipped in markets where that vertical is off.
- `sx.variant_requires` (for example `{"game-strip": "casino", "scoreboard": "sports"}`) removes single variants
  in markets where their vertical is off; seeded picks never land on them and a pinned one is replaced with a warning.
- Content rules live in the market file, not in templates. Example: `bonus-card` reads
  `promotions.welcome_bonus`. If it is `restricted`, an offer card renders as a feature card unless
  `allow_offer` is set, which compliance must approve.

## 8. Diversification levers (in order of impact)

1. Token preset (`tokens/presets/*.json`): type, shape, surface style, density, hero background.
2. Block variants: structural layouts chosen per site by seed.
3. Composition: which optional blocks a site type uses, and in what order (blueprints, from Phase 1).
4. Class prefix (`"prefix": "auto"`) and comment-free CSS: no shared class names or comments across sites.
4a. Code dialect (`"dialect"`, seeded per site, `lib/dialect.php`): the same design written differently, so sibling
   sites share almost no CSS or markup text. Pin choices with `"dialect": {"classes": "short"}`; `"dialect": false` turns it off.
5. Token overrides per site (`"tokens": {"color": {"accent": "#..."}}`) for brand colour.

## 9. Adding a block (checklist)

- [ ] `block.json` with `name: "sx/<name>"`, `acf.renderCallback: "sx_acf_render"`, `sx.variants`, `sx.role`, `sx.guardrails`
- [ ] `fields.json` with defaults for every optional field
- [ ] At least 2 variants that are structurally different
- [ ] `style.css` passes the CSS rules above
- [ ] Works in all 4 demo locales (`en`, `ru`, `kk`, `pt`) without overflow
- [ ] Added to the style-tile page or a type demo, and the build prints no new warnings
- [ ] `php wp/generate-acf-json.php` re-run

## 10. Multi-page sites

Blocks never hard-code internal paths. Use `sx_page_url('<page id>')` for links, `sx_home_url()` for the logo,
`sx_pages()` / `sx_page_id()` to know the site's pages and the current one, and `sx_abs_url()` for canonical or
JSON-LD URLs. On one-page sites `sx_pages()` is empty, so a block must still render without it.
See [docs/types/satellite.md](types/satellite.md).
