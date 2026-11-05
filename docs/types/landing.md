# Site type: one-page branded landing (`landing`)

Status: **Phase 1 complete.** Brief: [docs/briefs/01-one-page-landing.md](../briefs/01-one-page-landing.md) ·
Blueprint: [blueprints/landing.json](../../blueprints/landing.json) · Schema: `dist/schemas/landing.schema.json`

Reference demos (fictional brands):

| Demo | Config | Review page |
|---|---|---|
| A · Vantro · AU · EN | [sites/demos/a-vantro-au.json](../../sites/demos/a-vantro-au.json) | https://claude.ai/artifact/6rq8HUmPD4bWTVm5MByQ4Z |
| B · Nyxa · demo market · RU | [sites/demos/b-nyxa-xx.json](../../sites/demos/b-nyxa-xx.json) | https://claude.ai/artifact/2qsjUPWpWFcfPYNFPJbYtf |
| C · Marlowe Club · BR · PT | [sites/demos/c-marlowe-br.json](../../sites/demos/c-marlowe-br.json) | https://claude.ai/artifact/BRe64qmtbdQHAFSL37CiRt |

## 1. What the page is for

One brand, one page, one goal: a click to the brand's registration page through the tracked link.
Everything on the page either builds trust or removes a reason not to click. Read section 3 of the brief
before changing anything.

## 2. Build a landing site by hand

1. Copy [sites/templates/landing.json](../../sites/templates/landing.json) to `sites/<project>/<id>.json`.
2. Set `brand`, `market`, `preset`, `angle` and a unique `seed`. Keep `"prefix": "auto"`.
3. Put the tracked URL in `links.go`. It is used by every CTA. In Brazil it must be a `.bet.br` domain.
4. Fill `content`. Every TODO must go. Limits are in the schema and in each block's `fields.json`.
5. Build: `php build/build.php sites/<project>/<id>.json`
6. Read the output. The build prints the resolved page, for example
   `header:minimal → hero:stat-led → trust:badges → steps:timeline → …`, and any warnings.
   **A build with warnings is not shippable.** The command exits 1 on warnings.
7. Check the project: `php build/diversity.php sites/<project>/*.json` (see section 6).
8. Run the [QA checklist](../qa-checklist.md) on the output in `dist/sites/<id>/index.html`.

## 3. Build landing sites automatically

The content pipeline produces one JSON file per site that validates against
`dist/schemas/landing.schema.json` (regenerate with `php build/config-schema.php landing`).

Suggested flow:

```
brief inputs (brand, market, angle)  →  generator fills "content" against the schema
  →  php build/build.php <file>       →  warnings? regenerate the failing fields
  →  php build/diversity.php <project> →  FAIL? change preset / angle / seed and rebuild
```

Rules for generators:

- Pick `angle` per site and write copy to the angle's hero formula (brief, section 5).
- Leave `variants` empty. The seed chooses within the blueprint's rules. Change the `seed`, not the variants, to reshuffle.
- Never generate compliance text, ages, licence numbers, payment lists or ratings. Those come from `markets/` and `brands/`.
- Never generate offers, bonus amounts or promo codes for KZ, AU or BR.
- Showcase odds, tables and fixtures are illustrations. The `note` field must say so.

The builder checks `required`, `maxlength` and repeater `min`/`max` from `fields.json`, so a generator that ignores
the schema still cannot ship broken content without a warning.

## 4. Page anatomy

Order: frame top → header → **top zone** → **middle zone** (seeded order) → **bottom zone** → footer → overlays.

| Section id | Block | Rule | Variants available | Notes |
|---|---|---|---|---|
| (strip) | compliance-bar | required in AU | strip | Mandated tagline (AU) |
| header | header | required | minimal, minimal-split | Never sticky on landings; no nav |
| hero | hero | required, first | split, centered, scoreboard\*, stat-led, full-bleed | \*sports markets only |
| trust | trust-bar | required | strip, badges | Values from brand + market only |
| features | features | required, middle | grid, rows, bento | 3–4 items |
| showcase | showcase | optional, middle | odds-board\*, game-strip† | \*sports, †casino markets only; never right after hero |
| steps | steps | required, middle | row, timeline | Exactly 3 steps; carries the mid CTA |
| payments | payments | optional, middle | table, tiles | Always after features; methods must exist in the market |
| faq | faq | required, bottom | accordion, columns | 4–6 Q&As; emits FAQPage JSON-LD |
| final | cta-band | required, last | solid, split | No urgency claims |
| footer | footer | required | compact | Embeds the compliance panel |
| age-gate | age-gate | required | modal, fullscreen | Age from market |
| sticky-cta | sticky-cta | required | bar, pill | Phones only |

Angles switch optional sections on and narrow variant lists:

| Angle | Turns on | Hero options |
|---|---|---|
| matchday | showcase (odds-board) | scoreboard, split |
| fast-payout | payments | stat-led, split |
| app-first | — | split, centered |
| trusted | payments | centered, split |
| live-casino (casino markets) | showcase (game-strip) | full-bleed, split |

## 5. Conversion guardrails the code enforces

| Guardrail | Where it is enforced |
|---|---|
| One CTA label and one destination | `sx_cta()` in lib/render.php. Blocks have no CTA fields. |
| CTA position tagging for analytics | `data-cta="header|hero|card|mid|final|sticky"` on every CTA |
| `rel="nofollow sponsored"` on brand links | `sx_cta()` |
| No nav on landings | Blueprint locks the header to minimal variants |
| Required compliance (age gate, panel, AU strip) | Builder adds missing blocks and warns |
| Offers blocked where restricted | bonus-card switches to feature mode and warns |
| Casino/sports layouts only where allowed | `variant_requires` in block.json |
| `.bet.br` brand domain in Brazil | Site context check |
| Content limits | Blueprint validation against fields.json |
| Ratings only with a source | `sx_proof_points()` |

Not enforced by code (check in QA): CTA above the fold at 375×667 with your real copy, Lighthouse scores, copy quality.

## 6. Diversity inside a project

`php build/diversity.php sites/<project>/*.json` resolves every config and compares each pair:

- **FAIL** if two sites share the preset, the angle, the hero variant or the class prefix.
- **Overlap** is the share of sections using the same variant (footer and age gate excluded). Keep it under 60%.

With today's library, one project can hold **4 landings per sports-only market (KZ, AU) and 5 in BR** that pass
the rule, because the angle must differ and live-casino needs a casino market. Above about 3 sites, overlap rises
because most middle blocks have only 2 variants. The demos show this:
A vs C overlap is 67%. Pin different `variants` for the worst pair, or add variants (see section 8).

## 7. WordPress

Each section is an ACF block (`sx/hero`, `sx/trust-bar`, …). Build the page in the editor in the order the static
build prints, using the same variant and content. The field groups come from `fields.json`
(`php wp/generate-acf-json.php`). A WP page template that assembles a landing from one ACF options page is a
candidate for Phase 2.

## 8. Known gaps and next steps

- Lighthouse not measured yet: run it on a staging deploy of each demo.
- WP adapter not yet tested in a live install.
- Variant depth: add a third variant to trust-bar, steps, faq and cta-band (brief lists ticker, phone-frames,
  minimal-line) to bring overlap down for projects with 4+ landings.
- `match-list` showcase and a hero image slot for `full-bleed` (if photos are approved).
- Compliance copy and Kazakh strings still need sign-off.

**Variation demo (Russian, demo market):** `php build/variations.php sites/demos/b-nyxa-xx.json` builds 6 pages: product (casino + sports / casino / sports) × length (full = adds bonus cards: the `offers` section, a promo-list with `include_lead` and `types` matching the product / compact, via `layout: compact` = hero, trust, features, final). Casino + sports shows both the odds board (`showcase`) and the live-casino game strip (the optional `games` section, also a showcase block). It runs on the demo market because casino play is illegal in KZ; `sites/demos/b-nyxa-kz.json` stays as the sports-only KZ landing. Review link: https://claude.ai/artifact/2qsjUPWpWFcfPYNFPJbYtf.
