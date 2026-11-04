# Site type: small brand review site (`reviews`)

Status: **Phase 7: Demos A, B, C (sample reviews).** Brief: [docs/briefs/07-brand-review-ugc.md](../briefs/07-brand-review-ugc.md) ·
Blueprint: [blueprints/reviews.json](../../blueprints/reviews.json) · Schema: `dist/schemas/reviews.schema.json`

> **Seeded reviews are demo-only.** A site whose feed contains `source: "seed"` reviews builds only with `"demo": true`
> (set `"sample_notice": true` to show a sample-content banner; the demo gallery leaves it off). Production sites need real reviews.

| Demo | Config | Review link (all pages clickable) |
|---|---|---|
| A · Nyxa · KZ · RU · payout-focus · 5 pages · 24 sample reviews | [sites/demos/reviews-a-nyxa-kz.json](../../sites/demos/reviews-a-nyxa-kz.json) | https://claude.ai/artifact/T9F1WiTpA4z6odfmXbP1NB |
| B · Marlowe Club · BR · PT · support-focus · band hero · 6 pages · 24 sample reviews | [sites/demos/reviews-b-marlowe-br.json](../../sites/demos/reviews-b-marlowe-br.json) | https://claude.ai/artifact/UYKvJJK3tJTuA6uJopVfos |
| C · Vantro · AU · EN · balanced · quotes hero · 5 pages · 24 sample reviews | [sites/demos/reviews-c-vantro-au.json](../../sites/demos/reviews-c-vantro-au.json) | https://claude.ai/artifact/DaYPRhsDgSP4LvSZVN5TG1 |

**Editor box and sharing.** Every page except the review form ends with an `editor-box`: the person who maintains and
moderates the site (`"editor": {name, role, bio}` in the site config), the last update and a link to the moderation
policy, followed by share links (same plain links as the news type).

**Image mode.** `"images": true` adds pictures: thumbnails on article and link cards, the lead story's picture, a hero
picture on single articles and on text pages, and reviewer avatars on review cards. An item's own `image` (URL, with
`image_alt`) is used when present; otherwise a generated cover (inline SVG in the site's colour tokens, seeded layout,
a pictogram picked from the title: football, hockey, payments, phone, licence, odds, limits, casino, chat, star) stands
in. The review link of the Russian demo contains both modes (`img-*.html`), with a bar on top that switches the page.

## 1. What the site is for

Players reviewing one brand, styled like a review platform. Visitors are sceptical, so honesty is the conversion
strategy: every published review shown newest first, scores computed from the feed, the CTA next to the evidence.

## 2. Data

| Data | Lives in | Who maintains it |
|---|---|---|
| Reviews: id, source (`seed`, `site-form`, `import`), status (`pending`, `published`, `rejected`), market, locale, name, city, date, rating 1–5, topics, title, text, optional `reply` {date, text} | `brands/reviews/<brand>.json` | Moderators (production), us (demo seeds) |
| Topics: payouts, verification, support, app, odds, deposits | `SX_REVIEW_TOPICS` in [lib/reviews.php](../../lib/reviews.php) + `reviews.topic.*` strings | Fixed |
| Headlines, FAQ, moderation policy, "what happens next" | site config `content` | Content writer |

Only `published` reviews for the site's market appear. Averages, counts, the 5→1 distribution and topic scores are
computed by `sx_review_stats()` / `sx_topic_stats()`; nothing about scores is typed into a config.

## 3. Pages

| Page | Slug | Rule | Sections |
|---|---|---|---|
| home | `/` | required | rating-summary `board` (score, count, lead topic, CTA, distribution), `quotes` (score card plus the latest praise and the latest complaint) or `band` (score, stacked 5-to-1 bar, topic scores, CTA in one row), trust, topic-scores, latest 6 reviews, faq, final |
| reviews | `/reviews/`, `/reviews/2/`… | required, paged | rating-summary `strip`, review-list with filters (CTA after every 6), pagination, related, final |
| payouts / support | `/payout-reviews/`, `/payout-reviews/2/`… | angle, paged | rating-summary for that topic, topic review list, pagination, prose (opt.), related, final |
| write | `/write-a-review/` | required | page-hero (no CTA), review-form, "what happens next", related; no sticky CTA |
| policy | `/how-we-moderate/` | required (footer + disclosure link, not in header) | page-hero, prose, related; no sticky CTA |

**Paging.** Review lists show 15 per page (`"paginate"` in the blueprint; override per site with
`"paging": {"reviews": 10}`). Page n gets slug `reviews/n` (`/reviews/2/`, or `reviews-2.html` with `files` URLs),
its own canonical URL, "· page n" added to title and description, breadcrumbs Home / Reviews / Page n, and a place in
the sitemap. Page 2+ is never in the header, footer or page-link cards; the pagination bar links it. Filters apply to
the reviews on the current page (the page says so); topic pages are the way to see one topic across all reviews.
15 per page keeps a page under 60 KB with longer real reviews (each card adds about 0.7–1.2 KB to a ~41 KB page).

Every page: compliance strip (if the market needs it), the **disclosure line** with a link to the policy, header, footer.

| Angle | Home lead topic | Topic page |
|---|---|---|
| payout-focus | payouts | payout-reviews |
| balanced | none | none (page links on home) |
| support-focus | support | support-reviews |

## 4. Build a review site

1. Fill `brands/reviews/<brand>.json` with real, moderated reviews (`status: published`). For a design demo, seed reviews
   with `source: "seed"`, at least 20% at 1–3 stars, and set `"demo": true` in the config.
2. Copy [sites/templates/reviews.json](../../sites/templates/reviews.json); set brand, market, locale, preset, angle, seed, `site.url`.
3. Fill `meta.<page>` and `content`. Never write scores or counts into copy; the blocks compute them.
4. Review collection: set `links.review_form` to the endpoint that stores submissions (WordPress or a form service).
   Without it, or on a demo, the form renders but its submit button is disabled.
5. `php build/build.php sites/<project>/<id>.json`; review link: `php build/export-review-site.php sites/<project>/<id>.json "Title"`.

## 5. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Seeded reviews without `"demo": true` | **Build fails** |
| No published reviews for the brand in the market | **Build fails** |
| Seeded reviews with under 20% at 1–3 stars | Warning |
| Unknown review topic | Warning |
| Sample banner on every page of a demo | Forced by the builder |
| Disclosure line + policy link on every page | Frame block `disclosure` |
| No `AggregateRating` / `Review` structured data | Not implemented by design |
| Newest first, no hidden low ratings, CTA never inside a review card | `review-list` |
| Filters work without JavaScript | Radio buttons + CSS `:has()`; a 3-line script only pre-selects a topic from `#t-<topic>` |
| Form asks for a display name only, needs the age and moderation consent, no incentives | `review-form` |

## 6. Known gaps

- Filters work per page on static sites. Cross-page filtering needs the WordPress version to filter server-side, or a
  topic page per topic.
- Review collection backend (brief 07, question 1), moderation owner and SLA (question 2).
- Imports from app stores or review platforms (question 4).
- Three home heroes (`board`, `quotes`, `band`) cap one project at three review sites under the diversity rule.

**Variation demo (Russian, KZ):** `php build/export-review-site.php sites/demos/reviews-a-nyxa-kz.json` builds images (off / on) × list layout (cards / compact / verified; verified shows a score column, a "verified player" badge, topic chips and the brand reply). Negative reviews stay visible in every layout. Review link: https://claude.ai/artifact/T9F1WiTpA4z6odfmXbP1NB.
