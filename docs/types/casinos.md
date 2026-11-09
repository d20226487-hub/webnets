# Site type: multi-brand casino review site (`casinos`)

Status: **Phase 8: Demos A, B, C.** Brief: [docs/briefs/08-multi-brand-casino-reviews.md](../briefs/08-multi-brand-casino-reviews.md) ·
Blueprint: [blueprints/casinos.json](../../blueprints/casinos.json) · Schema: `dist/schemas/casinos.schema.json`

> **Casino markets only** (BR, demo market). KZ and AU fail the build: casino is not legal there.
> With `"product": "sports"` the site needs the sports vertical instead (see Products below).

## Products: casino, sports, or both

`"product"` in the site config (optional) picks `products.<product>` in the blueprint:

| Product | Brands must offer | Criteria (weights) | "Live" ranking means | Headline facts |
|---|---|---|---|---|
| `casino` (= no product) | casino | safety 25, payouts 25, games 15, live 10, app 10, support 15, bonuses 10 | live casino | withdrawal, live tables, games, support |
| `sports` | sports | safety 20, payouts 20, odds 20, markets 10, live_bet 10, app 10, support 10, bonuses 10 | live betting (`live_bet`) | withdrawal, live events a day, markets per match, margin, support |
| `both` | casino **and** sports | safety 20, payouts 20, games 10, live 10, odds 10, live_bet 10, app 10, support 10, bonuses 10 | mean of live casino and live betting (`live_any`, blueprint `composite`) | withdrawal, live tables + events, games, markets, support |

- `rank_by: live` keeps working in every product (blueprint `alias`), so a rank switcher stays valid when the product changes.
- Sportsbook data: `sports` in `brands/facts/<market>.json` (`count`, `markets_per_match`, `live_events`, `margin`) and
  `odds`, `markets`, `live_bet` scores in the ratings. A hybrid brand's review text per product goes in `products.sports` /
  `products.both` of its rating (summary, verdict, pros, cons).
- UI strings can have product wording: `key@sports`, `key@both` (e.g. the toplist column "Букмекер" / "Оператор").

| Demo | Config | Review link (all pages clickable) |
|---|---|---|
| A · Cassino em Foco · BR · PT · fast-payouts · 5 brands · 10 pages | [sites/demos/casinos-a-br.json](../../sites/demos/casinos-a-br.json) | https://claude.ai/artifact/HFpVi7eExxJ5unqxdf2uzZ |
| B · Живой стол · demo market · RU · live-casino · product switcher (казино + спорт / казино / спорт) · comparison-table toplist · featured Verazul · Russian slugs · 8–10 pages per product | [sites/demos/casinos-b-xx.json](../../sites/demos/casinos-b-xx.json) | https://claude.ai/artifact/SgmodicpPGcZvK5gjjYDFE |
| C · Casino Scout · demo market · EN · overall · podium toplist · no featured slot · English slugs · 10 pages | [sites/demos/casinos-c-xx.json](../../sites/demos/casinos-c-xx.json) | https://claude.ai/artifact/7MqksxBRowFv4oZkVRwNuy |

## 1. What the site is for

An editorial site that ranks and reviews several licensed casinos in one market. The site is branded as a
**publisher** (`brands/publishers.json`), not as one operator, and every brand has its own tracked link.

## 2. Data

| Data | Lives in | Notes |
|---|---|---|
| Operators (identity, licence, markets, verticals, logo) | `brands/operators.json` | Demo adds Verazul, Cobaltio, Lunaro (fictional) |
| Publisher identity | `brands/publishers.json` | Header logo, footer, compliance line "we only list operators licensed by…" |
| Facts per market: min deposit, withdrawal minutes per method, payments, support, games, live tables, app, domain, `checked_at` | `brands/facts/<market>.json` | Warning after 30 days |
| Ratings per market: score 0–10 + reason per criterion, summary, verdict, pros, cons, `tested_at` | `brands/ratings/<market>.json` | Warning after 90 days; no cons = build fails |
| Criteria and weights | `criteria` in the blueprint | `requires_promo` criteria (bonuses) count only where the market allows promotions |
| Studios per brand | `brands/catalog/<brand>.json` (Phase 4), if present | Shown in the facts table |
| Compared brands, featured slot, per-brand links | site config `brands`, `featured`, `links.brands.<id>` | |

**Overall score = weighted average of criterion scores**, computed by `sx_casino_score()`. Nothing in a config or
content field can set a score or a rank.

## 3. Pages

| Page | Slug | Rule | Sections |
|---|---|---|---|
| home | `/` | required | toplist (H1, ranked by the angle's criterion, featured slot; `cards` rows, `table` real comparison table with the ranked column tinted, `podium` winner spotlight with criterion bars beside the runners-up, `list` compact rows, `spotlight` the heading in an accent panel beside the list, `grid` brand tiles with a large score on a tinted band, `logo-grid` the heading with ranking tabs beside it and a dense grid of logo tiles with the offer and a play button, `offer-cards` the top three as tall cards with offer, promo code, strengths and terms then wide rows, `portal` a large colour tile for #1, two tiles and a "top" sidebar, or `claim-cards` a three-column grid of cards with a dark logo band, the offer in large type, a claim pill, a review pill and the age line; the job form's Design option picks one, "Any" spreads them all), page links, faq, final |
| review | `/analise/{brand}/` (pt; ru `/obzor/`, kk `/sholu/`, en `/reviews/`, from `slug_locales` in the blueprint) | **generated: one per brand** | brand-header (score, rank, verdict, facts, CTA, tested date), score-box (every criterion + reason), pros-cons, facts-table, other brands (toplist list), final |
| compare | `/comparar/` | required | compare-table (H1; brands by overall score, sticky label column), related |
| fast / live / app | `/saque-rapido/`, `/cassino-ao-vivo/`, `/melhores-apps/` | angle | toplist (H1, ranked by that criterion), prose, related |
| method | `/como-avaliamos/` | required | page-hero, criteria (weights from the blueprint), prose, related; no sticky CTA |

| Angle | Home ranks by | Category pages |
|---|---|---|
| overall | overall score | fast, live |
| fast-payouts | withdrawals | live, app |
| live-casino | live casino | fast, app |

**CTAs.** Every button names its brand and carries `data-cta` + `data-brand`. Generic CTAs (sticky bar, final band)
go to the top brand for the angle on list pages and to the reviewed brand on review pages. On phones the header
CTA is hidden; the ranked list carries the buttons.

## 4. Build a comparison site

1. Add each brand's facts and rating for the market (test first; write a reason for every score; list cons).
2. Copy [sites/templates/casinos.json](../../sites/templates/casinos.json); set publisher, market, brands, links, angle, preset, seed.
3. Optional `featured`: one brand shown in a labelled paid slot above the list. It keeps its real rank and score.
4. Fill `meta` and `content`. In review-page content and meta, `{item}` is the brand's name.
5. `php build/build.php sites/<project>/<id>.json`; review link: `php build/export-review-site.php sites/<project>/<id>.json "Title"`.

## 5. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Market without casino (KZ, AU) | **Build fails** |
| Fewer than 3 brands; unknown brand; brand not licensed in the market (markets + licence number) | **Build fails** |
| Brand that does not offer casino (e.g. a sportsbook) | **Build fails** |
| Brand link not on the market's domain (`.bet.br` in BR) | **Build fails** |
| Missing facts or rating; review with no cons | **Build fails** |
| Featured brand not in the list | **Build fails** |
| Score without a reason; rating older than 90 days; facts older than 30 days; two verdicts over 80% identical | Warning |
| Ranking order | Always the computed scores; the featured slot is labelled "posição paga, Nº pela nota" |
| Bonuses in BR | Criterion weight dropped, criterion hidden, note shown on score box and methodology |
| Structured data | No `AggregateRating` / `Review` markup |

## 6. Plumbing added (reused by Phase 9)

- **Generated pages**: `"generate": "brands"` on a blueprint page makes one page per item, with `{item}` in slug,
  content and meta (`sx_expand_site()`). Generated pages stay out of menus and link cards.
- **Publisher identity** for sites that are not one operator (`publisher` in the config).
- **Per-brand CTAs** (`sx_brand_cta()`), and `cta_brand` in the context so shared blocks (sticky bar, CTA band, trust
  points) follow the page's brand.

## 7. Known gaps

- Page slugs in the blueprint are Portuguese. A site in another language sets `"slugs": {"review": "reviews/{item}", "compare": "compare", ...}` in its config (Demo C does).
- Structural overlap between the three demos is 62–82%: review, compare and method pages use blocks with one layout each. The diversity rule (preset, angle, hero) still passes; more layouts for `score-box`, `facts-table` and `compare-table` would lower it.

- Demos B and C; one review-page layout (`split`) so far.
- Who tests and how often (brief question 2); real facts source (question 4).
- A sportsbook-comparison variant for KZ/AU (question 1): the engine supports `"product": "sports"`; KZ/AU still need sportsbook facts and ratings and a sports-only angle.
- On phones the featured slot plus the #1 brand fill the first screen; the #2 brand is just below the fold.

**Variation demo (Russian, demo market):** `php build/export-review-site.php sites/demos/casinos-b-xx.json` builds product (казино + спорт: Nyxa, Verazul, Cobaltio / казино: 5 casinos / спорт: Vantro, Nyxa, Verazul, Cobaltio) × rank by (live / overall / payouts / app; `rank_by: overall` uses the overall score) × toplist (table / cards / podium): 36 combinations. The plain config is казино + спорт. Product-specific titles per ranking use `_with` (cross-axis overrides, see build/export-review-site.php). Review link: https://claude.ai/artifact/SgmodicpPGcZvK5gjjYDFE.
