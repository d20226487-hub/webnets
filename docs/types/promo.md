# Site type: one-page brand bonuses and promo codes (`promo`)

Status: **Phase 3 complete: Demos A, B, C (demo-only market).** Brief: [docs/briefs/03-bonuses-promo-codes.md](../briefs/03-bonuses-promo-codes.md) ·
Blueprint: [blueprints/promo.json](../../blueprints/promo.json) · Schema: `dist/schemas/promo.schema.json`

> **Not usable in KZ, AU or BR yet.** Their promotion policies are `restricted`/`unknown` until compliance answers,
> so the build refuses this type there. Demo C also uses the demo market; a real-market demo follows once one is cleared.

Reference demos (fictional brands, demo-only market `xx`; one demo per language: EN, RU, PT):

| Demo | Config | Review page |
|---|---|---|
| A · Vantro · EN · welcome angle · code VANTRO100 | [sites/demos/promo-a-vantro-xx.json](../../sites/demos/promo-a-vantro-xx.json) | https://claude.ai/artifact/X2fNCbnTMZwtcnXzWVtuwG |
| B · Nyxa · RU · everyday angle · no-code lead offer | [sites/demos/promo-b-nyxa-xx.json](../../sites/demos/promo-b-nyxa-xx.json) | https://claude.ai/artifact/LDUjXqQSoR8y2xd1Q4GzpB |
| C · Marlowe Club · PT · code-first angle · centred code MARLOWE50 · casino offers | [sites/demos/promo-c-marlowe-xx.json](../../sites/demos/promo-c-marlowe-xx.json) | https://claude.ai/artifact/4wB8VkYemLJ4erWZ8qNYhb |

## 1. What the page is for

The visitor searched for the brand's code or offers. The page shows what is **really** available in their market,
makes claiming it easy (copy code → Claim), and puts the significant terms next to the offer. It must stay true:
everything about an offer comes from the promotion feed, and the page refuses to build when there is nothing true to show.

## 2. Where the data comes from

| Data | Lives in | Who maintains it |
|---|---|---|
| Which promotion types a market allows | `markets/<id>.json` → `promotions` (`allowed` / `restricted` / `unknown`) | Compliance, once per market |
| The offers: value, code, terms, validity, verified date, terms URL | `brands/promotions/<brand>.json` (copy `_template.json`) | Affiliate team, whenever an offer changes |
| Headline, subtitle, how-to-claim steps, FAQ | site config `content` | Content writer or pipeline |
| Tracked link (usually with the code in it) | site config `links.go` | Per site |

Feed fields: `id`, `type` (one of the eight policy types), `markets`, `title` (per locale), `value`
(`match`, `boost`, `cashback`, `free_bets`, `free_spins`, `prize_pool`), `code` (or `null`), `code_scope`,
`terms` (min deposit, wagering, min odds, max bet, expiry days, eligibility, excluded payments, games),
`valid_from`, `valid_to`, `verified_at`, `terms_url`.

An offer appears on a page only if **all** hold: its `markets` include the site's market, its `type` is `allowed`
there, and today is between `valid_from` and `valid_to`.

## 3. Build a promo page

1. Make sure the brand's feed has a live offer for the market, with a recent `verified_at`.
2. Copy [sites/templates/promo.json](../../sites/templates/promo.json); set brand, market, preset, angle, seed.
3. Put the tracked link (with the code if the operator uses one) in `links.go`.
4. Fill `content`: headline (optional), steps, FAQ, final band. Do not type values, codes or terms; they come from the feed.
5. `php build/build.php sites/<project>/<id>.json`. **A build with warnings or errors is not shippable.**
6. Re-build at least weekly: verification goes stale after 14 days and offers expire.

Content pipelines target `dist/schemas/promo.schema.json` and must never generate offer values, codes, terms, dates or
"exclusive" claims.

## 4. Page anatomy

| Section id | Block | Rule | Variants |
|---|---|---|---|
| (strip) | compliance-bar | when the market requires it | strip |
| header | header | required | minimal, minimal-split (CTA label "Claim offer") |
| hero | offer-hero | required, first | code-ticket, split-offer, centered-code |
| trust | trust-bar | required | strip, badges |
| terms | promo-terms | required, middle | table, checklist |
| steps | steps | required, middle | row, timeline |
| more | promo-list | optional, middle, never first | cards, list |
| faq | faq | required | accordion, columns |
| final | cta-band | required, shows the lead code | solid, split |
| sticky-cta | sticky-cta | required | code-bar (code + copy + Claim) |

| Angle | Lead offer | Available when the market allows |
|---|---|---|
| welcome | welcome / deposit bonus / free bets | any of those |
| code-first | first offer that has a code | promo_code, welcome or deposit bonus |
| everyday | odds boost / cashback / tournament | any of those |
| loyalty | loyalty programme | loyalty |

## 5. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Market allows no promotion type this page needs | **Build fails** (no page) |
| No live offer for brand + market (all expired, none in feed) | **Build fails** |
| Demo-only market without `"demo": true` | **Build fails** |
| An offer has expired | Removed from the page, build error message |
| `verified_at` older than 14 days | Warning per offer |
| Offer type not allowed in the market | Silently left out, whatever the feed says |
| Value, code, two key terms and Claim above the fold on phones | Layouts enforce it; checked at 375×667 |
| Codes never wrap; copy has a select-text fallback | `sx_code_box()` |
| Copy and claim tracked separately | `data-cta="copy-<pos>"` vs `data-cta="hero|list|final|sticky|header"` |
| Verified date and full-terms link on every offer | All offer blocks |
| No countdowns, no click-to-reveal | Not implemented by design |

## 6. Diversity

With three hero layouts built, **one project can hold 3 promo pages** that pass the diversity rule (distinct preset,
angle and hero). The demos overlap 11–56%: most sections have two layouts, so a third page repeats some of them.

### Offer modes

`"offer_mode"` in the site config sets how offers are shown:

| Mode | What the page shows |
|---|---|
| `mixed` (default) | Coded offers with their code box, code-free offers as cards |
| `banners` | Every offer as a visual banner (`offer-hero` variant `banner`, `promo-list` variant `banners`), no code boxes. Coded offers appear only when the feed marks `code_via_link: true` (the tracked link applies the code); the page then says so. Write code-free copy for this mode. |
| `codes` | Only offers with a code, each with its code box |

Promo demo B (Russian) switches between the three (review link: https://claude.ai/artifact/LDUjXqQSoR8y2xd1Q4GzpB,
`php build/variations.php sites/demos/promo-b-nyxa-xx.json`). In `mixed` the other offers show as banners, with the code box under a coded banner.

## 7. Known gaps

- Compliance answer for KZ, AU, BR (the type is blocked there until then).
- Who maintains the feed, and whether codes are exclusive per satellite (`code_scope`).
- Multi-locale offers: titles and terms are per-locale maps; the demo market `xx` serves EN, RU and PT (dates and numbers follow `locale_formats` in `markets/xx.json`).
