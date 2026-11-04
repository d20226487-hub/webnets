# Site type: one-page brand vendor overview (`vendor`)

Status: **Phase 4 complete.** Brief: [docs/briefs/04-vendor-overview.md](../briefs/04-vendor-overview.md) ·
Blueprint: [blueprints/vendor.json](../../blueprints/vendor.json) · Schema: `dist/schemas/vendor.schema.json`

> **Casino markets only.** KZ and AU do not allow online casino, so the build refuses this type there. This is a
> legal fact, not a pending compliance answer.

Reference demos (fictional brands and studios):

| Demo | Config | Review page |
|---|---|---|
| A · Marlowe Club · BR · Emberforge · new-releases · spotlight hero | [sites/demos/vendor-a-marlowe-br.json](../../sites/demos/vendor-a-marlowe-br.json) | https://claude.ai/artifact/D2R4dMEyfAtHmKqSGYi1cX |
| B · Nyxa · xx (demo) · RU · Tidewell Studios · popular · stats hero + table | [sites/demos/vendor-b-nyxa-xx.json](../../sites/demos/vendor-b-nyxa-xx.json) | https://claude.ai/artifact/FPWQnGzwiLhwTbFy13ptyf |
| C · Marlowe Club · xx (demo) · Paper Lantern Games · studio · studio-banner hero | [sites/demos/vendor-c-marlowe-xx.json](../../sites/demos/vendor-c-marlowe-xx.json) | https://claude.ai/artifact/MmS16an8n8Tx798wBS98kg |

## 1. What the page is for

"{Provider} slots at {brand}": one studio's games **as the brand carries them** in the visitor's market. The visitor
wants to know whether the brand has the studio, which games are new or popular, and what they are like. Every game
has its own Play link; the lobby CTA is above the fold.

## 2. Where the data comes from

| Data | Lives in | Who maintains it |
|---|---|---|
| Studio facts: name, founded, signature (per locale), colours, games with studio RTP, volatility, max win, release date | `brands/providers.json` | Affiliate team, shared by all brands |
| What the brand carries per market: game ids, date added to the brand, popularity rank, **the RTP version the brand runs** | `brands/catalog/<brand>.json` (copy `_template.json`) | Affiliate team or an operator game feed |
| Headline, subtitle, about-the-studio items, FAQ, final band | site config `content` | Content writer or pipeline |
| Lobby link, per-game link pattern (`{game}`), other-studio pattern (`{provider}`) | site config `links.go`, `links.game`, `links.provider` | Per site |

Rules the code applies:

- A game appears only if the brand's catalog lists it for the site's market.
- RTP shown is the catalog's (brand + market) value, labelled e.g. "96.4% (Marlowe Club, BR)". `null` gives
  "Set by {brand}". The studio's own RTP is never shown as the brand's.
- **New** = added to the brand within 60 days of `today` (`SX_NEW_DAYS` in `lib/vendor.php`).
- **Popular** = catalog `rank`. Without ranks the section is titled "{provider} games at {brand}".
- "More providers" lists only studios in the same catalog market, with game counts. No ranking between them.

## 3. Build a vendor page

1. Make sure `brands/catalog/<brand>.json` lists the provider's live games for the market (ids must exist in
   `brands/providers.json`).
2. Copy [sites/templates/vendor.json](../../sites/templates/vendor.json); set brand, market, provider, preset, angle, seed.
3. Set `links.go` (provider lobby) and `links.game` (with `{game}`) if the brand allows game deep links; without it every
   game links to the lobby. Optional `links.provider` (with `{provider}`) for the "more providers" links.
4. Fill `content`. Don't write RTPs, volatility, max wins or counts into copy the data could contradict;
   if the FAQ names games, use the names from `providers.json`.
5. `php build/build.php sites/<project>/<id>.json`. **A build with warnings or errors is not shippable.**
6. Re-build when the catalog changes, and at least monthly ("New" badges age out after 60 days).

## 4. Page anatomy

| Section id | Block | Rule | Variants |
|---|---|---|---|
| (strip) | compliance-bar | when the market requires it | strip |
| header | header | required | minimal, minimal-split (short label "Play") |
| hero | vendor-hero | required, first | spotlight, stats, studio-banner |
| trust | trust-bar | required | strip, badges |
| new | game-grid (mode new) | optional; skipped when fewer than 2 new games | strip (feature planned) |
| games | game-grid (mode popular) | required, after `new` | grid, table |
| explainer | rtp-explainer | required, after `games` | inline, card |
| about | features | optional, never first | grid, rows, bento |
| more | provider-links | optional | chips, logos-text |
| faq | faq | required | accordion, columns |
| final | cta-band | required | solid, split |
| sticky-cta | sticky-cta | required | bar, pill (short label "Play") |

| Angle | Leads with | Hero options | Optional sections on |
|---|---|---|---|
| new-releases | games recently added to the brand | spotlight, studio-banner | new, about |
| popular | the brand's most-played titles | stats, studio-banner | about |
| studio | who the studio is | studio-banner, stats | about, more |

## 5. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Market does not allow casino (KZ, AU) | **Build fails** (`requires_vertical` in the blueprint) |
| No `provider` in the config, or the brand carries none of its games in the market | **Build fails** |
| Catalog lists a game id unknown to `providers.json` | Warning, game skipped |
| RTP unknown for the brand | "Set by {brand}", never the studio figure |
| Every game has its own tracked CTA | `data-cta="game|game-new|game-hero"` + `data-game="<id>"`; lobby CTAs use `hero|header|final|sticky` |
| Lobby CTA and games count above the fold at 375×667 | Hero layouts enforce it |
| Long lobby labels | Header and sticky use the short label; other buttons wrap (`btn--wrap`) |
| RTP and volatility explained once, near the games | `rtp-explainer` is required and must follow `games` |
| No "hot/due" slots, win-rate claims, demo play, studio rankings, licensed art | Not implemented by design; covers are CSS-drawn in studio colours |

## 6. Diversity

Three hero layouts, two game layouts and two explainer layouts: **one project can hold 3 vendor pages per brand**
that pass the diversity rule. Demos A–B and B–C overlap 11%. A–C overlap 50% (same brand, both on the grid layout)
and still passes.

### One studio or several, and provider colours

- `"providers": ["emberforge", "tidewell", ...]` (with `"provider"` as the lead studio) turns the page into a
  several-studio overview: the hero shows every studio with its colour, the counts cover all of them, the games are
  grouped per studio, game cards name their studio and `{provider}` in copy reads as the list of studios. Turn off the
  `more` section (other studios) and write the about/FAQ copy for all studios.
- `"look": "provider"` paints the page in the lead studio's own colours (`brands/providers.json` `colors`), beside the
  shared looks `neutral` and `brand` (the casino brand's colour).

Vendor demo B (Russian) switches between one / several studios and original / neutral / casino brand / provider brand colours
(review link: https://claude.ai/artifact/FPWQnGzwiLhwTbFy13ptyf, `php build/variations.php sites/demos/vendor-b-nyxa-xx.json`).

## 7. Known gaps

- Game feed from operators or the affiliate program (catalogs are hand-maintained until then).
- Licensed thumbnails and logos (`sx_game_cover()` is the single place to swap in real art).
- Whether brands allow tracked deep links to a single game (otherwise drop `links.game`).
- `feature` layout for the new-releases section.
- A "provider hub" (several studios on one page) would be its own type.
