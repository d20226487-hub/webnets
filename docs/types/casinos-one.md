# Site type: one-page multi-brand casino reviews (`casinos-one`)

Blueprint: [blueprints/casinos-one.json](../../blueprints/casinos-one.json) · Demo (generator source):
[sites/demos/casinos1-b-xx.json](../../sites/demos/casinos1-b-xx.json) · Brief: the multi-page type's
[docs/briefs/08-multi-brand-casino-reviews.md](../briefs/08-multi-brand-casino-reviews.md)

The multi-page casino review site (`casinos`) on one page: no review page per brand, no separate comparison or method
pages. Same rules: casino markets only (or the sports vertical with `"product": "sports"`), at least three brands
licensed in the market, the paid featured slot labelled, the affiliate disclosure at the top, negative points kept.

| Zone | Section | Block | Rule |
|---|---|---|---|
| top | hero (the ranking, first screen) | toplist: cards, table, podium, list, spotlight, grid, logo-grid, offer-cards, portal, claim-cards (A / B for cards and list); the job form's Design option picks one | required |
| middle | compare | compare-table | required |
| middle | criteria (how we score) | criteria | optional (angle) |
| middle | text | prose | optional (angle), never first |
| bottom | faq, final | faq, cta-band | required |

Products, criteria, composite scores and angles (overall, fast payouts, live casino; `rank_by`) are the multi-page
type's. On this one page, generic CTAs go to the top brand for the angle, the header menu jumps to the ranking, the
comparison and the questions, and a sidebar (when the site has one) lists the ranked brands, each to its own site.

The demo is the multi-page demo B (`casinos-b-xx`) flattened: its home, comparison and method copy on one page, with the
same variations (product, ranking focus, layout).
