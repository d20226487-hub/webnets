# Brief 07: Small brand review site (user reviews)

Status: **Demo A built** · Phase 7 · Site type id: `reviews` · Guide: [docs/types/reviews.md](../types/reviews.md)

## 1. Job of the site

A small site where players review **one brand**, in the style of a review platform rather than an affiliate page. The
visitor searching "{brand} reviews" / "{brand} отзывы" / "{brand} é confiável" wants to know what other players say,
especially about payouts and support, before signing up.

| | |
|---|---|
| Primary conversion | Brand CTA click after reading reviews (one tracked link) |
| Secondary conversion | A submitted review (once collection is live) |
| Primary KPI | Outbound CTR from review pages |
| Secondary KPIs | Topic-filter use, scroll depth on the review list, review submissions |
| Traffic | "{brand} reviews", "{brand} withdrawal problems", "{brand} legit", "{brand} отзывы", "{brand} reclame aqui" |
| Visitor mindset | Sceptical. "Do they pay out? What goes wrong, and does the brand fix it?" |

The site only works if it is believable. A page of five-star praise converts worse than an honest mix, and it is the
kind of content that regulators and search engines act against. Every rule below follows from that.

## 2. Seeded reviews: demo only, by construction

You asked for a mockup with seeded reviews. Fabricated reviews presented as real are unlawful in all three markets
(misleading conduct under Australian Consumer Law, the consumer code in Brazil, advertising law in Kazakhstan) and
violate search-engine policies. So seeding is built as a **demo-only feature** that cannot leak into production:

| Rule | Enforced by |
|---|---|
| Every review has `source`: `seed` (written by us for demos) or a real source (`site-form`, `import`) | Review feed schema |
| A config with any `seed` review builds only with `"demo": true` | **Build fails** otherwise |
| A demo build shows a "Sample reviews for a design demo" banner on every page | Forced by the builder, not optional |
| Seeded reviews never get review structured data | Builder |
| Demo seed sets must include a realistic spread (some 1–2 star reviews, complaints about payouts or verification) | Build warning if fewer than 20% are 1–3 stars |

## 3. Honesty rules for real reviews (production)

1. **Show every published review**, sorted newest first by default. No hiding low ratings; no "best first" default.
2. **Rating summary computed from the feed only**: average, count, 5→1 distribution, per-topic scores. No typed-in score.
3. **No `AggregateRating` structured data.** Search engines ignore or penalise ratings a site publishes about a business
   it promotes; showing it risks a manual action. The summary is visible on the page only.
4. **Moderation policy is public** (its own page): what is removed (abuse, personal data, spam, ads, duplicate
   accounts) and what is **never** removed (negative opinions).
5. **Brand replies** are shown under the review, labelled "Reply from {brand}", with date. Only the operator supplies them.
6. **Affiliate disclosure** on every page: "We earn a commission if you sign up through our links. It never changes
   which reviews we publish."
7. **Gambling-advertising rules apply to reviews**, because the affiliate publishes them. Moderation removes win
   brags as the main message, "easy money" or income claims, and bonus offers where the market restricts them.
   (Players can still say they won or lost; they can't advertise it.)
8. **Personal data**: display name = first name + initial, city optional. No email, no account numbers.

## 4. Data

| Data | Lives in | Who maintains it |
|---|---|---|
| Reviews: id, market, locale, name, date, rating 1–5, topics, title, text, status, source, optional brand reply | `brands/reviews/<brand>.json` (new; seed file for demos) | Moderators (production), us (demo seeds) |
| Topics: payouts, verification, support, app, odds/games, deposits | `i18n/strings.json` + blueprint | Fixed list |
| Headline, intro, moderation policy text, FAQ | site config `content` | Content writer or pipeline |

Only `status: published` reviews for the site's market are shown. One brand, one market per site.

## 5. Pages (multi-page, reusing Phase 5)

| Page | Slug | Rule | Sections |
|---|---|---|---|
| home | `/` | Required | rating-summary hero (score, count, distribution, CTA), topic scores, latest reviews (6), "most discussed" topic chips, brand facts strip, FAQ, final CTA |
| reviews | `/reviews/` | Required | page-hero, topic + rating filters, full list (paged at 20; filters work without JavaScript via anchors, JS adds instant filtering), final CTA |
| write | `/write-a-review/` | Required | page-hero, guidelines, review form (**mockup**: disabled submit in demos), what happens next |
| policy | `/how-we-moderate/` | Required | prose: moderation rules, disclosure, contact |
| topic pages | `/reviews/payouts/` etc. | Optional (angle) | page-hero with topic score, filtered list, related topics |

New blocks: `rating-summary` (hero), `review-list` (cards/compact), `review-card`, `topic-scores` (bars/grid),
`review-filters`, `review-form` (mockup), `disclosure` (one line, every page).

## 6. Conversion without compromising trust

- The CTA sits **next to the evidence**: in the rating-summary hero and after every 6 reviews ("Read enough? Visit {brand}"),
  never inside a review card.
- Payout and verification topics get their own scores up front: the two things sceptical players check first.
- Brand replies that resolved a complaint are visible; they convert better than unanswered praise.
- Sticky CTA on phones as on other types; no exit pop-ups.

### Anti-patterns (never)

- Only 4–5 star reviews; hidden or collapsed negative reviews; "verified" badges we cannot back up
- Star ratings in search results via structured data
- Reviews written or edited by us presented as player reviews (outside the demo banner)
- Incentivised reviews (bonus for a review) without disclosure; in KZ/AU/BR, no incentives at all

## 7. Angles

| Angle | Home leads with | Optional pages |
|---|---|---|
| `payout-focus` | Payout topic score + latest payout reviews | topic page "payouts" |
| `balanced` | Overall score + distribution | none |
| `support-focus` | Support topic score + brand reply rate | topic page "support" |

## 8. Demos planned

| Demo | Brand | Market | Language | Preset | Angle | Notes |
|---|---|---|---|---|---|---|
| A | Nyxa | KZ | RU | fresh-minimal | payout-focus | First Russian multi-page site; 21+; 24 seeded reviews, demo banner |
| B | Marlowe Club | BR | PT | velvet-club | support-focus | Later |
| C | Vantro | AU | EN | editorial | balanced | Later |

## 9. Acceptance criteria for Demo A

- [x] Builds only with `"demo": true`; demo banner on every page; no review structured data
- [x] Summary, distribution and topic scores computed from the seed file; at least 20% of seeds are 1–3 stars
- [x] Rating summary, a payout score and the CTA above the fold at 375×667
- [x] Filters work without JavaScript; with JavaScript they filter instantly
- [x] Write-a-review form renders, is accessible, and cannot submit in demo mode
- [x] Disclosure line on every page; moderation-policy page linked from every page; under 60 KB per page; no warnings

## 10. Open questions

1. **Collecting real reviews:** on WordPress a review post type with a moderation queue is straightforward; static
   sites need a form service or no collection at all. Which do you want, or is this mockup-only for now?
2. **Moderation:** who approves reviews, and within what time?
3. **Brand replies:** will operators supply replies, or should the reply feature wait?
4. **Importing reviews** from app stores or review platforms needs their permission and licence terms. Is that in scope?
5. **Demos:** only A (like Phase 5), or A, B and C?
