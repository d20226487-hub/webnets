# Brief 08: Multi-brand casino review site

Status: **Demo A built** · Phase 8 · Site type id: `casinos` · Guide: [docs/types/casinos.md](../types/casinos.md)

## 1. Job of the site

A small editorial site that reviews and compares several licensed online casinos in one market: a ranked list, a full
review page per brand, a comparison table and a public scoring method. Unlike every type so far, it promotes **several
brands**, each with its own tracked link.

| | |
|---|---|
| Primary conversion | CTA click on any brand (per-brand tracked link) |
| Primary KPI | Outbound CTR per brand and per position in the ranking |
| Secondary KPIs | Review-page entries, comparison-table use, clicks from category pages |
| Traffic | "melhores cassinos online", "cassino com saque rápido pix", "{brand} é confiável", "{brand} análise" |
| Visitor mindset | "Which of these is safe and pays fast? What's the catch with each?" |

## 2. Markets

Casino reviews exist only where online casino is legal, so the type is gated like `vendor`:

| | KZ | AU | BR | xx (demo) |
|---|---|---|---|---|
| Type allowed | **No** (build fails) | **No** (build fails) | Yes | Yes |
| Brands allowed | – | – | Only operators authorised in Brazil, on a `.bet.br` domain | Any fictional brand |
| Bonuses | – | – | Not reviewed or scored: Brazilian law prohibits advance bonuses (`markets/br.json`) | Allowed |

Reviewing offshore casinos for Australians would mean advertising prohibited services, which is why AU is a hard block,
not a setting. A sportsbook-comparison variant for KZ/AU is possible later (open question 1).

## 3. Data

| Data | Lives in | Who maintains it |
|---|---|---|
| Brand identity, licence, domains, app | `brands/operators.json` (exists; 3 more fictional casino brands added for the demo) | Affiliate team |
| Per-market facts: min deposit, withdrawal time by method, payment methods, support channels and hours, game count, live tables | `brands/facts/<market>.json` (new) | Affiliate team, with a `checked_at` date |
| Studios and games the brand carries | `brands/catalog/<brand>.json` (Phase 4) | Affiliate team / game feed |
| **Editorial scores** per criterion (0–10) with a one-line reason each, pros, cons, verdict, `tested_at` | `brands/ratings/<market>.json` (new) | The review team, who must actually test |
| Per-brand tracked links | site config `links.brands.<id>` | Per site |
| Headlines, intros, guides | site config `content` | Content writer |

Rules:

- **The overall score is computed** from the criterion scores and the published weights. Nobody types an overall score.
- **Every score has a reason**, shown on the review page next to the score.
- **`tested_at` older than 90 days → warning**; facts `checked_at` older than 30 days → warning (payout times change).
- Bonus criterion weight is **0 where the market's promotion policy blocks bonuses** (BR today) and the criterion is hidden.

## 4. Honesty rules

1. **Ranking = score order.** A brand can be placed above its score order only as a labelled "Parceiro em destaque"
   (featured partner) slot, at most one per list, never presented as the top-rated brand.
2. **Methodology page is required** and linked from every ranked list: criteria, weights, how testing works, how we earn.
3. **Every review shows cons.** A review with no cons fails the build (no brand is perfect, and readers know it).
4. **Affiliate disclosure** line on every page (reuses the Phase 7 `disclosure` block).
5. **No user ratings** on this type (that is the `reviews` type). Scores are editorial and labelled as such.
6. No "guaranteed", "best odds of winning", RTP-as-win-chance claims; RTP/volatility wording reuses the Phase 4 explainer.
7. Only brands licensed in the market appear; a brand without a licence number for the market fails the build.

## 5. Pages

| Page | Slug | Rule | Sections |
|---|---|---|---|
| home | `/` | Required | toplist hero (ranked brands, score, 1-line reason, CTA), quick-compare strip, category links, latest reviews, methodology summary, FAQ |
| review (one per brand) | `/analise/{brand}/` (localized slug) | Required, **generated from the data** | brand header (score, verdict, CTA), score breakdown with reasons, pros/cons, facts table, games (from catalog: studios + counts, links to vendor pages if any), payments, support, final verdict + CTA, other brands |
| compare | `/comparar/` | Required | comparison table of all brands across criteria and key facts; sticky first column on phones |
| category pages | `/saque-rapido/`, `/cassino-ao-vivo/`, `/melhor-app/` | Optional (angle) | toplist re-ranked by one criterion + short guide prose |
| methodology | `/como-avaliamos/` | Required | criteria + weights (rendered from data), testing process, disclosure |

New plumbing: **generated pages** (one page per data item: brand reviews now, articles in Phase 9), and **per-brand
tracked links** (`data-cta` + `data-brand`).

New blocks: `toplist` (cards, table), `score-box` (overall + breakdown), `pros-cons`, `facts-table`, `compare-table`,
`criteria` (methodology weights), `brand-header`, `brand-links` (other brands). Reused: page-hero, prose, faq,
page-links, disclosure, game cards and provider links from Phase 4.

## 6. Conversion

- Each toplist entry: logo, score, one reason, the key fact for the page's angle (e.g. "PIX withdrawal ~15 min"), CTA.
- The first CTA is above the fold on phones: H1 + the first two brands with CTAs at 375×667.
- Review pages: CTA in the brand header, after the score breakdown, and at the end; sticky CTA names the brand.
- Comparison table: CTA in every column header and at the bottom.
- Category pages capture specific intent ("saque rápido") and send it to the best brand for that intent.

### Anti-patterns (never)

- An unlabelled paid #1; scores without reasons; reviews without cons
- Bonus amounts or "best bonus" lists in Brazil
- Fake urgency ("only 3 spots left"), countdown timers, fake user ratings
- Copy-paste reviews: the build warns when two brands' verdicts are near-identical

## 7. Angles

| Angle | Home ranks by | Category pages on |
|---|---|---|
| `overall` | Overall score | fast withdrawals, live casino |
| `fast-payouts` | Payout criterion (ties: overall) | live casino, apps |
| `live-casino` | Live-casino criterion | fast withdrawals, apps |

## 8. Demo A

| Market | Language | Preset | Angle | Brands |
|---|---|---|---|---|
| BR | PT | editorial (or another unused for this type) | fast-payouts | Marlowe Club, Nyxa + 3 new fictional casino brands, all with `.bet.br` domains |

## 9. Acceptance criteria for Demo A

- [x] Fails in KZ/AU; fails for a brand without a BR licence or `.bet.br` domain; fails for a review without cons
- [x] Overall scores computed from criteria and weights; bonus criterion hidden in BR; ranking matches scores
- [x] H1 and the first two list entries with CTAs above the fold at 375×667 (with a featured slot: featured + #1; #2 just below)
- [x] One generated review page per brand; comparison table usable on phones; methodology page linked from every list
- [x] Per-brand tracked links with `data-brand`; disclosure on every page; under 60 KB per page; no warnings

## 10. Open questions

1. **Vertical:** casino only (BR and demo market), or also a sportsbook-comparison variant that KZ and AU could use?
2. **Testing:** who tests and scores the brands, and how often are scores refreshed?
3. **Paid placement:** are any ranking positions sold? If yes, they are shown as the labelled featured slot only.
4. **Brands per site:** how many (5–10?), and where will real per-market facts come from?
5. **Demos:** A only, like the last two phases?
