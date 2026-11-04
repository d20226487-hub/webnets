# Brief 04: One-page brand vendor overview

Status: **complete** · Phase 4 · Site type id: `vendor` · Guide: [docs/types/vendor.md](../types/vendor.md)

## 1. Job of the page

A single-brand page about one game provider **as it exists on that brand**: "Emberforge slots at Marlowe Club".
It shows the provider's popular and newest games on the brand, with honest game facts, and sends the visitor to play
them at the brand.

| | |
|---|---|
| Primary conversion | "Play at {brand}" on a specific game (deep link) or on the provider lobby |
| Primary KPI | Game CTR (clicks on any game ÷ visitors) |
| Secondary KPIs | Lobby CTR, new-releases section engagement, scroll to "more providers" |
| Traffic | "{provider} slots {brand}", "{brand} {provider}", "{game} {brand}", "new {provider} games" |
| Visitor mindset | "Does this brand have this studio's games? Which ones are new or popular? What are they like?" |

## 2. Market and vertical rules

The page is about casino games, so it only exists where online casino is legal:

| | Casino allowed | Vendor type |
|---|---|---|
| KZ | No | **Build fails** (same mechanism as `requires_vertical`) |
| AU | No | **Build fails** |
| BR | Yes | Allowed |
| xx (demo) | Yes | Allowed (demos only) |

Unlike promo pages, this is a legal fact, not a pending compliance answer: the KZ/AU block is permanent.

## 3. Data: three sources, none typed per site

| Data | Lives in | Notes |
|---|---|---|
| Provider identity: name, founded, signature mechanic, colours, game list with RTP, volatility, max win, release date, tags | `brands/providers.json` (exists) | Shared across brands |
| What the **brand** carries: which providers and games are live per market, date added to the brand, popularity rank, **RTP version used in that market**, deep-link id | `brands/catalog/<brand>.json` (new) | Maintained by the affiliate team or an operator game feed |
| Headline, intro, FAQ | site config `content` | Content writer or pipeline |

Rules that follow from this:

- A game appears only if the brand's catalog lists it as live in the site's market.
- **RTP shown is the brand's version for that market.** Many studios ship several RTP settings and operators choose
  one. If the catalog has no RTP for a game, the page says "RTP set by {brand}" instead of guessing.
- "New" means added to **the brand** within the last 60 days (catalog date), not the studio's release date.
- "Popular" is the brand's rank from the catalog. Without ranks, the section uses release order and is titled
  "Games" rather than "Popular".

## 4. Conversion and honesty rules

1. **Lobby CTA above the fold**: "Play {provider} at {brand}" plus the number of games the brand carries.
2. **Every game card has its own CTA** to that game at the brand (`links.game` pattern with `{game}` placeholder),
   tagged `data-cta="game"` and `data-game="<id>"`.
3. **Game facts are labelled precisely**: "RTP 96.1% (Marlowe Club, BR)", "Volatility: very high", "Max win 25,000×
   stake (theoretical)". Facts come from the catalog and provider data only.
4. **Explain RTP and volatility once**, in one plain paragraph near the first game grid: RTP is a long-term
   average across all players; volatility describes how wins are spread; neither predicts a session.
5. **Same CTA rules as other types**: tracked links, `rel="nofollow sponsored"`, sticky CTA on phones.

### Anti-patterns (never)

- "Hot" or "due to pay" slots, win-rate claims, "beat the slot" tips, "best chance to win"
- Highest-max-win as the lead message ("win 50,000×!"). Max win is a fact in the card, never a headline
- Free demo play embedded on the page (age-verification rules vary; link to the brand instead)
- Provider logos or game art that the affiliate program has not licensed (demos use CSS-drawn covers)
- Ranking providers against each other or comparing brands (that is the review-site type)

## 5. Section blueprint

| # | Section | Rule | Purpose | Variants planned |
|---|---|---|---|---|
| 1 | Header | R | Logo + lobby CTA | minimal, minimal-split |
| 2 | Vendor hero | R, first | Studio identity, games at brand, lobby CTA | **studio-banner** (name over CSS art), **spotlight** (one featured new game beside the studio), **stats** (games count, newest, avg RTP at brand) |
| 3 | Trust | R | Licence, payout time, since | existing |
| 4 | New on {brand} | O (on when ≥ 2 new games), S | Games added in the last 60 days | **strip** (scroll row), **feature** (one large + list) |
| 5 | Popular games | R, S | Top games at the brand with facts and CTA | **grid**, **table** (sortable-looking comparison of RTP/volatility/max win, no JS sort needed) |
| 6 | RTP & volatility explainer | R, after the first game section | One paragraph + a 3-step volatility scale | **inline**, **card** |
| 7 | About the studio | O, S | 3–4 facts: founded, signature mechanics, number of games | existing features block (rows) |
| 8 | More providers at {brand} | O | Other providers the brand carries in the market, linking to their pages or lobbies | **chips**, **logos-text** |
| 9 | FAQ | R | Availability, RTP, mobile, deposits, limits | existing |
| 10 | Final CTA | R | Lobby CTA | existing cta-band |
| – | Footer, age gate, sticky | R | | existing (sticky: bar or pill) |

New blocks: `vendor-hero`, `game-grid` (grid/table/strip/feature layouts), `rtp-explainer`, `provider-links`.
New data: `brands/catalog/<brand>.json` loader with market filtering, RTP-version and "new" logic.

## 6. Angles

| Angle | Leads with | Hero options | Optional sections on |
|---|---|---|---|
| `new-releases` | Games added to the brand recently | spotlight, studio-banner | new-on-brand (forced), about |
| `popular` | The brand's most played titles from this studio | stats, studio-banner | about |
| `studio` | Who the studio is and its signature mechanics | studio-banner, stats | about (forced), more providers |

## 7. References (general industry knowledge)

- **Provider pages on casino directories (Casino.guru, AskGamblers style):** studio facts + game list with RTP/volatility. We keep the facts, drop the multi-brand comparison.
- **Studio websites (Pragmatic Play, Hacksaw style):** strong visual identity, new-release spotlight. Basis for the spotlight hero.
- **Operator lobbies filtered by provider:** dense game grids with one tap to play. Basis for game cards with per-game CTAs.

## 8. Demos planned

| Demo | Brand | Market | Provider | Preset | Angle | Hero |
|---|---|---|---|---|---|---|
| A | Marlowe Club | BR (PT) | Emberforge | luxury-gold | new-releases | spotlight |
| B | Nyxa | BR (PT) | Tidewell Studios | candy-pop | popular | stats |
| C | Marlowe Club | xx (EN, demo) | Paper Lantern Games | editorial | studio | studio-banner |

A and B use a real market (BR), so they can ship once their catalog data is real.

## 9. Acceptance criteria for Demo A

- [x] Lobby CTA and games count above the fold at 375×667; each game has its own tracked CTA
- [x] RTP labelled with brand and market; falls back to "RTP set by {brand}" when unknown
- [x] "New" computed from the brand catalog date; build fails in KZ/AU
- [x] No anti-pattern from section 4; under 60 KB; no warnings

## 10. Open questions

1. **Scope:** one page per provider (recommended, as above), or one page covering several popular and new providers
   at the brand? (The second could become its own "provider hub" type later.)
2. **Game data:** will operators or the affiliate program supply a game feed (game ids, per-market RTP, deep links,
   "added" dates, popularity)? Until then the catalog is maintained by hand.
3. **Assets:** are licensed game thumbnails and provider logos available through the affiliate programs? Until then,
   CSS-drawn covers in the provider's colours.
4. **Deep links:** do brands allow tracked links straight to a game, or only to the casino lobby?
